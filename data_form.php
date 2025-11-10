<?php
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);

$is_edit = false;
$data = ['id' => '', 'nama_barang' => '', 'deskripsi' => '', 'stok' => '', 'harga' => '', 'foto_barang' => ''];
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

        <!-- Form CRUD (Create/Update) barang.
             enctype="multipart/form-data" diperlukan untuk upload file foto_barang -->
        <form id="barangForm" action="proses_data.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="action" value="<?= $action; ?>">
            <input type="hidden" name="id" value="<?= $data['id']; ?>">
            <input type="hidden" name="foto_lama" value="<?= $data['foto_barang']; ?>">

            <div class="mb-3">
                <label class="form-label">Nama Barang</label>
                <input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($data['nama_barang']); ?>" required placeholder="Nama Barang">
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
            
            <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Data</button>
        </form>
      </div>
    </div>
    <script>
      // Konfirmasi submit menggunakan SweetAlert2
      document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('barangForm');
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          Swal.fire({
            title: 'Simpan data?',
            text: 'Pastikan data sudah benar sebelum disimpan.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, simpan',
            cancelButtonText: 'Cek lagi',
            customClass: { confirmButton: 'btn btn-success', cancelButton: 'btn btn-secondary' },
            buttonsStyling: false
          }).then(function(result){
            if (result.isConfirmed) {
              form.submit();
            }
          });
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