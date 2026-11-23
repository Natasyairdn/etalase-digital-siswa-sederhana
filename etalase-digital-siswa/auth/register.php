<?php
// =====================================================================
// FILE: auth/register.php
// FUNGSI: Registrasi Akun Siswa. Akun baru SELALU berperan 'siswa'.
// =====================================================================
require_once __DIR__ . '/../middleware/auth_check.php';
if (current_user()) redirect(home_for_role(current_user()['role']));   // sudah login? tidak perlu daftar

$error = '';
$nama = $email = '';   // isi kembali form kalau ada error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // ---- Validasi input (urut dari yang paling dasar) ----
    if ($nama === '' || $email === '' || $password === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (mb_strlen($nama) > 100) {                       // mb_strlen: hitung karakter dengan benar
        $error = 'Nama maksimal 100 karakter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        // Cek apakah email sudah dipakai orang lain
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            $error = 'Email sudah terdaftar. Gunakan email lain atau login.';
        } else {
            // Role ditulis langsung "siswa" di query, BUKAN dari input form.
            // Jadi tidak ada yang bisa mendaftar sebagai admin lewat halaman ini.
            $ins = $pdo->prepare('INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, "siswa")');
            // buat_hash(): ubah password jadi hash satu arah (fungsi ada di middleware/auth_check.php)
            $ins->execute([$nama, $email, buat_hash($password)]);
            flash('Akun berhasil dibuat. Silakan login.');   // pesan untuk halaman login
            redirect('auth/login.php');
        }
    }
}
$pageTitle = 'Daftar Siswa';
$showNav = false;
$bodyClass = 'auth-page';
include __DIR__ . '/../partials/header.php';
?>
<main class="auth-wrap">
    <form class="auth-card" method="post" novalidate>
        <img src="<?= e(asset('img/logo.png')) ?>" alt="Logo" class="auth-logo">
        <h1>Daftar Siswa</h1>
        <p class="auth-sub">Buat akun untuk mengunggah karyamu</p>
        <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <label for="nama">Nama Lengkap</label>
        <!-- Petunjuk isian nama lengkap, mengikuti gaya placeholder di halaman login. -->
        <input type="text" id="nama" name="nama" placeholder="Masukkan nama lengkap" value="<?= e($nama) ?>" maxlength="100" required>
        <label for="email">Email</label>
        <!-- Petunjuk isian email; nilai email tetap dipertahankan jika form mengalami error. -->
        <input type="email" id="email" name="email" placeholder="Masukkan email" value="<?= e($email) ?>" maxlength="100" autocomplete="username" required>
        <label for="password">Password</label>
        <!-- Petunjuk isian password; ketentuan minimal 6 karakter tidak diubah. -->
        <input type="password" id="password" name="password" placeholder="Masukkan password" minlength="6" autocomplete="new-password" required>
        <button type="submit" class="btn btn-primary btn-block">Daftar Sekarang &rarr;</button>
        <p class="auth-foot">Sudah punya akun? <a href="<?= e(url('auth/login.php')) ?>">Login disini!</a></p>
    </form>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
