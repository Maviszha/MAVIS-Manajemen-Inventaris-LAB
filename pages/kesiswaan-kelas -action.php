<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  http_response_code(403);
  die("Akses ditolak.");
}

function flash($type, $message) {
  $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

$action = $_POST['action'] ?? '';

try {
  if ($action === 'create') {
    $nama  = trim($_POST['nama'] ?? '');
    $aktif = (int)($_POST['aktif'] ?? 1);

    if ($nama === '') {
      flash('warning', 'Nama kelas wajib diisi.');
      header("Location: kesiswaan-kelas.php");
      exit;
    }

    $stmt = $conn->prepare("INSERT INTO kelas (nama, aktif) VALUES (?, ?)");
    $stmt->bind_param("si", $nama, $aktif);
    $stmt->execute();

    // Optional: log jika kamu punya fungsi log
    if (function_exists('add_log')) { add_log('KESISWAAN_KELAS_CREATE', "Tambah kelas: $nama"); }

    flash('success', 'Kelas berhasil ditambahkan.');
    header("Location: kesiswaan_kelas.php");
    exit;
  }

  if ($action === 'update') {
    $id    = (int)($_POST['id'] ?? 0);
    $nama  = trim($_POST['nama'] ?? '');
    $aktif = (int)($_POST['aktif'] ?? 1);

    if ($id <= 0 || $nama === '') {
      flash('warning', 'Data tidak valid.');
      header("Location: kesiswaan_kelas.php");
      exit;
    }

    $stmt = $conn->prepare("UPDATE kelas SET nama=?, aktif=? WHERE id=?");
    $stmt->bind_param("sii", $nama, $aktif, $id);
    $stmt->execute();

    if (function_exists('add_log')) { add_log('KESISWAAN_KELAS_UPDATE', "Update kelas #$id => $nama"); }

    flash('success', 'Kelas berhasil diupdate.');
    header("Location: kesiswaan_kelas.php");
    exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      flash('warning', 'ID tidak valid.');
      header("Location: kesiswaan_kelas.php");
      exit;
    }

    // Ambil nama untuk pesan/log
    $nama = '';
    $stmt0 = $conn->prepare("SELECT nama FROM kelas WHERE id=?");
    $stmt0->bind_param("i", $id);
    $stmt0->execute();
    $stmt0->bind_result($nama);
    $stmt0->fetch();
    $stmt0->close();

    $stmt = $conn->prepare("DELETE FROM kelas WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if (function_exists('add_log')) { add_log('KESISWAAN_KELAS_DELETE', "Hapus kelas #$id ($nama)"); }

    flash('success', 'Kelas berhasil dihapus.');
    header("Location: kesiswaan-kelas.php");
    exit;
  }

  flash('warning', 'Aksi tidak dikenali.');
  header("Location: kesiswaan-kelas.php");
  exit;

} catch (mysqli_sql_exception $e) {
  // FK restrict / duplicate nama, dll
  $msg = $e->getMessage();

  if (stripos($msg, 'Duplicate') !== false) {
    flash('danger', 'Nama kelas sudah ada (duplikat).');
  } elseif (stripos($msg, 'a foreign key constraint fails') !== false) {
    flash('danger', 'Kelas tidak bisa dihapus karena sudah dipakai oleh data siswa.');
  } else {
    flash('danger', 'Terjadi error: ' . $msg);
  }

  header("Location: kesiswaan_kelas.php");
  exit;
}
