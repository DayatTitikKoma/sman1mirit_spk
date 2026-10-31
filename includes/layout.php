<?php
// ============================================================
// includes/layout.php
// Template layout utama — dipanggil oleh semua halaman
// ============================================================

function layoutStart(string $pageTitle = 'SPK MOORA', string $activePage = ''): void {
    $projectRoot  = str_replace('\\', '/', dirname(__DIR__));
    $callerFile   = str_replace('\\', '/', debug_backtrace()[0]['file']);
    $relativePath = str_replace($projectRoot, '', dirname($callerFile));
    $depth  = substr_count($relativePath, '/');
    $ROOT   = str_repeat('../', $depth);

    // Ambil info user yang login
    $user     = getCurrentUser();
    $role     = $user['role']  ?? 'guest';
    $namaUser = $user['nama']  ?? 'Pengguna';
    $isAdminUser = ($role === 'admin');

    // Ikon SVG reusable
    $icoHome     = '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>';
    $icoUsers    = '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>';
    $icoCalc     = '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg>';
    $icoKriteria = '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>';
    $icoHistori  = '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>';
    $icoPDF      = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';
    $icoCSV      = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';
    $icoLogout   = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>';
    $icoUser     = '<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';

    // Badge warna berdasarkan role
    $roleBadgeStyle = $isAdminUser
        ? 'background: rgba(99,102,241,.20); color: #a5b4fc;'
        : 'background: rgba(16,185,129,.15); color: #6ee7b7;';
    $roleLabel = $isAdminUser ? 'Administrator' : 'Guru BK';
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — SPK MOORA SMAN 1 Mirit</title>
    <meta name="description" content="Sistem Pendukung Keputusan Rekomendasi Peminatan Siswa menggunakan Metode MOORA — SMA Negeri 1 Mirit">
    <link rel="stylesheet" href="<?= $ROOT ?>assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="icon" href="<?= $ROOT ?>public/assets/favicon.svg" type="image/svg+xml">
</head>
<body>
<div class="app-wrapper">
    <!-- ======= SIDEBAR ======= -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">
                <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                </svg>
            </div>
            <div class="logo-text">
                <div class="logo-title">SPK MOORA</div>
                <div class="logo-subtitle">SMA Negeri 1 Mirit</div>
            </div>
        </div>

        <!-- Info Pengguna -->
        <div style="padding: 10px 16px 12px; border-bottom: 1px solid rgba(255,255,255,.08); margin-bottom: 4px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                <span style="opacity:.6;"><?= $icoUser ?></span>
                <span style="font-size:13px; font-weight:600; color:rgba(255,255,255,.9); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= htmlspecialchars($namaUser) ?>
                </span>
            </div>
            <span style="display:inline-block; font-size:10px; font-weight:700; padding:2px 8px; border-radius:100px; <?= $roleBadgeStyle ?>">
                <?= $roleLabel ?>
            </span>
        </div>

        <nav class="sidebar-nav">
            <!-- ====== MENU UTAMA (semua role) ====== -->
            <div class="sidebar-section-label">Menu Utama</div>

            <a href="<?= $ROOT ?>dashboard.php"
               class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                <span class="nav-icon"><?= $icoHome ?></span> Dashboard
            </a>

            <!-- Hasil Perhitungan & Histori: SEMUA role bisa akses -->
            <a href="<?= $ROOT ?>modules/perhitungan/index.php"
               class="nav-item <?= $activePage === 'perhitungan' ? 'active' : '' ?>">
                <span class="nav-icon"><?= $icoCalc ?></span>
                <?= $isAdminUser ? 'Hitung MOORA' : 'Hasil Perhitungan' ?>
            </a>

            <a href="<?= $ROOT ?>modules/histori/index.php"
               class="nav-item <?= $activePage === 'histori' ? 'active' : '' ?>">
                <span class="nav-icon"><?= $icoHistori ?></span> Histori
            </a>

            <a href="<?= $ROOT ?>modules/langkah/index.php"
               class="nav-item <?= $activePage === 'langkah' ? 'active' : '' ?>">
                <span class="nav-icon"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></span> Langkah MOORA
            </a>

            <?php if ($isAdminUser): ?>
            <!-- ====== MENU KHUSUS ADMIN ====== -->
            <div class="divider"></div>
            <div class="sidebar-section-label">Kelola Data</div>

            <a href="<?= $ROOT ?>modules/siswa/index.php"
               class="nav-item <?= $activePage === 'siswa' ? 'active' : '' ?>">
                <span class="nav-icon"><?= $icoUsers ?></span> Data Siswa
            </a>

            <a href="<?= $ROOT ?>modules/kriteria/index.php"
               class="nav-item <?= $activePage === 'kriteria' ? 'active' : '' ?>">
                <span class="nav-icon"><?= $icoKriteria ?></span> Kelola Kriteria
            </a>



            <a href="<?= $ROOT ?>modules/users/index.php"
               class="nav-item <?= $activePage === 'users' ? 'active' : '' ?>">
                <span class="nav-icon"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M6 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"></path><line x1="19" y1="8" x2="23" y2="8"></line><line x1="21" y1="6" x2="21" y2="10"></line></svg></span> Kelola Pengguna
            </a>

            <?php endif; ?>

            <!-- ====== EXPORT (semua role) ====== -->
            <div class="divider"></div>
            <div class="sidebar-section-label">Export</div>
            <a href="<?= $ROOT ?>modules/export/pdf.php" class="nav-item" target="_blank">
                <span class="nav-icon"><?= $icoPDF ?></span> Export PDF
            </a>
            <a href="<?= $ROOT ?>modules/export/csv.php" class="nav-item">
                <span class="nav-icon"><?= $icoCSV ?></span> Export CSV
            </a>

            <!-- ====== AKUN (semua role) ====== -->
            <div class="divider"></div>
            <div class="sidebar-section-label">Akun</div>
            <a href="<?= $ROOT ?>logout.php" class="nav-item" style="color: #ef4444;">
                <span class="nav-icon"><?= $icoLogout ?></span> Logout
            </a>
        </nav>

        <div class="sidebar-footer">
            <strong>Metode MOORA</strong><br>
            Multi-Objective Optimization<br>
            on the Basis of Ratio Analysis<br>
            <span style="color: var(--ipa-light); font-weight: 600;">Versi 1.0 — 2024</span>
        </div>
    </aside>

    <!-- ======= MAIN ======= -->
    <div class="main-content">
        <div class="top-bar">
            <button class="btn btn-secondary btn-sm" id="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')" style="display:none">
                ☰
            </button>
            <div class="page-title"><?= htmlspecialchars($pageTitle) ?></div>
            <div class="breadcrumb">SPK MOORA / <?= htmlspecialchars($pageTitle) ?></div>
        </div>

        <div class="content-area animate-fade-in">
    <?php
}

function layoutEnd(): void {
    $projectRoot  = str_replace('\\', '/', dirname(__DIR__));
    $callerFile   = str_replace('\\', '/', debug_backtrace()[0]['file']);
    $relativePath = str_replace($projectRoot, '', dirname($callerFile));
    $depth = substr_count($relativePath, '/');
    $ROOT  = str_repeat('../', $depth);
    ?>
        </div><!-- end content-area -->
    </div><!-- end main-content -->
</div><!-- end app-wrapper -->

<div id="toast-container"></div>
<script src="<?= $ROOT ?>assets/js/main.js"></script>
</body>
</html>
    <?php
}
