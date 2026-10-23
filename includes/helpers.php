<?php
// ============================================================
// helpers.php - small reusable functions used on every page.
// ============================================================

/** Escape text before printing it in HTML (prevents XSS attacks). */
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Send the browser to another page and stop this script. */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/** Store a one-time message that is shown on the next page (type: success, danger, warning, info). */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Read and clear all pending flash messages. */
function get_flashes(): array {
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

/** Send a JSON response (used by the api/ files) and stop. */
function json_response(bool $success, string $message, array $extra = [], int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

/** Record an important action in the audit_logs table (Practical 14). */
function audit_log(mysqli $conn, string $action, string $details = '', ?int $user_id = null, ?string $username = null): void {
    $user_id = $user_id ?? (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null);
    $username = $username ?? ($_SESSION['username'] ?? 'guest');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    try {
        db_execute($conn,
            "INSERT INTO audit_logs (user_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)",
            'issss', [$user_id, $username, $action, $details, $ip]);
    } catch (Throwable $e) {
        error_log('Audit log failed: ' . $e->getMessage());   // logging must never break the page
    }
}

/** Append one row to a CSV file (creates the file and header row the first time). */
function append_csv(string $file, array $row): void {
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0755, true);
    }
    clearstatcache();
    $needs_header = !file_exists($file) || filesize($file) === 0;
    $handle = fopen($file, 'a');
    if ($handle === false) {
        throw new RuntimeException('Could not open the CSV file for writing.');
    }
    flock($handle, LOCK_EX);
    if ($needs_header) {
        fputcsv($handle, array_keys($row));
    }
    fputcsv($handle, $row);
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
}

/** Format a yyyy-mm-dd date as "05 Nov 2026". */
function format_date(?string $date): string {
    return $date ? date('d M Y', strtotime($date)) : '-';
}

/** Saves a validated poster under a random file name and returns its relative path. */
function save_uploaded_poster(array $file): string {
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extension = ALLOWED_POSTER_TYPES[$mime];
    $file_name = 'poster_' . bin2hex(random_bytes(8)) . '.' . $extension;   // random name = safer
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $file_name)) {
        throw new RuntimeException('The poster could not be saved on the server.');
    }
    return 'uploads/posters/' . $file_name;
}

/** Deletes an uploaded poster. Seeded sample images (assets/...) are never deleted. */
function delete_uploaded_poster(?string $path): void {
    if ($path && str_starts_with($path, 'uploads/posters/')) {
        $file = UPLOAD_DIR . basename($path);
        if (is_file($file)) {
            unlink($file);
        }
    }
}
