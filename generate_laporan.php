<?php
// generate_laporan.php - Reporting PDF
// Menghasilkan file PDF laporan data barang menggunakan Dompdf.
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);

// Memuat library Dompdf
require_once 'libs/dompdf/autoload.inc.php'; 
use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Mengambil data dari database
// Contoh filter menggunakan **GET**
$filter_stok = isset($_GET['min_stok']) ? (int)$_GET['min_stok'] : 0;
$query = "SELECT * FROM barang WHERE stok >= ? ORDER BY nama_barang ASC";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $filter_stok);
$stmt->execute();
$result = $stmt->get_result();

// 2. Membuat struktur HTML laporan
$html = '
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Data Barang</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h1 { text-align: center; }
        .foto-cell { width: 90px; }
        .foto-cell img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #ccc; }
        .text-muted { color: #777; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Laporan Data Barang</h1>
    <p>Data yang ditampilkan dengan stok minimal: ' . $filter_stok . '</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th class="foto-cell">Foto</th>
                <th>Nama Barang</th>
                <th>Deskripsi</th>
                <th>Stok</th>
                <th>Harga</th>
            </tr>
        </thead>
        <tbody>';
            
$no = 1;
while($row = $result->fetch_assoc()) {
    // Tentukan path foto lokal (dalam chroot Dompdf)
    $foto = isset($row['foto_barang']) ? trim($row['foto_barang']) : '';
    $fotoPathRel = $foto !== '' ? 'uplouds/' . $foto : '';
    $fotoExists = ($fotoPathRel !== '' && file_exists(__DIR__ . '/' . $fotoPathRel));

    $html .= '
            <tr>
                <td>' . $no++ . '</td>
                <td class="foto-cell">' . (
                    $fotoExists
                        ? '<img src="' . htmlspecialchars($fotoPathRel) . '" alt="Foto Barang">'
                        : '<span class="text-muted">Tidak ada foto</span>'
                ) . '</td>
                <td>' . htmlspecialchars($row['nama_barang']) . '</td>
                <td>' . htmlspecialchars(substr($row['deskripsi'], 0, 50)) . '...</td>
                <td>' . $row['stok'] . '</td>
                <td>Rp ' . number_format($row['harga'], 0, ',', '.') . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

// 3. Mengatur Dompdf
$options = new Options();
$options->set('defaultFont', 'Helvetica');
// Hindari ketergantungan Masterminds\HTML5 jika tidak terpasang
$options->set('isHtml5ParserEnabled', false);
// Pastikan akses file lokal aman dan path relatif dapat dibaca
$options->set('chroot', __DIR__);
$dompdf = new Dompdf($options);

// 4. Memuat HTML dan render
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 5. Output file PDF ke browser untuk di-download
// Penamaan file: laporan_barang_{urutan}_{dd}_{mm}_{yyyy}.pdf
// Urutan harian disimpan di file per tanggal pada folder uplouds agar konsisten lintas user
$dateKey = date('Y-m-d');
$dateLabel = date('d_m_Y');
$countFile = __DIR__ . '/uplouds/laporan_count_' . $dateKey . '.txt';
$count = 0;
if (file_exists($countFile)) {
    $raw = @file_get_contents($countFile);
    if ($raw !== false) {
        $n = (int)trim($raw);
        if ($n >= 0) { $count = $n; }
    }
}
$count++;
@file_put_contents($countFile, (string)$count);

$filename = 'laporan_barang_' . $count . '_' . $dateLabel . '.pdf';
$dompdf->stream($filename, array("Attachment" => true)); // **REPORTING PDF**

exit;
?>