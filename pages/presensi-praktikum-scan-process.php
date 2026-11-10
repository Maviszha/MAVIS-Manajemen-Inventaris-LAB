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
$barcode     = trim($_POST['barcode'] ?? '');

if ($presensi_id <= 0 || $barcode === '') {
  header("Location: presensi-praktikum-list.php");
  exit;
}

// ambil header
$h = $conn->prepare("SELECT id, kelas_id FROM presensi_praktikum WHERE id=? LIMIT 1");
$h->bind_param("i", $presensi_id);
$h->execute();
$hdr = $h->get_result()->fetch_assoc();
$h->close();

if (!$hdr) {
  header("Location: presensi-praktikum-list.php");
  exit;
}

$kelas_id = (int)$hdr['kelas_id'];

// cari siswa by NIS
$s = $conn->prepare("SELECT id, nama, kelas_id FROM siswa WHERE nis = ? LIMIT 1");
$s->bind_param("s", $barcode);
$s->execute();
$stu = $s->get_result()->fetch_assoc();
$s->close();

if (!$stu) {
  header("Location: presensi-praktikum-session.php?id={$presensi_id}&msg=" . urlencode("NIS tidak ditemukan: {$barcode}"));
  exit;
}

if ((int)$stu['kelas_id'] !== $kelas_id) {
  header("Location: presensi-praktikum-session.php?id={$presensi_id}&msg=" . urlencode("Siswa bukan dari kelas sesi ini."));
  exit;
}

$siswa_id = (int)$stu['id'];
$nama     = $stu['nama'];

// update hadir_praktikum + keterangan otomatis
$u = $conn->prepare("
  UPDATE presensi_praktikum_detail
  SET hadir_praktikum = 1,
      scan_time = NOW(),
      keterangan_otomatis = CASE
        WHEN laporan = 1 THEN 'lengkap'
        ELSE 'hadir praktikum'
      END
  WHERE presensi_id = ? AND siswa_id = ?
");
$u->bind_param("ii", $presensi_id, $siswa_id);
$u->execute();
$affected = $u->affected_rows;
$u->close();

$username = $_SESSION['username'] ?? 'unknown';
logActivity(
  'presensi_praktikum',
  'scan',
  'Scan hadir praktikum',
  $username,
  null,
  json_encode(['presensi_id'=>$presensi_id,'siswa_id'=>$siswa_id,'nis'=>$barcode,'affected'=>$affected])
);

header("Location: presensi-praktikum-session.php?id={$presensi_id}&scan=" . urlencode($nama));
exit;
