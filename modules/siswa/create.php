<?php
// ============================================================
// modules/siswa/create.php — Form Tambah/Edit Siswa
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
requireRole('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/layout.php';

$errors  = [];
$success = false;
$edit    = isset($_GET['id']) && is_numeric($_GET['id']);
$siswa   = null;

// Mode edit: ambil data yang ada
if ($edit) {
    $siswa = dbQueryOne('SELECT * FROM siswa WHERE id = ?', [(int) $_GET['id']]);
    if (!$siswa) {
        header('Location: index.php');
        exit;
    }
}

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis     = trim($_POST['nis'] ?? '');
    $nama    = trim($_POST['nama'] ?? '');
    $c1      = $_POST['c1_matematika'] ?? '';
    $c2      = $_POST['c2_ipa'] ?? '';
    $c3      = $_POST['c3_ips'] ?? '';
    $c4      = strtoupper(trim($_POST['c4_minat_siswa'] ?? ''));
    $c5      = strtoupper(trim($_POST['c5_minat_ortu'] ?? ''));

    // Validasi
    if (empty($nis))  $errors['nis']  = 'NIS tidak boleh kosong';
    if (empty($nama)) $errors['nama'] = 'Nama tidak boleh kosong';

    foreach (['c1' => $c1, 'c2' => $c2, 'c3' => $c3] as $key => $val) {
        if (!is_numeric($val) || $val < 0 || $val > 100) {
            $errors[$key] = 'Nilai harus berupa angka antara 0 - 100';
        }
    }

    if (!in_array($c4, ['IPA', 'IPS', 'RAGU'])) $errors['c4'] = 'Pilih IPA, IPS, atau Ragu-ragu';
    if (!in_array($c5, ['IPA', 'IPS', 'RAGU'])) $errors['c5'] = 'Pilih IPA, IPS, atau Ragu-ragu';

    // Cek duplikat NIS (kecuali saat edit diri sendiri)
    if (empty($errors['nis'])) {
        $existingId = $edit ? (int) $_GET['id'] : 0;
        $dup = dbQueryOne('SELECT id FROM siswa WHERE nis = ? AND id != ?', [$nis, $existingId]);
        if ($dup) $errors['nis'] = 'NIS sudah terdaftar';
    }

    if (empty($errors)) {
        if ($edit) {
            dbExecute(
                'UPDATE siswa SET nis=?, nama=?, c1_matematika=?, c2_ipa=?, c3_ips=?, c4_minat_siswa=?, c5_minat_ortu=? WHERE id=?',
                [$nis, $nama, $c1, $c2, $c3, $c4, $c5, (int) $_GET['id']]
            );
            header('Location: index.php?created=1');
        } else {
            dbExecute(
                'INSERT INTO siswa (nis, nama, c1_matematika, c2_ipa, c3_ips, c4_minat_siswa, c5_minat_ortu) VALUES (?,?,?,?,?,?,?)',
                [$nis, $nama, $c1, $c2, $c3, $c4, $c5]
            );
            header('Location: index.php?created=1');
        }
        exit;
    }

    // Isi ulang field yang sudah diisi jika ada error
    $siswa = compact('nis', 'nama', 'c1', 'c2', 'c3', 'c4', 'c5');
    $siswa['c1_matematika'] = $c1;
    $siswa['c2_ipa']        = $c2;
    $siswa['c3_ips']        = $c3;
    $siswa['c4_minat_siswa'] = $c4;
    $siswa['c5_minat_ortu']  = $c5;
}

$pageTitle = $edit ? 'Edit Data Siswa' : 'Tambah Siswa Baru';
layoutStart($pageTitle, 'siswa');
?>

<div style="max-width: 680px; margin: 0 auto;">
    <div class="flex-between mb-24">
        <div class="section-heading">
            <?= $edit ? '✏️ Edit Data Siswa' : '➕ Tambah Siswa Baru' ?>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">Kembali</a>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <div>
        <div>
            <strong>Terdapat kesalahan input:</strong>
            <ul style="margin: 6px 0 0 16px;">
                <?php foreach ($errors as $e): ?>
                <li style="font-size: 12px;"><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Data Nilai & Kriteria</div>
        </div>
        <div class="card-body">
            <form method="POST">
                <!-- Identitas -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">NIS *</label>
                        <input type="text" name="nis" class="form-control"
                               placeholder="Contoh: 2024001"
                               value="<?= htmlspecialchars($siswa['nis'] ?? '') ?>">
                        <?php if (isset($errors['nis'])): ?>
                        <div class="form-error"><?= $errors['nis'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="nama" class="form-control"
                               placeholder="Nama siswa"
                               value="<?= htmlspecialchars($siswa['nama'] ?? '') ?>">
                        <?php if (isset($errors['nama'])): ?>
                        <div class="form-error"><?= $errors['nama'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="divider"></div>
                <div style="font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 16px;">
                    Nilai Rapor Kelas 10 (Skala 0–100)
                </div>

                <!-- Nilai -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">C1 — Nilai Matematika (35%)</label>
                        <input type="number" name="c1_matematika" class="form-control"
                               min="0" max="100" step="0.01" placeholder="0 - 100"
                               value="<?= htmlspecialchars($siswa['c1_matematika'] ?? '') ?>">
                        <?php if (isset($errors['c1'])): ?>
                        <div class="form-error"><?= $errors['c1'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">C2 — Nilai IPA (25%)</label>
                        <input type="number" name="c2_ipa" class="form-control"
                               min="0" max="100" step="0.01" placeholder="0 - 100"
                               value="<?= htmlspecialchars($siswa['c2_ipa'] ?? '') ?>">
                        <?php if (isset($errors['c2'])): ?>
                        <div class="form-error"><?= $errors['c2'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">C3 — Nilai IPS (20%)</label>
                        <input type="number" name="c3_ips" class="form-control"
                               min="0" max="100" step="0.01" placeholder="0 - 100"
                               value="<?= htmlspecialchars($siswa['c3_ips'] ?? '') ?>">
                        <?php if (isset($errors['c3'])): ?>
                        <div class="form-error"><?= $errors['c3'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="divider"></div>
                <div style="font-size: 13px; font-weight: 600; color: var(--text-secondary); margin-bottom: 16px;">
                    Data Angket Minat
                </div>

                <!-- Minat -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">C4 — Minat Siswa (15%)</label>
                        <select name="c4_minat_siswa" class="form-control">
                            <option value="">-- Pilih Minat --</option>
                            <option value="IPA" <?= ($siswa['c4_minat_siswa'] ?? '') === 'IPA' ? 'selected' : '' ?>>IPA</option>
                            <option value="IPS" <?= ($siswa['c4_minat_siswa'] ?? '') === 'IPS' ? 'selected' : '' ?>>IPS</option>
                            <option value="RAGU" <?= ($siswa['c4_minat_siswa'] ?? '') === 'RAGU' ? 'selected' : '' ?>>Ragu-ragu</option>
                        </select>
                        <?php if (isset($errors['c4'])): ?>
                        <div class="form-error"><?= $errors['c4'] ?></div>
                        <?php endif; ?>
                        <div class="form-hint">Pilihan siswa dari angket Google Form</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">C5 — Minat Orang Tua (5%)</label>
                        <select name="c5_minat_ortu" class="form-control">
                            <option value="">-- Pilih Minat --</option>
                            <option value="IPA" <?= ($siswa['c5_minat_ortu'] ?? '') === 'IPA' ? 'selected' : '' ?>>IPA</option>
                            <option value="IPS" <?= ($siswa['c5_minat_ortu'] ?? '') === 'IPS' ? 'selected' : '' ?>>IPS</option>
                            <option value="RAGU" <?= ($siswa['c5_minat_ortu'] ?? '') === 'RAGU' ? 'selected' : '' ?>>Ragu-ragu</option>
                        </select>
                        <?php if (isset($errors['c5'])): ?>
                        <div class="form-error"><?= $errors['c5'] ?></div>
                        <?php endif; ?>
                        <div class="form-hint">Pilihan orang tua dari angket Google Form</div>
                    </div>
                </div>

                <div class="alert alert-info mt-16" style="font-size: 11px;">
                    <div>
                        <strong>Catatan Encoding C4 & C5:</strong>
                        Pilihan IPA/IPS akan diubah ke angka secara otomatis saat perhitungan MOORA:
                        <strong>IPA = 100</strong>, <strong>Ragu-ragu = 50</strong>, <strong>IPS = 0</strong> untuk skor IPA (berlaku sebaliknya untuk IPS).
                    </div>
                </div>

                <div class="card-footer" style="padding: 20px 0 0; border-top: 1px solid var(--border-color); margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <?= $edit ? 'Simpan Perubahan' : 'Tambah Siswa' ?>
                    </button>
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php layoutEnd(); ?>
