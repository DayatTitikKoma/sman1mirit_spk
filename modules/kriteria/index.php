<?php
// ============================================================
// modules/kriteria/index.php — Kelola Kriteria
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
requireRole('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/layout.php';

$kriteria = [];
try {
    $kriteria = dbQuery('SELECT * FROM kriteria ORDER BY kode ASC');
} catch (PDOException $e) {
    // Abaikan jika tabel belum dibuat
}

// Hitung total bobot
$totalBobotIPA = 0;
$totalBobotIPS = 0;
foreach ($kriteria as $k) {
    if (in_array($k['kode'], ['c1', 'c2', 'c4', 'c5'])) {
        $totalBobotIPA += (float)$k['bobot'];
    }
    if (in_array($k['kode'], ['c1', 'c3', 'c4', 'c5'])) {
        $totalBobotIPS += (float)$k['bobot'];
    }
}

layoutStart('Kelola Kriteria', 'kriteria');
?>

<div class="card mb-24">
    <div class="card-header">
        <div class="card-title">⚖️ Bobot Kriteria MOORA</div>
    </div>
    <div class="card-body">
        <p class="text-muted mb-16" style="line-height: 1.6;">
            Nilai bobot di bawah ini akan digunakan dalam setiap perhitungan MOORA untuk menentukan rekomendasi peminatan. Anda dapat mengubah nilai bobot sesuai dengan kebijakan terbaru sekolah.
        </p>

        <div style="display: flex; gap: 20px; margin-bottom: 24px;">
            <div class="alert alert-info" style="flex: 1;">
                <strong>Total Bobot IPA (C1, C2, C4, C5):</strong> <?= number_format($totalBobotIPA * 100, 0) ?>%
            </div>
            <div class="alert alert-info" style="flex: 1;">
                <strong>Total Bobot IPS (C1, C3, C4, C5):</strong> <?= number_format($totalBobotIPS * 100, 0) ?>%
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Kriteria</th>
                        <th>Sifat</th>
                        <th>Bobot</th>
                        <th style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($kriteria)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Data kriteria kosong. Harap jalankan setup_kriteria.php</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($kriteria as $k): ?>
                    <tr>
                        <td class="td-mono font-bold"><?= htmlspecialchars(strtoupper($k['kode'])) ?></td>
                        <td class="td-highlight"><?= htmlspecialchars($k['nama']) ?></td>
                        <td><span class="badge badge-success"><?= htmlspecialchars($k['jenis']) ?></span></td>
                        <td class="font-mono text-ipa font-bold">
                            <?= number_format($k['bobot'] * 100, 0) ?>%
                            <span class="text-muted" style="font-weight: normal; font-size: 12px; margin-left: 8px;">(<?= $k['bobot'] ?>)</span>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $k['id'] ?>" class="btn btn-secondary btn-sm">Edit Bobot</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php layoutEnd(); ?>
