<?php
// stats_aktivitas.php — Data grafik aktivitas stok per bulan (masuk & keluar)
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);
header('Content-Type: application/json');

function table_exists(mysqli $db, $name){
  $q = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
  $q->bind_param('s', $name);
  $q->execute();
  $r = $q->get_result()->fetch_assoc();
  return isset($r['c']) && ((int)$r['c'] > 0);
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$masuk = array_fill(0, 12, 0);
$keluar = array_fill(0, 12, 0);

if (table_exists($koneksi, 'barang_masuk')) {
  $sql = "SELECT MONTH(tanggal_masuk) AS m, COALESCE(SUM(jumlah_masuk),0) AS total FROM barang_masuk WHERE YEAR(tanggal_masuk) = ? GROUP BY m";
  if ($stmt = $koneksi->prepare($sql)) {
    $stmt->bind_param('i', $year);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      while($row = $res->fetch_assoc()){
        $idx = max(1, min(12, (int)$row['m'])) - 1;
        $masuk[$idx] = (int)$row['total'];
      }
    }
  }
}
if (table_exists($koneksi, 'barang_keluar')) {
  $sql = "SELECT MONTH(tanggal_keluar) AS m, COALESCE(SUM(jumlah_keluar),0) AS total FROM barang_keluar WHERE YEAR(tanggal_keluar) = ? GROUP BY m";
  if ($stmt = $koneksi->prepare($sql)) {
    $stmt->bind_param('i', $year);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      while($row = $res->fetch_assoc()){
        $idx = max(1, min(12, (int)$row['m'])) - 1;
        $keluar[$idx] = (int)$row['total'];
      }
    }
  }
}

$labels = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
echo json_encode([
  'status' => 'success',
  'year' => $year,
  'labels' => $labels,
  'masuk' => $masuk,
  'keluar' => $keluar
]);
exit;
?>