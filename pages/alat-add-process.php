<?php
// pages/alat-add-process.php
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: alat-add.php");
    exit;
}

function fail(string $msg): void {
    header("Location: alat-add.php?fail=" . urlencode($msg));
    exit;
}

// Normalisasi tanggal agar aman kalau ada input "2025" (jadi 2025-01-01)
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

// ✅ TOTAL OTOMATIS (abaikan input jumlah_total dari form)
$jumlah_total = $jumlah_baik + $jumlah_rusak_berat + $jumlah_rusak_ringan + $jumlah_hilang;

$keterangan = trim($_POST['keterangan'] ?? '');

// ===== Validasi wajib =====
if ($nama_alat === '') fail('Nama alat wajib diisi.');
if ($lokasi_id <= 0) fail('Lokasi wajib dipilih.');
if ($tahun_pengadaan_db === null) fail('Tahun pengadaan wajib & harus valid.');
if ($sumber_pengadaan_id <= 0) fail('Sumber pengadaan wajib dipilih.');

// No inventaris opsional
if ($no_inventaris === '') $no_inventaris = null;

// (Opsional) cek duplikat no_inventaris jika diisi
if ($no_inventaris !== null) {
    $cek = $conn->prepare("SELECT id FROM alat_laboratorium WHERE no_inventaris = ? LIMIT 1");
    $cek->bind_param("s", $no_inventaris);
    $cek->execute();
    $dup = $cek->get_result()->fetch_assoc();
    $cek->close();
    if ($dup) {
        fail('No inventaris sudah dipakai alat lain.');
    }
}

// ===== Insert =====
$sql = "
    INSERT INTO alat_laboratorium
    (
        nama_alat,
        merk_alat,
        spesifikasi,
        lokasi_id,
        tahun_pengadaan,
        sumber_pengadaan_id,
        no_inventaris,
        jumlah_total,
        jumlah_baik,
        jumlah_rusak_berat,
        jumlah_rusak_ringan,
        jumlah_hilang,
        keterangan
    )
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    fail("Prepare gagal: " . $conn->error);
}

// 13 param: s s s i s i s i i i i i s
$stmt->bind_param(
    "sssisisiiiiis",
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
    $keterangan
);

$stmt->execute();
$newId = $stmt->insert_id;
$stmt->close();

// ===== Log =====
$username = $_SESSION['username'] ?? 'unknown';

logActivity(
    'alat',
    'create',
    'Tambah alat laboratorium',
    $username,
    null,
    json_encode([
        'id' => $newId,
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
    ])
);

header("Location: alat-list.php?success=1");
exit;
