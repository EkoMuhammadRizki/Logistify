<?php
// stats_min_stok.php — Data grafik Stok Minimum (Top-5)
// Fitur:
// - Hanya menampilkan barang dengan stok < 5
// - Mengecualikan item soft-deleted (is_deleted = 0) bila kolom tersedia
// - Dipakai oleh dashboard-ui.js untuk grafik horizontal (indexAxis: 'y')
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);

header('Content-Type: application/json');

try {
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 5;

    // Cek apakah kolom soft-delete tersedia agar bisa dikecualikan dari grafik
    $hasSoftDelete = false;
    if ($chk = $koneksi->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'barang' AND column_name = 'is_deleted'")) {
        $chk->execute();
        $resChk = $chk->get_result();
        if ($resChk && ($row = $resChk->fetch_assoc())) {
            $hasSoftDelete = ((int)$row['c'] > 0);
        }
    }

    if ($hasSoftDelete) {
        $sql = "SELECT nama_barang, stok FROM barang WHERE is_deleted = 0 AND stok < 5 ORDER BY stok ASC, nama_barang ASC LIMIT ?";
    } else {
        $sql = "SELECT nama_barang, stok FROM barang WHERE stok < 5 ORDER BY stok ASC, nama_barang ASC LIMIT ?";
    }

    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();

    $labels = [];
    $values = [];
    while ($row = $res->fetch_assoc()) {
        $labels[] = (string)$row['nama_barang'];
        $values[] = (int)$row['stok'];
    }

    echo json_encode([
        'status' => 'success',
        'labels' => $labels,
        'values' => $values,
        'limit'  => $limit
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memuat data stok minimum']);
}