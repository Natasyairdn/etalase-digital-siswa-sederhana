<?php
// =====================================================================
// FILE: admin/users.php (halaman Kelola Siswa)
// FUNGSI: Kelola User (Super Admin Only): lihat daftar, tambah Admin, hapus user.
// Satu file menangani tampilan tabel DAN dua aksi POST (tambah & hapus).
// =====================================================================
require_once __DIR__ . '/../middleware/auth_check.php';
wajib_role(['super_admin']);     // selain super admin langsung ditolak (403)
$me = current_user();            // super admin yang sedang login

// ---------------------------------------------------------------------
// PROSES AKSI (hanya jalan kalau form dikirim lewat POST)
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';        // "tambah" atau "hapus" (dari input tersembunyi di form)

    if ($aksi === 'tambah') {
        // ===== TAMBAH ADMIN BARU =====
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validasi, urut dari yang paling dasar. Hasilnya disimpan di $pesanError.
        $pesanError = '';
        if ($nama === '' || $email === '' || $password === '') $pesanError = 'Semua kolom wajib diisi.';
        elseif (mb_strlen($nama) > 100) $pesanError = 'Nama maksimal 100 karakter.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) $pesanError = 'Format email tidak valid.';
        elseif (strlen($password) < 6) $pesanError = 'Password minimal 6 karakter.';
        else {
            // Pastikan email belum dipakai
            $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $st->execute([$email]);
            if ($st->fetch()) $pesanError = 'Email sudah terdaftar.';
        }

        if ($pesanError !== '') {
            flash($pesanError, 'err');
        } else {
            // Role dikunci "admin" di sisi server (tidak diambil dari form)
            $ins = $pdo->prepare('INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, "admin")');
            $ins->execute([$nama, $email, buat_hash($password)]);  // password disimpan sebagai hash
            flash('Admin baru berhasil ditambahkan.');
        }

    } elseif ($aksi === 'hapus') {
        // ===== HAPUS USER =====
        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT id, role FROM users WHERE id = ?');
        $st->execute([$id]);
        $target = $st->fetch();

        if (!$target) {
            flash('User tidak ditemukan.', 'err');
        } elseif ((int)$target['id'] === (int)$me['id'] || $target['role'] === 'super_admin') {
            // Akun Super Admin (termasuk diri sendiri) tidak boleh dihapus, supaya sistem tidak "terkunci"
            flash('Akun Super Admin tidak bisa dihapus.', 'err');
        } else {
            // Hapus dulu file gambar milik user ini.
            // Baris karyanya di database terhapus otomatis (ON DELETE CASCADE),
            // tapi file gambarnya tidak, jadi dihapus manual.
            $imgs = $pdo->prepare('SELECT gambar FROM projects WHERE user_id = ?');
            $imgs->execute([$id]);
            foreach ($imgs->fetchAll() as $img) delete_image($img['gambar']);

            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flash('User berhasil dihapus.');
        }
    }
    redirect('admin/users.php');         // kembali ke tabel (mencegah kirim ulang form saat refresh)
}

// ---------------------------------------------------------------------
// TAMPILAN: ambil semua user, urut dari ID terkecil
// ---------------------------------------------------------------------
$users = $pdo->query('SELECT id, nama, email, role FROM users ORDER BY id ASC')->fetchAll();

$pageTitle = 'Kelola Siswa';
$showNav = true;
include __DIR__ . '/../partials/header.php';
?>
<main class="page">
    <div class="toolbar">
        <span></span>  <!-- penyeimbang agar tombol berada di kanan -->
        <!-- data-open-form: ditangkap JavaScript untuk membuka pop up tambah admin -->
        <button type="button" class="btn btn-primary" data-open-form>Tambah Admin +</button>
    </div>

    <!-- Tabel user: ID, Nama, Email, Role, Aksi (sesuai sketsa) -->
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>ID</th><th>Nama</th><th>Email</th><th>Role</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $row): ?>   <!-- ulangi satu baris untuk setiap user -->
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= e($row['nama']) ?></td>
                    <td><?= e($row['email']) ?></td>
                    <td><span class="badge badge-<?= e($row['role']) ?>"><?= e(role_label($row['role'])) ?></span></td>
                    <td>
                        <?php // Tombol Hapus tidak ditampilkan untuk Super Admin atau diri sendiri ?>
                        <?php if ($row['role'] !== 'super_admin' && (int)$row['id'] !== (int)$me['id']): ?>
                        <form method="post" data-confirm="Hapus akun <?= e($row['nama']) ?>? Semua karya milik akun ini ikut terhapus.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="aksi" value="hapus">
                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                        <?php else: ?>
                            <span class="muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Pop up Tambah Admin (dibuka tombol "Tambah Admin +", dikontrol assets/js/app.js) -->
<div class="modal" id="modal-form" hidden>
    <div class="modal-backdrop" data-close></div>
    <div class="modal-box modal-narrow" role="dialog" aria-modal="true" aria-labelledby="f-title">
        <button type="button" class="modal-x" data-close aria-label="Tutup">&times;</button>
        <form method="post" class="form-pad">
            <h2 id="f-title">Tambah Admin</h2>
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="tambah">
            <label for="a-nama">Nama</label>
            <input type="text" id="a-nama" name="nama" maxlength="100" required>
            <label for="a-email">Email</label>
            <input type="email" id="a-email" name="email" maxlength="100" required>
            <label for="a-pass">Password <span class="hint">(minimal 6 karakter)</span></label>
            <input type="password" id="a-pass" name="password" minlength="6" autocomplete="new-password" required>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" data-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../partials/footer.php'; ?>
