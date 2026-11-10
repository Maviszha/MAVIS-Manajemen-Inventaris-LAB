<?php
// pages/unggah-mandiri-download.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';

// admin only
$role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? null);
if (strtolower((string)$role) !== 'admin') {
  http_response_code(403);
  exit('Akses ditolak.');
}

if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  exit('Koneksi database tidak tersedia.');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = strtolower((string)($_GET['type'] ?? ''));

if ($id <= 0 || !in_array($type, ['poster', 'pdf'], true)) {
  http_response_code(400);
  exit('Parameter tidak valid.');
}

$stmt = $conn->prepare("SELECT poster_path, fulltext_path FROM unggah_mandiri WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
  http_response_code(404);
  exit('Data tidak ditemukan.');
}

$pathRel = ($type === 'poster') ? ($row['poster_path'] ?? '') : ($row['fulltext_path'] ?? '');
$pathRel = (string)$pathRel;

// sanitasi minimal: wajib ada prefix uploads/mandiri/
if ($pathRel === '' || strpos($pathRel, 'uploads/mandiri/') !== 0) {
  http_response_code(400);
  exit('Path file tidak valid.');
}

$baseAbs = realpath(__DIR__ . '/..'); // root mavis
if (!$baseAbs) {
  http_response_code(500);
  exit('Base path tidak valid.');
}

$abs = realpath($baseAbs . '/' . $pathRel);
if (!$abs || strpos($abs, $baseAbs) !== 0 || !is_file($abs)) {
  http_response_code(404);
  exit('File tidak ditemukan.');
}

// Tentukan header mime
$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
$mime = 'application/octet-stream';
if ($ext === 'pdf') $mime = 'application/pdf';
if (in_array($ext, ['jpg','jpeg'], true)) $mime = 'image/jpeg';
if ($ext === 'png') $mime = 'image/png';

$filename = basename($abs);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($abs));
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');

readfile($abs);
exit;
