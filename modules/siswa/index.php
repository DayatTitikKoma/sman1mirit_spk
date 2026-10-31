<?php
// ============================================================
// modules/siswa/index.php — Daftar Semua Siswa
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
requireRole('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/layout.php';

// Handle delete single & bulk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $id = (int) $_POST['delete_id'];
        dbExecute('DELETE FROM siswa WHERE id = ?', [$id]);
        header('Location: index.php?deleted=1');
        exit;
    } elseif (isset($_POST['bulk_delete']) && !empty($_POST['ids']) && is_array($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        dbExecute("DELETE FROM siswa WHERE id IN ($placeholders)", $ids);
        header('Location: index.php?deleted=' . count($ids));
        exit;
    }
}

// Ambil semua siswa
$siswaList = dbQuery(
    'SELECT s.*,
            (SELECT rekomendasi FROM hasil_perhitungan h
             JOIN sesi_perhitungan sp ON sp.id = h.sesi_id
             WHERE h.siswa_id = s.id
             ORDER BY sp.created_at DESC LIMIT 1) as rekomendasi_terakhir
     FROM siswa s
     ORDER BY s.nama ASC'
);

layoutStart('Data Siswa', 'siswa');
?>

<!-- Toolbar -->
<div class="flex-between mb-24 gap-12" style="flex-wrap: wrap;">
    <div class="section-heading" style="flex: 1;">Data Siswa</div>
    <div class="flex gap-8">
        <button type="submit" form="bulk-delete-form" class="btn btn-danger" id="btn-bulk-delete" disabled onclick="return confirm('Yakin ingin menghapus siswa yang dipilih? Data tidak bisa dikembalikan.')">
            Hapus Terpilih (<span id="bulk-count">0</span>)
        </button>
        <a href="create.php" class="btn btn-primary">Tambah Siswa</a>
        <a href="import.php" class="btn btn-secondary">Import CSV</a>
        <a href="../../modules/export/csv.php" class="btn btn-secondary">Export CSV</a>
    </div>
</div>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success" data-auto-dismiss="3000">
    <?= (int)$_GET['deleted'] ?> data siswa berhasil dihapus.
</div>
<?php endif; ?>

<?php if (isset($_GET['created'])): ?>
<div class="alert alert-success" data-auto-dismiss="3000">
    Data siswa berhasil ditambahkan.
</div>
<?php endif; ?>

<!-- Search & Filter -->
<div class="card mb-16">
    <div class="card-body" style="padding: 16px;">
        <div class="form-row">
            <div class="form-group" style="margin-bottom: 0;">
                <input type="text" id="search-input" class="form-control" placeholder="🔍 Cari NIS atau Nama...">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <select id="filter-rekomendasi" class="form-control">
                    <option value="">Semua Rekomendasi</option>
                    <option value="IPA">Rekomendasi IPA</option>
                    <option value="IPS">Rekomendasi IPS</option>
                </select>
            </div>
            <div style="align-self: flex-end;">
                <span class="text-secondary" style="font-size: 12px;">Total: <strong class="text-primary"><?= count($siswaList) ?></strong> siswa</span>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Siswa -->
<div class="card">
    <form method="POST" id="bulk-delete-form">
        <input type="hidden" name="bulk_delete" value="1">
        <div class="table-wrapper" id="search-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;"><input type="checkbox" id="check-all"></th>
                        <th>No</th>
                    <th data-sort="nis">NIS</th>
                    <th data-sort="nama">Nama</th>
                    <th data-sort="c1">C1 Matematika</th>
                    <th data-sort="c2">C2 IPA</th>
                    <th data-sort="c3">C3 IPS</th>
                    <th>C4 Minat Siswa</th>
                    <th>C5 Minat Ortu</th>
                    <th>Rek. Terakhir</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($siswaList)): ?>
                <tr>
                    <td colspan="11" class="text-center text-muted" style="padding: 48px;">
                        <div style="font-size: 48px; margin-bottom: 12px; color: var(--text-muted);"><svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                        <div style="font-size: 15px; font-weight: 600;">Belum ada data siswa</div>
                        <div style="font-size: 13px; margin-top: 4px;">
                            <a href="create.php" class="text-ipa">Tambah siswa manual</a>
                            atau
                            <a href="import.php" class="text-ips">import dari CSV</a>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
            <?php foreach ($siswaList as $i => $s): ?>
                <tr>
                    <td style="text-align: center;"><input type="checkbox" name="ids[]" value="<?= $s['id'] ?>" class="check-item"></td>
                    <td style="width: 40px; color: var(--text-muted);"><?= $i + 1 ?></td>
                    <td class="td-mono"><?= htmlspecialchars($s['nis']) ?></td>
                    <td class="td-highlight"><?= htmlspecialchars($s['nama']) ?></td>
                    <td data-raw="<?= $s['c1_matematika'] ?>" class="font-mono text-center"><?= number_format($s['c1_matematika'], 1) ?></td>
                    <td data-raw="<?= $s['c2_ipa'] ?>" class="font-mono text-center"><?= number_format($s['c2_ipa'], 1) ?></td>
                    <td data-raw="<?= $s['c3_ips'] ?>" class="font-mono text-center"><?= number_format($s['c3_ips'], 1) ?></td>
                    <td><span class="badge badge-<?= strtolower($s['c4_minat_siswa']) ?>"><?= $s['c4_minat_siswa'] ?></span></td>
                    <td><span class="badge badge-<?= strtolower($s['c5_minat_ortu']) ?>"><?= $s['c5_minat_ortu'] ?></span></td>
                    <td data-rekomendasi="<?= htmlspecialchars($s['rekomendasi_terakhir'] ?? '') ?>">
                        <?php if ($s['rekomendasi_terakhir']): ?>
                        <span class="badge badge-<?= strtolower($s['rekomendasi_terakhir']) ?>">
                            <?= $s['rekomendasi_terakhir'] === 'IPA' ? '🔬' : '📚' ?>
                            <?= $s['rekomendasi_terakhir'] ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted" style="font-size: 11px;">Belum dihitung</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex gap-8">
                            <a href="show.php?id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">Detail</a>
                            <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <button type="submit" form="bulk-delete-form" name="delete_id" value="<?= $s['id'] ?>" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Hapus siswa <?= htmlspecialchars(addslashes($s['nama'])) ?>? Data tidak bisa dikembalikan.');">
                                Hapus
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkAll = document.getElementById('check-all');
    const checkItems = document.querySelectorAll('.check-item');
    const btnBulk = document.getElementById('btn-bulk-delete');
    const bulkCount = document.getElementById('bulk-count');

    function updateBulkButton() {
        const checkedCount = document.querySelectorAll('.check-item:checked').length;
        bulkCount.textContent = checkedCount;
        btnBulk.disabled = checkedCount === 0;
        checkAll.checked = (checkedCount > 0 && checkedCount === checkItems.length);
    }

    if (checkAll) {
        checkAll.addEventListener('change', (e) => {
            checkItems.forEach(item => item.checked = e.target.checked);
            updateBulkButton();
        });
    }

    checkItems.forEach(item => {
        item.addEventListener('change', updateBulkButton);
    });
});
</script>

<?php layoutEnd(); ?>
