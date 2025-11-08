<?php
require_once 'functions/auth.php'; // Memastikan session_start() sudah terpanggil

// Hapus Session
$_SESSION = array(); // Kosongkan array session
session_destroy(); // Hancurkan session

// Hapus Cookie "Remember Me" (jika ada)
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, "/"); // Set waktu kedaluwarsa ke masa lalu
}

header('Location: login.php');
exit;
?>