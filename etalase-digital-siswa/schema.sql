-- =====================================================================
-- DATABASE SCHEMA (schema.sql)
-- Cara pakai: buka phpMyAdmin -> tab SQL -> tempel semua isi file ini -> Go
-- =====================================================================

-- Buat database baru kalau belum ada (IF NOT EXISTS = aman dijalankan berulang)
CREATE DATABASE IF NOT EXISTS db_etalase_stella;
-- Pilih database tersebut supaya semua perintah di bawah menyasar ke sini
USE db_etalase_stella;

-- ---------------------------------------------------------------------
-- TABEL users: menyimpan semua akun (super admin, admin, dan siswa)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,      -- nomor unik user, naik otomatis (1, 2, 3, ...)
    nama VARCHAR(100) NOT NULL,             -- nama lengkap, wajib diisi
    email VARCHAR(100) UNIQUE NOT NULL,     -- email, wajib & tidak boleh sama dengan user lain
    password VARCHAR(255) NOT NULL,         -- password dalam bentuk HASH (bukan teks asli)
    role ENUM('super_admin', 'admin', 'siswa') DEFAULT 'siswa'  -- hak akses; default siswa
);

-- ---------------------------------------------------------------------
-- TABEL projects: menyimpan karya digital yang diunggah
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,                -- nomor unik karya
    user_id INT NOT NULL,                             -- pemilik karya (merujuk ke users.id)
    nama_project VARCHAR(150) NOT NULL,               -- judul karya
    deskripsi TEXT NOT NULL,                          -- penjelasan karya (teks panjang)
    gambar VARCHAR(255) NOT NULL,                     -- NAMA FILE gambar di folder uploads/
    link_project VARCHAR(255) NOT NULL,               -- link karya (misal GitHub / website)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   -- waktu upload, terisi otomatis
    -- Foreign key: user_id harus ada di tabel users.
    -- ON DELETE CASCADE: kalau user dihapus, semua karyanya ikut terhapus.
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- AKUN DEFAULT untuk pengujian (password semuanya: password123)
-- Kolom password berisi hash bcrypt, bukan "password123" langsung.
-- ---------------------------------------------------------------------
INSERT INTO users (nama, email, password, role) VALUES
('Super Admin Stella', 'superadmin@stella.sch.id', '$2y$10$sdeM9WaFDPnmewQiRnHdQOFwNvmwrWh.B44frKITGnE2727jGeVNC', 'super_admin'),
('Admin Stella', 'admin@stella.sch.id', '$2y$10$sdeM9WaFDPnmewQiRnHdQOFwNvmwrWh.B44frKITGnE2727jGeVNC', 'admin'),
('Natasya', 'siswa@stella.sch.id', '$2y$10$sdeM9WaFDPnmewQiRnHdQOFwNvmwrWh.B44frKITGnE2727jGeVNC', 'siswa');
