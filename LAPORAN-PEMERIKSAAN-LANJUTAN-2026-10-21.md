# Pemeriksaan lanjutan web Permata Insani

Tanggal: 21 Oktober 2026. Lingkungan lokal: http://127.0.0.1:8000.

Pembaruan setelah audit: cakupan ringkasan/pencarian, penerapan filter bersama, dan pagination server telah ditangani. Lihat [hasil implementasi dan verifikasi](HASIL-FILTER-PAGINATION-SERVER-2026-10-21.md). Temuan di bawah merupakan catatan kondisi saat audit; tidak semua temuan telah diperbaiki.

## Cakupan dan hasil yang baik

31 halaman utama diperiksa dengan Playwright pada desktop 1440 × 900 dan mobile 390 × 844 (62 kombinasi): halaman publik, menu admin, laporan, pengaturan, serta portal siswa. Semuanya HTTP 200, tanpa error JavaScript, gambar rusak, atau overflow horizontal halaman pada pemeriksaan ini. Pengukuran ini tidak berarti setiap detail UI sudah sempurna.

Sesi tanpa login diuji secara terpisah: Tagihan admin, dashboard siswa, dan Backup diarahkan ke halaman login masing-masing. Login admin dan siswa terbuka normal. Tidak dilakukan percobaan brute force, transaksi pembayaran, penghapusan, perubahan akun, atau pengiriman formulir penyimpanan. Tidak ada kode aplikasi yang diubah dalam audit ini; berkas baru hanya laporan, metadata pemeriksaan, dan screenshot.

## Temuan prioritas tinggi: keamanan akun

### 1. Salinan kata sandi siswa disimpan dalam teks asli

- Query agregat read-only menemukan **193 akun** dengan `password_raw` terisi. Tidak ada isi kata sandi yang ditampilkan atau dicantumkan dalam bukti.
- Alur tambah/perbarui siswa menyimpan input kata sandi langsung ke kolom tersebut: [SiswaController.php](app/Http/Controllers/SiswaController.php:129), [pembaruan siswa](app/Http/Controllers/SiswaController.php:246), dan [perubahan kata sandi oleh siswa](app/Http/Controllers/Siswa/ProfileSiswaController.php:67).
- Model tidak mengenkripsi atribut ini, dan atribut tersebut tidak masuk daftar `$hidden`: [Siswa.php](app/Models/Siswa.php:54). Login juga menerima salinan tersebut sebagai fallback: [AuthController.php](app/Http/Controllers/Siswa/AuthController.php:30).
- Dampak: kebocoran database/backup berpotensi mengungkap kata sandi asli. Ini temuan penyimpanan, bukan bukti sudah terjadi kebocoran.
- Arah perbaikan: migrasi ke hash saja, hapus kebutuhan membaca/mengekspor kata sandi asli, serta sediakan reset kata sandi yang aman. Penghapusan salinan memerlukan rencana kompatibilitas akun lama terlebih dahulu.

### 2. Login aktif belum membatasi percobaan gagal di tingkat aplikasi

- `route:list --path=login -v` menunjukkan POST `/login` dan `/siswa/login` hanya memakai middleware `web`, tanpa `throttle`.
- Controller yang benar-benar digunakan tidak menjalankan `RateLimiter`: [login admin](app/Http/Controllers/Auth/AdminAuthController.php:25), [login siswa](app/Http/Controllers/Siswa/AuthController.php:19), dan [registrasi route](routes/web.php:45).
- Tersedia kelas `LoginRequest` yang mempunyai limiter, tetapi bukan jalur yang dipakai kedua controller tersebut.
- Dampak: tidak ada pembatasan percobaan kata sandi yang terkonfirmasi pada jalur login lokal. Perlindungan reverse proxy/hosting tidak termasuk pemeriksaan ini.
- Arah perbaikan: pembatasan berbasis akun dan IP, jeda percobaan, serta pencatatan kegagalan tanpa menyimpan kata sandi.

## Temuan tampilan dan penggunaan

### 3. Kedipan layout lama masih ada di menu Siswa

- Perbaikan pemuatan awal Tagihan Siswa belum diterapkan ke seluruh menu.
- Pada `/siswa`, skrip sengaja ditahan untuk memeriksa tampilan sebelum inisialisasi: form lama setinggi **90,19 px** dan **193 baris** terlihat. Setelah JavaScript berjalan, form berubah menjadi toolbar **36 px** dan **25 baris**.
- Ini membuktikan struktur awal dan akhir masih berbeda, sehingga ada potensi kedipan pada refresh, terutama saat skrip terlambat.
- Penyebab: [compact-filters.js](public/js/compact-filters.js:249) membentuk ulang filter setelah DOMContentLoaded, sedangkan pagination juga baru dijalankan di browser.
- Arah perbaikan: perluas toolbar server-rendered dan kesiapan tabel ke menu lain, tanpa membuat halaman kosong apabila JavaScript gagal.
- Bukti: [sebelum JavaScript](.playwright-mcp/reaudit-siswa-before-js.png), [setelah JavaScript](.playwright-mcp/reaudit-siswa-after-js.png), [metadata](.playwright-mcp/reaudit-other-refresh.json).

### 4. Identitas siswa pada kartu Tagihan mobile terlalu sempit

- Pada 390 px, nama `Abil Shidqi Allfarasya` membutuhkan 105 px tetapi hanya mendapat 69 px, sehingga terpotong dengan elipsis. Sekolah dan kelas juga terpotong.
- Dampak: sulit membedakan siswa ketika memilih tombol Bayar; informasi identitas perlu lebih diutamakan.
- Sumber: [aturan elipsis](resources/views/tagihan/index_new.blade.php:480) bersama layout kartu tabel responsif.
- Bukti: [identitas terpotong](.playwright-mcp/reaudit-billing-names-mobile.png).

### 5. Angka ringkasan dan cakupan pencarian belum jelas

- Pencarian `Nisa Rizqia` menghasilkan **1 baris**, toolbar menyatakan **1 data**, tetapi kartu **Siswa ditampilkan** tetap **193**. Label kartu memberi kesan jumlah hasil yang sedang terlihat, padahal datanya mengikuti cakupan sekolah/kelas/tahun sebelum pencarian lokal.
- Label **Sisa tagihan** memakai `sisa_bayar`, yaitu sisa yang sudah jatuh tempo; nominal total mencakup juga tagihan aktif yang belum jatuh tempo. Potongan/diskon juga tidak dijelaskan di ringkasan. Sumber: [perhitungan ringkasan](app/Http/Controllers/TagihanController.php:627) dan [label kartu](resources/views/tagihan/index_new.blade.php:922).
- Ini temuan kejelasan informasi dan cakupan, **bukan bukti perhitungan transaksi salah**.
- Arah perbaikan: seragamkan cakupan penghitung dan pisahkan label sisa jatuh tempo, sisa keseluruhan, serta potongan.
- Bukti: [193 pada ringkasan vs 1 hasil](.playwright-mcp/reaudit-billing-count-mobile.png).

### 6. Filter langsung reload meskipun ada tombol Terapkan filter

- Memilih sekolah pada Tagihan langsung mengirim form, halaman reload, dan panel tertutup, tanpa menekan Terapkan filter. Pemilihan kelas/tahun mempunyai perilaku yang sama.
- Sumber: [event perubahan filter](resources/views/tagihan/index_new.blade.php:1151).
- Dampak: untuk mengatur beberapa pilihan, pengguna perlu membuka panel berulang kali; pola interaksi tidak sesuai tombol yang ditampilkan.
- Arah perbaikan: tentukan satu pola konsisten: penerapan otomatis yang jelas, atau beberapa perubahan dikumpulkan lalu diterapkan bersama. Filter kelas yang bergantung pada sekolah harus tetap dijaga.

## Kinerja dan kesiapan pemeliharaan

### 7. Pagination masih di browser; seluruh hasil tetap dikirim

- HTML Tagihan memuat 193 baris meskipun hanya 10/25 yang ditampilkan. Respons HTML yang diukur sekitar **658 KB**, belum termasuk CSS, JavaScript, dan font.
- Query mengambil seluruh hasil melalui `get()`: [TagihanController.php](app/Http/Controllers/TagihanController.php:618), [SiswaController.php](app/Http/Controllers/SiswaController.php:62), dan alur laporan. Pemisahan halaman pada [table-pagination.js](public/js/table-pagination.js) hanya mengatur visibilitas.
- Dampak: panjang layar sudah berkurang, tetapi transfer, pemrosesan server, dan jumlah node DOM belum berkurang; berpotensi makin terasa ketika data bertambah.
- Arah perbaikan: pagination dan filter di server untuk daftar operasional; kebutuhan cetak/ekspor seluruh data tetap dipisahkan. Belum dilakukan uji beban, sehingga tidak ada klaim kapasitas maksimum.

### 8. Uji integrasi belum bisa menjadi penjaga regresi

- Unit test: **20 passed, 34 assertions**.
- `UiSmokeTest`: **2 passed, 8 failed**. Kegagalan terjadi sebelum halaman diuji karena SQLite `:memory:` belum mempunyai tabel `admin`/`siswa`.
- Sumber: [konfigurasi pengujian](phpunit.xml:24) dan [UiSmokeTest.php](tests/Feature/UiSmokeTest.php:16).
- Ini kekurangan lingkungan/fixture pengujian, bukan delapan kerusakan halaman produksi. Arah perbaikan: skema, factory, dan fixture pengujian yang mandiri.

## Catatan sebelum publikasi

Lingkungan yang diperiksa memang lokal dan memakai debug aktif. Konfigurasi itu wajar untuk pengembangan, tetapi harus ditinjau sebelum publikasi: debug nonaktif, HTTPS/cookie secure, kredensial uji diganti, serta backup dilindungi. Konfigurasi hosting publik belum diperiksa. Bootstrap dan Font Awesome juga masih menggunakan CDN; kesiapan tanpa koneksi internet belum dijamin.

## Metadata pemeriksaan utama

- [31 halaman desktop](.playwright-mcp/reaudit-main-desktop.json)
- [31 halaman mobile](.playwright-mcp/reaudit-main-mobile.json)

Audit ini bukan pengujian penetrasi menyeluruh, audit pembukuan, atau sertifikasi keamanan. Pengiriman pembayaran nyata, penyimpanan perubahan, ekspor berkas, pemulihan backup, dan uji beban tidak dilakukan.
