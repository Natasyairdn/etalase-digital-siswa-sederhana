<?php
// =====================================================================
// FILE: project_edit.php
// FUNGSI: Form Edit Project. (UPDATE pada CRUD)
// Alamat: project_edit.php?id=5  (id = nomor karya yang diedit)
// Aturan: Admin & Super Admin boleh edit semua karya, Siswa hanya miliknya.
// =====================================================================
// Muat middleware untuk koneksi database, helper, sesi, dan pemeriksaan akses.
require_once __DIR__ . '/middleware/auth_check.php';
// Pastikan halaman hanya dapat dibuka oleh pengguna yang sudah masuk.
wajib_login();
// Simpan data akun aktif untuk menentukan izin dan tujuan redirect.
$u = current_user();

// Ambil id karya dari alamat (saat buka form) atau dari form (saat simpan)
// Ambil ID dari URL saat halaman dibuka, atau dari input tersembunyi saat form dikirim.
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
// Query menggunakan parameter agar nilai ID tidak disisipkan langsung ke SQL.
$st = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
$st->execute([$id]);
$p = $st->fetch();                    // Data project yang ditemukan; false jika ID tidak cocok.

if (!$p) { flash('Karya tidak ditemukan.', 'err'); redirect(home_for_role($u['role'])); }

// CEK HAK AKSES di server: siswa hanya boleh edit karya miliknya.
// (Tombol Edit memang disembunyikan, tapi orang bisa mengetik alamat ?id=... sendiri, jadi wajib dicek di sini.)
if (!boleh_kelola_karya($p['user_id'])) {
    http_response_code(403);
    exit('403 - Kamu hanya bisa mengubah karya milikmu sendiri.');
}

// Variabel error menampung pesan validasi untuk ditampilkan pada halaman.
$error = '';
// Isi form pertama kali menggunakan data lama dari database agar pengguna dapat mengeditnya.
$nama = $p['nama_project'];
$deskripsi = $p['deskripsi'];
$link = $p['link_project'];

// Proses penyimpanan hanya dijalankan saat form dikirim menggunakan metode POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Periksa token keamanan untuk mencegah pengiriman form lintas situs (CSRF).
    csrf_check();
    // Isi form diganti dengan yang baru diketik (supaya tidak hilang kalau ada error)
    $nama = trim($_POST['nama_project'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $link = trim($_POST['link_project'] ?? '');

    // Validasi memastikan semua data wajib tersedia dan formatnya sesuai sebelum disimpan.
    // ---- Validasi input ----
    if ($nama === '' || $deskripsi === '' || $link === '') {
        $error = 'Nama, deskripsi, dan link wajib diisi.';
    } elseif (mb_strlen($nama) > 150) {
        $error = 'Nama project maksimal 150 karakter.';
    } elseif (mb_strlen($link) > 255 || !filter_var($link, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $link)) {
        $error = 'Link harus berupa URL yang diawali http:// atau https://';
    } else {
        // Jika tidak ada gambar baru, gunakan gambar yang sudah tersimpan sebelumnya.
        $gambar = $p['gambar'];
        // Cek apakah pengguna memilih file gambar pengganti pada form.
        $adaGambarBaru = isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE;
        if ($adaGambarBaru) {
            // Helper memvalidasi file dan menyimpan gambar baru; pesan gagal masuk ke $error.
            $baru = handle_upload($_FILES['gambar'], $error);
            if ($baru) {
                // Setelah gambar baru berhasil disimpan, hapus gambar lama agar tidak menumpuk.
                delete_image($p['gambar']);
                $gambar = $baru;
            }
        }
        // Update database hanya jika seluruh validasi dan proses upload tidak menghasilkan error.
        if ($error === '') {
            // Perbarui kolom project dengan data baru, termasuk gambar yang dipilih.
            $up = $pdo->prepare('UPDATE projects SET nama_project = ?, deskripsi = ?, gambar = ?, link_project = ? WHERE id = ?');
            $up->execute([$nama, $deskripsi, $gambar, $link, $id]);
            flash('Karya berhasil diperbarui.');
            redirect(home_for_role($u['role']));
        }
    }
}
// Atur judul halaman dan tampilkan navbar sebelum merender form.
$pageTitle = 'Edit Project';
$showNav = true;
include __DIR__ . '/partials/header.php';
?>
<main class="page">
    <!-- Form edit mengirim teks dan file gambar melalui POST multipart. -->
    <form class="form-card" method="post" enctype="multipart/form-data" novalidate>
        <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
        <!-- Token CSRF dan ID project dipakai untuk keamanan serta identifikasi data. -->
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <!-- Field berikut menampilkan data lama dan dapat diubah oleh pengguna. -->
        <label for="nama_project">Nama project</label>
        <input type="text" id="nama_project" name="nama_project" value="<?= e($nama) ?>" maxlength="150" required>
        <label for="deskripsi">Deskripsi</label>
        <textarea id="deskripsi" name="deskripsi" rows="4" required><?= e($deskripsi) ?></textarea>
        <label for="gambar">Gambar <span class="hint">(kosongkan jika tidak ingin mengganti)</span></label>
        <input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp,image/gif" data-preview="#preview">
        <!-- Awalnya menampilkan gambar yang tersimpan; kalau pilih file baru, JS menggantinya -->
        <img id="preview" class="preview" src="<?= e(upload_url($p['gambar'])) ?>" alt="Gambar saat ini">
        <label for="link_project">Link project</label>
        <input type="url" id="link_project" name="link_project" value="<?= e($link) ?>" maxlength="255" placeholder="https://" required>
        <!-- Tombol batal kembali ke halaman sesuai role; tombol simpan mengirim perubahan. -->
        <div class="form-actions">
            <a class="btn btn-outline" href="<?= e(url(home_for_role($u['role']))) ?>">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
