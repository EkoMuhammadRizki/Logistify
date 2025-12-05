<?php
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 50;
$table_missing = false; $result = null;

$check = $koneksi->query("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'locations'");
$exists = ($check && ($row = $check->fetch_assoc()) && ((int)$row['c'] > 0));
$params = []; $types = '';
if ($exists) {
  $sql = "SELECT id, nama_lokasi, kode_lokasi, kapasitas, keterangan FROM locations WHERE user_id = ?";
  if ($q !== '') { $sql .= " AND (nama_lokasi LIKE ? OR kode_lokasi LIKE ? OR keterangan LIKE ?)"; $like = '%'.$q.'%'; array_push($params,$like,$like,$like); $types.='sss'; }
  $sql .= " ORDER BY nama_lokasi ASC LIMIT ?"; $params[] = $limit; $types.='i';
  try { $stmt = $koneksi->prepare($sql); $uid = (int)($_SESSION['user_id'] ?? 0); $types = 'i' . $types; $params = array_merge([$uid], $params); if ($types!==''){ $stmt->bind_param($types, ...$params);} $stmt->execute(); $result = $stmt->get_result(); } catch(Throwable $e){ $table_missing = true; }
} else { $table_missing = true; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <title>Logistify - Lokasi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/dashboard.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="assets/media/fav-icon.png">
  <meta name="theme-color" content="#28a745">
</head>
<body>
  <div class="brand-bar">
    <div class="logo-dummy"><img src="assets/media/logistify.png" alt="Logo"></div>
    <div class="site-title">Logistify</div>
  </div>
  <div class="container mt-3">
    <div class="dashboard-container">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="m-0">Lokasi</h2>
        <div class="d-flex gap-2">
          <a href="dashboard.php" class="btn btn-outline-light"><i class="bi bi-grid"></i> Dashboard</a>
        </div>
      </div>

      <form id="formLokasi" class="border rounded p-3 mb-3">
        <h5 class="mb-3">Tambah / Edit Lokasi</h5>
        <input type="hidden" name="id" id="locId">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Nama Lokasi</label>
            <input type="text" name="nama_lokasi" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Kode Lokasi</label>
            <input type="text" name="kode_lokasi" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Kapasitas (opsional)</label>
            <input type="number" name="kapasitas" class="form-control" min="0" step="1">
          </div>
        </div>
        <div class="mt-2">
          <label class="form-label">Keterangan</label>
          <textarea name="keterangan" class="form-control" rows="2"></textarea>
        </div>
        <div class="mt-3 d-flex gap-2">
          <button type="button" id="btnSaveLokasi" class="btn btn-success"><i class="bi bi-save"></i> Simpan</button>
          <button type="reset" class="btn btn-secondary">Reset</button>
        </div>
      </form>

      <form class="filter-bar" method="get">
        <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($q); ?>" placeholder="Cari nama/kode/keterangan...">
        <select name="limit" class="form-select">
          <?php foreach ([20,50,100,200] as $opt): ?><option value="<?= $opt; ?>" <?= $limit===$opt?'selected':''; ?>>Tampilkan <?= $opt; ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-success" type="submit"><i class="bi bi-filter"></i> Terapkan</button>
      </form>

      <div class="table-responsive mt-2">
        <table class="table table-bordered table-striped table-dashboard">
          <thead>
            <tr>
              <th>Nama Lokasi</th>
              <th>Kode Lokasi</th>
              <th>Kapasitas</th>
              <th>Keterangan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($table_missing): ?>
              <tr><td colspan="5" class="text-center text-light">Tabel <code>locations</code> belum tersedia.<br><button class="btn btn-success btn-sm mt-2" id="btnMigrateLokasi"><i class="bi bi-hammer"></i> Buat Tabel Otomatis</button></td></tr>
            <?php elseif ($result && $result->num_rows > 0): ?>
              <?php while($row = $result->fetch_assoc()): ?>
              <tr data-id="<?= (int)$row['id']; ?>">
                <td><?= htmlspecialchars($row['nama_lokasi']); ?></td>
                <td><?= htmlspecialchars($row['kode_lokasi']); ?></td>
                <td><?= htmlspecialchars($row['kapasitas'] ?? ''); ?></td>
                <td><?= htmlspecialchars($row['keterangan'] ?? ''); ?></td>
                <td>
                  <button class="btn btn-warning btn-sm edit-lokasi"><i class="bi bi-pencil"></i> Edit</button>
                  <button class="btn btn-danger btn-sm delete-lokasi"><i class="bi bi-trash"></i> Hapus</button>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="5" class="text-center text-light">Belum ada data lokasi.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    $(function(){
      $('#btnMigrateLokasi').on('click', function(){
        $.ajax({ url:'proses_lokasi.php?action=migrate', type:'POST', dataType:'json', success:function(r){
          if (r.status==='success'){ Swal.fire({title:'Selesai',text:r.message,icon:'success'}).then(function(){ location.reload(); }); } else { Swal.fire({title:'Gagal',text:r.message,icon:'error'}); }
        }, error:function(){ Swal.fire({title:'Error',text:'Kesalahan migrasi.',icon:'error'}); }});
      });
      $('#btnSaveLokasi').on('click', function(){
        var form = document.getElementById('formLokasi'); var fd = new FormData(form);
        var id = document.getElementById('locId').value; fd.append('action', id? 'update':'create');
        $.ajax({ url:'proses_lokasi.php', type:'POST', data:fd, contentType:false, processData:false, dataType:'json', success:function(r){
          if (r.status==='success'){ Swal.fire({title:'Berhasil',text:r.message,icon:'success'}).then(function(){ location.reload(); }); } else { Swal.fire({title:'Gagal',text:r.message,icon:'error'}); }
        }, error:function(){ Swal.fire({title:'Error',text:'Kesalahan komunikasi.',icon:'error'}); }});
      });
      $(document).on('click','.edit-lokasi', function(){
        var tr = $(this).closest('tr'); var id = tr.data('id');
        $('#locId').val(id);
        $('input[name="nama_lokasi"]').val(tr.children().eq(0).text());
        $('input[name="kode_lokasi"]').val(tr.children().eq(1).text());
        $('input[name="kapasitas"]').val(tr.children().eq(2).text());
        $('textarea[name="keterangan"]').val(tr.children().eq(3).text());
        $('html, body').animate({ scrollTop: $('#formLokasi').offset().top - 80 }, 300);
      });
      $(document).on('click','.delete-lokasi', function(){
        var id = $(this).closest('tr').data('id');
        Swal.fire({title:'Hapus lokasi?',icon:'warning',showCancelButton:true}).then(function(x){
          if (!x.isConfirmed) return;
          $.ajax({ url:'proses_lokasi.php?action=delete', type:'POST', data:{ id:id }, dataType:'json', success:function(r){
            if (r.status==='success'){ Swal.fire({title:'Berhasil',text:r.message,icon:'success'}).then(function(){ $('tr[data-id='+id+']').remove(); }); } else { Swal.fire({title:'Gagal',text:r.message,icon:'error'}); }
          }, error:function(){ Swal.fire({title:'Error',text:'Kesalahan komunikasi.',icon:'error'}); }});
        });
      });
    });
  </script>
</body>
</html>
