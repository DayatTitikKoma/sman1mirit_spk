<?php
// ============================================================
// modules/kriteria/edit.php — Edit Bobot Kriteria
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
requireRole('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/layout.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$kriteria = dbQueryOne('SELECT * FROM kriteria WHERE id = ?', [$id]);
if (!$kriteria) {
    die("Data kriteria tidak ditemukan.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode        = trim($_POST['kode'] ?? '');
    $nama        = trim($_POST['nama'] ?? '');
    $jenis       = $_POST['jenis'] ?? 'Benefit';
    $bobotPersen = filter_input(INPUT_POST, 'bobot_persen', FILTER_VALIDATE_FLOAT);
    
    if (empty($kode) || empty($nama)) {
        $error = 'Kode dan Nama kriteria tidak boleh kosong.';
    } elseif ($bobotPersen === false || $bobotPersen < 0 || $bobotPersen > 100) {
        $error = 'Nilai bobot harus berupa angka antara 0 dan 100.';
    } else {
        $bobotDesimal = $bobotPersen / 100;
        
        try {
            dbExecute('UPDATE kriteria SET kode = ?, nama = ?, jenis = ?, bobot = ? WHERE id = ?', 
                [$kode, $nama, $jenis, $bobotDesimal, $id]
            );
            $success = 'Data kriteria berhasil diperbarui.';
            // Refresh data
            $kriteria = dbQueryOne('SELECT * FROM kriteria WHERE id = ?', [$id]);
        } catch (Exception $e) {
            $error = 'Gagal menyimpan ke database: ' . $e->getMessage();
        }
    }
}

layoutStart('Edit Kriteria', 'kriteria');
?>

<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <div class="card-title">✏️ Edit Kriteria: <?= htmlspecialchars(strtoupper($kriteria['kode'])) ?></div>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger mb-16"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success mb-16"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group mb-16">
                <label class="form-label" style="display: block; margin-bottom: 8px;">Kode Kriteria</label>
                <input type="text" name="kode" class="form-control" value="<?= htmlspecialchars(strtoupper($kriteria['kode'])) ?>" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div class="form-group mb-16">
                <label class="form-label" style="display: block; margin-bottom: 8px;">Nama Kriteria</label>
                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($kriteria['nama']) ?>" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            
            <div class="form-group mb-16">
                <label class="form-label" style="display: block; margin-bottom: 8px;">Sifat / Jenis</label>
                <select name="jenis" class="form-control" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; background-color: white;">
                    <option value="Benefit" <?= $kriteria['jenis'] === 'Benefit' ? 'selected' : '' ?>>Benefit</option>
                    <option value="Cost" <?= $kriteria['jenis'] === 'Cost' ? 'selected' : '' ?>>Cost</option>
                </select>
            </div>

            <div class="form-group mb-24">
                <label class="form-label" for="bobot_persen" style="display: block; margin-bottom: 8px; font-weight: bold; color: #0f172a;">Bobot Persentase (%)</label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="number" step="0.1" min="0" max="100" id="bobot_persen" name="bobot_persen" class="form-control" value="<?= htmlspecialchars((float)$kriteria['bobot'] * 100) ?>" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px;">
                    <span style="font-size: 18px; font-weight: 600; color: #475569;">%</span>
                </div>
                <div style="margin-top: 6px; font-size: 12px; color: #64748b;">Nilai yang disimpan di database: <span id="preview_desimal"><?= $kriteria['bobot'] ?></span></div>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px; border: none; border-radius: 6px; background-color: #2563eb; color: white; cursor: pointer; font-weight: 500;">Simpan Perubahan</button>
                <a href="index.php" class="btn btn-secondary" style="padding: 10px 20px; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; color: #475569; background-color: white; font-weight: 500;">Kembali</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('bobot_persen').addEventListener('input', function(e) {
        let val = parseFloat(e.target.value);
        if (!isNaN(val)) {
            document.getElementById('preview_desimal').innerText = (val / 100).toFixed(4);
        } else {
            document.getElementById('preview_desimal').innerText = '0.0000';
        }
    });
</script>

<?php layoutEnd(); ?>
