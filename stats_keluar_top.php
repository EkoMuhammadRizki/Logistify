<?php
// stats_keluar_top.php — Data grafik Barang Keluar Terbanyak (Top-N)
// Fitur:
// - Mengagregasi total keluar per barang untuk tahun yang diminta (default: tahun berjalan)
// - Mengembalikan labels (nama barang) dan values (jumlah keluar) untuk dipakai Chart.js
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);

header('Content-Type: application/json');

try {
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 5;

    // Agregasi total barang keluar per barang (tahun berjalan)
    $sql = "SELECT nama_barang, SUM(jumlah_keluar) AS total_keluar
            FROM barang_keluar
            WHERE YEAR(tanggal_keluar) = ?
            GROUP BY nama_barang
            ORDER BY total_keluar DESC, nama_barang ASC
            LIMIT ?";

    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("ii", $year, $limit);
    $stmt->execute();
    $res = $stmt->get_result();

    $labels = [];
    $values = [];
    while ($row = $res->fetch_assoc()) {
        $labels[] = (string)$row['nama_barang'];
        $values[] = (int)$row['total_keluar'];
    }

    echo json_encode([
        'status' => 'success',
        'labels' => $labels,
        'values' => $values,
        'year'   => $year,
        'limit'  => $limit
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memuat data barang keluar terbanyak']);
}