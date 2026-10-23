<?php
// ============================================================
// header.php - shared <head>, skip link, notice banner, role-based menu,
// breadcrumbs and flash messages. Pages set $page_title and optionally $breadcrumbs first.
// ============================================================
$current_page = basename($_SERVER['PHP_SELF']);
$role         = current_role();
$logged_in    = is_logged_in();
$page_title   = $page_title ?? APP_NAME . ' Portal';
$breadcrumbs  = $breadcrumbs ?? [];
$page_scripts = $page_scripts ?? [];

// Menu items. Shown depending on the role (dynamic menu, Practical 14).
$menu_public  = [['index.php', 'Home'], ['about.php', 'About'], ['events.php', 'Events'], ['faq.php', 'FAQ'], ['contact.php', 'Contact']];
$menu_student = [['dashboard.php', 'Dashboard'], ['profile.php', 'My Profile'], ['feedback.php', 'Feedback']];
$menu_admin   = [['admin_dashboard.php', 'Admin Dashboard'], ['manage_students.php', 'Manage Students'], ['manage_events.php', 'Manage Events']];

$menu = $menu_public;
if ($role === 'student') { $menu = array_merge($menu, $menu_student); }
if ($role === 'admin')   { $menu = array_merge($menu, $menu_admin); }

function nav_item(string $href, string $label, string $current): string {
    $active = $href === $current ? ' aria-current="page" class="active"' : '';
    return '<li><a href="' . e($href) . '"' . $active . '>' . e($label) . '</a></li>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token']); ?>">
  <meta name="description" content="StudentHUB - events, registrations and student services for CHARUSAT students.">
  <title><?php echo e($page_title); ?></title>
  <script>
    // Apply the saved light/dark theme before the page is painted (avoids a flash).
    try { var t = localStorage.getItem('studenthub-theme'); if (t) { document.documentElement.setAttribute('data-theme', t); } } catch (err) {}
  </script>
  <link rel="stylesheet" href="assets/css/style.css">
  <script>
    // Values used by the JavaScript files (no secrets in here).
    window.STUDENTHUB = { loggedIn: <?php echo json_encode($logged_in); ?>, role: <?php echo json_encode($role); ?> };
  </script>
</head>
<body>
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <div class="notice-banner" id="notice-banner" role="region" aria-label="Announcement">
    <p>Event registrations for the 2026-27 semester are now open. <a href="events.php">Browse events</a></p>
    <button type="button" class="icon-btn" id="close-banner-btn" aria-label="Dismiss announcement">&times;</button>
  </div>

  <header class="site-header">
    <div class="header-inner container">
      <a href="index.php" class="site-brand" aria-label="StudentHUB home">StudentHUB</a>

      <button type="button" class="hamburger" id="hamburger-toggle" aria-controls="nav-menu" aria-expanded="false" aria-label="Open menu">
        <span></span><span></span><span></span>
      </button>

      <div class="nav-wrapper" id="nav-menu">
        <nav aria-label="Main navigation">
          <ul class="main-nav">
            <?php foreach ($menu as [$href, $label]) { echo nav_item($href, $label, $current_page); } ?>
          </ul>
        </nav>

        <ul class="auth-nav">
          <?php if ($logged_in): ?>
            <li><span class="user-badge"><?php echo e(current_full_name()); ?> (<?php echo e(ucfirst($role)); ?>)</span></li>
            <li><a href="logout.php" class="btn btn-outline btn-small">Logout</a></li>
          <?php else: ?>
            <?php echo nav_item('login.php', 'Login', $current_page); ?>
            <li><a href="register.php" class="btn btn-primary btn-small">Register</a></li>
          <?php endif; ?>
        </ul>

        <button type="button" class="btn btn-small theme-btn" id="theme-toggle-btn" aria-pressed="false">Dark Mode</button>
      </div>
    </div>
  </header>

  <main id="main-content" class="container main-content" tabindex="-1">
    <?php if ($breadcrumbs): ?>
      <nav aria-label="Breadcrumb">
        <ol class="breadcrumb">
          <?php foreach ($breadcrumbs as $i => [$href, $label]):
            $is_last = $i === array_key_last($breadcrumbs); ?>
            <li>
              <?php if ($is_last): ?>
                <span aria-current="page"><?php echo e($label); ?></span>
              <?php else: ?>
                <a href="<?php echo e($href); ?>"><?php echo e($label); ?></a>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </nav>
    <?php endif; ?>

    <?php foreach (get_flashes() as $flash): ?>
      <div class="alert alert-<?php echo e($flash['type']); ?>" role="<?php echo $flash['type'] === 'danger' ? 'alert' : 'status'; ?>">
        <?php echo e($flash['message']); ?>
      </div>
    <?php endforeach; ?>
