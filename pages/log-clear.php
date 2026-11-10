<?php
session_start();
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

$role = $_SESSION['role'] ?? '';

if ($role !== 'admin') {
    die("Akses ditolak.");
}

// TRUNCATE log
$conn->query("TRUNCATE TABLE system_log");

// Catat bahwa log dihapus
logActivity('system_log', 'clear', 'Administrator menghapus semua log.');

header("Location: log-system.php?cleared=1");
exit;
