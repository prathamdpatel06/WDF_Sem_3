<?php
require_once __DIR__ . '/includes/bootstrap.php';
redirect_if_logged_in();

$page_title   = 'Create Account | StudentHUB';
$breadcrumbs  = [['index.php', 'Home'], ['register.php', 'Register']];
$page_scripts = ['assets/js/validation.js'];
$errors = [];

// Values typed by the user, so the form is not emptied after an error
$old = [
    'full_name' => '', 'username' => '', 'email' => '', 'mobile' => '',
    'department' => '', 'year_of_study' => '', 'gender' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $field => $_) {
        $old[$field] = trim($_POST[$field] ?? '');
    }
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your form session expired. Please submit the form again.';
    } else {
        // 1. Field rules (same rules as the JavaScript)
        $errors = validate_student_fields($old, $password);
        if ($password !== $confirm) {
            $errors['confirm_password'] = 'The two passwords do not match.';
        }
        if (empty($_POST['terms'])) {
            $errors['terms'] = 'You must accept the terms and conditions.';
        }

        // 2. Duplicate check (username / email must be unique)
        if (empty($errors)) {
            $errors = array_merge($errors, find_duplicate_account($conn, $old['username'], $old['email']));
        }

        // 3. Save to the database
        if (empty($errors)) {
            try {
                $user_id = create_student($conn, $old, $password);
                audit_log($conn, 'STUDENT_REGISTERED', 'New student account: ' . $old['username'], $user_id, $old['username']);
                set_flash('success', 'Your account has been created. Please log in with your new password.');
                redirect('login.php');
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {   // duplicate key (someone registered at the same moment)
                    $errors['form'] = 'That username or email is already registered.';
                } else {
                    error_log('Register error: ' . $e->getMessage());
                    $errors['form'] = 'We could not save your account right now. Please try again.';
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="form-card form-card-wide" aria-labelledby="register-heading">
  <h1 id="register-heading">Student Registration</h1>
  <p class="muted">Fields marked <span class="required" aria-hidden="true">*</span> are required.</p>

  <?php if (!empty($errors['form'])): ?>
    <div class="alert alert-danger" role="alert"><?php echo e($errors['form']); ?></div>
  <?php endif; ?>

  <form method="post" action="register.php" novalidate data-validate>
    <?php echo csrf_field(); ?>

    <div class="form-grid">
      <div class="form-group">
        <label for="full_name">Full name <span class="required" aria-hidden="true">*</span></label>
        <input type="text" id="full_name" name="full_name" required autocomplete="name"
               value="<?php echo e($old['full_name']); ?>"
               data-rule="required name" data-label="Full name"
               aria-describedby="full_name-hint full_name-error"
               <?php echo isset($errors['full_name']) ? 'aria-invalid="true"' : ''; ?>>
        <small id="full_name-hint" class="hint">Letters, spaces, dot, apostrophe, hyphen.</small>
        <p class="field-error" id="full_name-error" role="alert"><?php echo e($errors['full_name'] ?? ''); ?></p>
      </div>

      <div class="form-group">
        <label for="username">Username <span class="required" aria-hidden="true">*</span></label>
        <input type="text" id="username" name="username" required autocomplete="username"
               value="<?php echo e($old['username']); ?>"
               data-rule="required username" data-label="Username"
               aria-describedby="username-hint username-error"
               <?php echo isset($errors['username']) ? 'aria-invalid="true"' : ''; ?>>
        <small id="username-hint" class="hint">4-20 characters: letters, numbers, underscore.</small>
        <p class="field-error" id="username-error" role="alert"><?php echo e($errors['username'] ?? ''); ?></p>
      </div>

      <div class="form-group">
        <label for="email">Email <span class="required" aria-hidden="true">*</span></label>
        <input type="email" id="email" name="email" required autocomplete="email"
               value="<?php echo e($old['email']); ?>"
               data-rule="required email" data-label="Email"
               aria-describedby="email-error"
               <?php echo isset($errors['email']) ? 'aria-invalid="true"' : ''; ?>>
        <p class="field-error" id="email-error" role="alert"><?php echo e($errors['email'] ?? ''); ?></p>
      </div>

      <div class="form-group">
        <label for="mobile">Mobile number <span class="required" aria-hidden="true">*</span></label>
        <input type="tel" id="mobile" name="mobile" required autocomplete="tel" inputmode="numeric" maxlength="10"
               value="<?php echo e($old['mobile']); ?>"
               data-rule="required mobile" data-label="Mobile number"
               aria-describedby="mobile-error"
               <?php echo isset($errors['mobile']) ? 'aria-invalid="true"' : ''; ?>>
        <p class="field-error" id="mobile-error" role="alert"><?php echo e($errors['mobile'] ?? ''); ?></p>
      </div>

      <div class="form-group">
        <label for="department">Course / Department <span class="required" aria-hidden="true">*</span></label>
        <select id="department" name="department" required data-rule="required select" data-label="Course"
                aria-describedby="department-error">
          <option value="">-- Select course --</option>
          <?php foreach (COURSES as $course): ?>
            <option value="<?php echo e($course); ?>" <?php echo $old['department'] === $course ? 'selected' : ''; ?>><?php echo e($course); ?></option>
          <?php endforeach; ?>
        </select>
        <p class="field-error" id="department-error" role="alert"><?php echo e($errors['department'] ?? ''); ?></p>
      </div>

      <div class="form-group">
        <label for="year_of_study">Year of study <span class="required" aria-hidden="true">*</span></label>
        <select id="year_of_study" name="year_of_study" required data-rule="required select" data-label="Year"
                aria-describedby="year_of_study-error">
          <option value="">-- Select year --</option>
          <?php for ($y = 1; $y <= 4; $y++): ?>
            <option value="<?php echo $y; ?>" <?php echo (string)$old['year_of_study'] === (string)$y ? 'selected' : ''; ?>>Year <?php echo $y; ?></option>
          <?php endfor; ?>
        </select>
        <p class="field-error" id="year_of_study-error" role="alert"><?php echo e($errors['year_of_study'] ?? ''); ?></p>
      </div>

      <fieldset class="form-group">
        <legend>Gender <span class="required" aria-hidden="true">*</span></legend>
        <div class="radio-row">
          <?php foreach (GENDERS as $g): ?>
            <label><input type="radio" name="gender" value="<?php echo e($g); ?>" data-rule="radio" data-label="Gender"
                   <?php echo $old['gender'] === $g ? 'checked' : ''; ?>> <?php echo e($g); ?></label>
          <?php endforeach; ?>
        </div>
        <p class="field-error" id="gender-error" role="alert"><?php echo e($errors['gender'] ?? ''); ?></p>
      </fieldset>

      <div class="form-group">
        <label for="password">Password <span class="required" aria-hidden="true">*</span></label>
        <input type="password" id="password" name="password" required autocomplete="new-password"
               data-rule="required password" data-label="Password" data-strength="password-strength"
               aria-describedby="password-hint password-strength password-error"
               <?php echo isset($errors['password']) ? 'aria-invalid="true"' : ''; ?>>
        <small id="password-hint" class="hint">At least 8 characters, with a letter and a number.</small>
        <p class="strength" id="password-strength" aria-live="polite"></p>
        <p class="field-error" id="password-error" role="alert"><?php echo e($errors['password'] ?? ''); ?></p>
      </div>

      <div class="form-group">
        <label for="confirm_password">Confirm password <span class="required" aria-hidden="true">*</span></label>
        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password"
               data-rule="required confirm" data-match="password" data-label="Confirm password"
               aria-describedby="confirm_password-error"
               <?php echo isset($errors['confirm_password']) ? 'aria-invalid="true"' : ''; ?>>
        <p class="field-error" id="confirm_password-error" role="alert"><?php echo e($errors['confirm_password'] ?? ''); ?></p>
      </div>
    </div>

    <div class="form-group checkbox-row">
      <input type="checkbox" id="terms" name="terms" data-rule="checkbox" data-label="Terms" aria-describedby="terms-error"
             <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
      <label for="terms">I accept the portal terms and I will use my account responsibly. <span class="required" aria-hidden="true">*</span></label>
    </div>
    <p class="field-error" id="terms-error" role="alert"><?php echo e($errors['terms'] ?? ''); ?></p>

    <button type="submit" class="btn btn-primary btn-block">Create account</button>
  </form>

  <p class="center mt-2">Already registered? <a href="login.php">Login here</a></p>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
