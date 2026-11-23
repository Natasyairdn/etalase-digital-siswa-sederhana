<?php
// =====================================================================
// FILE: middleware/auth_check.php
// FUNGSI: Proteksi Sesi & Hak Akses (3 Role). Di-include di AWAL setiap halaman.
// Isinya:
//   1. Memulai session + koneksi database
//   2. Fungsi URL dan keamanan dasar (escape output, CSRF, pesan flash)
//   3. Pengecekan login & role (super_admin / admin / siswa) + hash password
//   4. Upload & hapus gambar
// =====================================================================

// ---------------------------------------------------------------------
// 1. SESSION & DATABASE
// ---------------------------------------------------------------------
// Mulai session kalau belum dimulai. Session = "ingatan" server tentang user yang login.
if (session_status() === PHP_SESSION_NONE) {
    // httponly: cookie session tidak bisa dibaca JavaScript (cegah pencurian session)
    // samesite Lax: cookie tidak dikirim dari situs lain (bantu cegah CSRF)
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
// Muat koneksi database supaya variabel $pdo tersedia di semua halaman
require_once __DIR__ . '/../config/kuis_db.php';

// ---------------------------------------------------------------------
// 2. FUNGSI URL
// Halaman ada di folder berbeda (root, auth/, admin/), jadi alamat
// harus dihitung dari folder utama aplikasi supaya link tidak salah.
// ---------------------------------------------------------------------

// Alamat folder utama aplikasi di browser, contoh "/etalase-digital-stella"
function base_url(): string {
    $root = str_replace('\\', '/', realpath(__DIR__ . '/..'));                           // folder utama aplikasi
    $dir  = str_replace('\\', '/', realpath(dirname($_SERVER['SCRIPT_FILENAME'])));      // folder halaman yang sedang dibuka
    $rel  = trim(substr($dir, strlen($root)), '/');                                      // "" (root), "auth", atau "admin"
    $naik = $rel === '' ? 0 : substr_count($rel, '/') + 1;                               // berapa tingkat harus naik ke root
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));                    // alamat folder halaman saat ini
    for ($i = 0; $i < $naik; $i++) $base = dirname($base);                               // naik ke folder utama
    return rtrim($base, '/');
}
// Buat alamat lengkap ke sebuah file. Contoh: url('auth/login.php')
function url(string $path = ''): string { return base_url() . '/' . ltrim($path, '/'); }
// Alamat file statis (css, js, logo) di folder assets/
function asset(string $path): string { return url('assets/' . $path); }
// Alamat gambar karya di folder uploads/
function upload_url(string $name): string { return url('uploads/' . rawurlencode($name)); }
// Pindah ke halaman lain lalu hentikan script (exit penting agar kode di bawahnya tidak jalan)
function redirect(string $path): void { header('Location: ' . url($path)); exit; }

// ---------------------------------------------------------------------
// e() = "escape". Mengubah karakter berbahaya (< > " ') jadi aman.
// WAJIB dipakai setiap menampilkan data dari user, untuk mencegah XSS
// (misal orang menulis <script> di nama karya).
// ---------------------------------------------------------------------
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ---------------------------------------------------------------------
// flash() = pesan sekali tampil (contoh: "Karya berhasil ditambahkan").
// - Dipanggil dengan isi pesan -> menyimpan pesan di session
// - Dipanggil tanpa isi        -> mengambil pesan lalu menghapusnya
// ---------------------------------------------------------------------
function flash(?string $msg = null, string $type = 'ok'): ?array {
    if ($msg !== null) {                                  // mode SIMPAN
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;                      // mode AMBIL (null kalau tidak ada)
    unset($_SESSION['flash']);                            // hapus supaya tidak muncul dua kali
    return $f;
}

// ---------------------------------------------------------------------
// CSRF: pelindung agar form tidak bisa dikirim palsu dari situs lain.
// Cara kerja: tiap form membawa token rahasia, server mencocokkannya.
// ---------------------------------------------------------------------
// Ambil token dari session; kalau belum ada, buat token acak baru (64 karakter)
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
// Input tersembunyi berisi token, dipasang di dalam setiap <form method="post">
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
// Cocokkan token dari form dengan token di session. Panggil di awal setiap proses POST.
function csrf_check(): void {
    // hash_equals = perbandingan aman (tidak bisa ditebak lewat selisih waktu)
    $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    if (!$ok) { http_response_code(419); exit('Sesi formulir tidak valid. Kembali lalu coba lagi.'); }
}

// ---------------------------------------------------------------------
// 3. AUTENTIKASI (siapa kamu?) dan OTORISASI (boleh ngapain?)
// ---------------------------------------------------------------------

// Ambil data user yang sedang login (array), atau null kalau belum login
function current_user(): ?array { return $_SESSION['user'] ?? null; }

// Ubah nama role di database jadi tulisan yang enak dibaca di layar
function role_label(string $role): string {
    return ['super_admin' => 'Super Admin', 'admin' => 'Admin', 'siswa' => 'Siswa'][$role] ?? $role;
}
// Halaman utama setelah login, tergantung role:
// Super Admin & Admin -> admin/index.php, Siswa -> index.php
function home_for_role(string $role): string {
    return in_array($role, ['super_admin', 'admin'], true) ? 'admin/index.php' : 'index.php';
}

// ---------------------------------------------------------------------
// PASSWORD HASH: semua urusan hash password dikumpulkan di dua fungsi ini.
// Password TIDAK disimpan sebagai teks asli, tapi sebagai hash satu arah
// (bcrypt) yang tidak bisa dikembalikan ke password aslinya.
// Dipakai di: auth/register.php, admin/users.php (membuat), auth/login.php (mengecek).
// ---------------------------------------------------------------------

// Ubah password teks biasa jadi hash. Hasilnya yang disimpan di kolom users.password.
// PASSWORD_DEFAULT = algoritma bawaan PHP yang terbaru (saat ini bcrypt).
// Setiap pemanggilan menghasilkan hash BERBEDA walau passwordnya sama (karena ada "salt" acak).
function buat_hash(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}
// Cek apakah password yang diketik cocok dengan hash di database. Hasilnya true / false.
function cek_hash(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

// Wajib login. Kalau belum, lempar ke halaman login.
function wajib_login(): void {
    if (!current_user()) redirect('auth/login.php');
}
// Wajib login DAN role harus termasuk daftar $roles.
// Contoh: wajib_role(['super_admin']) -> hanya Super Admin yang boleh masuk.
function wajib_role(array $roles): void {
    wajib_login();                                                // cek login dulu
    if (!in_array($_SESSION['user']['role'], $roles, true)) {     // role user tidak ada di daftar?
        http_response_code(403);                                  // 403 = Forbidden (dilarang)
        exit('403 - Kamu tidak punya akses ke halaman ini. <a href="' . e(url(home_for_role($_SESSION['user']['role']))) . '">Kembali</a>');
    }
}
// Aturan edit/hapus karya (sesuai matriks LKPD):
// - Super Admin & Admin boleh semua karya
// - Siswa hanya boleh karya miliknya (user_id sama dengan id yang login)
function boleh_kelola_karya($ownerId): bool {
    $u = current_user();
    if (!$u) return false;                                        // belum login -> tidak boleh
    return in_array($u['role'], ['super_admin', 'admin'], true)   // admin? boleh
        || (int)$u['id'] === (int)$ownerId;                       // atau pemilik karya? boleh
}

// ---------------------------------------------------------------------
// 4. UPLOAD GAMBAR
// $file  = data file dari $_FILES['gambar']
// $error = diisi pesan error (dikirim lewat reference &)
// Return: nama file baru kalau sukses, null kalau gagal
// ---------------------------------------------------------------------
function handle_upload(array $file, ?string &$error): ?string {
    // Tidak ada file yang dipilih
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) { $error = 'Gambar wajib diisi.'; return null; }
    // Ada error saat upload dari PHP
    if ($file['error'] !== UPLOAD_ERR_OK) { $error = 'Upload gambar gagal. Coba lagi.'; return null; }
    // Batasi ukuran maksimal 10 MB (10 * 1024 * 1024 byte)
    if ($file['size'] > 10 * 1024 * 1024) { $error = 'Ukuran gambar maksimal 10 MB.'; return null; }

    // Cek JENIS file dari isinya (bukan dari nama/ekstensi, karena ekstensi gampang dipalsukan)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    // Daftar jenis gambar yang diizinkan beserta ekstensi amannya
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime])) { $error = 'Format gambar harus JPG, PNG, WEBP, atau GIF.'; return null; }

    // Pastikan folder uploads tersedia sebelum file dipindahkan.
    $uploadDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        $error = 'Folder penyimpanan gambar tidak dapat dibuat.'; return null;
    }
    if (!is_writable($uploadDir)) {
        $error = 'Folder penyimpanan gambar tidak memiliki izin tulis.'; return null;
    }

    // Nama file acak supaya tidak bentrok dan tidak bisa ditebak.
    $name = bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
    // Pindahkan file dari folder sementara ke folder uploads/.
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $name)) {
        $error = 'Gambar tidak bisa disimpan. Periksa izin folder uploads.'; return null;
    }
    return $name; // sukses: kembalikan nama file untuk disimpan di database
}
// Hapus file gambar dari folder uploads/ (saat karya dihapus / gambar diganti)
function delete_image(string $name): void {
    $name = basename($name);                              // basename: buang unsur path agar tidak bisa keluar folder
    $path = __DIR__ . '/../uploads/' . $name;             // alamat lengkap file
    if ($name !== '' && is_file($path)) @unlink($path);   // kalau filenya ada, hapus
}
