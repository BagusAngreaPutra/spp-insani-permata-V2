# Rencana Dompet Digital Siswa

Dokumen ini adalah sumber memori proyek untuk implementasi dompet digital siswa. Setiap eksekusi AI harus memperbarui bagian status dan mencatat migration, test, serta keputusan yang dibuat.

## Tujuan

Dompet internal sekolah dipakai siswa untuk membayar tagihan SPP, tagihan sekolah lain, dan transaksi koperasi. Payment gateway hanya menjadi jalur pengisian saldo. Ledger internal menjadi sumber kebenaran saldo.

Dompet tidak menyediakan transfer antar siswa, tarik tunai, atau pembayaran di luar lingkungan sekolah pada tahap awal.

## Keputusan arsitektur

- Framework: Laravel 10, PHP 8.1.
- Identitas pemilik wallet: `siswa`.
- Saldo resmi: jurnal immutable pada tabel wallet ledger.
- `available_balance` dan `held_balance` adalah cache yang harus konsisten dengan ledger.
- Pembayaran lama di tabel `pembayaran` tetap berjalan.
- Pembayaran wallet memakai `metode_bayar = dompet` dan `transaction_id` dari jurnal wallet.
- Pembayaran koperasi akan mendukung metode `dompet` pada tahap integrasi koperasi.
- Gateway diakses melalui adapter agar vendor dapat diganti.
- Top up dikredit hanya setelah webhook gateway terverifikasi dan diproses idempotent.

## Status eksekusi

- [x] Tahap 1: audit sistem pembayaran dan integrasi.
- [x] Tahap 2: migration database wallet.
- [ ] Tahap 3: model dan relasi Eloquent.
- [ ] Tahap 4: `WalletLedgerService`.
- [ ] Tahap 5: adapter payment gateway sandbox.
- [ ] Tahap 6: top up siswa.
- [ ] Tahap 7: pembayaran tagihan dengan wallet.
- [ ] Tahap 8: pembayaran koperasi dengan wallet.
- [ ] Tahap 9: dashboard siswa.
- [ ] Tahap 10: dashboard admin dan rekonsiliasi.
- [ ] Tahap 11: security hardening.
- [ ] Tahap 12: rekonsiliasi harian.
- [ ] Tahap 13: uji sandbox dan pilot.

## Hasil audit tahap 1

- Aplikasi menggunakan login siswa melalui guard `auth:siswa` dan admin melalui guard `auth:web`.
- Tagihan berada di tabel `tagihan` dan berelasi ke siswa melalui `siswa_id`.
- Riwayat pembayaran berada di tabel `pembayaran`, sudah memiliki `transaction_id`, `metode_bayar`, nominal, diskon, periode, dan nomor kwitansi.
- Pembayaran manual diproses oleh `TagihanController` dan `PembayaranController`.
- Transaksi koperasi berada di `koperasi_penjualan`; saat ini metode pembayaran yang digunakan adalah tunai dan transfer.
- Koperasi mengubah stok dan menyimpan transaksi dalam database transaction, sehingga integrasi wallet harus mengunci wallet dan stok secara bersamaan.
- Risiko utama yang harus dicegah: webhook ganda, pembayaran tagihan ganda, saldo negatif, dan saldo terpotong ketika transaksi koperasi gagal.
- Integrasi wallet sebaiknya melalui service terpusat dan tidak menulis saldo langsung dari controller.

## Hasil tahap 2

Migration wallet dibuat pada:

- `2026_11_12_000001_create_wallet_accounts_table.php`
- `2026_11_12_000002_create_wallet_journals_table.php`
- `2026_11_12_000003_create_wallet_ledger_entries_table.php`
- `2026_11_12_000004_create_wallet_topups_table.php`
- `2026_11_12_000005_create_wallet_payments_table.php`
- `2026_11_12_000006_create_wallet_holds_table.php`
- `2026_11_12_000007_create_payment_gateway_events_table.php`
- `2026_11_12_000008_create_wallet_reconciliations_table.php`

Migration ini belum mengubah tabel `tagihan`, `pembayaran`, atau `koperasi_penjualan`.

## Aturan eksekusi berikutnya

1. Baca dokumen ini sebelum mengerjakan tahap baru.
2. Kerjakan hanya tahap yang diminta.
3. Jangan menghapus migration lama.
4. Semua perubahan saldo harus memakai database transaction dan row lock.
5. Setelah setiap tahap, perbarui checklist dan hasil test di dokumen ini.
