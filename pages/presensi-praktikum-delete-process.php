<?php
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: presensi-praktikum-list.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header("Location: presensi-praktikum-list.php?msg=notfound");
    exit;
}

// Ambil data sebelum hapus untuk log
$stmt = $conn->prepare("SELECT id, tanggal, sesi, kelas_id, created_by, created_at FROM presensi_praktikum WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$before = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$before) {
    header("Location: presensi-praktikum-list.php?msg=notfound");
    exit;
}

// Hapus header (detail akan terhapus via ON DELETE CASCADE)
$del = $conn->prepare("DELETE FROM presensi_praktikum WHERE id = ?");
$del->bind_param("i", $id);
$del->execute();
$del->close();

$username = $_SESSION['username'] ?? 'unknown';
logActivity(
    'presensi_praktikum',
    'delete',
    'Hapus sesi presensi praktikum',
    $username,
    json_encode($before),
    null
);

header("Location: presensi-praktikum-list.php?msg=deleted");
exit;
