<?php
require_once 'config/koneksi.php';
require_once 'functions/auth.php'; // Digunakan untuk fungsi session/redirect, meskipun tidak wajib di register

if (is_logged_in($koneksi)) {
    header('Location: dashboard.php'); // Jika sudah login, redirect ke dashboard
    exit;
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    if (empty($username) || empty($email) || empty($password) || empty($konfirmasi_password)) {
        $error_message = "Semua kolom wajib diisi.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password harus minimal 8 karakter.";
    } elseif ($password !== $konfirmasi_password) {
        $error_message = "Konfirmasi password tidak cocok.";
    } else {
        // 1. Cek apakah username atau email sudah terdaftar (prepared statement untuk keamanan)
        $stmt_check = $koneksi->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error_message = "Username atau Email sudah terdaftar.";
        } else {
            // 2. Hash Password (PENTING!)
            // Gunakan password_hash agar password tersimpan aman di DB.
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // 3. Query INSERT INTO users (CRUD: CREATE)
            $stmt = $koneksi->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $password_hashed, $email);
            
            if ($stmt->execute()) {
                // Pendaftaran berhasil
                header('Location: login.php?status=register_sukses');
                exit;
            } else {
                $error_message = "Pendaftaran gagal. Silakan coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Register Akun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4@5/bootstrap-4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="assets/css/landing.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <link href="assets/css/auth.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/media/fav-icon.png">
    <link rel="shortcut icon" href="assets/media/fav-icon.png">
    <link rel="apple-touch-icon" href="assets/media/fav-icon.png">
    <link rel="manifest" href="manifest.webmanifest">
    <meta name="theme-color" content="#28a745">
</head>
<body>
    <div class="brand-bar">
      <div class="logo-dummy"><img src="assets/media/logistify.png" alt="Logo Logistify"></div>
      <div class="site-title">Logistify</div>
    </div>
  <div class="auth-container">
    <div class="auth-box">
      <div class="d-flex justify-content-end mb-2">
        <a href="index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-house"></i> Halaman Utama</a>
      </div>
      <h1 class="auth-title">Register</h1>
      <?php if ($error_message): ?><div class="alert-inline"><?= $error_message; ?></div><?php endif; ?>
        <form method="POST" autocomplete="on" class="needs-validation" novalidate>
          <div class="input-wrap">
            <span class="input-icon"><i class="bi bi-person"></i></span>
            <input type="text" name="username" placeholder="Username" required value="<?= htmlspecialchars($username ?? ''); ?>">
          </div>
          <div class="input-wrap">
            <span class="input-icon"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($email ?? ''); ?>">
          </div>
          <div class="input-wrap">
            <span class="input-icon"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="registerPassword" placeholder="Password" required autocomplete="new-password" minlength="8">
            <button type="button" class="toggle-eye" id="toggleRegisterPassword" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
          </div>
          <div class="input-wrap">
            <span class="input-icon"><i class="bi bi-lock"></i></span>
            <input type="password" name="konfirmasi_password" id="registerPasswordConfirm" placeholder="Konfirmasi Password" required autocomplete="new-password" minlength="8">
            <button type="button" class="toggle-eye" id="toggleRegisterPasswordConfirm" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
          </div>
          <button type="submit" class="btn-auth">DAFTAR</button>
          <p class="auth-subtext">Sudah punya akun? <a href="login.php">Login</a></p>
        </form>
      </div>
    </div>
    <script src="assets/js/validation-popup.js"></script>
</body>
<script>
(function() {
  function setupToggle(btnId, inputId) {
    var btn = document.getElementById(btnId);
    var input = document.getElementById(inputId);
    if (!btn || !input) return;
    btn.addEventListener('click', function() {
      var icon = btn.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        if (icon) { icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash'); }
        btn.setAttribute('aria-label', 'Sembunyikan password');
      } else {
        input.type = 'password';
        if (icon) { icon.classList.remove('bi-eye-slash'); icon.classList.add('bi-eye'); }
        btn.setAttribute('aria-label', 'Tampilkan password');
      }
    });
  }
  setupToggle('toggleRegisterPassword', 'registerPassword');
  setupToggle('toggleRegisterPasswordConfirm', 'registerPasswordConfirm');
})();
</script>
</html>
