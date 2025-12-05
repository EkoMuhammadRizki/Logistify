<?php
// barang_masuk.php — Daftar Transaksi Barang Masuk
// Tampilan kolom sesuai permintaan: Tanggal Masuk, Nama Barang, Kode Barang,
// Jumlah Masuk, Satuan (opsional), Supplier, Lokasi Penyimpanan, Dokumen Pendukung (opsional),
// Keterangan, dan Aksi (Edit/Hapus).
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);

$stmtChk = $koneksi->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'barang_masuk' AND column_name = 'user_id'");
$stmtChk->execute();
$c = 0; $rs = $stmtChk->get_result(); if ($rs && ($row = $rs->fetch_assoc())) { $c = (int)$row['c']; }
if ($c === 0) { $koneksi->query("ALTER TABLE `barang_masuk` ADD COLUMN `user_id` INT DEFAULT NULL"); $koneksi->query("ALTER TABLE `barang_masuk` ADD INDEX `idx_user` (`user_id`)"); }

$stmtChk2 = $koneksi->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'barang' AND column_name = 'user_id'");
$stmtChk2->execute();
$c2 = 0; $rs2 = $stmtChk2->get_result(); if ($rs2 && ($row2 = $rs2->fetch_assoc())) { $c2 = (int)$row2['c']; }
if ($c2 === 0) { $koneksi->query("ALTER TABLE `barang` ADD COLUMN `user_id` INT DEFAULT NULL"); $koneksi->query("ALTER TABLE `barang` ADD INDEX `idx_user` (`user_id`)"); }

$stmtChk3 = $koneksi->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'suppliers' AND column_name = 'user_id'");
$stmtChk3->execute();
$c3 = 0; $rs3 = $stmtChk3->get_result(); if ($rs3 && ($row3 = $rs3->fetch_assoc())) { $c3 = (int)$row3['c']; }
if ($c3 === 0) { $koneksi->query("ALTER TABLE `suppliers` ADD COLUMN `user_id` INT DEFAULT NULL"); $koneksi->query("ALTER TABLE `suppliers` ADD INDEX `idx_user` (`user_id`)"); }

$stmtChk4 = $koneksi->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'locations' AND column_name = 'user_id'");
$stmtChk4->execute();
$c4 = 0; $rs4 = $stmtChk4->get_result(); if ($rs4 && ($row4 = $rs4->fetch_assoc())) { $c4 = (int)$row4['c']; }
if ($c4 === 0) { $koneksi->query("ALTER TABLE `locations` ADD COLUMN `user_id` INT DEFAULT NULL"); $koneksi->query("ALTER TABLE `locations` ADD INDEX `idx_user` (`user_id`)"); }

// Parameter filter sederhana
// Filter minimal untuk daftar transaksi masuk
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 20; // Default 20, batas 200

// Coba ambil dari tabel transaksi `barang_masuk` jika tersedia
// Skema yang diharapkan: id, tanggal_masuk(DATETIME/TIMESTAMP), nama_barang, kode_barang,
// jumlah_masuk(INT), satuan(VARCHAR, opsional), supplier(VARCHAR), lokasi(VARCHAR), dokumen(VARCHAR, opsional), keterangan(TEXT)
$table_missing = false; $result = null;

// Cek keberadaan tabel terlebih dulu untuk mencegah fatal error pada mysqli->prepare
$exists = false;
$check = $koneksi->query("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'barang_masuk'");
if ($check && $row = $check->fetch_assoc()) { $exists = ((int)$row['c'] > 0); }

$params = []; $types = '';
if ($exists) {
  $sql = "SELECT id, tanggal_masuk, nama_barang, kode_barang, jumlah_masuk, satuan, supplier, lokasi, dokumen, foto_barang, keterangan FROM barang_masuk WHERE 1=1 AND user_id = ?";
  if ($q !== '') {
    $sql .= " AND (nama_barang LIKE ? OR kode_barang LIKE ? OR supplier LIKE ? OR lokasi LIKE ? OR keterangan LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like);
    $types .= 'sssss';
  }
  $sql .= " ORDER BY tanggal_masuk DESC, id DESC LIMIT ?";
  $params[] = $limit; $types .= 'i';

  try {
    $stmt = $koneksi->prepare($sql);
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $types = 'i' . $types;
    $params = array_merge([$uid], $params);
    if ($types !== '') { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $result = $stmt->get_result();
  } catch (Throwable $e) {
    // Jika terjadi error (misal table belum ada), tandai missing agar UI menawarkan migrasi
    $table_missing = true;
    $result = null;
  }
} else {
  $table_missing = true;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Logistify - Barang Masuk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4@5/bootstrap-4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="assets/css/landing.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/media/fav-icon.png">
    <link rel="shortcut icon" href="assets/media/fav-icon.png">
    <link rel="apple-touch-icon" href="assets/media/fav-icon.png">
    <link rel="manifest" href="manifest.webmanifest">
    <meta name="theme-color" content="#28a745">
    <style>
      /* Badge "Baru" untuk item paling atas ketika sort DESC */
      .badge-new { background: #28a745; color: #fff; border-radius: 10px; padding: 2px 8px; font-size: .75rem; }
      .badge-reviewed { background: #6c757d; color: #fff; border-radius: 10px; padding: 2px 8px; font-size: .75rem; }
      .actions { display:flex; gap: 6px; }
    </style>
  </head>
<body>
  <div class="brand-bar">
    <div class="logo-dummy"><img src="assets/media/logistify.png" alt="Logo Logistify" /></div>
    <div class="site-title">Logistify</div>
  </div>
  <div class="container mt-3">
    <div class="dashboard-container">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="m-0">Barang Masuk</h2>
        <div class="d-flex align-items-center gap-2">
          <a href="dashboard.php" class="btn btn-outline-light"><i class="bi bi-grid"></i> Dashboard</a>
          <a href="generate_laporan.php" target="_blank" class="btn btn-success"><i class="bi bi-file-earmark-pdf"></i> Laporan PDF</a>
        </div>
      </div>

      <!-- Form input Barang Masuk (AJAX) untuk item yang sudah ada -->
      <?php $uid = (int)($_SESSION['user_id'] ?? 0); $stmtIt = $koneksi->prepare("SELECT id, nama_barang FROM barang WHERE user_id = ? ORDER BY nama_barang ASC"); $stmtIt->bind_param('i',$uid); $stmtIt->execute(); $items = $stmtIt->get_result(); ?>
      <form id="formMasuk" class="border rounded p-3 mb-3" enctype="multipart/form-data">
        <h5 class="mb-3">Tambah Transaksi Barang Masuk</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Pilih Barang</label>
            <select name="barang_id" class="form-select" required>
              <option value="">-- Pilih --</option>
              <?php if ($items) while($it = $items->fetch_assoc()): ?>
                <?php $kode = 'BRG-' . str_pad((string)$it['id'], 4, '0', STR_PAD_LEFT); $label = '['.htmlspecialchars($kode).'] ' . htmlspecialchars($it['nama_barang']); ?>
                <option value="<?= (int)$it['id']; ?>"><?= $label; ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Jumlah Masuk</label>
            <input type="number" name="jumlah_masuk" class="form-control" min="1" step="1" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tanggal</label>
            <input type="datetime-local" name="tanggal_masuk" class="form-control">
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label class="form-label">Supplier</label>
            <?php 
              $supReady = false; $suppliers = [];
              if ($rs = $koneksi->query("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'suppliers'")) {
                $row = $rs->fetch_assoc(); $supReady = ((int)$row['c'] > 0);
                if ($supReady) {
                  $uid = (int)($_SESSION['user_id'] ?? 0);
                  $stmtSup = $koneksi->prepare("SELECT id, nama_supplier FROM suppliers WHERE user_id = ? ORDER BY nama_supplier ASC");
                  $stmtSup->bind_param('i',$uid);
                  if ($stmtSup->execute()) { $resSup = $stmtSup->get_result(); while($r = $resSup->fetch_assoc()){ $suppliers[] = $r; } }
                }
              }
            ?>
            <?php if ($supReady && count($suppliers) > 0): ?>
              <select name="supplier" class="form-select" required>
                <option value="">-- Pilih Supplier --</option>
                <?php foreach($suppliers as $sup): ?>
                  <option value="<?= htmlspecialchars($sup['nama_supplier']); ?>"><?= htmlspecialchars($sup['nama_supplier']); ?></option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Kelola daftar di <a href="supplier.php" class="text-success">Supplier</a>.</div>
            <?php else: ?>
              <input type="text" name="supplier" class="form-control" placeholder="Nama supplier" required>
              <div class="form-text">Buat daftar di <a href="supplier.php" class="text-success">Supplier</a>.</div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Dokumen (opsional)</label>
            <input type="file" name="dokumen" class="form-control">
          </div>
        </div>
        <div class="mt-2">
          <label class="form-label">Keterangan (opsional)</label>
          <textarea name="keterangan" class="form-control" rows="2"></textarea>
        </div>
        <div class="mt-3">
          <button type="button" id="btnSubmitMasuk" class="btn btn-success"><i class="bi bi-download"></i> Simpan Transaksi Masuk</button>
        </div>
      </form>

      <!-- Filter bar: pencarian dan batas tampil -->
      <form class="filter-bar" method="get">
        <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($q); ?>" placeholder="Cari nama/kode/supplier/lokasi/keterangan...">
        <select name="limit" class="form-select">
          <?php foreach ([10,20,50,100,200] as $opt): ?>
            <option value="<?= $opt; ?>" <?= $limit===$opt?'selected':''; ?>>Tampilkan <?= $opt; ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-success" type="submit"><i class="bi bi-filter"></i> Terapkan</button>
      </form>

      <!-- Tabel Barang Masuk sesuai kolom yang diminta -->
      <div class="table-responsive mt-2">
        <table class="table table-bordered table-striped table-dashboard">
          <thead>
            <tr>
              <th>Tanggal Barang Masuk</th>
              <th>Nama Barang</th>
              <th>Kode Barang</th>
              <th>Jumlah Masuk</th>
              <th>Satuan</th>
              <th>Supplier</th>
              <th>Lokasi Penyimpanan</th>
              <th>Dokumen Pendukung</th>
              <th>Foto</th>
              <th>Keterangan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($table_missing): ?>
              <tr>
                <td colspan="10" class="text-center text-light">
                  Tabel transaksi <code>barang_masuk</code> belum tersedia.<br>
                  <button class="btn btn-success btn-sm mt-2" id="btnMigrateMasuk"><i class="bi bi-hammer"></i> Buat Tabel Otomatis</button>
                </td>
              </tr>
            <?php elseif ($result && $result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <?php $doc = isset($row['dokumen']) ? trim($row['dokumen']) : ''; $docRel = $doc !== '' ? 'uplouds/' . $doc : ''; $docExists = ($docRel !== '' && file_exists(__DIR__ . '/' . $docRel)); $foto = isset($row['foto_barang']) ? trim($row['foto_barang']) : ''; $fotoRel = $foto !== '' ? 'uplouds/' . $foto : ''; $fotoExists = ($fotoRel !== '' && file_exists(__DIR__ . '/' . $fotoRel)); ?>
                <tr data-id="<?= (int)$row['id']; ?>">
                  <td><?= htmlspecialchars($row['tanggal_masuk']); ?></td>
                  <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                  <td><?= htmlspecialchars($row['kode_barang']); ?></td>
                  <td><?= (int)$row['jumlah_masuk']; ?></td>
                  <td><?= htmlspecialchars($row['satuan'] ?? ''); ?></td>
                  <td><?= htmlspecialchars($row['supplier']); ?></td>
                  <td><?= htmlspecialchars($row['lokasi']); ?></td>
                  <td>
                    <?php if ($docExists): ?>
                      <a href="<?= htmlspecialchars($docRel); ?>" class="btn btn-outline-light btn-sm" target="_blank"><i class="bi bi-paperclip"></i> Lihat</a>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($fotoExists): ?>
                      <img src="<?= htmlspecialchars($fotoRel); ?>" class="thumb" alt="Foto">
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($row['keterangan']); ?></td>
                  <td class="actions">
                    <button class="btn btn-warning btn-sm edit-masuk"><i class="bi bi-pencil"></i> Edit</button>
                    <button class="btn btn-danger btn-sm delete-masuk"><i class="bi bi-trash"></i> Hapus</button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="10" class="text-center text-light">Belum ada transaksi barang masuk.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="mt-2">
        <small class="text-light">Catatan: Dokumen pendukung bersifat opsional. Gunakan tombol Edit/Hapus untuk mengelola transaksi saat backend siap.</small>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="assets/js/custom.js"></script>
  <script>
    // Placeholder aksi Edit/Hapus untuk transaksi Masuk (akan dihubungkan ke backend khusus)
    document.addEventListener('DOMContentLoaded', function(){
      // Jalankan migrasi jika tombol ditekan
      var btnMig = document.getElementById('btnMigrateMasuk');
      if (btnMig) {
        btnMig.addEventListener('click', function(){
          $.ajax({ url:'proses_masuk.php?action=migrate', type:'POST', dataType:'json', success:function(resp){
            if (resp.status === 'success') {
              Swal.fire({ title:'Selesai', text: resp.message, icon:'success', confirmButtonText:'OK', customClass:{ confirmButton:'btn btn-success' }, buttonsStyling:false })
              .then(function(){ window.location.reload(); });
            } else {
              Swal.fire({ title:'Gagal', text: resp.message, icon:'error', confirmButtonText:'OK', customClass:{ confirmButton:'btn btn-danger' }, buttonsStyling:false });
            }
          }, error:function(){ Swal.fire({ title:'Error', text:'Terjadi kesalahan migrasi.', icon:'error', confirmButtonText:'OK', customClass:{ confirmButton:'btn btn-danger' }, buttonsStyling:false }); }});
        });
      }
      document.querySelectorAll('.edit-masuk').forEach(function(btn){
        btn.addEventListener('click', function(){
          Swal.fire({ title:'Edit Barang Masuk', text:'Fitur edit akan diaktifkan setelah implementasi backend transaksi.', icon:'info' });
        });
      });
    });
  </script>
</body>
</html>
