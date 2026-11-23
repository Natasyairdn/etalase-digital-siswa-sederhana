<?php
// =====================================================================
// FILE: project_hapus.php
// FUNGSI: Proses Hapus Project + file Gambar. (DELETE pada CRUD)
// Tidak punya tampilan: hanya memproses lalu kembali ke galeri.
// =====================================================================
require_once __DIR__ . '/middleware/auth_check.php';
wajib_login();
$u = current_user();

// Hanya boleh lewat form POST + token CSRF (hapus tidak boleh lewat link biasa)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Metode tidak diizinkan.'); }
csrf_check();

// Ambil karya yang mau dihapus
$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
$st->execute([$id]);
$p = $st->fetch();

if (!$p) {
    flash('Karya tidak ditemukan.', 'err');
} elseif (!boleh_kelola_karya($p['user_id'])) {
    // Admin boleh hapus semua, siswa hanya miliknya
    http_response_code(403);
    exit('403 - Kamu hanya bisa menghapus karya milikmu sendiri.');
} else {
    $pdo->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);   // hapus baris di database
    delete_image($p['gambar']);                                           // hapus juga file gambarnya
    flash('Karya berhasil dihapus.');
}
redirect(home_for_role($u['role']));   // kembali ke galeri
