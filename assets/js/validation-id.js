// Indonesian Validation Messages + Bootstrap styling helper
(function () {
  function labelOf(input) {
    var lbl = input.getAttribute('data-label') || input.getAttribute('placeholder');
    if (!lbl) lbl = (input.name || 'field').replace(/_/g, ' ');
    return lbl;
  }

  function messageFor(input) {
    var v = input.validity;
    var label = labelOf(input);
    if (v.valueMissing) return 'Harap isi ' + label + '.';
    if (v.typeMismatch) {
      if (input.type === 'email') return 'Harap masukkan alamat email yang valid.';
      if (input.type === 'url') return 'Harap masukkan URL yang valid.';
      return 'Nilai yang dimasukkan tidak sesuai tipe.';
    }
    if (v.patternMismatch) return 'Format ' + label + ' tidak sesuai pola yang ditentukan.';
    if (v.tooShort) return label + ' terlalu pendek.';
    if (v.tooLong) return label + ' terlalu panjang.';
    if (v.rangeUnderflow) return label + ' terlalu kecil.';
    if (v.rangeOverflow) return label + ' terlalu besar.';
    if (v.stepMismatch) return 'Nilai ' + label + ' tidak sesuai kelipatan yang diperbolehkan.';
    return 'Periksa kembali ' + label + '.';
  }

  function ensureFeedback(input) {
    var fb = input.nextElementSibling;
    if (!fb || !fb.classList || !fb.classList.contains('invalid-feedback')) {
      fb = document.createElement('div');
      fb.className = 'invalid-feedback';
      input.parentNode.insertBefore(fb, input.nextSibling);
    }
    return fb;
  }

  function applyInvalid(input) {
    var msg = messageFor(input);
    input.classList.add('is-invalid');
    input.setCustomValidity(msg);
    var fb = ensureFeedback(input);
    fb.textContent = msg;
  }

  function clearInvalid(input) {
    input.classList.remove('is-invalid');
    input.setCustomValidity('');
    var fb = input.nextElementSibling;
    if (fb && fb.classList && fb.classList.contains('invalid-feedback')) {
      fb.textContent = '';
    }
  }

  function initForm(form) {
    form.setAttribute('novalidate', '');
    form.classList.add('needs-validation');

    form.addEventListener('submit', function (e) {
      var valid = form.checkValidity();
      if (!valid) {
        e.preventDefault();
        e.stopPropagation();
        Array.prototype.forEach.call(form.querySelectorAll('input, select, textarea'), function (input) {
          if (!input.checkValidity()) applyInvalid(input); else clearInvalid(input);
        });
        form.classList.add('was-validated');
      }
    });

    Array.prototype.forEach.call(form.querySelectorAll('input, select, textarea'), function (input) {
      input.addEventListener('input', function () {
        if (input.checkValidity()) clearInvalid(input); else applyInvalid(input);
      });
      input.addEventListener('invalid', function (e) {
        e.preventDefault();
        applyInvalid(input);
      });
    });
  }

  function start() {
    document.querySelectorAll('form').forEach(initForm);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();