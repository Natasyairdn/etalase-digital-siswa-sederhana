// =====================================================================
// FILE: assets/js/app.js
// FUNGSI: JavaScript untuk interaksi halaman:
//   1. Membuka & menutup pop up (modal)
//   2. Mengisi pop up detail karya (di galeri)
//   3. Membuka pop up Tambah Admin (di halaman Kelola Siswa)
//   4. Pratinjau gambar sebelum diunggah (form tambah & edit project)
//   5. Konfirmasi sebelum menghapus
//   6. Menghilangkan pesan flash otomatis
// Dibungkus (function () { ... })(); supaya variabelnya tidak bentrok
// dengan script lain (disebut IIFE).
// =====================================================================
(function () {
    // Menyimpan elemen yang terakhir difokus, agar fokus kembali ke sana saat pop up ditutup
    var lastFocus = null;

    // ---------- Fungsi buka pop up ----------
    function openModal(m) {
        lastFocus = document.activeElement;               // ingat tombol yang diklik
        m.hidden = false;                                 // tampilkan pop up
        document.body.classList.add('modal-open');        // kunci scroll halaman di belakang
        // Pindahkan fokus keyboard ke elemen pertama di dalam pop up (aksesibilitas)
        var f = m.querySelector('input:not([type=hidden]), textarea, a.btn, .btn-primary');
        if (f) f.focus();
    }

    // ---------- Fungsi tutup pop up ----------
    function closeModal(m) {
        m.hidden = true;                                  // sembunyikan pop up
        document.body.classList.remove('modal-open');     // aktifkan scroll lagi
        if (lastFocus && lastFocus.focus) lastFocus.focus(); // kembalikan fokus
    }

    // ---------- Pasang aksi "tutup" ----------
    // Semua elemen ber-atribut data-close (tombol X, Batal, latar gelap) menutup pop up
    document.querySelectorAll('.modal').forEach(function (m) {
        m.querySelectorAll('[data-close]').forEach(function (el) {
            el.addEventListener('click', function () { closeModal(m); });
        });
    });
    // Tekan tombol Escape = tutup pop up yang sedang terbuka
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') {
            document.querySelectorAll('.modal:not([hidden])').forEach(closeModal);
        }
    });

    // ---------- POP UP DETAIL KARYA ----------
    var detail = document.getElementById('modal-detail');
    if (detail) {                                          // hanya ada di halaman galeri
        document.querySelectorAll('[data-open-detail]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                // Ambil data karya dari atribut data-* milik kartu (<article class="card">)
                var c = btn.closest('.card').dataset;
                var img = document.getElementById('d-gambar');
                img.src = c.gambar;
                img.alt = 'Gambar ' + c.nama;
                // textContent (bukan innerHTML) supaya teks dari user tidak dijalankan sebagai HTML (aman dari XSS)
                document.getElementById('d-nama').textContent = c.nama;
                document.getElementById('d-pemilik').textContent = 'Oleh ' + c.pemilik;
                document.getElementById('d-tanggal').textContent = c.tanggal;
                document.getElementById('d-deskripsi').textContent = c.deskripsi;
                document.getElementById('d-link').href = c.link;
                openModal(detail);                         // tampilkan pop up
            });
        });
    }

    // ---------- POP UP TAMBAH ADMIN (halaman Kelola Siswa) ----------
    var modalForm = document.getElementById('modal-form');
    if (modalForm) {
        document.querySelectorAll('[data-open-form]').forEach(function (btn) {
            btn.addEventListener('click', function () { openModal(modalForm); });
        });
    }

    // ---------- PRATINJAU GAMBAR (JS Preview) ----------
    // Input file yang punya data-preview="#id" akan menampilkan gambar pilihannya
    // di elemen <img> dengan id tersebut, sebelum form dikirim.
    document.querySelectorAll('input[type=file][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            var img = document.querySelector(input.dataset.preview);   // elemen <img> tujuan
            var file = input.files && input.files[0];                  // file yang dipilih (kalau ada)
            if (!img || !file) return;
            // URL.createObjectURL membuat alamat sementara untuk file di komputer user
            // (file tidak dikirim ke mana-mana, hanya ditampilkan)
            img.src = URL.createObjectURL(file);
            img.hidden = false;                                        // tampilkan gambar pratinjau
        });
    });

    // ---------- KONFIRMASI HAPUS ----------
    // Form yang punya data-confirm menampilkan kotak "OK / Batal" dulu
    document.querySelectorAll('form[data-confirm]').forEach(function (f) {
        f.addEventListener('submit', function (ev) {
            // Kalau user pilih Batal, batalkan pengiriman form
            if (!window.confirm(f.dataset.confirm)) ev.preventDefault();
        });
    });

    // ---------- PESAN FLASH HILANG SENDIRI ----------
    var flash = document.querySelector('.flash');
    if (flash) setTimeout(function () { flash.classList.add('flash-hide'); }, 4500); // 4,5 detik lalu memudar
})();
