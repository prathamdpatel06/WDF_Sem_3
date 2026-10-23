<?php
// logout.php - ends the session, removes the remember-me token and returns to the login page.
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    audit_log($conn, 'USER_LOGOUT', 'User logged out');
    logout_user($conn);
}
redirect('login.php?message=logged_out');
