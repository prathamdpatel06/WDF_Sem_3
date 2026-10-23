/* ==========================================================
   home.js - Home page: upcoming events (Fetch API + fallback JSON) and the image slider.
   ========================================================== */
(function () {
  'use strict';

  var list = document.getElementById('upcoming-list');
  var status = document.getElementById('upcoming-status');

  /** Creates one event card. Text is inserted with textContent to avoid HTML injection. */
  function buildCard(ev) {
    var card = document.createElement('article');
    card.className = 'event-card';

    if (ev.poster) {
      var img = document.createElement('img');
      img.className = 'poster';
      img.src = ev.poster;
      img.alt = 'Poster for ' + ev.title;
      img.loading = 'lazy';
      card.appendChild(img);
    } else {
      var placeholder = document.createElement('div');
      placeholder.className = 'poster-placeholder';
      placeholder.setAttribute('aria-hidden', 'true');
      placeholder.textContent = ev.category;
      card.appendChild(placeholder);
    }

    var body = document.createElement('div');
    body.className = 'event-body';
    var title = document.createElement('h3');
    title.textContent = ev.title;
    var meta = document.createElement('p');
    meta.className = 'event-meta';
    meta.textContent = formatDate(ev.event_date) + ' · ' + ev.location;
    var more = document.createElement('a');
    more.href = 'events.php';
    more.className = 'btn btn-outline btn-small';
    more.textContent = 'View and register';
    body.appendChild(title);
    body.appendChild(meta);
    body.appendChild(more);
    card.appendChild(body);
    return card;
  }

  function formatDate(iso) {
    var d = new Date(iso + 'T00:00:00');
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  function render(events, note) {
    list.innerHTML = '';
    if (!events.length) {
      status.textContent = 'No upcoming events right now. Check back soon.';
      return;
    }
    events.forEach(function (ev) { list.appendChild(buildCard(ev)); });
    status.textContent = note || '';
  }

  /** Live data from the API; if that fails, the sample file data/events.json is used. */
  function loadUpcoming() {
    if (!list) { return; }
    StudentHub.getJSON('api/events.php?action=list&upcoming=1&status=Open&limit=3')
      .then(function (result) {
        if (!result.success) { throw new Error(result.message); }
        render(result.data.items, '');
      })
      .catch(function () {
        StudentHub.getJSON('data/events.json')
          .then(function (events) {
            var upcoming = events.filter(function (ev) { return ev.status === 'Open' && ev.event_date >= new Date().toISOString().slice(0, 10); })
              .slice(0, 3)
              .map(function (ev) { return { title: ev.title, category: ev.category, event_date: ev.event_date, location: ev.location, poster: ev.poster || null }; });
            render(upcoming, 'Live data is unavailable, so a sample schedule is shown.');
          })
          .catch(function () {
            status.textContent = 'Could not load events. Please refresh the page.';
          });
      });
  }

  /** Simple slider with previous / next buttons and dots. */
  function setupSlider(root) {
    var slides = root.querySelectorAll('.slide');
    var dotsBox = root.querySelector('[data-slider-dots]');
    var current = 0;
    if (!slides.length) { return; }

    slides.forEach(function (_, i) {
      var dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'dot';
      dot.setAttribute('aria-label', 'Show slide ' + (i + 1));
      dot.addEventListener('click', function () { show(i); });
      dotsBox.appendChild(dot);
    });

    function show(index) {
      current = (index + slides.length) % slides.length;
      slides.forEach(function (s, i) { s.classList.toggle('is-active', i === current); });
      dotsBox.querySelectorAll('.dot').forEach(function (d, i) {
        d.classList.toggle('is-active', i === current);
        d.setAttribute('aria-current', i === current ? 'true' : 'false');
      });
    }

    root.querySelector('[data-slider-prev]').addEventListener('click', function () { show(current - 1); });
    root.querySelector('[data-slider-next]').addEventListener('click', function () { show(current + 1); });
    root.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') { show(current - 1); }
      if (event.key === 'ArrowRight') { show(current + 1); }
    });
    show(0);
  }

  document.querySelectorAll('[data-slider]').forEach(setupSlider);
  loadUpcoming();
})();
