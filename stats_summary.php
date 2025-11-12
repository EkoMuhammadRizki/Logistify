<?php
// stats_summary.php — Endpoint ringkasan stok
// Fitur:
// - Menghitung total_qty (SUM stok), menipis_count (stok >0 dan <=5), habis_count (stok = 0)
// - Kompatibel dengan soft-delete: bila alur hapus mengatur stok=0, maka ikut terhitung sebagai "habis"
// - Dipanggil dari dashboard-ui.js untuk auto-refresh kartu ringkasan dan menampilkan notifikasi SweetAlert
header('Content-Type: application/json');

require_once __DIR__ . '/config/koneksi.php';

$response = [
  'status' => 'error',
  'message' => 'Terjadi kesalahan',
  'total_qty' => 0,
  'menipis_count' => 0,
  'habis_count' => 0,
];

try {
  $sql = "SELECT 
            COALESCE(SUM(stok),0) AS total_qty,
            SUM(CASE WHEN stok > 0 AND stok <= 5 THEN 1 ELSE 0 END) AS menipis_count,
            SUM(CASE WHEN stok = 0 THEN 1 ELSE 0 END) AS habis_count
          FROM barang";

  if ($res = $koneksi->query($sql)) {
    if ($row = $res->fetch_assoc()) {
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