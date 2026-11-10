<?php
// pages/data-siswa-action.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';

// ===== Guard role (admin/guru) =====
$role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? null);
if (!in_array($role, ['admin', 'guru'], true)) {
  http_response_code(403);
  exit('Akses ditolak.');
}

if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  exit('Koneksi database tidak tersedia.');
}

function flash($type, $message) {
  $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function redirectBack($fallback = 'data-siswa.php') {
  $ref = $_SERVER['HTTP_REFERER'] ?? $fallback;
  header("Location: $ref");
  exit;
}

function is_ajax(): bool {
  if (!empty($_GET['ajax']) && $_GET['ajax'] == '1') return true;
  $hdr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
  return strtolower($hdr) === 'xmlhttprequest';
}

function json_out($ok, $message = '', $extra = []) {
  // pastikan tidak ada output sampah sebelum JSON
  if (ob_get_length()) { @ob_clean(); }

  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge(['ok' => (bool)$ok, 'message' => (string)$message], (array)$extra));
  exit;
}

// ===== Logging (fallback jika logActivity tidak ada) =====
function log_action(mysqli $conn, string $module, string $action, string $desc): void {
  if (function_exists('logActivity')) {
    try { @logActivity($module, $action, $desc); } catch (\Throwable $e) {}
    return;
  }

  $userId = (int)($_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 0));
  $stmt = $conn->prepare("INSERT INTO system_log (user_id, module, action, description) VALUES (?, ?, ?, ?)");
  if ($stmt) {
    $stmt->bind_param("isss", $userId, $module, $action, $desc);
    $stmt->execute();
    $stmt->close();
  }
}

function get_kelas_id_by_name(mysqli $conn, string $namaKelas): int {
  $namaKelas = trim($namaKelas);
  if ($namaKelas === '') return 0;

  $stmt = $conn->prepare("SELECT id FROM kelas WHERE nama = ? LIMIT 1");
  $stmt->bind_param("s", $namaKelas);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  if ($row) return (int)$row['id'];

  $aktif = 1;
  $stmt = $conn->prepare("INSERT INTO kelas (nama, aktif) VALUES (?, ?)");
  $stmt->bind_param("si", $namaKelas, $aktif);
  $stmt->execute();
  $newId = $stmt->insert_id;
  $stmt->close();
  return (int)$newId;
}

function handle_upload_foto(array $file, string $nis): ?string {
  if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;

  $maxBytes = 2 * 1024 * 1024;
  if ((int)$file['size'] > $maxBytes) {
    throw new RuntimeException('Ukuran foto maksimal 2MB.');
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime  = $finfo->file($file['tmp_name']);
  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
  ];
  if (!isset($allowed[$mime])) {
    throw new RuntimeException('Format foto harus jpg/png/webp.');
  }
  $ext = $allowed[$mime];

  $dir = __DIR__ . '/../uploads/siswa';
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
      throw new RuntimeException('Gagal membuat folder uploads/siswa.');
    }
  }

  $safeNis = preg_replace('/[^0-9A-Za-z_\-]/', '', (string)$nis);
  if ($safeNis === '') $safeNis = 'unknown';

  $filename = 'member' . $safeNis . '.' . $ext;
  $pathAbs  = $dir . '/' . $filename;

  if (is_file($pathAbs)) @unlink($pathAbs);

  if (!move_uploaded_file($file['tmp_name'], $pathAbs)) {
    throw new RuntimeException('Gagal menyimpan foto.');
  }

  return 'uploads/siswa/' . $filename;
}

$action = $_GET['action'] ?? '';

/* =========================
   KELAS LIST (AJAX)
========================= */
if ($action === 'kelas_list') {
  try {
    $list = [];
    $q = $conn->query("SELECT id, nama, aktif FROM kelas ORDER BY nama ASC");
    if ($q) while ($r = $q->fetch_assoc()) $list[] = $r;
    json_out(true, 'OK', ['kelas' => $list]);
  } catch (Throwable $e) {
    json_out(false, $e->getMessage());
  }
}

try {

  /* =========================
     KELOLA KELAS
  ========================= */
  if ($action === 'kelas_create') {
    $nama = trim($_POST['nama_kelas'] ?? '');
    $aktif = (int)($_POST['aktif'] ?? 1);
    $aktif = ($aktif === 0) ? 0 : 1;

    if ($nama === '') {
      if (is_ajax()) json_out(false, 'Nama kelas wajib diisi.');
      flash('danger', 'Nama kelas wajib diisi.');
      redirectBack('data-siswa.php');
    }

    $stmt = $conn->prepare("INSERT INTO kelas (nama, aktif) VALUES (?, ?)");
    $stmt->bind_param("si", $nama, $aktif);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();

    if (!$ok) {
      if (is_ajax()) json_out(false, 'Gagal tambah kelas: ' . $err);
      flash('danger', 'Gagal tambah kelas: ' . $err);
      redirectBack('data-siswa.php');
    }

    
$newSiswaId = (int)$conn->insert_id;

// Backfill presensi_praktikum_detail untuk semua sesi praktikum di kelas yang sama
// agar siswa baru langsung muncul di laporan sesi-sesi lama.
$qs = $conn->prepare("SELECT id FROM presensi_praktikum WHERE kelas_id = ?");
$qs->bind_param("i", $kelas_id);
$qs->execute();
$rsSesi = $qs->get_result();

$ins = $conn->prepare("INSERT IGNORE INTO presensi_praktikum_detail (presensi_id, siswa_id, hadir_praktikum, laporan, catatan, keterangan_otomatis)
                       VALUES (?, ?, 0, 0, '', '')");

while ($rowSesi = $rsSesi->fetch_assoc()) {
  $pid = (int)$rowSesi['id'];
  $ins->bind_param("ii", $pid, $newSiswaId);
  $ins->execute();
}

$ins->close();
$qs->close();

log_action($conn, 'kelas', 'create', "Tambah kelas: {$nama}");
    if (is_ajax()) json_out(true, 'Kelas berhasil ditambahkan.');
    flash('success', 'Kelas berhasil ditambahkan.');
    redirectBack('data-siswa.php');
  }

  if ($action === 'kelas_update') {
    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama_kelas'] ?? '');
    $aktif = (int)($_POST['aktif'] ?? 1);
    $aktif = ($aktif === 0) ? 0 : 1;

    if ($id <= 0 || $nama === '') {
      if (is_ajax()) json_out(false, 'Data kelas tidak valid.');
      flash('danger', 'Data kelas tidak valid.');
      redirectBack('data-siswa.php');
    }

    $stmt = $conn->prepare("UPDATE kelas SET nama = ?, aktif = ? WHERE id = ?");
    $stmt->bind_param("sii", $nama, $aktif, $id);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();

    if (!$ok) {
      if (is_ajax()) json_out(false, 'Gagal update kelas: ' . $err);
      flash('danger', 'Gagal update kelas: ' . $err);
      redirectBack('data-siswa.php');
    }

    log_action($conn, 'kelas', 'update', "Update kelas ID {$id} => {$nama} (aktif={$aktif})");
    if (is_ajax()) json_out(true, 'Kelas berhasil diperbarui.');
    flash('success', 'Kelas berhasil diperbarui.');
    redirectBack('data-siswa.php');
  }

  if ($action === 'kelas_delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      if (is_ajax()) json_out(false, 'ID kelas tidak valid.');
      flash('danger', 'ID kelas tidak valid.');
      redirectBack('data-siswa.php');
    }

    $stmt = $conn->prepare("SELECT nama FROM kelas WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
      if (is_ajax()) json_out(false, 'Kelas tidak ditemukan.');
      flash('warning', 'Kelas tidak ditemukan.');
      redirectBack('data-siswa.php');
    }

    $stmt = $conn->prepare("DELETE FROM kelas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();

    if (!$ok) {
      $msg = 'Gagal hapus kelas: ' . $err . ' (Jika dipakai siswa, ubah status jadi Nonaktif).';
      if (is_ajax()) json_out(false, $msg);
      flash('danger', $msg);
      redirectBack('data-siswa.php');
    }

    log_action($conn, 'kelas', 'delete', "Hapus kelas: {$row['nama']}");
    if (is_ajax()) json_out(true, 'Kelas berhasil dihapus.');
    flash('success', 'Kelas berhasil dihapus.');
    redirectBack('data-siswa.php');
  }

  /* =========================
     SISWA: CREATE
  ========================= */
  if ($action === 'create') {
    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $kelas_id = (int)($_POST['kelas_id'] ?? 0);

    if ($nis === '' || $nama === '' || $kelas_id <= 0) {
      flash('danger', 'NIS, Nama, dan Kelas wajib diisi.');
      redirectBack('data-siswa.php');
    }

    $fotoPath = null;
    if (!empty($_FILES['foto']['name'])) {
      $fotoPath = handle_upload_foto($_FILES['foto'], $nis);
    }

    $stmt = $conn->prepare("INSERT INTO siswa (nis, nama, kelas_id, foto) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $nis, $nama, $kelas_id, $fotoPath);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();

    if (!$ok) {
      flash('danger', 'Gagal tambah siswa: ' . $err);
      redirectBack('data-siswa.php');
    }

    log_action($conn, 'siswa', 'create', "Tambah siswa NIS {$nis} - {$nama}");
    flash('success', 'Siswa berhasil ditambahkan.');
    header("Location: data-siswa.php");
    exit;
  }

  /* =========================
     SISWA: UPDATE
  ========================= */
  if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $kelas_id = (int)($_POST['kelas_id'] ?? 0);

    if ($id <= 0 || $nis === '' || $nama === '' || $kelas_id <= 0) {
      flash('danger', 'Data tidak valid.');
      redirectBack('data-siswa.php');
    }

    $oldFoto = null;
    $stmt = $conn->prepare("SELECT foto FROM siswa WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if ($row) $oldFoto = $row['foto'];

    $newFoto = $oldFoto;
    if (!empty($_FILES['foto']['name'])) {
      $newFoto = handle_upload_foto($_FILES['foto'], $nis);
      if ($oldFoto) {
        $oldAbs = __DIR__ . '/../' . ltrim($oldFoto, '/');
        if (is_file($oldAbs)) @unlink($oldAbs);
      }
    }

    $stmt = $conn->prepare("UPDATE siswa SET nis = ?, nama = ?, kelas_id = ?, foto = ? WHERE id = ?");
    $stmt->bind_param("ssisi", $nis, $nama, $kelas_id, $newFoto, $id);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();

    if (!$ok) {
      flash('danger', 'Gagal update siswa: ' . $err);
      redirectBack('data-siswa.php');
    }

    log_action($conn, 'siswa', 'update', "Update siswa ID {$id} => NIS {$nis} - {$nama}");
    flash('success', 'Siswa berhasil diupdate.');
    header("Location: data-siswa.php");
    exit;
  }

  /* =========================
     SISWA: DELETE
  ========================= */
  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      flash('danger', 'ID tidak valid.');
      redirectBack('data-siswa.php');
    }

    $stmt = $conn->prepare("SELECT nis, nama, foto FROM siswa WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
      flash('warning', 'Data siswa tidak ditemukan.');
      redirectBack('data-siswa.php');
    }

    $stmt = $conn->prepare("DELETE FROM siswa WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();

    if (!$ok) {
      flash('danger', 'Gagal hapus siswa: ' . $err);
      redirectBack('data-siswa.php');
    }

    if (!empty($row['foto'])) {
      $abs = __DIR__ . '/../' . ltrim($row['foto'], '/');
      if (is_file($abs)) @unlink($abs);
    }

    log_action($conn, 'siswa', 'delete', "Hapus siswa NIS {$row['nis']} - {$row['nama']}");
    flash('success', 'Siswa berhasil dihapus.');
    header("Location: data-siswa.php");
    exit;
  }

  flash('warning', 'Aksi tidak dikenal.');
  header("Location: data-siswa.php");
  exit;

} catch (Throwable $e) {
  if (is_ajax()) json_out(false, 'Error: ' . $e->getMessage());
  flash('danger', 'Error: ' . $e->getMessage());
  redirectBack('data-siswa.php');
}
