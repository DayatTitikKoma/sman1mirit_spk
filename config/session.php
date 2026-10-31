<?php
// ============================================================
// config/session.php
// Database-based session handler — wajib untuk Vercel (serverless)
// Di XAMPP lokal, file ini juga berjalan normal (fallback ke DB)
// ============================================================

require_once __DIR__ . '/database.php';

class DbSessionHandler implements SessionHandlerInterface {
    private PDO $db;
    private int $lifetime;

    public function __construct() {
        $this->db       = getDB();
        $this->lifetime = (int) ini_get('session.gc_maxlifetime') ?: 1800;
    }

    public function open(string $path, string $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {
        $stmt = $this->db->prepare(
            'SELECT data FROM php_sessions WHERE session_id = ? AND expires_at > NOW()'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['data'] : '';
    }

    public function write(string $id, string $data): bool {
        $expires = date('Y-m-d H:i:s', time() + $this->lifetime);
        $stmt = $this->db->prepare(
            'INSERT INTO php_sessions (session_id, data, expires_at)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE data = VALUES(data), expires_at = VALUES(expires_at)'
        );
        return $stmt->execute([$id, $data, $expires]);
    }

    public function destroy(string $id): bool {
        $stmt = $this->db->prepare('DELETE FROM php_sessions WHERE session_id = ?');
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false {
        $stmt = $this->db->prepare('DELETE FROM php_sessions WHERE expires_at < NOW()');
        $stmt->execute();
        return $stmt->rowCount();
    }
}

// Daftarkan handler dan mulai session
$handler = new DbSessionHandler();
session_set_save_handler($handler, true);

// Konfigurasi session agar aman di Vercel (HTTPS)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

session_set_cookie_params([
    'lifetime' => 1800,
    'path'     => '/',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

