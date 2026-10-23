<?php
// ============================================================
// auth.php - sessions, login state, remember-me, timeout and role checks.
// Every protected page calls require_login() or require_role().
// ============================================================

/** Start the session safely and make sure a CSRF token exists. */
function start_session_safely(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_role(): string {
    return $_SESSION['role'] ?? 'guest';
}

function current_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function current_full_name(): string {
    return $_SESSION['full_name'] ?? 'Guest';
}

/** Where each role lands after login or when they hit a page they may not open. */
function home_for_role(string $role): string {
    return $role === 'admin' ? 'admin_dashboard.php' : 'dashboard.php';
}

/** Store the user in the session. New session ID prevents session-fixation attacks. */
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/** Destroy the session and the remember-me cookie/token. */
function logout_user(mysqli $conn): void {
    if (!empty($_COOKIE[REMEMBER_COOKIE])) {
        db_execute($conn, "DELETE FROM remember_tokens WHERE token_hash = ?", 's',
            [hash('sha256', $_COOKIE[REMEMBER_COOKIE])]);
    }
    setcookie(REMEMBER_COOKIE, '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    $_SESSION = [];
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    session_destroy();
}

/** Create a random remember-me token. Only its SHA-256 hash is stored in the database. */
function create_remember_token(mysqli $conn, int $user_id): void {
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + REMEMBER_DAYS * 86400);
    db_execute($conn, "INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)",
        'iss', [$user_id, hash('sha256', $token), $expires_at]);
    setcookie(REMEMBER_COOKIE, $token, ['expires' => time() + REMEMBER_DAYS * 86400, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
}

/** If the remember-me cookie is valid, log the user in automatically. */
function try_remember_me_login(mysqli $conn): void {
    if (is_logged_in() || empty($_COOKIE[REMEMBER_COOKIE])) {
        return;
    }
    $token = $_COOKIE[REMEMBER_COOKIE];
    if (!ctype_xdigit($token) || strlen($token) !== 64) {
        return;   // not a token we created
    }
    $user = db_fetch_one($conn,
        "SELECT u.id, u.username, u.full_name, u.role, u.is_active
         FROM remember_tokens t JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = ? AND t.expires_at > NOW()",
        's', [hash('sha256', $token)]);
    if ($user && (int)$user['is_active'] === 1) {
        login_user($user);
    }
}

/**
 * Runs on every request:
 *  - logs out users who were idle longer than SESSION_TIMEOUT
 *  - logs out deactivated accounts
 *  - refreshes role and name from the database so admin changes apply at once
 */
function enforce_session_rules(mysqli $conn): void {
    if (!is_logged_in()) {
        return;
    }
    $is_api = defined('IS_API');

    if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
        logout_user($conn);
        if ($is_api) {
            json_response(false, 'Your session expired. Please log in again.', [], 401);
        }
        redirect('login.php?error=timeout');
    }

    $row = db_fetch_one($conn, "SELECT role, full_name, is_active FROM users WHERE id = ?", 'i', [current_user_id()]);
    if (!$row || (int)$row['is_active'] !== 1) {
        logout_user($conn);
        if ($is_api) {
            json_response(false, 'Your account is not active.', [], 403);
        }
        redirect('login.php?error=deactivated');
    }
    $_SESSION['role'] = $row['role'];
    $_SESSION['full_name'] = $row['full_name'];
    $_SESSION['last_activity'] = time();
}

/** Page guard: the visitor must be logged in. */
function require_login(): void {
    if (!is_logged_in()) {
        $page = basename($_SERVER['PHP_SELF']);
        redirect('login.php?error=login_required&next=' . urlencode($page));
    }
}

/** Page guard: logged in AND has the given role ('student' or 'admin'). */
function require_role(string $role): void {
    require_login();
    if (current_role() !== $role) {
        set_flash('danger', 'Access denied: you do not have permission to open that page.');
        redirect(home_for_role(current_role()));
    }
}

/** Guard for guest-only pages (login, register). */
function redirect_if_logged_in(): void {
    if (is_logged_in()) {
        redirect(home_for_role(current_role()));
    }
}

/** API guard: returns JSON instead of redirecting. */
function api_require_role(string $role): void {
    if (!is_logged_in()) {
        json_response(false, 'Please log in first.', [], 401);
    }
    if (current_role() !== $role) {
        json_response(false, 'You do not have permission for this action.', [], 403);
    }
}

/** Hidden input that every POST form must contain. */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}

/** Check the token sent with a form or API request. */
function csrf_is_valid(?string $token): bool {
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
