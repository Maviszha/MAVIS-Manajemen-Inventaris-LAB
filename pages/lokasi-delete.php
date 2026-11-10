<?php
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";
requireAdmin();
// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: lokasi-list.php?error=invalid");
    exit;
}

$id = (int)$_GET['id'];

// Cek apakah lokasi digunakan oleh alat_laboratorium
$cek = $conn->prepare("SELECT COUNT(*) AS c FROM alat_laboratorium WHERE lokasi_id = ?");
$cek->bind_param("i", $id);
$cek->execute();
$result = $cek->get_result()->fetch_assoc();
$cek->close();

if ($result['c'] > 0) {
    // Tidak boleh delete jika masih digunakan
    header("Location: lokasi-list.php?used=" . (int)$result['c']);
    exit;
}

// Hapus lokasi menggunakan prepared statement
$del = $conn->prepare("DELETE FROM lokasi_alat WHERE id = ?");
$del->bind_param("i", $id);

if ($del->execute()) {
    $del->close();

    $from = $_GET['from'] ?? '';
    if ($from === 'alat-add') {
        header("Location: alat-add.php?open=lokasi&lokasi_deleted=1");
    } else {
        header("Location: lokasi-list.php?deleted=1");
    }
    exit;
    } else {
}
