<?php
// ============================================================
// modules/langkah/index.php
// Menampilkan langkah perhitungan MOORA untuk semua siswa
// (Matriks Keputusan, Normalisasi, Pembobotan)
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/MOORA.php';
require_once __DIR__ . '/../../includes/layout.php';

// 1. Ambil sesi_id jika ada, atau ambil sesi terakhir
$sesiId = (int)($_GET['sesi_id'] ?? 0);
if (!$sesiId) {
    $lastSesi = dbQueryOne('SELECT id FROM sesi_perhitungan ORDER BY created_at DESC LIMIT 1');
    if ($lastSesi) {
        $sesiId = (int)$lastSesi['id'];
    }
}

// Ambil data sesi
$sesi = null;
if ($sesiId) {
    $sesi = dbQueryOne('SELECT * FROM sesi_perhitungan WHERE id = ?', [$sesiId]);
}

$hasilList = [];
$pembagi = []; // Akan diambil dari salah satu detail_json
if ($sesi) {
    $hasilListDB = dbQuery(
        'SELECT h.*, s.nis, s.nama, s.c1_matematika, s.c2_ipa, s.c3_ips, s.c4_minat_siswa, s.c5_minat_ortu
         FROM hasil_perhitungan h
         JOIN siswa s ON s.id = h.siswa_id
         WHERE h.sesi_id = ?
         ORDER BY h.yi DESC',
        [$sesiId]
    );

    $noUrut = 1;
    foreach ($hasilListDB as $row) {
        $json = json_decode($row['detail_json'], true) ?? [];
        if (empty($pembagi) && isset($json['pembagi'])) {
            $pembagi = $json['pembagi'];
        }
        $hasilList[] = [
            'siswa'       => [
                'nis'  => $row['nis'],
                'nama' => $row['nama'],
                'c1'   => $row['c1_matematika'],
                'c2'   => $row['c2_ipa'],
                'c3'   => $row['c3_ips'],
                'c4'   => $row['c4_minat_siswa'],
                'c5'   => $row['c5_minat_ortu'],
            ],
            'encoding'    => $json['encoding']    ?? [],
            'normalisasi' => $json['normalisasi'] ?? [],
            'pembobotan'  => $json['pembobotan']  ?? [],
            'skor_ipa'    => $row['skor_ipa'],
            'skor_ips'    => $row['skor_ips'],
            'yi'          => $row['yi'],
            'rekomendasi' => $row['rekomendasi'],
            'no_urut'     => $noUrut++
        ];
    }
}

layoutStart('Langkah Perhitungan Lengkap', 'langkah');
?>

<div class="flex-between mb-24" style="flex-wrap: wrap; gap: 12px;">
    <div>
        <div class="section-heading">Langkah Perhitungan MOORA (Keseluruhan)</div>
        <div class="text-secondary" style="font-size: 13px;">
            Menampilkan matriks langkah demi langkah untuk seluruh alternatif siswa secara bersamaan.
        </div>
    </div>
    <div class="flex gap-8">
        <?php if ($sesiId): ?>
        <a href="../perhitungan/index.php?sesi_id=<?= $sesiId ?>" class="btn btn-primary btn-sm">Lihat Hasil Akhir</a>
        <?php endif; ?>
        <button class="btn btn-secondary btn-sm" data-print="1">🖨️ Print Laporan</button>
    </div>
</div>

<?php if (!$sesiId || empty($hasilList)): ?>
<div class="card">
    <div class="card-body text-center" style="padding: 64px;">
        <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">Tidak Ada Data Perhitungan</div>
        <div class="text-secondary">Belum ada perhitungan yang tersimpan atau sesi tidak ditemukan.</div>
    </div>
</div>
<?php else: ?>

<div class="alert alert-info mb-24">
    <span class="alert-icon">ℹ️</span>
    <div>
        Data di bawah ini merujuk pada sesi perhitungan: <strong><?= htmlspecialchars($sesi['nama_sesi']) ?></strong> 
        (<?= date('d M Y H:i', strtotime($sesi['created_at'])) ?>).
        Menampilkan <strong><?= count($hasilList) ?> alternatif</strong>.
    </div>
</div>

<!-- ==============================================
     1. MATRIKS KEPUTUSAN AWAL (X)
     ============================================== -->
<div class="card mb-24">
    <div class="card-header"><div class="card-title">1. Matriks Keputusan Awal (Data Mentah & Encoding)</div></div>
    <div class="card-body">
        <p class="text-muted mb-16" style="font-size:13px;">
            Kolom C1, C2, C3 adalah nilai rapor. Kolom C4 & C5 (Minat) diubah menjadi nilai 100 (Sesuai), 50 (Ragu), atau 0 (Berlawanan) sesuai dengan arah perhitungan.
        </p>

        <h5 style="margin: 16px 0 8px; color: var(--ipa-dark);">A. Matriks Keputusan Arah IPA</h5>
        <div class="table-wrapper mb-24">
            <table class="table-striped">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>Alternatif (Siswa)</th>
                        <th>C1 (Mat)</th>
                        <th>C2 (IPA)</th>
                        <th>C4 (IPA)</th>
                        <th>C5 (IPA)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($hasilList as $i => $h): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($h['siswa']['nama']) ?></td>
                        <td class="font-mono"><?= number_format($h['siswa']['c1'], 2) ?></td>
                        <td class="font-mono"><?= number_format($h['siswa']['c2'], 2) ?></td>
                        <td class="font-mono"><?= $h['encoding']['c4_ipa_val'] ?? 0 ?></td>
                        <td class="font-mono"><?= $h['encoding']['c5_ipa_val'] ?? 0 ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h5 style="margin: 16px 0 8px; color: var(--ips-dark);">B. Matriks Keputusan Arah IPS</h5>
        <div class="table-wrapper">
            <table class="table-striped">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>Alternatif (Siswa)</th>
                        <th>C1 (Mat)</th>
                        <th>C3 (IPS)</th>
                        <th>C4 (IPS)</th>
                        <th>C5 (IPS)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($hasilList as $i => $h): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($h['siswa']['nama']) ?></td>
                        <td class="font-mono"><?= number_format($h['siswa']['c1'], 2) ?></td>
                        <td class="font-mono"><?= number_format($h['siswa']['c3'], 2) ?></td>
                        <td class="font-mono"><?= $h['encoding']['c4_ips_val'] ?? 0 ?></td>
                        <td class="font-mono"><?= $h['encoding']['c5_ips_val'] ?? 0 ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==============================================
     2. MATRIKS NORMALISASI (R)
     ============================================== -->
<div class="card mb-24">
    <div class="card-header"><div class="card-title">2. Matriks Normalisasi (R)</div></div>
    <div class="card-body">
        <p class="text-muted mb-16" style="font-size:13px;">
            Setiap nilai pada matriks keputusan dibagi dengan akar jumlah kuadrat (pembagi) dari masing-masing kolom.
        </p>

        <h5 style="margin: 16px 0 8px; color: var(--ipa-dark);">A. Matriks Normalisasi Arah IPA</h5>
        <div class="table-wrapper mb-24">
            <table class="table-striped">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>Alternatif (Siswa)</th>
                        <th>C1 (Mat)</th>
                        <th>C2 (IPA)</th>
                        <th>C4 (IPA)</th>
                        <th>C5 (IPA)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($hasilList as $i => $h): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($h['siswa']['nama']) ?></td>
                        <td class="font-mono"><?= number_format($h['normalisasi']['ipa']['c1'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($h['normalisasi']['ipa']['c2'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($h['normalisasi']['ipa']['c4_ipa'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($h['normalisasi']['ipa']['c5_ipa'] ?? 0, 4) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:var(--bg-secondary); font-weight:bold; font-size:12px;">
                        <td colspan="2" class="text-right">Pembagi (√Σx²)</td>
                        <td class="font-mono"><?= number_format($pembagi['ipa']['c1'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($pembagi['ipa']['c2'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($pembagi['ipa']['c4_ipa'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($pembagi['ipa']['c5_ipa'] ?? 0, 4) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <h5 style="margin: 16px 0 8px; color: var(--ips-dark);">B. Matriks Normalisasi Arah IPS</h5>
        <div class="table-wrapper">
            <table class="table-striped">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>Alternatif (Siswa)</th>
                        <th>C1 (Mat)</th>
                        <th>C3 (IPS)</th>
                        <th>C4 (IPS)</th>
                        <th>C5 (IPS)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($hasilList as $i => $h): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($h['siswa']['nama']) ?></td>
                        <td class="font-mono"><?= number_format($h['normalisasi']['ips']['c1'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($h['normalisasi']['ips']['c3'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($h['normalisasi']['ips']['c4_ips'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($h['normalisasi']['ips']['c5_ips'] ?? 0, 4) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:var(--bg-secondary); font-weight:bold; font-size:12px;">
                        <td colspan="2" class="text-right">Pembagi (√Σx²)</td>
                        <td class="font-mono"><?= number_format($pembagi['ips']['c1'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($pembagi['ips']['c3'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($pembagi['ips']['c4_ips'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($pembagi['ips']['c5_ips'] ?? 0, 4) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- ==============================================
     3. MATRIKS NORMALISASI TERBOBOT (Y)
     ============================================== -->
<div class="card mb-24">
    <div class="card-header"><div class="card-title">3. Matriks Normalisasi Terbobot (Y)</div></div>
    <div class="card-body">
        <p class="text-muted mb-16" style="font-size:13px;">
            Setiap nilai pada matriks normalisasi dikalikan dengan bobot kriteria. Kemudian dijumlahkan (karena semua kriteria adalah Benefit) untuk mendapatkan Skor Akhir IPA dan IPS.
        </p>

        <h5 style="margin: 16px 0 8px; color: var(--ipa-dark);">A. Matriks Terbobot Arah IPA</h5>
        <div class="table-wrapper mb-24">
            <table class="table-striped">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>Alternatif (Siswa)</th>
                        <th>C1 (W=0.35)</th>
                        <th>C2 (W=0.25)</th>
                        <th>C4 (W=0.15)</th>
                        <th>C5 (W=0.05)</th>
                        <th style="color:var(--ipa-dark);">Σ Skor IPA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($hasilList as $i => $h): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($h['siswa']['nama']) ?></td>
                        <td class="font-mono"><?= number_format($h['pembobotan']['ipa']['c1'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($h['pembobotan']['ipa']['c2'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($h['pembobotan']['ipa']['c4_ipa'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($h['pembobotan']['ipa']['c5_ipa'] ?? 0, 4) ?></td>
                        <td class="font-mono" style="font-weight:bold; color:var(--ipa-dark);"><?= number_format($h['skor_ipa'], 4) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h5 style="margin: 16px 0 8px; color: var(--ips-dark);">B. Matriks Terbobot Arah IPS</h5>
        <div class="table-wrapper">
            <table class="table-striped">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>Alternatif (Siswa)</th>
                        <th>C1 (W=0.35)</th>
                        <th>C3 (W=0.20)</th>
                        <th>C4 (W=0.15)</th>
                        <th>C5 (W=0.05)</th>
                        <th style="color:var(--ips-dark);">Σ Skor IPS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($hasilList as $i => $h): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($h['siswa']['nama']) ?></td>
                        <td class="font-mono"><?= number_format($h['pembobotan']['ips']['c1'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($h['pembobotan']['ips']['c3'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($h['pembobotan']['ips']['c4_ips'] ?? 0, 4) ?></td>
                        <td class="font-mono"><?= number_format($h['pembobotan']['ips']['c5_ips'] ?? 0, 4) ?></td>
                        <td class="font-mono" style="font-weight:bold; color:var(--ips-dark);"><?= number_format($h['skor_ips'], 4) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>
<?php layoutEnd(); ?>
