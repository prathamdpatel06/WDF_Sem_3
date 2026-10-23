<?php
require_once __DIR__ . '/includes/bootstrap.php';

$page_title   = 'StudentHUB | Home';
$page_scripts = ['assets/js/home.js'];
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero" aria-labelledby="hero-heading">
  <div class="hero-text">
    <p class="eyebrow">CHARUSAT - Faculty of Technology and Engineering</p>
    <h1 id="hero-heading">Welcome to StudentHUB</h1>
    <p class="lead">Find campus events, register in one click and keep track of your academic activities in one place.</p>
    <div class="hero-actions">
      <?php if (is_logged_in()): ?>
        <a class="btn btn-primary" href="<?php echo e(home_for_role(current_role())); ?>">Go to my dashboard</a>
      <?php else: ?>
        <a class="btn btn-primary" href="register.php">Create account</a>
        <a class="btn btn-outline" href="login.php">Login</a>
      <?php endif; ?>
      <a class="btn btn-outline" href="events.php">Browse events</a>
    </div>
  </div>
  <img class="hero-image" src="assets/images/feature_dashboard.jpg" alt="Students working together on laptops" width="640" height="420">
</section>

<section aria-labelledby="features-heading" class="section">
  <h2 id="features-heading">Portal features</h2>
  <div class="grid-3">
    <article class="card">
      <img src="assets/images/feature_events.jpg" alt="" class="card-img" loading="lazy">
      <h3>Events</h3>
      <p>Search, filter and register for hackathons, workshops, seminars and cultural events.</p>
    </article>
    <article class="card">
      <img src="assets/images/feature_profile.jpg" alt="" class="card-img" loading="lazy">
      <h3>Profile</h3>
      <p>Keep your academic and contact details up to date and change your password safely.</p>
    </article>
    <article class="card">
      <img src="assets/images/feature_dashboard.jpg" alt="" class="card-img" loading="lazy">
      <h3>Dashboard</h3>
      <p>See your upcoming events, your recent activity and share feedback with the college.</p>
    </article>
  </div>
</section>

<section class="section" aria-labelledby="upcoming-heading">
  <div class="section-head">
    <h2 id="upcoming-heading">Upcoming events</h2>
    <a href="events.php">See all events</a>
  </div>
  <p id="upcoming-status" class="status-line" role="status">Loading upcoming events...</p>
  <div id="upcoming-list" class="grid-3" aria-live="polite"></div>
</section>

<section class="section" aria-labelledby="highlights-heading">
  <h2 id="highlights-heading">Highlights</h2>
  <div class="slider" data-slider>
    <div class="slides">
      <figure class="slide is-active">
        <img src="assets/images/event_techfest.jpg" alt="TechFest 2026 poster" loading="lazy">
        <figcaption>TechFest 2026 - project showcases and keynote talks.</figcaption>
      </figure>
      <figure class="slide">
        <img src="assets/images/event_hackathon.jpg" alt="24-hour hackathon poster" loading="lazy">
        <figcaption>24-Hour Campus Hackathon - form a team of four.</figcaption>
      </figure>
      <figure class="slide">
        <img src="assets/images/event_photography.jpg" alt="Photography workshop poster" loading="lazy">
        <figcaption>Creative Photography Workshop - limited seats, register early.</figcaption>
      </figure>
    </div>
    <div class="slider-controls">
      <button type="button" class="btn btn-outline btn-small" data-slider-prev aria-label="Previous slide">&larr; Prev</button>
      <div class="dots" data-slider-dots></div>
      <button type="button" class="btn btn-outline btn-small" data-slider-next aria-label="Next slide">Next &rarr;</button>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
