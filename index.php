<?php
// index.php — Halaman Selamat Datang
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Peminatan MOORA — SMAN 1 Mirit</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc; /* light slate */
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
        
        /* Typography */
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
            font-size: 48px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -1px;
            margin-bottom: 24px;
            color: #0f172a;
            animation: fadeUp 0.8s ease-out 0.1s both;
        }
        .main-title span {
            color: #2563eb;
        }
        .description {
            font-size: 17px;
            line-height: 1.6;
            color: #475569;
            margin-bottom: 48px;
            max-width: 500px;
            animation: fadeUp 0.8s ease-out 0.2s both;
        }
        
        /* Button */
        .btn-masuk {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background-color: #2563eb;
            color: #ffffff;
            padding: 18px 40px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
            animation: fadeUp 0.8s ease-out 0.3s both;
            align-self: flex-start;
        }
        .btn-masuk:hover {
            transform: translateY(-2px);
            background-color: #1d4ed8;
            box-shadow: 0 15px 25px rgba(37, 99, 235, 0.3);
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
        
        /* Animations */
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

        /* Responsive */
        @media (max-width: 1024px) {
            .content-side { padding: 60px; }
            .main-title { font-size: 40px; }
        }
        @media (max-width: 768px) {
            body {
                height: auto;
                min-height: 100vh;
                overflow-y: auto;
            }
            .split-layout { flex-direction: column; min-height: 100vh; }
            .graphic-side { display: none; }
            .content-side { padding: 40px 20px; align-items: center; text-align: center; }
            .main-title { font-size: 32px; }
            .btn-masuk { align-self: center; width: 100%; max-width: 320px; }
            .footer { position: relative; bottom: 0; margin-top: 40px; }
        }
        @media (max-width: 480px) {
            .content-side { padding: 30px 16px; }
            .main-title { font-size: 26px; }
        }
    </style>
</head>
<body>

    <div class="split-layout">
        <!-- Bagian Kiri: Konten -->
        <div class="content-side">
            <div class="badge-spk">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                    <path d="M2 17l10 5 10-5"></path>
                    <path d="M2 12l10 5 10-5"></path>
                </svg>
                SPK MOORA
            </div>
            
            <h1 class="main-title">
                Tentukan Arah Peminatan <span>Lebih Akurat</span>
            </h1>
            
            <p class="description">
                Selamat datang di Sistem Pendukung Keputusan peminatan jurusan Ilmu Pengetahuan Alam (IPA) dan Ilmu Pengetahuan Sosial (IPS) menggunakan metode Multi-Objective Optimization on the Basis of Ratio Analysis (MOORA).
            </p>
            
            <a href="login.php" class="btn-masuk">
                Masuk ke Aplikasi
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </a>
            
            <div class="footer">
                &copy; <?= date('Y') ?> SMA Negeri 1 Mirit<br>
                Sistem Informasi Akademik &bull; Untuk Keperluan Penelitian
            </div>
        </div>
        
        <!-- Bagian Kanan: Grafis Dekoratif -->
        <div class="graphic-side">
            <div class="circle-1"></div>
            <div class="circle-2"></div>
            
            <div class="glass-panel">
                <div class="glass-icon">
                    <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="9" y1="21" x2="9" y2="9"></line>
                    </svg>
                </div>
                <div style="margin-bottom: auto; margin-top: 24px; color: white;">
                    <div style="font-size: 24px; font-weight: 600; margin-bottom: 8px;">Analisis Presisi</div>
                    <div style="font-size: 14px; color: rgba(255,255,255,0.6); line-height: 1.6;">
                        Menggunakan algoritma MOORA yang mengoptimalkan berbagai kriteria secara transparan dan objektif.
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
