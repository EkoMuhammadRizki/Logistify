<?php
// stats_aktivitas.php — Data grafik aktivitas stok per bulan
// Fitur:
// - Agregasi total Barang Masuk & Keluar per bulan untuk tahun berjalan
// - Aman terhadap ketiadaan tabel (cek keberadaan tabel terlebih dahulu)
// - Dipanggil via dashboard-ui.js untuk merender grafik Aktivitas Stok
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
$uid = (int)($_SESSION['user_id'] ?? 0);

if (table_exists($koneksi, 'barang_masuk')) {
  $colChk = $koneksi->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'barang_masuk' AND column_name = 'user_id'");
  $colChk->execute(); $hasUser = false; if ($r = $colChk->get_result()->fetch_assoc()) { $hasUser = ((int)$r['c'] > 0); }
  if ($hasUser) {
    $sql = "SELECT MONTH(tanggal_masuk) AS m, COALESCE(SUM(jumlah_masuk),0) AS total FROM barang_masuk WHERE YEAR(tanggal_masuk) = ? AND user_id = ? GROUP BY m";
    if ($stmt = $koneksi->prepare($sql)) {
      $stmt->bind_param('ii', $year, $uid);
      if ($stmt->execute()) {
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){
          $idx = max(1, min(12, (int)$row['m'])) - 1;
          $masuk[$idx] = (int)$row['total'];
        }
      }
    }
  }
}
if (table_exists($koneksi, 'barang_keluar')) {
  $colChk = $koneksi->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'barang_keluar' AND column_name = 'user_id'");
  $colChk->execute(); $hasUser = false; if ($r = $colChk->get_result()->fetch_assoc()) { $hasUser = ((int)$r['c'] > 0); }
  if ($hasUser) {
    $sql = "SELECT MONTH(tanggal_keluar) AS m, COALESCE(SUM(jumlah_keluar),0) AS total FROM barang_keluar WHERE YEAR(tanggal_keluar) = ? AND user_id = ? GROUP BY m";
    if ($stmt = $koneksi->prepare($sql)) {
      $stmt->bind_param('ii', $year, $uid);
      if ($stmt->execute()) {
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){
          $idx = max(1, min(12, (int)$row['m'])) - 1;
          $keluar[$idx] = (int)$row['total'];
        }
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
