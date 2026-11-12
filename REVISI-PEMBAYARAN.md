# Revisi pembayaran 14 Oktober 2026

Cadangan kode dan database sebelum revisi:
`D:\Kerjoan\5. Coding\SPP-backup-sebelum-revisi-20261014`

Cadangan mempertahankan perubahan lokal yang sudah ada sebelum revisi, termasuk file yang belum di-commit. File pribadi, lampiran pembayaran, serta arsip ZIP/SQL di folder proyek tidak dihapus atau dipindahkan.

## Mencoba revisi

- Riwayat Transaksi: buka rincian transaksi untuk **Koreksi transaksi** atau **Batalkan / Hapus**. Koreksi mendukung siswa, tanggal pembayaran/kwitansi, metode, keterangan, dan nominal tiap item. Pembatalan berlaku untuk seluruh kwitansi gabungan; alasan wajib diisi.
- Koperasi: transaksi baru memilih **Tunai (TN)** atau **Transfer (TF)**. Detail transaksi menyediakan koreksi barang/jumlah, siswa, tanggal, metode dan catatan, serta pembatalan. Harga barang yang sudah ada tetap memakai harga transaksi asal; barang tambahan memakai harga saat ini. Stok dan total kas dihitung ulang.
- Pengaturan data → **Pengaturan kwitansi**: pilih nomor berlanjut tahunan atau mulai 1 setiap bulan; pilih apakah nama petugas ditampilkan. Default mempertahankan urutan tahunan dan tidak menampilkan nama. Urutan berdasarkan tanggal penerbitan nomor, bukan tanggal transaksi yang dimundurkan. Nomor kwitansi lama tidak diubah. Nomor bulanan menyertakan bulan; nomor yang dibatalkan tidak dipakai ulang.
- Nama petugas memakai nama akun saat input, termasuk ketika kwitansi dicetak oleh akun lain. Transaksi sebelum revisi belum menyimpan petugas; label umum tetap dipakai.
  Halaman pratinjau kwitansi siswa maupun koperasi juga menyediakan tombol koreksi dan pembatalan. Alasan pembatalan diisi melalui dialog setelah tombol diklik; kontrol tidak ikut tercetak.
- Kwitansi Karate/TPQ menyebut bulan/tahun tagihan. Laporan SPP memakai nama bulan dan tahun ajaran tanpa angkatan.
- Laporan **Semua Transaksi**: tabel utama memakai 9 kolom. Sekolah/kelas berada di bawah siswa, jenis transaksi di bawah referensi, serta jumlah item dan catatan berada pada rincian. TN, TF, KJC, tabungan dan **Belum dicatat** ditampilkan dalam ringkasan dana masuk yang ikut tercetak. Metode koperasi lama bisa dilengkapi melalui koreksi.
- Ekspor Excel memakai 9 kolom pada laporan utama dan tab **Jenis Dana** untuk rincian dana per transaksi beserta subtotalnya. Cetak Excel memakai A4 landscape, satu halaman lebar, dengan header berulang pada halaman selanjutnya.
- Ringkasan laporan dan subtotal metode pembayaran memakai satu panel ringkas dengan dua baris. Label kecil berada di atas angka, pemisah tipis mengatur jarak, total diterima diberi aksen hijau, dan metode memiliki penanda warna. Layar kecil memakai dua kolom; cetak mempertahankan strip teks padat. Pola ini juga berlaku pada laporan tagihan siswa.
- Kartu ringkasan pada menu admin dibuat lebih pendek dengan ikon kecil, angka yang tetap terbaca, dan jarak seragam. Filter sekolah, kelas, tahun, pencarian, serta tombol tindakan disusun dalam baris yang ringkas; filter khusus tetap dapat dibuka saat diperlukan. Penyesuaian ini hanya menyentuh tampilan kartu ringkasan dan filter di layar, tanpa mengubah proses transaksi, tabel, atau tata letak cetak.
- Kolom aksi admin memakai lebar tombol sesuai teks dan jarak seragam. Empat aksi penjualan koperasi disusun dua baris, tanpa scrollbar kecil atau teks terpotong; tombol koreksi dan kwitansi mendapat ikon yang sesuai. Username admin yang panjang membungkus di dalam kolom, label jumlah siswa tetap utuh, dan filter sekolah memakai lebar penuh pada layar kecil. Tabel tagihan di layar sedang dapat digeser agar nominal dan tombol pembayaran tidak bertumpuk.
- Font Manrope disimpan di `public/fonts/manrope` beserta lisensinya, sehingga tampilan tidak bergantung pada pengunduhan font dari Google. Laporan layar dan cetak memakai font yang sama; tombol Print menunggu font selesai dimuat.
- Menu **Catatan Kas Keluar** dan **Ringkasan Kas** dikembalikan, mengikuti izin akun. Kolom catatan pengeluaran ditampilkan kembali.
- Pembatalan menyembunyikan transaksi dari riwayat aktif, kwitansi, dan perhitungan kas; data asal serta alasan tetap tersimpan. Jejak tindakan dapat dilihat melalui **Aktivitas**.

## Kembali ke versi sebelum revisi

Dari PowerShell di folder proyek, periksa cadangan tanpa mengubah apa pun:

```powershell
.\scripts\pulihkan-sebelum-revisi.ps1 -CekSaja
```

Untuk benar-benar memulihkan:

```powershell
.\scripts\pulihkan-sebelum-revisi.ps1 -KonfirmasiPulihkanKodeDanDatabase
```

Pemulihan mengembalikan **kode dan database ke kondisi sebelum revisi**. Transaksi/koreksi yang dibuat setelah cadangan tidak muncul di versi lama. Skrip membuat cadangan kondisi terbaru terlebih dahulu di folder `SPP-backup-sebelum-pemulihan-*`, menjalankan mode pemeliharaan, memulihkan hanya file yang diubah revisi, dan membersihkan cache. Skrip tidak melakukan `git reset` atau menghapus folder proyek. Pemulihan penuh belum dijalankan pada database aktif karena revisi sedang digunakan.

## Pemasangan di lingkungan lain

Jalankan hanya migrasi revisi ini setelah mencadangkan database:

```powershell
php artisan migrate --path=database/migrations/2026_10_14_000000_add_receipt_and_transaction_revision_fields.php
php artisan optimize:clear
```

Migrasi lama `2026_09_14_000000_repair_legacy_transaction_consistency` yang sudah ada sebelum revisi tidak dijalankan pada database lokal dalam pekerjaan ini.

## Hasil pemeriksaan

- 20 pengujian unit dan 16 pengujian fitur terkait lulus. Pengujian transaksi berjalan di SQLite dalam memori, tanpa mengoreksi atau membatalkan transaksi nyata.
- 12 halaman admin/kwitansi/formulir berhasil dirender dengan database lokal (HTTP 200).
- Sintaks PHP, cache template Blade, dan integritas cadangan berhasil diperiksa. Skrip pemulihan diuji dengan `-CekSaja`; pemulihan penuh belum dijalankan.
- Penyempurnaan laporan diperiksa dengan rendering lokal Chromium: contoh 45 transaksi berisi nama dan rincian panjang dicetak menjadi PDF A4 landscape. Halaman awal, lanjutan dan akhir diperiksa secara visual, termasuk header berulang dan total. Pemeriksaan ini memakai data contoh, bukan transaksi nyata.
- Penyempurnaan kartu/filter berhasil dirender pada 14 halaman admin (HTTP 200), dengan pemeriksaan visual halaman utama, menu data, tagihan, riwayat dan koperasi, termasuk tampilan sempit dan filter khusus terbuka.
- Pemeriksaan lanjutan tampilan admin mencakup 39 halaman menu/formulir/detail (HTTP 200). Susunan tombol diperiksa melalui rendering Chromium pada lebar 1440, 1024 dan 480 px; pemeriksaan visual mencakup penjualan koperasi, guru/admin, sekolah, siswa, jenis pembayaran, tahun ajaran, stok koperasi serta menu sistem. Tidak ada transaksi nyata yang dikirim atau dibatalkan untuk pemeriksaan ini.
