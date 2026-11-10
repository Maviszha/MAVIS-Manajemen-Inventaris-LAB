<?php
// public/unggah-mandiri-action.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  exit('Koneksi database tidak tersedia.');
}

function is_ajax(): bool {
  if (!empty($_GET['ajax']) && $_GET['ajax'] == '1') return true;
  $hdr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
  return strtolower($hdr) === 'xmlhttprequest';
}

function json_out(bool $ok, string $message = '', array $extra = []): void {
  if (ob_get_length()) { @ob_clean(); }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
  exit;
}

function flash_public(bool $ok, string $message): void {
  $_SESSION['flash_public'] = ['ok' => $ok, 'message' => $message];
}

function redirect_public(): void {
  header("Location: /mavis/public/unggah-mandiri.php");
  exit;
}

function ensure_admin(): void {
  $role = strtolower((string)($_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '')));
  if ($role !== 'admin') {
    http_response_code(403);
    if (is_ajax()) json_out(false, 'Akses ditolak (admin saja).');
    exit('Akses ditolak (admin saja).');
  }
}

function safe_filename(string $name): string {
  $name = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $name);
  return trim($name, '_');
}

function upload_file(array $file, array $allowedMimeToExt, int $maxBytes, string $dirAbs, string $prefix): string {
  if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
    throw new RuntimeException('File upload tidak valid.');
  }
  if ((int)$file['size'] > $maxBytes) {
    throw new RuntimeException('Ukuran file terlalu besar.');
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($file['tmp_name']);
  if (!isset($allowedMimeToExt[$mime])) {
    throw new RuntimeException('Format file tidak didukung.');
  }
  $ext = $allowedMimeToExt[$mime];

  if (!is_dir($dirAbs)) {
    if (!mkdir($dirAbs, 0775, true) && !is_dir($dirAbs)) {
      throw new RuntimeException('Gagal membuat folder upload.');
    }
  }

  $rand = bin2hex(random_bytes(6));
  $filename = safe_filename($prefix . '_' . date('Ymd_His') . '_' . $rand . '.' . $ext);
  $pathAbs = rtrim($dirAbs, '/\\') . DIRECTORY_SEPARATOR . $filename;

  if (!move_uploaded_file($file['tmp_name'], $pathAbs)) {
    throw new RuntimeException('Gagal menyimpan file upload.');
  }

  // return relative to /mavis/
  $rel = 'uploads/mandiri/' . $filename;
  return $rel;
}

function get_student_by_nis(mysqli $conn, string $nis): ?array {
  $stmt = $conn->prepare("
    SELECT s.id, s.nis, s.nama, s.kelas_id, k.nama AS kelas_nama
    FROM siswa s
    LEFT JOIN kelas k ON k.id = s.kelas_id
    WHERE s.nis = ?
    LIMIT 1
  ");
  if (!$stmt) return null;
  $stmt->bind_param("s", $nis);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ?: null;
}

function delete_one_upload(mysqli $conn, int $id): array {
  $stmt = $conn->prepare("SELECT poster_path, fulltext_path FROM unggah_mandiri WHERE id = ? LIMIT 1");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  if (!$row) return ['ok' => false, 'message' => 'Data tidak ditemukan.'];

  $stmt = $conn->prepare("DELETE FROM unggah_mandiri WHERE id = ?");
  $stmt->bind_param("i", $id);
  $ok = $stmt->execute();
  $err = $stmt->error;
  $stmt->close();

  if (!$ok) return ['ok' => false, 'message' => 'Gagal hapus: ' . $err];

  $root = realpath(__DIR__ . '/..');
  foreach (['poster_path','fulltext_path'] as $k) {
    $p = (string)($row[$k] ?? '');
    if ($p !== '') {
      $abs = $root . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $p), DIRECTORY_SEPARATOR);
      if (is_file($abs)) @unlink($abs);
    }
  }
  return ['ok' => true, 'message' => 'Berhasil dihapus.'];
}

$action = $_GET['action'] ?? '';

/* =========================
   CEK SISWA (PUBLIC AJAX)
========================= */
if ($action === 'cek_siswa') {
  $nis = trim((string)($_GET['nis'] ?? ''));
  if ($nis === '') json_out(false, 'NIS kosong.');

  $siswa = get_student_by_nis($conn, $nis);
  if (!$siswa) {
    json_out(false, 'NIS tidak ditemukan.');
  }

  json_out(true, 'OK', [
    'siswa' => [
      'id' => (int)$siswa['id'],
      'nis' => (string)$siswa['nis'],
      'nama' => (string)($siswa['nama'] ?? ''),
      'kelas_id' => (int)($siswa['kelas_id'] ?? 0),
      'kelas_nama' => (string)($siswa['kelas_nama'] ?? ''),
    ]
  ]);
}

/* =========================
   SUBMIT (PUBLIC)
========================= */
if ($action === 'submit') {
  try {
    $nis   = trim((string)($_POST['nis'] ?? ''));
    $judul = trim((string)($_POST['judul'] ?? ''));

    if ($nis === '' || $judul === '') {
      flash_public(false, 'NIS dan Judul wajib diisi.');
      redirect_public();
    }

    // VALIDASI: patokan NIS saja
    $siswa = get_student_by_nis($conn, $nis);
    if (!$siswa) {
      flash_public(false, 'Upload gagal: NIS tidak ditemukan.');
      redirect_public();
    }

  // === SIMPAN KE FOLDER TERPISAH ===
$rootAbs = realpath(__DIR__ . '/..'); // -> /mavis
$posterDirAbs   = $rootAbs . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'mandiri' . DIRECTORY_SEPARATOR . 'poster';
$fulltextDirAbs = $rootAbs . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'mandiri' . DIRECTORY_SEPARATOR . 'fulltext';

$posterRel = upload_file(
  $_FILES['poster'] ?? [],
  ['image/jpeg' => 'jpg', 'image/png' => 'png'],
  3 * 1024 * 1024,
  $posterDirAbs,
  'poster_' . $nis
);

$fulltextRel = upload_file(
  $_FILES['fulltext'] ?? [],
  ['application/pdf' => 'pdf'],
  10 * 1024 * 1024,
  $fulltextDirAbs,
  'fulltext_' . $nis
);

// IMPORTANT: perbaiki return path-nya agar sesuai subfolder
$posterRel   = 'uploads/mandiri/poster/' . basename($posterRel);
$fulltextRel = 'uploads/mandiri/fulltext/' . basename($fulltextRel);


    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $siswa_id = (int)$siswa['id'];

    $stmt = $conn->prepare("INSERT INTO unggah_mandiri (siswa_id, judul, poster_path, fulltext_path, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) throw new RuntimeException('Prepare gagal: ' . $conn->error);

    $stmt->bind_param("isssss", $siswa_id, $judul, $posterRel, $fulltextRel, $ip, $ua);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();

    if (!$ok) throw new RuntimeException('Gagal menyimpan: ' . $err);

    flash_public(true, 'Upload berhasil. Terima kasih!');
    redirect_public();

  } catch (Throwable $e) {
    flash_public(false, 'Error: ' . $e->getMessage());
    redirect_public();
  }
}

/* =========================
   DELETE ONE / SELECTED (ADMIN, AJAX)
========================= */
if ($action === 'delete_one') {
  ensure_admin();
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) json_out(false, 'ID tidak valid.');

  $ret = delete_one_upload($conn, $id);
  json_out((bool)$ret['ok'], (string)$ret['message']);
}

if ($action === 'delete_selected') {
  ensure_admin();
  $ids = $_POST['ids'] ?? [];
  if (!is_array($ids) || count($ids) === 0) json_out(false, 'Tidak ada data yang dipilih.');

  $deleted = 0;
  foreach ($ids as $raw) {
    $id = (int)$raw;
    if ($id <= 0) continue;
    $ret = delete_one_upload($conn, $id);
    if (!empty($ret['ok'])) $deleted++;
  }
  json_out(true, "Berhasil menghapus {$deleted} data.");
}

http_response_code(404);
if (is_ajax()) json_out(false, 'Aksi tidak dikenal.');
exit('Aksi tidak dikenal.');
