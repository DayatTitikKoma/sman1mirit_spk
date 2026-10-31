<?php
// ============================================================
// setup_admin.php
// Script untuk membuat / mereset akun admin dan guru BK
// ============================================================
require_once __DIR__ . '/config/database.php';

$style = "font-family: 'Segoe UI', sans-serif; max-width: 600px; margin: 40px auto; padding: 0 20px;";
$cardStyle = "background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px 24px; margin-bottom: 16px;";

echo "<div style=\"$style\"><h2 style='color:#1e293b'>⚙️ Setup Pengguna — SPK MOORA</h2>";

try {
    // Pastikan tabel users ada
    getDB()->exec("
    CREATE TABLE IF NOT EXISTS `users` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username`   VARCHAR(50) NOT NULL UNIQUE,
        `password`   VARCHAR(255) NOT NULL,
        `nama`       VARCHAR(100) NOT NULL,
        `role`       ENUM('admin', 'guru_bk') NOT NULL DEFAULT 'admin',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Daftar akun yang dibuat / di-reset
    $accounts = [
        [
            'username' => 'admin',
            'password' => 'admin123',
            'nama'     => 'Administrator',
            'role'     => 'admin',
        ],
        [
            'username' => 'guru_bk',
            'password' => 'gurubk123',
            'nama'     => 'Guru BK',
            'role'     => 'guru_bk',
        ],
    ];

    foreach ($accounts as $acc) {
        $hash   = password_hash($acc['password'], PASSWORD_BCRYPT);
        $exists = dbQueryOne("SELECT id FROM users WHERE username = ?", [$acc['username']]);

        if ($exists) {
            dbExecute("UPDATE users SET password = ?, nama = ?, role = ? WHERE username = ?",
                [$hash, $acc['nama'], $acc['role'], $acc['username']]);
            $status = "<span style='color:#16a34a'>✅ Password berhasil direset</span>";
        } else {
            dbExecute("INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, ?)",
                [$acc['username'], $hash, $acc['nama'], $acc['role']]);
            $status = "<span style='color:#2563eb'>🆕 Akun baru dibuat</span>";
        }

        $roleBadge = $acc['role'] === 'admin'
            ? "<span style='background:#ede9fe;color:#6d28d9;padding:2px 10px;border-radius:100px;font-size:12px;font-weight:700;'>Administrator</span>"
            : "<span style='background:#d1fae5;color:#065f46;padding:2px 10px;border-radius:100px;font-size:12px;font-weight:700;'>Guru BK</span>";

        echo "<div style=\"$cardStyle\">
            <div style='margin-bottom:8px;'>$roleBadge &nbsp; $status</div>
            <table style='border-collapse:collapse;width:100%;font-size:14px;'>
                <tr><td style='padding:3px 0;color:#64748b;width:110px;'>Username</td><td><strong>{$acc['username']}</strong></td></tr>
                <tr><td style='padding:3px 0;color:#64748b;'>Password</td><td><strong>{$acc['password']}</strong></td></tr>
                <tr><td style='padding:3px 0;color:#64748b;'>Nama</td><td>{$acc['nama']}</td></tr>
            </table>
        </div>";
    }

    echo "<div style='margin-top:8px;background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400e;'>
        ⚠️ <strong>Penting:</strong> Hapus atau amankan file ini setelah digunakan di lingkungan produksi.
    </div>";

    echo "<br><a href='login.php' style='display:inline-block;padding:10px 22px;background:#2563eb;color:white;text-decoration:none;border-radius:8px;font-weight:600;'>
        → Ke Halaman Login
    </a>";

} catch (Exception $e) {
    echo "<div style='color:#dc2626;background:#fee2e2;padding:16px;border-radius:8px;'>
        <strong>Terjadi kesalahan:</strong><br>" . htmlspecialchars($e->getMessage()) . "
    </div>";
}

echo "</div>";
