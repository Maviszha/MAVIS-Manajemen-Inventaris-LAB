<?php
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: sumber-list.php");
    exit;
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header("Location: sumber-list.php?updated=0");
    exit;
}

$id = (int)$_POST['id'];
$nama_sumber = trim($_POST['nama_sumber'] ?? '');

if ($nama_sumber === '') {
    header("Location: sumber-edit.php?id={$id}&error=empty");
    exit;
}

$sql  = "UPDATE sumber_pengadaan SET nama_sumber = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("si", $nama_sumber, $id);

if ($stmt->execute()) {
    $stmt->close();

    $from = $_POST['from'] ?? '';
    if ($from === 'alat-add') {
        header("Location: alat-add.php?open=sumber&sumber_updated=1");
    } else {
        header("Location: sumber-list.php?updated=1");
    }
    exit;
}

