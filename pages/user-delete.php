<?php
// /mavis/pages/user-delete.php
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";
require_once dirname(__DIR__) . "/config/log.php";

requireAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: user-list.php"); exit;
}

$id = (int)$_GET['id'];

// tidak boleh hapus dirinya sendiri
if ($id === (int)($_SESSION['user_id'] ?? 0)) {
    echo "<script>alert('Anda tidak dapat menghapus akun Anda sendiri.'); window.location='user-list.php';</script>";
    exit;
}

// data lama
$oldRes = $conn->query("SELECT * FROM users WHERE id = {$id} LIMIT 1");
$oldRow = $oldRes ? $oldRes->fetch_assoc() : null;

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();

    logActivity(
        'user',
        'delete',
        "Hapus user ID {$id}" . ($oldRow ? " ({$oldRow['username']})" : ''),
        $oldRow,
        null
    );

    header("Location: user-list.php?deleted=1");
    exit;
} else {
    $err = $stmt->error;
    $stmt->close();
    echo "Gagal menghapus user: " . htmlspecialchars($err);
}
