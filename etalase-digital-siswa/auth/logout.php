<?php
// =====================================================================
// FILE: auth/logout.php
// FUNGSI: Proses Logout Sesi. Hanya bisa lewat form POST + token CSRF.
// =====================================================================
require_once __DIR__ . '/../middleware/auth_check.php';

// Tolak kalau bukan lewat form POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Metode tidak diizinkan.'); }
csrf_check();

$_SESSION = [];                      // kosongkan semua data session

// Hapus juga cookie session di browser
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();                   // hancurkan session di server
session_start();                     // mulai session baru (hanya untuk membawa pesan flash)
flash('Kamu sudah logout.');
redirect('auth/login.php');
