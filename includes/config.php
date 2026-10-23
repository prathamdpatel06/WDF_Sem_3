<?php
// ============================================================
// config.php - the ONLY file you normally need to edit.
// Change the database details to match your XAMPP / MySQL setup.
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');              // XAMPP default is an empty password
define('DB_NAME', 'studenthub_db');

define('APP_NAME', 'StudentHUB');
define('SESSION_TIMEOUT', 900);      // 900 seconds = 15 minutes of inactivity
define('REMEMBER_DAYS', 7);          // "Remember me" lasts 7 days
define('REMEMBER_COOKIE', 'studenthub_remember');

define('MAX_POSTER_SIZE', 2 * 1024 * 1024);   // 2 MB
define('ALLOWED_POSTER_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
]);

define('UPLOAD_DIR', __DIR__ . '/../uploads/posters/');
define('STORAGE_DIR', __DIR__ . '/../storage/');

date_default_timezone_set('Asia/Kolkata');
