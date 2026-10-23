<?php
// profile.php - students view and edit their profile and change their password.
require_once __DIR__ . '/includes/bootstrap.php';
require_role('student');

$page_title   = 'My Profile | StudentHUB';
$breadcrumbs  = [['index.php', 'Home'], ['dashboard.php', 'Dashboard'], ['profile.php', 'My Profile']];
$page_scripts = ['assets/js/validation.js'];
$uid = current_user_id();
$errors = [];

// Load the current data (after every save we redirect, so this always shows the newest values)
$me = db_fetch_one($conn,
    "SELECT u.username, u.full_name, u.email, u.last_login, s.id AS student_id, s.student_code, s.mobile, s.department, s.year_of_study, s.gender
     FROM users u JOIN students s ON s.user_id = u.id WHERE u.id = ?",
    'i', [$uid]);
if (!$me) {
    set_flash('danger', 'Your student profile could not be found.');
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your form session expired. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'profile') {
        // ---- Update profile details ----
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''), 'email' => trim($_POST['email'] ?? ''),
            'mobile' => trim($_POST['mobile'] ?? ''), 'department' => trim($_POST['department'] ?? ''),
            'year_of_study' => trim($_POST['year_of_study'] ?? ''), 'gender' => trim($_POST['gender'] ?? ''),
        ];
        $errors = validate_student_fields($data, null, false);
        if (empty($errors)) {
            $errors = array_merge($errors, find_duplicate_account($conn, $me['username'], $data['email'], $uid));
        }
        if (empty($errors)) {
            try {
                update_student_profile($conn, $uid, $data);
                audit_log($conn, 'PROFILE_UPDATED', 'Profile details changed');
                set_flash('success', 'Your profile has been updated.');
                redirect('profile.php');
            } catch (Throwable $e) {
                error_log('Profile update error: ' . $e->getMessage());
                $errors['form'] = 'Your changes could not be saved. Please try again.';
            }
        }
        // keep what the user typed when there is an error
        $me = array_merge($me, $data);
    } elseif (($_POST['action'] ?? '') === 'password') {
        // ---- Change password ----
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $stored  = db_fetch_one($conn, "SELECT password_hash FROM users WHERE id = ?", 'i', [$uid]);

        if (!$stored || !password_verify($current, $stored['password_hash'])) {
            $errors['current_password'] = 'Your current password is incorrect.';
        } elseif (password_problem($new)) {
            $errors['new_password'] = password_problem($new);
        } elseif ($new !== $confirm) {
            $errors['confirm_password'] = 'The new passwords do not match.';
        } else {
            try {
                db_execute($conn, "UPDATE users SET password_hash = ? WHERE id = ?",
                    'si', [password_hash($new, PASSWORD_DEFAULT), $uid]);
                audit_log($conn, 'PASSWORD_CHANGED', 'Password changed by the student');
                set_flash('success', 'Your password has been changed.');
                redirect('profile.php');
            } catch (Throwable $e) {
                error_log('Password change error: ' . $e->getMessage());
                $errors['form'] = 'The password could not be changed. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1>My Profile</h1>
<p class="muted">Student ID <code><?php echo e($me['student_code']); ?></code> &middot; Username <code><?php echo e($me['username']); ?></code> (cannot be changed)</p>

<?php if (!empty($errors['form'])): ?>
  <div class="alert alert-danger" role="alert"><?php echo e($errors['form']); ?></div>
<?php endif; ?>

<div class="grid-2">
  <section class="form-card" aria-labelledby="edit-heading">
    <h2 id="edit-heading">Personal and academic details</h2>
    <form method="post" action="profile.php" novalidate data-validate>
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="profile">

      <div class="form-group">
        <label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" value="<?php echo e($me['full_name']); ?>" required
               data-rule="required name" data-label="Full name" aria-describedby="full_name-error">
        <p class="field-error" id="full_name-error" role="alert"><?php echo e($errors['full_name'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo e($me['email']); ?>" required
               data-rule="required email" data-label="Email" aria-describedby="email-error">
        <p class="field-error" id="email-error" role="alert"><?php echo e($errors['email'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="mobile">Mobile number</label>
        <input type="tel" id="mobile" name="mobile" value="<?php echo e($me['mobile']); ?>" maxlength="10" required
               data-rule="required mobile" data-label="Mobile number" aria-describedby="mobile-error">
        <p class="field-error" id="mobile-error" role="alert"><?php echo e($errors['mobile'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="department">Course / Department</label>
        <select id="department" name="department" data-rule="required select" data-label="Course" aria-describedby="department-error">
          <?php foreach (COURSES as $course): ?>
            <option value="<?php echo e($course); ?>" <?php echo $me['department'] === $course ? 'selected' : ''; ?>><?php echo e($course); ?></option>
          <?php endforeach; ?>
        </select>
        <p class="field-error" id="department-error" role="alert"><?php echo e($errors['department'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="year_of_study">Year of study</label>
        <select id="year_of_study" name="year_of_study" data-rule="required select" data-label="Year" aria-describedby="year-error">
          <?php for ($y = 1; $y <= 4; $y++): ?>
            <option value="<?php echo $y; ?>" <?php echo (int)$me['year_of_study'] === $y ? 'selected' : ''; ?>>Year <?php echo $y; ?></option>
          <?php endfor; ?>
        </select>
        <p class="field-error" id="year-error" role="alert"><?php echo e($errors['year_of_study'] ?? ''); ?></p>
      </div>
      <fieldset class="form-group">
        <legend>Gender</legend>
        <div class="radio-row">
          <?php foreach (GENDERS as $g): ?>
            <label><input type="radio" name="gender" value="<?php echo e($g); ?>" data-rule="radio" data-label="Gender"
                   <?php echo $me['gender'] === $g ? 'checked' : ''; ?>> <?php echo e($g); ?></label>
          <?php endforeach; ?>
        </div>
        <p class="field-error" id="gender-error" role="alert"><?php echo e($errors['gender'] ?? ''); ?></p>
      </fieldset>
      <button type="submit" class="btn btn-primary">Save changes</button>
    </form>
  </section>

  <section class="form-card" aria-labelledby="password-heading">
    <h2 id="password-heading">Change password</h2>
    <form method="post" action="profile.php" novalidate data-validate>
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="password">

      <div class="form-group">
        <label for="current_password">Current password</label>
        <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
               data-rule="required" data-label="Current password" aria-describedby="current_password-error">
        <p class="field-error" id="current_password-error" role="alert"><?php echo e($errors['current_password'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="new_password">New password</label>
        <input type="password" id="new_password" name="new_password" required autocomplete="new-password"
               data-rule="required password" data-label="New password" data-strength="new-password-strength"
               aria-describedby="new_password-hint new-password-strength new_password-error">
        <small id="new_password-hint" class="hint">At least 8 characters, with a letter and a number.</small>
        <p class="strength" id="new-password-strength" aria-live="polite"></p>
        <p class="field-error" id="new_password-error" role="alert"><?php echo e($errors['new_password'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirm new password</label>
        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password"
               data-rule="required confirm" data-match="new_password" data-label="Confirm password" aria-describedby="confirm_password-error">
        <p class="field-error" id="confirm_password-error" role="alert"><?php echo e($errors['confirm_password'] ?? ''); ?></p>
      </div>
      <button type="submit" class="btn btn-primary">Update password</button>
    </form>

    <p class="muted mt-2"><small>Last login: <?php echo $me['last_login'] ? e(date('d M Y, h:i A', strtotime($me['last_login']))) : 'First login'; ?></small></p>
  </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
