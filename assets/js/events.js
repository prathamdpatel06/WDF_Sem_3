/* ==========================================================
   events.js - Events page: AJAX search (debounced), filters, pagination,
   event details popup and register / cancel buttons (Practical 4, 6, 13).
   ========================================================== */
(function () {
  'use strict';

  var form = document.getElementById('event-filter-form');
  var list = document.getElementById('events-list');
  var status = document.getElementById('events-status');
  var pager = document.getElementById('events-pagination');
  var pageInfo = document.getElementById('events-page-info');
  var prevBtn = document.getElementById('events-prev');
  var nextBtn = document.getElementById('events-next');
  var modalBody = document.getElementById('event-modal-body');
  if (!form || !list) { return; }

  var state = { page: 1, pages: 1, limit: 6 };
  var debounceTimer = null;

  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) { node.className = className; }
    if (text !== undefined) { node.textContent = text; }
    return node;
  }

  function formatDate(iso) {
    return new Date(iso + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  /** Builds the query string from the filter form. */
  function queryString() {
    var data = new FormData(form);
    var params = new URLSearchParams();
    params.set('action', 'list');
    params.set('q', (data.get('q') || '').trim());
    params.set('category', data.get('category') || '');
    params.set('status', data.get('status') || '');
    if (form.elements.upcoming.checked) { params.set('upcoming', '1'); }
    params.set('page', String(state.page));
    params.set('limit', String(state.limit));
    return 'api/events.php?' + params.toString();
  }

  function showLoading() {
    status.textContent = 'Loading events...';
    status.setAttribute('role', 'status');
    list.innerHTML = '';
    pager.hidden = true;
  }

  function showError(message) {
    list.innerHTML = '';
    status.innerHTML = '';
    var alert = el('div', 'alert alert-danger', message + ' ');
    var retry = el('button', 'btn btn-outline btn-small', 'Try again');
    retry.type = 'button';
    retry.addEventListener('click', loadEvents);
    alert.appendChild(retry);
    status.appendChild(alert);
  }

  /** One card per event. */
  function buildCard(ev) {
    var card = el('article', 'event-card');
    card.setAttribute('aria-labelledby', 'event-title-' + ev.id);

    if (ev.poster) {
      var img = el('img', 'poster');
      img.src = ev.poster;
      img.alt = 'Poster for ' + ev.title;
      img.loading = 'lazy';
      card.appendChild(img);
    } else {
      var placeholder = el('div', 'poster-placeholder', ev.category);
      placeholder.setAttribute('aria-hidden', 'true');
      card.appendChild(placeholder);
    }

    var body = el('div', 'event-body');
    var title = el('h3', '', ev.title);
    title.id = 'event-title-' + ev.id;
    body.appendChild(title);

    var meta = el('ul', 'event-meta');
    meta.appendChild(el('li', '', formatDate(ev.event_date) + ' · ' + ev.location));
    meta.appendChild(el('li', '', ev.category + ' · ' + ev.seats_left + ' of ' + ev.seats_limit + ' seats left'));
    var stateText = ev.is_past ? 'Event has ended' : (ev.status === 'Closed' ? 'Registration closed' : (ev.seats_left === 0 ? 'Fully booked' : 'Open for registration'));
    meta.appendChild(el('li', '', stateText));
    body.appendChild(meta);

    var actions = el('div', 'event-actions');
    var details = el('button', 'btn btn-outline btn-small', 'Details');
    details.type = 'button';
    details.addEventListener('click', function () { openDetails(ev); });
    actions.appendChild(details);

    var loggedInStudent = window.STUDENTHUB && window.STUDENTHUB.loggedIn && window.STUDENTHUB.role === 'student';
    if (!window.STUDENTHUB || !window.STUDENTHUB.loggedIn) {
      var login = el('a', 'btn btn-primary btn-small', 'Login to register');
      login.href = 'login.php';
      actions.appendChild(login);
    } else if (loggedInStudent && ev.is_registered) {
      actions.appendChild(el('span', 'badge badge-ok', 'Registered'));
      var cancel = el('button', 'btn btn-danger btn-small', 'Cancel');
      cancel.type = 'button';
      cancel.setAttribute('data-api', 'api/registrations.php');
      cancel.setAttribute('data-action', 'cancel');
      cancel.setAttribute('data-id', String(ev.id));
      cancel.setAttribute('data-confirm', 'Cancel your registration for ' + ev.title + '?');
      actions.appendChild(cancel);
    } else if (loggedInStudent && ev.status === 'Open' && !ev.is_past && ev.seats_left > 0) {
      var register = el('button', 'btn btn-primary btn-small', 'Register');
      register.type = 'button';
      register.setAttribute('data-api', 'api/registrations.php');
      register.setAttribute('data-action', 'register');
      register.setAttribute('data-id', String(ev.id));
      actions.appendChild(register);
    }
    body.appendChild(actions);
    card.appendChild(body);
    return card;
  }

  /** Fills and opens the event details popup. */
  function openDetails(ev) {
    modalBody.innerHTML = '';
    if (ev.poster) {
      var img = el('img', 'poster');
      img.src = ev.poster;
      img.alt = 'Poster for ' + ev.title;
      img.style.marginBottom = '12px';
      img.style.borderRadius = '8px';
      modalBody.appendChild(img);
    }
    document.getElementById('event-modal-title').textContent = ev.title;
    modalBody.appendChild(el('p', '', ev.description || 'No description available.'));
    var dl = el('ul', 'event-meta');
    dl.appendChild(el('li', '', 'Date: ' + formatDate(ev.event_date)));
    dl.appendChild(el('li', '', 'Location: ' + ev.location));
    dl.appendChild(el('li', '', 'Category: ' + ev.category));
    dl.appendChild(el('li', '', 'Seats left: ' + ev.seats_left + ' of ' + ev.seats_limit));
    modalBody.appendChild(dl);
    StudentHub.openModal('event-modal');
  }

  function render(data) {
    list.innerHTML = '';
    state.pages = data.pages;
    if (!data.items.length) {
      status.textContent = 'No events match your search. Try different filters.';
      pager.hidden = true;
      return;
    }
    status.textContent = data.total + ' event(s) found.';
    data.items.forEach(function (ev) { list.appendChild(buildCard(ev)); });
    pageInfo.textContent = 'Page ' + data.page + ' of ' + data.pages;
    prevBtn.disabled = data.page <= 1;
    nextBtn.disabled = data.page >= data.pages;
    pager.hidden = data.pages <= 1;
  }

  function loadEvents() {
    showLoading();
    StudentHub.getJSON(queryString())
      .then(function (result) {
        if (!result.success) { throw new Error(result.message); }
        render(result.data);
      })
      .catch(function (err) {
        showError('Events could not be loaded. ' + (err && err.message ? '(' + err.message + ')' : ''));
      });
  }

  // Search: wait 300 ms after the user stops typing (debounce)
  form.elements.q.addEventListener('input', function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function () { state.page = 1; loadEvents(); }, 300);
  });
  ['category', 'status', 'upcoming'].forEach(function (name) {
    form.elements[name].addEventListener('change', function () { state.page = 1; loadEvents(); });
  });
  form.addEventListener('submit', function (event) { event.preventDefault(); state.page = 1; loadEvents(); });

  prevBtn.addEventListener('click', function () { if (state.page > 1) { state.page--; loadEvents(); } });
  nextBtn.addEventListener('click', function () { if (state.page < state.pages) { state.page++; loadEvents(); } });

  // Register / cancel buttons send a request, then the list is refreshed without a page reload
  document.addEventListener('studenthub:changed', loadEvents);

  loadEvents();
})();
