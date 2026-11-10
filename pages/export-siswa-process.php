<?php
// pages/export-siswa-process.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';

// Guard role
$role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? null);
if (!in_array($role, ['admin','guru'], true)) {
  http_response_code(403);
  exit('Akses ditolak.');
}

if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  exit('Koneksi database tidak tersedia.');
}

$format = strtolower(trim($_POST['format'] ?? 'csv'));
if ($format !== 'csv') {
  $_SESSION['flash'] = ['type'=>'danger','message'=>'Format export tidak didukung.'];
  header("Location: data-siswa-exim.php");
  exit;
}

$kelas_id = (isset($_POST['kelas_id']) && $_POST['kelas_id'] !== '') ? (int)$_POST['kelas_id'] : null;
$keyword  = trim((string)($_POST['keyword'] ?? ''));
$includeHeader = !empty($_POST['include_header']) ? 1 : 0;

// Query dinamis
$where = [];
$params = [];
$types  = "";

$sql = "SELECT s.nis, s.nama, k.nama AS kelas
        FROM siswa s
        LEFT JOIN kelas k ON k.id = s.kelas_id";

if ($kelas_id !== null) {
  $where[] = "s.kelas_id = ?";
  $types .= "i";
  $params[] = $kelas_id;
}

if ($keyword !== '') {
  $where[] = "(s.nis LIKE ? OR s.nama LIKE ?)";
  $kw = "%{$keyword}%";
  $types .= "ss";
  $params[] = $kw;
  $params[] = $kw;
}

if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY k.nama ASC, s.nama ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  $_SESSION['flash'] = ['type'=>'danger','message'=>'Prepare gagal: '.$conn->error];
  header("Location: data-siswa-exim.php");
  exit;
}

if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="data_siswa_' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');
if ($includeHeader) fputcsv($out, ['nis','nama','kelas']);

if ($res) {
  while ($r = $res->fetch_assoc()) {
    fputcsv($out, [$r['nis'] ?? '', $r['nama'] ?? '', $r['kelas'] ?? '']);
  }
}

fclose($out);
$stmt->close();
exit;
