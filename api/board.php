<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) && sameOrigin($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'null'));
function sameOrigin(string $origin): bool { $host=parse_url($origin,PHP_URL_HOST); return is_string($host) && strcasecmp($host,(string)($_SERVER['HTTP_HOST']??''))===0; }
function fail(int $code,string $message): never { http_response_code($code); echo json_encode(['error'=>$message]); exit; }
function body(): array { $raw=file_get_contents('php://input'); if(strlen($raw)>4500000) fail(413,'Request too large'); $v=json_decode($raw,true); if(!is_array($v)) fail(400,'Invalid JSON'); return $v; }
function validHex($v,int $size): bool { return is_string($v) && (bool)preg_match('/^[a-f0-9]{'.$size.'}$/D',$v); }
$action=(string)($_GET['action']??'');
$dir=dirname(__DIR__).'/storage';
if(!is_dir($dir) && !mkdir($dir,0700,true)) fail(500,'Storage unavailable');
if($action==='create') {
 if($_SERVER['REQUEST_METHOD']!=='POST') fail(405,'POST required');
 $id=bin2hex(random_bytes(10)); $key=bin2hex(random_bytes(24));
 $record=['hash'=>hash('sha256',$key),'sequence'=>0,'ops'=>[],'created'=>time()];
 if(file_put_contents($dir.'/'.$id.'.json',json_encode($record),LOCK_EX)===false) fail(500,'Unable to create board');
 echo json_encode(['room'=>$id,'key'=>$key,'sequence'=>0]); exit;
}
$room=(string)($_GET['room']??''); $key=(string)($_SERVER['HTTP_X_BOARD_KEY']??'');
if(!validHex($room,20)||!validHex($key,48)) fail(400,'Invalid board link');
$path=$dir.'/'.$room.'.json'; $f=@fopen($path,'c+'); if(!$f) fail(404,'Board unavailable');
if(!flock($f,LOCK_EX)) fail(500,'Board busy');
$raw=stream_get_contents($f); $state=json_decode($raw,true);
if(!is_array($state)||!isset($state['hash'])) fail(404,'Board not found');
if(!hash_equals($state['hash'],hash('sha256',$key))) fail(403,'Invalid invitation link');
if($action==='get') {
 $since=max(0,(int)($_GET['since']??0));
 $ops=array_values(array_filter($state['ops'],static fn($op)=>($op['seq']??0)>$since));
 echo json_encode(['sequence'=>$state['sequence'],'ops'=>$ops],JSON_UNESCAPED_SLASHES); exit;
}
if($action==='append') {
 if($_SERVER['REQUEST_METHOD']!=='POST') fail(405,'POST required');
 $input=body(); $ops=$input['ops']??null;
 if(!is_array($ops)||count($ops)>30||count($ops)<1) fail(400,'Invalid operations');
 foreach($ops as $op) {
  if(!is_array($op)||!in_array($op['type']??'', ['upsert','delete','clear'],true)) fail(400,'Invalid operation type');
  if(($op['type']??'')==='upsert') {
   $obj=$op['object']??null;
   if(!is_array($obj)||!preg_match('/^[a-zA-Z0-9_-]{5,60}$/D',(string)($obj['id']??''))||!in_array($obj['type']??'', ['stroke','rect','ellipse','text','note','arrow','image'],true)) fail(400,'Invalid board object');
   if(strlen(json_encode($obj))>2900000) fail(413,'Object too large');
  }
  if(($op['type']??'')==='delete' && !preg_match('/^[a-zA-Z0-9_-]{5,60}$/D',(string)($op['id']??''))) fail(400,'Invalid object ID');
  $op['seq']=++$state['sequence']; $state['ops'][]=$op;
 }
 if(count($state['ops'])>25000) fail(507,'Board operation limit reached; export and create a new board');
 rewind($f); ftruncate($f,0); if(fwrite($f,json_encode($state,JSON_UNESCAPED_SLASHES))===false) fail(500,'Save failed'); fflush($f);
 echo json_encode(['sequence'=>$state['sequence']]); exit;
}
fail(404,'Unknown action');
