# Etalase Digital Siswa (PHP Native)

## Cara pakai (XAMPP)
1. Salin folder `etalase-digital-stella` ke `C:\xampp\htdocs\`.
2. Jalankan **Apache** dan **MySQL** di XAMPP.
3. Buka phpMyAdmin -> tab SQL -> tempel isi `schema.sql` -> Go.
4. Buka http://localhost/etalase-digital-stella/

## Akun default
Setelah impor schema.sql, buka `reset_password.php` SEKALI di browser (lalu hapus file itu).
Password ketiga akun jadi: password123.

| Role        | Email                         | Setelah login ke      |
|-------------|-------------------------------|-----------------------|
| Super Admin | superadmin@stella.sch.id      | admin/index.php       |
| Admin       | admin@stella.sch.id           | admin/index.php       |
| Siswa       | siswa@stella.sch.id (Natasya) | index.php             |

## Struktur folder
```
etalase-digital-stella/
├── config/
│   └── kuis_db.php        # Koneksi Basis Data PDO
├── middleware/
│   └── auth_check.php     # Proteksi Sesi & Hak Akses (3 Role)
├── auth/
│   ├── login.php          # Form & Proses Login Multi-Role
│   ├── register.php       # Registrasi Akun Siswa
│   └── logout.php         # Proses Logout Sesi
├── admin/
│   ├── index.php          # Dashboard Admin/Super Admin
│   └── users.php          # Kelola User (Super Admin Only)
├── uploads/               # Folder Penyimpanan Gambar Project
├── index.php              # Halaman Utama (Etalase Karya)
├── project_tambah.php     # Form Tambah Project + JS Preview
├── project_edit.php       # Form Edit Project
├── project_hapus.php      # Proses Hapus Project + Gambar
├── schema.sql             # Database Schema
├── reset_password.php     # (tambahan) reset password akun contoh, jalankan sekali lalu hapus
├── partials/              # (tambahan) header, footer, galeri yang dipakai bersama
└── assets/                # (tambahan) css, js, logo
```

## Hak akses
- admin/users.php: hanya Super Admin.
- admin/index.php: Super Admin & Admin.
- Edit/Hapus karya: Super Admin & Admin semua karya; Siswa hanya miliknya.
- Semua pengecekan dilakukan di server (middleware/auth_check.php), bukan cuma disembunyikan di tampilan.
