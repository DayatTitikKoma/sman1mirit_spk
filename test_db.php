<?php
// test_db.php - Skrip diagnosa koneksi database
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "<h3>Diagnosa Sistem SPK MOORA</h3>";
echo "Versi PHP: " . phpversion() . "<br><br>";

require_once __DIR__ . '/config/database.php';

echo "Host: " . DB_HOST . "<br>";
echo "User: " . DB_USER . "<br>";
echo "Database: " . DB_NAME . "<br><br>";

try {
    $pdo = getDB();
    echo "<span style='color:green;font-weight:bold;'>✅ KONEKSI DATABASE BERHASIL!</span><br><br>";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Daftar tabel yang ditemukan (" . count($tables) . " tabel):<br>";
    if (empty($tables)) {
        echo "<span style='color:red;'>⚠️ Belum ada tabel! Silakan import file database/schema.sql lewat phpMyAdmin.</span>";
    } else {
        foreach ($tables as $t) {
            echo "- $t<br>";
        }
    }
} catch (Exception $e) {
    echo "<span style='color:red;font-weight:bold;'>❌ GAGAL: " . $e->getMessage() . "</span>";
}
