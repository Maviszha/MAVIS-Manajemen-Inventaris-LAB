<?php
// Hapus sumber pengadaan
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";
requireAdmin();
// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Kalau dipanggil dari alat-add, balik ke sana
    $from = $_GET['from'] ?? '';
    if ($from === 'alat-add') {
        header("Location: alat-add.php?open=sumber&error=invalid");
    } else {
        header("Location: sumber-list.php?error=invalid");
    }
    exit;
}

$id   = (int)$_GET['id'];
$from = $_GET['from'] ?? '';

// 1. CEK apakah sumber ini dipakai di alat_laboratorium
$cek = $conn->prepare("SELECT COUNT(*) AS c FROM alat_laboratorium WHERE sumber_pengadaan_id = ?");
if (!$cek) {
    // Kalau prepare gagal, minimal jangan blank
    $msg = "Prepare cek gagal: " . htmlspecialchars($conn->error);
    die($msg);
}
$cek->bind_param("i", $id);
$cek->execute();
$res = $cek->get_result();
$row = $res->fetch_assoc();
$cek->close();

$dipakai = (int)($row['c'] ?? 0);

if ($dipakai > 0) {
    // Tidak boleh hapus kalau masih dipakai
    if ($from === 'alat-add') {
        header("Location: alat-add.php?open=sumber&sumber_used={$dipakai}");
    } else {
        header("Location: sumber-list.php?used={$dipakai}");
    }
    exit;
}

// 2. HAPUS sumber
$del = $conn->prepare("DELETE FROM sumber_pengadaan WHERE id = ?");
if (!$del) {
    $msg = "Prepare delete gagal: " . htmlspecialchars($conn->error);
    die($msg);
}
$del->bind_param("i", $id);

if ($del->execute()) {
    $del->close();

    if ($from === 'alat-add') {
        header("Location: alat-add.php?open=sumber&sumber_deleted=1");
    } else {
        header("Location: sumber-list.php?deleted=1");
    }
    exit;
} else {
    $error = htmlspecialchars($del->error);
    $del->close();
    die("Gagal menghapus sumber: {$error}");
}
