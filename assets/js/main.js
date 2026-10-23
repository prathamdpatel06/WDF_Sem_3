/* ==========================================================
   main.js - runs on every page.
   Hamburger menu, light/dark theme, notice banner, modal popup,
   toast messages, Fetch API helpers and AJAX buttons.
   ========================================================== */
(function () {
  'use strict';

  var THEME_KEY = 'studenthub-theme';
  var BANNER_KEY = 'studenthub-banner-closed';

  // Safe storage access: private mode or blocked storage must not break the page.
  function storageGet(key) { try { return window.localStorage.getItem(key); } catch (err) { return null; } }
  function storageSet(key, value) { try { window.localStorage.setItem(key, value); } catch (err) { /* ignore */ } }

  // ---------- Namespace shared with the other scripts ----------
  window.StudentHub = window.StudentHub || {};

  StudentHub.csrfToken = function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  };

  /** POST JSON to an API file. Always resolves with an object that has success + message. */
  StudentHub.postJSON = function (url, data) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': StudentHub.csrfToken() },
      body: JSON.stringify(data || {})
    }).then(function (response) {
      return response.json().catch(function () {
        return { success: false, message: 'Unexpected server response (' + response.status + ').' };
      });
    });
  };

  /** GET JSON. Rejects when the network fails or the status is not OK. */
  StudentHub.getJSON = function (url) {
    return fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (response) {
        if (!response.ok) { throw new Error('Request failed with status ' + response.status); }
        return response.json();
      });
  };

  /** Small pop-up message in the bottom-right corner (role=status keeps it announced). */
  StudentHub.showToast = function (message, type) {
    var container = document.getElementById('toast-container');
    if (!container) { return; }
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + (type || 'info');
    toast.setAttribute('role', type === 'danger' ? 'alert' : 'status');
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(function () { toast.remove(); }, 3800);
  };

  // ---------- Hamburger menu ----------
  var hamburger = document.getElementById('hamburger-toggle');
  var navMenu = document.getElementById('nav-menu');
  if (hamburger && navMenu) {
    hamburger.addEventListener('click', function () {
      var open = navMenu.classList.toggle('is-open');
      hamburger.setAttribute('aria-expanded', open ? 'true' : 'false');
      hamburger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    });
  }

  // ---------- Light / dark theme ----------
  var themeButton = document.getElementById('theme-toggle-btn');
  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    if (themeButton) {
      themeButton.textContent = theme === 'dark' ? 'Light Mode' : 'Dark Mode';
      themeButton.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    }
  }
  applyTheme(document.documentElement.getAttribute('data-theme') || 'light');
  if (themeButton) {
    themeButton.addEventListener('click', function () {
      var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      applyTheme(next);
      storageSet(THEME_KEY, next);
    });
  }

  // ---------- Notice banner (remembers that it was closed) ----------
  var banner = document.getElementById('notice-banner');
  var closeBanner = document.getElementById('close-banner-btn');
  if (banner && storageGet(BANNER_KEY) === 'yes') {
    banner.hidden = true;
  }
  if (banner && closeBanner) {
    closeBanner.addEventListener('click', function () {
      banner.classList.add('is-hidden');
      storageSet(BANNER_KEY, 'yes');
      setTimeout(function () { banner.hidden = true; }, 300);
    });
  }

  // ---------- Modal popup ----------
  StudentHub.openModal = function (id) {
    var modal = document.getElementById(id);
    if (!modal) { return; }
    modal.hidden = false;
    modal.dataset.returnFocus = document.activeElement ? document.activeElement.id || '' : '';
    var first = modal.querySelector('button, a, [tabindex]');
    if (first) { first.focus(); }
  };
  StudentHub.closeModal = function (modal) {
    modal.hidden = true;
    var returnId = modal.dataset.returnFocus;
    if (returnId && document.getElementById(returnId)) { document.getElementById(returnId).focus(); }
  };
  document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
    btn.addEventListener('click', function () { StudentHub.openModal(btn.getAttribute('data-modal-open')); });
  });
  document.querySelectorAll('.modal').forEach(function (modal) {
    modal.addEventListener('click', function (event) {
      if (event.target === modal || event.target.hasAttribute('data-modal-close')) { StudentHub.closeModal(modal); }
    });
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal:not([hidden])').forEach(function (m) { StudentHub.closeModal(m); });
    }
  });

  // ---------- AJAX buttons: <button data-api="api/x.php" data-action="..." data-id="..."> ----------
  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-api]');
    if (!button) { return; }
    event.preventDefault();

    var message = button.getAttribute('data-confirm');
    if (message && !window.confirm(message)) { return; }

    button.disabled = true;
    StudentHub.postJSON(button.getAttribute('data-api'), {
      action: button.getAttribute('data-action'),
      id: button.getAttribute('data-id')
    }).then(function (result) {
      button.disabled = false;
      StudentHub.showToast(result.message, result.success ? 'success' : 'danger');
      if (result.success) {
        document.dispatchEvent(new CustomEvent('studenthub:changed', { detail: result }));
        if (button.hasAttribute('data-reload')) {
          setTimeout(function () { window.location.reload(); }, 600);   // flash messages show after reload
        }
      }
    }).catch(function () {
      button.disabled = false;
      StudentHub.showToast('Network error. Please check your connection and try again.', 'danger');
    });
  });

  // ---------- Forms that need a confirmation (<form data-confirm="...">) ----------
  document.addEventListener('submit', function (event) {
    var form = event.target;
    var message = form.getAttribute('data-confirm');
    if (message && !window.confirm(message)) {
      event.preventDefault();
    }
  });
})();
