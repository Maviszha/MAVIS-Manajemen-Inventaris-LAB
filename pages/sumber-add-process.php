<?php
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: sumber-add.php");
    exit;
}

$nama_sumber = trim($_POST['nama_sumber'] ?? '');

if ($nama_sumber === '') {
    header("Location: sumber-add.php?error=empty");
    exit;
}

$sql  = "INSERT INTO sumber_pengadaan (nama_sumber) VALUES (?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("s", $nama_sumber);

if ($stmt->execute()) {
    $stmt->close();

    $from = $_POST['from'] ?? '';
    if ($from === 'alat-add') {
        header("Location: alat-add.php?open=sumber&sumber_added=1");
    } else {
        header("Location: sumber-list.php?success=1");
    }
    exit;
}

