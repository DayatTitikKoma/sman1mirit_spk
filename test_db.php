<?php
// test_db.php - Skrip diagnosa lengkap sistem SPK MOORA
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "<h2>Diagnosa Sistem SPK MOORA</h2>";
echo "Versi PHP: " . phpversion() . "<br><br>";

require_once __DIR__ . '/config/database.php';

echo "Host: " . DB_HOST . "<br>";
echo "User: " . DB_USER . "<br>";
echo "Database: " . DB_NAME . "<br><br>";

try {
    $pdo = getDB();
    echo "<span style='color:green;font-weight:bold;font-size:16px;'>✅ KONEKSI DATABASE BERHASIL!</span><br><br>";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    echo "<hr><h3>Pemeriksaan Tabel SPK MOORA:</h3>";
    $required = ['siswa', 'sesi_perhitungan', 'hasil_perhitungan', 'kriteria', 'users'];
    $missing = [];
    foreach ($required as $tbl) {
        if (in_array($tbl, $tables)) {
            echo "✅ Tabel <code>$tbl</code>: ADA<br>";
        } else {
            echo "<span style='color:red;'>❌ Tabel <code>$tbl</code>: BELUM ADA</span><br>";
            $missing[] = $tbl;
        }
    }

    if (!empty($missing)) {
        echo "<br><div style='padding:10px;background:#fee2e2;border:1px solid #ef4444;border-radius:6px;color:#991b1b;'>";
        echo "<strong>PERHATIAN:</strong> Database kamu saat ini berisi tabel proyek lain (seperti cache, migrations, jobs).<br>";
        echo "Tabel SPK MOORA berikut belum di-import: <b>" . implode(', ', $missing) . "</b>.<br>";
        echo "Silakan buka phpMyAdmin di cPanel dan import file <code>database/schema.sql</code>.</div><br>";
    }

    echo "<hr><h3>Pemeriksaan Akun Login:</h3>";
    if (in_array('users', $tables)) {
        $cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
        echo "Kolom di tabel users: " . implode(', ', $cols) . "<br>";
        if (!in_array('role', $cols)) {
            echo "<span style='color:red;'>❌ Kolom 'role' tidak ada di tabel users! Tabel users ini kemungkinan bawaan sistem lain.</span><br>";
        } else {
            $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            echo "Jumlah akun user: $userCount<br>";
        }
    }

    echo "<hr><h3>Pemeriksaan Session:</h3>";
    require_once __DIR__ . '/config/session.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['spk_test'] = 'ok';
    echo "✅ Session PHP berjalan normal tanpa error!<br>";

    echo "<hr><div style='color:green;'><b>Hasil Diagnosa Selesai.</b></div>";

} catch (Throwable $e) {
    echo "<span style='color:red;font-weight:bold;'>❌ ERROR: " . $e->getMessage() . "</span><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
