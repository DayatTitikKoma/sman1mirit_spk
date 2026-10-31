<?php
// ============================================================
// modules/export/csv.php — Export Hasil ke CSV
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/MOORA.php';

$sesiId = (int) ($_GET['sesi_id'] ?? 0);

// Ambil data sesi
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
    // Gunakan sesi terakhir
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

if (empty($hasilList)) {
    die('Tidak ada data untuk diekspor. Jalankan perhitungan MOORA terlebih dahulu.');
}

// Set header untuk download CSV
$filename = 'hasil_moora_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// BOM untuk UTF-8 agar Excel dapat membaca karakter Indonesia
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// Metadata
fputcsv($out, ['SPK MOORA - SMA Negeri 1 Mirit']);
fputcsv($out, ['Sesi:', $sesi['nama_sesi'] ?? '-', 'Tanggal:', $sesi ? date('d/m/Y H:i', strtotime($sesi['created_at'])) : '-']);
fputcsv($out, ['Diekspor pada:', date('d/m/Y H:i:s')]);
fputcsv($out, []); // baris kosong

// Header tabel
fputcsv($out, [
    'No', 'Rank', 'NIS', 'Nama',
    'C1 Matematika', 'C2 Nilai IPA', 'C3 Nilai IPS',
    'C4 Minat Siswa', 'C5 Minat Ortu',
    'Skor IPA', 'Skor IPS', 'Yi (Total)',
    'Confidence (%)', 'Rekomendasi'
]);

// Baris data
foreach ($hasilList as $i => $h) {
    fputcsv($out, [
        $i + 1,
        $h['rank_ipa'] ?? ($i + 1),
        $h['nis'],
        $h['nama'],
        $h['c1_matematika'],
        $h['c2_ipa'],
        $h['c3_ips'],
        $h['c4_minat_siswa'],
        $h['c5_minat_ortu'],
        number_format($h['skor_ipa'], 8, '.', ''),
        number_format($h['skor_ips'], 8, '.', ''),
        number_format($h['yi'],       8, '.', ''),
        number_format($h['confidence'], 4, '.', ''),
        $h['rekomendasi'],
    ]);
}

fputcsv($out, []); // baris kosong
fputcsv($out, ['--- Catatan Encoding C4/C5 ---']);
fputcsv($out, ['Untuk Skor IPA: IPA = 100, IPS = 0']);
fputcsv($out, ['Untuk Skor IPS: IPS = 100, IPA = 0']);
fputcsv($out, ['Rumus normalisasi: x*ij = xij / sqrt(SUM(xij^2))']);
fputcsv($out, ['Rumus pembobotan: y*ij = wj * x*ij']);
fputcsv($out, ['Rumus rekomendasi: if Skor_IPA > Skor_IPS then IPA else IPS']);

fclose($out);
exit;
