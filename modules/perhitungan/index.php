<?php
// ============================================================
// modules/perhitungan/index.php — Halaman Utama Perhitungan MOORA
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/MOORA.php';
require_once __DIR__ . '/../../core/Logger.php';
require_once __DIR__ . '/../../includes/layout.php';

$hasilList  = [];
$sesiId     = null;
$pesanError = '';
$pesanOK    = '';

// ---- Jalankan Perhitungan (ADMIN ONLY) ----
if (isAdmin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hitung'])) {

    $namaSesi   = trim($_POST['nama_sesi'] ?? 'Perhitungan ' . date('d/m/Y H:i'));
    $deskripsi  = trim($_POST['deskripsi'] ?? '');

    // Ambil semua data siswa dari DB
    $rawSiswa = dbQuery('SELECT * FROM siswa ORDER BY nama ASC');

    if (empty($rawSiswa)) {
        $pesanError = 'Tidak ada data siswa. Silakan tambahkan data siswa terlebih dahulu.';
    } else {
        // Konversi ke format yang diterima MOORA::hitung()
        $inputMoora = array_map(fn($s) => [
            'id'   => $s['id'],
            'nis'  => $s['nis'],
            'nama' => $s['nama'],
            'c1'   => $s['c1_matematika'],
            'c2'   => $s['c2_ipa'],
            'c3'   => $s['c3_ips'],
            'c4'   => $s['c4_minat_siswa'],
            'c5'   => $s['c5_minat_ortu'],
        ], $rawSiswa);

        // *** KALKULASI MOORA ***
        $hasilList = MOORA::hitung($inputMoora);

        // Simpan ke histori
        $sesiId = Logger::buatSesi($namaSesi, $deskripsi);
        Logger::simpanHasil($sesiId, $hasilList);

        $pesanOK = '✅ Perhitungan MOORA selesai! ' . count($hasilList) . ' siswa telah dihitung. Sesi disimpan ke histori.';
    }
}

// ---- Tampilkan hasil sesi terakhir jika ada ----
$isHasilLama = false; // flag: true jika yang ditampilkan adalah sesi lama (bukan baru dihitung)
if (empty($hasilList)) {
    // Coba load dari sesi terakhir
    $sesiTerakhir = dbQueryOne('SELECT * FROM sesi_perhitungan ORDER BY created_at DESC LIMIT 1');
    $isHasilLama  = ($sesiTerakhir !== null);
    if ($sesiTerakhir) {
        $sesiId    = $sesiTerakhir['id'];
        $dbHasil   = dbQuery(
            'SELECT h.*, s.nis, s.nama, s.c1_matematika, s.c2_ipa, s.c3_ips, s.c4_minat_siswa, s.c5_minat_ortu
             FROM hasil_perhitungan h
             JOIN siswa s ON s.id = h.siswa_id
             WHERE h.sesi_id = ?
             ORDER BY h.rank_ipa ASC',
            [$sesiId]
        );
        // Konversi ke format hasilList
        foreach ($dbHasil as $i => $r) {
            $hasilList[] = [
                'no_urut'     => $i + 1,
                'siswa'       => ['nis' => $r['nis'], 'nama' => $r['nama']],
                'skor_ipa'    => $r['skor_ipa'],
                'skor_ips'    => $r['skor_ips'],
                'yi'          => $r['yi'],
                'confidence'  => $r['confidence'],
                'rekomendasi' => $r['rekomendasi'],
                'rank_ipa'    => $r['rank_ipa'],
                'rank_ips'    => $r['rank_ips'],
                'db_id'       => $r['id'],
            ];
        }
    }
}

// Hitung distribusi untuk chart
$jmlIPA = count(array_filter($hasilList, fn($h) => $h['rekomendasi'] === 'IPA'));
$jmlIPS = count(array_filter($hasilList, fn($h) => $h['rekomendasi'] === 'IPS'));

// Data untuk chart confidence
$chartLabels  = json_encode(array_map(fn($h) => $h['siswa']['nama'], $hasilList));
$chartConf    = json_encode(array_map(fn($h) => round($h['confidence'], 2), $hasilList));

layoutStart('Perhitungan MOORA', 'perhitungan');
?>

<!-- ---- Form Hitung (Admin Only) ---- -->
<?php if (isAdmin()): ?>
<div class="card mb-24">
    <div class="card-header">
        <div class="card-title">Jalankan Perhitungan MOORA</div>
    </div>
    <div class="card-body">
        <?php if ($pesanError): ?>
        <div class="alert alert-danger mb-16"><?= $pesanError ?></div>
        <?php endif; ?>
        <?php if ($pesanOK): ?>
        <div class="alert alert-success mb-16" data-auto-dismiss="5000"><?= $pesanOK ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Sesi Perhitungan</label>
                    <input type="text" name="nama_sesi" class="form-control"
                           value="Perhitungan <?= date('d/m/Y H:i') ?>"
                           placeholder="Contoh: Perhitungan Batch 1 — 2024">
                    <div class="form-hint">Digunakan untuk membedakan di histori</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Deskripsi (opsional)</label>
                    <input type="text" name="deskripsi" class="form-control"
                           placeholder="Contoh: Setelah perbaikan encoding C4/C5">
                </div>
                <div style="align-self: flex-end; padding-bottom: 4px;">
                    <button type="submit" name="hitung" class="btn btn-primary btn-lg">
                        Hitung Rekomendasi
                    </button>
                </div>
            </div>
        </form>

        <div class="alert alert-info" style="font-size: 11px; margin-top: 8px; margin-bottom: 0;">
            <div>
                Perhitungan menggunakan <strong>seluruh data siswa</strong> yang ada di database.
                C1 (Matematika) diikutsertakan di kedua skor IPA dan IPS.
                Semua kriteria bersifat <strong>Benefit</strong>.
                <strong>Bobot yang akan dipakai sesuai dengan nilai di menu Kriteria saat ini.</strong>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Info untuk Guru BK -->
<div class="alert alert-info mb-24">
    <span class="alert-icon">📊</span>
    <div>
        <strong>Hasil Perhitungan MOORA</strong> &mdash; Anda login sebagai <strong>Guru BK</strong>.
        Halaman ini menampilkan hasil dan rekomendasi peminatan terakhir yang telah dihitung oleh Admin.
    </div>
</div>
<?php endif; ?>

<?php if (!empty($hasilList)): ?>

<?php if ($isHasilLama && isset($sesiTerakhir)): ?>
<!-- Banner: hasil yang ditampilkan adalah sesi lama -->
<div class="alert alert-warning mb-24">
    <span class="alert-icon">⚠️</span>
    <div>
        Yang ditampilkan di bawah adalah <strong>hasil sesi lama</strong>
        (<?= htmlspecialchars($sesiTerakhir['nama_sesi']) ?>,
        <?= date('d M Y H:i', strtotime($sesiTerakhir['created_at'])) ?>).
        Jika Anda baru mengubah bobot kriteria atau data siswa,
        klik <strong>"Hitung Rekomendasi"</strong> di atas untuk mendapatkan hasil terbaru.
    </div>
</div>
<?php endif; ?>

<!-- ---- Stat Summary ---- -->
<div class="stat-grid mb-24">
    <div class="stat-card total">
        <div class="stat-icon"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
        <div class="stat-info">
            <div class="stat-value"><?= count($hasilList) ?></div>
            <div class="stat-label">Total Siswa Dihitung</div>
        </div>
    </div>
    <div class="stat-card ipa">
        <div class="stat-icon"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m6 12V3"></path></svg></div>
        <div class="stat-info">
            <div class="stat-value"><?= $jmlIPA ?></div>
            <div class="stat-label">Rekomendasi IPA</div>
        </div>
    </div>
    <div class="stat-card ips">
        <div class="stat-icon"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg></div>
        <div class="stat-info">
            <div class="stat-value"><?= $jmlIPS ?></div>
            <div class="stat-label">Rekomendasi IPS</div>
        </div>
    </div>
    <div class="stat-card info">
        <div class="stat-icon"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg></div>
        <div class="stat-info">
            <div class="stat-value"><?= round($jmlIPA / max(count($hasilList), 1) * 100) ?>%</div>
            <div class="stat-label">Persentase IPA</div>
        </div>
    </div>
</div>

<!-- ---- Charts ---- -->
<div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; margin-bottom: 24px;">
    <div class="card">
        <div class="card-header"><div class="card-title">Distribusi</div></div>
        <div class="card-body" style="height: 220px; display:flex; align-items:center; justify-content:center;">
            <canvas id="chart-distribusi" data-ipa="<?= $jmlIPA ?>" data-ips="<?= $jmlIPS ?>"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">Confidence Score per Siswa</div></div>
        <div class="card-body" style="height: 220px;">
            <canvas id="chart-confidence" data-labels='<?= $chartLabels ?>' data-values='<?= $chartConf ?>'></canvas>
        </div>
    </div>
</div>

<!-- ---- Tabel Hasil Ranking ---- -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Tabel Hasil Ranking MOORA</div>
        <div class="flex gap-8">
            <select id="filter-rekomendasi" class="form-control" style="width: auto; padding: 6px 12px; font-size: 12px;">
                <option value="">Semua</option>
                <option value="IPA">IPA Saja</option>
                <option value="IPS">IPS Saja</option>
            </select>
            <input type="text" id="search-input" class="form-control" style="width: 180px; padding: 6px 12px; font-size: 12px;" placeholder="Cari nama...">
            <a href="../export/pdf.php<?= $sesiId ? '?sesi_id=' . $sesiId : '' ?>" class="btn btn-secondary btn-sm" target="_blank">PDF</a>
            <a href="../export/csv.php<?= $sesiId ? '?sesi_id=' . $sesiId : '' ?>" class="btn btn-secondary btn-sm">CSV</a>
        </div>
    </div>
    <div class="table-wrapper" id="search-table">
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th data-sort="nis">NIS</th>
                    <th data-sort="nama">Nama</th>
                    <th data-sort="skor_ipa" title="Skor MOORA untuk peminatan IPA">Skor IPA</th>
                    <th data-sort="skor_ips" title="Skor MOORA untuk peminatan IPS">Skor IPS</th>
                    <th data-sort="yi" title="Yi = Skor IPA + Skor IPS">Yi (Total)</th>
                    <th data-sort="confidence">Confidence</th>
                    <th>Rekomendasi</th>
                    <th class="no-print">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hasilList as $i => $h):
                    $rankClass = $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : ''));
                    $rekIPA    = $h['rekomendasi'] === 'IPA';
                ?>
                <tr>
                    <td>
                        <span class="rank-badge <?= $rankClass ?>"><?= $h['no_urut'] ?></span>
                    </td>
                    <td class="td-mono"><?= htmlspecialchars($h['siswa']['nis']) ?></td>
                    <td class="td-highlight"><?= htmlspecialchars($h['siswa']['nama']) ?></td>
                    <td data-raw="<?= $h['skor_ipa'] ?>" class="font-mono" style="color: var(--ipa-light);">
                        <?= MOORA::formatAngka($h['skor_ipa'], 6) ?>
                    </td>
                    <td data-raw="<?= $h['skor_ips'] ?>" class="font-mono" style="color: var(--ips-light);">
                        <?= MOORA::formatAngka($h['skor_ips'], 6) ?>
                    </td>
                    <td data-raw="<?= $h['yi'] ?>" class="font-mono text-secondary">
                        <?= MOORA::formatAngka($h['yi'], 6) ?>
                    </td>
                    <td data-raw="<?= $h['confidence'] ?>">
                        <div class="confidence-bar">
                            <div class="confidence-track">
                                <div class="confidence-fill <?= strtolower($h['rekomendasi']) ?>"
                                     data-value="<?= $h['confidence'] ?>"
                                     style="width: <?= min($h['confidence'], 100) ?>%">
                                </div>
                            </div>
                            <span class="confidence-text" style="color: <?= $rekIPA ? 'var(--ipa-light)' : 'var(--ips-light)' ?>">
                                <?= number_format($h['confidence'], 1) ?>%
                            </span>
                        </div>
                    </td>
                    <td data-rekomendasi="<?= $h['rekomendasi'] ?>">
                        <span class="badge badge-<?= strtolower($h['rekomendasi']) ?>" style="font-size: 12px; padding: 6px 14px;">
                            <?= $h['rekomendasi'] ?>
                        </span>
                    </td>
                    <td class="no-print">
                        <a href="detail.php?sesi_id=<?= $sesiId ?>&nis=<?= urlencode($h['siswa']['nis']) ?>" class="btn btn-secondary btn-sm" title="Lihat penjelasan langkah-per-langkah perhitungan MOORA">
                            🔍 Detail Perhitungan
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-body text-center" style="padding: 64px;">
        <div style="font-size: 64px; margin-bottom: 16px; color: var(--text-muted);"><svg width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg></div>
        <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">Belum ada hasil perhitungan</div>
        <div class="text-secondary">Pastikan sudah ada data siswa, kemudian tekan tombol <strong>Hitung Rekomendasi</strong> di atas.</div>
        <div class="mt-16">
            <a href="../siswa/index.php" class="btn btn-secondary">Lihat Data Siswa</a>
            <a href="../siswa/import.php" class="btn btn-primary" style="margin-left: 8px;">Import CSV</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php layoutEnd(); ?>
