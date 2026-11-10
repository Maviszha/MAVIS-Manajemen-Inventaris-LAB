<?php
// pages/export-bahan-process.php
// Export CSV bahan_laboratorium (rapi, anti-HTML error, auto-detect master tables)

if (session_status() === PHP_SESSION_NONE) session_start();

include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
requireAdmin();

// Jangan biarkan error HTML ikut keluar ke CSV
ini_set('display_errors', '0');
error_reporting(0);

function table_exists(mysqli $conn, string $table): bool {
  $t = $conn->real_escape_string($table);
  $res = $conn->query("SHOW TABLES LIKE '{$t}'");
  return $res && $res->num_rows > 0;
}

function pick_table(mysqli $conn, array $candidates): ?string {
  foreach ($candidates as $t) {
    if (table_exists($conn, $t)) return $t;
  }
  return null;
}

function pick_name_col(mysqli $conn, string $table, array $candidates): ?string {
  $cols = [];
  $res = $conn->query("SHOW COLUMNS FROM `$table`");
  if ($res) {
    while ($r = $res->fetch_assoc()) $cols[] = $r['Field'];
  }
  foreach ($candidates as $c) {
    if (in_array($c, $cols, true)) return $c;
  }
  return null;
}

function safe_val($v): string {
  if ($v === null) return '';
  return trim((string)$v);
}

try {
  // FIX: lokasi kamu sudah pakai lokasi_alat
  $tblLokasi = 'lokasi_alat';
  $joinLokasi = '';
  $lokCol = 'NULL';

  if (table_exists($conn, $tblLokasi)) {
    $colLok = pick_name_col($conn, $tblLokasi, ['nama_lokasi','nama']);
    if ($colLok) {
      $joinLokasi = "LEFT JOIN `$tblLokasi` la ON la.id = b.lokasi_id";
      $lokCol = "la.`$colLok`";
    }
  }

  // spesifikasi
  $tblSpes = pick_table($conn, ['spesifikasi_bahan','spesifikasi_alat','spesifikasi','spesifikasi_lab']);
  $joinSpes = '';
  $spesCol = 'NULL';
  if ($tblSpes) {
    $colSp = pick_name_col($conn, $tblSpes, ['nama_spesifikasi','nama','spesifikasi']);
    if ($colSp) {
      $joinSpes = "LEFT JOIN `$tblSpes` sb ON sb.id = b.spesifikasi_id";
      $spesCol = "sb.`$colSp`";
    }
  }

  // satuan (ini sumber error kamu sebelumnya)
  $tblSatuan = pick_table($conn, ['satuan_bahan','satuan_alat','satuan_lab','satuan_laboratorium','satuan']);
  $joinSatuan = '';
  $satCol = 'NULL';
  if ($tblSatuan) {
    $colSat = pick_name_col($conn, $tblSatuan, ['nama_satuan','nama','satuan']);
    if ($colSat) {
      $joinSatuan = "LEFT JOIN `$tblSatuan` st ON st.id = b.satuan_id";
      $satCol = "st.`$colSat`";
    }
  }

  // sumber pengadaan
  $tblSumber = pick_table($conn, ['sumber_pengadaan','sumber_dana','sumber_pengadaan_alat','sumber_pengadaan_bahan']);
  $joinSumber = '';
  $srcCol = 'NULL';
  if ($tblSumber) {
    $colSrc = pick_name_col($conn, $tblSumber, ['nama_sumber','nama_sumber_dana','nama','sumber']);
    if ($colSrc) {
      $joinSumber = "LEFT JOIN `$tblSumber` sp ON sp.id = b.sumber_pengadaan_id";
      $srcCol = "sp.`$colSrc`";
    }
  }

  // Query utama (sesuai struktur tabel bahan_laboratorium kamu)
  $sql = "
    SELECT
      b.nama_bahan,
      b.rumus_kimia,
      $spesCol   AS spesifikasi,
      $lokCol    AS lokasi,
      b.volume,
      $satCol    AS satuan,
      b.tahun_pengadaan,
      b.no_inventaris,
      $srcCol    AS sumber_pengadaan,
      b.tanggal_kadaluarsa,
      b.jumlah_total,
      b.jumlah_baik,
      b.jumlah_rusak_berat,
      b.jumlah_rusak_ringan,
      b.jumlah_hilang,
      b.keterangan
    FROM bahan_laboratorium b
    $joinSpes
    $joinLokasi
    $joinSatuan
    $joinSumber
    ORDER BY b.id ASC
  ";

  $res = $conn->query($sql);
  if (!$res) {
    throw new Exception("Query export gagal: " . $conn->error);
  }

  // Output CSV (rapi)
  $filename = "export_bahan_" . date('Ymd_His') . ".csv";
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  header('Pragma: no-cache');
  header('Expires: 0');

  $out = fopen('php://output', 'w');

  // BOM agar LibreOffice/Excel tidak berantakan karakter
  fwrite($out, "\xEF\xBB\xBF");

  // Header sesuai kebutuhan kamu
  fputcsv($out, [
    'Nama','Rumus Kimia','Spesifikasi','Lokasi','Vol','Sat','Pengadaan',
    'No Inventaris','Sumber Dana','Kadaluarsa',
    'Total','Baik','RB','RR','H','Keterangan'
  ]);

  while ($r = $res->fetch_assoc()) {
    fputcsv($out, [
      safe_val($r['nama_bahan']),
      safe_val($r['rumus_kimia']),
      safe_val($r['spesifikasi']),
      safe_val($r['lokasi']),
      safe_val($r['volume']),
      safe_val($r['satuan']),
      safe_val($r['tahun_pengadaan']),
      safe_val($r['no_inventaris']),
      safe_val($r['sumber_pengadaan']),
      safe_val($r['tanggal_kadaluarsa']),
      safe_val($r['jumlah_total']),
      safe_val($r['jumlah_baik']),
      safe_val($r['jumlah_rusak_berat']),
      safe_val($r['jumlah_rusak_ringan']),
      safe_val($r['jumlah_hilang']),
      safe_val($r['keterangan']),
    ]);
  }

  fclose($out);
  exit;

} catch (Throwable $e) {
  // Kalau gagal, jangan output HTML juga (tetap aman)
  header("Content-Type: text/plain; charset=UTF-8", true, 500);
  echo "Export gagal: " . $e->getMessage();
  exit;
}
