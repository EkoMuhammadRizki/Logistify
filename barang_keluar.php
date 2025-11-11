<?php
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 20;
$table_missing = false; $result = null;

$exists = false;
$check = $koneksi->query("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'barang_keluar'");
if ($check && $row = $check->fetch_assoc()) { $exists = ((int)$row['c'] > 0); }

$params = []; $types = '';
if ($exists) {
  $sql = "SELECT id, tanggal_keluar, nama_barang, kode_barang, jumlah_keluar, tujuan, dokumen, keterangan FROM barang_keluar WHERE 1=1";
  if ($q !== '') {
    $sql .= " AND (nama_barang LIKE ? OR kode_barang LIKE ? OR tujuan LIKE ? OR keterangan LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
  }
  $sql .= " ORDER BY tanggal_keluar DESC, id DESC LIMIT ?";
  $params[] = $limit; $types .= 'i';
  try {
    $stmt = $koneksi->prepare($sql);
    if ($types !== '') { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $result = $stmt->get_result();
  } catch (Throwable $e) { $table_missing = true; $result = null; }
} else { $table_missing = true; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <title>Logistify - Barang Keluar</title>
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
        <h2 class="m-0">Barang Keluar</h2>
        <div class="d-flex gap-2">
          <a href="dashboard.php" class="btn btn-outline-light"><i class="bi bi-arrow-left"></i> Kembali</a>
          <a href="generate_laporan.php?report=keluar" target="_blank" class="btn btn-success"><i class="bi bi-file-earmark-pdf"></i> Laporan PDF</a>
        </div>
      </div>

      <?php $items = $koneksi->query("SELECT id, nama_barang FROM barang ORDER BY nama_barang ASC"); ?>
      <form id="formKeluar" class="border rounded p-3 mb-3" enctype="multipart/form-data">
        <h5 class="mb-3">Tambah Transaksi Barang Keluar</h5>
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
            <label class="form-label">Jumlah Keluar</label>
            <input type="number" name="jumlah_keluar" class="form-control" min="1" step="1" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tanggal</label>
            <input type="datetime-local" name="tanggal_keluar" class="form-control">
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label class="form-label">Tujuan/Penerima</label>
            <input type="text" name="tujuan" class="form-control" placeholder="Nama penerima" required>
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
          <button type="button" id="btnSubmitKeluar" class="btn btn-success"><i class="bi bi-upload"></i> Simpan Transaksi Keluar</button>
        </div>
      </form>

      <form class="filter-bar" method="get">
        <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($q); ?>" placeholder="Cari nama/kode/tujuan/keterangan...">
        <select name="limit" class="form-select">
          <?php foreach ([10,20,50,100,200] as $opt): ?>
            <option value="<?= $opt; ?>" <?= $limit===$opt?'selected':''; ?>>Tampilkan <?= $opt; ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-success" type="submit"><i class="bi bi-filter"></i> Terapkan</button>
      </form>

      <div class="table-responsive mt-2">
        <table class="table table-bordered table-striped table-dashboard">
          <thead>
            <tr>
              <th>Tanggal Barang Keluar</th>
              <th>Nama Barang</th>
              <th>Kode Barang</th>
              <th>Jumlah Keluar</th>
              <th>Tujuan/Penerima</th>
              <th>Dokumen</th>
              <th>Keterangan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($table_missing): ?>
              <tr>
                <td colspan="8" class="text-center text-light">
                  Tabel transaksi <code>barang_keluar</code> belum tersedia.<br>
                  <button class="btn btn-success btn-sm mt-2" id="btnMigrateKeluar"><i class="bi bi-hammer"></i> Buat Tabel Otomatis</button>
                </td>
              </tr>
            <?php elseif ($result && $result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <?php $doc = isset($row['dokumen']) ? trim($row['dokumen']) : ''; $docRel = $doc !== '' ? 'uplouds/' . $doc : ''; $docExists = ($docRel !== '' && file_exists(__DIR__ . '/' . $docRel)); ?>
                <tr data-id="<?= (int)$row['id']; ?>">
                  <td><?= htmlspecialchars($row['tanggal_keluar']); ?></td>
                  <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                  <td><?= htmlspecialchars($row['kode_barang']); ?></td>
                  <td><?= (int)$row['jumlah_keluar']; ?></td>
                  <td><?= htmlspecialchars($row['tujuan']); ?></td>
                  <td>
                    <?php if ($docExists): ?>
                      <a href="<?= htmlspecialchars($docRel); ?>" class="btn btn-outline-light btn-sm" target="_blank"><i class="bi bi-paperclip"></i> Lihat</a>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($row['keterangan']); ?></td>
                  <td>
                    <button class="btn btn-danger btn-sm delete-keluar"><i class="bi bi-trash"></i> Hapus</button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center text-light">Belum ada transaksi barang keluar.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(function(){
      $('#btnMigrateKeluar').on('click', function(){
        $.ajax({ url:'proses_keluar.php?action=migrate', type:'POST', dataType:'json', success:function(resp){
          if (resp.status==='success') { Swal.fire({ title:'Selesai', text:resp.message, icon:'success' }).then(function(){ location.reload(); }); }
          else { Swal.fire({ title:'Gagal', text:resp.message, icon:'error' }); }
        }, error:function(){ Swal.fire({ title:'Error', text:'Terjadi kesalahan migrasi.', icon:'error' }); }});
      });
      $('#btnSubmitKeluar').on('click', function(){
        var form = document.getElementById('formKeluar'); if (!form) return;
        var fd = new FormData(form); fd.append('action','create');
        $.ajax({
          url:'proses_keluar.php',
          type:'POST',
          data:fd,
          contentType:false,
          processData:false,
          dataType:'json',
          success:function(resp){
            if (resp.status==='success') {
              Swal.fire({ title:'Berhasil', text:resp.message, icon:'success' });
              form.reset();
              if (typeof window.refreshStockChart === 'function') { window.refreshStockChart(); }
              if (typeof window.refreshMinStockChart === 'function') { window.refreshMinStockChart(); }
              if (typeof window.refreshTopKeluarChart === 'function') { window.refreshTopKeluarChart(); }
            } else { Swal.fire({ title:'Gagal', text:resp.message, icon:'error' }); }
          },
          error:function(){ Swal.fire({ title:'Error', text:'Terjadi kesalahan komunikasi dengan server.', icon:'error' }); }
        });
      });
      $(document).on('click', '.delete-keluar', function(){
        var id = $(this).closest('tr').data('id');
        Swal.fire({ title:'Hapus transaksi?', icon:'warning', showCancelButton:true }).then(function(r){
          if (!r.isConfirmed) return;
          $.ajax({
            url:'proses_keluar.php?action=delete',
            type:'POST',
            data:{ id:id },
            dataType:'json',
            success:function(resp){
              if (resp.status==='success'){
                Swal.fire({ title:'Berhasil', text:resp.message, icon:'success' }).then(function(){
                  $('tr[data-id="'+id+'"]').remove();
                  if (typeof window.refreshStockChart === 'function') { window.refreshStockChart(); }
                  if (typeof window.refreshMinStockChart === 'function') { window.refreshMinStockChart(); }
                  if (typeof window.refreshTopKeluarChart === 'function') { window.refreshTopKeluarChart(); }
                });
              } else { Swal.fire({ title:'Gagal', text:resp.message, icon:'error' }); }
            },
            error:function(){ Swal.fire({ title:'Error', text:'Kesalahan komunikasi.', icon:'error' }); }
          });
        });
      });
    });
  </script>
</body>
</html>