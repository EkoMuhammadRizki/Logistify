<?php
require_once 'config/koneksi.php';
require_once 'functions/auth.php';

// Hanya boleh akses jika sudah login
if (!is_logged_in($koneksi)) {
  header('Location: login.php');
  exit;
}

$query = "SELECT * FROM barang ORDER BY id DESC";
$result = $koneksi->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Logistify - Dashboard & Data Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4@5/bootstrap-4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="assets/css/landing.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <link href="assets/css/loader.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/media/fav-icon.png">
    <link rel="shortcut icon" href="assets/media/fav-icon.png">
    <link rel="apple-touch-icon" href="assets/media/fav-icon.png">
    <link rel="manifest" href="manifest.webmanifest">
    <meta name="theme-color" content="#28a745">
</head>
<body>
    <div class="loader-wrapper">
        <img src="assets/media/fav-icon.png" alt="Loading..." class="loader-logo">
        <div class="loader-text">Logistify</div>
        <div class="progress-container">
          <div class="progress-percent">0%</div>
          <div class="progress-track">
            <div class="progress-bar"></div>
          </div>
        </div>
    </div>
    <div class="brand-bar">
      <div class="logo-dummy"><img src="assets/media/logistify.png" alt="Logo Logistify"></div>
      <div class="site-title">Logistify</div>
    </div>
    <div class="container mt-3">
      <div class="dashboard-container">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="m-0">Data Barang 📦</h2>
        <div class="d-flex gap-2">
          <a href="index.php" id="homeBtn" class="btn btn-outline-light"><i class="bi bi-house"></i> Halaman Utama</a>
          <a href="logout.php" id="logoutBtn" class="btn btn-danger">Logout</a>
        </div>
      </div>
        <div class="dashboard-actions mb-3">
          <a href="data_form.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Tambah Barang Baru</a>
          <a href="generate_laporan.php" class="btn btn-info" target="_blank"><i class="bi bi-filetype-pdf"></i> Download Laporan (PDF)</a>
        </div>

        <table class="table table-bordered table-striped table-dashboard">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>Stok</th>
                    <th>Foto</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="data-table">
                <?php $no = 1; while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                    <td><?= $row['stok']; ?></td>
                    <td>
                        <?php if ($row['foto_barang']): ?>
 <img src="uplouds/<?= $row['foto_barang']; ?>" class="thumb">
                        <?php else: ?>
                            Tidak ada foto
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="data_form.php?id=<?= $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $row['id']; ?>">Hapus (AJAX)</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/custom.js"></script>
    <script>
      // Loader setelah login menuju dashboard (0–100% dengan logo)
      (function() {
        const urlParams = new URLSearchParams(window.location.search);
        const loader = document.querySelector('.loader-wrapper');
        const percentEl = document.querySelector('.progress-percent');
        const barEl = document.querySelector('.progress-bar');
        function runProgress(onDone) {
          let p = 0;
          percentEl.textContent = '0%';
          barEl.style.width = '0%';
          const step = setInterval(() => {
            p = Math.min(100, p + Math.floor(Math.random() * 10) + 3); // 3–12%
            percentEl.textContent = p + '%';
            barEl.style.width = p + '%';
            if (p >= 100) { clearInterval(step); if (typeof onDone === 'function') onDone(); }
          }, 120);
        }
        if (urlParams.get('status') === 'loading') {
          loader.classList.remove('hidden');
          runProgress(() => {
            loader.classList.add('hidden');
            setTimeout(() => {
              Swal.fire({
                title: 'Selamat!',
                text: 'Kamu berhasil login.',
                icon: 'success',
                confirmButtonText: 'OK',
                customClass: { confirmButton: 'btn btn-success' },
                buttonsStyling: false
              });
              window.history.replaceState({}, document.title, window.location.pathname);
            }, 400);
          });
        } else {
          loader.classList.add('hidden');
        }
      })();
    </script>
    <?php if (isset($_GET['status'])): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const status = '<?= htmlspecialchars($_GET['status']); ?>';
        const showMsg = (title, text, icon) => Swal.fire({
          title, text, icon,
          confirmButtonText: 'OK',
          customClass: { confirmButton: 'btn btn-success' },
          buttonsStyling: false
        });
        if (status === 'tambah_sukses') {
          showMsg('Berhasil', 'Data berhasil ditambahkan.', 'success');
        } else if (status === 'edit_sukses') {
          showMsg('Berhasil', 'Data berhasil diperbarui.', 'success');
        } else if (status === 'login_sukses') {
          showMsg('Selamat!', 'Kamu berhasil login.', 'success');
        }
      });
    </script>
    <?php endif; ?>
    <script>
      // Konfirmasi tombol Halaman Utama & Logout menggunakan SweetAlert2
      document.addEventListener('DOMContentLoaded', function() {
        const homeBtn = document.getElementById('homeBtn');
        if (homeBtn) {
          homeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
              title: 'Anda yakin ingin kembali kehalaman utama?',
              icon: 'question',
              showCancelButton: true,
              confirmButtonText: 'Ya, kembali',
              cancelButtonText: 'Batal',
              customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-secondary' },
              buttonsStyling: false
            }).then(function(result) {
              if (result.isConfirmed) {
                window.location.href = homeBtn.getAttribute('href');
              }
            });
          });
        }

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
          logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
              title: 'Apakah Anda yakin ingin log out?',
              icon: 'question',
              showCancelButton: true,
              confirmButtonText: 'Ya, logout',
              cancelButtonText: 'Batal',
              customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-secondary' },
              buttonsStyling: false
            }).then(function(result) {
              if (result.isConfirmed) {
                window.location.href = logoutBtn.getAttribute('href');
              }
            });
          });
        }
      });
    </script>
  </body>
</html>