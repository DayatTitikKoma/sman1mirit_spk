-- ============================================================
-- SPK MOORA - Sistem Rekomendasi Peminatan Siswa SMA
-- Schema Database
-- Dibuat untuk: Penelitian Skripsi
-- ============================================================

-- CREATE DATABASE IF NOT EXISTS `spk_sman1mirit`
--     CHARACTER SET utf8mb4
--     COLLATE utf8mb4_unicode_ci;

-- USE `spk_sman1mirit`;

-- ------------------------------------------------------------
-- Tabel: siswa
-- Menyimpan data input mentah sebelum dihitung
-- c4_minat_siswa & c5_minat_ortu: nilai string 'IPA' atau 'IPS'
-- Encoding ke angka (100/0) dilakukan di MOORA.php, BUKAN di sini
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `siswa` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nis`             VARCHAR(20) NOT NULL UNIQUE,
    `nama`            VARCHAR(100) NOT NULL,
    `c1_matematika`   DECIMAL(5,2) NOT NULL COMMENT 'Nilai Matematika rapor kelas 10 (0-100)',
    `c2_ipa`          DECIMAL(5,2) NOT NULL COMMENT 'Nilai IPA rapor kelas 10 (0-100)',
    `c3_ips`          DECIMAL(5,2) NOT NULL COMMENT 'Nilai IPS rapor kelas 10 (0-100)',
    `c4_minat_siswa`  ENUM('IPA','IPS','RAGU') NOT NULL COMMENT 'Minat siswa dari angket: IPA, IPS, atau RAGU',
    `c5_minat_ortu`   ENUM('IPA','IPS','RAGU') NOT NULL COMMENT 'Minat orang tua dari angket: IPA, IPS, atau RAGU',
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_nis` (`nis`),
    INDEX `idx_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: sesi_perhitungan
-- Setiap kali tombol "Hitung" ditekan, dibuat satu sesi baru
-- Ini yang membentuk "histori perhitungan" untuk audit trail
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sesi_perhitungan` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nama_sesi`   VARCHAR(150) NOT NULL DEFAULT 'Perhitungan Otomatis',
    `deskripsi`   TEXT NULL,
    -- Bobot disimpan per sesi untuk keperluan audit (jika di masa depan bobot berubah)
    `bobot_c1`    DECIMAL(5,4) NOT NULL DEFAULT 0.35 COMMENT 'Bobot Matematika',
    `bobot_c2`    DECIMAL(5,4) NOT NULL DEFAULT 0.25 COMMENT 'Bobot IPA',
    `bobot_c3`    DECIMAL(5,4) NOT NULL DEFAULT 0.20 COMMENT 'Bobot IPS',
    `bobot_c4`    DECIMAL(5,4) NOT NULL DEFAULT 0.15 COMMENT 'Bobot Minat Siswa',
    `bobot_c5`    DECIMAL(5,4) NOT NULL DEFAULT 0.05 COMMENT 'Bobot Minat Ortu',
    `jumlah_siswa` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: hasil_perhitungan
-- Hasil MOORA per siswa per sesi
-- detail_json menyimpan seluruh langkah perhitungan untuk tampilan step-by-step
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hasil_perhitungan` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sesi_id`         INT UNSIGNED NOT NULL,
    `siswa_id`        INT UNSIGNED NOT NULL,
    `skor_ipa`        DECIMAL(10,8) NOT NULL COMMENT 'Skor MOORA untuk peminatan IPA',
    `skor_ips`        DECIMAL(10,8) NOT NULL COMMENT 'Skor MOORA untuk peminatan IPS',
    `yi`              DECIMAL(10,8) NOT NULL COMMENT 'Skor gabungan (skor_ipa + skor_ips)',
    `confidence`      DECIMAL(8,4) NOT NULL COMMENT 'Confidence score dalam persen',
    `rekomendasi`     ENUM('IPA','IPS') NOT NULL COMMENT 'IPA jika skor_ipa > skor_ips',
    `rank_ipa`        INT UNSIGNED NULL COMMENT 'Ranking berdasarkan skor IPA',
    `rank_ips`        INT UNSIGNED NULL COMMENT 'Ranking berdasarkan skor IPS',
    `detail_json`     LONGTEXT NOT NULL COMMENT 'JSON: seluruh langkah normalisasi, pembobotan, encoding',
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`sesi_id`) REFERENCES `sesi_perhitungan`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`) ON DELETE CASCADE,
    INDEX `idx_sesi` (`sesi_id`),
    INDEX `idx_siswa` (`siswa_id`),
    INDEX `idx_rekomendasi` (`rekomendasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tabel: kriteria
-- Menyimpan data kriteria dan bobot dinamis
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kriteria` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `kode`       VARCHAR(10) NOT NULL UNIQUE,
    `nama`       VARCHAR(100) NOT NULL,
    `bobot`      DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
    `jenis`      ENUM('Benefit', 'Cost') NOT NULL DEFAULT 'Benefit',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default criteria
INSERT IGNORE INTO `kriteria` (`kode`, `nama`, `bobot`, `jenis`) VALUES
('c1', 'Nilai Matematika', 0.35, 'Benefit'),
('c2', 'Nilai IPA', 0.25, 'Benefit'),
('c3', 'Nilai IPS', 0.20, 'Benefit'),
('c4', 'Minat Siswa', 0.15, 'Benefit'),
('c5', 'Minat Orang Tua', 0.05, 'Benefit');


-- ------------------------------------------------------------
-- Tabel: users
-- Tabel untuk menyimpan data akun yang bisa login
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(50) NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL COMMENT 'Hash dari password',
    `nama`       VARCHAR(100) NOT NULL,
    `role`       ENUM('admin', 'guru_bk') NOT NULL DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default users
-- Passwords di-hash menggunakan password_hash('admin123', PASSWORD_BCRYPT)
-- dan password_hash('gurubk123', PASSWORD_BCRYPT)
INSERT IGNORE INTO `users` (`username`, `password`, `nama`, `role`) VALUES 
('admin',   '$2y$10$wN2a1vLto8L/3X/H.A6O8e8h.7lUEXO2O4zJgP5K1YgB2xP9/b3fO', 'Administrator', 'admin'),
('guru_bk', '$2y$10$K8oQQmz3U6wTe0L5.NxHJOvXKI8qFBRn7F2eEH1p1C5YKC0nv.5gC', 'Guru BK',       'guru_bk');
