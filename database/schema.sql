-- ============================================================
-- SPK MOORA - Schema Database Universal
-- Kompatibel penuh dengan Localhost XAMPP, InfinityFree, & Cloud
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Hapus tabel lama jika ada agar bersih dari bentrok
DROP TABLE IF EXISTS `hasil_perhitungan`;
DROP TABLE IF EXISTS `sesi_perhitungan`;
DROP TABLE IF EXISTS `siswa`;
DROP TABLE IF EXISTS `kriteria`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `php_sessions`;

-- ------------------------------------------------------------
-- Tabel: siswa
-- ------------------------------------------------------------
CREATE TABLE `siswa` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nis`             VARCHAR(20) NOT NULL UNIQUE,
    `nama`            VARCHAR(100) NOT NULL,
    `c1_matematika`   DECIMAL(5,2) NOT NULL,
    `c2_ipa`          DECIMAL(5,2) NOT NULL,
    `c3_ips`          DECIMAL(5,2) NOT NULL,
    `c4_minat_siswa`  ENUM('IPA','IPS','RAGU') NOT NULL,
    `c5_minat_ortu`   ENUM('IPA','IPS','RAGU') NOT NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_nis` (`nis`),
    INDEX `idx_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: sesi_perhitungan
-- ------------------------------------------------------------
CREATE TABLE `sesi_perhitungan` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nama_sesi`   VARCHAR(150) NOT NULL DEFAULT 'Perhitungan Otomatis',
    `deskripsi`   TEXT NULL,
    `bobot_c1`    DECIMAL(5,4) NOT NULL DEFAULT 0.35,
    `bobot_c2`    DECIMAL(5,4) NOT NULL DEFAULT 0.25,
    `bobot_c3`    DECIMAL(5,4) NOT NULL DEFAULT 0.20,
    `bobot_c4`    DECIMAL(5,4) NOT NULL DEFAULT 0.15,
    `bobot_c5`    DECIMAL(5,4) NOT NULL DEFAULT 0.05,
    `jumlah_siswa` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: hasil_perhitungan
-- ------------------------------------------------------------
CREATE TABLE `hasil_perhitungan` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sesi_id`         INT UNSIGNED NOT NULL,
    `siswa_id`        INT UNSIGNED NOT NULL,
    `skor_ipa`        DECIMAL(10,8) NOT NULL,
    `skor_ips`        DECIMAL(10,8) NOT NULL,
    `yi`              DECIMAL(10,8) NOT NULL,
    `confidence`      DECIMAL(8,4) NOT NULL,
    `rekomendasi`     ENUM('IPA','IPS') NOT NULL,
    `rank_ipa`        INT UNSIGNED NULL,
    `rank_ips`        INT UNSIGNED NULL,
    `detail_json`     LONGTEXT NOT NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sesi` (`sesi_id`),
    INDEX `idx_siswa` (`siswa_id`),
    INDEX `idx_rekomendasi` (`rekomendasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: kriteria
-- ------------------------------------------------------------
CREATE TABLE `kriteria` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `kode`       VARCHAR(10) NOT NULL UNIQUE,
    `nama`       VARCHAR(100) NOT NULL,
    `bobot`      DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
    `jenis`      ENUM('Benefit', 'Cost') NOT NULL DEFAULT 'Benefit',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data default kriteria
INSERT INTO `kriteria` (`kode`, `nama`, `bobot`, `jenis`) VALUES
('c1', 'Nilai Matematika', 0.35, 'Benefit'),
('c2', 'Nilai IPA', 0.25, 'Benefit'),
('c3', 'Nilai IPS', 0.20, 'Benefit'),
('c4', 'Minat Siswa', 0.15, 'Benefit'),
('c5', 'Minat Orang Tua', 0.05, 'Benefit');

-- ------------------------------------------------------------
-- Tabel: users
-- ------------------------------------------------------------
CREATE TABLE `users` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(50) NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL,
    `nama`       VARCHAR(100) NOT NULL,
    `role`       ENUM('admin', 'guru_bk') NOT NULL DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default login: admin / admin123 dan guru_bk / gurubk123
INSERT INTO `users` (`username`, `password`, `nama`, `role`) VALUES 
('admin',   '$2y$10$wN2a1vLto8L/3X/H.A6O8e8h.7lUEXO2O4zJgP5K1YgB2xP9/b3fO', 'Administrator', 'admin'),
('guru_bk', '$2y$10$K8oQQmz3U6wTe0L5.NxHJOvXKI8qFBRn7F2eEH1p1C5YKC0nv.5gC', 'Guru BK',       'guru_bk');

SET FOREIGN_KEY_CHECKS = 1;
