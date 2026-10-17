# Whiteboard Studio — Study & Team

A small, dependency-free interactive whiteboard for laptops, desktops, mobile phones, tablets and touchscreens. Runs with PHP 8+ on shared Apache/cPanel hosting, with no database and no Node server.

## Install on cPanel

1. Create the HTTPS subdomain `whiteboard.63.lv` and note its document root.
2. Upload **the contents** of this ZIP (not the parent directory) into that document root.
3. Use cPanel File Manager to give the `storage` directory write permission to PHP (usually 755; set 775 only if required by your host). Never use 777.
4. Visit `https://whiteboard.63.lv/` and test a local board. Click **Share board** to create a cloud room and copy the URL including its `#key=...` fragment.
5. Open the link in a second browser/device. Both users can draw on the same board; updates are synchronized by polling approximately every 2.2 seconds.
6. **Install** on supported Android browsers or Chrome/Edge desktop. On iPhone/iPad use Safari's Share → Add to Home Screen. Use HTTPS for PWA installation.

## Key features

- Pen, highlighter, eraser, rectangle, ellipse, arrow, text, sticky notes, image upload (PNG/JPG/WebP/GIF under 350 KB)
- Mouse, touch and pointer/stylus drawing; keyboard shortcuts; wheel zoom; hand pan; selection and moving objects
- Undo/redo (local session), save local board in browser storage, import/export editable JSON, export PNG
- Shared multi-user boards: unique random invitation URL with independent secret key; server-side append-only operation log in JSON files
- Web app manifest and offline assets cache; local boards can be used offline after initial visit

## Important limitations and security

- **Shared boards need a PHP host and network connection.** Static hosting alone supports local boards but not collaboration.
- Invitation links grant **edit** access. Treat them as secrets; no user accounts, role controls or password revocation in this first version.
- On shared boards, changes sync in near-real time using periodic HTTP polling, not WebSockets. If multiple users change exactly the same object simultaneously, the most recently received operation wins. Undo/redo is a local action that can affect shared objects; use with care.
- `storage/.htaccess` denies direct web access on Apache. If deployed on Nginx or a server ignoring `.htaccess`, **move storage outside the web root and adjust `$dir` in `api/board.php`**, or configure a deny location; do not expose stored JSON files.
- `storage/` contains user content. Back it up and limit HTTP request and file storage usage. No built-in deletion/retention schedule. A room is limited to 25,000 operations; export and start a fresh room when full.
- Uploaded raster images are stored in board state as data URLs. Keep images small. Do not upload sensitive content to an untrusted host.
- Board title is local to each browser; it is not synchronized between users.
- HTTPS is required for PWA installation and clipboard permissions on most browsers. Apple installation uses browser Add to Home Screen, not a signed App Store binary.

## Developer smoke test

Run `php -S 127.0.0.1:8080` from the project directory. Create a room using POST `/api/board.php?action=create`; send the returned key in header `X-Board-Key` to read or append. `php -l api/board.php` and `node --check assets/app.js` validate syntax. For production, use PHP/Apache with TLS.

## Files

- `index.html` responsive web app
- `assets/app.css` UI
- `assets/app.js` canvas drawing and synchronization
- `api/board.php` JSON room API
- `storage/.htaccess` stored board protection
- `sw.js` PWA offline shell
- `manifest.webmanifest` install metadata


## Version 1.1 improvements
- Clear All clears local or shared room and can be undone via the in-session undo action.
- Up to 2 MiB original image upload (server-side board object and POST limits increased). Large boards can use significant disk space; PHP application limits may need adjusting.
- Add a text box, edit it inline by double-clicking, drag it in select mode. Selected text formatting panel supports font size, face, bold, italic, color, rotation. Resize selected text with Alt + arrow keys; rotate in 5 degree steps with Alt + bracket keys. Inline editor also has resize corner.
- PDF: Export > Export formatted PDF opens a browser print view; choose Save as PDF. Enable pop-ups if blocked.
- 21 language selections: translated Clear All and DigitalPulse ad text. Other legacy interface strings remain English pending full localization.
- NOTE: Collaborator links confer full edit access; do not share publicly.

## v1.2 language patch
Language picker displays national flags (options have native tooltip language names). Latvian translations cover the visible UI and dynamically opened dialogs. Other languages retain their existing partial translations; no claim of full translations for those languages. Replace assets/app.js, assets/app.css, assets/i18n.js, index.html and sw.js. Clear browser cache or reload twice if an old service worker is active.


## v1.3 additions
- Sticky notes: double click to edit, drag to move, colors and text formatting.
- Simple music staff object; double click to add pitched notes and durations (not full Music Writer integration).
- Extra widgets: timer, clock, dice, polls, maths, link labels, lined/grid background.
- Sidebar links to music-writer, book, translate and language apps.
- Existing shared board storage is not included. Back it up and preserve it on deployment.


### v1.5 UI update
- Language dropdown uses SVG flags and localized language names; hidden original selector preserves translation events.
- Brand icon replaced throughout header and PWA asset.
- Existing PHP board storage and sharing unchanged.
