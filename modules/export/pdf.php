<?php
// ============================================================
// modules/export/pdf.php — Export Hasil ke PDF (via Print Browser)
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/MOORA.php';

$sesiId = (int) ($_GET['sesi_id'] ?? 0);

if ($sesiId) {
    $hasilList = dbQuery(
        'SELECT h.*, s.nis, s.nama, s.c1_matematika, s.c2_ipa, s.c3_ips, s.c4_minat_siswa, s.c5_minat_ortu
         FROM hasil_perhitungan h
         JOIN siswa s ON s.id = h.siswa_id
         WHERE h.sesi_id = ?
         ORDER BY h.rank_ipa ASC',
        [$sesiId]
    );
    $sesi = dbQueryOne('SELECT * FROM sesi_perhitungan WHERE id = ?', [$sesiId]);
} else {
    $sesi = dbQueryOne('SELECT * FROM sesi_perhitungan ORDER BY created_at DESC LIMIT 1');
    if ($sesi) {
        $sesiId = $sesi['id'];
        $hasilList = dbQuery(
            'SELECT h.*, s.nis, s.nama, s.c1_matematika, s.c2_ipa, s.c3_ips, s.c4_minat_siswa, s.c5_minat_ortu
             FROM hasil_perhitungan h
             JOIN siswa s ON s.id = h.siswa_id
             WHERE h.sesi_id = ?
             ORDER BY h.rank_ipa ASC',
            [$sesiId]
        );
    } else {
        $hasilList = [];
    }
}

$jmlIPA = count(array_filter($hasilList, fn($h) => $h['rekomendasi'] === 'IPA'));
$jmlIPS = count(array_filter($hasilList, fn($h) => $h['rekomendasi'] === 'IPS'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan SPK MOORA — SMA Negeri 1 Mirit</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            background: white;
            padding: 24px;
        }

        /* Header */
        .report-header {
            text-align: center;
            border-bottom: 3px double #4f46e5;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .report-header .school-name {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 2px;
        }

        .report-header .report-title {
            font-size: 13px;
            font-weight: 600;
            color: #4f46e5;
            margin-bottom: 4px;
        }

        .report-header .report-sub {
            font-size: 10px;
            color: #666;
        }

        /* Info Sesi */
        .session-info {
            background: #f8f7ff;
            border: 1px solid #e0e0f0;
            border-radius: 6px;
            padding: 10px 16px;
            margin-bottom: 16px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }

        .session-info .info-item {
            font-size: 10px;
            color: #555;
        }

        .session-info .info-item strong {
            color: #1a1a2e;
        }

        /* Summary boxes */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }

        .summary-box {
            border: 1px solid;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
        }

        .summary-box.total { border-color: #cbd5e1; background: #f8fafc; }
        .summary-box.ipa   { border-color: #a5b4fc; background: #eef2ff; }
        .summary-box.ips   { border-color: #6ee7b7; background: #ecfdf5; }
        .summary-box.conf  { border-color: #fbbf24; background: #fffbeb; }

        .summary-box .box-value { font-size: 22px; font-weight: 700; color: #1a1a2e; }
        .summary-box.ipa .box-value { color: #4f46e5; }
        .summary-box.ips .box-value { color: #059669; }
        .summary-box .box-label { font-size: 10px; color: #666; margin-top: 2px; }

        /* Tabel */
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }

        thead th {
            background: #4f46e5;
            color: white;
            padding: 7px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
        }

        tbody tr:nth-child(even) { background: #f9f9f9; }
        tbody tr:hover { background: #f0f0ff; }

        tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }

        .badge-ipa {
            background: #eef2ff;
            color: #4f46e5;
            border: 1px solid #c7d2fe;
            padding: 2px 8px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 700;
        }

        .badge-ips {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
            padding: 2px 8px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 700;
        }

        .font-mono { font-family: 'Courier New', monospace; }

        /* Footer */
        .report-footer {
            margin-top: 24px;
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
            font-size: 9px;
            color: #888;
            display: flex;
            justify-content: space-between;
        }

        .method-note {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            padding: 10px 16px;
            margin-bottom: 16px;
            font-size: 10px;
            color: #0369a1;
        }

        /* Signature box */
        .sig-box {
            margin-top: 32px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .sig-item { text-align: center; }
        .sig-line { border-top: 1px solid #333; margin-top: 60px; padding-top: 6px; font-size: 10px; }

        /* Print button - hidden in print */
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4f46e5;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(79,70,229,0.4);
        }

        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
            @page { margin: 1.5cm; }
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">🖨️ Print / Save PDF</button>

<!-- Header -->
<div class="report-header">
    <div class="school-name">SMA NEGERI 1 MIRIT</div>
    <div class="report-title">LAPORAN SISTEM PENDUKUNG KEPUTUSAN PEMINATAN SISWA</div>
    <div class="report-sub">
        Menggunakan Metode MOORA (Multi-Objective Optimization on the Basis of Ratio Analysis)
    </div>
</div>

<!-- Info Sesi -->
<div class="session-info">
    <div class="info-item"><strong>Sesi:</strong> <?= htmlspecialchars($sesi['nama_sesi'] ?? '-') ?></div>
    <div class="info-item"><strong>Tanggal Hitung:</strong> <?= $sesi ? date('d MMMM Y, H:i', strtotime($sesi['created_at'])) : '-' ?></div>
    <div class="info-item"><strong>Bobot:</strong> C1=<?= ($sesi['bobot_c1'] ?? 0.35)*100 ?>% | C2=<?= ($sesi['bobot_c2'] ?? 0.25)*100 ?>% | C3=<?= ($sesi['bobot_c3'] ?? 0.20)*100 ?>% | C4=<?= ($sesi['bobot_c4'] ?? 0.15)*100 ?>% | C5=<?= ($sesi['bobot_c5'] ?? 0.05)*100 ?>%</div>
    <div class="info-item"><strong>Dicetak:</strong> <?= date('d/m/Y H:i:s') ?></div>
</div>

<!-- Summary -->
<div class="summary-grid">
    <div class="summary-box total">
        <div class="box-value"><?= count($hasilList) ?></div>
        <div class="box-label">Total Siswa</div>
    </div>
    <div class="summary-box ipa">
        <div class="box-value"><?= $jmlIPA ?></div>
        <div class="box-label">Rekomendasi IPA</div>
    </div>
    <div class="summary-box ips">
        <div class="box-value"><?= $jmlIPS ?></div>
        <div class="box-label">Rekomendasi IPS</div>
    </div>
    <div class="summary-box conf">
        <div class="box-value"><?= count($hasilList) > 0 ? round(array_sum(array_column($hasilList, 'confidence')) / count($hasilList), 1) : 0 ?>%</div>
        <div class="box-label">Rata-rata Confidence</div>
    </div>
</div>

<!-- Catatan Metode -->
<div class="method-note">
    <strong>Catatan Metodologi:</strong>
    Skor IPA dihitung dari: C1 (35%) + C2 (25%) + C4_ipa (15%) + C5_ipa (5%) = 80%.
    Skor IPS dihitung dari: C1 (35%) + C3 (20%) + C4_ips (15%) + C5_ips (5%) = 75%.
    C1 (Matematika) diikutkan di kedua skor karena relevan sebagai fondasi akademik.
    Encoding C4/C5: IPA=100 untuk arah IPA, IPS=100 untuk arah IPS.
    Normalisasi: x*ij = xij/√(Σxij²). Rekomendasi: IPA jika Skor_IPA &gt; Skor_IPS.
</div>

<!-- Tabel Utama -->
<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Rank</th>
            <th>NIS</th>
            <th>Nama Siswa</th>
            <th>C1</th>
            <th>C2</th>
            <th>C3</th>
            <th>C4</th>
            <th>C5</th>
            <th>Skor IPA</th>
            <th>Skor IPS</th>
            <th>Yi</th>
            <th>Confidence</th>
            <th>Rekomendasi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($hasilList as $i => $h): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td style="text-align:center; font-weight: 700;"><?= $h['rank_ipa'] ?? ($i+1) ?></td>
            <td class="font-mono"><?= htmlspecialchars($h['nis']) ?></td>
            <td style="font-weight: 600;"><?= htmlspecialchars($h['nama']) ?></td>
            <td class="font-mono" style="text-align:center;"><?= $h['c1_matematika'] ?></td>
            <td class="font-mono" style="text-align:center;"><?= $h['c2_ipa'] ?></td>
            <td class="font-mono" style="text-align:center;"><?= $h['c3_ips'] ?></td>
            <td style="text-align:center;"><?= $h['c4_minat_siswa'] ?></td>
            <td style="text-align:center;"><?= $h['c5_minat_ortu'] ?></td>
            <td class="font-mono" style="color:#4f46e5;"><?= number_format($h['skor_ipa'], 6) ?></td>
            <td class="font-mono" style="color:#059669;"><?= number_format($h['skor_ips'], 6) ?></td>
            <td class="font-mono"><?= number_format($h['yi'], 6) ?></td>
            <td class="font-mono" style="text-align:center;"><?= number_format($h['confidence'], 2) ?>%</td>
            <td style="text-align:center;">
                <span class="badge-<?= strtolower($h['rekomendasi']) ?>">
                    <?= $h['rekomendasi'] ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Tanda Tangan -->
<div class="sig-box">
    <div class="sig-item">
        <div>Mengetahui,</div>
        <div>Kepala SMA Negeri 1 Mirit</div>
        <div class="sig-line">( _________________________ )</div>
    </div>
    <div class="sig-item">
        <div>Mirit, <?= date('d F Y') ?></div>
        <div>Peneliti / Guru BK</div>
        <div class="sig-line">( _________________________ )</div>
    </div>
</div>

<!-- Footer -->
<div class="report-footer">
    <span>SPK MOORA — SMA Negeri 1 Mirit | Sistem Pendukung Keputusan Peminatan Siswa</span>
    <span>Dicetak: <?= date('d/m/Y H:i:s') ?> | Sesi ID: <?= $sesiId ?></span>
</div>

</body>
</html>
