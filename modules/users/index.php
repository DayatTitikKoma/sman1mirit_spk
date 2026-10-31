<?php
// ============================================================
// modules/users/index.php — Kelola Pengguna
// Hanya dapat diakses oleh Admin
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
requireRole('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/layout.php';

$message = '';
$msgType = '';

// Handle hapus user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $hapusId = (int) $_POST['hapus_id'];
    $currentUserId = $_SESSION['user_id'] ?? 0;

    if ($hapusId === (int) $currentUserId) {
        $message = 'Anda tidak dapat menghapus akun yang sedang digunakan.';
        $msgType = 'danger';
    } else {
        $target = dbQueryOne('SELECT username, role FROM users WHERE id = ?', [$hapusId]);
        if ($target && $target['username'] === 'admin') {
            $message = 'Akun admin utama tidak dapat dihapus.';
            $msgType = 'danger';
        } else {
            dbExecute('DELETE FROM users WHERE id = ?', [$hapusId]);
            $message = 'Pengguna berhasil dihapus.';
            $msgType = 'success';
        }
    }
}

// Ambil semua user
$users = dbQuery('SELECT id, username, nama, role, created_at FROM users ORDER BY role ASC, nama ASC');

$roleLabels = [
    'admin'   => 'Administrator',
    'guru_bk' => 'Guru BK',
];

layoutStart('Kelola Pengguna', 'users');
?>

<div class="flex-between mb-24">
    <div class="section-heading">👤 Kelola Pengguna</div>
    <a href="create.php" class="btn btn-primary">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px;vertical-align:-2px"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Tambah Pengguna
    </a>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $msgType ?> mb-16" data-auto-dismiss="4000">
    <span class="alert-icon"><?= $msgType === 'success' ? '✅' : '⚠️' ?></span>
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['created'])): ?>
<div class="alert alert-success mb-16" data-auto-dismiss="4000">
    <span class="alert-icon">✅</span> Pengguna baru berhasil ditambahkan.
</div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
<div class="alert alert-success mb-16" data-auto-dismiss="4000">
    <span class="alert-icon">✅</span> Data pengguna berhasil diperbarui.
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Daftar Akun Pengguna</div>
        <span class="badge badge-info"><?= count($users) ?> pengguna</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="width:40px">No</th>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Dibuat</th>
                    <th style="width:140px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted" style="padding:32px;">
                        Belum ada data pengguna.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($users as $i => $u): ?>
                <?php
                    $isSelf   = ((int)$u['id'] === (int)($_SESSION['user_id'] ?? 0));
                    $isAdmin  = ($u['role'] === 'admin' && $u['username'] === 'admin');
                    $roleBadge = $u['role'] === 'admin' ? 'ipa' : 'ips';
                    $roleLabel = $roleLabels[$u['role']] ?? $u['role'];
                ?>
                <tr>
                    <td class="font-mono text-muted"><?= $i + 1 ?></td>
                    <td class="td-highlight">
                        <?= htmlspecialchars($u['nama']) ?>
                        <?php if ($isSelf): ?>
                        <span style="font-size:10px;background:#fef3c7;color:#92400e;padding:1px 7px;border-radius:100px;margin-left:6px;font-weight:700;">Anda</span>
                        <?php endif; ?>
                    </td>
                    <td class="td-mono"><?= htmlspecialchars($u['username']) ?></td>
                    <td>
                        <span class="badge badge-<?= $roleBadge ?>"><?= $roleLabel ?></span>
                    </td>
                    <td class="text-muted" style="font-size:12px;">
                        <?= date('d M Y', strtotime($u['created_at'])) ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="edit.php?id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <?php if (!$isSelf && !$isAdmin): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="hapus_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm"
                                        data-confirm="Hapus pengguna '<?= htmlspecialchars($u['nama']) ?>'?">
                                    Hapus
                                </button>
                            </form>
                            <?php else: ?>
                            <button class="btn btn-secondary btn-sm" disabled title="Tidak dapat dihapus" style="opacity:0.4;cursor:not-allowed;">Hapus</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-info mt-24" style="font-size:12px;">
    <span class="alert-icon">💡</span>
    <div>
        <strong>Catatan:</strong>
        Akun <code>admin</code> utama dan akun yang sedang aktif tidak dapat dihapus.
        Role <strong>Administrator</strong> memiliki akses penuh ke seluruh sistem.
        Role <strong>Guru BK</strong> hanya dapat melihat hasil perhitungan dan perangkingan.
    </div>
</div>

<?php layoutEnd(); ?>
