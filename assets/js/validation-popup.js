// Popup Validation (SweetAlert2) dengan pesan bahasa Indonesia
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
    if (v.tooShort) {
      var isRegister = /register\.php$/i.test(window.location.pathname);
      if (isRegister && input.name === 'password') return 'Password Minimal 8 Karakter';
      if (isRegister && input.name === 'konfirmasi_password') return 'Konfirmasi Password Minimal 8 Karakter';
      return label + ' terlalu pendek.';
    }
    if (v.tooLong) return label + ' terlalu panjang.';
    if (v.rangeUnderflow) return label + ' terlalu kecil.';
    if (v.rangeOverflow) return label + ' terlalu besar.';
    if (v.stepMismatch) return 'Nilai ' + label + ' tidak sesuai kelipatan yang diperbolehkan.';
    return 'Periksa kembali ' + label + '.';
  }

  function customChecks(form) {
    // Contoh: konfirmasi password pada register
    var pass = form.querySelector('input[name="password"]');
    var confirm = form.querySelector('input[name="konfirmasi_password"]');
    if (pass && confirm) {
      if (confirm.value.trim() === '') {
        return { input: confirm, message: 'Harap isi Konfirmasi Password.' };
      }
      if (confirm.value !== pass.value) {
        return { input: confirm, message: 'Konfirmasi password tidak sama.' };
      }
    }
    return null;
  }

  function start() {
    var forms = document.querySelectorAll('form');
    forms.forEach(function (form) {
      form.setAttribute('novalidate', '');
      form.addEventListener('submit', function (e) {
        var invalidInput = null;
        var invalidMsg = '';
        var inputs = form.querySelectorAll('input, select, textarea');
        for (var i = 0; i < inputs.length; i++) {
          var input = inputs[i];
          if (!input.checkValidity()) {
            invalidInput = input;
            invalidMsg = messageFor(input);
            break;
          }
        }

        // Jalankan custom check (mis. konfirmasi password)
        if (!invalidInput) {
          var custom = customChecks(form);
          if (custom) {
            invalidInput = custom.input;
            invalidMsg = custom.message;
          }
        }

        if (invalidInput) {
          e.preventDefault();
          e.stopPropagation();
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'warning',
              title: 'Perhatian!',
              text: invalidMsg,
              confirmButtonText: 'OK',
              customClass: { confirmButton: 'btn btn-success' },
              buttonsStyling: false
            }).then(function () {
              invalidInput.focus();
            });
          } else {
            alert(invalidMsg);
            invalidInput.focus();
          }
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
