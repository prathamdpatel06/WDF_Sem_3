<?php
// manage_events.php - admin: add, edit, delete events and upload posters (Practical 12).
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$page_title   = 'Manage Events | StudentHUB';
$breadcrumbs  = [['index.php', 'Home'], ['admin_dashboard.php', 'Admin'], ['manage_events.php', 'Manage Events']];
$page_scripts = ['assets/js/validation.js'];

$q      = trim($_GET['q'] ?? '');
$cat    = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

// ---------- DELETE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Your form session expired. Please try again.');
    } else {
        $event = db_fetch_one($conn, "SELECT id, title, poster FROM events WHERE id = ?", 'i', [(int)($_POST['event_id'] ?? 0)]);
        if ($event) {
            db_execute($conn, "DELETE FROM events WHERE id = ?", 'i', [(int)$event['id']]);
            delete_uploaded_poster($event['poster']);
            audit_log($conn, 'EVENT_DELETED', 'Deleted event: ' . $event['title']);
            set_flash('success', 'Event "' . $event['title'] . '" was deleted.');
        } else {
            set_flash('warning', 'That event no longer exists.');
        }
    }
    redirect('manage_events.php');
}

// ---------- ADD / UPDATE ----------
$form = ['event_id' => 0, 'title' => '', 'category' => '', 'event_date' => '', 'location' => '',
         'seats_limit' => '100', 'description' => '', 'status' => 'Open', 'poster' => null];
$errors = [];

$edit_id = (int)($_GET['edit'] ?? 0);
if ($edit_id > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $row = db_fetch_one($conn, "SELECT * FROM events WHERE id = ?", 'i', [$edit_id]);
    if ($row) {
        $form = array_merge($form, $row, ['event_id' => (int)$row['id']]);
    } else {
        set_flash('warning', 'That event could not be found.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your form session expired. Please try again.';
    } else {
        foreach (['title', 'category', 'event_date', 'location', 'seats_limit', 'description', 'status'] as $field) {
            $form[$field] = trim($_POST[$field] ?? '');
        }
        $form['event_id'] = (int)($_POST['event_id'] ?? 0);
        $existing = $form['event_id'] > 0 ? db_fetch_one($conn, "SELECT * FROM events WHERE id = ?", 'i', [$form['event_id']]) : null;

        $errors = validate_event_fields($form);
        $poster_file = $_FILES['poster'] ?? ['error' => UPLOAD_ERR_NO_FILE];
        $poster_problem = validate_poster_upload($poster_file);
        if ($poster_problem) {
            $errors['poster'] = $poster_problem;
        }
        if ($form['event_id'] > 0 && !$existing) {
            $errors['form'] = 'That event no longer exists.';
        }

        if (empty($errors)) {
            $new_poster = null;
            try {
                if ($poster_file['error'] === UPLOAD_ERR_OK) {
                    $new_poster = save_uploaded_poster($poster_file);
                }
                $poster = $existing['poster'] ?? null;
                if (isset($_POST['remove_poster'])) {
                    $poster = null;
                }
                if ($new_poster !== null) {
                    $poster = $new_poster;
                }
                $seats = (int)$form['seats_limit'];

                if ($existing) {
                    db_execute($conn,
                        "UPDATE events SET title = ?, category = ?, event_date = ?, location = ?, seats_limit = ?, description = ?, poster = ?, status = ?
                         WHERE id = ?",
                        'ssssisssi', [$form['title'], $form['category'], $form['event_date'], $form['location'], $seats,
                                      $form['description'], $poster, $form['status'], $existing['id']]);
                    if ($existing['poster'] !== $poster) {
                        delete_uploaded_poster($existing['poster']);   // remove the replaced/removed file
                    }
                    audit_log($conn, 'EVENT_UPDATED', 'Updated event: ' . $form['title']);
                    set_flash('success', 'Event updated successfully.');
                } else {
                    db_execute($conn,
                        "INSERT INTO events (title, category, event_date, location, seats_limit, description, poster, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        'ssssisss', [$form['title'], $form['category'], $form['event_date'], $form['location'], $seats,
                                     $form['description'], $poster, $form['status']]);
                    audit_log($conn, 'EVENT_CREATED', 'Created event: ' . $form['title']);
                    set_flash('success', 'New event added.');
                }
                redirect('manage_events.php');
            } catch (Throwable $e) {
                delete_uploaded_poster($new_poster);   // clean up the file if the database save failed
                error_log('Event save error: ' . $e->getMessage());
                $errors['form'] = 'The event could not be saved. Please try again.';
            }
        }
    }
}
$is_edit_form = $form['event_id'] > 0;

// ---------- LIST with filters ----------
$where = []; $types = ''; $params = [];
if ($q !== '') { $where[] = '(e.title LIKE ? OR e.location LIKE ?)'; $types .= 'ss'; array_push($params, "%$q%", "%$q%"); }
if (in_array($cat, EVENT_CATEGORIES, true)) { $where[] = 'e.category = ?'; $types .= 's'; $params[] = $cat; }
if (in_array($status, EVENT_STATUSES, true)) { $where[] = 'e.status = ?'; $types .= 's'; $params[] = $status; }
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$events = db_fetch_all($conn,
    "SELECT e.*, (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status = 'Confirmed') AS booked
     FROM events e $where_sql ORDER BY e.event_date ASC", $types, $params);

require_once __DIR__ . '/includes/header.php';
?>

<h1>Manage Events</h1>
<p class="muted"><?php echo count($events); ?> event(s) listed.</p>

<form class="filter-bar" method="get" action="manage_events.php" role="search" aria-label="Filter events">
  <div class="form-group">
    <label for="q">Search</label>
    <input type="search" id="q" name="q" value="<?php echo e($q); ?>" placeholder="Title or location">
  </div>
  <div class="form-group">
    <label for="category">Category</label>
    <select id="category" name="category">
      <option value="">All</option>
      <?php foreach (EVENT_CATEGORIES as $c): ?>
        <option value="<?php echo e($c); ?>" <?php echo $cat === $c ? 'selected' : ''; ?>><?php echo e($c); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label for="status">Status</label>
    <select id="status" name="status">
      <option value="">All</option>
      <?php foreach (EVENT_STATUSES as $s): ?>
        <option value="<?php echo e($s); ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo e($s); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter-actions">
    <button type="submit" class="btn btn-primary">Apply</button>
    <a class="btn btn-outline" href="manage_events.php">Reset</a>
  </div>
</form>

<?php if (empty($events)): ?>
  <p class="empty-state">No events match your filters.</p>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <caption class="visually-hidden">Event list</caption>
      <thead>
        <tr><th scope="col">Poster</th><th scope="col">Event</th><th scope="col">Date</th><th scope="col">Seats booked</th><th scope="col">Status</th><th scope="col">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($events as $ev): ?>
          <tr>
            <td>
              <?php if ($ev['poster']): ?>
                <img class="thumb" src="<?php echo e($ev['poster']); ?>" alt="Poster for <?php echo e($ev['title']); ?>" width="64" height="48" loading="lazy">
              <?php else: ?>
                <span class="thumb-placeholder" aria-hidden="true"><?php echo e($ev['category']); ?></span>
              <?php endif; ?>
            </td>
            <td><strong><?php echo e($ev['title']); ?></strong><br><small class="muted"><?php echo e($ev['category']); ?> &middot; <?php echo e($ev['location']); ?></small></td>
            <td><?php echo e(format_date($ev['event_date'])); ?></td>
            <td><?php echo (int)$ev['booked']; ?> / <?php echo (int)$ev['seats_limit']; ?></td>
            <td><span class="badge badge-<?php echo $ev['status'] === 'Open' ? 'ok' : 'off'; ?>"><?php echo e($ev['status']); ?></span></td>
            <td class="actions">
              <a class="btn btn-outline btn-small" href="manage_events.php?edit=<?php echo (int)$ev['id']; ?>#event-form">Edit</a>
              <form method="post" action="manage_events.php" class="inline-form"
                    data-confirm="Delete this event and all its registrations?">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="event_id" value="<?php echo (int)$ev['id']; ?>">
                <button type="submit" class="btn btn-danger btn-small">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<section class="form-card section" id="event-form" aria-labelledby="event-form-heading">
  <h2 id="event-form-heading"><?php echo $is_edit_form ? 'Edit event' : 'Add a new event'; ?></h2>
  <?php if (!empty($errors['form'])): ?>
    <div class="alert alert-danger" role="alert"><?php echo e($errors['form']); ?></div>
  <?php endif; ?>

  <form method="post" action="manage_events.php<?php echo $is_edit_form ? '?edit=' . (int)$form['event_id'] : ''; ?>"
        enctype="multipart/form-data" novalidate data-validate>
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="event_id" value="<?php echo (int)$form['event_id']; ?>">

    <div class="form-grid">
      <div class="form-group">
        <label for="e_title">Title *</label>
        <input type="text" id="e_title" name="title" value="<?php echo e($form['title']); ?>" required
               data-rule="required min:3 max:150" data-label="Title" aria-describedby="e_title-error">
        <p class="field-error" id="e_title-error" role="alert"><?php echo e($errors['title'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="e_category">Category *</label>
        <select id="e_category" name="category" required data-rule="required select" data-label="Category" aria-describedby="e_category-error">
          <option value="">-- Select --</option>
          <?php foreach (EVENT_CATEGORIES as $c): ?>
            <option value="<?php echo e($c); ?>" <?php echo $form['category'] === $c ? 'selected' : ''; ?>><?php echo e($c); ?></option>
          <?php endforeach; ?>
        </select>
        <p class="field-error" id="e_category-error" role="alert"><?php echo e($errors['category'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="e_date">Date *</label>
        <input type="date" id="e_date" name="event_date" value="<?php echo e($form['event_date']); ?>" required
               data-rule="required" data-label="Date" aria-describedby="e_date-error">
        <p class="field-error" id="e_date-error" role="alert"><?php echo e($errors['event_date'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="e_location">Location *</label>
        <input type="text" id="e_location" name="location" value="<?php echo e($form['location']); ?>" required maxlength="120"
               data-rule="required" data-label="Location" aria-describedby="e_location-error">
        <p class="field-error" id="e_location-error" role="alert"><?php echo e($errors['location'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="e_seats">Seats *</label>
        <input type="number" id="e_seats" name="seats_limit" min="1" max="1000" value="<?php echo e($form['seats_limit']); ?>" required
               data-rule="required number" data-label="Seats" aria-describedby="e_seats-error">
        <p class="field-error" id="e_seats-error" role="alert"><?php echo e($errors['seats_limit'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="e_status">Status *</label>
        <select id="e_status" name="status" required data-rule="required select" data-label="Status" aria-describedby="e_status-error">
          <?php foreach (EVENT_STATUSES as $s): ?>
            <option value="<?php echo e($s); ?>" <?php echo $form['status'] === $s ? 'selected' : ''; ?>><?php echo e($s); ?></option>
          <?php endforeach; ?>
        </select>
        <p class="field-error" id="e_status-error" role="alert"><?php echo e($errors['status'] ?? ''); ?></p>
      </div>
    </div>

    <div class="form-group">
      <label for="e_description">Description (max 1000 characters)</label>
      <textarea id="e_description" name="description" rows="4" maxlength="1000"
                data-rule="max:1000" data-label="Description" aria-describedby="e_description-error"><?php echo e($form['description']); ?></textarea>
      <p class="field-error" id="e_description-error" role="alert"><?php echo e($errors['description'] ?? ''); ?></p>
    </div>

    <div class="form-group">
      <label for="e_poster">Poster image (JPG, PNG or WEBP, max 2 MB)</label>
      <input type="file" id="e_poster" name="poster" accept="image/jpeg,image/png,image/webp"
             data-rule="file" data-max-size="2097152" data-types="image/jpeg,image/png,image/webp" data-label="Poster"
             aria-describedby="e_poster-error">
      <?php if ($is_edit_form && !empty($form['poster'])): ?>
        <p class="hint">Current poster: <a href="<?php echo e($form['poster']); ?>" target="_blank" rel="noopener">view image</a></p>
        <label class="checkbox-row"><input type="checkbox" name="remove_poster"> Remove current poster</label>
      <?php endif; ?>
      <p class="field-error" id="e_poster-error" role="alert"><?php echo e($errors['poster'] ?? ''); ?></p>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><?php echo $is_edit_form ? 'Save event' : 'Add event'; ?></button>
      <?php if ($is_edit_form): ?><a class="btn btn-outline" href="manage_events.php">Cancel edit</a><?php endif; ?>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
