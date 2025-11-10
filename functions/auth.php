<?php
// auth.php - Fungsi-fungsi terkait autentikasi
// Mencakup: pemeriksaan login (SESSION/COOKIE) dan guard redirect.

// Mulai Session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Fungsi untuk memeriksa status login pengguna (SESSION dan COOKIE)
 * - Mengembalikan true jika user terautentikasi.
 * - Prioritas: cek SESSION dahulu, lalu COOKIE "remember_me".
 * - Jika COOKIE valid, buat SESSION baru agar sesi konsisten.
 */
function is_logged_in($koneksi) {
    // Cek Session
    if (isset($_SESSION['user_id'])) {
        return true;
    }

    // Cek Cookie "Remember Me"
    if (isset($_COOKIE['remember_me'])) {
        $token = $_COOKIE['remember_me'];
        
        // Cari token di database
        $stmt = $koneksi->prepare("SELECT id FROM users WHERE token_cookie = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Cookie valid, buat session baru untuk menjaga konsistensi state
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
    }

    return false;
}

/**
 * Fungsi untuk mengarahkan pengguna ke halaman login jika belum login
 * Dipakai di halaman yang butuh autentikasi (dashboard, form data, laporan).
 */
function require_login($koneksi) {
    if (!is_logged_in($koneksi)) {
        header('Location: login.php');
        exit;
    }
}
?>