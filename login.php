<?php
// ============================================================
// login.php — Halaman Login
// ============================================================
session_start();
require_once __DIR__ . '/config/database.php';

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        // Cek database
        $user = dbQueryOne('SELECT * FROM users WHERE username = ?', [$username]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Login sukses, set sesi
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SPK MOORA SMAN 1 Mirit</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            height: 100vh;
            overflow: hidden;
            display: flex;
        }
        .split-layout {
            display: flex;
            width: 100%;
            height: 100%;
        }
        .content-side {
            flex: 1;
            padding: 80px 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: #ffffff;
            position: relative;
            z-index: 10;
            box-shadow: 20px 0 40px rgba(0,0,0,0.02);
        }
        .graphic-side {
            flex: 1;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .badge-spk {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
            border-radius: 100px;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.5px;
            margin-bottom: 24px;
            animation: fadeUp 0.8s ease-out;
        }
        .main-title {
            font-size: 40px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -1px;
            margin-bottom: 12px;
            color: #0f172a;
            animation: fadeUp 0.8s ease-out 0.1s both;
        }
        .description {
            font-size: 15px;
            color: #475569;
            margin-bottom: 32px;
            animation: fadeUp 0.8s ease-out 0.2s both;
        }
        
        .login-form {
            max-width: 400px;
            width: 100%;
            animation: fadeUp 0.8s ease-out 0.3s both;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #334155;
        }
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.2s;
            outline: none;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .btn-login {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            background-color: #2563eb;
            color: #ffffff;
            padding: 16px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
            margin-top: 8px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            background-color: #1d4ed8;
            box-shadow: 0 15px 25px rgba(37, 99, 235, 0.3);
        }
        
        .alert {
            padding: 14px 16px;
            background-color: #fef2f2;
            color: #b91c1c;
            border-radius: 8px;
            border: 1px solid #f87171;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .footer {
            position: absolute;
            bottom: 40px;
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.5;
            animation: fadeUp 0.8s ease-out 0.4s both;
        }
        
        /* Decorative Graphics */
        .circle-1 {
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.15) 0%, rgba(37, 99, 235, 0.25) 100%);
            top: -100px;
            right: -100px;
            filter: blur(40px);
            animation: float 10s ease-in-out infinite;
        }
        .circle-2 {
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.2) 100%);
            bottom: -50px;
            left: -50px;
            filter: blur(40px);
            animation: float 8s ease-in-out infinite alternate;
        }
        
        .glass-panel {
            position: relative;
            width: 340px;
            height: 440px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 40px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: scaleUp 1s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
        }
        .glass-icon {
            width: 64px;
            height: 64px;
            background: rgba(37, 99, 235, 0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #60a5fa;
            margin-bottom: auto;
        }
        .glass-lines div {
            height: 8px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            margin-bottom: 12px;
        }
        .glass-lines div:nth-child(1) { width: 100%; }
        .glass-lines div:nth-child(2) { width: 80%; }
        .glass-lines div:nth-child(3) { width: 60%; }
        
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes scaleUp {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes float {
            0% { transform: translateY(0) translateX(0); }
            50% { transform: translateY(-20px) translateX(10px); }
            100% { transform: translateY(0) translateX(0); }
        }

        @media (max-width: 1024px) {
            .content-side { padding: 60px; }
        }
        @media (max-width: 768px) {
            .split-layout { flex-direction: column; }
            .graphic-side { display: none; }
            .content-side { padding: 40px; align-items: center; }
            .login-form { margin: 0 auto; }
            .footer { position: relative; bottom: 0; margin-top: 40px; text-align: center; }
        }
    </style>
    <link rel="icon" href="public/assets/favicon.svg" type="image/svg+xml">
</head>
<body>

    <div class="split-layout">
        <!-- Bagian Kiri: Form -->
        <div class="content-side">
            <div style="max-width: 400px; width: 100%; margin: 0 auto;">
                <div class="badge-spk">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5"></path>
                        <path d="M2 12l10 5 10-5"></path>
                    </svg>
                    SPK MOORA
                </div>
                
                <h1 class="main-title">Masuk</h1>
                <p class="description">Silakan masuk menggunakan akun Anda untuk mengakses sistem SPK MOORA.</p>
                
                <?php if ($error): ?>
                    <div class="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form class="login-form" method="POST" action="login.php">
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username..." required autofocus autocomplete="username">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password..." required autocomplete="current-password">
                    </div>
                    
                    <button type="submit" class="btn-login">
                        Login ke Dashboard
                    </button>
                    
                    <div style="margin-top: 24px; text-align: center;">
                        <a href="index.php" style="color: #64748b; text-decoration: none; font-size: 14px;">← Kembali ke Halaman Utama</a>
                    </div>
                </form>
            </div>
            
            <div class="footer">
                &copy; <?= date('Y') ?> SMA Negeri 1 Mirit<br>
                Sistem Informasi Akademik
            </div>
        </div>
        
        <!-- Bagian Kanan: Grafis Dekoratif -->
        <div class="graphic-side">
            <div class="circle-1"></div>
            <div class="circle-2"></div>
            
            <div class="glass-panel">
                <div class="glass-icon">
                    <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <div style="margin-bottom: auto; margin-top: 24px; color: white;">
                    <div style="font-size: 24px; font-weight: 600; margin-bottom: 8px;">Akses Aman</div>
                    <div style="font-size: 14px; color: rgba(255,255,255,0.6); line-height: 1.6;">
                        Data rekomendasi peminatan siswa dikelola dan dijaga keamanannya menggunakan sistem enkripsi standar.
                    </div>
                </div>
                <div class="glass-lines">
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
