<?php
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";
requireAdmin();
// Hanya boleh diakses lewat POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: lokasi-list.php");
    exit;
}

// Validasi ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header("Location: lokasi-list.php?updated=0");
    exit;
}

$id = (int)$_POST['id'];
$nama_lokasi = trim($_POST['nama_lokasi'] ?? '');

if ($nama_lokasi === '') {
    header("Location: lokasi-edit.php?id={$id}&error=empty");
    exit;
}

// UPDATE pakai prepared statement
$sql  = "UPDATE lokasi_alat SET nama_lokasi = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("si", $nama_lokasi, $id);

if ($stmt->execute()) {
    $stmt->close();

    $from = $_POST['from'] ?? '';
    if ($from === 'alat-add') {
        header("Location: alat-add.php?open=lokasi&lokasi_updated=1");
    } else {
        header("Location: lokasi-list.php?updated=1");
    }
    exit;
    } else {
}

