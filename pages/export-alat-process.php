<?php
// pages/export-alat-process.php
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: export-alat.php");
  exit;
}

$format = strtolower(trim($_POST['format'] ?? 'csv'));
if ($format !== 'csv') $format = 'csv';

$includeHeader = isset($_POST['include_header']) ? 1 : 0;
$start = trim($_POST['start_date'] ?? '');
$end   = trim($_POST['end_date'] ?? '');

$where = [];
$params = [];
$types = '';

$sql = "
  SELECT
    a.no_inventaris,
    a.nama_alat,
    a.merk_alat,
    a.spesifikasi,
    l.nama_lokasi,
    a.tahun_pengadaan,
    s.nama_sumber,
    a.jumlah_total,
    a.jumlah_baik,
    a.jumlah_rusak_berat,
    a.jumlah_rusak_ringan,
    a.jumlah_hilang,
    a.keterangan
  FROM alat_laboratorium a
  LEFT JOIN lokasi_alat l ON a.lokasi_id = l.id
  LEFT JOIN sumber_pengadaan s ON a.sumber_pengadaan_id = s.id
  WHERE 1=1
";

if ($start !== '') {
  $where[] = "a.tahun_pengadaan >= ?";
  $types .= "s";
  $params[] = $start;
}
if ($end !== '') {
  $where[] = "a.tahun_pengadaan <= ?";
  $types .= "s";
  $params[] = $end;
}
if ($where) $sql .= " AND " . implode(" AND ", $where);

$sql .= " ORDER BY a.nama_alat ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  die("Prepare failed: " . htmlspecialchars($conn->error));
}
if ($types !== '') $stmt->bind_param($types, ...$params);

$stmt->execute();
$res = $stmt->get_result();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="data_alat_' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');

if ($includeHeader) {
  fputcsv($out, [
    'no_inventaris','nama_alat','merk_alat','spesifikasi','lokasi',
    'tahun_pengadaan','sumber_pengadaan',
    'jumlah_total','jumlah_baik','jumlah_rusak_berat','jumlah_rusak_ringan','jumlah_hilang',
    'keterangan'
  ]);
}

if ($res) {
  while ($r = $res->fetch_assoc()) {
    fputcsv($out, [
      $r['no_inventaris'] ?? '',
      $r['nama_alat'] ?? '',
      $r['merk_alat'] ?? '',
      $r['spesifikasi'] ?? '',
      $r['nama_lokasi'] ?? '',
      $r['tahun_pengadaan'] ?? '',
      $r['nama_sumber'] ?? '',
      (int)($r['jumlah_total'] ?? 0),
      (int)($r['jumlah_baik'] ?? 0),
      (int)($r['jumlah_rusak_berat'] ?? 0),
      (int)($r['jumlah_rusak_ringan'] ?? 0),
      (int)($r['jumlah_hilang'] ?? 0),
      $r['keterangan'] ?? '',
    ]);
  }
}

fclose($out);
$stmt->close();

if (function_exists('logActivity')) {
  @logActivity('alat', 'export_csv', 'Export data alat (CSV) termasuk no_inventaris');
}
exit;
