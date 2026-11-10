<?php
// pages/bahan-add-process.php (fresh sesuai skema baru)
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: bahan-add.php");
    exit;
}

function fail(string $msg): void {
    header("Location: bahan-add.php?fail=" . urlencode($msg));
    exit;
}

// Normalisasi DATE agar aman (support YYYY-MM-DD / YYYY / MM/DD/YYYY)
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

// ===== Validasi minimal =====
if ($nama_bahan === '') fail('Nama bahan wajib diisi');
if ($tahun_pengadaan_db === null) fail('Tahun pengadaan wajib & harus valid');
if ($satuan_id === null) fail('Satuan wajib dipilih');

// No inventaris opsional
if ($no_inventaris === '') $no_inventaris = null;

// ===== Insert (prepared) =====
$stmt = $conn->prepare("
    INSERT INTO bahan_laboratorium
    (nama_bahan, rumus_kimia, spesifikasi_id, lokasi_id, volume, satuan_id,
     tahun_pengadaan, no_inventaris, sumber_pengadaan_id, tanggal_kadaluarsa,
     jumlah_total, jumlah_baik, jumlah_rusak_berat, jumlah_rusak_ringan, jumlah_hilang,
     keterangan, created_at)
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

if (!$stmt) {
    fail("Prepare gagal: " . $conn->error);
}

// 16 parameter
$types = "ssiisissisiiiiis";

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
    $keterangan
);

$stmt->execute();
$newId = $stmt->insert_id;
$stmt->close();

// ===== Log (signature project: module first) =====
logActivity(
    'bahan',
    'create',
    'Tambah bahan laboratorium',
    $_SESSION['username'] ?? 'unknown',
    null,
    json_encode([
        'id' => $newId,
        'nama_bahan' => $nama_bahan,
        'no_inventaris' => $no_inventaris,
        'lokasi_id' => $lokasi_id,
        'satuan_id' => $satuan_id,
        'sumber_pengadaan_id' => $sumber_pengadaan_id,
        'tahun_pengadaan' => $tahun_pengadaan_db,
        'jumlah_total' => $jumlah_total,
        'jumlah_baik' => $jumlah_baik,
        'jumlah_rusak_berat' => $jumlah_rusak_berat,
        'jumlah_rusak_ringan' => $jumlah_rusak_ringan,
        'jumlah_hilang' => $jumlah_hilang
    ])
);

header("Location: bahan-list.php?success=1");
exit;
