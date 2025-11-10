# Logistify — Aplikasi Manajemen Stok (PHP)

Logistify adalah aplikasi web sederhana untuk mencatat data barang, mengelola stok, dan membuat laporan PDF. Proyek ini dibangun dengan PHP + MySQL, menggunakan Bootstrap untuk antarmuka, jQuery + SweetAlert2 untuk interaksi, serta Dompdf untuk generasi PDF.

## Fitur Utama

- CRUD Barang
  - Create/Update dengan upload foto barang (`data_form.php` → `proses_data.php`).
  - Read daftar barang di tabel (`dashboard.php`).
  - Delete menggunakan AJAX jQuery (`assets/js/custom.js` → `proses_data.php`).
- Login & Register
  - Register user baru dengan `password_hash` (`register.php`).
  - Login dengan verifikasi `password_verify` dan opsi Remember Me (`login.php`).
- Session & Cookie
  - `$_SESSION['user_id']` untuk menjaga sesi pengguna.
  - Cookie `remember_me` (token) untuk login otomatis, divalidasi di `functions/auth.php`.
- Upload File
  - Upload foto barang (JPG/PNG, maks 10MB) ke folder `uplouds/` (`proses_data.php`).
- Reporting (PDF)
  - Unduh laporan data barang sebagai PDF melalui Dompdf (`generate_laporan.php`).
- Bootstrap
  - UI menggunakan Bootstrap 5 + Bootstrap Icons. SweetAlert2 memakai tema Bootstrap.

## Fitur Terbaru (Belum Terdokumentasi Sebelumnya)

- Loading Bar Overlay (Transisi Login → Dashboard)
  - Bar hijau transparan sebagai indikator perpindahan halaman (`assets/js/loading-bar.js`, `assets/css/loading-bar.css`).
  - Mengurangi kesan “blank” saat autentikasi selesai dan dashboard dimuat.
- Splash Screen Landing
  - Intro singkat saat halaman `index.php` dibuka (`assets/js/splash.js`, `assets/css/splash.css`).
  - Menampilkan logo/brand sebelum konten utama ditampilkan.
- Peningkatan UI Dashboard
  - Interaksi dan helper UI terpisah di `assets/js/dashboard-ui.js` untuk menjaga kode modular.
- Validasi ID & Popup
  - Validasi format ID/form khusus di `assets/js/validation-id.js`.
  - Validasi berbasis popup (SweetAlert2) di `assets/js/validation-popup.js` dengan pesan konsisten.
- Pelacakan Counter Laporan
  - File counter per hari di `uplouds/` (misal `laporan_count_YYYY-MM-DD.txt`) untuk menandai jumlah unduhan/generasi laporan.
- Manifest Web
  - `manifest.webmanifest` untuk metadata aplikasi web (ikon, nama, dan pengaturan display) agar lebih siap dipakai sebagai PWA ringan.

## Struktur Proyek (ringkas)

- `config/koneksi.php` — Konfigurasi koneksi database MySQL.
- `functions/auth.php` — Utilitas autentikasi: cek login via Session/Cookie dan guard `require_login`.
- `index.php` — Halaman landing menggunakan Bootstrap.
- `register.php` — Form pendaftaran pengguna baru.
- `login.php` — Form login (Remember Me opsional).
- `dashboard.php` — Daftar barang (Read), tombol tambah, edit, hapus (AJAX), unduh PDF.
- `data_form.php` — Form tambah/edit barang (Create/Update) + input harga format Rupiah.
- `proses_data.php` — Handler CRUD: Create/Update (POST), Delete (AJAX), termasuk upload file.
- `generate_laporan.php` — Rendering laporan PDF memakai Dompdf.
- `assets/js/custom.js` — AJAX delete dengan jQuery dan SweetAlert2.
- `assets/js/loading-bar.js` — Mengelola overlay progress saat transisi halaman.
- `assets/js/splash.js` — Menangani splash/intro di landing.
- `assets/js/dashboard-ui.js` — Utilitas UI khusus dashboard.
- `assets/js/validation-id.js` — Validasi ID/form sesuai aturan bisnis.
- `assets/js/validation-popup.js` — Validasi dengan feedback popup yang konsisten.
- `assets/css/*.css` — Styling landing, dashboard, auth, dan loader.
- `uplouds/` — Menyimpan file upload (foto barang) & counter nama file laporan.
- `libs/dompdf/` — Vendor Dompdf untuk generasi PDF.

## Persiapan & Instalasi

1. Siapkan XAMPP (Apache + MySQL) di Windows dan pastikan `htdocs` aktif.
2. Salin folder proyek ini ke `d:\xampp\htdocs\aplikasi-manajemen`.
3. Buat database MySQL dengan nama `aplikasi_manajemen`.
4. Buat tabel yang dibutuhkan:

```sql
-- Tabel users untuk autentikasi
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  token_cookie VARCHAR(255) NULL
);

-- Tabel barang untuk data stok
CREATE TABLE barang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_barang VARCHAR(120) NOT NULL,
  deskripsi TEXT NULL,
  stok INT NOT NULL DEFAULT 0,
  harga DECIMAL(18,2) NOT NULL DEFAULT 0,
  foto_barang VARCHAR(255) NULL
);
```

5. Pastikan kredensial database sudah sesuai di `config/koneksi.php`.

## Cara Menjalankan

- Buka `http://localhost/aplikasi-manajemen/index.php`.
- Register akun baru di `register.php` lalu login di `login.php`.
- Kelola data barang dari `dashboard.php`:
  - Tambah/Edit barang melalui `data_form.php`.
  - Hapus barang dengan tombol “Hapus (AJAX)”.
  - Unduh laporan PDF melalui tombol “Download Laporan (PDF)”.

## Penjelasan Teknis per Fitur

- CRUD
  - Create/Update: form `data_form.php` (POST) → `proses_data.php` memakai prepared statements.
  - Read: query `SELECT * FROM barang` di `dashboard.php` menampilkan tabel dengan Bootstrap.
  - Delete: tombol dengan class `.delete-btn` (dashboard) memanggil AJAX di `assets/js/custom.js` → `proses_data.php` menghapus data dan mengembalikan JSON.
- Login & Register
  - Register: validasi sederhana + `password_hash` sebelum `INSERT` ke `users`.
  - Login: cek `username`, verifikasi `password_verify`; set `$_SESSION['user_id']`; jika Remember Me diaktifkan, generate token acak, simpan ke DB (`users.token_cookie`), set cookie `remember_me`.
- Session & Cookie
  - `functions/auth.php::is_logged_in($koneksi)`: cek `$_SESSION['user_id']` terlebih dulu, jika tidak ada cek `$_COOKIE['remember_me']` dan validasi ke DB; jika valid, buat session baru.
  - `functions/auth.php::require_login($koneksi)`: redirect ke `login.php` jika belum login.
- Upload File
  - `proses_data.php::handle_upload($file_array, $foto_lama = null)`: memvalidasi tipe file (jpg/png/jpeg) dan ukuran, menyimpan ke folder `uplouds/`, menghapus file lama saat edit.
- Reporting PDF
  - `generate_laporan.php`: ambil data dari DB (opsional filter `min_stok` via GET), buat HTML, render dengan Dompdf, dan kirim ke browser sebagai file download. Akses file lokal difokuskan dengan `chroot` ke direktori proyek.
- Bootstrap & Interaksi
  - Sebagian besar halaman menyertakan Bootstrap 5 & Icons via CDN.
  - SweetAlert2 dipakai untuk konfirmasi dan pesan sukses/gagal.
  - Loader (loading bar overlay) saat login/masuk dashboard.
  - Splash (intro singkat) di landing untuk pengalaman visual yang lebih halus.

## Catatan Komentar di Kode

- Setiap file inti kini memiliki komentar penjelas di bagian atas yang menjabarkan:
  - Fitur yang diimplementasikan oleh file tersebut.
  - Fungsi utama di dalam file dan alur eksekusinya.
- Pada blok-blok penting (AJAX, validasi, render PDF, autentikasi), ditambahkan komentar inline untuk menjelaskan apa yang terjadi dan alasan desainnya.

## Catatan Keamanan & Praktik Baik

- Selalu pakai prepared statements untuk query DB (sudah diterapkan di file-file inti).
- Jangan simpan password dalam plaintext — gunakan `password_hash`/`password_verify` (sudah diterapkan).
- Token Remember Me disimpan di DB; saat logout, token & cookie dihapus.
- Direktori upload bernama `uplouds/` mengikuti struktur proyek (ejaan tetap disesuaikan dengan yang ada di repo).

## Perbaikan Lanjutan (Opsional)

- Tambahkan CSRF token pada form penting.
- Validasi sisi server lebih ketat untuk field numerik/harga.
- Batasi ukuran dan resolusi gambar yang diunggah.
- Pagination pada dashboard untuk jumlah data besar.
- Tambahkan service worker untuk cache aset statis jika ingin PWA penuh.

## Lisensi

Proyek ini menggunakan Dompdf (LGPL). Konten aplikasi contoh bebas digunakan untuk pembelajaran/internal.