<?php
// ============================================================
// logout.php
// Menghancurkan session dan kembali ke login
// ============================================================
require_once __DIR__ . '/config/session.php';   // DB session handler (Vercel-compatible)
session_start();

// Hapus semua data sesi
$_SESSION = [];

// Hancurkan sesi
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Arahkan ke halaman login
header("Location: login.php");
exit;
