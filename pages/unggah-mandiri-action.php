<?php
// pages/unggah-mandiri-action.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';

$role = strtolower((string)($_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '')));
if ($role !== 'admin') {
  http_response_code(403);
  exit('Akses ditolak.');
}

if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  exit('Koneksi database tidak tersedia.');
}

function is_ajax(): bool {
  $hdr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
  return strtolower($hdr) === 'xmlhttprequest';
}

function json_out($ok, $message = '', $extra = []) {
  if (ob_get_length()) { @ob_clean(); }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge(['ok' => (bool)$ok, 'message' => (string)$message], (array)$extra));
  exit;
}

function flash($type, $message) {
  $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function delete_file_if_exists(string $path): void {
  $path = ltrim($path, '/');
  if ($path === '') return;
  // path tersimpan biasanya "uploads/xxx/yyy.ext" -> absolute dari root mavis
  $abs = dirname(__DIR__) . '/../' . $path; // naik dari pages -> mavis
  $abs = realpath($abs) ?: $abs;
  if (is_file($abs)) @unlink($abs);
}

$action = $_GET['action'] ?? '';

try {

  if ($action === 'delete_one') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      if (is_ajax()) json_out(false, 'ID tidak valid.');
      flash('danger', 'ID tidak valid.');
      header('Location: unggah-mandiri-list.php');
      exit;
    }

    $stmt = $conn->prepare("SELECT poster_path, fulltext_path FROM unggah_mandiri WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
      if (is_ajax()) json_out(false, 'Data tidak ditemukan.');
      flash('warning', 'Data tidak ditemukan.');
      header('Location: unggah-mandiri-list.php');
      exit;
    }

    $stmt = $conn->prepare("DELETE FROM unggah_mandiri WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();

    if (!$ok) {
      if (is_ajax()) json_out(false, 'Gagal hapus: ' . $err);
      flash('danger', 'Gagal hapus: ' . $err);
      header('Location: unggah-mandiri-list.php');
      exit;
    }

    delete_file_if_exists((string)($row['poster_path'] ?? ''));
    delete_file_if_exists((string)($row['fulltext_path'] ?? ''));

    if (is_ajax()) json_out(true, 'Berhasil dihapus.');
    flash('success', 'Berhasil dihapus.');
    header('Location: unggah-mandiri-list.php');
    exit;
  }

  if ($action === 'delete_selected') {
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids) || count($ids) === 0) {
      if (is_ajax()) json_out(false, 'Tidak ada data yang dipilih.');
      flash('warning', 'Tidak ada data yang dipilih.');
      header('Location: unggah-mandiri-list.php');
      exit;
    }

    // sanitasi -> int
    $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
    if (count($ids) === 0) {
      if (is_ajax()) json_out(false, 'ID tidak valid.');
      flash('danger', 'ID tidak valid.');
      header('Location: unggah-mandiri-list.php');
      exit;
    }

    // ambil file dulu
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $stmt = $conn->prepare("SELECT id, poster_path, fulltext_path FROM unggah_mandiri WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();
    $files = [];
    if ($res) while ($r = $res->fetch_assoc()) $files[] = $r;
    $stmt->close();

    // delete rows
    $stmt = $conn->prepare("DELETE FROM unggah_mandiri WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $affected = $stmt->affected_rows;
    $stmt->close();

    if (!$ok) {
      if (is_ajax()) json_out(false, 'Gagal hapus terpilih: ' . $err);
      flash('danger', 'Gagal hapus terpilih: ' . $err);
      header('Location: unggah-mandiri-list.php');
      exit;
    }

    // delete files
    foreach ($files as $f) {
      delete_file_if_exists((string)($f['poster_path'] ?? ''));
      delete_file_if_exists((string)($f['fulltext_path'] ?? ''));
    }

    if (is_ajax()) json_out(true, "Berhasil hapus {$affected} data.");
    flash('success', "Berhasil hapus {$affected} data.");
    header('Location: unggah-mandiri-list.php');
    exit;
  }

  if (is_ajax()) json_out(false, 'Aksi tidak dikenal.');
  flash('warning', 'Aksi tidak dikenal.');
  header('Location: unggah-mandiri-list.php');
  exit;

} catch (Throwable $e) {
  if (is_ajax()) json_out(false, 'Error: ' . $e->getMessage());
  flash('danger', 'Error: ' . $e->getMessage());
  header('Location: unggah-mandiri-list.php');
  exit;
}
