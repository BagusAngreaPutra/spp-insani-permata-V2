-- Pembaruan database Sistem SPP Permata Insani
-- Target: MariaDB 10.11 / MySQL melalui phpMyAdmin
-- Buat backup database sebelum menjalankan berkas ini.

-- 1. Kolom transaksi koperasi.
ALTER TABLE `koperasi`
    ADD COLUMN IF NOT EXISTS `jenis_transaksi` ENUM('jual', 'sewa') NOT NULL DEFAULT 'jual' AFTER `kategori`,
    ADD COLUMN IF NOT EXISTS `harga_sewa_siswa` DECIMAL(12,2) NULL AFTER `harga_jual`,
    ADD COLUMN IF NOT EXISTS `harga_sewa_umum` DECIMAL(12,2) NULL AFTER `harga_sewa_siswa`,
    ADD COLUMN IF NOT EXISTS `harga_penggantian` DECIMAL(12,2) NULL AFTER `harga_sewa_umum`;

CREATE INDEX IF NOT EXISTS `koperasi_jenis_transaksi_status_index`
    ON `koperasi` (`jenis_transaksi`, `status`);

-- 2. Tanggal kuitansi dapat berbeda dari tanggal pembayaran.
ALTER TABLE `pembayaran`
    ADD COLUMN IF NOT EXISTS `tanggal_kwitansi` DATE NULL AFTER `tanggal_bayar`;

UPDATE `pembayaran`
SET `tanggal_kwitansi` = `tanggal_bayar`
WHERE `tanggal_kwitansi` IS NULL;

-- 3. Token idempotensi jenis pembayaran.
ALTER TABLE `jenis_pembayaran`
    ADD COLUMN IF NOT EXISTS `submission_token` CHAR(36) NULL AFTER `target_type`;

-- 4. Gabungkan jenis pembayaran dengan konfigurasi yang benar-benar identik.
DROP TEMPORARY TABLE IF EXISTS `tmp_jp_duplicate_map`;
CREATE TEMPORARY TABLE `tmp_jp_duplicate_map` (
    `duplicate_id` BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    `keeper_id` BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

INSERT INTO `tmp_jp_duplicate_map` (`duplicate_id`, `keeper_id`)
SELECT `jp`.`id`, `grouped`.`keeper_id`
FROM `jenis_pembayaran` AS `jp`
INNER JOIN (
    SELECT
        MIN(`id`) AS `keeper_id`,
        `sekolah_id`,
        `tahun_ajaran_id`,
        `nama_pembayaran`,
        `tipe`,
        `nominal`,
        `jatuh_tempo`,
        `target_type`
    FROM `jenis_pembayaran`
    GROUP BY
        `sekolah_id`, `tahun_ajaran_id`, `nama_pembayaran`, `tipe`,
        `nominal`, `jatuh_tempo`, `target_type`
    HAVING COUNT(*) > 1
) AS `grouped`
    ON `jp`.`sekolah_id` <=> `grouped`.`sekolah_id`
   AND `jp`.`tahun_ajaran_id` <=> `grouped`.`tahun_ajaran_id`
   AND `jp`.`nama_pembayaran` <=> `grouped`.`nama_pembayaran`
   AND `jp`.`tipe` <=> `grouped`.`tipe`
   AND `jp`.`nominal` <=> `grouped`.`nominal`
   AND `jp`.`jatuh_tempo` <=> `grouped`.`jatuh_tempo`
   AND `jp`.`target_type` <=> `grouped`.`target_type`
WHERE `jp`.`id` <> `grouped`.`keeper_id`;

INSERT INTO `jenis_pembayaran_siswa`
    (`jenis_pembayaran_id`, `siswa_id`, `created_at`, `updated_at`)
SELECT
    `map`.`keeper_id`, `pivot`.`siswa_id`, `pivot`.`created_at`, `pivot`.`updated_at`
FROM `jenis_pembayaran_siswa` AS `pivot`
INNER JOIN `tmp_jp_duplicate_map` AS `map`
    ON `map`.`duplicate_id` = `pivot`.`jenis_pembayaran_id`
WHERE NOT EXISTS (
    SELECT 1
    FROM `jenis_pembayaran_siswa` AS `existing`
    WHERE `existing`.`jenis_pembayaran_id` = `map`.`keeper_id`
      AND `existing`.`siswa_id` = `pivot`.`siswa_id`
);

INSERT INTO `jenis_pembayaran_kelas`
    (`jenis_pembayaran_id`, `kelas_id`, `created_at`, `updated_at`)
SELECT
    `map`.`keeper_id`, `pivot`.`kelas_id`, `pivot`.`created_at`, `pivot`.`updated_at`
FROM `jenis_pembayaran_kelas` AS `pivot`
INNER JOIN `tmp_jp_duplicate_map` AS `map`
    ON `map`.`duplicate_id` = `pivot`.`jenis_pembayaran_id`
WHERE NOT EXISTS (
    SELECT 1
    FROM `jenis_pembayaran_kelas` AS `existing`
    WHERE `existing`.`jenis_pembayaran_id` = `map`.`keeper_id`
      AND `existing`.`kelas_id` = `pivot`.`kelas_id`
);

DELETE `pivot`
FROM `jenis_pembayaran_siswa` AS `pivot`
INNER JOIN `tmp_jp_duplicate_map` AS `map`
    ON `map`.`duplicate_id` = `pivot`.`jenis_pembayaran_id`;

DELETE `pivot`
FROM `jenis_pembayaran_kelas` AS `pivot`
INNER JOIN `tmp_jp_duplicate_map` AS `map`
    ON `map`.`duplicate_id` = `pivot`.`jenis_pembayaran_id`;

UPDATE `tagihan` AS `tagihan`
INNER JOIN `tmp_jp_duplicate_map` AS `map`
    ON `map`.`duplicate_id` = `tagihan`.`jenis_pembayaran_id`
SET `tagihan`.`jenis_pembayaran_id` = `map`.`keeper_id`;

DELETE `jenis`
FROM `jenis_pembayaran` AS `jenis`
INNER JOIN `tmp_jp_duplicate_map` AS `map`
    ON `map`.`duplicate_id` = `jenis`.`id`;

-- 5. Gabungkan tagihan ganda per siswa, jenis pembayaran, dan periode.
DROP TEMPORARY TABLE IF EXISTS `tmp_tagihan_duplicate_map`;
CREATE TEMPORARY TABLE `tmp_tagihan_duplicate_map` (
    `duplicate_id` BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    `keeper_id` BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

INSERT INTO `tmp_tagihan_duplicate_map` (`duplicate_id`, `keeper_id`)
SELECT `tagihan`.`id`, `grouped`.`keeper_id`
FROM `tagihan` AS `tagihan`
INNER JOIN (
    SELECT
        MIN(`id`) AS `keeper_id`,
        `siswa_id`,
        `jenis_pembayaran_id`,
        `periode`
    FROM `tagihan`
    WHERE `jenis_pembayaran_id` IS NOT NULL
    GROUP BY `siswa_id`, `jenis_pembayaran_id`, `periode`
    HAVING COUNT(*) > 1
) AS `grouped`
    ON `tagihan`.`siswa_id` = `grouped`.`siswa_id`
   AND `tagihan`.`jenis_pembayaran_id` = `grouped`.`jenis_pembayaran_id`
   AND `tagihan`.`periode` <=> `grouped`.`periode`
WHERE `tagihan`.`id` <> `grouped`.`keeper_id`;

UPDATE `pembayaran` AS `pembayaran`
INNER JOIN `tmp_tagihan_duplicate_map` AS `map`
    ON `map`.`duplicate_id` = `pembayaran`.`tagihan_id`
SET `pembayaran`.`tagihan_id` = `map`.`keeper_id`;

UPDATE `tagihan` AS `keeper`
INNER JOIN (
    SELECT `map`.`keeper_id`
    FROM `tmp_tagihan_duplicate_map` AS `map`
    INNER JOIN `tagihan` AS `duplicate`
        ON `duplicate`.`id` = `map`.`duplicate_id`
    WHERE `duplicate`.`status` = 'lunas'
    GROUP BY `map`.`keeper_id`
) AS `paid_duplicates` ON `paid_duplicates`.`keeper_id` = `keeper`.`id`
SET `keeper`.`status` = 'lunas';

DELETE `tagihan`
FROM `tagihan` AS `tagihan`
INNER JOIN `tmp_tagihan_duplicate_map` AS `map`
    ON `map`.`duplicate_id` = `tagihan`.`id`;

-- 6. Indeks pencegah submit dan tagihan ganda di masa depan.
CREATE UNIQUE INDEX IF NOT EXISTS `jp_submission_token_unique`
    ON `jenis_pembayaran` (`submission_token`);

CREATE UNIQUE INDEX IF NOT EXISTS `tagihan_student_payment_period_unique`
    ON `tagihan` (`siswa_id`, `jenis_pembayaran_id`, `periode`);

-- 7. Rekonsiliasi tagihan yang pembayarannya sudah mencukupi.
UPDATE `tagihan` AS `tagihan`
INNER JOIN (
    SELECT
        `tagihan_id`,
        SUM(`jumlah_bayar` + COALESCE(`diskon`, 0)) AS `total_coverage`
    FROM `pembayaran`
    GROUP BY `tagihan_id`
) AS `payment_totals` ON `payment_totals`.`tagihan_id` = `tagihan`.`id`
SET `tagihan`.`status` = 'lunas'
WHERE `tagihan`.`status` <> 'lunas'
  AND `payment_totals`.`total_coverage` >= `tagihan`.`nominal`;

-- 8. Tandai migrasi agar Laravel tidak mengulang pembaruan manual ini.
SET @migration_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_05_000002_add_rental_fields_to_koperasi_table', @migration_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_08_05_000002_add_rental_fields_to_koperasi_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_13_000000_add_tanggal_kwitansi_to_pembayaran_table', @migration_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_08_13_000000_add_tanggal_kwitansi_to_pembayaran_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_13_000001_prevent_duplicate_payment_types_and_bills', @migration_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_08_13_000001_prevent_duplicate_payment_types_and_bills'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_13_000002_reconcile_fully_paid_bill_statuses', @migration_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_08_13_000002_reconcile_fully_paid_bill_statuses'
);

DROP TEMPORARY TABLE IF EXISTS `tmp_tagihan_duplicate_map`;
DROP TEMPORARY TABLE IF EXISTS `tmp_jp_duplicate_map`;

-- Hasil akhir yang diharapkan: semua nilai di bawah bernilai 0.
SELECT COUNT(*) AS `duplikat_jenis_pembayaran`
FROM (
    SELECT 1
    FROM `jenis_pembayaran`
    GROUP BY
        `sekolah_id`, `tahun_ajaran_id`, `nama_pembayaran`, `tipe`,
        `nominal`, `jatuh_tempo`, `target_type`
    HAVING COUNT(*) > 1
) AS `duplicate_types`;

SELECT COUNT(*) AS `duplikat_tagihan`
FROM (
    SELECT 1
    FROM `tagihan`
    WHERE `jenis_pembayaran_id` IS NOT NULL
    GROUP BY `siswa_id`, `jenis_pembayaran_id`, `periode`
    HAVING COUNT(*) > 1
) AS `duplicate_bills`;
