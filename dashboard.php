<?php
// ============================================================
// index.php — Dashboard Utama SPK MOORA
// ============================================================
require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/core/MOORA.php';

// Ambil statistik dari database
$totalSiswa   = dbQueryOne('SELECT COUNT(*) as n FROM siswa')['n'] ?? 0;
$totalSesi    = dbQueryOne('SELECT COUNT(*) as n FROM sesi_perhitungan')['n'] ?? 0;
$totalIPA     = 0;
$totalIPS     = 0;
$sesiTerakhir = null;

if ($totalSesi > 0) {
    $sesiTerakhir = dbQueryOne(
        'SELECT s.*, 
                SUM(CASE WHEN h.rekomendasi=\'IPA\' THEN 1 ELSE 0 END) as jml_ipa,
                SUM(CASE WHEN h.rekomendasi=\'IPS\' THEN 1 ELSE 0 END) as jml_ips,
                COUNT(h.id) as jml_siswa
         FROM sesi_perhitungan s
         LEFT JOIN hasil_perhitungan h ON h.sesi_id = s.id
         GROUP BY s.id
         ORDER BY s.created_at DESC LIMIT 1'
    );
    $totalIPA = $sesiTerakhir['jml_ipa'] ?? 0;
    $totalIPS = $sesiTerakhir['jml_ips'] ?? 0;
}

$recentSiswa = dbQuery('SELECT * FROM siswa ORDER BY created_at DESC LIMIT 5');

$kriteriaList = [];
try {
    $kriteriaList = dbQuery('SELECT * FROM kriteria ORDER BY kode ASC');
} catch (PDOException $e) {
    // Abaikan jika tabel kriteria belum dibuat
}

layoutStart('Dashboard', 'dashboard');
?>

<!-- Pesan akses ditolak -->
<?php if (isset($_GET['akses_ditolak'])): ?>
<div class="alert alert-danger mb-16" data-auto-dismiss="5000">
    <span class="alert-icon">🚫</span>
    <strong>Akses Ditolak:</strong> Anda tidak memiliki izin untuk mengakses halaman tersebut.
</div>
<?php endif; ?>

<!-- Hero Banner -->
<div class="hero-banner">
    <div class="hero-title">🎓 Sistem Pendukung Keputusan Peminatan Siswa</div>
    <div class="hero-subtitle">
        Rekomendasi peminatan <strong>IPA atau IPS</strong> untuk siswa kelas 11
        menggunakan metode <strong>MOORA</strong> (Multi-Objective Optimization on the Basis of Ratio Analysis).
        Perhitungan transparan dan dapat ditelusuri langkah demi langkah.
    </div>
    <div class="hero-actions">
        <?php if (isAdmin()): ?>
        <a href="modules/perhitungan/index.php" class="btn btn-primary btn-lg">
            🧮 Hitung Rekomendasi
        </a>
        <a href="modules/siswa/create.php" class="btn btn-secondary btn-lg">
            ➕ Tambah Siswa
        </a>
        <?php else: ?>
        <a href="modules/perhitungan/index.php" class="btn btn-primary btn-lg">
            📊 Lihat Hasil Perhitungan
        </a>
        <a href="modules/histori/index.php" class="btn btn-secondary btn-lg">
            🕘 Lihat Histori
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Stat Cards -->
<div class="stat-grid">
    <div class="stat-card total">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalSiswa ?></div>
            <div class="stat-label">Total Data Siswa</div>
        </div>
    </div>
    <div class="stat-card info">
        <div class="stat-icon">📋</div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalSesi ?></div>
            <div class="stat-label">Sesi Perhitungan</div>
        </div>
    </div>
    <div class="stat-card ipa">
        <div class="stat-icon">🔬</div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalIPA ?></div>
            <div class="stat-label">Rekomendasi IPA</div>
        </div>
    </div>
    <div class="stat-card ips">
        <div class="stat-icon">📚</div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalIPS ?></div>
            <div class="stat-label">Rekomendasi IPS</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;" class="mb-24">
    <!-- Chart Distribusi -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">📊 Distribusi Rekomendasi (Sesi Terakhir)</div>
        </div>
        <div class="card-body" style="height: 240px; display: flex; align-items: center; justify-content: center;">
            <?php if ($totalIPA + $totalIPS > 0): ?>
            <canvas id="chart-distribusi"
                    data-ipa="<?= $totalIPA ?>"
                    data-ips="<?= $totalIPS ?>"
                    style="max-height: 220px;"></canvas>
            <?php else: ?>
            <div class="text-center text-muted">
                <div style="font-size: 40px; margin-bottom: 10px;">📊</div>
                <div>Belum ada data perhitungan</div>
                <div style="font-size: 11px; margin-top: 4px;">Jalankan perhitungan MOORA terlebih dahulu</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info Kriteria & Bobot -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">⚖️ Kriteria & Bobot</div>
        </div>
        <div class="card-body" style="padding: 16px;">
            <table style="width:100%">
                <thead>
                    <tr>
                        <th>Kode</th><th>Kriteria</th><th>Bobot</th><th>Jenis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($kriteriaList)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Belum ada data kriteria</td></tr>
                    <?php else: ?>
                    <?php foreach ($kriteriaList as $k): ?>
                    <tr>
                        <td class="td-mono"><?= htmlspecialchars(strtoupper($k['kode'])) ?></td>
                        <td><?= htmlspecialchars($k['nama']) ?></td>
                        <td class="font-mono text-ipa"><?= number_format($k['bobot'] * 100, 0) ?>%</td>
                        <td><span class="badge badge-success"><?= htmlspecialchars($k['jenis']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="alert alert-info mt-16" style="font-size:11px; padding: 10px 14px;">
                <span class="alert-icon">💡</span>
                <span>C4 & C5 di-encode menjadi <strong>100</strong> jika sesuai arah peminatan, <strong>0</strong> jika tidak sesuai.</span>
            </div>
        </div>
    </div>
</div>

<!-- Data Siswa Terbaru -->
<div class="card">
    <div class="card-header">
        <div class="card-title">👥 Data Siswa Terbaru</div>
        <a href="modules/siswa/index.php" class="btn btn-secondary btn-sm">Lihat Semua →</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>NIS</th><th>Nama</th>
                    <th>C1 Mat</th><th>C2 IPA</th><th>C3 IPS</th>
                    <th>Minat Siswa</th><th>Minat Ortu</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentSiswa)): ?>
                <tr><td colspan="8" class="text-center text-muted" style="padding: 32px;">
                    Belum ada data siswa.
                    <a href="modules/siswa/create.php" class="text-ipa">Tambah sekarang →</a>
                </td></tr>
                <?php else: ?>
                <?php foreach ($recentSiswa as $s): ?>
                <tr>
                    <td class="td-mono"><?= htmlspecialchars($s['nis']) ?></td>
                    <td class="td-highlight"><?= htmlspecialchars($s['nama']) ?></td>
                    <td class="font-mono"><?= $s['c1_matematika'] ?></td>
                    <td class="font-mono"><?= $s['c2_ipa'] ?></td>
                    <td class="font-mono"><?= $s['c3_ips'] ?></td>
                    <td><span class="badge badge-<?= strtolower($s['c4_minat_siswa']) ?>"><?= $s['c4_minat_siswa'] ?></span></td>
                    <td><span class="badge badge-<?= strtolower($s['c5_minat_ortu']) ?>"><?= $s['c5_minat_ortu'] ?></span></td>
                    <td>
                        <a href="modules/siswa/show.php?id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">Detail</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php layoutEnd(); ?>
