<?php
// ============================================================
// modules/users/create.php — Tambah Pengguna Baru
// Hanya dapat diakses oleh Admin
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
requireRole('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/layout.php';

$errors  = [];
$success = false;

// Data form (sticky)
$form = [
    'nama'     => '',
    'username' => '',
    'role'     => 'guru_bk',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nama']     = trim($_POST['nama']     ?? '');
    $form['username'] = trim($_POST['username'] ?? '');
    $form['role']     = trim($_POST['role']     ?? '');
    $password         = $_POST['password']         ?? '';
    $passwordConfirm  = $_POST['password_confirm'] ?? '';

    // Validasi
    if (empty($form['nama']))     $errors['nama']     = 'Nama tidak boleh kosong.';
    if (empty($form['username'])) $errors['username'] = 'Username tidak boleh kosong.';
    if (strlen($form['username']) < 4) $errors['username'] = 'Username minimal 4 karakter.';
    if (!preg_match('/^[a-z0-9_]+$/i', $form['username'])) {
        $errors['username'] = 'Username hanya boleh huruf, angka, dan underscore.';
    }
    if (!in_array($form['role'], ['admin', 'guru_bk'])) {
        $errors['role'] = 'Pilih role yang valid.';
    }
    if (empty($password)) {
        $errors['password'] = 'Password tidak boleh kosong.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password minimal 6 karakter.';
    } elseif ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Konfirmasi password tidak cocok.';
    }

    // Cek duplikat username
    if (empty($errors['username'])) {
        $exists = dbQueryOne('SELECT id FROM users WHERE username = ?', [$form['username']]);
        if ($exists) $errors['username'] = 'Username sudah digunakan.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        dbExecute(
            'INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, ?)',
            [$form['username'], $hash, $form['nama'], $form['role']]
        );
        header('Location: index.php?created=1');
        exit;
    }
}

layoutStart('Tambah Pengguna', 'users');
?>

<div class="flex-between mb-24">
    <div class="section-heading">➕ Tambah Pengguna Baru</div>
    <a href="index.php" class="btn btn-secondary">← Kembali</a>
</div>

<div class="card" style="max-width: 560px;">
    <div class="card-header">
        <div class="card-title">Formulir Pengguna Baru</div>
    </div>
    <div class="card-body">
        <form method="POST" novalidate>

            <!-- Nama Lengkap -->
            <div class="form-group mb-16">
                <label class="form-label">Nama Lengkap <span style="color:var(--danger)">*</span></label>
                <input type="text" name="nama" id="input-nama"
                       class="form-control <?= isset($errors['nama']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($form['nama']) ?>"
                       placeholder="Contoh: Siti Rahayu">
                <?php if (isset($errors['nama'])): ?>
                <div class="form-error"><?= $errors['nama'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Username -->
            <div class="form-group mb-16">
                <label class="form-label">Username <span style="color:var(--danger)">*</span></label>
                <input type="text" name="username" id="input-username"
                       class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($form['username']) ?>"
                       placeholder="Contoh: guru_bk2"
                       autocomplete="off">
                <div class="form-hint">Hanya huruf, angka, dan underscore. Minimal 4 karakter.</div>
                <?php if (isset($errors['username'])): ?>
                <div class="form-error"><?= $errors['username'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Role -->
            <div class="form-group mb-16">
                <label class="form-label">Role / Hak Akses <span style="color:var(--danger)">*</span></label>
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
            </div>

            <!-- Password -->
            <div class="form-group mb-16">
                <label class="form-label">Password <span style="color:var(--danger)">*</span></label>
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
                <label class="form-label">Konfirmasi Password <span style="color:var(--danger)">*</span></label>
                <input type="password" name="password_confirm" id="input-password-confirm"
                       class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                       placeholder="Ulangi password"
                       autocomplete="new-password">
                <?php if (isset($errors['password_confirm'])): ?>
                <div class="form-error"><?= $errors['password_confirm'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Informasi Role -->
            <div class="alert alert-info mb-24" style="font-size:12px;">
                <span class="alert-icon">ℹ️</span>
                <div>
                    <strong>Administrator:</strong> Kelola siswa, kriteria, jalankan perhitungan MOORA.<br>
                    <strong>Guru BK:</strong> Hanya melihat hasil perhitungan, perangkingan & rekomendasi peminatan.
                </div>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary" id="btn-simpan">
                    💾 Simpan Pengguna
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php layoutEnd(); ?>
