<?php
require_once 'config/koneksi.php';
require_once 'functions/auth.php';

// Akses hanya untuk user yang sudah login
if (!is_logged_in($koneksi)) {
  header('Location: login.php');
  exit;
}

$stmtChk = $koneksi->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'barang' AND column_name = 'user_id'");
$stmtChk->execute();
$c = 0; $rs = $stmtChk->get_result(); if ($rs && ($row = $rs->fetch_assoc())) { $c = (int)$row['c']; }
if ($c === 0) { $koneksi->query("ALTER TABLE `barang` ADD COLUMN `user_id` INT DEFAULT NULL"); $koneksi->query("ALTER TABLE `barang` ADD INDEX `idx_user` (`user_id`)"); }

// Ambil data barang milik user
$stmt = $koneksi->prepare("SELECT * FROM barang WHERE user_id = ? ORDER BY id DESC");
$uid = (int)($_SESSION['user_id'] ?? 0);
$stmt->bind_param('i', $uid);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <title>Logistify - Data Barang</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4@5/bootstrap-4.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="assets/css/dashboard.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="assets/media/fav-icon.png">
  <meta name="theme-color" content="#28a745">
</head>
<body>
  <div class="brand-bar">
    <div class="logo-dummy"><img src="assets/media/logistify.png" alt="Logo Logistify"></div>
    <div class="site-title">Logistify</div>
  </div>

  <div class="container mt-3">
    <div class="dashboard-container">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="m-0">Data Barang 📦</h2>
        <div class="d-flex gap-2">
          <a href="data_form.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Tambah Barang Baru</a>
          <a href="dashboard.php" class="btn btn-outline-light"><i class="bi bi-grid"></i> Dashboard</a>
        </div>
      </div>

      <div class="filter-bar">
        <input type="text" id="searchInput" class="form-control" placeholder="Cari nama, deskripsi, atau kode...">
      </div>

      <table class="table table-bordered table-striped table-dashboard">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama Barang</th>
            <th>Kode</th>
            <th>Kategori</th>
            <th>Satuan</th>
            <th>Stok</th>
            <th>Harga</th>
            <th>Lokasi</th>
            <th>Supplier</th>
            <th>Foto</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="data-table">
          <?php $no = 1; while($row = $result->fetch_assoc()): ?>
          <?php 
            $kode = isset($row['kode_barang']) && $row['kode_barang'] !== '' 
              ? $row['kode_barang'] 
              : 'BRG-' . str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT);
            $harga = isset($row['harga']) && $row['harga'] !== '' ? (float)$row['harga'] : 0;
          ?>
          <tr>
            <td><?= $no++; ?></td>
            <td data-search="true"><?= htmlspecialchars($row['nama_barang']); ?></td>
            <td data-search="true"><?= htmlspecialchars($kode); ?></td>
            <td data-search="true"><?= htmlspecialchars($row['kategori'] ?? ''); ?></td>
            <td><?= htmlspecialchars($row['satuan'] ?? ''); ?></td>
            <td data-col="stok"><?= (int)$row['stok']; ?></td>
            <td><?= 'Rp ' . number_format($harga, 0, ',', '.'); ?></td>
            <td><?= htmlspecialchars($row['lokasi'] ?? ''); ?></td>
            <td><?= htmlspecialchars($row['supplier'] ?? ''); ?></td>
            <td>
              <?php if (!empty($row['foto_barang'])): ?>
                <img src="uplouds/<?= htmlspecialchars($row['foto_barang']); ?>" class="thumb" alt="Foto Barang">
              <?php else: ?>
                Tidak ada foto
              <?php endif; ?>
            </td>
            <td>
              <a href="data_form.php?id=<?= $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
              <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $row['id']; ?>">Hapus</button>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="assets/js/custom.js"></script>
  <script src="assets/js/dashboard-ui.js"></script>
</body>
</html>
