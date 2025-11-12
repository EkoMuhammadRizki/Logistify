<?php
// dashboard.php — Halaman utama setelah login
// Fitur: ringkasan stok (total/menipis/habis), grafik aktivitas, tautan aksi,
// dan tombol CRUD (Tambah, Edit, Hapus) yang bertema hijau Logistify.
// Menggunakan guard login, mengambil data dari DB, dan menata UI via dashboard.css.
require_once 'config/koneksi.php';
require_once 'functions/auth.php';

// Hanya boleh akses jika sudah login
if (!is_logged_in($koneksi)) {
  header('Location: login.php');
  exit;
}

// CRUD: READ - ambil daftar barang untuk ditampilkan di tabel
$query = "SELECT * FROM barang ORDER BY id DESC";
$result = $koneksi->query($query);

// Ambil nama user yang login untuk sapaan di header
$current_username = 'Pengguna';
if (isset($_SESSION['user_id'])) {
  $uid = (int)$_SESSION['user_id'];
  if ($stmtUser = $koneksi->prepare("SELECT username FROM users WHERE id = ?")) {
    $stmtUser->bind_param("i", $uid);
    if ($stmtUser->execute()) {
      $resUser = $stmtUser->get_result();
      if ($rowU = $resUser->fetch_assoc()) {
        $current_username = $rowU['username'];
      }
    }
  }
}
// Ringkasan stok: total stok (jumlah keseluruhan), jumlah item menipis (<=5), dan item habis (=0)
$summary = [
  'total_qty' => 0,
  'menipis_count' => 0,
  'habis_count' => 0,
];
$sumQuery = "SELECT 
    COALESCE(SUM(stok),0) AS total_qty,
    SUM(CASE WHEN stok > 0 AND stok <= 5 THEN 1 ELSE 0 END) AS menipis_count,
    SUM(CASE WHEN stok = 0 THEN 1 ELSE 0 END) AS habis_count
  FROM barang";
if ($sumRes = $koneksi->query($sumQuery)) {
  if ($rowSum = $sumRes->fetch_assoc()) {
    $summary['total_qty'] = (int)$rowSum['total_qty'];
    $summary['menipis_count'] = (int)$rowSum['menipis_count'];
    $summary['habis_count'] = (int)$rowSum['habis_count'];
  }
}
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
    <link href="assets/css/loading-bar.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="assets/media/fav-icon.png">
    <link rel="shortcut icon" href="assets/media/fav-icon.png">
    <link rel="apple-touch-icon" href="assets/media/fav-icon.png">
    <link rel="manifest" href="manifest.webmanifest">
    <meta name="theme-color" content="#28a745">
</head>
<body>
    <div id="loadingBarOverlay" class="loading-overlay" style="display:none">
      <div class="loading-box">
        <div class="loading-head">
          <img src="assets/media/logistify.png" alt="Logo Logistify" class="loading-logo">
          <div class="loading-title">Memuat Dashboard</div>
        </div>
        <div class="loading-track"><div class="loading-bar"></div></div>
        <div class="loading-percent">0%</div>
      </div>
    </div>
    <!-- Dashboard utama: ringkasan stok + tiga grafik Chart.js (Aktivitas, Stok Minimum, Keluar Terbanyak) -->
    <div class="container mt-3">
      <div class="app-shell">
        <aside class="sidebar">
          <div class="d-flex align-items-center gap-2 mb-3">
            <div class="logo-dummy"><img src="assets/media/logistify.png" alt="Logo Logistify"></div>
            <div class="site-title">Logistify</div>
          </div>
          <!-- Sidebar navigation -->
          <nav class="nav flex-column">
              <a class="nav-link active" href="#" data-section="dashboardHome">Dashboard</a>
              <a class="nav-link" href="data_form.php" data-bs-toggle="tooltip" title="Daftarkan item baru ke master Data Barang."><i class="bi bi-plus-circle"></i> Tambah Barang Baru</a>
              <a class="nav-link" href="data_barang.php" data-bs-toggle="tooltip" title="Total Stok Barang">Data Barang</a>
              <a class="nav-link" href="barang_masuk.php" data-bs-toggle="tooltip" title="Catat penambahan stok untuk barang yang sudah ada.">Barang Masuk</a>
              <a class="nav-link" href="barang_keluar.php" data-bs-toggle="tooltip" title="Catat pengurangan stok untuk barang yang sudah ada.">Barang Keluar</a>
              <a class="nav-link" href="supplier.php" data-bs-toggle="tooltip" title="Kelola daftar pemasok barang.">Supplier</a>
              <a class="nav-link" href="lokasi.php" data-bs-toggle="tooltip" title="Kelola lokasi penyimpanan gudang.">Lokasi</a>
              <!-- Ubah tautan laporan agar memicu modal -->
              <a class="nav-link" href="#" id="linkLaporanPdf" data-bs-toggle="tooltip" title="Unduh Laporan PDF">Laporan PDF</a>
          </nav>
          <hr>
          <a href="logout.php" id="logoutBtn" class="btn btn-danger w-100">Logout</a>
          <a href="index.php" id="homeBtn" class="btn btn-outline-light w-100 mt-2"><i class="bi bi-house"></i> Halaman Utama</a>
        </aside>
        <main>
          <div class="dashboard-container">
            <div class="header-bar">
              <div>
                <h4 class="m-0">Halo, <?= htmlspecialchars($current_username); ?> 👋 — Selamat datang kembali!</h4>
                <small>Kelola stok dengan mudah menggunakan Logistify!</small>
              </div>
            </div>
      <!-- Dashboard Home Section -->
      <div id="section-dashboardHome" class="content-section active">
      <!-- Ringkasan Stok Barang -->
      <!-- Ringkasan Stok (auto-refresh via stats_summary.php + dashboard-ui.js) -->
      <div class="summary-wrap">
        <!-- Total kuantitas stok seluruh barang -->
        <div class="summary-card">
          <div class="summary-title">Total Stok</div>
          <div class="summary-value" id="summaryTotalQty"><?= number_format($summary['total_qty'], 0, ',', '.'); ?></div>
          <div class="summary-sub">Jumlah keseluruhan kuantitas barang</div>
        </div>
        <!-- Jumlah item dengan stok ≤ 5 (menipis) -->
        <div class="summary-card">
          <div class="summary-title">Stok Menipis</div>
          <div class="summary-value" id="summaryMenipisCount"><?= number_format($summary['menipis_count'], 0, ',', '.'); ?></div>
          <div class="summary-sub">Item dengan stok ≤ 5</div>
        </div>
        <!-- Jumlah item dengan stok = 0 (habis). Notifikasi SweetAlert muncul satu kali per sesi atau saat bertambah -->
        <div class="summary-card">
          <div class="summary-title">Stok Habis</div>
          <div class="summary-value" id="summaryHabisCount"><?= number_format($summary['habis_count'], 0, ',', '.'); ?></div>
          <div class="summary-sub">Item dengan stok = 0</div>
        </div>
      </div>

      <!-- Bar judul lama dan filter select dihapus sesuai permintaan -->
        <!-- Aksi PDF dipindah ke sidebar; tombol di area konten dihapus sesuai instruksi -->

      </div> <!-- /#section-dashboardHome -->

        <!-- Data Barang Section -->
        <div id="section-dataBarang" class="content-section">
          <!-- Konten Data Barang dihapus sesuai instruksi -->
        </div>
        <!-- Area grafik: kiri Aktivitas Stok; kanan Stok Minimum -->
        <div class="content-wrap mt-3">
          <div class="chart-card">
            <!-- Grafik Aktivitas Stok: sumber data stats_aktivitas.php; refresh otomatis saat transaksi -->
            <div class="chart-title">Grafik Aktivitas Stok</div>
            <div class="chart-canvas-wrap">
              <canvas id="stockChart"></canvas>
            </div>
          </div>
          <!-- Grafik Stok Minimum (tetap) -->
          <div class="chart-card chart-narrow">
            <!-- Grafik Stok Minimum (Top-5 stok terendah < 5, exclude soft-deleted jika ada kolom) → stats_min_stok.php -->
            <div class="chart-title">Grafik Stok Minimum</div>
            <div class="chart-canvas-wrap">
              <canvas id="minStockChart" style="height: 240px;"></canvas>
            </div>
          </div>
        </div>

        <!-- Kartu fitur dalam pengembangan: Riwayat, Barang Masuk/Keluar, Manajemen Supplier/Lokasi, Notifikasi Minimum -->
        <!-- Ganti blok feature-grid (6 card) menjadi satu grafik Barang Keluar Terbanyak -->
        <!-- Grafik Barang Keluar Terbanyak sepanjang tahun berjalan (Top-5) → stats_keluar_top.php -->
        <div class="mt-3">
          <div class="chart-card">
            <div class="chart-title">Grafik Barang Keluar Terbanyak</div>
            <div class="chart-canvas-wrap">
              <canvas id="topKeluarChart" style="height: 280px;"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Barang Masuk -->
    <div class="modal fade" id="modalMasuk" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-success">
          <div class="modal-header">
            <h5 class="modal-title">Input Barang Masuk</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="formMasuk" enctype="multipart/form-data">
              <div class="mb-2">
                <label class="form-label">Tanggal Barang Masuk</label>
                <input type="datetime-local" name="tanggal_masuk" class="form-control">
              </div>
              <div class="mb-2">
                <label class="form-label">Nama Barang</label>
                <input type="text" name="nama_barang" class="form-control" placeholder="Nama Barang" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Kode Barang</label>
                <input type="text" name="kode_barang" class="form-control" placeholder="Kode unik barang" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Jumlah Masuk</label>
                <input type="number" name="jumlah_masuk" class="form-control" placeholder="0" min="1" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Satuan (opsional)</label>
                <input type="text" name="satuan" class="form-control" placeholder="pcs, box, pack, dll">
              </div>
              <div class="mb-2">
                <label class="form-label">Supplier</label>
                <input type="text" name="supplier" class="form-control" placeholder="Nama supplier" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Lokasi Penyimpanan</label>
                <input type="text" name="lokasi" class="form-control" placeholder="Gudang/Rak" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Dokumen Pendukung (opsional)</label>
                <input type="file" name="dokumen" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
              </div>
              <div class="mb-2">
                <label class="form-label">Keterangan</label>
                <textarea name="keterangan" class="form-control" placeholder="Catatan tambahan"></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button class="btn btn-success" id="btnSubmitMasuk">Simpan</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal Barang Keluar -->
    <div class="modal fade" id="modalKeluar" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-success">
          <div class="modal-header">
            <h5 class="modal-title">Input Barang Keluar</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="formKeluar">
              <div class="mb-2">
                <label class="form-label">Nama Barang</label>
                <input type="text" class="form-control" placeholder="Nama Barang" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Jumlah</label>
                <input type="number" class="form-control" placeholder="0" min="1" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Keterangan</label>
                <input type="text" class="form-control" placeholder="Catatan (opsional)">
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button class="btn btn-success" id="btnSubmitKeluar">Simpan</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/js/dashboard-ui.js"></script>
    <script>
      // Toggle sections via sidebar nav
      document.addEventListener('DOMContentLoaded', function(){
        const links = document.querySelectorAll('.sidebar .nav-link[data-section]');
        const sections = {
          dashboardHome: document.getElementById('section-dashboardHome'),
          dataBarang: document.getElementById('section-dataBarang')
        };
        function show(sec){
          Object.keys(sections).forEach(function(key){
            const el = sections[key];
            if (!el) return;
            el.classList.toggle('active', key === sec);
          });
          links.forEach(function(link){
            link.classList.toggle('active', link.getAttribute('data-section') === sec);
          });
          // When switching to Data Barang, focus search and trigger filter
          if (sec === 'dataBarang') {
            const input = document.getElementById('searchInput');
            if (input) { input.focus(); }
            if (window.logiFilterTable) window.logiFilterTable();
          }
        }
        links.forEach(function(link){
          link.addEventListener('click', function(e){
            const sec = link.getAttribute('data-section');
            if (sec){ e.preventDefault(); show(sec); }
          });
        });
        // Default to dashboard
        show('dashboardHome');
      });
    </script>
    <script>
      // Loader setelah login menuju dashboard (0–100% dengan logo)
      (function() {
        const urlParams = new URLSearchParams(window.location.search);
        const loader = document.querySelector('.loader-wrapper');
        const percentEl = document.querySelector('.progress-percent');
        const barEl = document.querySelector('.progress-bar');
        if (!loader || !percentEl || !barEl) return; // loader tidak digunakan lagi
        function runProgress(onDone) {
          let p = 0;
          percentEl.textContent = '0%';
          barEl.style.width = '0%';
          const step = setInterval(() => {
            p = Math.min(100, p + Math.floor(Math.random() * 10) + 3);
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
    <script>
      // Grafik aktivitas stok (placeholder) dan handler modal masuk/keluar
      document.addEventListener('DOMContentLoaded', function(){
        var ctx = document.getElementById('stockChart');
        if (ctx && typeof Chart !== 'undefined') {
          // Data dinamis Jan–Des
          if (typeof window.refreshStockChart === 'function') { window.refreshStockChart(); }
          // Stok minimum top-5
          if (typeof window.refreshMinStockChart === 'function') { window.refreshMinStockChart(); }
          // Barang keluar terbanyak (top-5, tahun berjalan)
          if (typeof window.refreshTopKeluarChart === 'function') { window.refreshTopKeluarChart(); }
        }
        var btnNotif = document.getElementById('btnSimulateNotif');
        if (btnNotif) {
          btnNotif.addEventListener('click', function(){
            Swal.fire({
              title:'Peringatan Stok!',
              text:'Beberapa barang mendekati batas minimum. Periksa dan tambah stok.',
              icon:'warning',
              confirmButtonText:'OK',
              customClass:{ confirmButton:'btn btn-success' },
              buttonsStyling:false
            });
          });
        }

        var btnMasuk = document.getElementById('btnSubmitMasuk');
        if (btnMasuk) btnMasuk.addEventListener('click', function(){
          Swal.fire({ title:'Barang Masuk', text:'Fitur ini sedang dalam tahap penyempurnaan dan akan diaktifkan pada versi berikutnya.', icon:'info', confirmButtonText:'OK', customClass:{confirmButton:'btn btn-success'}, buttonsStyling:false });
        });
        var btnKeluar = document.getElementById('btnSubmitKeluar');
        if (btnKeluar) btnKeluar.addEventListener('click', function(){
          Swal.fire({ title:'Barang Keluar', text:'Fitur ini sedang dalam tahap penyempurnaan dan akan diaktifkan pada versi berikutnya.', icon:'info', confirmButtonText:'OK', customClass:{confirmButton:'btn btn-success'}, buttonsStyling:false });
        });
      });
    </script>
    <script src="assets/js/loading-bar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        var triggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        triggerList.forEach(function(el){ new bootstrap.Tooltip(el); });
      });
    </script>
</body>
</html>

<!-- Modal Pilihan Laporan PDF -->
<div class="modal fade" id="modalLaporanPdf" tabindex="-1" aria-labelledby="modalLaporanPdfLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalLaporanPdfLabel">Unduh Laporan PDF</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label text-success">Pilih jenis laporan:</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="reportType" id="reportStok" value="stok" checked>
              <label class="form-check-label text-success" for="reportStok">Laporan Data Barang</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="reportType" id="reportMasuk" value="masuk">
              <label class="form-check-label text-success" for="reportMasuk">Laporan Barang Masuk</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="reportType" id="reportKeluar" value="keluar">
              <label class="form-check-label text-success" for="reportKeluar">Laporan Barang Keluar</label>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label text-success" for="laporanYear">Tahun:</label>
          <select class="form-select" id="laporanYear">
            <?php
              $currentYear = (int)date('Y');
              for ($y = $currentYear; $y >= $currentYear - 10; $y--) {
                echo '<option value="'.$y.'"'.($y===$currentYear?' selected':'').'>'.$y.'</option>';
              }
            ?>
          </select>
          <small class="text-muted d-block mt-1">Untuk “Data Barang”, tahun tidak digunakan (menampilkan stok saat ini).</small>
        </div>

        <div class="p-3 border rounded">
          <div class="fw-bold text-success" id="previewTitle">LOGISTIFY - LAPORAN DATA BARANG</div>
          <div class="text-success" id="previewSub">Tanggal Cetak: <?= date('d'); ?> <?= date('F'); ?> <?= date('Y'); ?></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-success" id="btnDownloadLaporan"><i class="bi bi-file-earmark-pdf"></i> Unduh</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Inisialisasi modal & aksi unduh laporan
  document.addEventListener('DOMContentLoaded', function(){
    var link = document.getElementById('linkLaporanPdf');
    var modalEl = document.getElementById('modalLaporanPdf');
    if (!link || !modalEl) return;

    var modal = new bootstrap.Modal(modalEl);
    link.addEventListener('click', function(e){
      e.preventDefault();
      modal.show();
    });

    var btnDownload = document.getElementById('btnDownloadLaporan');
    var yearSelect = document.getElementById('laporanYear');
    var radios = document.querySelectorAll('input[name="reportType"]');
    var previewTitle = document.getElementById('previewTitle');
    var previewSub = document.getElementById('previewSub');

    function updatePreview(){
      var type = document.querySelector('input[name="reportType"]:checked').value;
      var year = yearSelect.value;
      var months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
      var now = new Date();
      if (type === 'stok'){
        previewTitle.textContent = 'LOGISTIFY - LAPORAN DATA BARANG';
        previewSub.textContent = 'Tanggal Cetak: ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
        yearSelect.disabled = true;
      } else if (type === 'masuk'){
        previewTitle.textContent = 'LOGISTIFY - LAPORAN BARANG MASUK';
        previewSub.textContent = 'Periode: Januari - Desember ' + year;
        yearSelect.disabled = false;
      } else {
        previewTitle.textContent = 'LOGISTIFY - LAPORAN BARANG KELUAR';
        previewSub.textContent = 'Periode: Januari - Desember ' + year;
        yearSelect.disabled = false;
      }
    }

    radios.forEach(function(r){ r.addEventListener('change', updatePreview); });
    yearSelect.addEventListener('change', updatePreview);
    updatePreview();

    btnDownload.addEventListener('click', function(){
      var type = document.querySelector('input[name="reportType"]:checked').value;
      var year = yearSelect.value;
      var url = 'generate_laporan.php?report=' + encodeURIComponent(type) + '&year=' + encodeURIComponent(year);
      window.open(url, '_blank');
      modal.hide();
    });
  });
</script>