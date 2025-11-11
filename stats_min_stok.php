<?php
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);

header('Content-Type: application/json');

try {
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 5;
    $sql = "SELECT nama_barang, stok FROM barang ORDER BY stok ASC, nama_barang ASC LIMIT ?";
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