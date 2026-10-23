<?php
require_once __DIR__ . '/includes/bootstrap.php';
redirect_if_logged_in();   // already logged in? go straight to your dashboard

$page_title  = 'Login | StudentHUB';
$breadcrumbs = [['index.php', 'Home'], ['login.php', 'Login']];
$page_scripts = ['assets/js/validation.js'];
$errors = [];

// Messages coming from other pages (for example a timeout)
$notice_map = [
    'login_required' => 'Please log in to open that page.',
    'timeout'        => 'Your session expired after 15 minutes of inactivity. Please log in again.',
    'deactivated'    => 'Your account is inactive. Please contact the administrator.',
];
$notice = $notice_map[$_GET['error'] ?? ''] ?? '';

// Where to go after login (only pages that require login are allowed)
$allowed_next = ['dashboard.php', 'profile.php', 'feedback.php', 'admin_dashboard.php', 'manage_students.php', 'manage_events.php'];
$next = basename($_GET['next'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember_me']);

    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your form session expired. Please try again.';
    } elseif ($login_id === '' || $password === '') {
        $errors['form'] = 'Please enter your username or email and your password.';
    } else {
        try {
            // Prepared statement: the user input can never change the SQL.
            $user = db_fetch_one($conn,
                "SELECT id, username, full_name, password_hash, role, is_active FROM users WHERE username = ? OR email = ?",
                'ss', [$login_id, $login_id]);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                audit_log($conn, 'LOGIN_FAILED', 'Wrong username or password for: ' . $login_id, null, $login_id);
                $errors['form'] = 'Incorrect username/email or password.';   // same message on purpose
            } elseif ((int)$user['is_active'] !== 1) {
                $errors['form'] = 'Your account is inactive. Please contact the administrator.';
            } else {
                login_user($user);
                db_execute($conn, "UPDATE users SET last_login = NOW() WHERE id = ?", 'i', [(int)$user['id']]);
                if ($remember) {
                    create_remember_token($conn, (int)$user['id']);
                }
                audit_log($conn, 'USER_LOGIN', 'Logged in as ' . $user['role'], (int)$user['id'], $user['username']);

                // Role-based redirection
                if ($user['role'] === 'student' && in_array($next, $allowed_next, true) && !in_array($next, ['admin_dashboard.php', 'manage_students.php', 'manage_events.php'], true)) {
                    redirect($next);
                }
                if ($user['role'] === 'admin' && in_array($next, ['admin_dashboard.php', 'manage_students.php', 'manage_events.php'], true)) {
                    redirect($next);
                }
                redirect(home_for_role($user['role']));
            }
        } catch (Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            $errors['form'] = 'Login is not available right now. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="form-card" aria-labelledby="login-heading">
  <h1 id="login-heading">Login to StudentHUB</h1>

  <?php if ($notice): ?>
    <div class="alert alert-warning" role="status"><?php echo e($notice); ?></div>
  <?php endif; ?>
  <?php if (!empty($errors['form'])): ?>
    <div class="alert alert-danger" role="alert" id="form-error"><?php echo e($errors['form']); ?></div>
  <?php endif; ?>

  <details class="demo-box">
    <summary>Demo accounts (for testing)</summary>
    <p>Admin: <code>admin</code> / <code>Admin@123</code></p>
    <p>Student: <code>student</code> / <code>Student@123</code></p>
  </details>

  <form method="post" action="login.php<?php echo $next ? '?next=' . urlencode($next) : ''; ?>" novalidate data-validate>
    <?php echo csrf_field(); ?>

    <div class="form-group">
      <label for="login_id">Username or email <span class="required" aria-hidden="true">*</span></label>
      <input type="text" id="login_id" name="login_id" autocomplete="username" required
             value="<?php echo e($_POST['login_id'] ?? ''); ?>"
             data-rule="required" data-label="Username or email">
      <p class="field-error" id="login_id-error" role="alert"></p>
    </div>

    <div class="form-group">
      <label for="password">Password <span class="required" aria-hidden="true">*</span></label>
      <input type="password" id="password" name="password" autocomplete="current-password" required
             data-rule="required" data-label="Password">
      <p class="field-error" id="password-error" role="alert"></p>
    </div>

    <div class="form-group checkbox-row">
      <input type="checkbox" id="remember_me" name="remember_me">
      <label for="remember_me">Remember me for 7 days</label>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Login</button>
  </form>

  <p class="center mt-2">New student? <a href="register.php">Create an account</a></p>
  <p class="center"><small>Forgot your password? <a href="contact.php">Contact the administrator</a>.</small></p>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
