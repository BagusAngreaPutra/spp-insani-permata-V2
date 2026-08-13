-- PEMBARUAN STRUKTUR SAJA - Sistem SPP Permata Insani
-- Target: MariaDB 10.11 melalui phpMyAdmin.
-- SQL ini TIDAK memasukkan, mengubah, menggabungkan, atau menghapus data sekolah.

-- Kolom fitur penjualan/sewa koperasi.
ALTER TABLE `koperasi`
    ADD COLUMN IF NOT EXISTS `jenis_transaksi` ENUM('jual', 'sewa') NOT NULL DEFAULT 'jual' AFTER `kategori`,
    ADD COLUMN IF NOT EXISTS `harga_sewa_siswa` DECIMAL(12,2) NULL AFTER `harga_jual`,
    ADD COLUMN IF NOT EXISTS `harga_sewa_umum` DECIMAL(12,2) NULL AFTER `harga_sewa_siswa`,
    ADD COLUMN IF NOT EXISTS `harga_penggantian` DECIMAL(12,2) NULL AFTER `harga_sewa_umum`;

CREATE INDEX IF NOT EXISTS `koperasi_jenis_transaksi_status_index`
    ON `koperasi` (`jenis_transaksi`, `status`);

-- Kolom tanggal penerbitan kuitansi. Nilai data lama dibiarkan NULL;
-- sistem otomatis memakai tanggal pembayaran sebagai fallback.
ALTER TABLE `pembayaran`
    ADD COLUMN IF NOT EXISTS `tanggal_kwitansi` DATE NULL AFTER `tanggal_bayar`;

-- Token untuk mencegah satu form jenis pembayaran tersimpan dua kali.
ALTER TABLE `jenis_pembayaran`
    ADD COLUMN IF NOT EXISTS `submission_token` CHAR(36) NULL AFTER `target_type`;

CREATE UNIQUE INDEX IF NOT EXISTS `jp_submission_token_unique`
    ON `jenis_pembayaran` (`submission_token`);

-- Pemeriksaan struktur; hasil 1 berarti kolom sudah tersedia.
SELECT
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
    ) AS `kolom_koperasi_tersedia`;
