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
- [x] Tahap 3: model dan relasi Eloquent.
- [x] Tahap 4: `WalletLedgerService`.
- [x] Tahap 5: adapter payment gateway sandbox.
- [x] Tahap 6: top up siswa.
- [x] Tahap 7: pembayaran tagihan dengan wallet.
- [x] Tahap 8: pembayaran koperasi dengan wallet.
- [x] Tahap 9: dashboard siswa.
- [x] Tahap 10: dashboard admin dan rekonsiliasi.
- [x] Tahap 11: security hardening.
- [x] Tahap 12: rekonsiliasi harian.
- [x] Tahap 13: uji sandbox dan pilot.

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

## Hasil tahap 3 dan 4

- Model dibuat untuk akun wallet, jurnal, entry ledger, top up, pembayaran, hold, event gateway, dan rekonsiliasi.
- `Siswa` sekarang memiliki relasi `walletAccount`.
- `WalletLedgerService` menyediakan pembuatan akun, kredit, debit, hold, capture, release, dan refund.
- Operasi saldo menggunakan database transaction, row lock, nominal minor unit, dan idempotency key.
- Setiap jurnal posted menghasilkan entry wallet dan entry akun lawan dengan nominal yang sama.
- Test `tests/Unit/WalletLedgerServiceTest.php` lulus: 2 test, 10 assertions.

## Hasil tahap 5 dan 6

- Kontrak gateway dibuat pada `app/Contracts/PaymentGateway.php`.
- DTO pembayaran gateway dibuat pada `app/Data/GatewayPayment.php`.
- Driver `MidtransGateway` tersedia untuk Snap sandbox dan memverifikasi signature webhook.
- `FakePaymentGateway` tersedia untuk testing tanpa koneksi vendor.
- Driver dipilih melalui `PAYMENT_GATEWAY` dan di-resolve melalui `PaymentGatewayManager`.
- Siswa memiliki halaman dompet dan top up pada route `siswa.dompet.*`.
- Webhook tersedia pada `POST /webhooks/payment-gateway/{provider}`.
- Webhook memeriksa signature, nominal, status, event idempotency, dan hanya mengkredit saldo satu kali.
- Top up sukses membuat jurnal kredit melalui `WalletLedgerService`.

## Hasil tahap 7 dan 8

- `WalletBillPaymentService` membayar satu atau beberapa tagihan milik siswa dalam satu database transaction.
- Nominal dibaca ulang dari database dan hanya sisa tagihan yang dibayar.
- Pembayaran wallet membuat record `pembayaran` dengan `metode_bayar = dompet`, `transaction_id` jurnal, dan record `wallet_payments`.
- Pembayaran ganda, tagihan siswa lain, tagihan tidak aktif, dan saldo kurang ditolak.
- Halaman siswa memiliki checkbox tagihan dan tombol bayar dengan dompet.
- Koperasi menerima metode `dompet` dan menggunakan `WalletCooperativePaymentService`.
- Stok, penjualan, dan debit wallet berada dalam database transaction yang sama.
- Koreksi transaksi koperasi yang sudah dibayar dengan dompet ditolak untuk mencegah debit ganda.
- Pembatalan penjualan koperasi yang dibayar dengan dompet membuat refund ledger.
- Kolom `wallet_payment_id` ditambahkan ke `koperasi_penjualan` melalui migration `2026_11_12_000009_add_wallet_payment_to_koperasi_penjualan_table.php`.

## Hasil tahap 9 dan 10

- Dashboard siswa menampilkan saldo tersedia, tautan dompet, dan aktivitas pembayaran wallet terbaru.
- Permission admin `wallet.view` dan `wallet.refund` ditambahkan.
- Dashboard admin tersedia pada route `wallet.admin.index`.
- Dashboard admin menampilkan jumlah akun, total saldo, saldo ditahan, top up pending, pembayaran, webhook error, pencarian siswa, dan pagination.
- Menu Dompet Siswa ditambahkan ke sidebar admin.

## Hasil tahap 11 sampai 13

- Endpoint top up dan pembayaran tagihan diberi rate limit `30` request per menit.
- Endpoint webhook diberi rate limit `120` request per menit.
- Validasi ownership siswa, nominal, saldo, dan idempotency tetap dijalankan di server.
- Command rekonsiliasi tersedia:
  `php artisan wallet:reconcile --provider=midtrans --date=YYYY-MM-DD --dry-run --audit-balances`.
- Rekonsiliasi mencatat top up paid yang memiliki atau tidak memiliki referensi gateway.
- Audit saldo membandingkan cache saldo wallet dengan entry ledger dan gagal jika ada saldo negatif atau selisih.
- Uji yang disiapkan untuk sandbox: top up sukses/gagal/expired, webhook ganda, nominal berbeda, saldo kurang, pembayaran ganda, refund, dan transaksi bersamaan.
- Production gateway belum diaktifkan; pilot harus dimulai dari sandbox dan satu kelompok siswa.

## Aturan eksekusi berikutnya

1. Baca dokumen ini sebelum mengerjakan tahap baru.
2. Kerjakan hanya tahap yang diminta.
3. Jangan menghapus migration lama.
4. Semua perubahan saldo harus memakai database transaction dan row lock.
5. Setelah setiap tahap, perbarui checklist dan hasil test di dokumen ini.
