<?php
require_once 'config/koneksi.php';
require_once 'functions/auth.php';
require_login($koneksi);

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

// Cek apakah request datang dari **POST** (untuk Create/Update)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // --- CREATE (Tambah Data) ---
    if ($action == 'tambah') {
        $upload_result = handle_upload($_FILES['foto_barang']); // **UPLOUD FILE**
        
        if ($upload_result['status'] == 'error') {
            die("Error Upload: " . $upload_result['message']);
        }

        $harga = normalize_price($_POST['harga'] ?? '');
        $stmt = $koneksi->prepare("INSERT INTO barang (nama_barang, deskripsi, stok, harga, foto_barang) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssids", $_POST['nama_barang'], $_POST['deskripsi'], $_POST['stok'], $harga, $upload_result['filename']);
        $stmt->execute();
        
        header('Location: dashboard.php?status=tambah_sukses');

    // --- UPDATE (Edit Data) ---
    } elseif ($action == 'edit') {
        $foto_lama = $_POST['foto_lama'];
        $upload_result = handle_upload($_FILES['foto_barang'], $foto_lama); // **UPLOUD FILE**

        if ($upload_result['status'] == 'error') {
            die("Error Upload: " . $upload_result['message']);
        }
        $foto_baru = $upload_result['filename'];

        $harga = normalize_price($_POST['harga'] ?? '');
        $stmt = $koneksi->prepare("UPDATE barang SET nama_barang=?, deskripsi=?, stok=?, harga=?, foto_barang=? WHERE id=?");
        $stmt->bind_param("ssidsi", $_POST['nama_barang'], $_POST['deskripsi'], $_POST['stok'], $harga, $foto_baru, $_POST['id']);
        $stmt->execute();

        header('Location: dashboard.php?status=edit_sukses');
    }
    exit;
} 
// Cek apakah request datang dari **AJAX** (untuk Delete)
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    // Diproses oleh AJAX
    header('Content-Type: application/json');
    $id = $_POST['delete_id'];
    
    // Ambil nama file foto lama sebelum dihapus
    $stmt_file = $koneksi->prepare("SELECT foto_barang FROM barang WHERE id = ?");
    $stmt_file->bind_param("i", $id);
    $stmt_file->execute();
    $result_file = $stmt_file->get_result();
    $row_file = $result_file->fetch_assoc();
    
    if ($row_file && $row_file['foto_barang']) {
        @unlink(__DIR__ . DIRECTORY_SEPARATOR . 'uplouds' . DIRECTORY_SEPARATOR . $row_file['foto_barang']); // Hapus file fisik
    }

    $stmt = $koneksi->prepare("DELETE FROM barang WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data.']);
    }
    exit;
}

// Jika request tidak valid (tidak POST), redirect ke dashboard
header('Location: dashboard.php');
exit;
?>