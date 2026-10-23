<?php
require_once __DIR__ . '/includes/bootstrap.php';

$page_title   = 'FAQ | StudentHUB';
$breadcrumbs  = [['index.php', 'Home'], ['faq.php', 'FAQ']];
$page_scripts = ['assets/js/faq.js'];
require_once __DIR__ . '/includes/header.php';
?>

<h1>Frequently Asked Questions</h1>
<p class="muted">Answers are loaded from <code>data/faqs.json</code> using the Fetch API.</p>

<form class="filter-bar" id="faq-filter-form" role="search" aria-label="Search FAQ">
  <div class="form-group">
    <label for="faq-q">Search questions</label>
    <input type="search" id="faq-q" placeholder="e.g. password" autocomplete="off">
  </div>
  <div class="form-group">
    <label for="faq-category">Category</label>
    <select id="faq-category"><option value="">All</option></select>
  </div>
  <div class="form-group">
    <label for="faq-sort">Sort by</label>
    <select id="faq-sort">
      <option value="default">Default order</option>
      <option value="az">Question A-Z</option>
    </select>
  </div>
</form>

<p id="faq-status" class="status-line" role="status">Loading FAQs...</p>
<div id="faq-list" class="accordion"></div>

<nav class="pagination" aria-label="FAQ pages" id="faq-pagination" hidden>
  <button type="button" class="btn btn-outline btn-small" id="faq-prev">&larr; Previous</button>
  <span id="faq-page-info" aria-live="polite"></span>
  <button type="button" class="btn btn-outline btn-small" id="faq-next">Next &rarr;</button>
</nav>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
