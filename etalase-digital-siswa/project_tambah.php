<?php
// =====================================================================
// FILE: project_tambah.php
// FUNGSI: Form Tambah Project + JS Preview gambar. (CREATE pada CRUD)
// Semua role yang login boleh menambah karya. Pemilik karya = user yang login.
// =====================================================================
// Muat middleware yang menyediakan sesi, koneksi database, dan fungsi bantuan.
require_once __DIR__ . '/middleware/auth_check.php';
// Pengguna harus login sebelum mengunggah project baru.
wajib_login();
// Data akun aktif digunakan sebagai pemilik project yang akan dibuat.
$u = current_user();

// Menyimpan pesan kesalahan validasi dan nilai input form.
$error = '';
// Nilai awal kosong; nilai yang dikirim akan dipertahankan jika validasi gagal.
$nama = $deskripsi = $link = '';

// Bagian pemrosesan hanya berjalan ketika pengguna mengirim form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi token untuk melindungi form dari request yang tidak sah.
    csrf_check();
    $nama = trim($_POST['nama_project'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $link = trim($_POST['link_project'] ?? '');

    // Pastikan nama, deskripsi, dan link terisi serta memenuhi batas/format.
    // ---- Validasi input ----
    if ($nama === '' || $deskripsi === '' || $link === '') {
        $error = 'Nama, deskripsi, dan link wajib diisi.';
    } elseif (mb_strlen($nama) > 150) {
        $error = 'Nama project maksimal 150 karakter.';
    } elseif (mb_strlen($link) > 255 || !filter_var($link, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $link)) {
        // Link harus URL valid DAN diawali http:// atau https:// (menolak "javascript:..." yang berbahaya)
        $error = 'Link harus berupa URL yang diawali http:// atau https://';
    } else {
        // Validasi dan simpan gambar; helper mengisi $error jika file tidak memenuhi aturan.
        $gambar = handle_upload($_FILES['gambar'] ?? [], $error);
        if ($gambar) {
            // ID pemilik diambil dari sesi login, bukan dari form, untuk mencegah pemalsuan pemilik.
            // Siapkan query INSERT dengan parameter agar input tidak disisipkan langsung ke SQL.
            $ins = $pdo->prepare('INSERT INTO projects (user_id, nama_project, deskripsi, gambar, link_project) VALUES (?, ?, ?, ?, ?)');
            // Simpan project baru beserta ID pengguna yang sedang login.
            $ins->execute([$u['id'], $nama, $deskripsi, $gambar, $link]);
            flash('Karya berhasil ditambahkan.');
            redirect(home_for_role($u['role']));      // kembali ke galeri
        }
    }
}
// Atur judul browser, navbar, dan kelas halaman sebelum memuat header.
$pageTitle = 'Tambah Karya';
$showNav = true;
$bodyClass = 'project-form-page';
include __DIR__ . '/partials/header.php';
?>
<main class="page add-page">
    <!-- enctype multipart/form-data diperlukan agar file gambar ikut dikirim bersama data form. -->
    <form class="form-card" method="post" enctype="multipart/form-data" novalidate>
        <h1>Unggah karya digital baru</h1>
        <?php if ($error): ?><div class="alert" role="alert"><?= e($error) ?></div><?php endif; ?>
        <!-- Token CSRF membantu memastikan form dikirim dari sesi pengguna yang sah. -->
        <?= csrf_field() ?>
        <!-- Field isian project: nama, penjelasan, tautan, dan file gambar. -->
        <label for="nama_project">Nama karya/aplikasi</label>
        <input type="text" id="nama_project" name="nama_project" value="<?= e($nama) ?>" maxlength="150" placeholder="Contoh: Aplikasi IoT" required>
        <label for="deskripsi">Deskripsi karya</label>
        <textarea id="deskripsi" name="deskripsi" rows="4" placeholder="Jelaskan fitur, latar belakang, dan kegunaan karyamu" required><?= e($deskripsi) ?></textarea>
        <label for="link_project">Link project (demo web, video, GitHub)</label>
        <input type="url" id="link_project" name="link_project" value="<?= e($link) ?>" maxlength="255" placeholder="https://..." required>
        <label for="gambar">Foto/tangkapan layar karya <span class="hint">(JPG, PNG, WEBP, GIF; maks. 10 MB)</span></label>
        <div class="file-field"><input type="file" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp,image/gif" data-preview="#preview" required></div>
        <img id="preview" class="preview" src="" alt="Pratinjau gambar" hidden>
        <!-- Simpan untuk menerbitkan karya; Batalkan untuk kembali ke halaman utama sesuai role. -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan &amp; publikasikan</button>
            <a class="btn btn-outline" href="<?= e(url(home_for_role($u['role']))) ?>">Batalkan</a>
        </div>
    </form>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
