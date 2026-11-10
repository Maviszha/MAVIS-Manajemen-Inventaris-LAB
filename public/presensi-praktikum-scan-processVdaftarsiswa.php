<?php
// /mavis/public/presensi-praktikum-scan-process.php
// Proses scan barcode (NIS) untuk presensi praktikum dari halaman PUBLIC

include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/log.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /mavis/public/presensi-praktikum-scan.php");
    exit;
}

$token  = trim($_POST['t'] ?? '');
$barcode = trim($_POST['barcode'] ?? '');

if ($token === '' || !ctype_xdigit($token)) {
    header("Location: /mavis/public/presensi-praktikum-scan.php?msg=" . urlencode("Token tidak valid"));
    exit;
}

if ($barcode === '') {
    header("Location: /mavis/public/presensi-praktikum-scan.php?t=" . urlencode($token) . "&msg=" . urlencode("Barcode/NIS kosong"));
    exit;
}

// Ambil sesi via token
$stmt = $conn->prepare(
    "SELECT p.id, p.kelas_id, p.public_token FROM presensi_praktikum p WHERE p.public_token = ? LIMIT 1"
);
$stmt->bind_param("s", $token);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$session) {
    header("Location: /mavis/public/presensi-praktikum-scan.php?msg=" . urlencode("Sesi tidak ditemukan"));
    exit;
}

$presensi_id = (int)$session['id'];
$kelas_id    = (int)$session['kelas_id'];

// Cari siswa by NIS
$s = $conn->prepare("SELECT id, nama, kelas_id FROM siswa WHERE nis = ? LIMIT 1");
$s->bind_param("s", $barcode);
$s->execute();
$stu = $s->get_result()->fetch_assoc();
$s->close();

if (!$stu) {
    header("Location: /mavis/public/presensi-praktikum-scan.php?t=" . urlencode($token) . "&msg=" . urlencode("NIS tidak ditemukan: {$barcode}"));
    exit;
}

if ((int)$stu['kelas_id'] !== $kelas_id) {
    header("Location: /mavis/public/presensi-praktikum-scan.php?t=" . urlencode($token) . "&msg=" . urlencode("Siswa bukan dari kelas sesi ini"));
    exit;
}

$siswa_id = (int)$stu['id'];
$nama     = (string)$stu['nama'];

// Update hadir_praktikum + keterangan otomatis (cek laporan yang sudah ada)
$u = $conn->prepare(
    "UPDATE presensi_praktikum_detail
     SET hadir_praktikum = 1,
         scan_time = NOW(),
         keterangan_otomatis = CASE
           WHEN laporan = 1 THEN 'lengkap'
           ELSE 'hadir praktikum'
         END
     WHERE presensi_id = ? AND siswa_id = ?"
);
$u->bind_param("ii", $presensi_id, $siswa_id);
$u->execute();
$affected = $u->affected_rows;
$u->close();

// Logging (username public)
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
logActivity(
    'presensi_praktikum',
    'scan_public',
    'Scan hadir praktikum (public)',
    'public',
    null,
    json_encode([
        'presensi_id' => $presensi_id,
        'siswa_id' => $siswa_id,
        'nis' => $barcode,
        'nama' => $nama,
        'affected' => $affected,
        'ip' => $ip,
        'user_agent' => $ua
    ])
);

header("Location: /mavis/public/presensi-praktikum-scan.php?t=" . urlencode($token) . "&ok=" . urlencode($nama));
exit;
