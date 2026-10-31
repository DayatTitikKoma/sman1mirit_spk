<?php
// ============================================================
// modules/users/edit.php — Edit Data Pengguna
// Hanya dapat diakses oleh Admin
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
requireRole('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/layout.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: index.php');
    exit;
}

$user = dbQueryOne('SELECT * FROM users WHERE id = ?', [$id]);
if (!$user) {
    header('Location: index.php');
    exit;
}

$errors  = [];
$success = false;

// Form data (sticky)
$form = [
    'nama'     => $user['nama'],
    'username' => $user['username'],
    'role'     => $user['role'],
];

$isAdminMain = ($user['username'] === 'admin'); // akun admin utama — role & username tidak bisa diubah

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nama']     = trim($_POST['nama']        ?? '');
    $form['username'] = trim($_POST['username']    ?? '');
    $form['role']     = trim($_POST['role']        ?? '');
    $password         = $_POST['password']         ?? '';
    $passwordConfirm  = $_POST['password_confirm'] ?? '';

    // Validasi nama
    if (empty($form['nama'])) $errors['nama'] = 'Nama tidak boleh kosong.';

    // Validasi username (kecuali akun admin utama)
    if (!$isAdminMain) {
        if (empty($form['username'])) {
            $errors['username'] = 'Username tidak boleh kosong.';
        } elseif (strlen($form['username']) < 4) {
            $errors['username'] = 'Username minimal 4 karakter.';
        } elseif (!preg_match('/^[a-z0-9_]+$/i', $form['username'])) {
            $errors['username'] = 'Username hanya boleh huruf, angka, dan underscore.';
        } else {
            // Cek duplikat username (kecuali milik sendiri)
            $exists = dbQueryOne('SELECT id FROM users WHERE username = ? AND id != ?', [$form['username'], $id]);
            if ($exists) $errors['username'] = 'Username sudah digunakan oleh pengguna lain.';
        }

        // Validasi role
        if (!in_array($form['role'], ['admin', 'guru_bk'])) {
            $errors['role'] = 'Pilih role yang valid.';
        }
    } else {
        // Akun admin utama: paksa nilai tetap
        $form['username'] = 'admin';
        $form['role']     = 'admin';
    }

    // Validasi password (opsional saat edit — kosong = tidak diubah)
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors['password'] = 'Password minimal 6 karakter.';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Konfirmasi password tidak cocok.';
        }
    }

    if (empty($errors)) {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            dbExecute(
                'UPDATE users SET username=?, nama=?, role=?, password=? WHERE id=?',
                [$form['username'], $form['nama'], $form['role'], $hash, $id]
            );
        } else {
            dbExecute(
                'UPDATE users SET username=?, nama=?, role=? WHERE id=?',
                [$form['username'], $form['nama'], $form['role'], $id]
            );
        }
        header('Location: index.php?updated=1');
        exit;
    }
}

layoutStart('Edit Pengguna', 'users');
?>

<div class="flex-between mb-24">
    <div class="section-heading">✏️ Edit Pengguna</div>
    <a href="index.php" class="btn btn-secondary">← Kembali</a>
</div>

<div class="card" style="max-width: 560px;">
    <div class="card-header">
        <div class="card-title">Edit: <?= htmlspecialchars($user['nama']) ?></div>
        <?php
            $roleBadge = $user['role'] === 'admin' ? 'ipa' : 'ips';
            $roleLabel = $user['role'] === 'admin' ? 'Administrator' : 'Guru BK';
        ?>
        <span class="badge badge-<?= $roleBadge ?>"><?= $roleLabel ?></span>
    </div>
    <div class="card-body">
        <?php if ($isAdminMain): ?>
        <div class="alert alert-info mb-20" style="font-size:12px;">
            <span class="alert-icon">🔒</span>
            Username dan role akun <strong>admin</strong> utama tidak dapat diubah untuk menjaga keamanan sistem.
        </div>
        <?php endif; ?>

        <form method="POST" novalidate>

            <!-- Nama Lengkap -->
            <div class="form-group mb-16">
                <label class="form-label">Nama Lengkap <span style="color:var(--danger)">*</span></label>
                <input type="text" name="nama" id="input-nama"
                       class="form-control <?= isset($errors['nama']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($form['nama']) ?>"
                       placeholder="Nama lengkap pengguna">
                <?php if (isset($errors['nama'])): ?>
                <div class="form-error"><?= $errors['nama'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Username -->
            <div class="form-group mb-16">
                <label class="form-label">Username</label>
                <?php if ($isAdminMain): ?>
                <input type="text" class="form-control" value="admin" disabled
                       style="opacity:.6;cursor:not-allowed;">
                <input type="hidden" name="username" value="admin">
                <?php else: ?>
                <input type="text" name="username" id="input-username"
                       class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($form['username']) ?>"
                       autocomplete="off">
                <div class="form-hint">Hanya huruf, angka, dan underscore. Minimal 4 karakter.</div>
                <?php if (isset($errors['username'])): ?>
                <div class="form-error"><?= $errors['username'] ?></div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Role -->
            <div class="form-group mb-16">
                <label class="form-label">Role / Hak Akses</label>
                <?php if ($isAdminMain): ?>
                <input type="text" class="form-control" value="Administrator" disabled
                       style="opacity:.6;cursor:not-allowed;">
                <input type="hidden" name="role" value="admin">
                <?php else: ?>
                <select name="role" id="input-role"
                        class="form-control <?= isset($errors['role']) ? 'is-invalid' : '' ?>">
                    <option value="guru_bk" <?= $form['role'] === 'guru_bk' ? 'selected' : '' ?>>
                        Guru BK — hanya bisa melihat hasil
                    </option>
                    <option value="admin" <?= $form['role'] === 'admin' ? 'selected' : '' ?>>
                        Administrator — akses penuh ke semua fitur
                    </option>
                </select>
                <?php if (isset($errors['role'])): ?>
                <div class="form-error"><?= $errors['role'] ?></div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Password (opsional) -->
            <div class="form-group mb-16">
                <label class="form-label">Password Baru <span class="text-muted" style="font-weight:400;">(kosongkan jika tidak ingin mengubah)</span></label>
                <input type="password" name="password" id="input-password"
                       class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                       placeholder="Minimal 6 karakter"
                       autocomplete="new-password">
                <?php if (isset($errors['password'])): ?>
                <div class="form-error"><?= $errors['password'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Konfirmasi Password -->
            <div class="form-group mb-24">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirm" id="input-password-confirm"
                       class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                       placeholder="Ulangi password baru"
                       autocomplete="new-password">
                <?php if (isset($errors['password_confirm'])): ?>
                <div class="form-error"><?= $errors['password_confirm'] ?></div>
                <?php endif; ?>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary" id="btn-simpan">
                    💾 Simpan Perubahan
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php layoutEnd(); ?>
