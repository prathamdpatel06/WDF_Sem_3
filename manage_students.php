<?php
// manage_students.php - admin: list, search, filter, add, edit, enable/disable, delete and CSV export.
// Toggle and delete use the AJAX API in api/students.php.
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$page_title   = 'Manage Students | StudentHUB';
$breadcrumbs  = [['index.php', 'Home'], ['admin_dashboard.php', 'Admin'], ['manage_students.php', 'Manage Students']];
$page_scripts = ['assets/js/validation.js'];

// ---------- 1. Filters from the URL ----------
$q      = trim($_GET['q'] ?? '');
$dept   = $_GET['department'] ?? '';
$status = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$per_page = 8;

$where  = ["u.role = 'student'"];
$types  = '';
$params = [];
if ($q !== '') {
    $where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR s.student_code LIKE ?)";
    $types .= 'ssss';
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}
if (in_array($dept, COURSES, true)) {
    $where[] = 's.department = ?';
    $types .= 's';
    $params[] = $dept;
}
if ($status === 'active') {
    $where[] = 'u.is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 'u.is_active = 0';
}
$from_sql = "FROM students s JOIN users u ON u.id = s.user_id WHERE " . implode(' AND ', $where);

// ---------- 2. CSV export (Practical 11 extension) ----------
if (($_GET['export'] ?? '') === 'csv') {
    $rows = db_fetch_all($conn,
        "SELECT s.student_code, u.full_name, u.username, u.email, s.mobile, s.department, s.year_of_study, s.gender, u.is_active $from_sql ORDER BY s.student_code",
        $types, $params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="students_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student Code', 'Full Name', 'Username', 'Email', 'Mobile', 'Department', 'Year', 'Gender', 'Active']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['student_code'], $r['full_name'], $r['username'], $r['email'], $r['mobile'],
            $r['department'], $r['year_of_study'], $r['gender'], $r['is_active'] ? 'Yes' : 'No']);
    }
    fclose($out);
    exit;
}

// ---------- 3. Add / edit form submitted ----------
$form = ['student_id' => 0, 'full_name' => '', 'username' => '', 'email' => '', 'mobile' => '',
         'department' => '', 'year_of_study' => '', 'gender' => ''];
$errors = [];

$load_student_sql = "SELECT s.id AS student_id, s.mobile, s.department, s.year_of_study, s.gender,
                            u.id AS user_id, u.username, u.full_name, u.email
                     FROM students s JOIN users u ON u.id = s.user_id WHERE s.id = ?";

// Editing: prefill the form from the database
$edit_id = (int)($_GET['edit'] ?? 0);
if ($edit_id > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $row = db_fetch_one($conn, $load_student_sql, 'i', [$edit_id]);
    if ($row) {
        $form = array_merge($form, $row);
    } else {
        set_flash('warning', 'That student could not be found.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your form session expired. Please try again.';
    } else {
        foreach (['full_name', 'username', 'email', 'mobile', 'department', 'year_of_study', 'gender'] as $field) {
            $form[$field] = trim($_POST[$field] ?? '');
        }
        $form['student_id'] = (int)($_POST['student_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $existing = $form['student_id'] > 0 ? db_fetch_one($conn, $load_student_sql, 'i', [$form['student_id']]) : null;

        try {
            if ($form['student_id'] > 0 && !$existing) {
                $errors['form'] = 'That student no longer exists.';
            } elseif ($existing) {
                // ---- UPDATE an existing student ----
                $new_password = $password !== '' ? $password : null;   // blank = keep old password
                $form['username'] = $existing['username'];              // username is never changed
                $errors = validate_student_fields($form, $new_password, false);
                if (empty($errors)) {
                    $errors = find_duplicate_account($conn, $existing['username'], $form['email'], (int)$existing['user_id']);
                }
                if (empty($errors)) {
                    update_student_profile($conn, (int)$existing['user_id'], $form, $new_password);
                    audit_log($conn, 'STUDENT_UPDATED', 'Updated student ' . $existing['username']);
                    set_flash('success', 'Student details were updated.');
                    redirect('manage_students.php');
                }
            } else {
                // ---- ADD a new student ----
                $errors = validate_student_fields($form, $password, true);
                if (empty($errors)) {
                    $errors = find_duplicate_account($conn, $form['username'], $form['email']);
                }
                if (empty($errors)) {
                    create_student($conn, $form, $password);
                    audit_log($conn, 'STUDENT_CREATED', 'Created student ' . $form['username']);
                    set_flash('success', 'New student added. They can now log in.');
                    redirect('manage_students.php');
                }
            }
        } catch (mysqli_sql_exception $e) {
            $errors['form'] = $e->getCode() === 1062
                ? 'Username or email already exists.'
                : 'The student could not be saved. Please try again.';
            error_log('Student save error: ' . $e->getMessage());
        }
    }
    $is_edit_form = $form['student_id'] > 0;
} else {
    $is_edit_form = $edit_id > 0 && $form['student_id'] > 0;
}

// ---------- 4. List with paging ----------
$total = (int)db_fetch_one($conn, "SELECT COUNT(*) AS c $from_sql", $types, $params)['c'];
$pages = max(1, (int)ceil($total / $per_page));
$page  = min($page, $pages);
$offset = ($page - 1) * $per_page;

$students = db_fetch_all($conn,
    "SELECT s.id, s.student_code, s.mobile, s.department, s.year_of_study, s.gender,
            u.id AS user_id, u.username, u.full_name, u.email, u.is_active, u.last_login,
            (SELECT COUNT(*) FROM registrations r WHERE r.student_id = s.id AND r.status = 'Confirmed') AS reg_count
     $from_sql ORDER BY s.student_code LIMIT ? OFFSET ?",
    $types . 'ii', [...$params, $per_page, $offset]);

// Links keep the current search and filters when paging
$filter_query = http_build_query(array_filter(['q' => $q, 'department' => $dept, 'status' => $status]));

require_once __DIR__ . '/includes/header.php';
?>

<h1>Manage Students</h1>
<p class="muted"><?php echo (int)$total; ?> student(s) found.</p>

<form class="filter-bar" method="get" action="manage_students.php" role="search" aria-label="Search and filter students">
  <div class="form-group">
    <label for="q">Search</label>
    <input type="search" id="q" name="q" value="<?php echo e($q); ?>" placeholder="Name, email, username or code">
  </div>
  <div class="form-group">
    <label for="department">Department</label>
    <select id="department" name="department">
      <option value="">All departments</option>
      <?php foreach (COURSES as $course): ?>
        <option value="<?php echo e($course); ?>" <?php echo $dept === $course ? 'selected' : ''; ?>><?php echo e($course); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label for="status">Status</label>
    <select id="status" name="status">
      <option value="">All</option>
      <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
      <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
    </select>
  </div>
  <div class="filter-actions">
    <button type="submit" class="btn btn-primary">Apply</button>
    <a class="btn btn-outline" href="manage_students.php">Reset</a>
    <a class="btn btn-outline" href="manage_students.php?<?php echo e(http_build_query(array_filter(['q' => $q, 'department' => $dept, 'status' => $status, 'export' => 'csv']))); ?>">Export CSV</a>
  </div>
</form>

<?php if (empty($students)): ?>
  <p class="empty-state">No students match your filters.</p>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <caption class="visually-hidden">Student list</caption>
      <thead>
        <tr>
          <th scope="col">Code</th><th scope="col">Name</th><th scope="col">Username</th><th scope="col">Department</th>
          <th scope="col">Year</th><th scope="col">Regs</th><th scope="col">Status</th><th scope="col">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
          <tr id="student-row-<?php echo (int)$s['id']; ?>">
            <td><code><?php echo e($s['student_code']); ?></code></td>
            <td><?php echo e($s['full_name']); ?><br><small class="muted"><?php echo e($s['email']); ?></small></td>
            <td><?php echo e($s['username']); ?></td>
            <td><?php echo e($s['department']); ?></td>
            <td><?php echo (int)$s['year_of_study']; ?></td>
            <td><?php echo (int)$s['reg_count']; ?></td>
            <td><span class="badge badge-<?php echo $s['is_active'] ? 'ok' : 'off'; ?>"><?php echo $s['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
            <td class="actions">
              <a class="btn btn-outline btn-small" href="manage_students.php?edit=<?php echo (int)$s['id']; ?>&amp;<?php echo e($filter_query); ?>#student-form">Edit</a>
              <button type="button" class="btn btn-outline btn-small"
                      data-api="api/students.php" data-action="toggle" data-id="<?php echo (int)$s['id']; ?>" data-reload>
                <?php echo $s['is_active'] ? 'Deactivate' : 'Activate'; ?>
              </button>
              <button type="button" class="btn btn-danger btn-small"
                      data-api="api/students.php" data-action="delete" data-id="<?php echo (int)$s['id']; ?>" data-reload
                      data-confirm="Delete <?php echo e($s['full_name']); ?> and all their registrations? This cannot be undone.">Delete</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav class="pagination" aria-label="Student pages">
    <?php if ($page > 1): ?>
      <a class="btn btn-outline btn-small" href="?<?php echo e($filter_query . '&page=' . ($page - 1)); ?>">&larr; Previous</a>
    <?php endif; ?>
    <span>Page <?php echo $page; ?> of <?php echo $pages; ?></span>
    <?php if ($page < $pages): ?>
      <a class="btn btn-outline btn-small" href="?<?php echo e($filter_query . '&page=' . ($page + 1)); ?>">Next &rarr;</a>
    <?php endif; ?>
  </nav>
<?php endif; ?>

<section class="form-card section" id="student-form" aria-labelledby="student-form-heading">
  <h2 id="student-form-heading"><?php echo $is_edit_form ? 'Edit student' : 'Add a new student'; ?></h2>
  <?php if (!empty($errors['form'])): ?>
    <div class="alert alert-danger" role="alert"><?php echo e($errors['form']); ?></div>
  <?php endif; ?>

  <form method="post" action="manage_students.php<?php echo $is_edit_form ? '?edit=' . (int)$form['student_id'] : ''; ?>" novalidate data-validate>
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="student_id" value="<?php echo (int)$form['student_id']; ?>">

    <div class="form-grid">
      <div class="form-group">
        <label for="a_full_name">Full name *</label>
        <input type="text" id="a_full_name" name="full_name" value="<?php echo e($form['full_name']); ?>" required
               data-rule="required name" data-label="Full name" aria-describedby="a_full_name-error">
        <p class="field-error" id="a_full_name-error" role="alert"><?php echo e($errors['full_name'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="a_username">Username *<?php echo $is_edit_form ? ' (cannot change)' : ''; ?></label>
        <input type="text" id="a_username" name="username" value="<?php echo e($form['username']); ?>"
               <?php echo $is_edit_form ? 'readonly' : 'required'; ?>
               data-rule="<?php echo $is_edit_form ? '' : 'required username'; ?>" data-label="Username" aria-describedby="a_username-error">
        <p class="field-error" id="a_username-error" role="alert"><?php echo e($errors['username'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="a_email">Email *</label>
        <input type="email" id="a_email" name="email" value="<?php echo e($form['email']); ?>" required
               data-rule="required email" data-label="Email" aria-describedby="a_email-error">
        <p class="field-error" id="a_email-error" role="alert"><?php echo e($errors['email'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="a_mobile">Mobile *</label>
        <input type="tel" id="a_mobile" name="mobile" value="<?php echo e($form['mobile']); ?>" maxlength="10" required
               data-rule="required mobile" data-label="Mobile" aria-describedby="a_mobile-error">
        <p class="field-error" id="a_mobile-error" role="alert"><?php echo e($errors['mobile'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="a_department">Department *</label>
        <select id="a_department" name="department" required data-rule="required select" data-label="Department" aria-describedby="a_department-error">
          <option value="">-- Select --</option>
          <?php foreach (COURSES as $course): ?>
            <option value="<?php echo e($course); ?>" <?php echo $form['department'] === $course ? 'selected' : ''; ?>><?php echo e($course); ?></option>
          <?php endforeach; ?>
        </select>
        <p class="field-error" id="a_department-error" role="alert"><?php echo e($errors['department'] ?? ''); ?></p>
      </div>
      <div class="form-group">
        <label for="a_year">Year *</label>
        <select id="a_year" name="year_of_study" required data-rule="required select" data-label="Year" aria-describedby="a_year-error">
          <option value="">-- Select --</option>
          <?php for ($y = 1; $y <= 4; $y++): ?>
            <option value="<?php echo $y; ?>" <?php echo (string)$form['year_of_study'] === (string)$y ? 'selected' : ''; ?>>Year <?php echo $y; ?></option>
          <?php endfor; ?>
        </select>
        <p class="field-error" id="a_year-error" role="alert"><?php echo e($errors['year_of_study'] ?? ''); ?></p>
      </div>
      <fieldset class="form-group">
        <legend>Gender *</legend>
        <div class="radio-row">
          <?php foreach (GENDERS as $g): ?>
            <label><input type="radio" name="gender" value="<?php echo e($g); ?>" data-rule="radio" data-label="Gender"
                   <?php echo $form['gender'] === $g ? 'checked' : ''; ?>> <?php echo e($g); ?></label>
          <?php endforeach; ?>
        </div>
        <p class="field-error" id="a_gender-error" role="alert"><?php echo e($errors['gender'] ?? ''); ?></p>
      </fieldset>
      <div class="form-group">
        <label for="a_password">Password <?php echo $is_edit_form ? '(leave blank to keep current)' : '*'; ?></label>
        <input type="password" id="a_password" name="password" autocomplete="new-password"
               data-rule="<?php echo $is_edit_form ? 'password' : 'required password'; ?>" <?php echo $is_edit_form ? 'data-optional="true"' : ''; ?>
               data-label="Password" data-strength="a_password-strength" aria-describedby="a_password-strength a_password-error">
        <p class="strength" id="a_password-strength" aria-live="polite"></p>
        <p class="field-error" id="a_password-error" role="alert"><?php echo e($errors['password'] ?? ''); ?></p>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><?php echo $is_edit_form ? 'Save changes' : 'Add student'; ?></button>
      <?php if ($is_edit_form): ?><a class="btn btn-outline" href="manage_students.php">Cancel edit</a><?php endif; ?>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
