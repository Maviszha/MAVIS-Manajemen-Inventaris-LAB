<?php
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: presensi-praktikum-list.php");
  exit;
}

$presensi_id = (int)($_POST['presensi_id'] ?? 0);
if ($presensi_id <= 0) {
  header("Location: presensi-praktikum-list.php");
  exit;
}

$laporanArr = $_POST['laporan'] ?? [];   // siswa_id => 1
$catatanArr = $_POST['catatan'] ?? [];   // siswa_id => text

$q = $conn->prepare("SELECT siswa_id, hadir_praktikum FROM presensi_praktikum_detail WHERE presensi_id=?");
$q->bind_param("i", $presensi_id);
$q->execute();
$rs = $q->get_result();
$q->close();

$upd = $conn->prepare("
  UPDATE presensi_praktikum_detail
  SET laporan = ?, catatan = ?, keterangan_otomatis = ?
  WHERE presensi_id = ? AND siswa_id = ?
");

$ok=0;
while($r = $rs->fetch_assoc()){
  $sid   = (int)$r['siswa_id'];
  $hadir = (int)$r['hadir_praktikum'];

  $lap = isset($laporanArr[$sid]) ? 1 : 0;
  $cat = trim($catatanArr[$sid] ?? '');
  if ($cat === '') $cat = null;

  $ket = '';
  if ($hadir===1 && $lap===1) $ket = 'lengkap';
  else if ($hadir===1) $ket = 'hadir praktikum';
  else if ($lap===1) $ket = 'lap.praktikum';
  else $ket = '';

  $upd->bind_param("issii", $lap, $cat, $ket, $presensi_id, $sid);
  $upd->execute();
  $ok++;
}
$upd->close();

$username = $_SESSION['username'] ?? 'unknown';
logActivity(
  'presensi_praktikum',
  'update_laporan',
  'Update checklist laporan praktikum',
  $username,
  null,
  json_encode(['presensi_id'=>$presensi_id,'updated_rows'=>$ok])
);

header("Location: presensi-praktikum-report.php?id={$presensi_id}&update=1");
exit;
