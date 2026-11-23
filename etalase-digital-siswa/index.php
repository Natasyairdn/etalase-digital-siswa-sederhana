<?php
// =====================================================================
// FILE: index.php
// FUNGSI: Halaman Utama (Etalase Karya). Menampilkan galeri karya semua siswa.
// Semua role yang sudah login boleh membuka. Belum login -> ke halaman login.
// =====================================================================
require_once __DIR__ . '/middleware/auth_check.php';
wajib_login();                                   // cek sesi login
$u = current_user();

// Judul menyesuaikan role: siswa -> "Dashboard Siswa", admin yang membuka -> "Galeri Karya"
$pageTitle = $u['role'] === 'siswa' ? 'Dashboard Siswa' : 'Galeri Karya';
$showNav = true;                                 // tampilkan navbar
include __DIR__ . '/partials/header.php';
?>
<main class="page">
    <?php include __DIR__ . '/partials/galeri.php'; // kolom cari, kartu karya, pop up detail ?>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
