<?php
// pages/emodul-add-process.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

function redirectBack(string $param, string $val): void {
    header("Location: emodul-add.php?" . urlencode($param) . "=" . urlencode($val));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: emodul-add.php");
    exit;
}

$judul      = trim($_POST['judul']      ?? '');
$penulis    = trim($_POST['penulis']    ?? '');
$tanggal      = trim($_POST['tanggal']      ?? '');
$link_pretest = trim($_POST['link_pretest'] ?? '');
$link_posttest= trim($_POST['link_posttest']?? '');
$link_angket  = trim($_POST['link_angket']  ?? '');
$keterangan   = trim($_POST['keterangan']   ?? '');
$link_video_emodul = trim($_POST['link_video_emodul'] ?? '');
$kelas_id     = (int)($_POST['kelas_id']    ?? 0);

if (!$judul || !$penulis || !$tanggal || !$kelas_id) {
    redirectBack('error', 'Judul, penulis, tanggal, dan kelas wajib diisi.');
}

// Validasi tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    redirectBack('error', 'Format tanggal tidak valid.');
}

// Direktori upload
$uploadDir  = dirname(__DIR__) . '/uploads/emodul/';
$gambarDir  = $uploadDir . 'gambar/';
$fileDir    = $uploadDir . 'file/';

foreach ([$uploadDir, $gambarDir, $fileDir] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

// Upload file PDF (wajib)
if (empty($_FILES['file_emodul']['name'])) {
    redirectBack('error', 'File PDF e-modul wajib diupload.');
}

$fileTmp   = $_FILES['file_emodul']['tmp_name'];
$fileType  = mime_content_type($fileTmp);
$allowedFile = [
    'application/pdf',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
];
if (!in_array($fileType, $allowedFile)) {
    redirectBack('error', 'File e-modul harus berformat PDF, PPT, atau PPTX.');
}
if ($_FILES['file_emodul']['size'] > 20 * 1024 * 1024) {
    redirectBack('error', 'Ukuran file melebihi 20 MB.');
}

$extMap  = [
    'application/pdf' => 'pdf',
    'application/vnd.ms-powerpoint' => 'ppt',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
];
$fileExt  = $extMap[$fileType] ?? 'bin';
$fileName = 'emodul_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
$filePath  = $fileDir . $fileName;

if (!move_uploaded_file($fileTmp, $filePath)) {
    redirectBack('error', 'Gagal menyimpan file PDF. Periksa izin folder.');
}
$fileRelative = 'uploads/emodul/file/' . $fileName;

// Upload gambar (opsional)
$gambarRelative = null;
if (!empty($_FILES['gambar']['name'])) {
    $gTmp  = $_FILES['gambar']['tmp_name'];
    $gMime = mime_content_type($gTmp);
    $allowedImg = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($gMime, $allowedImg)) {
        redirectBack('error', 'Gambar harus berformat JPG, PNG, atau WEBP.');
    }
    if ($_FILES['gambar']['size'] > 5 * 1024 * 1024) {
        redirectBack('error', 'Ukuran gambar melebihi 5 MB.');
    }
    $extMap = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $gExt   = $extMap[$gMime];
    $gName  = 'cover_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $gExt;
    $gPath  = $gambarDir . $gName;
    if (move_uploaded_file($gTmp, $gPath)) {
        $gambarRelative = 'uploads/emodul/gambar/' . $gName;
    }
}

// Simpan ke database
$stmt = $conn->prepare(
    "INSERT INTO e_modul (judul, penulis, tanggal, kelas_id, link_pretest, link_posttest, link_angket, keterangan, link_video_emodul, gambar_path, file_path)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('sssisssssss',
    $judul, $penulis, $tanggal,
    $kelas_id, $link_pretest, $link_posttest, $link_angket, $keterangan, $link_video_emodul,
    $gambarRelative, $fileRelative
);

if ($stmt->execute()) {
    header("Location: emodul-list.php?success=1");
} else {
    redirectBack('error', 'Gagal menyimpan ke database: ' . $conn->error);
}
exit;
