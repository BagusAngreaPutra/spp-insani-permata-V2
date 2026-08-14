-- PERBAIKAN STRUKTUR DATABASE HOSTING - Sistem SPP Permata Insani
-- Target: MariaDB 10.11 melalui phpMyAdmin.
-- Dibuat dari perbandingan admd2932_db_spp.sql dengan kode aplikasi saat ini.
-- SQL ini tidak menghapus, mengganti, atau mengisi data sekolah/transaksi.

-- Kolom ini dipakai oleh dashboard dan Pusat Laporan > Sekolah,
-- tetapi belum tersedia pada export database hosting.
ALTER TABLE `sekolah`
    ADD COLUMN IF NOT EXISTS `jenjang` VARCHAR(100) NULL AFTER `kode_sekolah`;

-- Pemeriksaan defensif untuk struktur fitur terbaru. Pada export 14 Agustus
-- kolom-kolom ini sudah ada; IF NOT EXISTS membuat perintah tetap aman dijalankan.
ALTER TABLE `pembayaran`
    ADD COLUMN IF NOT EXISTS `tanggal_kwitansi` DATE NULL AFTER `tanggal_bayar`;

ALTER TABLE `jenis_pembayaran`
    ADD COLUMN IF NOT EXISTS `submission_token` CHAR(36) NULL AFTER `target_type`;

CREATE UNIQUE INDEX IF NOT EXISTS `jp_submission_token_unique`
    ON `jenis_pembayaran` (`submission_token`);

ALTER TABLE `koperasi`
    ADD COLUMN IF NOT EXISTS `jenis_transaksi` ENUM('jual', 'sewa') NOT NULL DEFAULT 'jual' AFTER `kategori`,
    ADD COLUMN IF NOT EXISTS `harga_sewa_siswa` DECIMAL(12,2) NULL AFTER `harga_jual`,
    ADD COLUMN IF NOT EXISTS `harga_sewa_umum` DECIMAL(12,2) NULL AFTER `harga_sewa_siswa`,
    ADD COLUMN IF NOT EXISTS `harga_penggantian` DECIMAL(12,2) NULL AFTER `harga_sewa_umum`;

CREATE INDEX IF NOT EXISTS `koperasi_jenis_transaksi_status_index`
    ON `koperasi` (`jenis_transaksi`, `status`);

-- Index untuk ringkasan dashboard. Kolom tanggal diletakkan pertama agar
-- filter rentang tanggal tidak melakukan pemindaian penuh.
CREATE INDEX IF NOT EXISTS `pembayaran_tanggal_siswa_index`
    ON `pembayaran` (`tanggal_bayar`, `siswa_id`);

CREATE INDEX IF NOT EXISTS `pemasukan_tanggal_sekolah_index`
    ON `pemasukan` (`tanggal`, `sekolah_id`);

CREATE INDEX IF NOT EXISTS `pengeluaran_tanggal_sekolah_index`
    ON `pengeluaran` (`tanggal`, `sekolah_id`);

CREATE INDEX IF NOT EXISTS `koperasi_penjualan_tanggal_sekolah_index`
    ON `koperasi_penjualan` (`tanggal`, `sekolah_id`);

-- Semua hasil harus bernilai 1 setelah patch berhasil.
SELECT
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'sekolah'
          AND COLUMN_NAME = 'jenjang'
    ) AS `jenjang_tersedia`,
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'pembayaran'
          AND COLUMN_NAME = 'tanggal_kwitansi'
    ) AS `tanggal_kwitansi_tersedia`,
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'jenis_pembayaran'
          AND COLUMN_NAME = 'submission_token'
    ) AS `submission_token_tersedia`,
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'koperasi'
          AND COLUMN_NAME = 'jenis_transaksi'
    ) AS `kolom_koperasi_tersedia`,
    EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'pemasukan'
          AND INDEX_NAME = 'pemasukan_tanggal_sekolah_index'
    ) AS `index_dashboard_tersedia`;
