<?php
// ============================================================
// modules/histori/index.php — Histori Sesi Perhitungan
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Logger.php';
require_once __DIR__ . '/../../includes/layout.php';

// Handle hapus sesi (ADMIN ONLY)
if (isAdmin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_sesi_id'])) {
    Logger::hapusSesi((int) $_POST['hapus_sesi_id']);
    header('Location: index.php?deleted=1');
    exit;
}

$daftarSesi = Logger::getDaftarSesi();
layoutStart('Histori Perhitungan', 'histori');
?>

<div class="flex-between mb-24">
    <div class="section-heading">Histori Sesi Perhitungan</div>
</div>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success" data-auto-dismiss="3000">
    <span class="alert-icon">✅</span> Sesi berhasil dihapus.
</div>
<?php endif; ?>

<div class="alert alert-info mb-24">
    <span class="alert-icon">💡</span>
    <div>
        Histori menyimpan setiap sesi perhitungan yang pernah dijalankan.
        Berguna untuk <strong>membandingkan hasil sebelum-sesudah perubahan</strong> data atau logika,
        terutama untuk audit bug seperti yang pernah ditemukan sebelumnya.
    </div>
</div>

<?php if (empty($daftarSesi)): ?>
<div class="card">
    <div class="card-body text-center" style="padding: 64px;">
        <div style="font-size: 48px; margin-bottom: 12px; color: var(--text-muted);"><svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></div>
        <div style="font-size: 15px; font-weight: 600; margin-bottom: 8px;">Belum ada histori</div>
        <div class="text-secondary">Jalankan perhitungan MOORA untuk mulai menyimpan histori.</div>
        <div class="mt-16">
            <a href="../perhitungan/index.php" class="btn btn-primary">Ke Halaman Perhitungan</a>
        </div>
    </div>
</div>
<?php else: ?>

<div style="display: grid; gap: 16px;">
    <?php foreach ($daftarSesi as $i => $sesi):
        $jmlIPA = $sesi['jumlah_ipa'] ?? 0;
        $jmlIPS = $sesi['jumlah_ips'] ?? 0;
        $total  = $sesi['jumlah_hasil'] ?? 0;
        $pctIPA = $total > 0 ? round($jmlIPA / $total * 100) : 0;
    ?>
    <div class="card">
        <div class="card-body" style="padding: 20px 24px;">
            <div class="flex-between" style="flex-wrap: wrap; gap: 12px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
                        <span style="background: var(--ipa-bg); color: var(--ipa-light); padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 700; font-family: monospace;">
                            #<?= $sesi['id'] ?>
                        </span>
                        <span style="font-size: 16px; font-weight: 700; color: var(--text-primary);">
                            <?= htmlspecialchars($sesi['nama_sesi']) ?>
                        </span>
                    </div>
                    <div class="flex gap-16" style="font-size: 12px; color: var(--text-secondary);">
                        <span><?= date('d M Y, H:i', strtotime($sesi['created_at'])) ?></span>
                        <span><?= $total ?> siswa</span>
                        <span>C1=<?= $sesi['bobot_c1']*100 ?>% C2=<?= $sesi['bobot_c2']*100 ?>% C3=<?= $sesi['bobot_c3']*100 ?>% C4=<?= $sesi['bobot_c4']*100 ?>% C5=<?= $sesi['bobot_c5']*100 ?>%</span>
                    </div>
                </div>

                <div class="flex gap-8" style="align-items: center; flex-wrap: wrap;">
                    <div style="text-align: center; min-width: 100px;">
                        <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">IPA / IPS</div>
                        <div>
                            <span class="badge badge-ipa"><?= $jmlIPA ?> IPA</span>
                            <span class="badge badge-ips" style="margin-left: 4px;"><?= $jmlIPS ?> IPS</span>
                        </div>
                        <div class="confidence-bar mt-8" style="min-width: 100px;">
                            <div class="confidence-track">
                                <div class="confidence-fill ipa" style="width: <?= $pctIPA ?>%"></div>
                            </div>
                            <span style="font-size: 11px; color: var(--text-muted); font-family: monospace;"><?= $pctIPA ?>%</span>
                        </div>
                    </div>

                    <a href="../perhitungan/index.php?sesi_id=<?= $sesi['id'] ?>" class="btn btn-secondary btn-sm">
                        Lihat Hasil
                    </a>
                    <a href="../export/pdf.php?sesi_id=<?= $sesi['id'] ?>" class="btn btn-secondary btn-sm" target="_blank">
                        PDF
                    </a>
                    <a href="../export/csv.php?sesi_id=<?= $sesi['id'] ?>" class="btn btn-secondary btn-sm">
                        CSV
                    </a>

                    <?php if (isAdmin()): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="hapus_sesi_id" value="<?= $sesi['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"
                                data-confirm="Hapus sesi '<?= htmlspecialchars($sesi['nama_sesi']) ?>'? Semua hasil perhitungan sesi ini akan terhapus.">
                            🗑
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php layoutEnd(); ?>
