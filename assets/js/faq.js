/* ==========================================================
   faq.js - FAQ page: loads data/faqs.json with the Fetch API, then
   search, category filter, sorting, pagination and accordion (Practical 4, 6).
   ========================================================== */
(function () {
  'use strict';

  var PER_PAGE = 5;
  var search = document.getElementById('faq-q');
  var category = document.getElementById('faq-category');
  var sort = document.getElementById('faq-sort');
  var list = document.getElementById('faq-list');
  var status = document.getElementById('faq-status');
  var pager = document.getElementById('faq-pagination');
  var pageInfo = document.getElementById('faq-page-info');
  var prevBtn = document.getElementById('faq-prev');
  var nextBtn = document.getElementById('faq-next');
  if (!list) { return; }

  var faqs = [];
  var page = 1;

  function populateCategories() {
    var names = [];
    faqs.forEach(function (f) { if (names.indexOf(f.category) === -1) { names.push(f.category); } });
    names.sort().forEach(function (name) {
      var option = document.createElement('option');
      option.value = name;
      option.textContent = name;
      category.appendChild(option);
    });
  }

  /** Returns the filtered and sorted list. */
  function visibleFaqs() {
    var term = search.value.trim().toLowerCase();
    var result = faqs.filter(function (f) {
      var matchesCategory = !category.value || f.category === category.value;
      var matchesText = !term || f.question.toLowerCase().indexOf(term) !== -1 || f.answer.toLowerCase().indexOf(term) !== -1;
      return matchesCategory && matchesText;
    });
    if (sort.value === 'az') {
      result.sort(function (a, b) { return a.question.localeCompare(b.question); });
    }
    return result;
  }

  function render() {
    var items = visibleFaqs();
    var pages = Math.max(1, Math.ceil(items.length / PER_PAGE));
    page = Math.min(page, pages);
    var slice = items.slice((page - 1) * PER_PAGE, page * PER_PAGE);

    list.innerHTML = '';
    if (!items.length) {
      status.textContent = 'No questions match your search.';
      pager.hidden = true;
      return;
    }
    status.textContent = items.length + ' question(s) found.';

    slice.forEach(function (faq, index) {
      var item = document.createElement('div');
      item.className = 'accordion-item';

      var heading = document.createElement('h3');
      heading.style.margin = '0';
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'accordion-btn';
      button.setAttribute('aria-expanded', 'false');
      var panelId = 'faq-panel-' + faq.id;
      button.setAttribute('aria-controls', panelId);
      button.textContent = faq.question;
      var tag = document.createElement('span');
      tag.className = 'tag';
      tag.textContent = faq.category;
      button.appendChild(tag);
      heading.appendChild(button);

      var panel = document.createElement('div');
      panel.className = 'accordion-panel';
      panel.id = panelId;
      panel.hidden = true;
      panel.textContent = faq.answer;

      button.addEventListener('click', function () {
        var open = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', open ? 'false' : 'true');
        panel.hidden = open;
      });

      item.appendChild(heading);
      item.appendChild(panel);
      list.appendChild(item);
    });

    pageInfo.textContent = 'Page ' + page + ' of ' + pages;
    prevBtn.disabled = page <= 1;
    nextBtn.disabled = page >= pages;
    pager.hidden = pages <= 1;
  }

  function showError() {
    status.innerHTML = '';
    var alert = document.createElement('div');
    alert.className = 'alert alert-danger';
    alert.setAttribute('role', 'alert');
    alert.textContent = 'The FAQ could not be loaded. Please refresh the page.';
    status.appendChild(alert);
  }

  search.addEventListener('input', function () { page = 1; render(); });
  category.addEventListener('change', function () { page = 1; render(); });
  sort.addEventListener('change', function () { page = 1; render(); });
  prevBtn.addEventListener('click', function () { page--; render(); });
  nextBtn.addEventListener('click', function () { page++; render(); });
  document.getElementById('faq-filter-form').addEventListener('submit', function (e) { e.preventDefault(); });

  status.textContent = 'Loading FAQs...';
  StudentHub.getJSON('data/faqs.json')
    .then(function (data) {
      faqs = Array.isArray(data) ? data : data.faqs;
      populateCategories();
      render();
    })
    .catch(showError);
})();
