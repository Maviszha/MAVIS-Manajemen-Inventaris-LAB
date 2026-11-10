<?php
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";
requireAdmin();
// Hanya boleh diakses melalui POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: lokasi-add.php");
    exit;
}

// Ambil input & sanitasi
$nama_lokasi = trim($_POST['nama_lokasi'] ?? '');

if ($nama_lokasi === '') {
    header("Location: lokasi-add.php?error=empty");
    exit;
}

// Prepared statement
$sql = "INSERT INTO lokasi_alat (nama_lokasi) VALUES (?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("s", $nama_lokasi);

if ($stmt->execute()) {
    $stmt->close();

    $from = $_POST['from'] ?? '';
    if ($from === 'alat-add') {
        header("Location: alat-add.php?open=lokasi&lokasi_added=1");
    } else {
        header("Location: lokasi-list.php?success=1");
    }
    exit;
    } else {
}