<?php
// generate_laporan.php — Modul Reporting PDF
// Menghasilkan file PDF laporan data barang menggunakan Dompdf.
// Alur: ambil data (opsional filter stok), bangun HTML tabel, render Dompdf,
// set chroot untuk akses file lokal (uplouds/), dan beri nama file dengan counter harian.
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);

$chk = $koneksi->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'barang' AND column_name = 'user_id'");
$chk->execute(); $cr = 0; $rs = $chk->get_result(); if ($rs && ($rw = $rs->fetch_assoc())) { $cr = (int)$rw['c']; }
if ($cr === 0) { $koneksi->query("ALTER TABLE `barang` ADD COLUMN `user_id` INT DEFAULT NULL"); $koneksi->query("ALTER TABLE `barang` ADD INDEX `idx_user` (`user_id`)"); }

$chk2 = $koneksi->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'barang_masuk' AND column_name = 'user_id'");
$chk2->execute(); $cr2 = 0; $rs2 = $chk2->get_result(); if ($rs2 && ($rw2 = $rs2->fetch_assoc())) { $cr2 = (int)$rw2['c']; }
if ($cr2 === 0) { $koneksi->query("ALTER TABLE `barang_masuk` ADD COLUMN `user_id` INT DEFAULT NULL"); $koneksi->query("ALTER TABLE `barang_masuk` ADD INDEX `idx_user` (`user_id`)"); }

$chk3 = $koneksi->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'barang_keluar' AND column_name = 'user_id'");
$chk3->execute(); $cr3 = 0; $rs3 = $chk3->get_result(); if ($rs3 && ($rw3 = $rs3->fetch_assoc())) { $cr3 = (int)$rw3['c']; }
if ($cr3 === 0) { $koneksi->query("ALTER TABLE `barang_keluar` ADD COLUMN `user_id` INT DEFAULT NULL"); $koneksi->query("ALTER TABLE `barang_keluar` ADD INDEX `idx_user` (`user_id`)"); }

// Memuat library Dompdf
require_once 'libs/dompdf/autoload.inc.php'; 
use Dompdf\Dompdf;
use Dompdf\Options;

// Ambil parameter
$report = isset($_GET['report']) ? strtolower(trim($_GET['report'])) : 'stok';
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$htmlTitle = 'LOGISTIFY - LAPORAN DATA BARANG';
$subTitle = '';
$result = null;
if ($report === 'stok') {
  $filter_stok = isset($_GET['min_stok']) ? (int)$_GET['min_stok'] : 0;
$stmt = $koneksi->prepare("SELECT * FROM barang WHERE user_id = ? AND stok >= ? ORDER BY nama_barang ASC");
$uid = (int)($_SESSION['user_id'] ?? 0);
$stmt->bind_param("ii", $uid, $filter_stok);
  $stmt->execute();
  $result = $stmt->get_result();
  $htmlTitle = 'LOGISTIFY - LAPORAN DATA BARANG';

  // Format Tanggal Cetak (ID)
  $bulanID = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  $subTitle = 'Tanggal Cetak: ' . date('d') . ' ' . $bulanID[(int)date('n')-1] . ' ' . date('Y');
} elseif ($report === 'masuk') {
  // Pastikan tabel ada
  $koneksi->query("CREATE TABLE IF NOT EXISTS barang_masuk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_masuk DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nama_barang VARCHAR(255) NOT NULL,
    kode_barang VARCHAR(100) NOT NULL,
    jumlah_masuk INT NOT NULL,
    satuan VARCHAR(50) DEFAULT NULL,
    supplier VARCHAR(255) DEFAULT NULL,
    lokasi VARCHAR(255) DEFAULT NULL,
    dokumen VARCHAR(255) DEFAULT NULL,
    foto_barang VARCHAR(255) DEFAULT NULL,
    keterangan TEXT DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $stmt = $koneksi->prepare("SELECT tanggal_masuk, nama_barang, kode_barang, jumlah_masuk, satuan, supplier, lokasi, dokumen, foto_barang, keterangan FROM barang_masuk WHERE user_id = ? AND YEAR(tanggal_masuk) = ? ORDER BY tanggal_masuk DESC, id DESC");
  $uid = (int)($_SESSION['user_id'] ?? 0);
  $stmt->bind_param("ii", $uid, $year);
  $stmt->execute();
  $result = $stmt->get_result();

  $htmlTitle = 'LOGISTIFY - LAPORAN BARANG MASUK';
  $subTitle = 'Periode: Januari - Desember ' . $year;
} elseif ($report === 'keluar') {
  $koneksi->query("CREATE TABLE IF NOT EXISTS barang_keluar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_keluar DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nama_barang VARCHAR(255) NOT NULL,
    kode_barang VARCHAR(100) NOT NULL,
    jumlah_keluar INT NOT NULL,
    tujuan VARCHAR(255) NOT NULL,
    dokumen VARCHAR(255) DEFAULT NULL,
    keterangan TEXT DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $stmt = $koneksi->prepare("SELECT tanggal_keluar, nama_barang, kode_barang, jumlah_keluar, tujuan, dokumen, keterangan FROM barang_keluar WHERE user_id = ? AND YEAR(tanggal_keluar) = ? ORDER BY tanggal_keluar DESC, id DESC");
  $uid = (int)($_SESSION['user_id'] ?? 0);
  $stmt->bind_param("ii", $uid, $year);
  $stmt->execute();
  $result = $stmt->get_result();

  $htmlTitle = 'LOGISTIFY - LAPORAN BARANG KELUAR';
  $subTitle = 'Periode: Januari - Desember ' . $year;
} else {
  $stmt = $koneksi->prepare("SELECT * FROM barang WHERE user_id = ? ORDER BY nama_barang ASC");
  $uid = (int)($_SESSION['user_id'] ?? 0);
  $stmt->bind_param('i',$uid);
  $stmt->execute();
  $result = $stmt->get_result();
}

// 2. Membuat struktur HTML laporan
$html = '
<!DOCTYPE html>
<html>
<head>
    <title>' . htmlspecialchars($htmlTitle) . '</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h1 { text-align: center; margin-bottom: 4px; }
        .meta { text-align: center; margin: 0 0 12px; font-size: 12px; color: #333; }
        .foto-cell { width: 90px; }
        .foto-cell img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #ccc; }
        .text-muted { color: #777; font-size: 12px; }
    </style>
</head>
<body>
    <h1>' . htmlspecialchars($htmlTitle) . '</h1>
    <div class="meta">' . htmlspecialchars($subTitle) . '</div>
    <table>
        <thead>
            <tr>
';

// Header tabel berdasarkan jenis laporan
if ($report === 'stok') {
  $html .= '
                <th>No</th>
                <th>Nama Barang</th>
                <th>Kode Barang</th>
                <th>Kategori</th>
                <th>Satuan</th>
                <th>Stok Saat Ini</th>
                <th>Harga</th>
                <th>Lokasi</th>
                <th>Supplier</th>
                <th>Foto</th>
  ';
} elseif ($report === 'masuk') {
  $html .= '
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Barang</th>
                <th>Kode Barang</th>
                <th>Jumlah Masuk</th>
                <th>Satuan</th>
                <th>Supplier</th>
                <th>Lokasi</th>
                <th>Penyimpanan</th>
                <th>Dokumen Pendukung</th>
                <th>Foto</th>
                <th>Keterangan</th>
  ';
} else { // keluar
  $html .= '
                <th>No</th>
                <th>Nama Barang</th>
                <th>Kode Barang</th>
                <th>Jumlah Keluar</th>
                <th>Tujuan/Penerima</th>
                <th>Dokumen/Bukti</th>
                <th>Keterangan</th>
  ';
}

$html .= '
            </tr>
        </thead>
        <tbody>';

// Isi baris
$no = 1;
while($row = $result->fetch_assoc()) {
    $html .= '<tr>';
    $html .= '<td>' . $no++ . '</td>';

    if ($report === 'stok') {
        $foto = isset($row['foto_barang']) ? trim($row['foto_barang']) : '';
        $fotoPathRel = $foto !== '' ? 'uplouds/' . $foto : '';
        $fotoExists = ($fotoPathRel !== '' && file_exists(__DIR__ . '/' . $fotoPathRel));

        $html .= '<td>' . htmlspecialchars($row['nama_barang']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['kode_barang'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['kategori'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['satuan'] ?? '') . '</td>';
        $html .= '<td>' . (int)$row['stok'] . '</td>';
        $html .= '<td>Rp ' . number_format((float)($row['harga'] ?? 0), 0, ',', '.') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['lokasi'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['supplier'] ?? '') . '</td>';
        $html .= '<td class="foto-cell">' . ($fotoExists ? '<img src="' . htmlspecialchars($fotoPathRel) . '" alt="Foto">' : '<span class="text-muted">-</span>') . '</td>';

    } elseif ($report === 'masuk') {
        $doc = isset($row['dokumen']) ? trim($row['dokumen']) : '';
        $docRel = $doc !== '' ? 'uplouds/' . $doc : '';
        $docExists = ($docRel !== '' && file_exists(__DIR__ . '/' . $docRel));
        $foto = isset($row['foto_barang']) ? trim($row['foto_barang']) : '';
        $fotoRel = $foto !== '' ? 'uplouds/' . $foto : '';
        $fotoExists = ($fotoRel !== '' && file_exists(__DIR__ . '/' . $fotoRel));
        $lokasi = htmlspecialchars($row['lokasi'] ?? '');

        $html .= '<td>' . htmlspecialchars($row['tanggal_masuk']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['nama_barang']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['kode_barang']) . '</td>';
        $html .= '<td>' . (int)$row['jumlah_masuk'] . '</td>';
        $html .= '<td>' . htmlspecialchars($row['satuan'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['supplier'] ?? '') . '</td>';
        $html .= '<td>' . $lokasi . '</td>';
        $html .= '<td>' . $lokasi . '</td>'; // Penyimpanan (gunakan data lokasi yang sama)
        $html .= '<td>' . ($docExists ? '<a href="' . htmlspecialchars($docRel) . '" target="_blank">Lihat</a>' : '-') . '</td>';
        $html .= '<td class="foto-cell">' . ($fotoExists ? '<img src="' . htmlspecialchars($fotoRel) . '" alt="Foto">' : '<span class="text-muted">-</span>') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['keterangan'] ?? '') . '</td>';

    } else { // keluar
        $doc = isset($row['dokumen']) ? trim($row['dokumen']) : '';
        $docRel = $doc !== '' ? 'uplouds/' . $doc : '';
        $docExists = ($docRel !== '' && file_exists(__DIR__ . '/' . $docRel));

        $html .= '<td>' . htmlspecialchars($row['nama_barang']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['kode_barang']) . '</td>';
        $html .= '<td>' . (int)$row['jumlah_keluar'] . '</td>';
        $html .= '<td>' . htmlspecialchars($row['tujuan'] ?? '') . '</td>';
        $html .= '<td>' . ($docExists ? '<a href="' . htmlspecialchars($docRel) . '" target="_blank">Lihat</a>' : '-') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['keterangan'] ?? '') . '</td>';
    }

    $html .= '</tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

// 3. Mengatur Dompdf
$options = new Options();
$options->set('defaultFont', 'Helvetica'); // font default untuk teks
// Parser HTML5 dimatikan untuk menghindari dependency tambahan jika tidak tersedia
$options->set('isHtml5ParserEnabled', false);
// Batasi akses file ke root proyek untuk keamanan dan konsistensi path relatif
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

$prefix = ($report === 'stok' ? 'laporan_stok_' : ($report === 'masuk' ? 'laporan_masuk_' : 'laporan_keluar_'));
$filename = $prefix . $count . '_' . $dateLabel . '.pdf';
$dompdf->stream($filename, array("Attachment" => true)); // **REPORTING PDF**

exit;
?>
