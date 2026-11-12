# Audit tampilan web Permata Insani

> Dokumen ini mencatat kondisi sebelum perbaikan. Tindak lanjut dan bukti pengujian tersedia pada [hasil perbaikan tampilan](HASIL-PERBAIKAN-TAMPILAN-2026-10-21.md).

Tanggal: 21 Oktober 2026  
Alamat: http://127.0.0.1:8000  
Alat: plugin Playwright (`@playwright/mcp`, dijalankan melalui CLI), browser Chromium.

## Hasil umum

Pemeriksaan mencakup 62 halaman/varian halaman pada desktop 1440 × 900 dan mobile 390 × 844, yaitu 124 kombinasi utama. Tiga halaman berisiko diperiksa lagi pada tablet 1024 × 768. Pemeriksaan tambahan mencakup riwayat siswa dengan data terisi, pencarian Tagihan, navigasi sidebar, serta membuka dan menutup modal Buat Tagihan.

Semua halaman dalam cakupan utama memberikan HTTP 200. Tidak ditemukan error JavaScript pada halaman yang diuji, error konsol browser, atau gambar gagal dimuat pada pemeriksaan utama. Namun, respons 200 dan lebar dokumen normal tidak berarti layout benar: beberapa konten terpotong oleh `overflow: hidden` sehingga tidak menimbulkan scrollbar halaman.

Kode aplikasi tidak diperbaiki dalam audit ini. Berkas baru hanya berupa laporan, skrip pemeriksaan, metadata, dan screenshot di `.playwright-mcp/`. Login dan pembukaan halaman dapat tercatat pada log aktivitas bawaan aplikasi. Formulir penyimpanan, penghapusan, pembayaran, kenaikan kelas, dan kelulusan tidak dikirim.

## Temuan prioritas tinggi

### 1. Pencarian Tagihan di mobile tidak menyembunyikan siswa yang tidak cocok

- Halaman: `/tagihan/grouped`, mobile.
- Reproduksi: ketik `Nisa Rizqia` pada pencarian siswa.
- Hasil: penghitung berubah menjadi **1**, tetapi **193 kartu siswa tetap tampil**. Sebanyak 192 baris memiliki `hidden=true` tetapi tetap memiliki tinggi dan tetap terlihat. Pada desktop, kueri yang sama menampilkan satu baris dengan benar.
- Dampak: pengguna tetap melihat siswa lain beserta tombol Bayar ketika sedang mencari satu siswa tertentu.
- Penyebab terkonfirmasi: aturan kartu tabel mobile menggunakan `display: block !important` pada seluruh baris dan mengalahkan atribut `hidden`. Lihat [permata-system.css](public/css/permata-system.css#L8037); kode pencarian menggunakan `row.hidden` pada [index_new.blade.php](resources/views/tagihan/index_new.blade.php#L1222).
- Arah perbaikan: pertahankan perilaku `[hidden]` pada tabel responsif, kemudian uji pencarian dan filter status pada desktop serta mobile.
- Bukti: [mobile setelah pencarian](.playwright-mcp/finding-billing-search-mobile.png), [pembanding desktop](.playwright-mcp/finding-billing-search-desktop.png), [hasil pengukuran](.playwright-mcp/audit-search-results.json).

### 2. Tabel Tagihan di tablet memotong kolom kanan dan tombol pembayaran

- Halaman: `/tagihan/grouped`, tablet 1024 × 768.
- Hasil: tabel selebar **1040 px** berada dalam wadah selebar **699 px**. Wadah tabel memiliki `overflow: visible`, sedangkan panel luarnya `overflow: hidden`. Kolom Dibayar, Sisa, dan aksi di kanan tidak tersedia melalui scrollbar horizontal.
- Dampak: pengguna tablet tidak bisa membaca seluruh nilai dan menjangkau tombol pembayaran dari tampilan normal.
- Lokasi terkait: minimum lebar tabel pada [admin-layout-fixes.css](public/css/admin-layout-fixes.css#L114) dan aturan `.billing-table-wrap`/`.billing-panel`.
- Arah perbaikan: sediakan scroll horizontal pada wadah yang benar atau gunakan breakpoint kartu sebelum ruang tabel terlalu sempit.
- Bukti: [tabel tablet terpotong](.playwright-mcp/finding-tagihan-tablet-table.png).

### 3. Kwitansi di mobile memotong tombol aksi dan dokumen

- Halaman: `/koperasi/penjualan/6/kwitansi` dan `/pembayaran/kwitansi-grup?ids=425%2C424`.
- Hasil: sisi kanan tombol Koreksi, Kembali, Cetak, PDF, dan pembatalan terpotong. Tombol yang diukur membentang dari x=14 sampai sekitar x=729 pada viewport 390 px. Isi dokumen juga melewati sisi kanan layar, dan terdapat ruang kosong besar sebelum tombol.
- Dampak: label serta area tombol tidak sepenuhnya terbaca/dijangkau; pratinjau tidak dapat diperiksa utuh di ponsel.
- Lokasi terkait: `.receipt-screen-heading`, `.receipt-actions`, dan `.receipt-paper` pada [receipt.blade.php](resources/views/tagihan/kwitansi/receipt.blade.php#L24), serta aturan pada [transaction-revisions.css](public/css/transaction-revisions.css#L56). Ada beberapa aturan CSS yang saling menimpa; penyebab lengkap perlu ditangani bersama ketika memperbaiki layout.
- Arah perbaikan: pisahkan lebar toolbar dari ukuran dokumen cetak, batasi ukuran grid/flex, dan sediakan pratinjau dokumen yang bisa diskalakan atau digeser secara sengaja.
- Bukti: [kwitansi pembayaran mobile](.playwright-mcp/detail-pembayaran-kwitansi-grup-ids-425-2C424-mobile.png), [kwitansi koperasi mobile](.playwright-mcp/detail-koperasi-penjualan-6-kwitansi-mobile.png).

### 4. Profil siswa di mobile memotong nilai identitas

- Halaman: `/siswa/profil`.
- Hasil: tabel berukuran **680 px**, dari x=46 sampai x=726. Sel nilai dimulai sekitar x=352 sehingga nama, NIS, dan kelas hampir seluruhnya berada di luar layar. Tidak ada wadah scroll horizontal pada tabel profil.
- Penyebab terkonfirmasi: aturan tabel mobile global memberikan `min-width: 680px !important`; tabel profil tidak menggunakan pola kartu responsif atau wadah scroll. Lihat [permata-system.css](public/css/permata-system.css#L1103) dan [profil siswa](resources/views/siswa/profil/index.blade.php#L96).
- Arah perbaikan: gunakan pasangan label–nilai yang menumpuk di mobile dan batasi aturan minimum lebar tabel global.
- Bukti: [profil siswa mobile](.playwright-mcp/portal-profil-mobile.png), [pembanding desktop](.playwright-mcp/portal-profil-desktop.png).

## Temuan prioritas sedang

### 5. Profil admin kehilangan layout dan navigasi aplikasi

- Halaman: `/profile`, desktop dan mobile.
- Sidebar, header aplikasi, dan tautan kembali tidak tampil. Formulir melebar hampir memenuhi halaman. Bahasa juga bercampur: Profile Information, Save, Perbarui Kata Sandi, dan Hapus Akun.
- Halaman memakai `<x-app-layout>` tanpa menyertakan sidebar/header dan pembungkus konten seperti halaman admin lain. Utility class Tailwind yang dipakai halaman ini juga tidak menghasilkan layout yang diharapkan pada layout saat ini.
- Arah perbaikan: integrasikan halaman profil dengan layout admin dan konsistenkan bahasa.
- Bukti: [desktop](.playwright-mcp/audit-profile-desktop.png), [mobile](.playwright-mcp/audit-profile-mobile.png). Sumber: [profile/edit.blade.php](resources/views/profile/edit.blade.php).

### 6. Pesan “Siswa tidak ditemukan” tetap tampil ketika daftar berisi data

- Halaman: `/tagihan/grouped`, desktop dan mobile.
- Di bawah 193 siswa, pesan kosong tetap ditampilkan, diikuti keterangan “193 dari 193 siswa ditampilkan”.
- Penyebab terkonfirmasi: `#billingNoResults` memiliki `hidden=true`, tetapi aturan `.billing-empty` memaksakan `display: grid !important`. Lihat [permata-system.css](public/css/permata-system.css#L5334) dan [index_new.blade.php](resources/views/tagihan/index_new.blade.php#L1115).
- Arah perbaikan: hormati atribut `hidden` untuk empty state dan periksa hasil nol serta hasil berisi.
- Bukti: [pesan kosong di bawah data](.playwright-mcp/finding-tagihan-false-empty-mobile.png).

### 7. Angka keuangan penting terpotong dengan elipsis di mobile

- Halaman: `/tagihan/grouped` dan `/tagihan/proses-siswa/241?tahun_ajaran=5`.
- Kartu Sisa tagihan menampilkan angka seperti `Rp24.731.416...`; kartu Total nominal pada detail pembayaran juga memotong nilai. Nominal lengkap tidak terbaca langsung.
- Penyebab terkait: dua kolom kartu yang sempit, ruang ikon/padding, dan `white-space: nowrap`/`text-overflow: ellipsis` pada nilai. Lihat `.billing-stat-value` pada [index_new.blade.php](resources/views/tagihan/index_new.blade.php#L238).
- Arah perbaikan: utamakan keterbacaan nominal penuh, misalnya satu kolom pada layar kecil atau ukuran teks dan penempatan ikon yang menyesuaikan ruang.
- Bukti: [ringkasan Tagihan mobile](.playwright-mcp/audit-tagihan-grouped-mobile.png), [detail pembayaran mobile](.playwright-mcp/detail-tagihan-proses-siswa-241-tahun_ajaran-5-mobile.png).

### 8. Judul dan penjelasan terjepit oleh elemen di sebelahnya

- `/tahun_ajaran`: “Daftar Tahun Ajaran” pecah menjadi tiga baris sempit karena tombol Tambah Tahun Ajaran masih mengambil ruang di sisi kanan.
- `/pembayaran/koreksi/425`: badge referensi transaksi yang panjang membuat bagian “Transaksi saat ini” dan penjelasannya menjadi kolom sangat sempit.
- Arah perbaikan: tumpuk judul dan tombol/badge pada mobile; izinkan referensi panjang membungkus atau berada pada baris terpisah. Lokasi terkait: [header tahun ajaran](resources/views/tahun_ajaran/index.blade.php#L33) dan `.correction-panel-head`/`.correction-reference` pada [koreksi.blade.php](resources/views/riwayat/koreksi.blade.php#L39).
- Bukti: [Tahun Ajaran mobile](.playwright-mcp/audit-tahun_ajaran-mobile.png), [Koreksi mobile](.playwright-mcp/detail-pembayaran-koreksi-425-mobile.png).

### 9. Daftar panjang menjadi ratusan layar tanpa pembagian halaman

Ini merupakan masalah kemudahan penggunaan, bukan error pemuatan. Pengukuran mobile pada data yang tersedia:

| Halaman | Jumlah baris tabel | Tinggi dokumen | Pagination terdeteksi |
| --- | ---: | ---: | --- |
| Daftar Siswa | 193 | 83.401 px | Tidak |
| Laporan Tagihan Siswa | 351 | 192.051 px | Tidak |
| Laporan Semua Transaksi | 177 | 87.965 px | Tidak |
| Tagihan Siswa | 193 | 67.443 px | Tidak |

- Dampak: pencarian visual, akses ke bagian akhir, dan perpindahan antardata sulit di ponsel. Laporan Tagihan Siswa melebihi 220 tinggi viewport pengujian.
- Query daftar mengambil seluruh hasil menggunakan `get()`, misalnya pada [SiswaController.php](app/Http/Controllers/SiswaController.php#L65) dan [LaporanPembayaranController.php](app/Http/Controllers/Laporan/LaporanPembayaranController.php#L81).
- Arah perbaikan: pertimbangkan pagination atau pemuatan bertahap untuk tampilan layar, dengan ekspor/cetak tetap mencakup keseluruhan hasil filter.

## Temuan ringan

10. **Ubah Password siswa belum konsisten dengan layout lain.** Judul dan form menempel pada batas kiri konten, input memenuhi hampir seluruh lebar desktop, dan tidak ada tombol kembali yang jelas. Halaman menggunakan utility class Tailwind seperti `p-6` dan `max-w-lg` pada layout yang tidak menampilkan hasil spacing tersebut. Bukti: [desktop](.playwright-mcp/portal-profil-edit-password-desktop.png), [mobile](.playwright-mcp/portal-profil-edit-password-mobile.png). Sumber: [edit-password.blade.php](resources/views/siswa/profil/edit-password.blade.php).

11. **Ikon Ringkasan pada sidebar siswa kosong.** Menu lain memiliki ikon, sedangkan Ringkasan hanya menyisakan ruang kosong. Elemen memakai `fas fa-grid-2`. Gunakan ikon yang tersedia pada Font Awesome yang dimuat. Bukti: [sidebar siswa desktop](.playwright-mcp/portal-dashboard-desktop.png). Sumber: [sidebar-siswa.blade.php](resources/views/layouts/sidebar-siswa.blade.php#L17).

## Cakupan halaman

| Kelompok | Halaman yang dibuka pada desktop dan mobile | Hasil pemuatan |
| --- | --- | --- |
| Publik (3) | `/`, `/login`, `/siswa/login` | Semua 200 |
| Menu admin dan halaman pendukung (27) | `/tagihan/grouped`, `/koperasi/penjualan`, `/pemasukan`, `/sekolah`, `/tahun_ajaran`, `/siswa`, `/admin`, `/jenis_pembayaran`, `/riwayat`, `/pengeluaran`, `/keuangan-kas`, `/kenaikan-kelas`, `/kelulusan-kelas`, `/laporan/transaksi`, `/laporan/tagihan-siswa`, `/laporan/koperasi`, `/laporan/pemasukan`, `/pengaturan/diskon-spp`, `/pengaturan/kwitansi`, `/log-aktivitas`, `/import_excel`, `/export-excel`, `/backup`, `/profile`, `/dashboard`, `/koperasi`, `/kelas` | Semua 200 |
| Formulir/detail admin (27) | Tambah/edit/detail sekolah; tambah/edit tahun ajaran; tambah/edit kelas; tambah/edit/detail siswa; tambah/edit admin; tambah/edit jenis pembayaran; tambah/edit/stok barang; tambah/koreksi/detail/kwitansi penjualan koperasi; tambah pemasukan; tambah pengeluaran; proses pembayaran siswa; koreksi pembayaran; kwitansi gabungan; detail aktivitas | Semua 200 |
| Portal siswa (5) | `/siswa/dashboard`, `/siswa/tagihan`, `/siswa/riwayat-pembayaran`, `/siswa/profil`, `/siswa/profil/edit-password` | Semua 200 |

Halaman edit/detail diperiksa menggunakan contoh record yang tersedia, bukan setiap record dalam database. Riwayat siswa diuji dalam kondisi kosong dan berisi empat pembayaran. Pemeriksaan desktop dan mobile memakai resize viewport browser, bukan perangkat ponsel fisik. PDF hasil unduhan dan pengiriman form tidak termasuk pengujian ini.

## Interaksi yang lolos

- Login admin dan siswa.
- Membuka hamburger menu mobile.
- Mencari menu “kwitansi” dan membuka submenu yang ditemukan.
- Pesan pencarian menu tanpa hasil.
- Menutup sidebar lewat overlay dan sidebar tertutup setelah navigasi.
- Navigasi menu siswa ke Tagihan.
- Membuka dan menutup modal Buat Tagihan; modal berada dalam lebar viewport mobile.
- Riwayat pembayaran siswa dengan data kosong dan data terisi dapat dibuka.

## Bukti dan urutan perbaikan

Screenshot tersimpan di [folder bukti](.playwright-mcp/). Berkas `audit-*.png` berisi menu utama, `detail-*.png` formulir/detail, `portal-*.png` portal siswa, dan `finding-*.png` reproduksi masalah. `sheet-*` dan `detailsheet-*` adalah lembar kontak untuk peninjauan. Metadata tambahan tersedia pada [hasil detail](.playwright-mcp/audit-details-results.json), [hasil portal siswa](.playwright-mcp/audit-student-results.json), dan [hasil pencarian](.playwright-mcp/audit-search-results.json).

Urutan yang disarankan: perbaiki perilaku `hidden` dan tabel Tagihan terlebih dahulu; lanjutkan layout kwitansi dan profil siswa; kemudian integrasikan profil admin, tampilkan nominal lengkap, rapikan header mobile, dan batasi panjang daftar pada layar.
