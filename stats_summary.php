<?php
// stats_summary.php — Endpoint ringkasan stok
// Fitur:
// - Menghitung total_qty (SUM stok), menipis_count (stok >0 dan <=5), habis_count (stok = 0)
// - Kompatibel dengan soft-delete: bila alur hapus mengatur stok=0, maka ikut terhitung sebagai "habis"
// - Dipanggil dari dashboard-ui.js untuk auto-refresh kartu ringkasan dan menampilkan notifikasi SweetAlert
header('Content-Type: application/json');

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/functions/auth.php';
require_login($koneksi);

$response = [
  'status' => 'error',
  'message' => 'Terjadi kesalahan',
  'total_qty' => 0,
  'menipis_count' => 0,
  'habis_count' => 0,
];

try {
  // Pastikan kolom user_id ada di tabel barang
  if ($chk = $koneksi->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'barang' AND column_name = 'user_id'")) {
    $chk->execute(); $res = $chk->get_result(); $c = 0; if ($res && ($r = $res->fetch_assoc())) { $c = (int)$r['c']; }
    if ($c === 0) { $koneksi->query("ALTER TABLE `barang` ADD COLUMN `user_id` INT DEFAULT NULL"); $koneksi->query("ALTER TABLE `barang` ADD INDEX `idx_user` (`user_id`)"); }
  }
  $uid = (int)($_SESSION['user_id'] ?? 0);
  $sql = "SELECT 
            COALESCE(SUM(stok),0) AS total_qty,
            SUM(CASE WHEN stok > 0 AND stok <= 5 THEN 1 ELSE 0 END) AS menipis_count,
            SUM(CASE WHEN stok = 0 THEN 1 ELSE 0 END) AS habis_count
          FROM barang WHERE user_id = ?";

  if ($stmt = $koneksi->prepare($sql)) {
    $stmt->bind_param('i', $uid);
    if ($stmt->execute() && ($res = $stmt->get_result()) && ($row = $res->fetch_assoc())) {
      $response['status'] = 'success';
      $response['message'] = 'OK';
      $response['total_qty'] = (int)$row['total_qty'];
      $response['menipis_count'] = (int)$row['menipis_count'];
      $response['habis_count'] = (int)$row['habis_count'];
    } else {
      $response['message'] = 'Data tidak ditemukan';
    }
  } else {
    $response['message'] = 'Query gagal dieksekusi';
  }
} catch (Throwable $e) {
  $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
