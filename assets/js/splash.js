// Logistify Splash Screen Controller
// Shows a quick modern splash on page entry and fades it out

(function () {
  function hideSplash() {
    var el = document.getElementById('splash');
    if (!el) return;
    el.classList.add('hide');
    // Remove from flow after fade
    setTimeout(function () {
      el.style.display = 'none';
    }, 450);
  }

  function start() {
    var el = document.getElementById('splash');
    if (!el) return;
    // Keep it visible briefly then hide
    setTimeout(hideSplash, 900);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();