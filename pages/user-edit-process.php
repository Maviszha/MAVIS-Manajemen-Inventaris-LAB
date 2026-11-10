<?php
// /mavis/pages/user-edit-process.php
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";
require_once dirname(__DIR__) . "/config/log.php";

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: user-list.php"); exit;
}
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header("Location: user-list.php"); exit;
}

$id         = (int)$_POST['id'];
$full_name  = trim($_POST['full_name'] ?? '');
$username   = trim($_POST['username'] ?? '');
$email      = trim($_POST['email'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$role       = strtolower(trim($_POST['role'] ?? 'guru'));
$newPassRaw = $_POST['new_password'] ?? '';

$allowedRoles = ['admin','guru','siswa','laboran'];
if (!in_array($role, $allowedRoles, true)) {
    $role = 'guru';
}

if ($full_name === '' || $username === '') {
    header("Location: user-edit.php?id={$id}&error=1");
    exit;
}

// ambil data lama untuk log
$oldRes = $conn->query("SELECT * FROM users WHERE id = {$id} LIMIT 1");
$oldRow = $oldRes ? $oldRes->fetch_assoc() : null;

// cek username unik (kecuali dirinya sendiri)
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
$stmt->bind_param("si", $username, $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    echo "<script>alert('Username sudah digunakan user lain'); window.location='user-edit.php?id={$id}';</script>";
    exit;
}
$stmt->close();

// Susun update
if ($newPassRaw !== '') {
    $hash = password_hash($newPassRaw, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users
                            SET full_name = ?, username = ?, email = ?, phone = ?, role = ?, password_hash = ?
                            WHERE id = ?");
    $stmt->bind_param("ssssssi", $full_name, $username, $email, $phone, $role, $hash, $id);
} else {
    $stmt = $conn->prepare("UPDATE users
                            SET full_name = ?, username = ?, email = ?, phone = ?, role = ?
                            WHERE id = ?");
    $stmt->bind_param("sssssi", $full_name, $username, $email, $phone, $role, $id);
}

if ($stmt->execute()) {
    $stmt->close();

    $newRes = $conn->query("SELECT * FROM users WHERE id = {$id} LIMIT 1");
    $newRow = $newRes ? $newRes->fetch_assoc() : null;

    logActivity(
        'user',
        'update',
        "Edit user ID {$id}: {$username}",
        $oldRow,
        $newRow
    );

    header("Location: user-list.php?updated=1");
    exit;
} else {
    $err = $stmt->error;
    $stmt->close();
    echo "Gagal mengubah user: " . htmlspecialchars($err);
}
