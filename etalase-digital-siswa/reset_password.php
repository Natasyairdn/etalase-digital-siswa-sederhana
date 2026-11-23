<?php
// =====================================================================
// FILE: reset_password.php
// FUNGSI: Script administrasi untuk MEMBUAT ULANG hash password akun contoh
// dan langsung memperbaruinya di database.
// Cara pakai: buka http://localhost/etalase-digital-stella/reset_password.php
// satu kali di browser, lalu login memakai password baru yang tampil.
// CATATAN: HAPUS file ini setelah dipakai (siapa pun yang membuka alamatnya
// bisa mengganti password semua akun).
// =====================================================================

// Pengaman: hanya boleh dibuka dari komputer sendiri (localhost)
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Halaman ini hanya bisa dibuka dari localhost.');
}

// Muat koneksi database (menghasilkan variabel $pdo)
require_once __DIR__ . '/config/kuis_db.php';

// Daftar akun yang password-nya akan dibuat ulang.
// Format: email akun + password baru (teks biasa, nanti diubah jadi hash).
$accounts = [
    [
        'nama'     => 'Super Admin',
        'email'    => 'superadmin@stella.sch.id',
        'password' => 'password123'
    ],
    [
        'nama'     => 'Admin',
        'email'    => 'admin@stella.sch.id',
        'password' => 'password123'
    ],
    [
        'nama'     => 'Siswa',
        'email'    => 'siswa@stella.sch.id',
        'password' => 'password123'
    ]
];

$hasil = [];   // menyimpan status tiap akun untuk ditampilkan di browser

// Mengulang setiap akun agar proses reset dilakukan satu per satu.
foreach ($accounts as $account) {

    // Membuat hash password baru sebelum disimpan ke database.
    // (Password asli TIDAK disimpan, hanya hash-nya.)
    $hash = password_hash($account['password'], PASSWORD_DEFAULT);

    // Menyiapkan query untuk memperbarui password berdasarkan email akun.
    // Tanda :password dan :email diisi nilai secara aman (cegah SQL Injection).
    $stmt = $pdo->prepare("
        UPDATE users
        SET password = :password
        WHERE email = :email
    ");

    $stmt->execute([
        ':password' => $hash,
        ':email'    => $account['email']
    ]);

    // rowCount() = jumlah baris yang berubah. 0 berarti email tidak ditemukan di tabel users.
    $hasil[] = $stmt->rowCount() > 0;
}

// Fungsi kecil untuk escape output (cegah XSS)
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset Password | Etalase Digital Siswa</title>
<link rel="icon" href="assets/img/logo.png">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
<main class="auth-wrap">
    <div class="auth-card" style="max-width: 520px;">
        <img src="assets/img/logo.png" alt="Logo" class="auth-logo">
        <?php if (in_array(false, $hasil, true)): ?>
            <!-- Ada email yang tidak ketemu: biasanya karena schema.sql belum diimpor -->
            <h1>Sebagian akun tidak ditemukan</h1>
            <p class="auth-sub">Pastikan <strong>schema.sql</strong> sudah diimpor di phpMyAdmin, lalu buka halaman ini lagi.</p>
        <?php else: ?>
            <!-- Menampilkan informasi hasil reset password pada browser. -->
            <h1>Password semua akun berhasil diubah!</h1>
        <?php endif; ?>

        <?php foreach ($accounts as $i => $account): ?>
            <h3 style="margin: 1.2rem 0 .2rem;"><?= h($account['nama']) ?>
                <?= $hasil[$i] ? '' : ' <span class="muted">(email tidak ditemukan)</span>' ?></h3>
            Email: <?= h($account['email']) ?><br>
            Password: <?= h($account['password']) ?><br>
        <?php endforeach; ?>

        <p class="auth-foot"><strong>Hapus file reset_password.php</strong> setelah selesai.<br>
            <a href="auth/login.php">Ke halaman login</a></p>
    </div>
</main>
</body>
</html>
