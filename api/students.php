<?php
// api/students.php - ADMIN only: enable/disable or delete a student without reloading (Practical 13 + 14).
// POST JSON body: {"action":"toggle"|"delete", "id": <students.id>} + header X-CSRF-Token
define('IS_API', true);
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed. Use POST.', [], 405);
}
api_require_role('admin');

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    $body = $_POST;
}
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf_token'] ?? null);
if (!csrf_is_valid($token)) {
    json_response(false, 'Security check failed. Please refresh the page and try again.', [], 403);
}

$action = $body['action'] ?? '';
$id     = (int)($body['id'] ?? 0);
$student = db_fetch_one($conn,
    "SELECT s.id, s.user_id, u.username, u.full_name, u.is_active
     FROM students s JOIN users u ON u.id = s.user_id WHERE s.id = ?", 'i', [$id]);
if (!$student) {
    json_response(false, 'Student not found.', [], 404);
}

try {
    if ($action === 'toggle') {
        $new_state = (int)$student['is_active'] === 1 ? 0 : 1;
        db_execute($conn, "UPDATE users SET is_active = ? WHERE id = ?", 'ii', [$new_state, (int)$student['user_id']]);
        audit_log($conn, $new_state ? 'STUDENT_ACTIVATED' : 'STUDENT_DEACTIVATED', $student['username']);
        set_flash('success', $student['full_name'] . ($new_state ? ' was activated.' : ' was deactivated.'));
        json_response(true, $new_state ? 'Student activated.' : 'Student deactivated.', ['is_active' => (bool)$new_state]);
    }

    if ($action === 'delete') {
        // Deleting the user also deletes the student row and registrations (ON DELETE CASCADE)
        db_execute($conn, "DELETE FROM users WHERE id = ? AND role = 'student'", 'i', [(int)$student['user_id']]);
        audit_log($conn, 'STUDENT_DELETED', 'Deleted student ' . $student['username']);
        set_flash('success', 'Student ' . $student['full_name'] . ' was deleted.');
        json_response(true, 'Student deleted.');
    }
} catch (Throwable $e) {
    error_log('Admin students API error: ' . $e->getMessage());
    json_response(false, 'The change could not be saved. Please try again.', [], 500);
}

json_response(false, 'Unknown action.', [], 400);
