<?php
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: presensi-reguler-list.php");
  exit;
}

$presensi_id = (int)($_POST['presensi_id'] ?? 0);
if ($presensi_id <= 0) { header("Location: presensi-reguler-list.php"); exit; }

$hadirArr   = $_POST['hadir'] ?? [];
$catatanArr = $_POST['catatan'] ?? [];

$q = $conn->prepare("SELECT siswa_id FROM presensi_reguler_detail WHERE presensi_id=?");
$q->bind_param("i", $presensi_id);
$q->execute();
$rs = $q->get_result();
$q->close();

$upd = $conn->prepare("UPDATE presensi_reguler_detail SET hadir=?, catatan=? WHERE presensi_id=? AND siswa_id=?");

$ok=0;
while($r=$rs->fetch_assoc()){
  $sid   = (int)$r['siswa_id'];
  $hadir = isset($hadirArr[$sid]) ? 1 : 0;
  $cat   = trim($catatanArr[$sid] ?? '');
  if ($cat==='') $cat = null;

  $upd->bind_param("isii", $hadir, $cat, $presensi_id, $sid);
  $upd->execute();
  $ok++;
}
$upd->close();

$username = $_SESSION['username'] ?? 'unknown';
logActivity(
  'presensi_reguler',
  'update',
  'Update presensi reguler',
  $username,
  null,
  json_encode(['presensi_id'=>$presensi_id,'updated_rows'=>$ok])
);

header("Location: presensi-reguler-session.php?id={$presensi_id}&update=1");
exit;
