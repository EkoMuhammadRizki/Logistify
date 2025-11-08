<?php
// auth.php - Fungsi-fungsi terkait autentikasi

// Mulai Session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Fungsi untuk memeriksa status login pengguna (SESSION dan COOKIE)
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
            // Cookie valid, buat session baru
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
    }

    return false;
}

/**
 * Fungsi untuk mengarahkan pengguna ke halaman login jika belum login
 */
function require_login($koneksi) {
    if (!is_logged_in($koneksi)) {
        header('Location: login.php');
        exit;
    }
}
?>