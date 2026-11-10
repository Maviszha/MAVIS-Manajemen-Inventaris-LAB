<?php
// public/emodul-laporan-action.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function flashRedirect(string $type, string $msg, string $url): void {
    $_SESSION['flash_emodul'] = ['type' => $type, 'msg' => $msg];
    header("Location: $url");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: emodul.php");
    exit;
}

$emId           = (int)($_POST['emodul_id'] ?? 0);
$namaKelompok   = trim($_POST['nama_kelompok']   ?? '');
$judulPraktikum = trim($_POST['judul_praktikum']  ?? '');

$backUrl = "/mavis/public/emodul-laporan.php?emodul_id=$emId";

if (!$emId || !$namaKelompok || !$judulPraktikum) {
    flashRedirect('error', 'Semua field wajib diisi.', $backUrl);
}

if (
    !isset($_FILES['file_laporan']) ||
    $_FILES['file_laporan']['error'] !== 0
) {
    flashRedirect('error', 'File laporan wajib diupload.', $backUrl);
}

// Pastikan e-modul ada
$chk = $conn->prepare("SELECT id FROM e_modul WHERE id = ?");
$chk->bind_param('i', $emId);
$chk->execute();
if (!$chk->get_result()->fetch_assoc()) {
    flashRedirect(
        'error',
        'E-Modul tidak ditemukan.',
        "/mavis/public/emodul.php"
    );
}
// =========================
// Upload file laporan
// =========================

$uploadDir = __DIR__ . '/../uploads/laporan/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$file = $_FILES['file_laporan'];

$fileName = $file['name'];
$tmpName  = $file['tmp_name'];
$fileSize = $file['size'];

$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

$allowed = ['pdf', 'doc', 'docx'];

if (!in_array($ext, $allowed)) {
    flashRedirect(
        'error',
        'Format file tidak didukung.',
        $backUrl
    );
}

if ($fileSize > 10 * 1024 * 1024) {
    flashRedirect(
        'error',
        'Ukuran file maksimal 10 MB.',
        $backUrl
    );
}

$newFileName =
    'laporan_' .
    time() . '_' .
    mt_rand(1000,9999) .
    '.' . $ext;

$targetFile = $uploadDir . $newFileName;

if (!move_uploaded_file($tmpName, $targetFile)) {
    flashRedirect(
        'error',
        'Gagal upload file laporan.',
        $backUrl
    );
}

// Simpan kelompok
$stmt = $conn->prepare(
    "INSERT INTO laporan_kelompok
    (
        emodul_id,
        nama_kelompok,
        judul_praktikum,
        file_laporan
    )
    VALUES (?, ?, ?, ?)"
);

$stmt->bind_param(
    'isss',
    $emId,
    $namaKelompok,
    $judulPraktikum,
    $newFileName
);

if (!$stmt->execute()) {
    flashRedirect('error', 'Gagal menyimpan laporan. Coba lagi.', $backUrl);
}

$kelompokId = $conn->insert_id;

// Simpan anggota (unlimited, ambil semua index yang dikirim)
$nisArr   = $_POST['nis']   ?? [];
$namaArr  = $_POST['nama']  ?? [];
$kelasArr = $_POST['kelas'] ?? [];

$stmtA = $conn->prepare(
    "INSERT INTO laporan_kelompok_anggota (kelompok_id, nis, nama, kelas, urutan)
     VALUES (?, ?, ?, ?, ?)"
);

$urutan = 1;
foreach (array_keys($nisArr) as $key) {
    $nis   = trim($nisArr[$key]   ?? '');
    $nama  = trim($namaArr[$key]  ?? '');
    $kelas = trim($kelasArr[$key] ?? '');

    // Skip baris yang semua kolomnya kosong
    if (!$nis && !$nama && !$kelas) continue;

    $stmtA->bind_param('isssi', $kelompokId, $nis, $nama, $kelas, $urutan);
    $stmtA->execute();
    $urutan++;
}

flashRedirect(
    'success',
    'Laporan berhasil dikumpulkan! Terima kasih, ' . h($namaKelompok) . '.',
    $backUrl
);
