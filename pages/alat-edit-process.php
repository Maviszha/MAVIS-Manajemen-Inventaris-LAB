<?php
// pages/alat-edit-process.php
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: alat-list.php");
    exit;
}

function fail(int $id, string $msg): void {
    header("Location: alat-edit.php?id={$id}&fail=" . urlencode($msg));
    exit;
}

// Normalisasi tanggal agar aman (support YYYY-MM-DD / YYYY / MM/DD/YYYY)
function normalizeDateFlexible(?string $val): ?string {
    $val = trim((string)$val);
    if ($val === '') return null;

    if (preg_match('/^\d{4}$/', $val)) return $val . '-01-01';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) return $val;

    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $val, $m)) {
        $mm = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $dd = str_pad($m[2], 2, '0', STR_PAD_LEFT);
        $yy = $m[3];
        return "{$yy}-{$mm}-{$dd}";
    }
    return null;
}

// ID wajib & numerik
$id = $_POST['id'] ?? '';
if ($id === '' || !ctype_digit((string)$id)) {
    header("Location: alat-list.php?msg=invalid");
    exit;
}
$id = (int)$id;

// ===== AMBIL DATA LAMA UNTUK LOG =====
$oldStmt = $conn->prepare("SELECT * FROM alat_laboratorium WHERE id = ? LIMIT 1");
$oldStmt->bind_param("i", $id);
$oldStmt->execute();
$oldRow = $oldStmt->get_result()->fetch_assoc();
$oldStmt->close();

if (!$oldRow) {
    header("Location: alat-list.php?msg=notfound");
    exit;
}

// ===== Ambil input =====
$nama_alat     = trim($_POST['nama_alat'] ?? '');
$merk_alat     = trim($_POST['merk_alat'] ?? '');
$spesifikasi   = trim($_POST['spesifikasi'] ?? '');
$no_inventaris = trim($_POST['no_inventaris'] ?? '');

$lokasi_id           = isset($_POST['lokasi_id']) ? (int)$_POST['lokasi_id'] : 0;
$tahun_pengadaan_db  = normalizeDateFlexible($_POST['tahun_pengadaan'] ?? '');
$sumber_pengadaan_id = isset($_POST['sumber_pengadaan_id']) ? (int)$_POST['sumber_pengadaan_id'] : 0;

$jumlah_baik         = max(0, (int)($_POST['jumlah_baik'] ?? 0));
$jumlah_rusak_berat  = max(0, (int)($_POST['jumlah_rusak_berat'] ?? 0));
$jumlah_rusak_ringan = max(0, (int)($_POST['jumlah_rusak_ringan'] ?? 0));
$jumlah_hilang       = max(0, (int)($_POST['jumlah_hilang'] ?? 0));

// ✅ TOTAL OTOMATIS (abaikan input jumlah_total)
$jumlah_total = $jumlah_baik + $jumlah_rusak_berat + $jumlah_rusak_ringan + $jumlah_hilang;

$keterangan = trim($_POST['keterangan'] ?? '');

// ===== Validasi =====
if ($nama_alat === '') fail($id, 'Nama alat wajib diisi.');
if ($lokasi_id <= 0) fail($id, 'Lokasi wajib dipilih.');
if ($tahun_pengadaan_db === null) fail($id, 'Tanggal/Tahun pengadaan wajib & harus valid.');
if ($sumber_pengadaan_id <= 0) fail($id, 'Sumber pengadaan wajib dipilih.');

// No inventaris opsional
if ($no_inventaris === '') $no_inventaris = null;

// (opsional) cek duplikat no_inventaris jika diisi
if ($no_inventaris !== null) {
    $cek = $conn->prepare("SELECT id FROM alat_laboratorium WHERE no_inventaris = ? AND id <> ? LIMIT 1");
    $cek->bind_param("si", $no_inventaris, $id);
    $cek->execute();
    $dup = $cek->get_result()->fetch_assoc();
    $cek->close();
    if ($dup) {
        fail($id, 'No inventaris sudah dipakai alat lain.');
    }
}

// ===== UPDATE =====
$sql = "
    UPDATE alat_laboratorium SET
        nama_alat           = ?,
        merk_alat           = ?,
        spesifikasi         = ?,
        lokasi_id           = ?,
        tahun_pengadaan     = ?,
        sumber_pengadaan_id = ?,
        no_inventaris       = ?,
        jumlah_total        = ?,
        jumlah_baik         = ?,
        jumlah_rusak_berat  = ?,
        jumlah_rusak_ringan = ?,
        jumlah_hilang       = ?,
        keterangan          = ?
    WHERE id = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    fail($id, "Prepare gagal: " . $conn->error);
}

// 14 param
$stmt->bind_param(
    "sssisisiiiiisi",
    $nama_alat,
    $merk_alat,
    $spesifikasi,
    $lokasi_id,
    $tahun_pengadaan_db,
    $sumber_pengadaan_id,
    $no_inventaris,
    $jumlah_total,
    $jumlah_baik,
    $jumlah_rusak_berat,
    $jumlah_rusak_ringan,
    $jumlah_hilang,
    $keterangan,
    $id
);

$stmt->execute();
$stmt->close();

// ===== LOG =====
$username = $_SESSION['username'] ?? 'unknown';
$after = [
    'id' => $id,
    'nama_alat' => $nama_alat,
    'merk_alat' => $merk_alat,
    'spesifikasi' => $spesifikasi,
    'lokasi_id' => $lokasi_id,
    'tahun_pengadaan' => $tahun_pengadaan_db,
    'sumber_pengadaan_id' => $sumber_pengadaan_id,
    'no_inventaris' => $no_inventaris,
    'jumlah_total' => $jumlah_total,
    'jumlah_baik' => $jumlah_baik,
    'jumlah_rusak_berat' => $jumlah_rusak_berat,
    'jumlah_rusak_ringan' => $jumlah_rusak_ringan,
    'jumlah_hilang' => $jumlah_hilang,
    'keterangan' => $keterangan
];

logActivity(
    'alat',
    'update',
    'Update alat laboratorium',
    $username,
    json_encode($oldRow),
    json_encode($after)
);

header("Location: alat-list.php?update=1");
exit;
