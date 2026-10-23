<?php
// api/events.php - PUBLIC read-only JSON API for events (Practical 13).
//   GET api/events.php?action=list&q=&category=&status=&upcoming=1&page=1&limit=6
//   GET api/events.php?action=detail&id=3
define('IS_API', true);
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, 'Method not allowed. Use GET.', [], 405);
}

// Logged-in students get "is_registered" for each event
$student_id = 0;
if (is_logged_in() && current_role() === 'student') {
    $row = db_fetch_one($conn, "SELECT id FROM students WHERE user_id = ?", 'i', [current_user_id()]);
    $student_id = (int)($row['id'] ?? 0);
}

$action = $_GET['action'] ?? 'list';

// ---------- one event ----------
if ($action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    $event = db_fetch_one($conn, "SELECT id, title, category, event_date, location, seats_limit, description, poster, status FROM events WHERE id = ?", 'i', [$id]);
    if (!$event) {
        json_response(false, 'Event not found.', [], 404);
    }
    json_response(true, 'Event loaded.', ['data' => ['event' => $event]]);
}

if ($action !== 'list') {
    json_response(false, 'Unknown action.', [], 400);
}

// ---------- list with search, filter and pagination ----------
$q        = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$status   = $_GET['status'] ?? '';
$upcoming = ($_GET['upcoming'] ?? '') === '1';
$limit    = min(max((int)($_GET['limit'] ?? 6), 1), 50);
$page     = max((int)($_GET['page'] ?? 1), 1);

$where = []; $types = ''; $params = [];
if ($q !== '') {
    $where[] = '(e.title LIKE ? OR e.location LIKE ?)';
    $types .= 'ss';
    array_push($params, "%$q%", "%$q%");
}
if (in_array($category, EVENT_CATEGORIES, true)) {
    $where[] = 'e.category = ?';
    $types .= 's';
    $params[] = $category;
}
if (in_array($status, EVENT_STATUSES, true)) {
    $where[] = 'e.status = ?';
    $types .= 's';
    $params[] = $status;
}
if ($upcoming) {
    $where[] = 'e.event_date >= CURDATE()';
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int)db_fetch_one($conn, "SELECT COUNT(*) AS c FROM events e $where_sql", $types, $params)['c'];
$pages = max(1, (int)ceil($total / $limit));
$page  = min($page, $pages);
$offset = ($page - 1) * $limit;

// The first "?" belongs to the is_registered sub-query, so it goes first in the bind list.
$rows = db_fetch_all($conn,
    "SELECT e.id, e.title, e.category, e.event_date, e.location, e.seats_limit, e.description, e.poster, e.status,
        (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status = 'Confirmed') AS booked,
        (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.student_id = ? AND r.status = 'Confirmed') AS is_registered
     FROM events e $where_sql
     ORDER BY e.event_date ASC, e.id ASC
     LIMIT ? OFFSET ?",
    'i' . $types . 'ii', [$student_id, ...$params, $limit, $offset]);

$items = array_map(function (array $r): array {
    $seats = (int)$r['seats_limit'];
    $booked = (int)$r['booked'];
    return [
        'id' => (int)$r['id'],
        'title' => $r['title'],
        'category' => $r['category'],
        'event_date' => $r['event_date'],
        'location' => $r['location'],
        'seats_limit' => $seats,
        'seats_left' => max(0, $seats - $booked),
        'description' => $r['description'] ?? '',
        'poster' => $r['poster'],
        'status' => $r['status'],
        'is_registered' => (int)$r['is_registered'] > 0,
        'is_past' => $r['event_date'] < date('Y-m-d'),
    ];
}, $rows);

json_response(true, 'Events loaded.', ['data' => [
    'items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'limit' => $limit,
]]);
