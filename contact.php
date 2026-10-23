<?php
require_once __DIR__ . '/includes/bootstrap.php';

$page_title   = 'Contact | StudentHUB';
$breadcrumbs  = [['index.php', 'Home'], ['contact.php', 'Contact']];
$page_scripts = ['assets/js/validation.js'];
$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if (is_logged_in()) {   // pre-fill the form for logged-in students
    $old['name'] = current_full_name();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $field => $_) {
        $old[$field] = trim($_POST[$field] ?? '');
    }
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your form session expired. Please submit the form again.';
    } else {
        if (!is_valid_name($old['name'])) {
            $errors['name'] = 'Name: letters only, 2 to 100 characters.';
        }
        if (!is_valid_email($old['email'])) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (mb_strlen($old['subject']) < 5 || mb_strlen($old['subject']) > 150) {
            $errors['subject'] = 'Subject must be between 5 and 150 characters.';
        }
        if (mb_strlen($old['message']) < 10 || mb_strlen($old['message']) > 1000) {
            $errors['message'] = 'Message must be between 10 and 1000 characters.';
        }

        if (empty($errors)) {
            try {
                // Save to the database
                db_execute($conn, "INSERT INTO contact_inquiries (name, email, subject, message) VALUES (?, ?, ?, ?)",
                    'ssss', [$old['name'], $old['email'], $old['subject'], $old['message']]);
                // Also keep a CSV copy (Practical 7)
                append_csv(STORAGE_DIR . 'contact_submissions.csv', [
                    'submitted_at' => date('Y-m-d H:i:s'),
                    'name' => $old['name'], 'email' => $old['email'],
                    'subject' => $old['subject'], 'message' => $old['message'],
                ]);
                audit_log($conn, 'CONTACT_MESSAGE', 'Subject: ' . $old['subject']);
                set_flash('success', 'Thank you! Your message has been sent to the support team.');
                redirect('contact.php');
            } catch (Throwable $e) {
                error_log('Contact error: ' . $e->getMessage());
                $errors['form'] = 'Your message could not be sent right now. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1>Contact us</h1>

<div class="grid-2">
  <section class="card" aria-labelledby="info-heading">
    <h2 id="info-heading">Contact information</h2>
    <p><strong>Office:</strong> Student Affairs, Block A, CHARUSAT campus, Changa</p>
    <p><strong>Email:</strong> <a href="mailto:support@studenthub.edu">support@studenthub.edu</a></p>
    <p><strong>Phone:</strong> +91 2697 000000</p>
    <p><strong>Office hours:</strong> Monday to Friday, 9:30 AM - 5:00 PM</p>
  </section>

  <section class="form-card" aria-labelledby="contact-form-heading">
    <h2 id="contact-form-heading">Send a message</h2>

    <?php if (!empty($errors['form'])): ?>
      <div class="alert alert-danger" role="alert"><?php echo e($errors['form']); ?></div>
    <?php endif; ?>

    <form method="post" action="contact.php" novalidate data-validate>
      <?php echo csrf_field(); ?>

      <div class="form-group">
        <label for="name">Your name <span class="required" aria-hidden="true">*</span></label>
        <input type="text" id="name" name="name" required value="<?php echo e($old['name']); ?>"
               data-rule="required name" data-label="Name" aria-describedby="name-error">
        <p class="field-error" id="name-error" role="alert"><?php echo e($errors['name'] ?? ''); ?></p>
      </div>

      <div class="form-group">
        <label for="email">Email <span class="required" aria-hidden="true">*</span></label>
        <input type="email" id="email" name="email" required value="<?php echo e($old['email']); ?>"
               data-rule="required email" data-label="Email" aria-describedby="email-error">
        <p class="field-error" id="email-error" role="alert"><?php echo e($errors['email'] ?? ''); ?></p>
      </div>

      <div class="form-group">
        <label for="subject">Subject <span class="required" aria-hidden="true">*</span></label>
        <input type="text" id="subject" name="subject" required value="<?php echo e($old['subject']); ?>"
               data-rule="required min:5 max:150" data-label="Subject" aria-describedby="subject-error">
        <p class="field-error" id="subject-error" role="alert"><?php echo e($errors['subject'] ?? ''); ?></p>
      </div>

      <div class="form-group">
        <label for="message">Message <span class="required" aria-hidden="true">*</span></label>
        <textarea id="message" name="message" rows="5" required data-rule="required min:10 max:1000" data-label="Message"
                  aria-describedby="message-error"><?php echo e($old['message']); ?></textarea>
        <p class="field-error" id="message-error" role="alert"><?php echo e($errors['message'] ?? ''); ?></p>
      </div>

      <button type="submit" class="btn btn-primary">Send message</button>
    </form>
  </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
