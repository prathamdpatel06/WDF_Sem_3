<?php
// ============================================================
// db.php - opens the MySQL connection and provides small helpers
// that always use PREPARED statements (protection against SQL injection).
// ============================================================
require_once __DIR__ . '/config.php';

// Throw exceptions on database errors so they can be caught and shown nicely.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die('<h1>Database connection failed</h1>'
        . '<p>1) Start MySQL in XAMPP. 2) Import database/schema.sql in phpMyAdmin. '
        . '3) Check DB_USER / DB_PASS in includes/config.php.</p>'
        . '<p><small>' . htmlspecialchars($e->getMessage()) . '</small></p>');
}

/** Run a SELECT and return all rows. $types is a bind_param string such as "si". */
function db_fetch_all(mysqli $conn, string $sql, string $types = '', array $params = []): array {
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** Run a SELECT and return the first row (or null). */
function db_fetch_one(mysqli $conn, string $sql, string $types = '', array $params = []): ?array {
    $rows = db_fetch_all($conn, $sql, $types, $params);
    return $rows[0] ?? null;
}

/** Run INSERT / UPDATE / DELETE and return the number of affected rows. */
function db_execute(mysqli $conn, string $sql, string $types = '', array $params = []): int {
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}
