<?php
// admin_dashboard.php - statistics, latest registrations, feedback and the audit log. Admins only.
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$page_title  = 'Admin Dashboard | StudentHUB';
$breadcrumbs = [['index.php', 'Home'], ['admin_dashboard.php', 'Admin Dashboard']];

$stats = [
    'students'      => db_fetch_one($conn, "SELECT COUNT(*) AS c FROM students")['c'],
    'active'        => db_fetch_one($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'student' AND is_active = 1")['c'],
    'events'        => db_fetch_one($conn, "SELECT COUNT(*) AS c FROM events")['c'],
    'upcoming'      => db_fetch_one($conn, "SELECT COUNT(*) AS c FROM events WHERE event_date >= CURDATE()")['c'],
    'registrations' => db_fetch_one($conn, "SELECT COUNT(*) AS c FROM registrations WHERE status = 'Confirmed'")['c'],
    'feedback'      => db_fetch_one($conn, "SELECT COUNT(*) AS c FROM feedback")['c'],
];

// Latest registrations (dynamic data from several tables)
$latest = db_fetch_all($conn,
    "SELECT r.registered_at, r.status, u.full_name, s.student_code, e.title
     FROM registrations r
     JOIN students s ON s.id = r.student_id
     JOIN users u ON u.id = s.user_id
     JOIN events e ON e.id = r.event_id
     ORDER BY r.registered_at DESC, r.id DESC LIMIT 8");

// Confirmed registrations per category (simple analytics chart, Practical 14 extension)
$by_category = db_fetch_all($conn,
    "SELECT e.category, COUNT(*) AS total
     FROM registrations r JOIN events e ON e.id = r.event_id
     WHERE r.status = 'Confirmed' GROUP BY e.category ORDER BY total DESC");
$max_total = max(1, ...array_map(fn($row) => (int)$row['total'], $by_category ?: [['total' => 1]]));

$audit = db_fetch_all($conn,
    "SELECT username, action, details, ip_address, created_at FROM audit_logs ORDER BY created_at DESC, id DESC LIMIT 12");

$feedback = db_fetch_all($conn,
    "SELECT f.feedback_type, f.rating, f.comments, f.created_at, u.full_name
     FROM feedback f JOIN users u ON u.id = f.user_id ORDER BY f.created_at DESC LIMIT 5");

require_once __DIR__ . '/includes/header.php';
?>

<h1>Admin Dashboard</h1>
<p class="muted">Signed in as <?php echo e(current_full_name()); ?>. Numbers update automatically from the database.</p>

<div class="grid-3 mt-2">
  <article class="stat-card"><h2>Students</h2><p class="stat-number"><?php echo (int)$stats['students']; ?></p><small><?php echo (int)$stats['active']; ?> active</small></article>
  <article class="stat-card"><h2>Events</h2><p class="stat-number"><?php echo (int)$stats['events']; ?></p><small><?php echo (int)$stats['upcoming']; ?> upcoming</small></article>
  <article class="stat-card"><h2>Confirmed registrations</h2><p class="stat-number"><?php echo (int)$stats['registrations']; ?></p><small><?php echo (int)$stats['feedback']; ?> feedback messages</small></article>
</div>

<div class="grid-2 section">
  <section class="card" aria-labelledby="latest-heading">
    <h2 id="latest-heading">Latest registrations</h2>
    <?php if (empty($latest)): ?>
      <p class="empty-state">No registrations yet.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th scope="col">Student</th><th scope="col">Event</th><th scope="col">Status</th><th scope="col">When</th></tr></thead>
          <tbody>
            <?php foreach ($latest as $row): ?>
              <tr>
                <td><?php echo e($row['full_name']); ?><br><small class="muted"><?php echo e($row['student_code']); ?></small></td>
                <td><?php echo e($row['title']); ?></td>
                <td><span class="badge badge-<?php echo $row['status'] === 'Confirmed' ? 'ok' : 'off'; ?>"><?php echo e($row['status']); ?></span></td>
                <td><?php echo e(date('d M, h:i A', strtotime($row['registered_at']))); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="card" aria-labelledby="category-heading">
    <h2 id="category-heading">Registrations by category</h2>
    <?php if (empty($by_category)): ?>
      <p class="empty-state">No data yet.</p>
    <?php else: ?>
      <ul class="bar-chart">
        <?php foreach ($by_category as $row): ?>
          <li>
            <span class="bar-label"><?php echo e($row['category']); ?></span>
            <span class="bar-track"><span class="bar-fill" style="width: <?php echo round(100 * $row['total'] / $max_total); ?>%"></span></span>
            <span class="bar-value"><?php echo (int)$row['total']; ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>

<div class="grid-2 section">
  <section class="card" aria-labelledby="feedback-heading">
    <h2 id="feedback-heading">Latest feedback</h2>
    <?php if (empty($feedback)): ?>
      <p class="empty-state">No feedback yet.</p>
    <?php else: ?>
      <ul class="feedback-list">
        <?php foreach ($feedback as $item): ?>
          <li>
            <strong><?php echo e($item['full_name']); ?></strong> &middot; <?php echo e($item['feedback_type']); ?>
            <span class="rating-badge"><?php echo (int)$item['rating']; ?>/5</span>
            <p><?php echo e($item['comments']); ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <section class="card" aria-labelledby="audit-heading">
    <h2 id="audit-heading">Audit log</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th scope="col">Time</th><th scope="col">User</th><th scope="col">Action</th><th scope="col">Details</th></tr></thead>
        <tbody>
          <?php foreach ($audit as $row): ?>
            <tr>
              <td><small><?php echo e(date('d M, h:i A', strtotime($row['created_at']))); ?></small></td>
              <td><?php echo e($row['username']); ?></td>
              <td><code><?php echo e($row['action']); ?></code></td>
              <td><small><?php echo e($row['details']); ?></small></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
