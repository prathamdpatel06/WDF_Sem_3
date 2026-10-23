<?php
// api/registrations.php - students register for / cancel events WITHOUT reloading the page (Practical 13).
// POST JSON body: {"action":"register"|"cancel", "id": <event id>}  + header X-CSRF-Token
define('IS_API', true);
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed. Use POST.', [], 405);
}
api_require_role('student');

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    $body = $_POST;
}
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf_token'] ?? null);
if (!csrf_is_valid($token)) {
    json_response(false, 'Security check failed. Please refresh the page and try again.', [], 403);
}

$action   = $body['action'] ?? '';
$event_id = (int)($body['id'] ?? 0);

$student = db_fetch_one($conn, "SELECT id FROM students WHERE user_id = ?", 'i', [current_user_id()]);
if (!$student) {
    json_response(false, 'Your student profile was not found.', [], 404);
}
$student_id = (int)$student['id'];

// Results are decided inside the try block and sent after the transaction ends.
$result = ['ok' => false, 'message' => 'Unknown action.', 'code' => 400];

try {
    if ($action === 'register') {
        $conn->begin_transaction();
        // FOR UPDATE locks the event row so two students cannot grab the last seat together
        $event = db_fetch_one($conn, "SELECT id, title, seats_limit, status, event_date FROM events WHERE id = ? FOR UPDATE", 'i', [$event_id]);
        if (!$event) {
            $result = ['ok' => false, 'message' => 'Event not found.', 'code' => 404];
            $conn->rollback();
        } elseif ($event['status'] !== 'Open') {
            $result = ['ok' => false, 'message' => 'Registration is closed for this event.', 'code' => 409];
            $conn->rollback();
        } elseif ($event['event_date'] < date('Y-m-d')) {
            $result = ['ok' => false, 'message' => 'This event has already taken place.', 'code' => 409];
            $conn->rollback();
        } else {
            $existing = db_fetch_one($conn, "SELECT status FROM registrations WHERE student_id = ? AND event_id = ? FOR UPDATE", 'ii', [$student_id, $event_id]);
            $booked = (int)db_fetch_one($conn, "SELECT COUNT(*) AS c FROM registrations WHERE event_id = ? AND status = 'Confirmed'", 'i', [$event_id])['c'];

            if ($existing && $existing['status'] === 'Confirmed') {
                $result = ['ok' => false, 'message' => 'You are already registered for this event.', 'code' => 409];
                $conn->rollback();
            } elseif ($booked >= (int)$event['seats_limit']) {
                $result = ['ok' => false, 'message' => 'Sorry, this event is full.', 'code' => 409];
                $conn->rollback();
            } else {
                // Insert, or re-activate an old cancelled registration
                db_execute($conn,
                    "INSERT INTO registrations (student_id, event_id, status) VALUES (?, ?, 'Confirmed')
                     ON DUPLICATE KEY UPDATE status = 'Confirmed', registered_at = CURRENT_TIMESTAMP",
                    'ii', [$student_id, $event_id]);
                $conn->commit();
                audit_log($conn, 'EVENT_REGISTERED', 'Registered for: ' . $event['title']);
                $result = ['ok' => true, 'message' => 'You are registered for ' . $event['title'] . '.', 'code' => 200];
            }
        }
    } elseif ($action === 'cancel') {
        $affected = db_execute($conn,
            "UPDATE registrations SET status = 'Cancelled' WHERE student_id = ? AND event_id = ? AND status = 'Confirmed'",
            'ii', [$student_id, $event_id]);
        if ($affected === 0) {
            $result = ['ok' => false, 'message' => 'You are not registered for this event.', 'code' => 404];
        } else {
            $title = db_fetch_one($conn, "SELECT title FROM events WHERE id = ?", 'i', [$event_id])['title'] ?? 'event';
            audit_log($conn, 'EVENT_CANCELLED', 'Cancelled registration: ' . $title);
            $result = ['ok' => true, 'message' => 'Your registration was cancelled.', 'code' => 200];
        }
    }
} catch (Throwable $e) {
    try {
        $conn->rollback();   // undo any half-finished change
    } catch (Throwable $ignored) {
    }
    error_log('Registration API error: ' . $e->getMessage());
    $result = ['ok' => false, 'message' => 'Something went wrong. Please try again.', 'code' => 500];
}

json_response($result['ok'], $result['message'], [], $result['code']);
