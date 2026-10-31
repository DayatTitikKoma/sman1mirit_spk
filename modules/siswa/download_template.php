<?php
// ============================================================
// modules/siswa/download_template.php
// Download template CSV siswa dengan delimiter titik koma (;)
// Format standar Excel Indonesia (otomatis terbagi rapi per kolom)
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$filename = 'template_import_siswa.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM untuk kompatibilitas penuh Excel Windows
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Baris Header
fputcsv($output, [
    'NIS',
    'Nama Siswa',
    'Nilai Matematika',
    'Nilai IPA',
    'Nilai IPS',
    'Minat Siswa',
    'Minat Orang Tua'
], ';');

// Data Contoh sesuai format acuan
$data = [
    ['1001', 'Adit',  '70', '76', '86', 'IPA', 'IPS'],
    ['1002', 'Bella', '79', '78', '87', 'IPA', 'IPA'],
    ['1003', 'Citra', '80', '81', '76', 'IPA', 'IPS'],
    ['1004', 'Dimas', '78', '87', '79', 'IPS', 'IPS'],
    ['1005', 'Gita',  '84', '86', '80', 'IPS', 'IPS'],
];

foreach ($data as $row) {
    fputcsv($output, $row, ';');
}

fclose($output);
exit;
