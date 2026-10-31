<?php
// ============================================================
// modules/siswa/import.php — Import Data Siswa dari CSV
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
requireRole('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/layout.php';

$errors   = [];
$imported = 0;
$skipped  = 0;
$preview  = [];
$message  = '';

// Handle upload CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Gagal upload file. Error code: ' . $file['error'];
    } elseif (!in_array($file['type'], ['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/octet-stream'])) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $errors[] = 'Format file harus .csv';
        }
    }

    if (empty($errors)) {
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            $errors[] = 'Tidak dapat membaca file CSV.';
        } else {
            // Deteksi delimiter (koma atau titik koma)
            $firstLine = fgets($handle);
            $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
            rewind($handle);

            // Baca header
            $header = fgetcsv($handle, 1000, $delimiter);
            if (!$header) {
                fclose($handle);
                $errors[] = 'File CSV kosong atau format tidak valid.';
            } else {
                // Normalisasi header: hapus kutip, BOM, jadikan lowercase, dan ubah spasi jadi underscore
                $header = array_map(function($h) {
                    $clean = strtolower(trim(str_replace(['"', "'", "\xEF\xBB\xBF"], '', $h)));
                    return str_replace(' ', '_', $clean);
                }, $header);

                // Mapping kolom
                $colMap = [
                    'nis'            => array_search('nis', $header),
                    'nama'           => false,
                    'c1_matematika'  => false,
                    'c2_ipa'         => false,
                    'c3_ips'         => false,
                    'c4_minat_siswa' => false,
                    'c5_minat_ortu'  => false,
                ];

                // Cari kolom dengan berbagai kemungkinan nama
                foreach ($header as $i => $h) {
                    if (in_array($h, ['nama', 'nama_siswa', 'nama_lengkap']))                           $colMap['nama'] = $i;
                    if (in_array($h, ['matematika', 'c1_matematika', 'c1', 'nilai_matematika', 'mat'])) $colMap['c1_matematika'] = $i;
                    if (in_array($h, ['ipa', 'c2_ipa', 'c2', 'nilai_ipa', 'n_ipa']))                    $colMap['c2_ipa'] = $i;
                    if (in_array($h, ['ips', 'c3_ips', 'c3', 'nilai_ips', 'n_ips']))                    $colMap['c3_ips'] = $i;
                    if (in_array($h, ['minat_siswa', 'c4_minat_siswa', 'c4', 'minat']))                 $colMap['c4_minat_siswa'] = $i;
                    if (in_array($h, ['minat_ortu', 'c5_minat_ortu', 'c5', 'ortu', 'minat_orang_tua'])) $colMap['c5_minat_ortu'] = $i;
                }

                // Cek kolom wajib
                foreach (['nis', 'nama', 'c1_matematika', 'c2_ipa', 'c3_ips', 'c4_minat_siswa', 'c5_minat_ortu'] as $req) {
                    if ($colMap[$req] === false) {
                        $errors[] = "Kolom '$req' tidak ditemukan di CSV. Header yang ditemukan: " . implode(', ', $header);
                    }
                }

                if (empty($errors)) {
                    $rowNum = 1;
                    while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                        $rowNum++;
                        if (count($row) < 2) continue;

                        $nis  = trim($row[$colMap['nis']]  ?? '');
                        $nama = trim($row[$colMap['nama']] ?? '');
                        $c1   = trim($row[$colMap['c1_matematika']] ?? '');
                        $c2   = trim($row[$colMap['c2_ipa']] ?? '');
                        $c3   = trim($row[$colMap['c3_ips']] ?? '');
                        $c4   = strtoupper(trim($row[$colMap['c4_minat_siswa']] ?? ''));
                        $c5   = strtoupper(trim($row[$colMap['c5_minat_ortu']]  ?? ''));

                        // Normalisasi berbagai penulisan ragu-ragu
                        if (in_array($c4, ['RAGU', 'RAGU-RAGU', 'RAGURAGU', 'RAGU RAGU'])) $c4 = 'RAGU';
                        if (in_array($c5, ['RAGU', 'RAGU-RAGU', 'RAGURAGU', 'RAGU RAGU'])) $c5 = 'RAGU';

                        if (empty($nis) || empty($nama)) { $skipped++; continue; }
                        if (!is_numeric($c1) || !is_numeric($c2) || !is_numeric($c3)) {
                            $errors[] = "Baris $rowNum ($nama): nilai C1/C2/C3 bukan angka.";
                            continue;
                        }
                        if (!in_array($c4, ['IPA', 'IPS', 'RAGU']) || !in_array($c5, ['IPA', 'IPS', 'RAGU'])) {
                            $errors[] = "Baris $rowNum ($nama): C4/C5 harus berisi IPA, IPS, atau RAGU.";
                            continue;
                        }

                        // Insert atau update jika NIS sudah ada
                        $existing = dbQueryOne('SELECT id FROM siswa WHERE nis = ?', [$nis]);
                        if ($existing) {
                            dbExecute(
                                'UPDATE siswa SET nama=?,c1_matematika=?,c2_ipa=?,c3_ips=?,c4_minat_siswa=?,c5_minat_ortu=? WHERE nis=?',
                                [$nama, $c1, $c2, $c3, $c4, $c5, $nis]
                            );
                        } else {
                            dbExecute(
                                'INSERT INTO siswa (nis,nama,c1_matematika,c2_ipa,c3_ips,c4_minat_siswa,c5_minat_ortu) VALUES (?,?,?,?,?,?,?)',
                                [$nis, $nama, $c1, $c2, $c3, $c4, $c5]
                            );
                        }

                        $imported++;
                        $preview[] = compact('nis', 'nama', 'c1', 'c2', 'c3', 'c4', 'c5');
                    }
                    $message = "✅ Import selesai: <strong>$imported</strong> siswa berhasil diimport, <strong>$skipped</strong> baris dilewati.";
                }

                fclose($handle);
            }
        }
    }
}

layoutStart('Import Data CSV', 'import');
?>

<div class="flex-between mb-24">
    <div class="section-heading">Import Data Siswa dari CSV</div>
    <a href="index.php" class="btn btn-secondary btn-sm">Kembali</a>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;" class="mb-24">

    <!-- Form Upload -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Upload File CSV</div>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <div>
                    <strong>Error:</strong>
                    <ul style="margin: 4px 0 0 16px; font-size: 12px;">
                        <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($message): ?>
            <div class="alert alert-success" data-auto-dismiss="5000">
                <span><?= $message ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="upload-zone" id="upload-zone">
                    <div class="upload-icon"><svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div>
                    <div class="upload-text">Klik atau seret file CSV ke sini</div>
                    <div class="upload-subtext">Format: .csv, maksimal 5MB</div>
                    <input type="file" name="csv_file" accept=".csv" style="display:none;" id="csv-input">
                </div>

                <div class="mt-16">
                    <button type="submit" class="btn btn-primary btn-full btn-lg">
                        Mulai Import
                    </button>
                </div>
            </form>

            <div class="divider"></div>

            <a href="download_template.php" class="btn btn-secondary btn-full" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Download Template CSV (Excel Ready)
            </a>
        </div>
    </div>

    <!-- Panduan & Tabel Contoh Template CSV -->
    <div class="card">
        <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div>
                <div class="card-title">Tabel Template & Contoh Data CSV</div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    Format tabel di bawah ini sudah disesuaikan agar otomatis rapi saat dibuka di Microsoft Excel.
                </div>
            </div>
            <a href="download_template.php" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Download Template (.csv)
            </a>
        </div>
        <div class="card-body">
            <div class="alert alert-info" style="font-size: 13px; margin-bottom: 16px;">
                <div>
                    <strong>Petunjuk Pengisian:</strong> Baris pertama harus berupa nama kolom (header). Nilai Matematika, IPA, dan IPS berupa angka (0–100). Minat Siswa dan Orang Tua diisi <strong>IPA</strong>, <strong>IPS</strong>, atau <strong>RAGU</strong>.
                </div>
            </div>

            <!-- Tabel Preview Template CSV -->
            <div class="table-container mb-16" style="border: 1px solid var(--border-color); border-radius: 8px; overflow-x: auto;">
                <table style="width: 100%; font-size: 12px; margin-bottom: 0;">
                    <thead style="background: var(--bg-secondary);">
                        <tr>
                            <th style="padding: 10px 12px; text-align: center; width: 60px;">No</th>
                            <th style="padding: 10px 12px;">NIS</th>
                            <th style="padding: 10px 12px;">Nama Siswa</th>
                            <th style="padding: 10px 12px; text-align: center;">Matematika (C1)</th>
                            <th style="padding: 10px 12px; text-align: center;">IPA (C2)</th>
                            <th style="padding: 10px 12px; text-align: center;">IPS (C3)</th>
                            <th style="padding: 10px 12px; text-align: center;">Minat Siswa (C4)</th>
                            <th style="padding: 10px 12px; text-align: center;">Minat Ortu (C5)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center font-mono" style="color: var(--text-muted);">1</td>
                            <td class="font-mono" style="font-weight: 600;">1001</td>
                            <td style="font-weight: 500;">Adit</td>
                            <td class="font-mono text-center">70</td>
                            <td class="font-mono text-center">76</td>
                            <td class="font-mono text-center">86</td>
                            <td class="text-center"><span class="badge badge-ipa">IPA</span></td>
                            <td class="text-center"><span class="badge badge-ips">IPS</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-mono" style="color: var(--text-muted);">2</td>
                            <td class="font-mono" style="font-weight: 600;">1002</td>
                            <td style="font-weight: 500;">Bella</td>
                            <td class="font-mono text-center">79</td>
                            <td class="font-mono text-center">78</td>
                            <td class="font-mono text-center">87</td>
                            <td class="text-center"><span class="badge badge-ipa">IPA</span></td>
                            <td class="text-center"><span class="badge badge-ipa">IPA</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-mono" style="color: var(--text-muted);">3</td>
                            <td class="font-mono" style="font-weight: 600;">1003</td>
                            <td style="font-weight: 500;">Citra</td>
                            <td class="font-mono text-center">80</td>
                            <td class="font-mono text-center">81</td>
                            <td class="font-mono text-center">76</td>
                            <td class="text-center"><span class="badge badge-ipa">IPA</span></td>
                            <td class="text-center"><span class="badge badge-ips">IPS</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-mono" style="color: var(--text-muted);">4</td>
                            <td class="font-mono" style="font-weight: 600;">1004</td>
                            <td style="font-weight: 500;">Dimas</td>
                            <td class="font-mono text-center">78</td>
                            <td class="font-mono text-center">87</td>
                            <td class="font-mono text-center">79</td>
                            <td class="text-center"><span class="badge badge-ips">IPS</span></td>
                            <td class="text-center"><span class="badge badge-ips">IPS</span></td>
                        </tr>
                        <tr>
                            <td class="text-center font-mono" style="color: var(--text-muted);">5</td>
                            <td class="font-mono" style="font-weight: 600;">1005</td>
                            <td style="font-weight: 500;">Gita</td>
                            <td class="font-mono text-center">84</td>
                            <td class="font-mono text-center">86</td>
                            <td class="font-mono text-center">80</td>
                            <td class="text-center"><span class="badge badge-ips">IPS</span></td>
                            <td class="text-center"><span class="badge badge-ips">IPS</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Detail Aturan Kolom -->
            <div style="font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">
                Spesifikasi Format Kolom CSV
            </div>
            <div class="table-container" style="border: 1px solid var(--border-color); border-radius: 8px;">
                <table style="width: 100%; font-size: 12px; margin-bottom: 0;">
                    <thead style="background: var(--bg-secondary);">
                        <tr><th style="padding: 8px 12px;">Header Kolom</th><th style="padding: 8px 12px;">Keterangan</th><th style="padding: 8px 12px;">Format Nilai</th></tr>
                    </thead>
                    <tbody>
                        <tr><td class="font-mono" style="color: var(--ipa-primary); font-weight: 600;">NIS</td><td>Nomor Induk Siswa (unik)</td><td>Teks / Angka (contoh: 1001)</td></tr>
                        <tr><td class="font-mono" style="color: var(--ipa-primary); font-weight: 600;">Nama Siswa</td><td>Nama Lengkap Siswa</td><td>Teks</td></tr>
                        <tr><td class="font-mono" style="color: var(--ipa-primary); font-weight: 600;">Nilai Matematika</td><td>Nilai Kriteria C1 (Benefit)</td><td>Angka Desimal (0.00 - 100.00)</td></tr>
                        <tr><td class="font-mono" style="color: var(--ipa-primary); font-weight: 600;">Nilai IPA</td><td>Nilai Kriteria C2 (Benefit)</td><td>Angka Desimal (0.00 - 100.00)</td></tr>
                        <tr><td class="font-mono" style="color: var(--ipa-primary); font-weight: 600;">Nilai IPS</td><td>Nilai Kriteria C3 (Benefit)</td><td>Angka Desimal (0.00 - 100.00)</td></tr>
                        <tr><td class="font-mono" style="color: var(--ipa-primary); font-weight: 600;">Minat Siswa</td><td>Angket Minat Siswa (C4)</td><td>Pilihan: <strong>IPA</strong>, <strong>IPS</strong>, atau <strong>RAGU</strong></td></tr>
                        <tr><td class="font-mono" style="color: var(--ipa-primary); font-weight: 600;">Minat Orang Tua</td><td>Angket Minat Orang Tua (C5)</td><td>Pilihan: <strong>IPA</strong>, <strong>IPS</strong>, atau <strong>RAGU</strong></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Preview Hasil Import -->
<?php if (!empty($preview)): ?>
<div class="card">
    <div class="card-header">
        <div class="card-title">Preview Data Berhasil Diimport (<?= count($preview) ?> siswa)</div>
        <a href="../../modules/perhitungan/index.php" class="btn btn-primary btn-sm">Hitung MOORA</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>NIS</th><th>Nama</th><th>C1</th><th>C2</th><th>C3</th><th>C4 Minat</th><th>C5 Ortu</th></tr>
            </thead>
            <tbody>
                <?php foreach ($preview as $p): ?>
                <tr>
                    <td class="td-mono"><?= htmlspecialchars($p['nis']) ?></td>
                    <td class="td-highlight"><?= htmlspecialchars($p['nama']) ?></td>
                    <td class="font-mono text-center"><?= $p['c1'] ?></td>
                    <td class="font-mono text-center"><?= $p['c2'] ?></td>
                    <td class="font-mono text-center"><?= $p['c3'] ?></td>
                    <td><span class="badge badge-<?= strtolower($p['c4']) ?>"><?= $p['c4'] ?></span></td>
                    <td><span class="badge badge-<?= strtolower($p['c5']) ?>"><?= $p['c5'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
// Hubungkan upload zone dengan input file
document.getElementById('upload-zone').addEventListener('click', () => {
    document.getElementById('csv-input').click();
});
document.getElementById('csv-input').addEventListener('change', function() {
    if (this.files.length) {
        document.querySelector('#upload-zone .upload-text').textContent = '✅ ' + this.files[0].name;
    }
});
</script>

<?php layoutEnd(); ?>
