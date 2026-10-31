<?php
// Script otomatis untuk migrasi dari validasi ke kriteria
require_once __DIR__ . '/config/database.php';

try {
    // 1. Hapus tabel validasi_evaluasi jika ada
    getDB()->exec("DROP TABLE IF EXISTS `validasi_evaluasi`");
    
    // 2. Buat tabel kriteria
    getDB()->exec("
        CREATE TABLE IF NOT EXISTS `kriteria` (
            `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `kode`       VARCHAR(10) NOT NULL UNIQUE,
            `nama`       VARCHAR(100) NOT NULL,
            `bobot`      DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
            `jenis`      ENUM('Benefit', 'Cost') NOT NULL DEFAULT 'Benefit',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 3. Masukkan data default
    getDB()->exec("
        INSERT IGNORE INTO `kriteria` (`kode`, `nama`, `bobot`, `jenis`) VALUES
        ('c1', 'Nilai Matematika', 0.35, 'Benefit'),
        ('c2', 'Nilai IPA', 0.25, 'Benefit'),
        ('c3', 'Nilai IPS', 0.20, 'Benefit'),
        ('c4', 'Minat Siswa', 0.15, 'Benefit'),
        ('c5', 'Minat Orang Tua', 0.05, 'Benefit')
    ");

    echo "<h3 style='color:green; font-family:sans-serif;'>Setup Berhasil!</h3>";
    echo "<p style='font-family:sans-serif;'>Tabel kriteria telah berhasil ditambahkan dan data default berhasil disimpan.</p>";
    echo "<br><a href='dashboard.php' style='padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px; font-family:sans-serif;'>Kembali ke Dashboard</a>";

} catch (Exception $e) {
    echo "<h3 style='color:red;'>Terjadi kesalahan:</h3>";
    echo $e->getMessage();
}
