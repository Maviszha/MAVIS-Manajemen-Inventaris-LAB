<?php
// pages/import-siswa-process.php
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

function flash($type,$message){
  $_SESSION['flash'] = ['type'=>$type,'message'=>$message];
}

function get_kelas_id_by_name(mysqli $conn, string $namaKelas): int {
  $namaKelas = trim($namaKelas);
  if ($namaKelas === '') return 0;

  $stmt = $conn->prepare("SELECT id FROM kelas WHERE nama = ? LIMIT 1");
  $stmt->bind_param("s", $namaKelas);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  if ($row) return (int)$row['id'];

  $aktif = 1;
  $stmt = $conn->prepare("INSERT INTO kelas (nama, aktif) VALUES (?, ?)");
  $stmt->bind_param("si", $namaKelas, $aktif);
  $stmt->execute();
  $newId = (int)$stmt->insert_id;
  $stmt->close();
  return $newId;
}

if (empty($_FILES['csv']['tmp_name']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
  flash('danger','File CSV tidak valid.');
  header("Location: data-siswa-exim.php");
  exit;
}

$tmp = $_FILES['csv']['tmp_name'];
$fh = fopen($tmp, 'r');
if (!$fh) {
  flash('danger','Gagal membaca file CSV.');
  header("Location: data-siswa-exim.php");
  exit;
}

$conn->begin_transaction();

$rowNum = 0;
$okCount = 0;
$skipCount = 0;

$ins = $conn->prepare("INSERT INTO siswa (nis, nama, kelas_id) VALUES (?, ?, ?)");
$upd = $conn->prepare("UPDATE siswa SET nama = ?, kelas_id = ? WHERE nis = ?");

try {
  while (($cols = fgetcsv($fh)) !== false) {
    $rowNum++;

    // header detect
    if ($rowNum === 1) {
      $h0 = strtolower(trim($cols[0] ?? ''));
      $h1 = strtolower(trim($cols[1] ?? ''));
      $h2 = strtolower(trim($cols[2] ?? ''));
      if ($h0 === 'nis' && $h1 === 'nama' && $h2 === 'kelas') continue;
    }

    $nis  = trim($cols[0] ?? '');
    $nama = trim($cols[1] ?? '');
    $kelasNama = trim($cols[2] ?? '');

    if ($nis === '' || $nama === '' || $kelasNama === '') {
      $skipCount++;
      continue;
    }

    $kelasId = get_kelas_id_by_name($conn, $kelasNama);
    if ($kelasId <= 0) { $skipCount++; continue; }

    $cek = $conn->prepare("SELECT id FROM siswa WHERE nis = ? LIMIT 1");
    $cek->bind_param("s", $nis);
    $cek->execute();
    $res = $cek->get_result();
    $exists = $res && $res->fetch_assoc();
    $cek->close();

    if ($exists) {
      $upd->bind_param("sis", $nama, $kelasId, $nis);
      $upd->execute();
      $okCount++;
    } else {
      $ins->bind_param("ssi", $nis, $nama, $kelasId);
      $ins->execute();
      $okCount++;
    }
  }

  fclose($fh);
  $ins->close();
  $upd->close();

  $conn->commit();

  flash('success', "Import selesai. Berhasil: {$okCount}, Dilewati: {$skipCount}");
  header("Location: data-siswa-exim.php");
  exit;

} catch (Throwable $e) {
  $conn->rollback();
  fclose($fh);

  flash('danger', 'Import gagal: ' . $e->getMessage());
  header("Location: data-siswa-exim.php");
  exit;
}
