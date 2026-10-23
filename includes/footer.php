<?php
// ============================================================
// footer.php - closes <main>, shows the footer, toast area and loads JavaScript.
// Pages can add extra scripts by setting $page_scripts = ['assets/js/x.js'];
// ============================================================
?>
  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <nav aria-label="Footer navigation">
        <ul class="footer-links">
          <li><a href="index.php">Home</a></li>
          <li><a href="about.php">About</a></li>
          <li><a href="events.php">Events</a></li>
          <li><a href="faq.php">FAQ</a></li>
          <li><a href="contact.php">Contact</a></li>
          <?php if (!is_logged_in()): ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
          <?php endif; ?>
        </ul>
      </nav>
      <p class="copyright">&copy; <?php echo date('Y'); ?> StudentHUB Integrated Academic Portal. CHARUSAT FTE - Web Development Frameworks (ITUE203).</p>
    </div>
  </footer>

  <div id="toast-container" class="toast-container" aria-live="polite"></div>

  <script src="assets/js/main.js"></script>
  <?php foreach ($page_scripts as $script): ?>
    <script src="<?php echo e($script); ?>"></script>
  <?php endforeach; ?>
</body>
</html>
