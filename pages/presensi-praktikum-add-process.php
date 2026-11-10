<?php
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: presensi-praktikum-add.php");
  exit;
}

function failBack(string $msg): void {
  header("Location: presensi-praktikum-add.php?fail=" . urlencode($msg));
  exit;
}

function genToken(): string {
  return strtolower(bin2hex(random_bytes(16)));
}

$tanggal  = trim($_POST['tanggal'] ?? '');
$sesi     = trim($_POST['sesi'] ?? '');
$kelas_id = (int)($_POST['kelas_id'] ?? 0);

if ($tanggal === '') failBack("Tanggal wajib diisi");
if ($sesi === '') failBack("Sesi wajib diisi");
if ($kelas_id <= 0) failBack("Kelas wajib dipilih");

$username = $_SESSION['username'] ?? 'unknown';

$token = genToken();
$cek = $conn->prepare("SELECT id FROM presensi_praktikum WHERE public_token = ? LIMIT 1");
for ($i=0; $i<5; $i++) {
  $cek->bind_param("s", $token);
  $cek->execute();
  $dup = $cek->get_result()->fetch_assoc();
  if (!$dup) break;
  $token = genToken();
}
$cek->close();

$stmt = $conn->prepare("
  INSERT INTO presensi_praktikum (tanggal, sesi, kelas_id, public_token, created_by)
  VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("ssiss", $tanggal, $sesi, $kelas_id, $token, $username);
$stmt->execute();
$presensi_id = (int)$stmt->insert_id;
$stmt->close();

$stu = $conn->prepare("SELECT id FROM siswa WHERE kelas_id = ?");
$stu->bind_param("i", $kelas_id);
$stu->execute();
$rstu = $stu->get_result();
$stu->close();

$ins = $conn->prepare("INSERT INTO presensi_praktikum_detail (presensi_id, siswa_id) VALUES (?, ?)");
$ok=0; $failCount=0;
while ($s = $rstu->fetch_assoc()) {
  $sid = (int)$s['id'];
  $ins->bind_param("ii", $presensi_id, $sid);
  try { $ins->execute(); $ok++; }
  catch (Throwable $e) { $failCount++; }
}
$ins->close();

logActivity(
  'presensi_praktikum',
  'create',
  'Buat sesi presensi praktikum',
  $username,
  null,
  json_encode([
    'presensi_id' => $presensi_id,
    'tanggal' => $tanggal,
    'sesi' => $sesi,
    'kelas_id' => $kelas_id,
    'public_token' => $token,
    'generated' => $ok,
    'failed' => $failCount
  ])
);

header("Location: presensi-praktikum-add.php?success=1&token=" . urlencode($token));
exit;
