<?php
// dashboard.php - student home page. Only logged-in STUDENTS can open it.
require_once __DIR__ . '/includes/bootstrap.php';
require_role('student');

$page_title   = 'Dashboard | StudentHUB';
$breadcrumbs  = [['index.php', 'Home'], ['dashboard.php', 'Dashboard']];

$profile = db_fetch_one($conn,
    "SELECT s.id AS student_id, s.student_code, s.department, s.year_of_study, u.full_name, u.email, u.last_login
     FROM students s JOIN users u ON u.id = s.user_id WHERE s.user_id = ?",
    'i', [current_user_id()]);

if (!$profile) {
    // Only possible if the database was edited by hand. Show a clear message instead of crashing.
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-danger" role="alert">Your student profile could not be found. Please contact the administrator.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$sid = (int)$profile['student_id'];
$stats = db_fetch_one($conn,
    "SELECT
        SUM(r.status = 'Confirmed') AS confirmed,
        SUM(r.status = 'Confirmed' AND e.event_date >= CURDATE()) AS upcoming,
        (SELECT COUNT(*) FROM feedback f WHERE f.user_id = ?) AS feedback_count
     FROM registrations r JOIN events e ON e.id = r.event_id
     WHERE r.student_id = ?",
    'ii', [current_user_id(), $sid]) ?? ['confirmed' => 0, 'upcoming' => 0, 'feedback_count' => 0];

$upcoming = db_fetch_all($conn,
    "SELECT e.id, e.title, e.category, e.event_date, e.location, e.poster
     FROM registrations r JOIN events e ON e.id = r.event_id
     WHERE r.student_id = ? AND r.status = 'Confirmed' AND e.event_date >= CURDATE()
     ORDER BY e.event_date ASC",
    'i', [$sid]);

$activity = db_fetch_all($conn,
    "SELECT action, details, created_at FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 6",
    'i', [current_user_id()]);

require_once __DIR__ . '/includes/header.php';
?>

<h1>Welcome back, <?php echo e($profile['full_name']); ?>!</h1>
<p class="muted">Student ID <code><?php echo e($profile['student_code']); ?></code> &middot; <?php echo e($profile['department']); ?> &middot; Year <?php echo (int)$profile['year_of_study']; ?></p>

<div class="grid-3 mt-2">
  <article class="stat-card">
    <h2>Registered events</h2>
    <p class="stat-number"><?php echo (int)$stats['confirmed']; ?></p>
  </article>
  <article class="stat-card">
    <h2>Upcoming events</h2>
    <p class="stat-number"><?php echo (int)$stats['upcoming']; ?></p>
  </article>
  <article class="stat-card">
    <h2>My feedback</h2>
    <p class="stat-number"><?php echo (int)$stats['feedback_count']; ?></p>
  </article>
</div>

<section class="section" aria-labelledby="upcoming-heading">
  <h2 id="upcoming-heading">My upcoming events</h2>
  <?php if (empty($upcoming)): ?>
    <p class="empty-state">You have no upcoming registrations. <a href="events.php">Find an event</a>.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <caption class="visually-hidden">Events you are registered for</caption>
        <thead><tr><th scope="col">Event</th><th scope="col">Category</th><th scope="col">Date</th><th scope="col">Location</th><th scope="col">Action</th></tr></thead>
        <tbody>
          <?php foreach ($upcoming as $ev): ?>
            <tr>
              <td><strong><?php echo e($ev['title']); ?></strong></td>
              <td><?php echo e($ev['category']); ?></td>
              <td><?php echo e(format_date($ev['event_date'])); ?></td>
              <td><?php echo e($ev['location']); ?></td>
              <td>
                <button type="button" class="btn btn-danger btn-small"
                        data-api="api/registrations.php" data-action="cancel" data-id="<?php echo (int)$ev['id']; ?>"
                        data-confirm="Cancel your registration for <?php echo e($ev['title']); ?>?" data-reload>Cancel</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<div class="grid-2 section">
  <section class="card" aria-labelledby="quick-heading">
    <h2 id="quick-heading">Quick actions</h2>
    <ul class="action-list">
      <li><a href="events.php">Browse and register for events</a></li>
      <li><a href="profile.php">Edit my profile or change password</a></li>
      <li><a href="feedback.php">Send feedback</a></li>
      <li><a href="contact.php">Contact support</a></li>
    </ul>
  </section>

  <section class="card" aria-labelledby="activity-heading">
    <h2 id="activity-heading">Recent activity</h2>
    <?php if (empty($activity)): ?>
      <p class="empty-state">No activity yet.</p>
    <?php else: ?>
      <ul class="activity-list">
        <?php foreach ($activity as $row): ?>
          <li>
            <strong><?php echo e(str_replace('_', ' ', $row['action'])); ?></strong>
            <span class="muted"><?php echo e($row['details']); ?></span>
            <br><small><?php echo e(date('d M Y, h:i A', strtotime($row['created_at']))); ?></small>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <p class="muted"><small>Last login: <?php echo $profile['last_login'] ? e(date('d M Y, h:i A', strtotime($profile['last_login']))) : 'This is your first login'; ?></small></p>
  </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
