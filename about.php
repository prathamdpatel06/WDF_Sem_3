<?php
require_once __DIR__ . '/includes/bootstrap.php';

$page_title  = 'About | StudentHUB';
$breadcrumbs = [['index.php', 'Home'], ['about.php', 'About']];
require_once __DIR__ . '/includes/header.php';
?>

<h1>About StudentHUB</h1>

<section class="section" aria-labelledby="mission-heading">
  <div class="grid-2">
    <div class="card">
      <h2 id="mission-heading">Our mission</h2>
      <p>StudentHUB brings campus life together in one secure portal. Students can discover events, register, manage their profile and send feedback, while administrators manage students and events with clear records.</p>
    </div>
    <div class="card">
      <h2>Our vision</h2>
      <p>A simple, accessible and reliable digital campus where every student can take part in academic and cultural activities.</p>
    </div>
  </div>
</section>

<section class="section" aria-labelledby="features-list-heading">
  <h2 id="features-list-heading">What you can do</h2>
  <ul class="feature-list">
    <li><strong>Secure accounts:</strong> register, log in with hashed passwords, stay logged in with "Remember me".</li>
    <li><strong>Events:</strong> search and filter events, see details and register or cancel.</li>
    <li><strong>Dashboard:</strong> view your registered events and recent activity.</li>
    <li><strong>Feedback and support:</strong> send feedback to the college and contact the support team.</li>
    <li><strong>Admin tools:</strong> manage students, events, posters and view the audit log.</li>
  </ul>
</section>

<section class="section" aria-labelledby="institution-heading">
  <h2 id="institution-heading">Institution</h2>
  <p>Charotar University of Science and Technology (CHARUSAT), Faculty of Technology and Engineering. Subject: Web Development Frameworks (ITUE203), Semester 3, 2026-27.</p>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
