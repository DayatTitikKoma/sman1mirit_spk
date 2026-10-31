<?php
// ============================================================
// modules/siswa/edit.php — Redirect ke create.php mode edit
// ============================================================
if (isset($_GET['id'])) {
    header('Location: create.php?id=' . (int) $_GET['id']);
    exit;
}
header('Location: index.php');
exit;
