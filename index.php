<?php
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Logistify - Kelola Stok dan Logistik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4@5/bootstrap-4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="assets/css/landing.css" rel="stylesheet">
</head>
<body>
  <div class="landing">
    <div class="video-bg">
      <video autoplay muted loop playsinline preload="auto">
        <source src="assets/media/landing.mp4" type="video/mp4">
      </video>
    </div>
    <div class="overlay-dark"></div>
    <section class="hero">
      <div class="brand">
        <div class="logo-dummy"><img src="assets/media/logistify.png" alt="Logo Logistify"></div>
        <div class="site-title">Logistify</div>
      </div>
      <h1 id="hero-title">Kelola Logistik &amp; Pencatatan Barang dengan Mudah</h1>
      <p>Catat stok secara real-time, buat laporan cepat, dan pantau pergerakan barang dalam satu platform yang ringan dan aman.</p>
      <div class="cta">
        <a class="btn btn-start" href="register.php"><i class="bi bi-rocket-takeoff"></i> Mulai (Daftar)</a>
        <a class="btn btn-login" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Masuk</a>
      </div>
    </section>
    <section class="features">
      <div class="cards">
        <div class="card">
          <div class="icon"><i class="bi bi-box-seam"></i></div>
          <h3>Pencatatan Stok Real-time</h3>
          <p>Lihat jumlah stok, tambah/edit barang, dan pantau perubahan secara instan.</p>
        </div>
        <div class="card">
          <div class="icon"><i class="bi bi-file-earmark-pdf"></i></div>
          <h3>Laporan Cepat</h3>
          <p>Unduh laporan PDF untuk kebutuhan audit, rekap, atau distribusi.</p>
        </div>
        <div class="card">
          <div class="icon"><i class="bi bi-shield-lock"></i></div>
          <h3>Mudah & Aman</h3>
          <p>Antarmuka sederhana, autentikasi aman, dan dukungan pengelolaan berbasis web.</p>
        </div>
      </div>
    </section>
    <footer class="footer">© 2025 Logistify</footer>
  </div>
  <script>
    // Efek mengetik looping dengan caret berkedip pada judul hero
    (function() {
      var el = document.getElementById('hero-title');
      if (!el) return;
      var full = (el.textContent || '').trim();
      var prefersReduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (prefersReduce || full.length === 0) { el.textContent = full; return; }

      // Struktur: span.typed (teks), span.typing-caret (kursor)
      el.innerHTML = '<span class="typed"></span><span class="typing-caret" aria-hidden="true"></span>';
      var target = el.querySelector('.typed');

      var i = 0;
      var typingSpeed = 100;      // lebih lambat: ms per karakter
      var eraseSpeed = 55;        // kecepatan hapus
      var pauseAfterType = 1500;  // jeda setelah selesai ketik
      var pauseAfterErase = 900;  // jeda setelah selesai hapus

      function type() {
        if (i <= full.length) {
          target.textContent = full.slice(0, i);
          i++;
          setTimeout(type, typingSpeed);
        } else {
          setTimeout(erase, pauseAfterType);
        }
      }

      function erase() {
        if (i >= 0) {
          target.textContent = full.slice(0, i);
          i--;
          setTimeout(erase, eraseSpeed);
        } else {
          setTimeout(type, pauseAfterErase);
        }
      }

      // Mulai setelah sedikit jeda agar hero siap
      setTimeout(type, 500);
    })();
  </script>
</body>