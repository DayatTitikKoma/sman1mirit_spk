<?php
// ============================================================
// modules/siswa/download_template.php
// Mengunduh langsung file template/template.csv
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$filePath = dirname(__DIR__, 2) . '/template/template.csv';

if (!file_exists($filePath)) {
    $filePath = dirname(__DIR__, 2) . '/public/templates/template_import.csv';
}

if (!file_exists($filePath)) {
    http_response_code(404);
    die('File template tidak ditemukan.');
}

$filename = 'template.csv';

header('Content-Description: File Transfer');
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Expires: 0');

while (ob_get_level()) {
    ob_end_clean();
}

readfile($filePath);
exit;
