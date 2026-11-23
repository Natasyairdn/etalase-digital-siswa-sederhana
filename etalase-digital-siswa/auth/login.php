<?php
// =====================================================================
// FILE: auth/login.php
// FUNGSI: Form & Proses Login Multi-Role (super admin, admin, siswa).
// Alur: user isi email & password -> cari di database ->
//       cocokkan password dengan hash -> simpan data di session ->
//       arahkan ke halaman sesuai role.
// =====================================================================
require_once __DIR__ . '/../middleware/auth_check.php';

// Kalau sudah login, tidak perlu lihat halaman login lagi
if (current_user()) redirect(home_for_role(current_user()['role']));

$error = '';   // tempat pesan error untuk ditampilkan
$email = '';   // menyimpan email yang diketik supaya tidak hilang kalau salah

// Blok ini hanya jalan saat form dikirim (method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();                              // pastikan token form valid
    $email = trim($_POST['email'] ?? '');      // trim = buang spasi di awal/akhir
    $password = $_POST['password'] ?? '';      // password tidak di-trim (spasi bisa jadi bagian password)

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        // Prepared statement: tanda ? diganti nilai secara aman -> cegah SQL Injection
        $st = $pdo->prepare('SELECT id, nama, email, password, role FROM users WHERE email = ?');
        $st->execute([$email]);                // jalankan query dengan nilai $email
        $row = $st->fetch();                   // ambil 1 baris hasil (false kalau tidak ada)

        // cek_hash() membandingkan password yang diketik dengan HASH di database
        // (fungsi ada di middleware/auth_check.php)
        if ($row && cek_hash($password, $row['password'])) {
            session_regenerate_id(true);       // ganti ID session setelah login (cegah session fixation)
            // Simpan data penting user di session (TANPA password)
            $_SESSION['user'] = [
                'id'    => (int)$row['id'],
                'nama'  => $row['nama'],
                'email' => $row['email'],
                'role'  => $row['role'],
            ];
            redirect(home_for_role($row['role']));   // login sukses -> halaman sesuai role
        }
        // Pesan sengaja dibuat sama untuk "email salah" dan "password salah"
        // supaya penyerang tidak tahu email mana yang terdaftar.
        $error = 'Email atau password salah.';
    }
}
$pageTitle = 'Login';          // judul tab
$showNav = false;              // halaman login tidak pakai navbar
$bodyClass = 'auth-page';      // kelas CSS untuk hiasan latar halaman login
include __DIR__ . '/../partials/header.php';
?>
<main class="auth-wrap">
    <!-- novalidate: validasi ditangani server, supaya pesan error seragam -->
    <form class="auth-card" method="post" novalidate>
        <img src="<?= e(asset('img/logo.png')) ?>" alt="Logo" class="auth-logo">
        <h1>Etalase Digital Siswa</h1>
        <p class="auth-sub">Silakan masuk ke akun Anda</p>
        <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Masukkan email" value="<?= e($email) ?>" autocomplete="username" required>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
        <button type="submit" class="btn btn-primary btn-block">Login</button>
        <p class="auth-foot">Belum punya akun? <a href="<?= e(url('auth/register.php')) ?>">Daftar Siswa!</a></p>
    </form>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
