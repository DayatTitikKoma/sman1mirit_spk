<?php
// ============================================================
// config/database.php
// Konfigurasi koneksi database MySQL untuk SPK MOORA
// Mendukung environment variable Railway (produksi) dan
// fallback ke XAMPP localhost (pengembangan lokal)
// ============================================================

// Railway menyediakan variabel MYSQL_URL atau MYSQLHOST secara otomatis
// Jika tidak ada (lokal), gunakan nilai default XAMPP
define('DB_HOST',    getenv('MYSQLHOST')     ?: 'localhost');
define('DB_USER',    getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS',    getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME',    getenv('MYSQLDATABASE') ?: 'spk_sman1mirit');
define('DB_PORT',    (int)(getenv('MYSQLPORT') ?: 3306));
define('DB_CHARSET', 'utf8mb4');

/**
 * Membuat koneksi PDO ke database MySQL.
 * Menggunakan PDO agar mendukung prepared statements (aman dari SQL injection).
 *
 * @return PDO instance koneksi database
 * @throws PDOException jika koneksi gagal
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Dukungan SSL untuk Cloud Database (Aiven/Railway)
        if (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1') {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Di lingkungan produksi, jangan tampilkan pesan error langsung
            // Tapi untuk keperluan skripsi/demo, tampilkan agar mudah debug
            die('<div style="color:red;padding:20px;font-family:monospace;">
                <strong>DATABASE ERROR:</strong><br>' . htmlspecialchars($e->getMessage()) .
                '<br><br>Pastikan XAMPP MySQL sudah berjalan dan database <code>' . DB_NAME . '</code> sudah dibuat.
                <br>Jalankan file <code>database/schema.sql</code> di phpMyAdmin terlebih dahulu.
            </div>');
        }
    }

    return $pdo;
}

/**
 * Helper: jalankan query SELECT dan return semua baris
 *
 * @param string $sql   Query SQL dengan placeholder ?
 * @param array  $params Parameter untuk prepared statement
 * @return array
 */
function dbQuery(string $sql, array $params = []): array {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Helper: jalankan query SELECT dan return satu baris saja
 *
 * @param string $sql
 * @param array  $params
 * @return array|null
 */
function dbQueryOne(string $sql, array $params = []): ?array {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Helper: jalankan INSERT/UPDATE/DELETE, return last insert ID atau rows affected
 *
 * @param string $sql
 * @param array  $params
 * @return string last insert ID
 */
function dbExecute(string $sql, array $params = []): string {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return getDB()->lastInsertId();
}
