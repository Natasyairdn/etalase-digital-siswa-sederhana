<?php
// =====================================================================
// FILE: admin/index.php
// FUNGSI: Dashboard Admin / Super Admin.
// Hanya Super Admin dan Admin yang boleh masuk (siswa ditolak 403).
// Admin & Super Admin bisa edit/hapus SEMUA karya (aturan di boleh_kelola_karya).
// =====================================================================
require_once __DIR__ . '/../middleware/auth_check.php';
wajib_role(['super_admin', 'admin']);            // selain admin langsung ditolak
$u = current_user();

$pageTitle = 'Dashboard ' . role_label($u['role']);   // contoh: "Dashboard Super Admin"
$showNav = true;
include __DIR__ . '/../partials/header.php';
?>
<main class="page">
    <?php include __DIR__ . '/../partials/galeri.php'; // kolom cari, kartu karya, pop up detail ?>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
