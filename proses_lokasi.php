<?php
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);
header('Content-Type: application/json');

function json_ok($data = [], $message = 'OK') { echo json_encode(['status'=>'success','message'=>$message,'data'=>$data]); exit; }
function json_err($message = 'Error', $code = 400) { http_response_code($code); echo json_encode(['status'=>'error','message'=>$message]); exit; }

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
if ($action === '') { json_err('Aksi tidak dikenal'); }

switch ($action) {
  case 'migrate': {
    $sql = "CREATE TABLE IF NOT EXISTS locations (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT DEFAULT NULL,
      nama_lokasi VARCHAR(255) NOT NULL,
      kode_lokasi VARCHAR(100) NOT NULL,
      kapasitas INT DEFAULT NULL,
      keterangan TEXT DEFAULT NULL,
      UNIQUE KEY uniq_kode (kode_lokasi),
      KEY idx_nama (nama_lokasi),
      KEY idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$koneksi->query($sql)) { json_err('Gagal membuat tabel: '.$koneksi->error); }
    $chk = $koneksi->query("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'locations' AND column_name = 'user_id'");
    if ($chk && ($row = $chk->fetch_assoc()) && (int)$row['c'] === 0) {
      $koneksi->query("ALTER TABLE locations ADD COLUMN user_id INT DEFAULT NULL");
      $koneksi->query("ALTER TABLE locations ADD INDEX idx_user (user_id)");
    }
    json_ok([], 'Migrasi tabel locations selesai');
  }
  case 'create': {
    $nama = trim($_POST['nama_lokasi'] ?? '');
    $kode = trim($_POST['kode_lokasi'] ?? '');
    $kap = isset($_POST['kapasitas']) && $_POST['kapasitas']!=='' ? (int)$_POST['kapasitas'] : null;
    $ket = trim($_POST['keterangan'] ?? '');
    if ($nama === '' || $kode === '') { json_err('Nama dan kode lokasi wajib diisi'); }
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $stmt = $koneksi->prepare("INSERT INTO locations (user_id, nama_lokasi, kode_lokasi, kapasitas, keterangan) VALUES (?,?,?,?,?)");
    $stmt->bind_param('issis', $uid, $nama, $kode, $kap, $ket);
    if (!$stmt->execute()) { json_err('Gagal menyimpan: '.$stmt->error); }
    json_ok(['id'=>$stmt->insert_id], 'Lokasi ditambahkan');
  }
  case 'update': {
    $id = (int)($_POST['id'] ?? 0);
    if ($id < 1) { json_err('ID tidak valid'); }
    $nama = trim($_POST['nama_lokasi'] ?? '');
    $kode = trim($_POST['kode_lokasi'] ?? '');
    $kap = isset($_POST['kapasitas']) && $_POST['kapasitas']!=='' ? (int)$_POST['kapasitas'] : null;
    $ket = trim($_POST['keterangan'] ?? '');
    if ($nama === '' || $kode === '') { json_err('Nama dan kode lokasi wajib diisi'); }
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $stmt = $koneksi->prepare("UPDATE locations SET nama_lokasi=?, kode_lokasi=?, kapasitas=?, keterangan=? WHERE id=? AND user_id=?");
    $stmt->bind_param('ssisii', $nama, $kode, $kap, $ket, $id, $uid);
    if (!$stmt->execute()) { json_err('Gagal mengupdate: '.$stmt->error); }
    json_ok([], 'Lokasi diperbarui');
  }
  case 'delete': {
    $id = (int)($_POST['id'] ?? 0);
    if ($id < 1) { json_err('ID tidak valid'); }
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $stmt = $koneksi->prepare("DELETE FROM locations WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $uid);
    if (!$stmt->execute()) { json_err('Gagal menghapus: '.$stmt->error); }
    json_ok([], 'Lokasi dihapus');
  }
  default: json_err('Aksi tidak didukung');
}
?>
