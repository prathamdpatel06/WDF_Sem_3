<?php
// feedback.php - students send feedback and see their earlier messages.
require_once __DIR__ . '/includes/bootstrap.php';
require_role('student');

$page_title   = 'Feedback | StudentHUB';
$breadcrumbs  = [['index.php', 'Home'], ['dashboard.php', 'Dashboard'], ['feedback.php', 'Feedback']];
$page_scripts = ['assets/js/validation.js'];
$errors = [];
$old = ['feedback_type' => 'General', 'rating' => '', 'comments' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['feedback_type'] = trim($_POST['feedback_type'] ?? '');
    $old['rating']        = trim($_POST['rating'] ?? '');
    $old['comments']      = trim($_POST['comments'] ?? '');

    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your form session expired. Please submit again.';
    } else {
        if (!in_array($old['feedback_type'], FEEDBACK_TYPES, true)) {
            $errors['feedback_type'] = 'Please choose a feedback type.';
        }
        if (!in_array($old['rating'], ['1', '2', '3', '4', '5'], true)) {
            $errors['rating'] = 'Please select a rating from 1 to 5.';
        }
        if (mb_strlen($old['comments']) < 10 || mb_strlen($old['comments']) > 500) {
            $errors['comments'] = 'Comments must be between 10 and 500 characters.';
        }
        if (empty($errors)) {
            try {
                db_execute($conn, "INSERT INTO feedback (user_id, feedback_type, rating, comments) VALUES (?, ?, ?, ?)",
                    'isis', [current_user_id(), $old['feedback_type'], (int)$old['rating'], $old['comments']]);
                audit_log($conn, 'FEEDBACK_SUBMITTED', $old['feedback_type'] . ' - rating ' . $old['rating']);
                set_flash('success', 'Thank you for your feedback!');
                redirect('feedback.php');
            } catch (Throwable $e) {
                error_log('Feedback error: ' . $e->getMessage());
                $errors['form'] = 'Your feedback could not be saved. Please try again.';
            }
        }
    }
}

$history = db_fetch_all($conn,
    "SELECT feedback_type, rating, comments, created_at FROM feedback WHERE user_id = ? ORDER BY created_at DESC",
    'i', [current_user_id()]);

require_once __DIR__ . '/includes/header.php';
?>

<h1>Feedback</h1>
<div class="grid-2">
  <section class="form-card" aria-labelledby="fb-form-heading">
    <h2 id="fb-form-heading">Send feedback</h2>
    <?php if (!empty($errors['form'])): ?>
      <div class="alert alert-danger" role="alert"><?php echo e($errors['form']); ?></div>
    <?php endif; ?>

    <form method="post" action="feedback.php" novalidate data-validate>
      <?php echo csrf_field(); ?>
      <div class="form-group">
        <label for="feedback_type">Type <span class="required" aria-hidden="true">*</span></label>
        <select id="feedback_type" name="feedback_type" data-rule="required select" data-label="Type" aria-describedby="feedback_type-error">
          <?php foreach (FEEDBACK_TYPES as $type): ?>
            <option value="<?php echo e($type); ?>" <?php echo $old['feedback_type'] === $type ? 'selected' : ''; ?>><?php echo e($type); ?></option>
          <?php endforeach; ?>
        </select>
        <p class="field-error" id="feedback_type-error" role="alert"><?php echo e($errors['feedback_type'] ?? ''); ?></p>
      </div>

      <fieldset class="form-group">
        <legend>Overall rating <span class="required" aria-hidden="true">*</span></legend>
        <div class="radio-row">
          <?php for ($r = 1; $r <= 5; $r++): ?>
            <label><input type="radio" name="rating" value="<?php echo $r; ?>" data-rule="radio" data-label="Rating"
                   <?php echo $old['rating'] === (string)$r ? 'checked' : ''; ?>> <?php echo $r; ?> &#9733;</label>
          <?php endfor; ?>
        </div>
        <p class="field-error" id="rating-error" role="alert"><?php echo e($errors['rating'] ?? ''); ?></p>
      </fieldset>

      <div class="form-group">
        <label for="comments">Comments <span class="required" aria-hidden="true">*</span></label>
        <textarea id="comments" name="comments" rows="5" data-rule="required min:10 max:500" data-label="Comments"
                  aria-describedby="comments-hint comments-error"><?php echo e($old['comments']); ?></textarea>
        <small id="comments-hint" class="hint">10 to 500 characters.</small>
        <p class="field-error" id="comments-error" role="alert"><?php echo e($errors['comments'] ?? ''); ?></p>
      </div>
      <button type="submit" class="btn btn-primary">Submit feedback</button>
    </form>
  </section>

  <section class="card" aria-labelledby="history-heading">
    <h2 id="history-heading">My previous feedback</h2>
    <?php if (empty($history)): ?>
      <p class="empty-state">You have not sent any feedback yet.</p>
    <?php else: ?>
      <ul class="feedback-list">
        <?php foreach ($history as $item): ?>
          <li>
            <strong><?php echo e($item['feedback_type']); ?></strong>
            <span class="rating-badge"><?php echo (int)$item['rating']; ?>/5</span>
            <p><?php echo e($item['comments']); ?></p>
            <small class="muted"><?php echo e(date('d M Y', strtotime($item['created_at']))); ?></small>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
