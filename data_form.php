<?php
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);

$is_edit = false;
$data = ['id' => '', 'nama_barang' => '', 'kode_barang' => '', 'kategori' => '', 'satuan' => '', 'supplier' => '', 'lokasi' => '', 'deskripsi' => '', 'stok' => '', 'harga' => '', 'foto_barang' => '', 'dokumen' => ''];
$action = 'tambah';
$title = 'Tambah Barang Baru';

// Cek jika ada ID di **GET** request (Mode Edit)
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $is_edit = true;
    $action = 'edit';
    $title = 'Edit Barang';

    $stmt = $koneksi->prepare("SELECT * FROM barang WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $data = $result->fetch_assoc();
    } else {
        // Data tidak ditemukan, redirect ke dashboard
        header('Location: dashboard.php');
        exit;
    }
}
// Muat referensi Supplier & Lokasi jika tabel tersedia
$supReady = false; $suppliers = [];
if ($rs = $koneksi->query("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'suppliers'")) {
  $row = $rs->fetch_assoc(); $supReady = ((int)$row['c'] > 0);
  if ($supReady) {
    if ($ls = $koneksi->query("SELECT id, nama_supplier FROM suppliers ORDER BY nama_supplier ASC")) {
      while($r = $ls->fetch_assoc()){ $suppliers[] = $r; }
    }
  }
}
$locReady = false; $locations = [];
if ($rs2 = $koneksi->query("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'locations'")) {
  $row2 = $rs2->fetch_assoc(); $locReady = ((int)$row2['c'] > 0);
  if ($locReady) {
    if ($ll = $koneksi->query("SELECT id, nama_lokasi, kode_lokasi FROM locations ORDER BY nama_lokasi ASC")) {
      while($r = $ll->fetch_assoc()){ $locations[] = $r; }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title><?= $title; ?></title>
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
</head>
<body>
    <div class="brand-bar">
      <div class="logo-dummy"><img src="assets/media/logistify.png" alt="Logo Logistify" /></div>
      <div class="site-title">Logistify</div>
    </div>
    <div class="container mt-3">
      <div class="dashboard-container">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="m-0"><?= $title; ?></h2>
          <a href="dashboard.php" class="btn btn-outline-light"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>

        <!-- Form CRUD (Create/Update) barang via AJAX tanpa reload.
             enctype="multipart/form-data" diperlukan untuk upload file foto_barang/dokumen -->
        <form id="barangForm" action="proses_data.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="action" value="<?= $action; ?>">
            <input type="hidden" name="id" value="<?= $data['id']; ?>">
            <input type="hidden" name="foto_lama" value="<?= $data['foto_barang']; ?>">

            <div class="mb-3">
                <label class="form-label">Nama Barang</label>
                <input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($data['nama_barang']); ?>" required placeholder="Nama Barang">
            </div>
            <div class="mb-3">
                <label class="form-label">Kode Barang</label>
                <input type="text" name="kode_barang" class="form-control" value="<?= htmlspecialchars($data['kode_barang']); ?>" placeholder="Misal BRG-001">
                <div class="form-text">Kosongkan bila ingin digenerate otomatis.</div>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Kategori</label>
                <input type="text" name="kategori" class="form-control" value="<?= htmlspecialchars($data['kategori']); ?>" placeholder="Kategori">
              </div>
              <div class="col-md-4">
                <label class="form-label">Satuan</label>
                <input type="text" name="satuan" class="form-control" value="<?= htmlspecialchars($data['satuan']); ?>" placeholder="Misal pcs, box">
              </div>
              <div class="col-md-4">
                <label class="form-label">Supplier</label>
                <?php if ($supReady && count($suppliers) > 0): ?>
                  <select name="supplier" class="form-select">
                    <option value="">-- Pilih Supplier --</option>
                    <?php foreach($suppliers as $sup): $sel = ($data['supplier'] === $sup['nama_supplier']) ? 'selected' : ''; ?>
                      <option value="<?= htmlspecialchars($sup['nama_supplier']); ?>" <?= $sel; ?>><?= htmlspecialchars($sup['nama_supplier']); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <div class="form-text">Kelola daftar di <a href="supplier.php" class="text-success">Supplier</a>.</div>
                <?php else: ?>
                  <input type="text" name="supplier" class="form-control" value="<?= htmlspecialchars($data['supplier']); ?>" placeholder="Nama supplier">
                  <div class="form-text">Buat daftar di <a href="supplier.php" class="text-success">Supplier</a>.</div>
                <?php endif; ?>
              </div>
            </div>
            <div class="mb-3 mt-3">
              <label class="form-label">Lokasi Penyimpanan</label>
              <?php if ($locReady && count($locations) > 0): ?>
                <select name="lokasi" class="form-select">
                  <option value="">-- Pilih Lokasi --</option>
                  <?php foreach($locations as $loc): $sel = ($data['lokasi'] === $loc['kode_lokasi']) ? 'selected' : ''; ?>
                    <option value="<?= htmlspecialchars($loc['kode_lokasi']); ?>" <?= $sel; ?>>[<?= htmlspecialchars($loc['kode_lokasi']); ?>] <?= htmlspecialchars($loc['nama_lokasi']); ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text">Kelola daftar di <a href="lokasi.php" class="text-success">Lokasi</a>.</div>
              <?php else: ?>
                <input type="text" name="lokasi" class="form-control" value="<?= htmlspecialchars($data['lokasi']); ?>" placeholder="Gudang/Rak">
                <div class="form-text">Buat daftar di <a href="lokasi.php" class="text-success">Lokasi</a>.</div>
              <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="deskripsi" class="form-control"><?= htmlspecialchars($data['deskripsi']); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Stok</label>
                <input type="number" name="stok" class="form-control" value="<?= $data['stok']; ?>" required placeholder="Stok" min="0" step="1">
            </div>
            <div class="mb-3">
                <label class="form-label">Harga</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="text" id="hargaRupiah" class="form-control" inputmode="numeric" placeholder="Harga" value="<?= $data['harga'] !== '' ? number_format((float)$data['harga'], 0, ',', '.') : '' ?>" required>
                </div>
                <!-- Hidden field harga menyimpan nilai numerik (tanpa format) untuk diproses di server -->
                <input type="hidden" name="harga" id="hargaValue" value="<?= htmlspecialchars($data['harga']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Foto Barang (Maks 10MB, JPG/PNG)</label>
                <input type="file" name="foto_barang" class="form-control">
                <?php if ($is_edit && $data['foto_barang']): ?>
                <!-- Pratayang foto saat ini untuk mode edit -->
                <p class="mt-2">Foto saat ini: <img src="uplouds/<?= $data['foto_barang']; ?>" style="width: 100px; object-fit: cover;"></p>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Dokumen Pendukung (opsional, PDF/JPG/PNG)</label>
                <input type="file" name="dokumen" class="form-control">
            </div>
            
            <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Data</button>
        </form>
      </div>
    </div>
    <script>
      // Submit via AJAX tanpa reload + konfirmasi menggunakan SweetAlert2
      document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('barangForm');
        form.addEventListener('submit', async function(e) {
          e.preventDefault();
          const confirm = await Swal.fire({
            title: 'Simpan data?',
            text: 'Pastikan data sudah benar sebelum disimpan.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, simpan',
            cancelButtonText: 'Cek lagi',
            customClass: { confirmButton: 'btn btn-success', cancelButton: 'btn btn-secondary' },
            buttonsStyling: false
          });
          if (!confirm.isConfirmed) return;
          const fd = new FormData(form);
          try {
            const res = await fetch('proses_data.php', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.status === 'success') {
              await Swal.fire({ title: 'Berhasil', text: json.message, icon: 'success', confirmButtonText: 'OK', customClass:{confirmButton:'btn btn-success'}, buttonsStyling:false });
              // Tetap di halaman tanpa reload; item kini tersedia untuk dipilih di menu Masuk/Keluar
            } else {
              throw new Error(json.message || 'Gagal menyimpan');
            }
          } catch(err) {
            Swal.fire({ title:'Error', text: err.message, icon:'error', confirmButtonText:'OK' });
          }
        });
      });
    </script>
    <script src="assets/js/validation-popup.js"></script>
</body>
<script>
// Format input harga sebagai Rupiah (ID) dan sinkronkan nilai numerik ke hidden field
(function() {
  var display = document.getElementById('hargaRupiah');
  var hidden = document.getElementById('hargaValue');
  if (!display || !hidden) return;

  function formatRupiah(val) {
    val = (val || '').toString().replace(/[^0-9,]/g, '');
    var parts = val.split(',');
    var whole = parts[0];
    // tambahkan titik setiap 3 digit
    var formatted = whole.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    if (parts.length > 1) {
      formatted += ',' + parts[1].slice(0, 2); // batasi 2 desimal
    }
    return formatted;
  }

  function toNumeric(val) {
    if (!val) return '';
    // ubah 1.234.567,89 => 1234567.89
    var clean = val.replace(/\./g, '').replace(',', '.');
    return clean;
  }

  function sync() {
    display.value = formatRupiah(display.value);
    hidden.value = toNumeric(display.value);
  }

  display.addEventListener('input', sync);
  // inisialisasi saat load
  sync();
})();
</script>
</html>