# Perbaikan filter, ringkasan, dan pagination

Tanggal: 21 Oktober 2026. Lingkungan lokal: http://127.0.0.1:8000.

Pembaruan pencarian: Tagihan kini mencari otomatis saat mengetik dengan jeda penggabungan ketikan 180 ms, tanpa reload halaman. Tombol X mengosongkan pencarian dan memperbarui tabel/ringkasan di tempat. Enter/tombol Cari tetap tersedia untuk mengirim segera. Detail verifikasi: [pencarian langsung](.playwright-mcp/verification-billing-live-search.json). Keterangan pencarian eksplisit di bawah mencatat implementasi sebelum pembaruan ini.

## Hasil implementasi

- Tagihan: pencarian, status, sekolah, kelas, dan tahun ajaran diterapkan di server sebelum ringkasan dihitung. Pencarian satu siswa menampilkan satu siswa pada kartu, toolbar, dan tabel. Ringkasan mencakup seluruh hasil filter, bukan hanya halaman yang terbuka.
- Pilihan sekolah/kelas/tahun/status tidak langsung reload. Pengguna mengatur beberapa pilihan lalu menekan **Terapkan filter**. Daftar kelas tetap mengikuti sekolah. Pencarian dikirim dengan Enter atau tombol ikon pencarian; tombol hapus mengembalikan hasil tanpa pencarian.
- Label keuangan diperjelas menjadi **Sisa jatuh tempo**, dengan informasi sisa belum jatuh tempo dan potongan. Aturan periode, diskon, pelunasan, serta tanggal jatuh tempo dipertahankan.
- Pagination database pada empat daftar: Tagihan Siswa, Siswa, Laporan Tagihan Siswa, dan Laporan Semua Transaksi. Halaman normal mengambil detail 10/25/50/100 baris; bukan lagi mengirim seluruh baris lalu menyembunyikannya di browser. Ukuran default 25.
- Pengurutan dilakukan di server, pilihan filter/ukuran tetap terbawa ketika pindah halaman, dan nomor baris berlanjut antarlaman. Parameter ukuran dan pengurutan dibatasi; nomor halaman di luar rentang disesuaikan.
- Ringkasan laporan tetap mencakup seluruh hasil filter. Satu kelompok transaksi pembayaran tidak terpecah akibat pagination laporan Semua Transaksi.
- **Cetak semua hasil** membuka mode khusus seluruh hasil filter. Ekspor Excel tetap seluruh hasil filter, tidak terbatas halaman layar.

## Verifikasi

- Playwright: empat daftar, pindah halaman, sort, ukuran halaman, pencarian satu siswa/kosong, penghapusan pencarian, filter gabungan, batas parameter, dan cetak lengkap lulus. Respons berukuran 10 benar-benar hanya memuat 10 baris data.
- Seluruh empat daftar diuji pada lebar 1440, 1024, 390, dan 320 px: tanpa overflow horizontal halaman maupun kontrol toolbar/footer terpotong pada pemeriksaan ini.
- Refresh Tagihan dengan JavaScript ditahan: 46 sampel frame per ukuran 1440/390/320 px; tidak ada frame layout lama, toolbar tidak dibangun ulang, pergeseran toolbar 0 px. Fallback ketika skrip gagal juga lulus.
- Perhitungan SQL Tagihan dibandingkan dengan perhitungan sebelumnya pada **772 kombinasi siswa–tahun ajaran**: jumlah tagihan, nominal, pembayaran, diskon, sisa, dan status sesuai.
- Ringkasan Semua Transaksi dibandingkan dengan alur sebelumnya pada **8 kombinasi filter**: sesuai. Empat berkas ekspor diuji jumlah baris dan totalnya: Semua Transaksi 177/3 hasil, Laporan Tagihan 351/7 hasil, termasuk ketika parameter halaman kedua dikirim.
- Tes baru `ServerPaginationTest`: **3 passed, 44 assertions**, memakai SQLite memori dengan fixture mandiri. Unit suite: **20 passed, 34 assertions**. PHP/JavaScript syntax, cache Blade, dan `git diff --check` lulus.
- HTML Tagihan default 25 baris: **141.652 byte**, dibanding pengukuran audit sebelumnya sekitar **658.356 byte** (turun sekitar 78%). Ini pengurangan payload HTML, bukan klaim peningkatan kecepatan 78% atau hasil uji beban. Query ringkasan tetap mengagregasi seluruh cakupan filter.

## Bukti dan batas cakupan

- [Hasil interaksi Playwright](.playwright-mcp/verification-server-pagination.json)
- [Hasil pemeriksaan refresh](.playwright-mcp/verification-server-pagination-first-paint.json)
- [Tagihan mobile](.playwright-mcp/server-pagination-tagihan-390.png)
- [Ringkasan satu siswa](.playwright-mcp/server-search-summary-mobile.png)
- [Footer pagination mobile](.playwright-mcp/server-pagination-footer-mobile.png)

Tidak dilakukan penyimpanan pembayaran, penghapusan akun, atau perubahan data keuangan pada database lokal. Temuan keamanan akun, pembatasan login, identitas mobile yang sempit, dan toolbar menu lain yang masih direkonstruksi JavaScript bukan bagian perubahan ini. Fixture `UiSmokeTest` lama yang belum lengkap juga belum diperbaiki; hasil tes di atas tidak berarti seluruh feature suite lama lulus.
