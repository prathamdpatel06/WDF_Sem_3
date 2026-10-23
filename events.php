<?php
require_once __DIR__ . '/includes/bootstrap.php';

$page_title   = 'Events | StudentHUB';
$breadcrumbs  = [['index.php', 'Home'], ['events.php', 'Events']];
$page_scripts = ['assets/js/events.js'];
require_once __DIR__ . '/includes/header.php';
?>

<h1>Events</h1>
<p class="muted">Search and filter events. <?php echo is_logged_in() ? 'Use the buttons on each card to register or cancel.' : '<a href="login.php">Login</a> to register for events.'; ?></p>

<form class="filter-bar" id="event-filter-form" role="search" aria-label="Filter events">
  <div class="form-group">
    <label for="event-q">Search by title or location</label>
    <input type="search" id="event-q" name="q" placeholder="e.g. hackathon" autocomplete="off">
  </div>
  <div class="form-group">
    <label for="event-category">Category</label>
    <select id="event-category" name="category">
      <option value="">All categories</option>
      <?php foreach (EVENT_CATEGORIES as $cat): ?>
        <option value="<?php echo e($cat); ?>"><?php echo e($cat); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label for="event-status">Status</label>
    <select id="event-status" name="status">
      <option value="">All</option>
      <option value="Open">Open</option>
      <option value="Closed">Closed</option>
    </select>
  </div>
  <div class="form-group checkbox-row">
    <input type="checkbox" id="event-upcoming" name="upcoming" checked>
    <label for="event-upcoming">Upcoming only</label>
  </div>
</form>

<p id="events-status" class="status-line" role="status" aria-live="polite">Loading events...</p>
<div id="events-list" class="grid-3"></div>

<nav class="pagination" aria-label="Event pages" id="events-pagination" hidden>
  <button type="button" class="btn btn-outline btn-small" id="events-prev">&larr; Previous</button>
  <span id="events-page-info" aria-live="polite"></span>
  <button type="button" class="btn btn-outline btn-small" id="events-next">Next &rarr;</button>
</nav>

<!-- Event details popup (modal) -->
<div class="modal" id="event-modal" hidden>
  <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="event-modal-title">
    <div class="modal-header">
      <h2 id="event-modal-title">Event details</h2>
      <button type="button" class="icon-btn" data-modal-close aria-label="Close details">&times;</button>
    </div>
    <div id="event-modal-body"></div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
