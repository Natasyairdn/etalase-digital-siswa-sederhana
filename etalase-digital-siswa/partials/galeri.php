<?php
// =====================================================================
// FILE: partials/galeri.php
// FUNGSI: bagian Galeri Projek Siswa yang dipakai DUA halaman:
//   - index.php        (Etalase Karya, semua role)
//   - admin/index.php  (Dashboard Admin / Super Admin)
// Isinya: kolom cari, tombol Tambah Project, kartu karya, dan pop up detail.
// Tombol Edit & Hapus di kartu hanya muncul kalau user berhak.
// =====================================================================
$u = current_user();
$q = trim($_GET['q'] ?? '');                         // kata kunci pencarian dari kolom cari

// Query dasar: semua karya + nama pemilik (JOIN menggabungkan tabel projects & users)
$sql = 'SELECT p.*, u.nama AS pemilik FROM projects p JOIN users u ON u.id = p.user_id';
$params = [];                                        // daftar nilai untuk tanda ? di query
if ($q !== '') {
    // Ada kata kunci: filter nama karya, deskripsi, atau nama pemilik
    $sql .= ' WHERE p.nama_project LIKE ? OR p.deskripsi LIKE ? OR u.nama LIKE ?';
    // Karakter khusus LIKE (% dan _) di-escape supaya dianggap huruf biasa
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY p.created_at DESC, p.id DESC';    // karya terbaru paling atas
$st = $pdo->prepare($sql);
$st->execute($params);
$projects = $st->fetchAll();                         // semua karya hasil query (array)
?>
<!-- Baris atas galeri (sesuai sketsa): judul di kiri, kolom cari + tombol Tambah Project di kanan -->
<div class="catalog-toolbar">
    <div class="catalog-heading">
        <!-- Judul bagian galeri projek siswa. -->
        <h2 class="section-title">Galeri Projek Siswa</h2>
        <span class="catalog-count"><?= count($projects) ?> karya</span>
    </div>
    <div class="toolbar-aksi">
        <form class="search" method="get" action="<?= e($_SERVER['SCRIPT_NAME']) ?>" role="search">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="⌕  Cari karya/nama pembuat" aria-label="Cari karya">
            <button type="submit" class="btn btn-primary btn-sm">Cari</button>
        </form>
        <!-- Tombol menuju halaman tambah projek; tautan dan kelas tetap sama. -->
        <a class="btn btn-primary add-project" href="<?= e(url('project_tambah.php')) ?>">Tambah Project +</a>
    </div>
</div>

<?php if (!$projects): ?>
    <!-- Tampilan kalau tidak ada karya / hasil cari kosong -->
    <div class="empty">
        <?php if ($q !== ''): ?>
            Tidak ada karya yang cocok dengan “<?= e($q) ?>”. <a href="<?= e($_SERVER['SCRIPT_NAME']) ?>">Lihat semua karya</a>
        <?php else: ?>
            Belum ada karya. Klik <strong>Tambah Project +</strong> untuk mengunggah karya pertama.
        <?php endif; ?>
    </div>
<?php else: ?>
<div class="grid">
    <?php foreach ($projects as $p):
        // Boleh edit/hapus karya ini? (aturan RBAC dari middleware/auth_check.php)
        $boleh = boleh_kelola_karya($p['user_id']); ?>
    <!-- Satu kartu karya. Atribut data-* menyimpan isi karya agar JavaScript
         bisa mengisi pop up detail tanpa request ke server lagi. -->
    <article class="card"
        data-nama="<?= e($p['nama_project']) ?>"
        data-deskripsi="<?= e($p['deskripsi']) ?>"
        data-link="<?= e($p['link_project']) ?>"
        data-gambar="<?= e(upload_url($p['gambar'])) ?>"
        data-pemilik="<?= e($p['pemilik']) ?>"
        data-tanggal="<?= e(date('d M Y', strtotime($p['created_at']))) ?>">

        <!-- Gambar + nama bisa diklik untuk membuka pop up detail -->
        <button type="button" class="card-open" data-open-detail aria-label="Lihat detail <?= e($p['nama_project']) ?>">
            <span class="thumb"><img src="<?= e(upload_url($p['gambar'])) ?>" alt="Gambar <?= e($p['nama_project']) ?>" loading="lazy"></span>
            <span class="card-name"><?= e($p['nama_project']) ?></span>
        </button>

        <div class="card-body">
            <p class="card-meta">Oleh <?= e($p['pemilik']) ?> <span><?= e(date('d-m-Y', strtotime($p['created_at']))) ?></span></p>
            <p class="card-desc"><?= e($p['deskripsi']) ?></p>
            <a class="card-link" href="<?= e($p['link_project']) ?>" target="_blank" rel="noopener noreferrer">↗ &nbsp;Lihat project tautan</a>
        </div>

        <?php if ($boleh): ?>
        <!-- Tombol Edit & Hapus HANYA muncul kalau user berhak.
             (Server tetap mengecek ulang di project_edit.php dan project_hapus.php) -->
        <div class="card-actions">
            <a class="btn btn-edit btn-sm" href="<?= e(url('project_edit.php?id=' . (int)$p['id'])) ?>">✎ &nbsp;Edit</a>
            <!-- data-confirm: JavaScript menampilkan konfirmasi sebelum form dikirim -->
            <form method="post" action="<?= e(url('project_hapus.php')) ?>" data-confirm="Hapus karya “<?= e($p['nama_project']) ?>”? Tindakan ini tidak bisa dibatalkan.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button type="submit" class="btn btn-delete btn-sm">♧ &nbsp;Hapus</button>
            </form>
        </div>
        <?php endif; ?>
    </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ============ POP UP DETAIL KARYA ============
     Awalnya tersembunyi (atribut hidden). Diisi & dibuka oleh assets/js/app.js -->
<div class="modal" id="modal-detail" hidden>
    <div class="modal-backdrop" data-close></div>  <!-- latar gelap, klik untuk menutup -->
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="d-nama">
        <button type="button" class="modal-x" data-close aria-label="Tutup">&times;</button>
        <img id="d-gambar" class="detail-img" src="" alt="">
        <div class="detail-body">
            <h2 id="d-nama"></h2>
            <p class="detail-meta"><span id="d-pemilik"></span> &middot; <span id="d-tanggal"></span></p>
            <p id="d-deskripsi" class="detail-desc"></p>
            <a id="d-link" class="btn btn-primary" href="#" target="_blank" rel="noopener noreferrer">Buka link karya</a>
        </div>
    </div>
</div>
