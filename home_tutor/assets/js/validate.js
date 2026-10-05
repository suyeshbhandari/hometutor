// Client-side validation for the register and login forms.
// (The PHP code validates again on the server, so this is only for quick feedback.)
(function () {
  // Same rules as includes/functions.php
  var NAME_RE  = /^[A-Za-z][A-Za-z .'-]{1,49}$/;
  var EMAIL_RE = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/;
  var PHONE_RE = /^9[6-8][0-9]{8}$/;

  function getError(input) {
    var field = input.closest('.field');
    return field ? field.querySelector('.field-error') : null;
  }
  function showError(input, message) {
    var box = getError(input);
    if (box) { box.textContent = message; }
    input.classList.add('invalid');
    input.classList.remove('valid');
  }
  function clearError(input) {
    var box = getError(input);
    if (box) { box.textContent = ''; }
    input.classList.remove('invalid');
  }

  // Each checker returns '' when OK, or an error message.
  var checks = {
    name: function (v) {
      v = v.trim();
      if (v === '') return 'Full name is required.';
      if (!NAME_RE.test(v)) return "Name must be 2-50 characters and contain only letters, spaces, . ' or -.";
      return '';
    },
    email: function (v) {
      v = v.trim();
      if (v === '') return 'Email is required.';
      if (v.length > 100) return 'Email must be 100 characters or fewer.';
      if (!EMAIL_RE.test(v)) return 'Please enter a valid email address (example: name@gmail.com).';
      return '';
    },
    phone: function (v) {
      v = v.trim();
      if (v === '') return 'Phone number is required.';
      if (!PHONE_RE.test(v)) return 'Enter a 10-digit Nepali mobile number starting with 96, 97 or 98.';
      return '';
    },
    password: function (v) {
      if (v === '') return 'Password is required.';
      if (v.length < 8 || v.length > 64) return 'Password must be 8 to 64 characters long.';
      if (!/[A-Za-z]/.test(v) || !/[0-9]/.test(v)) return 'Password must contain at least one letter and one number.';
      return '';
    }
  };

  function setup(form, fields, withConfirm) {
    function checkField(input) {
      var msg = '';
      if (input.name === 'confirm_password') {
        var pw = form.elements['password'].value;
        if (input.value === '') msg = 'Please confirm your password.';
        else if (input.value !== pw) msg = 'Passwords do not match.';
      } else {
        msg = checks[input.name](input.value);
      }
      if (msg) { showError(input, msg); return false; }
      clearError(input);
      if (input.type !== 'password') input.classList.add('valid');
      return true;
    }

    var names = fields.slice();
    if (withConfirm) names.push('confirm_password');

    names.forEach(function (n) {
      var input = form.elements[n];
      if (!input) return;
      input.addEventListener('blur', function () { if (input.value !== '' || input.classList.contains('invalid')) checkField(input); });
      input.addEventListener('input', function () { if (input.classList.contains('invalid')) checkField(input); });
    });
    if (withConfirm) {
      // re-check the confirm box when the password changes
      form.elements['password'].addEventListener('input', function () {
        var c = form.elements['confirm_password'];
        if (c.value !== '') checkField(c);
      });
    }

    form.addEventListener('submit', function (ev) {
      var ok = true;
      var firstBad = null;
      names.forEach(function (n) {
        var input = form.elements[n];
        if (input && !checkField(input)) {
          ok = false;
          if (!firstBad) firstBad = input;
        }
      });
      if (!ok) {
        ev.preventDefault();
        if (firstBad) firstBad.focus();
      }
    });
  }

  var reg = document.querySelector('form[data-validate="register"]');
  if (reg) { setup(reg, ['name', 'email', 'phone', 'password'], true); }

  var login = document.querySelector('form[data-validate="login"]');
  if (login) {
    // login only needs a valid email and a non-empty password
    login.addEventListener('submit', function (ev) {
      var email = login.elements['email'];
      var pw = login.elements['password'];
      var ok = true;
      var msg = checks.email(email.value);
      if (msg) { showError(email, msg); ok = false; } else { clearError(email); }
      if (pw.value === '') { showError(pw, 'Password is required.'); ok = false; } else { clearError(pw); }
      if (!ok) ev.preventDefault();
    });
  }
})();
