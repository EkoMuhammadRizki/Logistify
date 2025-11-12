<?php
// proses_data.php
// Menangani CRUD Barang:
// - Create/Update via form POST (dengan upload file foto)
// - Delete via permintaan AJAX (jQuery $.ajax dari assets/js/custom.js)
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);
// Semua endpoint merespon JSON agar mendukung AJAX tanpa reload
header('Content-Type: application/json');

// Normalisasi nilai harga (Rupiah formatted string -> float numeric)
function normalize_price($input) {
    if ($input === null) return 0.0;
    // Contoh: "1.234.567,89" -> "1234567.89"
    $clean = preg_replace('/[^0-9.,]/', '', (string)$input);
    if (strpos($clean, ',') !== false) {
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);
    }
    return (float)$clean;
}
// Fungsi untuk menangani proses Upload File
function handle_upload($file_array, $foto_lama = null) {
    if ($file_array['error'] == UPLOAD_ERR_NO_FILE) {
        return ['status' => 'success', 'filename' => $foto_lama]; // Tidak ada file baru, kembalikan nama lama
    }

    // Gunakan folder 'uplouds' (sesuai struktur proyek) dan buat jika belum ada
    $target_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uplouds' . DIRECTORY_SEPARATOR;
    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0777, true);
    }
    $file_name = uniqid('img_') . basename($file_array["name"]); // Ganti nama file
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validasi file
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        return ['status' => 'error', 'message' => "Hanya file JPG, JPEG, & PNG yang diizinkan."];
    }
    if ($file_array["size"] > 10000000) { // 10MB
        return ['status' => 'error', 'message' => "Ukuran file terlalu besar."];
    }

    // Pindahkan file ke folder uploads
    if (move_uploaded_file($file_array["tmp_name"], $target_file)) {
        // Hapus file lama jika ada dan bukan file default
        if ($foto_lama && file_exists($target_dir . $foto_lama)) {
            unlink($target_dir . $foto_lama);
        }
        return ['status' => 'success', 'filename' => $file_name];
    } else {
        return ['status' => 'error', 'message' => "Gagal mengunggah file."];
    }
}

// Upload dokumen opsional (pdf/jpg/jpeg/png) untuk master barang
function handle_upload_doc($file_array) {
    if (!isset($file_array) || !is_array($file_array) || ($file_array['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['status' => 'success', 'filename' => null];
    }
    $target_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uplouds' . DIRECTORY_SEPARATOR;
    if (!is_dir($target_dir)) { @mkdir($target_dir, 0777, true); }
    $file_name = uniqid('doc_') . basename($file_array["name"]);
    $target_file = $target_dir . $file_name;
    $ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed = ['pdf','jpg','jpeg','png'];
    if (!in_array($ext, $allowed)) { return ['status'=>'error','message'=>'Dokumen harus pdf/jpg/jpeg/png']; }
    if (($file_array["size"] ?? 0) > 10 * 1024 * 1024) { return ['status'=>'error','message'=>'Ukuran dokumen max 10MB']; }
    if (move_uploaded_file($file_array["tmp_name"], $target_file)) {
        return ['status' => 'success', 'filename' => $file_name];
    }
    return ['status'=>'error','message'=>'Gagal mengunggah dokumen'];
}

// Pastikan kolom tambahan tersedia di tabel barang
function ensure_barang_columns(mysqli $db) {
    $res = $db->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'barang'");
    $cols = [];
    if ($res) { while($r = $res->fetch_assoc()) { $cols[$r['COLUMN_NAME']] = true; } }
    if (!isset($cols['kode_barang'])) { $db->query("ALTER TABLE barang ADD COLUMN kode_barang VARCHAR(100) DEFAULT NULL"); }
    if (!isset($cols['kategori'])) { $db->query("ALTER TABLE barang ADD COLUMN kategori VARCHAR(100) DEFAULT NULL"); }
    if (!isset($cols['satuan'])) { $db->query("ALTER TABLE barang ADD COLUMN satuan VARCHAR(50) DEFAULT NULL"); }
    if (!isset($cols['supplier'])) { $db->query("ALTER TABLE barang ADD COLUMN supplier VARCHAR(255) DEFAULT NULL"); }
    if (!isset($cols['lokasi'])) { $db->query("ALTER TABLE barang ADD COLUMN lokasi VARCHAR(255) DEFAULT NULL"); }
    if (!isset($cols['dokumen'])) { $db->query("ALTER TABLE barang ADD COLUMN dokumen VARCHAR(255) DEFAULT NULL"); }
    // Kolom soft-delete
    if (!isset($cols['is_deleted'])) { $db->query("ALTER TABLE barang ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0"); }
    if (!isset($cols['deleted_at'])) { $db->query("ALTER TABLE barang ADD COLUMN deleted_at DATETIME DEFAULT NULL"); }
}

// Cek apakah request datang dari **POST** (untuk Create/Update)
// CRUD: CREATE & UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    ensure_barang_columns($koneksi);
    
    // --- CREATE (Tambah Data) ---
    if ($action == 'tambah') {
        $upload_foto = handle_upload($_FILES['foto_barang']);
        if ($upload_foto['status'] == 'error') { echo json_encode(['status'=>'error','message'=>$upload_foto['message']]); exit; }
        $upload_doc = handle_upload_doc($_FILES['dokumen'] ?? []);
        if ($upload_doc['status'] == 'error') { echo json_encode(['status'=>'error','message'=>$upload_doc['message']]); exit; }

        $nama = trim($_POST['nama_barang'] ?? '');
        $kode = trim($_POST['kode_barang'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $satuan = trim($_POST['satuan'] ?? '');
        $supplier = trim($_POST['supplier'] ?? '');
        $lokasi = trim($_POST['lokasi'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $stok = (int)($_POST['stok'] ?? 0);
        $harga = normalize_price($_POST['harga'] ?? '');
        if ($nama === '') { echo json_encode(['status'=>'error','message'=>'Nama barang wajib diisi']); exit; }

        $stmt = $koneksi->prepare("INSERT INTO barang (nama_barang, kode_barang, kategori, satuan, supplier, lokasi, deskripsi, stok, harga, foto_barang, dokumen) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $foto = $upload_foto['filename'];
        $doc = $upload_doc['filename'];
        $stmt->bind_param("sssssssidss", $nama, $kode, $kategori, $satuan, $supplier, $lokasi, $deskripsi, $stok, $harga, $foto, $doc);
        if (!$stmt->execute()) { echo json_encode(['status'=>'error','message'=>'Gagal menyimpan: '.$stmt->error]); exit; }
        echo json_encode(['status'=>'success','message'=>'Barang ditambahkan','data'=>['id'=>$stmt->insert_id]]); exit;

    // --- UPDATE (Edit Data) ---
    } elseif ($action == 'edit') {
        $foto_lama = $_POST['foto_lama'] ?? null;
        $upload_foto = handle_upload($_FILES['foto_barang'], $foto_lama);
        if ($upload_foto['status'] == 'error') { echo json_encode(['status'=>'error','message'=>$upload_foto['message']]); exit; }
        $upload_doc = handle_upload_doc($_FILES['dokumen'] ?? []);
        if ($upload_doc['status'] == 'error') { echo json_encode(['status'=>'error','message'=>$upload_doc['message']]); exit; }
        $foto_baru = $upload_foto['filename'];
        $doc_baru = $upload_doc['filename'];

        $nama = trim($_POST['nama_barang'] ?? '');
        $kode = trim($_POST['kode_barang'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $satuan = trim($_POST['satuan'] ?? '');
        $supplier = trim($_POST['supplier'] ?? '');
        $lokasi = trim($_POST['lokasi'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $stok = (int)($_POST['stok'] ?? 0);
        $harga = normalize_price($_POST['harga'] ?? '');
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }

        $stmt = $koneksi->prepare("UPDATE barang SET nama_barang=?, kode_barang=?, kategori=?, satuan=?, supplier=?, lokasi=?, deskripsi=?, stok=?, harga=?, foto_barang=?, dokumen=? WHERE id=?");
        $stmt->bind_param("sssssssidssi", $nama, $kode, $kategori, $satuan, $supplier, $lokasi, $deskripsi, $stok, $harga, $foto_baru, $doc_baru, $id);
        if (!$stmt->execute()) { echo json_encode(['status'=>'error','message'=>'Gagal mengupdate: '.$stmt->error]); exit; }
        echo json_encode(['status'=>'success','message'=>'Barang diperbarui']); exit;
    }
    exit;
} 
// Cek apakah request datang dari **AJAX** (untuk Delete)
// CRUD: DELETE via AJAX
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    // Diproses oleh AJAX
    header('Content-Type: application/json');
    $id = $_POST['delete_id'];
    // Soft-delete: jangan hapus baris; tandai sebagai terhapus dan set stok=0
    // Pastikan kolom soft-delete tersedia
    ensure_barang_columns($koneksi);

    $stmt = $koneksi->prepare("UPDATE barang SET is_deleted = 1, deleted_at = NOW(), stok = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Data dihapus (soft delete).']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data.']);
    }
    exit;
}

// Jika request tidak valid
echo json_encode(['status'=>'error','message'=>'Metode tidak didukung']);
exit;
?>