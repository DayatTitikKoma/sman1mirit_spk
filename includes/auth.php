<?php
// ============================================================
// includes/auth.php
// Menangani session, validasi login, dan kontrol akses peran
// Menggunakan database session handler (kompatibel Vercel)
// ============================================================

// Load database session handler (wajib sebelum session_start)
require_once dirname(__DIR__) . '/config/session.php';

// Mulai sesi jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Memastikan pengguna sudah login.
 * Jika belum, akan diarahkan ke halaman login.
 */
function requireLogin(): void {
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $callerFile  = str_replace('\\', '/', debug_backtrace()[0]['file']);
    $relativePath = str_replace($projectRoot, '', dirname($callerFile));
    $depth = substr_count($relativePath, '/');
    $ROOT  = str_repeat('../', $depth);

    if (!isset($_SESSION['user_id'])) {
        header("Location: {$ROOT}login.php");
        exit;
    }
}

/**
 * Memastikan pengguna sudah login DAN memiliki role tertentu.
 * Jika belum login → redirect ke login.
 * Jika role tidak sesuai → redirect ke dashboard dengan pesan error.
 *
 * @param string $requiredRole  Role yang dibutuhkan: 'admin' atau 'guru_bk'
 */
function requireRole(string $requiredRole): void {
    $projectRoot  = str_replace('\\', '/', dirname(__DIR__));
    $callerFile   = str_replace('\\', '/', debug_backtrace()[0]['file']);
    $relativePath = str_replace($projectRoot, '', dirname($callerFile));
    $depth = substr_count($relativePath, '/');
    $ROOT  = str_repeat('../', $depth);

    // Belum login → ke halaman login
    if (!isset($_SESSION['user_id'])) {
        header("Location: {$ROOT}login.php");
        exit;
    }

    // Role tidak sesuai → ke dashboard dengan pesan akses ditolak
    if (($_SESSION['role'] ?? '') !== $requiredRole) {
        header("Location: {$ROOT}dashboard.php?akses_ditolak=1");
        exit;
    }
}

/**
 * Memeriksa apakah pengguna yang login adalah admin.
 *
 * @return bool
 */
function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Memeriksa apakah pengguna yang login adalah Guru BK.
 *
 * @return bool
 */
function isGuruBK(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'guru_bk';
}

/**
 * Mengambil data pengguna yang sedang login dari session.
 *
 * @return array|null
 */
function getCurrentUser(): ?array {
    if (isset($_SESSION['user_id'])) {
        return [
            'id'       => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'nama'     => $_SESSION['nama'],
            'role'     => $_SESSION['role']
        ];
    }
    return null;
}

/**
 * Mengambil label tampilan dari kode role.
 *
 * @param string $role
 * @return string
 */
function getRoleLabel(string $role): string {
    return match($role) {
        'admin'   => 'Administrator',
        'guru_bk' => 'Guru BK',
        default   => ucfirst($role),
    };
}
