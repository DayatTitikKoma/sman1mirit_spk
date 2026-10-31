<?php
// ============================================================
// modules/siswa/download_template.php
// Download template CSV siswa resmi dengan UTF-8 BOM untuk Excel
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

// Header file download
$filename = 'template_import_siswa_' . date('Ymd') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Buka output stream
$output = fopen('php://output', 'w');

// Tulis UTF-8 BOM agar Microsoft Excel di Windows otomatis membuka per-kolom rapi
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header Kolom Tabel (sesuai parser import.php)
fputcsv($output, [
    'NIS',
    'Nama Siswa',
    'Nilai Matematika',
    'Nilai IPA',
    'Nilai IPS',
    'Minat Siswa',
    'Minat Orang Tua'
]);

// Contoh Data Siswa (format rapi untuk acuan pengguna)
$samples = [
    ['1001', 'Aditya Pratama',       '85.00', '88.50', '78.00', 'IPA',  'IPA'],
    ['1002', 'Bella Anggraini',      '76.50', '74.00', '86.50', 'IPS',  'IPS'],
    ['1003', 'Citra Lestari',        '90.00', '92.00', '75.00', 'IPA',  'IPA'],
    ['1004', 'Dimas Wahyu Saputra',  '78.00', '80.00', '82.00', 'RAGU', 'IPA'],
    ['1005', 'Eka Nurjanah',         '72.00', '70.00', '89.00', 'IPS',  'IPS'],
    ['1006', 'Fajar Ramadhan',       '88.00', '85.00', '80.00', 'IPA',  'RAGU'],
    ['1007', 'Gita Cahyani',         '79.50', '75.50', '87.00', 'IPS',  'IPS'],
    ['1008', 'Hadi Kurniawan',       '84.00', '86.00', '79.00', 'IPA',  'IPA'],
];

foreach ($samples as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
