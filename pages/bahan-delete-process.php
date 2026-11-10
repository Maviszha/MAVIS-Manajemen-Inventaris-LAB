<?php
ob_start();
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: bahan-list.php");
    exit;
}

$id = $_POST['id'] ?? '';
if ($id === '' || !ctype_digit($id)) {
    header("Location: bahan-list.php?msg=invalid");
    exit;
}
$id = (int)$id;

// Ambil data before untuk log
$get = $conn->prepare("SELECT * FROM bahan_laboratorium WHERE id = ? LIMIT 1");
$get->bind_param("i", $id);
$get->execute();
$before = $get->get_result()->fetch_assoc();
$get->close();

if (!$before) {
    header("Location: bahan-list.php?msg=notfound");
    exit;
}

// Hapus
$del = $conn->prepare("DELETE FROM bahan_laboratorium WHERE id = ?");
$del->bind_param("i", $id);
$del->execute();
$del->close();

// Log
$username = $_SESSION['username'] ?? 'unknown';
logActivity(
    'bahan',
    'delete',
    'Hapus bahan laboratorium',
    $username,
    json_encode($before),
    null
);

header("Location: bahan-list.php?msg=deleted");
exit;
