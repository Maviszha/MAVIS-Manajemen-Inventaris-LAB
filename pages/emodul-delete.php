<?php
// pages/emodul-delete.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: emodul-list.php"); exit; }

// Ambil path file agar bisa dihapus dari server
$stmt = $conn->prepare("SELECT gambar_path, file_path FROM e_modul WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if ($row) {
    $base = dirname(__DIR__) . '/';
    foreach (['gambar_path', 'file_path'] as $col) {
        if (!empty($row[$col])) {
            $full = $base . $row[$col];
            if (file_exists($full)) @unlink($full);
        }
    }

    $del = $conn->prepare("DELETE FROM e_modul WHERE id = ?");
    $del->bind_param('i', $id);
    $del->execute();
}

header("Location: emodul-list.php?deleted=1");
exit;
