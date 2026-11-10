<?php
ob_start();
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: bahan-list.php");
    exit;
}

function fail(int $id, string $msg): void {
    header("Location: bahan-edit.php?id={$id}&fail=" . urlencode($msg));
    exit;
}

// Normalisasi DATE aman (support YYYY-MM-DD / YYYY / MM/DD/YYYY)
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

$id = $_POST['id'] ?? '';
if ($id === '' || !ctype_digit($id)) {
    header("Location: bahan-list.php?msg=invalid");
    exit;
}
$id = (int)$id;

// Ambil data before (untuk log)
$get = $conn->prepare("SELECT * FROM bahan_laboratorium WHERE id = ? LIMIT 1");
$get->bind_param("i", $id);
$get->execute();
$before = $get->get_result()->fetch_assoc();
$get->close();

if (!$before) {
    header("Location: bahan-list.php?msg=notfound");
    exit;
}

// ===== Ambil input =====
$nama_bahan    = trim($_POST['nama_bahan'] ?? '');
$no_inventaris = trim($_POST['no_inventaris'] ?? '');
$rumus_kimia   = trim($_POST['rumus_kimia'] ?? '');

$spesifikasi_id = ($_POST['spesifikasi_id'] ?? '') !== '' ? (int)$_POST['spesifikasi_id'] : null;
$lokasi_id      = ($_POST['lokasi_id'] ?? '') !== '' ? (int)$_POST['lokasi_id'] : null;
$satuan_id      = ($_POST['satuan_id'] ?? '') !== '' ? (int)$_POST['satuan_id'] : null;

$volume = trim($_POST['volume'] ?? '');
if ($volume === '') $volume = null;

$tahun_pengadaan_db    = normalizeDateFlexible($_POST['tahun_pengadaan'] ?? '');
$tanggal_kadaluarsa_db = normalizeDateFlexible($_POST['tanggal_kadaluarsa'] ?? '');

$sumber_pengadaan_id = ($_POST['sumber_pengadaan_id'] ?? '') !== '' ? (int)$_POST['sumber_pengadaan_id'] : null;

// ===== Status stok =====
$jumlah_baik         = max(0, (int)($_POST['jumlah_baik'] ?? 0));
$jumlah_rusak_berat  = max(0, (int)($_POST['jumlah_rusak_berat'] ?? 0));
$jumlah_rusak_ringan = max(0, (int)($_POST['jumlah_rusak_ringan'] ?? 0));
$jumlah_hilang       = max(0, (int)($_POST['jumlah_hilang'] ?? 0));

// TOTAL OTOMATIS: dihitung dari Baik + RB + RR + Hilang (abaikan input jumlah_total)
$jumlah_total = $jumlah_baik + $jumlah_rusak_berat + $jumlah_rusak_ringan + $jumlah_hilang;

$keterangan = trim($_POST['keterangan'] ?? '');

// ===== Validasi =====
if ($nama_bahan === '') fail($id, 'Nama bahan wajib diisi');
if ($tahun_pengadaan_db === null) fail($id, 'Tahun pengadaan wajib & harus valid');
if ($satuan_id === null) fail($id, 'Satuan wajib dipilih');

// no inventaris opsional
if ($no_inventaris === '') $no_inventaris = null;

// (opsional) cek duplikat no inventaris jika diisi
if ($no_inventaris !== null) {
    $cek = $conn->prepare("SELECT id FROM bahan_laboratorium WHERE no_inventaris = ? AND id <> ? LIMIT 1");
    $cek->bind_param("si", $no_inventaris, $id);
    $cek->execute();
    $dup = $cek->get_result()->fetch_assoc();
    $cek->close();
    if ($dup) {
        fail($id, 'No inventaris sudah dipakai data lain.');
    }
}

// ===== UPDATE =====
$stmt = $conn->prepare("
    UPDATE bahan_laboratorium SET
        nama_bahan = ?,
        rumus_kimia = ?,
        spesifikasi_id = ?,
        lokasi_id = ?,
        volume = ?,
        satuan_id = ?,
        tahun_pengadaan = ?,
        no_inventaris = ?,
        sumber_pengadaan_id = ?,
        tanggal_kadaluarsa = ?,
        jumlah_total = ?,
        jumlah_baik = ?,
        jumlah_rusak_berat = ?,
        jumlah_rusak_ringan = ?,
        jumlah_hilang = ?,
        keterangan = ?
    WHERE id = ?
");

if (!$stmt) {
    fail($id, "Prepare gagal: " . $conn->error);
}

// 17 parameter
$types = "ssiisissisiiiiisi";

$stmt->bind_param(
    $types,
    $nama_bahan,
    $rumus_kimia,
    $spesifikasi_id,
    $lokasi_id,
    $volume,
    $satuan_id,
    $tahun_pengadaan_db,
    $no_inventaris,
    $sumber_pengadaan_id,
    $tanggal_kadaluarsa_db,
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
    'nama_bahan' => $nama_bahan,
    'no_inventaris' => $no_inventaris,
    'rumus_kimia' => $rumus_kimia,
    'spesifikasi_id' => $spesifikasi_id,
    'lokasi_id' => $lokasi_id,
    'volume' => $volume,
    'satuan_id' => $satuan_id,
    'tahun_pengadaan' => $tahun_pengadaan_db,
    'sumber_pengadaan_id' => $sumber_pengadaan_id,
    'tanggal_kadaluarsa' => $tanggal_kadaluarsa_db,
    'jumlah_total' => $jumlah_total,
    'jumlah_baik' => $jumlah_baik,
    'jumlah_rusak_berat' => $jumlah_rusak_berat,
    'jumlah_rusak_ringan' => $jumlah_rusak_ringan,
    'jumlah_hilang' => $jumlah_hilang,
    'keterangan' => $keterangan
];

logActivity(
    'bahan',
    'update',
    'Update bahan laboratorium',
    $username,
    json_encode($before),
    json_encode($after)
);

header("Location: bahan-list.php?update=1");
exit;
