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
    $sql = "CREATE TABLE IF NOT EXISTS suppliers (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT DEFAULT NULL,
      nama_supplier VARCHAR(255) NOT NULL,
      kontak VARCHAR(100) DEFAULT NULL,
      email VARCHAR(255) DEFAULT NULL,
      alamat TEXT DEFAULT NULL,
      keterangan TEXT DEFAULT NULL,
      KEY idx_nama (nama_supplier),
      KEY idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$koneksi->query($sql)) { json_err('Gagal membuat tabel: '.$koneksi->error); }
    // Tambah kolom user_id bila belum ada
    $chk = $koneksi->query("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'suppliers' AND column_name = 'user_id'");
    if ($chk && ($row = $chk->fetch_assoc()) && (int)$row['c'] === 0) {
      $koneksi->query("ALTER TABLE suppliers ADD COLUMN user_id INT DEFAULT NULL");
      $koneksi->query("ALTER TABLE suppliers ADD INDEX idx_user (user_id)");
    }
    json_ok([], 'Migrasi tabel suppliers selesai');
  }
  case 'create': {
    $nama = trim($_POST['nama_supplier'] ?? '');
    $kontak = trim($_POST['kontak'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $ket = trim($_POST['keterangan'] ?? '');
    if ($nama === '') { json_err('Nama supplier wajib diisi'); }
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $stmt = $koneksi->prepare("INSERT INTO suppliers (user_id, nama_supplier, kontak, email, alamat, keterangan) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('isssss', $uid, $nama, $kontak, $email, $alamat, $ket);
    if (!$stmt->execute()) { json_err('Gagal menyimpan: '.$stmt->error); }
    json_ok(['id'=>$stmt->insert_id], 'Supplier ditambahkan');
  }
  case 'update': {
    $id = (int)($_POST['id'] ?? 0);
    if ($id < 1) { json_err('ID tidak valid'); }
    $nama = trim($_POST['nama_supplier'] ?? '');
    $kontak = trim($_POST['kontak'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $ket = trim($_POST['keterangan'] ?? '');
    if ($nama === '') { json_err('Nama supplier wajib diisi'); }
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $stmt = $koneksi->prepare("UPDATE suppliers SET nama_supplier=?, kontak=?, email=?, alamat=?, keterangan=? WHERE id=? AND user_id=?");
    $stmt->bind_param('sssssii', $nama, $kontak, $email, $alamat, $ket, $id, $uid);
    if (!$stmt->execute()) { json_err('Gagal mengupdate: '.$stmt->error); }
    json_ok([], 'Supplier diperbarui');
  }
  case 'delete': {
    $id = (int)($_POST['id'] ?? 0);
    if ($id < 1) { json_err('ID tidak valid'); }
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $stmt = $koneksi->prepare("DELETE FROM suppliers WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $uid);
    if (!$stmt->execute()) { json_err('Gagal menghapus: '.$stmt->error); }
    json_ok([], 'Supplier dihapus');
  }
  default: json_err('Aksi tidak didukung');
}
?>
