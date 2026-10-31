<?php
// api/index.php - Vercel Serverless Function Router for SPK MOORA

ob_start();

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH);
$path = ltrim($parsedPath, '/');

// Direktori root proyek
$rootDir = dirname(__DIR__);

// Tentukan target file
if ($path === '' || $path === 'index.php') {
    $targetFile = $rootDir . '/index.php';
} else {
    $targetFile = $rootDir . '/' . $path;
    if (is_dir($targetFile)) {
        $targetFile = rtrim($targetFile, '/') . '/index.php';
    }
}

if (is_file($targetFile)) {
    $ext = pathinfo($targetFile, PATHINFO_EXTENSION);
    
    // Jika file statis (CSS, JS, gambar, CSV, PDF)
    if ($ext !== 'php') {
        $mimes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'csv'  => 'text/csv',
            'pdf'  => 'application/pdf',
        ];
        if (isset($mimes[$ext])) {
            header('Content-Type: ' . $mimes[$ext]);
        }
        readfile($targetFile);
        exit;
    }

    // Set variabel server
    $relativeScript = str_replace($rootDir, '', str_replace('\\', '/', $targetFile));
    $_SERVER['SCRIPT_FILENAME'] = $targetFile;
    $_SERVER['SCRIPT_NAME']     = $relativeScript;
    $_SERVER['PHP_SELF']        = $relativeScript;

    // Arahkan working directory ke lokasi file yang dipanggil
    chdir(dirname($targetFile));

    require $targetFile;
} else {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>Halaman tidak ditemukan.</p></body></html>';
}
