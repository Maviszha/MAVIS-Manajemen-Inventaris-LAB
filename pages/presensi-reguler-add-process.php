<?php
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: presensi-reguler-add.php");
  exit;
}

function fail($msg){
  header("Location: presensi-reguler-add.php?fail=" . urlencode($msg));
  exit;
}

$tanggal  = trim($_POST['tanggal'] ?? '');
$kegiatan = trim($_POST['kegiatan'] ?? '');
$kelas_id = (int)($_POST['kelas_id'] ?? 0);

if ($tanggal==='') fail("Tanggal wajib diisi");
if ($kegiatan==='') fail("Kegiatan wajib diisi");
if ($kelas_id<=0) fail("Kelas wajib dipilih");

$username = $_SESSION['username'] ?? 'unknown';

$stmt = $conn->prepare("INSERT INTO presensi_reguler (tanggal, kegiatan, kelas_id, created_by) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssis", $tanggal, $kegiatan, $kelas_id, $username);
$stmt->execute();
$presensi_id = $stmt->insert_id;
$stmt->close();

// generate detail siswa
$stu = $conn->prepare("SELECT id FROM siswa WHERE kelas_id=?");
$stu->bind_param("i", $kelas_id);
$stu->execute();
$rs = $stu->get_result();
$stu->close();

$ins = $conn->prepare("INSERT INTO presensi_reguler_detail (presensi_id, siswa_id) VALUES (?, ?)");
$ok=0;
while($r=$rs->fetch_assoc()){
  $sid=(int)$r['id'];
  $ins->bind_param("ii", $presensi_id, $sid);
  $ins->execute();
  $ok++;
}
$ins->close();

logActivity(
  'presensi_reguler',
  'create',
  'Buat presensi reguler',
  $username,
  null,
  json_encode(['presensi_id'=>$presensi_id,'tanggal'=>$tanggal,'kegiatan'=>$kegiatan,'kelas_id'=>$kelas_id,'generated'=>$ok])
);

header("Location: presensi-reguler-session.php?id=".$presensi_id."&created=1");
exit;
