<?php
// proses_masuk.php — Endpoint transaksi Barang Masuk (migrate/create/delete)
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);

header('Content-Type: application/json');

function json_ok($data = [], $message = 'OK') {
  echo json_encode(['status' => 'success', 'message' => $message, 'data' => $data]);
  exit;
}
function json_err($message = 'Error', $code = 400) {
  http_response_code($code);
  echo json_encode(['status' => 'error', 'message' => $message]);
  exit;
}

function ensure_upload_dir() {
  $dir = __DIR__ . DIRECTORY_SEPARATOR . 'uplouds';
  if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
  return $dir;
}

function handle_upload_doc($field = 'dokumen') {
  if (!isset($_FILES[$field]) || !is_array($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
    return null; // optional
  }
  $allowed = ['pdf','jpg','jpeg','png'];
  $name = $_FILES[$field]['name'];
  $tmp = $_FILES[$field]['tmp_name'];
  $size = $_FILES[$field]['size'];
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if (!in_array($ext, $allowed)) { json_err('Dokumen harus pdf/jpg/jpeg/png'); }
  if ($size > 4 * 1024 * 1024) { json_err('Ukuran dokumen max 4MB'); }
  $dir = ensure_upload_dir();
  $safeBase = preg_replace('/[^A-Za-z0-9_-]/','_', pathinfo($name, PATHINFO_FILENAME));
  $fname = 'doc_' . date('Ymd_His') . '_' . $safeBase . '.' . $ext;
  $dest = $dir . DIRECTORY_SEPARATOR . $fname;
  if (!move_uploaded_file($tmp, $dest)) { json_err('Gagal menyimpan dokumen'); }
  return $fname; // simpan nama file saja
}

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
if ($action === '') { json_err('Aksi tidak dikenal'); }

switch ($action) {
  case 'migrate': {
    $sql = "CREATE TABLE IF NOT EXISTS barang_masuk (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT DEFAULT NULL,
      tanggal_masuk DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      nama_barang VARCHAR(255) NOT NULL,
      kode_barang VARCHAR(100) NOT NULL,
      jumlah_masuk INT NOT NULL,
      satuan VARCHAR(50) DEFAULT NULL,
      supplier VARCHAR(255) DEFAULT NULL,
      lokasi VARCHAR(255) DEFAULT NULL,
      dokumen VARCHAR(255) DEFAULT NULL,
      foto_barang VARCHAR(255) DEFAULT NULL,
      keterangan TEXT DEFAULT NULL,
      KEY idx_kode_barang (kode_barang),
      KEY idx_tanggal (tanggal_masuk),
      KEY idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$koneksi->query($sql)) { json_err('Gagal membuat tabel: ' . $koneksi->error); }
    $chk = $koneksi->query("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'barang_masuk' AND column_name = 'user_id'");
    if ($chk && ($row = $chk->fetch_assoc()) && (int)$row['c'] === 0) {
      $koneksi->query("ALTER TABLE barang_masuk ADD COLUMN user_id INT DEFAULT NULL");
      $koneksi->query("ALTER TABLE barang_masuk ADD INDEX idx_user (user_id)");
    }
    // Pastikan kolom foto_barang ada dan kolom supplier/lokasi nullable untuk kompatibilitas
    $colCheck = $koneksi->query("SELECT COLUMN_NAME, IS_NULLABLE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'barang_masuk'");
    $cols = [];
    if ($colCheck) { while($r = $colCheck->fetch_assoc()){ $cols[$r['COLUMN_NAME']] = $r; } }
    if (!isset($cols['foto_barang'])) {
      $koneksi->query("ALTER TABLE barang_masuk ADD COLUMN foto_barang VARCHAR(255) DEFAULT NULL");
    }
    if (isset($cols['supplier']) && strtoupper($cols['supplier']['IS_NULLABLE']) === 'NO') {
      $koneksi->query("ALTER TABLE barang_masuk MODIFY supplier VARCHAR(255) NULL");
    }
    if (isset($cols['lokasi']) && strtoupper($cols['lokasi']['IS_NULLABLE']) === 'NO') {
      $koneksi->query("ALTER TABLE barang_masuk MODIFY lokasi VARCHAR(255) NULL");
    }
    json_ok([], 'Migrasi tabel barang_masuk selesai');
  }
  case 'create': {
    // Ambil data terpilih dari master barang
    $barang_id = isset($_POST['barang_id']) ? (int)$_POST['barang_id'] : 0;
    if ($barang_id < 1) { json_err('Barang belum dipilih'); }
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $resBarang = $koneksi->prepare("SELECT id, nama_barang FROM barang WHERE id = ? AND user_id = ?");
    $resBarang->bind_param('ii', $barang_id, $uid);
    $resBarang->execute();
    $rowBr = $resBarang->get_result()->fetch_assoc();
    if (!$rowBr) { json_err('Barang tidak ditemukan', 404); }

    // Ambil data input
    $tanggal = isset($_POST['tanggal_masuk']) && $_POST['tanggal_masuk'] !== '' ? $_POST['tanggal_masuk'] : null; // datetime-local
    $jumlah = isset($_POST['jumlah_masuk']) ? (int)$_POST['jumlah_masuk'] : 0;
    $supplier = isset($_POST['supplier']) ? trim($_POST['supplier']) : '';
    $keterangan = isset($_POST['keterangan']) ? trim($_POST['keterangan']) : null;
    if ($jumlah < 1 || $supplier === '') { json_err('Jumlah dan supplier wajib diisi'); }
    $dokumen = handle_upload_doc('dokumen');
    $satuan = null; // fallback jika kolom tidak ada
    $lokasi = '';
    // Normalisasi tanggal
    if ($tanggal === null || $tanggal === '') { $tanggal = date('Y-m-d H:i:s'); } else { $tanggal = str_replace('T', ' ', $tanggal) . ':00'; }

    // Simpan transaksi barang_masuk
    $stmt = $koneksi->prepare("INSERT INTO barang_masuk (user_id, tanggal_masuk, nama_barang, kode_barang, jumlah_masuk, satuan, supplier, lokasi, dokumen, foto_barang, keterangan) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $nullFoto = null;
    $kodeResolved = 'BRG-' . str_pad((string)$rowBr['id'], 4, '0', STR_PAD_LEFT);
    $stmt->bind_param('isssissssss', $uid, $tanggal, $rowBr['nama_barang'], $kodeResolved, $jumlah, $satuan, $supplier, $lokasi, $dokumen, $nullFoto, $keterangan);
    if (!$stmt->execute()) { json_err('Gagal menyimpan: ' . $stmt->error); }

    // Update stok di master barang
    $up = $koneksi->prepare("UPDATE barang SET stok = stok + ? WHERE id = ?");
    $up->bind_param('ii', $jumlah, $barang_id);
    $up->execute();

    json_ok(['id' => $stmt->insert_id], 'Transaksi barang masuk ditambahkan dan stok diperbarui');
  }
  case 'delete': {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    if ($id < 1) { json_err('ID tidak valid'); }
    // Ambil dokumen
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $stmt = $koneksi->prepare("SELECT dokumen FROM barang_masuk WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) { json_err('Data tidak ditemukan', 404); }
    $row = $res->fetch_assoc();
    $stmtDel = $koneksi->prepare("DELETE FROM barang_masuk WHERE id = ? AND user_id = ?");
    $stmtDel->bind_param('ii', $id, $uid);
    if (!$stmtDel->execute()) { json_err('Gagal menghapus: ' . $stmtDel->error); }
    if (!empty($row['dokumen'])) {
      @unlink(__DIR__ . DIRECTORY_SEPARATOR . 'uplouds' . DIRECTORY_SEPARATOR . $row['dokumen']);
    }
    json_ok([], 'Transaksi barang masuk dihapus');
  }
  default: json_err('Aksi tidak didukung');
}
