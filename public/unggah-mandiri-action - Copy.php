<?php
// public/unggah-mandiri-action.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';

function flash_public($type, $message) {
  $_SESSION['flash_public'] = ['type'=>$type, 'message'=>$message];
}

function back() {
  header("Location: unggah-mandiri.php");
  exit;
}

function clean_str($s): string {
  $s = trim((string)$s);
  $s = preg_replace('/\s+/', ' ', $s);
  return $s;
}

function ensure_dir($dir): void {
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
      throw new RuntimeException("Gagal membuat folder upload.");
    }
  }
}

function save_upload(array $file, array $allowedMime, int $maxBytes, string $destDirAbs, string $destNameNoExt): string {
  if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
    throw new RuntimeException("File upload tidak valid.");
  }
  if ((int)$file['size'] > $maxBytes) {
    throw new RuntimeException("Ukuran file melebihi batas.");
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($file['tmp_name']);
  if (!isset($allowedMime[$mime])) {
    throw new RuntimeException("Format file tidak sesuai.");
  }

  $ext = $allowedMime[$mime];
  ensure_dir($destDirAbs);

  $filename = $destNameNoExt . "_" . date('Ymd_His') . "_" . bin2hex(random_bytes(4)) . "." . $ext;
  $pathAbs = rtrim($destDirAbs, '/\\') . DIRECTORY_SEPARATOR . $filename;

  if (!move_uploaded_file($file['tmp_name'], $pathAbs)) {
    throw new RuntimeException("Gagal menyimpan file.");
  }

  // Path relatif dari root mavis
  $rel = "uploads/mandiri/" . basename($destDirAbs) . "/" . $filename;
  return $rel;
}

try {
  if (!isset($conn) || !($conn instanceof mysqli)) {
    throw new RuntimeException("Koneksi database tidak tersedia.");
  }

  // Input
  $nis = clean_str($_POST['nis'] ?? '');
  $nama_input = clean_str($_POST['nama'] ?? '');
  $kelas_id_input = (int)($_POST['kelas_id'] ?? 0);
  $judul = clean_str($_POST['judul'] ?? '');

  if ($nis === '' || $judul === '') {
    throw new RuntimeException("NIS dan Judul wajib diisi.");
  }

  // ============================
  // VALIDASI SISWA: NIS SAJA
  // ============================
  $stmt = $conn->prepare("SELECT id, nis, nama, kelas_id FROM siswa WHERE nis = ? LIMIT 1");
  if (!$stmt) throw new RuntimeException("Prepare gagal: " . $conn->error);
  $stmt->bind_param("s", $nis);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  if (!$row) {
    throw new RuntimeException("NIS tidak ditemukan. Upload dibatalkan.");
  }

  $siswa_id = (int)$row['id'];
  $nama_db = (string)$row['nama'];
  $kelas_id_db = (int)$row['kelas_id'];

  // (Opsional) kalau kamu mau: paksa dropdown kelas harus sama dengan kelas DB
  // Kalau tidak perlu, hapus blok ini.
  if ($kelas_id_input > 0 && $kelas_id_input !== $kelas_id_db) {
    throw new RuntimeException("Kelas yang dipilih tidak sesuai dengan data siswa.");
  }

  // Upload rules
  $posterAllowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
  ];
  $pdfAllowed = [
    'application/pdf' => 'pdf',
  ];

  // Lokasi folder
  $baseAbs = realpath(__DIR__ . '/..');
  if (!$baseAbs) throw new RuntimeException("Base path tidak valid.");

  $posterDirAbs = $baseAbs . '/uploads/mandiri/poster';
  $pdfDirAbs    = $baseAbs . '/uploads/mandiri/fulltext';

  // Nama dasar file aman
  $safeNis = preg_replace('/[^0-9A-Za-z_\-]/', '', $nis);
  if ($safeNis === '') $safeNis = 'unknown';

  // Wajib upload
  if (empty($_FILES['poster']['name'])) throw new RuntimeException("Poster wajib diunggah.");
  if (empty($_FILES['fulltext']['name'])) throw new RuntimeException("File PDF wajib diunggah.");

  $posterPath = save_upload($_FILES['poster'], $posterAllowed, 2 * 1024 * 1024, $posterDirAbs, "poster_{$safeNis}");
  $pdfPath    = save_upload($_FILES['fulltext'], $pdfAllowed, 10 * 1024 * 1024, $pdfDirAbs, "fulltext_{$safeNis}");

  // Simpan DB
  $ip = $_SERVER['REMOTE_ADDR'] ?? null;
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

  $stmt = $conn->prepare("INSERT INTO unggah_mandiri (siswa_id, judul, poster_path, fulltext_path, ip_address, user_agent)
                          VALUES (?, ?, ?, ?, ?, ?)");
  if (!$stmt) throw new RuntimeException("Prepare gagal: " . $conn->error);
  $stmt->bind_param("isssss", $siswa_id, $judul, $posterPath, $pdfPath, $ip, $ua);
  $ok = $stmt->execute();
  $err = $stmt->error;
  $stmt->close();

  if (!$ok) {
    @unlink($baseAbs . '/' . $posterPath);
    @unlink($baseAbs . '/' . $pdfPath);
    throw new RuntimeException("Gagal simpan data: " . $err);
  }

  // (Opsional) pesan sukses pakai nama DB biar user yakin
  flash_public('success', "Upload berhasil! (NIS: {$nis} | Nama: {$nama_db})");
  back();

} catch (Throwable $e) {
  flash_public('danger', 'Gagal: ' . $e->getMessage());
  back();
}
