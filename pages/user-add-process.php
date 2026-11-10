<?php
// /mavis/pages/user-add-process.php
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";
require_once dirname(__DIR__) . "/config/log.php";

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: user-list.php");
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$password  = $_POST['password'] ?? '';
$role      = strtolower(trim($_POST['role'] ?? 'guru'));

$allowedRoles = ['admin','guru','siswa']; // laboran lama masih terbaca di list
if (!in_array($role, $allowedRoles, true)) {
    $role = 'guru';
}

if ($full_name === '' || $username === '' || $password === '') {
    header("Location: user-add.php?error=1");
    exit;
}

// Cek username unik
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    echo "<script>alert('Username sudah digunakan'); window.location='user-add.php';</script>";
    exit;
}
$stmt->close();

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (full_name, username, email, phone, password_hash, role, created_at)
                        VALUES (?,?,?,?,?,?,NOW())");
$stmt->bind_param("ssssss", $full_name, $username, $email, $phone, $hash, $role);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();

    logActivity(
        'user',
        'create',
        "Tambah user baru: {$username} ({$role})",
        null,
        ['id' => $newId, 'username' => $username, 'role' => $role]
    );

    header("Location: user-list.php?success=1");
    exit;
} else {
    $err = $stmt->error;
    $stmt->close();
    echo "Gagal menyimpan user: " . htmlspecialchars($err);
}
