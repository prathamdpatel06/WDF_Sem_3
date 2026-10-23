/* ==========================================================
   validation.js - instant (real-time) form validation (Practical 5).
   Rules are read from HTML attributes:
     data-rule="required name email username mobile password confirm select radio checkbox file number min:5 max:150"
     data-label="Field name shown in messages"
     data-match="id-of-other-field"       (for confirm password)
     data-strength="id-of-meter"          (password strength text)
     data-optional="true"                 (skip checks when empty)
     data-max-size / data-types           (for file inputs)
   The server ALSO validates everything (includes/validation.php).
   ========================================================== */
(function () {
  'use strict';

  // Same patterns as includes/validation.php
  var PATTERNS = {
    name: /^[A-Za-z][A-Za-z .'\-]{1,99}$/,
    username: /^[A-Za-z0-9_]{4,20}$/,
    email: /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/,
    mobile: /^[6-9][0-9]{9}$/
  };
  var MESSAGES = {
    name: 'Use letters only (2 to 100 characters).',
    username: 'Use 4 to 20 letters, numbers or underscores.',
    email: 'Enter a valid email address.',
    mobile: 'Enter a valid 10-digit mobile number starting with 6-9.',
    number: 'Enter a whole number.'
  };

  /** Returns a password problem text or '' when the password is acceptable. */
  function passwordProblem(value) {
    if (value.length < 8) { return 'Password must be at least 8 characters long.'; }
    if (!/[A-Za-z]/.test(value) || !/[0-9]/.test(value)) { return 'Password must contain at least one letter and one number.'; }
    return '';
  }

  /** Simple strength score: 0-4 */
  function passwordScore(value) {
    var score = 0;
    if (value.length >= 8) { score++; }
    if (/[A-Za-z]/.test(value) && /[0-9]/.test(value)) { score++; }
    if (value.length >= 12 || /[^A-Za-z0-9]/.test(value)) { score++; }
    if (/[a-z]/.test(value) && /[A-Z]/.test(value)) { score++; }
    return score;
  }

  function errorElementFor(input) {
    var describedBy = (input.getAttribute('aria-describedby') || '').split(' ');
    for (var i = 0; i < describedBy.length; i++) {
      var el = document.getElementById(describedBy[i]);
      if (el && /-error$/.test(describedBy[i])) { return el; }
    }
    if (input.type === 'radio') {
      var fieldset = input.closest('fieldset');
      return fieldset ? fieldset.querySelector('.field-error') : null;
    }
    return document.getElementById(input.id + '-error');
  }

  /** Returns an error message for one input, or '' when valid. */
  function checkInput(input) {
    var rules = (input.getAttribute('data-rule') || '').split(' ').filter(Boolean);
    var label = input.getAttribute('data-label') || 'This field';
    if (!rules.length) { return ''; }

    var value = input.type === 'checkbox' || input.type === 'radio' || input.type === 'file' ? '' : input.value.trim();

    if (input.type === 'checkbox') {
      return rules.indexOf('checkbox') !== -1 && !input.checked ? 'You must accept the ' + label.toLowerCase() + '.' : '';
    }
    if (input.type === 'radio') {
      var group = document.querySelectorAll('input[type="radio"][name="' + input.name + '"]');
      var anyChecked = Array.prototype.some.call(group, function (r) { return r.checked; });
      return anyChecked ? '' : 'Please select a ' + label.toLowerCase() + '.';
    }
    if (input.type === 'file') {
      if (!input.files || !input.files.length) { return ''; }
      var file = input.files[0];
      var types = (input.getAttribute('data-types') || '').split(',');
      if (types.length && types[0] && types.indexOf(file.type) === -1) { return 'Only JPG, PNG or WEBP images are allowed.'; }
      if (input.getAttribute('data-max-size') && file.size > Number(input.getAttribute('data-max-size'))) { return 'The file must be 2 MB or smaller.'; }
      return '';
    }

    if (value === '') {
      if (input.getAttribute('data-optional') === 'true') { return ''; }
      return rules.indexOf('required') !== -1 ? label + ' is required.' : '';
    }

    for (var i = 0; i < rules.length; i++) {
      var rule = rules[i];
      if (PATTERNS[rule] && !PATTERNS[rule].test(value)) { return MESSAGES[rule]; }
      if (rule === 'password') {
        var problem = passwordProblem(input.value);
        if (problem) { return problem; }
      }
      if (rule === 'number' && !/^[0-9]+$/.test(value)) { return MESSAGES.number; }
      if (rule === 'confirm') {
        var other = document.getElementById(input.getAttribute('data-match'));
        if (other && other.value !== input.value) { return 'The passwords do not match.'; }
      }
      if (rule.indexOf('min:') === 0 && value.length < Number(rule.slice(4))) { return label + ' must be at least ' + rule.slice(4) + ' characters.'; }
      if (rule.indexOf('max:') === 0 && value.length > Number(rule.slice(4))) { return label + ' can be at most ' + rule.slice(4) + ' characters.'; }
      if (rule === 'select' && value === '') { return 'Please choose ' + label.toLowerCase() + '.'; }
    }
    return '';
  }

  function showResult(input, message) {
    var errorEl = errorElementFor(input);
    if (errorEl) { errorEl.textContent = message; }
    if (input.type === 'radio') {
      document.querySelectorAll('input[type="radio"][name="' + input.name + '"]').forEach(function (r) {
        r.setAttribute('aria-invalid', message ? 'true' : 'false');
      });
    } else {
      input.setAttribute('aria-invalid', message ? 'true' : 'false');
    }
  }

  function updateStrength(input) {
    var meterId = input.getAttribute('data-strength');
    if (!meterId) { return; }
    var meter = document.getElementById(meterId);
    if (!meter) { return; }
    if (!input.value) { meter.textContent = ''; meter.className = 'strength'; return; }
    var labels = ['Very weak', 'Weak', 'Fair', 'Good', 'Strong'];
    var classes = ['weak', 'weak', 'fair', 'good', 'strong'];
    var score = passwordScore(input.value);
    meter.textContent = 'Password strength: ' + labels[score];
    meter.className = 'strength ' + classes[score];
  }

  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    var inputs = form.querySelectorAll('[data-rule]');

    // Real-time feedback while typing / leaving a field
    inputs.forEach(function (input) {
      var eventName = input.type === 'checkbox' || input.type === 'radio' || input.type === 'file' || input.tagName === 'SELECT' ? 'change' : 'input';
      input.addEventListener(eventName, function () {
        updateStrength(input);
        showResult(input, checkInput(input));
        // A changed password must re-check its confirmation field
        if (input.id) {
          form.querySelectorAll('[data-match="' + input.id + '"]').forEach(function (c) { showResult(c, checkInput(c)); });
        }
      });
      input.addEventListener('blur', function () { showResult(input, checkInput(input)); });
    });

    // Final check on submit
    form.addEventListener('submit', function (event) {
      var firstInvalid = null;
      var seenRadioGroups = {};
      inputs.forEach(function (input) {
        if (input.type === 'radio') {
          if (seenRadioGroups[input.name]) { return; }
          seenRadioGroups[input.name] = true;
        }
        var message = checkInput(input);
        showResult(input, message);
        if (message && !firstInvalid) { firstInvalid = input; }
      });
      if (firstInvalid) {
        event.preventDefault();
        firstInvalid.focus();
      }
    });

    // Password meter shown on page load (for values filled in by the browser)
    inputs.forEach(updateStrength);
  });
})();
