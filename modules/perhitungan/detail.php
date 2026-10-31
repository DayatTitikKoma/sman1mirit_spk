<?php
// ============================================================
// modules/perhitungan/detail.php
// Halaman Detail Langkah-per-Langkah MOORA untuk SATU siswa
// Digunakan sebagai bukti transparansi di lampiran skripsi
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/MOORA.php';
require_once __DIR__ . '/../../includes/layout.php';

$nis    = $_GET['nis']      ?? '';
$sesiId = (int)($_GET['sesi_id'] ?? 0);

if (!$nis || !$sesiId) {
    header('Location: index.php');
    exit;
}

// Ambil hasil dari DB (sudah punya detail_json)
$hasilDB = dbQueryOne(
    'SELECT h.*, s.nis, s.nama, s.c1_matematika, s.c2_ipa, s.c3_ips, s.c4_minat_siswa, s.c5_minat_ortu
     FROM hasil_perhitungan h
     JOIN siswa s ON s.id = h.siswa_id
     JOIN sesi_perhitungan sp ON sp.id = h.sesi_id
     WHERE sp.id = ? AND s.nis = ?',
    [$sesiId, $nis]
);

if (!$hasilDB) {
    // Hitung ulang on-the-fly
    $rawSiswa = dbQuery('SELECT * FROM siswa ORDER BY nama ASC');
    $inputMoora = array_map(fn($s) => [
        'id' => $s['id'], 'nis' => $s['nis'], 'nama' => $s['nama'],
        'c1' => $s['c1_matematika'], 'c2' => $s['c2_ipa'],
        'c3' => $s['c3_ips'], 'c4' => $s['c4_minat_siswa'], 'c5' => $s['c5_minat_ortu'],
    ], $rawSiswa);
    $allHasil = MOORA::hitung($inputMoora);
    $target = null;
    foreach ($allHasil as $h) {
        if ($h['siswa']['nis'] === $nis) { $target = $h; break; }
    }
    if (!$target) {
        die('<p class="text-muted">Data tidak ditemukan.</p>');
    }
    $detail = $target;
} else {
    $detailJson = json_decode($hasilDB['detail_json'], true) ?? [];

    // Rekonstruksi format untuk tampilan
    $detail = [
        'siswa'       => [
            'nis'  => $hasilDB['nis'],
            'nama' => $hasilDB['nama'],
            'c1'   => $hasilDB['c1_matematika'],
            'c2'   => $hasilDB['c2_ipa'],
            'c3'   => $hasilDB['c3_ips'],
            'c4'   => $hasilDB['c4_minat_siswa'],
            'c5'   => $hasilDB['c5_minat_ortu'],
        ],
        'encoding'    => $detailJson['encoding']    ?? [],
        'nilai_mentah'=> $detailJson['nilai_mentah']?? [],
        'pembagi'     => $detailJson['pembagi']     ?? [],
        'normalisasi' => $detailJson['normalisasi'] ?? [],
        'bobot'       => $detailJson['bobot']       ?? [],
        'pembobotan'  => $detailJson['pembobotan']  ?? [],
        'skor_ipa'    => $hasilDB['skor_ipa'],
        'skor_ips'    => $hasilDB['skor_ips'],
        'yi'          => $hasilDB['yi'],
        'confidence'  => $hasilDB['confidence'],
        'rekomendasi' => $hasilDB['rekomendasi'],
        'rank_ipa'    => $hasilDB['rank_ipa'],
        'rank_ips'    => $hasilDB['rank_ips'],
    ];
}

$siswa     = $detail['siswa'];
$rekIPA    = $detail['rekomendasi'] === 'IPA';
$normIPA   = $detail['normalisasi']['ipa']  ?? [];
$normIPS   = $detail['normalisasi']['ips']  ?? [];
$bobIPA    = $detail['pembobotan']['ipa']   ?? [];
$bobIPS    = $detail['pembobotan']['ips']   ?? [];
$encArr    = $detail['encoding']            ?? [];
$pembagi   = $detail['pembagi']             ?? [];

layoutStart('Detail Perhitungan — ' . $siswa['nama'], 'perhitungan');
?>

<!-- Header Detail -->
<div class="flex-between mb-24" style="flex-wrap: wrap; gap: 12px;">
    <div>
        <div class="section-heading">Detail Perhitungan MOORA</div>
        <div class="text-secondary" style="font-size: 13px;">
            <?= htmlspecialchars($siswa['nama']) ?> (NIS: <?= htmlspecialchars($siswa['nis']) ?>) —
            Sesi ID: <?= $sesiId ?>
        </div>
    </div>
    <div class="flex gap-8">
        <a href="index.php" class="btn btn-secondary btn-sm">← Kembali ke Tabel</a>
        <button class="btn btn-secondary btn-sm" data-print="1">🖨️ Print</button>
    </div>
</div>

<!-- Skor & Rekomendasi Akhir -->
<div class="card mb-24">
    <div class="card-body">
        <div class="score-display">
            <div class="score-box ipa <?= $rekIPA ? 'winner' : '' ?>">
                <div class="score-label">Skor IPA</div>
                <div class="score-value"><?= MOORA::formatAngka($detail['skor_ipa'], 6) ?></div>
                <?php if ($rekIPA): ?><div style="font-size: 10px; color: var(--ipa-light); margin-top: 4px;">✅ LEBIH TINGGI</div><?php endif; ?>
            </div>
            <div style="display:flex; align-items:center; font-size: 24px; color: var(--text-muted); padding: 0 8px;">vs</div>
            <div class="score-box ips <?= !$rekIPA ? 'winner' : '' ?>">
                <div class="score-label">Skor IPS</div>
                <div class="score-value"><?= MOORA::formatAngka($detail['skor_ips'], 6) ?></div>
                <?php if (!$rekIPA): ?><div style="font-size: 10px; color: var(--ips-light); margin-top: 4px;">✅ LEBIH TINGGI</div><?php endif; ?>
            </div>
            <div style="flex: 1; background: <?= $rekIPA ? 'var(--ipa-bg)' : 'var(--ips-bg)' ?>; border: 1px solid <?= $rekIPA ? 'rgba(99,102,241,0.3)' : 'rgba(16,185,129,0.3)' ?>; border-radius: var(--radius-md); padding: 20px; text-align: center;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 8px;">Rekomendasi Akhir</div>
                <div style="font-size: 36px; font-weight: 900; color: <?= $rekIPA ? 'var(--ipa-light)' : 'var(--ips-light)' ?>">
                    <?= $rekIPA ? '🔬 IPA' : '📚 IPS' ?>
                </div>
                <div style="font-size: 12px; color: var(--text-secondary); margin-top: 8px;">
                    Confidence: <strong style="font-family: monospace;"><?= number_format($detail['confidence'], 2) ?>%</strong>
                </div>
                <div class="confidence-bar mt-8" style="justify-content: center;">
                    <div class="confidence-track" style="max-width: 160px;">
                        <div class="confidence-fill <?= strtolower($detail['rekomendasi']) ?>"
                             style="width: <?= min($detail['confidence'], 100) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="formula-box mt-16">
            📐 Dasar keputusan: Skor_IPA (<?= MOORA::formatAngka($detail['skor_ipa'], 6) ?>) 
            <?= $rekIPA ? '>' : '<' ?>
            Skor_IPS (<?= MOORA::formatAngka($detail['skor_ips'], 6) ?>) → Rekomendasi = <strong><?= $detail['rekomendasi'] ?></strong>
            <br>
            📏 Confidence = |<?= MOORA::formatAngka($detail['skor_ipa'], 6) ?> − <?= MOORA::formatAngka($detail['skor_ips'], 6) ?>| ÷ max(<?= MOORA::formatAngka($detail['skor_ipa'], 6) ?>, <?= MOORA::formatAngka($detail['skor_ips'], 6) ?>) × 100
            = <strong><?= number_format($detail['confidence'], 4) ?>%</strong>
        </div>
    </div>
</div>

<!-- ====================================================
     LANGKAH 0: Data Input Asli
     ==================================================== -->
<div class="step-block">
    <div class="step-header">
        <div class="step-number">0</div>
        <div class="step-title">Data Input Siswa (Nilai Asli)</div>
        <div class="step-toggle">▼</div>
    </div>
    <div class="step-body open">
        <table class="matrix-table">
            <thead>
                <tr>
                    <th>Kriteria</th>
                    <th>Kode</th>
                    <th>Bobot</th>
                    <th>Nilai Asli</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Nilai Matematika</td>
                    <td>C1</td>
                    <td>35%</td>
                    <td><?= number_format($siswa['c1'], 2) ?></td>
                    <td>Rapor kelas 10, numerik</td>
                </tr>
                <tr>
                    <td>Nilai IPA</td>
                    <td>C2</td>
                    <td>25%</td>
                    <td><?= number_format($siswa['c2'], 2) ?></td>
                    <td>Rapor kelas 10, numerik</td>
                </tr>
                <tr>
                    <td>Nilai IPS</td>
                    <td>C3</td>
                    <td>20%</td>
                    <td><?= number_format($siswa['c3'], 2) ?></td>
                    <td>Rapor kelas 10, numerik</td>
                </tr>
                <tr>
                    <td>Minat Siswa</td>
                    <td>C4</td>
                    <td>15%</td>
                    <td><?= htmlspecialchars($siswa['c4']) ?></td>
                    <td>Pilihan angket (kategorikal)</td>
                </tr>
                <tr>
                    <td>Minat Orang Tua</td>
                    <td>C5</td>
                    <td>5%</td>
                    <td><?= htmlspecialchars($siswa['c5']) ?></td>
                    <td>Pilihan angket (kategorikal)</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ====================================================
     LANGKAH 1: Encoding C4 & C5
     ==================================================== -->
<div class="step-block">
    <div class="step-header">
        <div class="step-number">1</div>
        <div class="step-title">Encoding C4 & C5 (Untuk Kedua Arah Peminatan)</div>
        <div class="step-toggle">▼</div>
    </div>
    <div class="step-body">
        <div class="formula-box">
            <strong>Aturan Encoding (KRITIS — harus konsisten):</strong><br>
            • Untuk <strong>Skor IPA</strong>: Pilihan IPA = 100, RAGU = 50, IPS = 0<br>
            • Untuk <strong>Skor IPS</strong>: Pilihan IPS = 100, RAGU = 50, IPA = 0<br>
            • <em>Alasan</em>: Semua kriteria bersifat BENEFIT. Angka 100 diberikan ke pilihan yang paling mendukung jurusan tersebut, 50 untuk pilihan netral (Ragu-ragu), dan 0 untuk pilihan berlawanan.
        </div>
        <table class="matrix-table">
            <thead>
                <tr>
                    <th>Kriteria</th>
                    <th>Pilihan Asli</th>
                    <th>Nilai untuk Skor IPA</th>
                    <th>Nilai untuk Skor IPS</th>
                    <th>Logika</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>C4 — Minat Siswa</td>
                    <td><span class="badge badge-<?= strtolower($encArr['c4_pilihan'] ?? $siswa['c4']) ?>"><?= $encArr['c4_pilihan'] ?? $siswa['c4'] ?></span></td>
                    <td style="color: var(--ipa-light); font-weight: 700;"><?= $encArr['c4_ipa_val'] ?? MOORA::encodeUntukIPA($siswa['c4']) ?></td>
                    <td style="color: var(--ips-light); font-weight: 700;"><?= $encArr['c4_ips_val'] ?? MOORA::encodeUntukIPS($siswa['c4']) ?></td>
                    <td style="font-size: 11px; color: var(--text-muted);">
                        Pilih <?= $siswa['c4'] ?> → <?= $siswa['c4'] === 'IPA' ? 'mendukung IPA (100), tidak mendukung IPS (0)' : 'mendukung IPS (100), tidak mendukung IPA (0)' ?>
                    </td>
                </tr>
                <tr>
                    <td>C5 — Minat Ortu</td>
                    <td><span class="badge badge-<?= strtolower($encArr['c5_pilihan'] ?? $siswa['c5']) ?>"><?= $encArr['c5_pilihan'] ?? $siswa['c5'] ?></span></td>
                    <td style="color: var(--ipa-light); font-weight: 700;"><?= $encArr['c5_ipa_val'] ?? MOORA::encodeUntukIPA($siswa['c5']) ?></td>
                    <td style="color: var(--ips-light); font-weight: 700;"><?= $encArr['c5_ips_val'] ?? MOORA::encodeUntukIPS($siswa['c5']) ?></td>
                    <td style="font-size: 11px; color: var(--text-muted);">
                        Pilih <?= $siswa['c5'] ?> → <?= $siswa['c5'] === 'IPA' ? 'mendukung IPA (100), tidak mendukung IPS (0)' : 'mendukung IPS (100), tidak mendukung IPA (0)' ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ====================================================
     LANGKAH 2: Normalisasi Vektor
     ==================================================== -->
<div class="step-block">
    <div class="step-header">
        <div class="step-number">2</div>
        <div class="step-title">Normalisasi Vektor: x*ij = xij / √(Σ xij²)</div>
        <div class="step-toggle">▼</div>
    </div>
    <div class="step-body">
        <div class="formula-box">
            <strong>Rumus:</strong> x*ij = xij ÷ √(Σ xij²)<br>
            Pembagi adalah akar dari jumlah kuadrat SELURUH nilai siswa pada kolom yang sama.<br>
            Normalisasi dilakukan per-kolom, sehingga setiap nilai di kolom yang sama menggunakan pembagi yang sama.
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <!-- IPA -->
            <div>
                <div style="font-size: 12px; font-weight: 700; color: var(--ipa-light); margin-bottom: 10px;">Normalisasi untuk Skor IPA</div>
                <table class="matrix-table">
                    <thead>
                        <tr><th>Kolom</th><th>Nilai (xij)</th><th>Pembagi √Σxij²</th><th>x*ij</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $ipaColDefs = [
                            'c1'     => ['label' => 'C1 Matematika', 'val' => $siswa['c1']],
                            'c2'     => ['label' => 'C2 Nilai IPA',  'val' => $siswa['c2']],
                            'c4_ipa' => ['label' => 'C4 Minat Siswa', 'val' => $encArr['c4_ipa_val'] ?? MOORA::encodeUntukIPA($siswa['c4'])],
                            'c5_ipa' => ['label' => 'C5 Minat Ortu',  'val' => $encArr['c5_ipa_val'] ?? MOORA::encodeUntukIPA($siswa['c5'])],
                        ];
                        foreach ($ipaColDefs as $kol => $def):
                            $pem = $pembagi['ipa'][$kol] ?? 1;
                            $nrm = $normIPA[$kol]        ?? 0;
                        ?>
                        <tr>
                            <td><?= $def['label'] ?></td>
                            <td><?= number_format($def['val'], 4) ?></td>
                            <td><?= number_format($pem, 6) ?></td>
                            <td style="color: var(--ipa-light); font-weight: 600;"><?= number_format($nrm, 6) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- IPS -->
            <div>
                <div style="font-size: 12px; font-weight: 700; color: var(--ips-light); margin-bottom: 10px;">Normalisasi untuk Skor IPS</div>
                <table class="matrix-table">
                    <thead>
                        <tr><th>Kolom</th><th>Nilai (xij)</th><th>Pembagi √Σxij²</th><th>x*ij</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $ipsColDefs = [
                            'c1'     => ['label' => 'C1 Matematika', 'val' => $siswa['c1']],
                            'c3'     => ['label' => 'C3 Nilai IPS',  'val' => $siswa['c3']],
                            'c4_ips' => ['label' => 'C4 Minat Siswa', 'val' => $encArr['c4_ips_val'] ?? MOORA::encodeUntukIPS($siswa['c4'])],
                            'c5_ips' => ['label' => 'C5 Minat Ortu',  'val' => $encArr['c5_ips_val'] ?? MOORA::encodeUntukIPS($siswa['c5'])],
                        ];
                        foreach ($ipsColDefs as $kol => $def):
                            $pem = $pembagi['ips'][$kol] ?? 1;
                            $nrm = $normIPS[$kol]        ?? 0;
                        ?>
                        <tr>
                            <td><?= $def['label'] ?></td>
                            <td><?= number_format($def['val'], 4) ?></td>
                            <td><?= number_format($pem, 6) ?></td>
                            <td style="color: var(--ips-light); font-weight: 600;"><?= number_format($nrm, 6) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ====================================================
     LANGKAH 3: Pembobotan
     ==================================================== -->
<div class="step-block">
    <div class="step-header">
        <div class="step-number">3</div>
        <div class="step-title">Pembobotan: y*ij = wj × x*ij</div>
        <div class="step-toggle">▼</div>
    </div>
    <div class="step-body">
        <div class="formula-box">
            <strong>Rumus:</strong> y*ij = wj × x*ij<br>
            Setiap nilai ternormalisasi dikalikan dengan bobot kriterianya.
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <!-- Pembobotan IPA -->
            <div>
                <div style="font-size: 12px; font-weight: 700; color: var(--ipa-light); margin-bottom: 10px;">Pembobotan Skor IPA</div>
                <table class="matrix-table">
                    <thead>
                        <tr><th>Kolom</th><th>x*ij</th><th>Bobot (wj)</th><th>y*ij</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $bobotIPAMap = ['c1' => 0.35, 'c2' => 0.25, 'c4_ipa' => 0.15, 'c5_ipa' => 0.05];
                        $sumBobIPA   = 0;
                        foreach ($bobotIPAMap as $kol => $w):
                            $nrm = $normIPA[$kol] ?? 0;
                            $bob = $bobIPA[$kol]  ?? ($w * $nrm);
                            $sumBobIPA += $bob;
                        ?>
                        <tr>
                            <td><?= MOORA::labelKolom($kol) ?></td>
                            <td class="font-mono"><?= number_format($nrm, 6) ?></td>
                            <td class="font-mono"><?= $w ?></td>
                            <td style="color: var(--ipa-light); font-weight: 600;" class="font-mono"><?= number_format($bob, 6) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="row-highlight">
                            <td colspan="3" style="text-align: right; font-weight: 700;">Σ = Skor IPA</td>
                            <td class="font-mono" style="font-size: 14px; font-weight: 800;"><?= number_format($detail['skor_ipa'], 6) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pembobotan IPS -->
            <div>
                <div style="font-size: 12px; font-weight: 700; color: var(--ips-light); margin-bottom: 10px;">Pembobotan Skor IPS</div>
                <table class="matrix-table">
                    <thead>
                        <tr><th>Kolom</th><th>x*ij</th><th>Bobot (wj)</th><th>y*ij</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $bobotIPSMap = ['c1' => 0.35, 'c3' => 0.20, 'c4_ips' => 0.15, 'c5_ips' => 0.05];
                        foreach ($bobotIPSMap as $kol => $w):
                            $nrm = $normIPS[$kol] ?? 0;
                            $bob = $bobIPS[$kol]  ?? ($w * $nrm);
                        ?>
                        <tr>
                            <td><?= MOORA::labelKolom($kol) ?></td>
                            <td class="font-mono"><?= number_format($nrm, 6) ?></td>
                            <td class="font-mono"><?= $w ?></td>
                            <td style="color: var(--ips-light); font-weight: 600;" class="font-mono"><?= number_format($bob, 6) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="row-highlight" style="background: rgba(16,185,129,0.12) !important;">
                            <td colspan="3" style="text-align: right; font-weight: 700; color: var(--ips-light);">Σ = Skor IPS</td>
                            <td class="font-mono" style="font-size: 14px; font-weight: 800; color: var(--ips-light);"><?= number_format($detail['skor_ips'], 6) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ====================================================
     LANGKAH 4: Kesimpulan & Penentuan Rekomendasi
     ==================================================== -->
<div class="step-block">
    <div class="step-header">
        <div class="step-number">4</div>
        <div class="step-title">Penentuan Rekomendasi Akhir & Confidence Score</div>
        <div class="step-toggle">▼</div>
    </div>
    <div class="step-body">
        <div class="formula-box">
            <strong>Rumus Penentuan Rekomendasi:</strong><br>
            if Skor_IPA > Skor_IPS → Rekomendasi = "IPA"<br>
            else                   → Rekomendasi = "IPS"<br><br>
            <strong>Kalkulasi:</strong><br>
            Skor IPA = <?= MOORA::formatAngka($detail['skor_ipa'], 8) ?><br>
            Skor IPS = <?= MOORA::formatAngka($detail['skor_ips'], 8) ?><br>
            <?= MOORA::formatAngka($detail['skor_ipa'], 8) ?> <?= $rekIPA ? '>' : '<' ?> <?= MOORA::formatAngka($detail['skor_ips'], 8) ?>
            → Rekomendasi = <strong>"<?= $detail['rekomendasi'] ?>"</strong><br><br>
            <strong>Rumus Confidence:</strong><br>
            confidence = |Skor_IPA − Skor_IPS| / max(Skor_IPA, Skor_IPS) × 100%<br>
            = |<?= MOORA::formatAngka($detail['skor_ipa'], 8) ?> − <?= MOORA::formatAngka($detail['skor_ips'], 8) ?>| / <?= MOORA::formatAngka(max($detail['skor_ipa'], $detail['skor_ips']), 8) ?> × 100<br>
            = <strong><?= number_format($detail['confidence'], 6) ?>%</strong>
        </div>

        <div class="alert <?= $detail['confidence'] >= 50 ? 'alert-success' : ($detail['confidence'] >= 20 ? 'alert-warning' : 'alert-danger') ?>">
            <span class="alert-icon">i</span>
            <div>
                <strong>Interpretasi Confidence <?= number_format($detail['confidence'], 2) ?>%:</strong>
                <?php if ($detail['confidence'] >= 50): ?>
                Tingkat keyakinan tinggi. Rekomendasi <?= $detail['rekomendasi'] ?> sangat kuat.
                <?php elseif ($detail['confidence'] >= 20): ?>
                Tingkat keyakinan sedang. Rekomendasi <?= $detail['rekomendasi'] ?> cukup kuat namun perlu pertimbangan tambahan.
                <?php else: ?>
                Tingkat keyakinan rendah. Perbedaan skor sangat kecil. Disarankan konsultasi lebih lanjut.
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php layoutEnd(); ?>
