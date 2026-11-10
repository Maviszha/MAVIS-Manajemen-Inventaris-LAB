<?php
// pages/import-bahan-process.php
// Proses import CSV bahan_laboratorium (STRICT, prepared, auto-detect master tables)

include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: import-bahan.php");
  exit;
}

function fail(string $msg) {
  header("Location: import-bahan.php?fail=" . urlencode($msg));
  exit;
}

function must(string $val, string $field, int $line) {
  if (trim($val) === '') throw new Exception("Baris {$line}: kolom {$field} wajib diisi.");
}

function parseDate(string $val, string $field, int $line): string {
  must($val, $field, $line);
  $v = trim($val);

  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;

  if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $v, $m)) {
    return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
  }

  throw new Exception("Baris {$line}: {$field} format tanggal tidak valid (YYYY-MM-DD).");
}

function intStrict(string $val, string $field, int $line): int {
  must($val, $field, $line);
  $v = trim($val);
  if (!preg_match('/^\d+$/', $v)) throw new Exception("Baris {$line}: {$field} harus angka.");
  return (int)$v;
}

function floatStrict(string $val, string $field, int $line): float {
  must($val, $field, $line);
  $v = str_replace(',', '.', trim($val));
  if (!is_numeric($v)) throw new Exception("Baris {$line}: {$field} harus angka.");
  return (float)$v;
}

function table_exists(mysqli $conn, string $table): bool {
  $t = $conn->real_escape_string($table);
  $res = $conn->query("SHOW TABLES LIKE '{$t}'");
  return $res && $res->num_rows > 0;
}

function pick_table(mysqli $conn, array $candidates, string $label): string {
  foreach ($candidates as $t) {
    if (table_exists($conn, $t)) return $t;
  }
  throw new Exception("Tabel master untuk {$label} tidak ditemukan. Kandidat dicek: " . implode(", ", $candidates));
}

function pick_name_col(mysqli $conn, string $table, array $candidates, string $label): string {
  $cols = [];
  $res = $conn->query("SHOW COLUMNS FROM `$table`");
  if ($res) {
    while ($r = $res->fetch_assoc()) $cols[] = $r['Field'];
  }
  foreach ($candidates as $c) {
    if (in_array($c, $cols, true)) return $c;
  }
  throw new Exception("Kolom nama untuk master {$label} tidak ditemukan pada tabel {$table}. Kolom ada: " . implode(", ", $cols));
}

function getOrCreate(mysqli $conn, string $table, string $col, string $val): int {
  $val = trim($val);
  if ($val === '') return 0;

  $stmt = $conn->prepare("SELECT id FROM `$table` WHERE `$col`=? LIMIT 1");
  if (!$stmt) throw new Exception("DB error: prepare failed on {$table}");
  $stmt->bind_param("s", $val);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($row && isset($row['id'])) return (int)$row['id'];

  $stmt = $conn->prepare("INSERT INTO `$table` (`$col`) VALUES (?)");
  if (!$stmt) throw new Exception("DB error: prepare insert failed on {$table}");
  $stmt->bind_param("s", $val);
  $stmt->execute();
  $id = (int)$conn->insert_id;
  $stmt->close();
  return $id;
}

/* =========================
   Upload + header
========================= */
if (!isset($_FILES['csv']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
  fail("File CSV belum dipilih.");
}
$hasHeader = isset($_POST['has_header']) && $_POST['has_header'] === '1';
if (!$hasHeader) fail("CSV wajib menggunakan header.");

$fh = fopen($_FILES['csv']['tmp_name'], 'r');
if (!$fh) fail("Gagal membaca file CSV.");

$header = fgetcsv($fh);
if (!$header) fail("CSV kosong / header tidak terbaca.");

// FIX BOM + normalisasi header
$map = [];
foreach ($header as $i => $h) {
  $h = (string)$h;
  $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
  $h = str_replace("\xC2\xA0", " ", $h);
  $h = strtolower(trim($h));
  $h = preg_replace('/\s+/', '_', $h);
  if ($h !== '') $map[$h] = $i;
}

$required = [
  'nama_bahan','rumus_kimia','spesifikasi','lokasi','volume','satuan',
  'tahun_pengadaan','no_inventaris','sumber_pengadaan',
  'tanggal_kadaluarsa',
  'jumlah_total','jumlah_baik','jumlah_rusak_berat','jumlah_rusak_ringan','jumlah_hilang','keterangan'
];

foreach ($required as $r) {
  if (!isset($map[$r])) {
    fail("Kolom wajib tidak ditemukan: {$r}. Header terbaca: " . implode(", ", array_keys($map)));
  }
}

$get = function(array $row, string $k) use ($map): string {
  $idx = (int)$map[$k];
  return isset($row[$idx]) ? trim((string)$row[$idx]) : '';
};

/* =========================
   DETECT MASTER TABLES
========================= */
try {
  // lokasi fix: lokasi_alat
  $tblLokasi = pick_table($conn, ['lokasi_alat'], 'lokasi');
  $colLokasi = pick_name_col($conn, $tblLokasi, ['nama_lokasi','nama'], 'lokasi');

  // spesifikasi
  $tblSpes = pick_table($conn, ['spesifikasi_bahan','spesifikasi_alat','spesifikasi','spesifikasi_lab'], 'spesifikasi');
  $colSpes = pick_name_col($conn, $tblSpes, ['nama_spesifikasi','nama','spesifikasi'], 'spesifikasi');

  // satuan
  $tblSatuan = pick_table($conn, ['satuan_bahan','satuan_alat','satuan_lab','satuan_laboratorium','satuan'], 'satuan');
  $colSatuan = pick_name_col($conn, $tblSatuan, ['nama_satuan','nama','satuan'], 'satuan');

  // sumber pengadaan
  $tblSumber = pick_table($conn, ['sumber_pengadaan','sumber_dana','sumber_pengadaan_alat','sumber_pengadaan_bahan'], 'sumber_pengadaan');
  $colSumber = pick_name_col($conn, $tblSumber, ['nama_sumber','nama_sumber_dana','nama','sumber'], 'sumber_pengadaan');

} catch (Throwable $e) {
  fclose($fh);
  fail($e->getMessage());
}

/* =========================
   SQL UPSERT (sesuai struktur tabel kamu)
   - tahun_pengadaan: DATE
   - tanggal_kadaluarsa: DATE
========================= */
$sql = "
INSERT INTO bahan_laboratorium
(nama_bahan, rumus_kimia, spesifikasi_id, volume, satuan_id, lokasi_id,
 tahun_pengadaan, no_inventaris, sumber_pengadaan_id, tanggal_kadaluarsa,
 jumlah_total, jumlah_baik, jumlah_rusak_berat, jumlah_rusak_ringan, jumlah_hilang,
 keterangan, created_at)
VALUES
(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())
ON DUPLICATE KEY UPDATE
  nama_bahan=VALUES(nama_bahan),
  rumus_kimia=VALUES(rumus_kimia),
  spesifikasi_id=VALUES(spesifikasi_id),
  volume=VALUES(volume),
  satuan_id=VALUES(satuan_id),
  lokasi_id=VALUES(lokasi_id),
  tahun_pengadaan=VALUES(tahun_pengadaan),
  sumber_pengadaan_id=VALUES(sumber_pengadaan_id),
  tanggal_kadaluarsa=VALUES(tanggal_kadaluarsa),
  jumlah_total=VALUES(jumlah_total),
  jumlah_baik=VALUES(jumlah_baik),
  jumlah_rusak_berat=VALUES(jumlah_rusak_berat),
  jumlah_rusak_ringan=VALUES(jumlah_rusak_ringan),
  jumlah_hilang=VALUES(jumlah_hilang),
  keterangan=VALUES(keterangan)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  fclose($fh);
  fail("Prepare SQL gagal: " . $conn->error);
}

$conn->begin_transaction();

$line = 1;
$inserted = 0;
$updated  = 0;

try {
  while (($row = fgetcsv($fh)) !== false) {
    $line++;

    if (count($row) === 1 && trim((string)$row[0]) === '') continue;

    $nama   = $get($row,'nama_bahan');    must($nama,'nama_bahan',$line);
    $rumus  = $get($row,'rumus_kimia');   must($rumus,'rumus_kimia',$line);
    $noinv  = $get($row,'no_inventaris'); must($noinv,'no_inventaris',$line);
    $ket    = $get($row,'keterangan');    must($ket,'keterangan',$line);

    $spesStr = $get($row,'spesifikasi');      must($spesStr,'spesifikasi',$line);
    $lokStr  = $get($row,'lokasi');           must($lokStr,'lokasi',$line);
    $satStr  = $get($row,'satuan');           must($satStr,'satuan',$line);
    $srcStr  = $get($row,'sumber_pengadaan'); must($srcStr,'sumber_pengadaan',$line);

    $tgl_peng = parseDate($get($row,'tahun_pengadaan'), 'tahun_pengadaan', $line);
    if ((int)substr($tgl_peng, 0, 4) < 2000) {
      throw new Exception("Baris {$line}: tahun_pengadaan minimal tahun 2000.");
    }

    $tgl_kad = parseDate($get($row,'tanggal_kadaluarsa'), 'tanggal_kadaluarsa', $line);

    // volume di DB TEXT, tapi CSV wajib numeric -> simpan sebagai string numeric
    $volume = (string)floatStrict($get($row,'volume'), 'volume', $line);

    $total  = intStrict($get($row,'jumlah_total'), 'jumlah_total', $line);
    $baik   = intStrict($get($row,'jumlah_baik'), 'jumlah_baik', $line);
    $rb     = intStrict($get($row,'jumlah_rusak_berat'), 'jumlah_rusak_berat', $line);
    $rr     = intStrict($get($row,'jumlah_rusak_ringan'), 'jumlah_rusak_ringan', $line);
    $hilang = intStrict($get($row,'jumlah_hilang'), 'jumlah_hilang', $line);

    if (($baik + $rb + $rr + $hilang) !== $total) {
      throw new Exception("Baris {$line}: jumlah_total tidak sesuai (baik+RB+RR+H).");
    }

    // master create
    $spesId = getOrCreate($conn, $tblSpes,   $colSpes,   $spesStr);
    $lokId  = getOrCreate($conn, $tblLokasi, $colLokasi, $lokStr);
    $satId  = getOrCreate($conn, $tblSatuan, $colSatuan, $satStr);
    $srcId  = getOrCreate($conn, $tblSumber, $colSumber, $srcStr);

    // 16 placeholder => 16 variabel
    // nama(s), rumus(s), spesId(i), volume(s), satId(i), lokId(i),
    // tgl_peng(s), noinv(s), srcId(i), tgl_kad(s),
    // total(i), baik(i), rb(i), rr(i), hilang(i), ket(s)
    $types = "ssisiissisiiiiis";

    $stmt->bind_param(
      $types,
      $nama, $rumus, $spesId, $volume, $satId, $lokId,
      $tgl_peng, $noinv, $srcId, $tgl_kad,
      $total, $baik, $rb, $rr, $hilang,
      $ket
    );

    $stmt->execute();
    $aff = (int)$stmt->affected_rows;
    if ($aff === 1) $inserted++;
    else $updated++;
  }

  fclose($fh);
  $stmt->close();
  $conn->commit();

} catch (Throwable $e) {
  fclose($fh);
  $stmt->close();
  $conn->rollback();
  fail($e->getMessage());
}

logActivity(
  'bahan_laboratorium',
  'import',
  'Import bahan laboratorium (CSV)',
  $_SESSION['user']['username'] ?? 'admin',
  null,
  json_encode([
    'file' => $_FILES['csv']['name'] ?? null,
    'inserted' => $inserted,
    'updated' => $updated
  ])
);

header("Location: import-bahan.php?success=1");
exit;
