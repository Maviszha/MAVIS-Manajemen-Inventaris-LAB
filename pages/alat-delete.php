<?php
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";
require_once dirname(__DIR__) . "/config/log.php"; // <-- tambahkan
requireAdmin();
// Pastikan ID dikirim & valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: alat-list.php?msg=invalid");
    exit;
}

$id = (int)$_GET['id'];

// ===== AMBIL DATA LAMA UNTUK LOG =====
$oldRow = null;
$oldRes = $conn->query("SELECT * FROM alat_laboratorium WHERE id = {$id} LIMIT 1");
if ($oldRes && $oldRes->num_rows > 0) {
    $oldRow = $oldRes->fetch_assoc();
}

// Hapus data pakai prepared statement
$sql  = "DELETE FROM alat_laboratorium WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Gagal menyiapkan query: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();

    // ===== LOG ACTIVITY: DELETE ALAT =====
    logActivity(
        'alat',
        'delete',
        "Hapus data alat ID: {$id}" . ($oldRow ? " ({$oldRow['nama_alat']})" : ''),
        $oldRow,
        null
    );

    header("Location: alat-list.php?msg=deleted");
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    echo "Gagal menghapus data: " . htmlspecialchars($error);
    exit;
}
