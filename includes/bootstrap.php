<?php
// ============================================================
// bootstrap.php - loaded at the top of EVERY page and API file.
// It sets up the database, session, remember-me login and the timeout / account checks.
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/students.php';

start_session_safely();
try_remember_me_login($conn);
enforce_session_rules($conn);
