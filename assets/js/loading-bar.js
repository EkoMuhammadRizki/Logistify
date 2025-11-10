// Loading bar shown only when arriving with status=login_sukses
(function () {
  function qs(name) {
    return new URLSearchParams(window.location.search).get(name);
  }

  function showLoading() {
    var overlay = document.getElementById('loadingBarOverlay');
    if (!overlay) return;
    overlay.style.display = 'flex';

    var bar = overlay.querySelector('.loading-bar');
    var percentEl = overlay.querySelector('.loading-percent');
    var percent = 0;
    var duration = 900; // ms
    var step = 18; // ms per tick
    var inc = 100 / (duration / step);
    var timer = setInterval(function () {
      percent = Math.min(100, percent + inc);
      bar.style.width = percent.toFixed(0) + '%';
      if (percentEl) percentEl.textContent = percent.toFixed(0) + '%';
      if (percent >= 100) {
        clearInterval(timer);
        // Small hold then fade
        setTimeout(function () {
          overlay.classList.add('hide');
          setTimeout(function () { overlay.style.display = 'none'; }, 420);
        }, 120);
      }
    }, step);

    // Clean the URL so status param disappears after showing
    try {
      var url = new URL(window.location.href);
      url.searchParams.delete('status');
      window.history.replaceState({}, document.title, url.toString());
    } catch (e) {}
  }

  function start() {
    if (qs('status') === 'login_sukses') {
      showLoading();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();