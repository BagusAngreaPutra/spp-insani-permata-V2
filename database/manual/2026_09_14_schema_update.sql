-- Pembaruan struktur database Sistem SPP Permata Insani
-- Aman dijalankan pada MariaDB melalui phpMyAdmin.

-- 1. Status aktif/nonaktif tagihan.
SET @has_is_active = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tagihan'
      AND COLUMN_NAME = 'is_active'
);
SET @sql = IF(
    @has_is_active = 0,
    'ALTER TABLE `tagihan` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`',
    'SELECT 1'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @has_is_active_index = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tagihan'
      AND INDEX_NAME = 'tagihan_is_active_index'
);
SET @sql = IF(
    @has_is_active_index = 0,
    'ALTER TABLE `tagihan` ADD INDEX `tagihan_is_active_index` (`is_active`)',
    'SELECT 1'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

UPDATE `tagihan`
SET `is_active` = 1
WHERE `is_active` IS NULL;

-- 2. Pengaturan diskon pelunasan SPP.
CREATE TABLE IF NOT EXISTS `spp_discount_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nominal` DECIMAL(15,2) NOT NULL DEFAULT 25000.00,
    `berlaku_mulai` DATE NULL,
    `berlaku_sampai` DATE NULL,
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `updated_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `spp_discount_settings_updated_by_foreign` (`updated_by`),
    CONSTRAINT `spp_discount_settings_updated_by_foreign`
        FOREIGN KEY (`updated_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nilai awal mempertahankan aturan lama: diskon Rp25.000, aktif,
-- dan periode mengikuti jatuh tempo masing-masing tagihan SPP.
INSERT INTO `spp_discount_settings` (
    `nominal`, `berlaku_mulai`, `berlaku_sampai`, `aktif`,
    `updated_by`, `created_at`, `updated_at`
)
SELECT 25000.00, NULL, NULL, 1, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM `spp_discount_settings`);
