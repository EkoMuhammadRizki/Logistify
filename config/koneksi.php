<?php
// Konfigurasi koneksi database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Sesuaikan dengan username DB Anda
define('DB_PASSWORD', '');     // Sesuaikan dengan password DB Anda
define('DB_NAME', 'aplikasi_manajemen'); // Sesuaikan dengan nama DB Anda

// Membuat koneksi
$koneksi = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
?>