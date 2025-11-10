<?php
require_once 'config/koneksi.php';
require_once 'functions/auth.php';

if (is_logged_in($koneksi)) {
    header('Location: dashboard.php'); // Jika sudah login, redirect ke dashboard
    exit;
}

$prefill_username = '';
$remember_checked = false;
// Prefill username jika ada cookie remember_me
if (isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $stmt_token = $koneksi->prepare("SELECT username FROM users WHERE token_cookie = ?");
    $stmt_token->bind_param("s", $token);
    $stmt_token->execute();
    $result_token = $stmt_token->get_result();
    if ($row_token = $result_token->fetch_assoc()) {
        $prefill_username = $row_token['username'];
        $remember_checked = true;
    }
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember_me']); // Cookie

    // **GET/POST:** Mengambil data login menggunakan POST
    // Proses autentikasi:
    // - Ambil hash password dari DB menggunakan prepared statement.
    // - Verifikasi menggunakan password_verify.
    // - Jika sukses: set SESSION dan (opsional) COOKIE "remember_me".

    $stmt = $koneksi->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Login Berhasil!
        $_SESSION['user_id'] = $user['id']; // **SESSION**
        
        if ($remember) {
            $token = bin2hex(random_bytes(32)); 
            // Simpan token ke database
            $stmt_token = $koneksi->prepare("UPDATE users SET token_cookie = ? WHERE id = ?");
            $stmt_token->bind_param("si", $token, $user['id']);
            $stmt_token->execute();
            // Set Cookie
            setcookie('remember_me', $token, time() + (86400 * 30), "/"); // **COOKIE** 30 hari
        } else {
            // Hapus cookie dan token jika sebelumnya pernah di-set
            if (isset($_COOKIE['remember_me'])) {
                setcookie('remember_me', '', time() - 3600, "/");
            }
            $stmt_clear = $koneksi->prepare("UPDATE users SET token_cookie = NULL WHERE id = ?");
            $stmt_clear->bind_param("i", $user['id']);
            $stmt_clear->execute();
        }
        
        header('Location: dashboard.php?status=login_sukses');
        exit;
    } else {
        $error_message = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Login</title>
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
    <div class="loader-wrapper" style="display:none">
        <img src="assets/media/fav-icon.png" alt="Loading..." class="loader-logo">
        <div class="loader-text">Logistify</div>
        <div class="progress-container">
          <div class="progress-percent">0%</div>
          <div class="progress-track">
            <div class="progress-bar"></div>
          </div>
        </div>
    </div>
    <div class="brand-bar">
      <div class="logo-dummy"><img src="assets/media/logistify.png" alt="Logo Logistify"></div>
      <div class="site-title">Logistify</div>
    </div>
    <div class="auth-container">
      <div class="auth-box">
        <div class="d-flex justify-content-end mb-2">
          <a href="index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-house"></i> Halaman Utama</a>
        </div>
        <h1 class="auth-title">Login</h1>
        <?php if ($error_message): ?><div class="alert-inline"><?= $error_message; ?></div><?php endif; ?>
        <form method="POST" autocomplete="on" class="needs-validation" novalidate>
          <div class="input-wrap">
            <span class="input-icon"><i class="bi bi-person"></i></span>
            <input type="text" name="username" placeholder="Username" required value="<?= htmlspecialchars($prefill_username); ?>">
          </div>
          <div class="input-wrap">
            <span class="input-icon"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="loginPassword" placeholder="Password" required autocomplete="current-password">
            <button type="button" class="toggle-eye" id="toggleLoginPassword" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
          </div>
          <div class="mb-2 form-check">
            <input type="checkbox" name="remember_me" class="form-check-input" id="rememberMe" <?= $remember_checked ? 'checked' : ''; ?>>
            <label class="form-check-label" for="rememberMe">Ingat Saya</label>
          </div>
          <button type="submit" class="btn-auth">LOGIN</button>
          <p class="auth-subtext">Belum punya akun? <a href="register.php">Daftar</a></p>
        </form>
      </div>
    </div>
    <script src="assets/js/validation-popup.js"></script>
</body>
<?php if (isset($_GET['status']) && $_GET['status'] === 'register_sukses'): ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
      title: 'Selamat!',
      text: 'Kamu berhasil register. Silakan login untuk masuk.',
      icon: 'success',
      confirmButtonText: 'OK',
      customClass: { confirmButton: 'btn btn-success' },
      buttonsStyling: false
    });
  });
</script>
<?php endif; ?>
<script>
(function() {
    const loader = document.querySelector('.loader-wrapper');
    const percentEl = document.querySelector('.progress-percent');
    const barEl = document.querySelector('.progress-bar');
    const urlParams = new URLSearchParams(window.location.search);

    function runProgress(onDone) {
      let p = 0;
      percentEl.textContent = '0%';
      barEl.style.width = '0%';
      const step = setInterval(() => {
        p = Math.min(100, p + Math.floor(Math.random() * 8) + 2);
        percentEl.textContent = p + '%';
        barEl.style.width = p + '%';
        if (p >= 100) {
          clearInterval(step);
          if (typeof onDone === 'function') onDone();
        }
      }, 120);
    }

    if (urlParams.get('status') === 'loading') {
        loader.classList.remove('hidden');
        runProgress(() => {
          loader.classList.add('hidden');
          setTimeout(() => {
            Swal.fire({
              title: 'Selamat!',
              text: 'Registrasi berhasil. Silakan login.',
              icon: 'success',
              confirmButtonText: 'OK',
              customClass: { confirmButton: 'btn btn-success' },
              buttonsStyling: false
            });
            window.history.replaceState({}, document.title, window.location.pathname);
          }, 400);
        });
    } else {
        loader.classList.add('hidden');
    }

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
    setupToggle('toggleLoginPassword', 'loginPassword');
})();
</script>
</html>