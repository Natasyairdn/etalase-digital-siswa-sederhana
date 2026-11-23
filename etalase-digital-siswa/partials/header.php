<?php
// =====================================================================
// FILE: partials/header.php
// FUNGSI: bagian ATAS halaman (head HTML + navbar), dipakai bersama.
// Variabel yang diisi halaman sebelum include file ini:
//   $pageTitle = judul tab browser
//   $showNav   = true kalau navbar mau ditampilkan (false di login/daftar)
//   $bodyClass = (opsional) kelas CSS tambahan untuk <body>
// =====================================================================
$u = current_user();                                // data user yang login (null kalau belum)
$flash = flash();                                   // pesan sekali tampil, kalau ada
$aktifUsers = basename($_SERVER['SCRIPT_NAME']) === 'users.php';   // sedang di halaman Kelola Siswa?
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<!-- viewport: supaya tampilan menyesuaikan lebar layar HP -->
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Judul tab browser, contoh: "Login | Etalase Digital Siswa" -->
<title><?= e($pageTitle ?? 'Etalase Digital Siswa') ?> | Etalase Digital Stella</title>
<!-- Ikon kecil di tab browser (favicon) = logo sekolah -->
<link rel="icon" href="<?= e(asset('img/logo.png')) ?>">
<!-- Font DM Sans dari Google Fonts. Kalau komputer offline, otomatis pakai font bawaan sistem. -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<?php if (!empty($showNav) && $u): ?>
<!-- NAVBAR: hanya tampil kalau $showNav true dan user sudah login -->
<header class="topbar">
    <div class="topbar-inner">
        <!-- Logo + nama aplikasi (klik = ke halaman utama sesuai role) -->
        <a class="brand" href="<?= e(url(home_for_role($u['role']))) ?>">
            <span class="brand-badge"><img src="<?= e(asset('img/logo.png')) ?>" alt="Logo" class="brand-logo"></span>
            <!-- Nama aplikasi yang ditampilkan pada navbar. -->
            <span>Etalase Digital Siswa</span>
        </a>
        <?php if (($bodyClass ?? '') === 'project-form-page'): ?>
            <nav class="menu"></nav>
        <?php else: ?>
            <nav class="menu">
                <a href="<?= e(url(home_for_role($u['role']))) ?>" class="<?= $aktifUsers ? '' : 'active' ?>">Galeri Karya</a>
                <?php if ($u['role'] === 'super_admin'): ?>
                    <a href="<?= e(url('admin/users.php')) ?>" class="<?= $aktifUsers ? 'active' : '' ?>">Kelola Siswa</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
        <div class="userbox">
            <!-- Nama user yang sedang login -->
            <?php if (($bodyClass ?? '') === 'project-form-page'): ?>
                <a class="btn btn-outline btn-sm" href="<?= e(url(home_for_role($u['role']))) ?>">Kembali ke etalase</a>
            <?php else: ?>
                <span class="username"><?= e($u['nama']) ?></span>
            <?php endif; ?>
            <!-- Logout memakai form POST + token CSRF supaya tidak bisa dipicu dari link luar -->
            <form method="post" action="<?= e(url('auth/logout.php')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline btn-sm">Logout</button>
            </form>
        </div>
    </div>
</header>
<?php endif; ?>
<?php if ($flash): ?>
    <!-- Pesan sukses/gagal (hijau/merah sesuai $flash['type']) -->
    <div class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['msg']) ?></div>
<?php endif; ?>
