<?php
// =====================================================================
// FILE: config/kuis_db.php
// FUNGSI: koneksi ke database MySQL memakai PDO.
// File ini di-include oleh middleware/auth_check.php, jadi semua halaman
// otomatis punya variabel $pdo untuk menjalankan query.
// =====================================================================

// Data koneksi (bawaan XAMPP: user "root", password kosong)
$db_host = 'localhost';          // alamat server database (komputer sendiri)
$db_name = 'db_etalase_stella';  // nama database yang dibuat lewat schema.sql
$db_user = 'root';               // username MySQL
$db_pass = '';                   // password MySQL (kosong di XAMPP)

// try ... catch: coba koneksi, kalau gagal tangkap errornya (tidak crash)
try {
    // Buat objek PDO = "jembatan" antara PHP dan MySQL
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", // alamat + nama db + dukungan huruf khusus
        $db_user,
        $db_pass,
        [
            // Kalau query error, lempar Exception (supaya mudah ditangkap)
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Hasil query dikembalikan sebagai array bernama, contoh $row['nama']
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Pakai prepared statement asli dari MySQL (lebih aman dari SQL Injection)
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // Kalau koneksi gagal: kirim kode HTTP 500 (error server)
    http_response_code(500);
    // Tampilkan pesan ramah, lalu hentikan program (detail error asli tidak dibocorkan)
    exit('Koneksi database gagal. Pastikan MySQL di XAMPP sudah menyala dan database sudah diimpor.');
}
