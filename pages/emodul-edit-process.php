<?php
// pages/emodul-edit-process.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

function redirectBack(int $id, string $param, string $val): void {
    header("Location: emodul-edit.php?id=$id&" . urlencode($param) . "=" . urlencode($val));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: emodul-list.php");
    exit;
}

$id            = (int)($_POST['id']            ?? 0);
$judul         = trim($_POST['judul']          ?? '');
$penulis       = trim($_POST['penulis']        ?? '');
$tanggal       = trim($_POST['tanggal']        ?? '');
$kelas_id      = (int)($_POST['kelas_id']      ?? 0);
$link_pretest  = trim($_POST['link_pretest']   ?? '');
$link_posttest = trim($_POST['link_posttest']  ?? '');
$link_angket   = trim($_POST['link_angket']    ?? '');
$keterangan    = trim($_POST['keterangan']     ?? '');
$link_video_emodul = trim($_POST['link_video_emodul'] ?? '');

if (!$id) { header("Location: emodul-list.php"); exit; }

if (!$judul || !$penulis || !$tanggal || !$kelas_id) {
    redirectBack($id, 'error', 'Judul, penulis, tanggal, dan kelas wajib diisi.');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    redirectBack($id, 'error', 'Format tanggal tidak valid.');
}

// Ambil data lama (untuk hapus file lama jika diganti)
$stmt = $conn->prepare("SELECT gambar_path, file_path FROM e_modul WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$old = $stmt->get_result()->fetch_assoc();
if (!$old) { header("Location: emodul-list.php"); exit; }

$base      = dirname(__DIR__) . '/';
$uploadDir = $base . 'uploads/emodul/';
$gambarDir = $uploadDir . 'gambar/';
$fileDir   = $uploadDir . 'file/';

foreach ([$uploadDir, $gambarDir, $fileDir] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

// --- GAMBAR (opsional, hanya ganti jika upload baru) ---
$gambarRelative = $old['gambar_path'];

if (!empty($_FILES['gambar']['name'])) {
    $gTmp  = $_FILES['gambar']['tmp_name'];
    $gMime = mime_content_type($gTmp);
    $allowedImg = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($gMime, $allowedImg)) {
        redirectBack($id, 'error', 'Gambar harus berformat JPG, PNG, atau WEBP.');
    }
    if ($_FILES['gambar']['size'] > 5 * 1024 * 1024) {
        redirectBack($id, 'error', 'Ukuran gambar melebihi 5 MB.');
    }

    $extMap = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $gExt   = $extMap[$gMime];
    $gName  = 'cover_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $gExt;
    $gPath  = $gambarDir . $gName;

    if (move_uploaded_file($gTmp, $gPath)) {
        // Hapus gambar lama
        if ($old['gambar_path'] && file_exists($base . $old['gambar_path'])) {
            @unlink($base . $old['gambar_path']);
        }
        $gambarRelative = 'uploads/emodul/gambar/' . $gName;
    }
}

// --- FILE PDF (opsional, hanya ganti jika upload baru) ---
$fileRelative = $old['file_path'];

if (!empty($_FILES['file_emodul']['name'])) {
    $fileTmp  = $_FILES['file_emodul']['tmp_name'];
    $fileType = mime_content_type($fileTmp);
    $allowedFile = [
        'application/pdf',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];
    if (!in_array($fileType, $allowedFile)) {
        redirectBack($id, 'error', 'File e-modul harus berformat PDF, PPT, atau PPTX.');
    }
    if ($_FILES['file_emodul']['size'] > 20 * 1024 * 1024) {
        redirectBack($id, 'error', 'Ukuran file melebihi 20 MB.');
    }

    $extMap   = [
        'application/pdf' => 'pdf',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
    ];
    $fileExt  = $extMap[$fileType] ?? 'bin';
    $fileName = 'emodul_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
    $filePath = $fileDir . $fileName;

    if (move_uploaded_file($fileTmp, $filePath)) {
        // Hapus file lama
        if ($old['file_path'] && file_exists($base . $old['file_path'])) {
            @unlink($base . $old['file_path']);
        }
        $fileRelative = 'uploads/emodul/file/' . $fileName;
    } else {
        redirectBack($id, 'error', 'Gagal menyimpan file PDF baru. Periksa izin folder.');
    }
}

// --- UPDATE DATABASE ---
$upd = $conn->prepare(
    "UPDATE e_modul
     SET judul=?, penulis=?, tanggal=?, kelas_id=?,
         link_pretest=?, link_posttest=?, link_angket=?,
         keterangan=?, link_video_emodul=?, gambar_path=?, file_path=?
     WHERE id=?"
);
$upd->bind_param('sssssssssssi',
    $judul, $penulis, $tanggal, $kelas_id,
    $link_pretest, $link_posttest, $link_angket,
    $keterangan, $link_video_emodul, $gambarRelative, $fileRelative, $id
);

if ($upd->execute()) {
    header("Location: emodul-list.php?success=1");
} else {
    redirectBack($id, 'error', 'Gagal menyimpan perubahan: ' . $conn->error);
}
exit;
