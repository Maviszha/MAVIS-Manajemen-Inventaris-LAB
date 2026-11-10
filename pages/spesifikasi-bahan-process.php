<?php
ob_start();
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: spesifikasi-bahan-list.php");
    exit;
}

$action = $_POST['action'] ?? '';
$username = $_SESSION['username'] ?? 'unknown';

function backFail(string $msg): void {
    header("Location: spesifikasi-bahan-list.php?fail=" . urlencode($msg));
    exit;
}

try {
    if ($action === 'add') {
        $nama = trim($_POST['nama_spesifikasi'] ?? '');
        if ($nama === '') backFail('Nama spesifikasi wajib diisi.');

        // Cek duplikat
        $cek = $conn->prepare("SELECT id FROM spesifikasi_bahan WHERE nama_spesifikasi = ? LIMIT 1");
        $cek->bind_param("s", $nama);
        $cek->execute();
        $dup = $cek->get_result()->fetch_assoc();
        $cek->close();
        if ($dup) backFail('Nama spesifikasi sudah ada.');

        $st = $conn->prepare("INSERT INTO spesifikasi_bahan (nama_spesifikasi) VALUES (?)");
        $st->bind_param("s", $nama);
        $st->execute();
        $newId = $st->insert_id;
        $st->close();

        logActivity(
            'spesifikasi_bahan',
            'create',
            'Tambah spesifikasi bahan',
            $username,
            null,
            json_encode(['id' => $newId, 'nama_spesifikasi' => $nama])
        );

        header("Location: spesifikasi-bahan-list.php?msg=added");
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_spesifikasi'] ?? '');

        if ($id <= 0) backFail('ID tidak valid.');
        if ($nama === '') backFail('Nama spesifikasi wajib diisi.');

        // before
        $get = $conn->prepare("SELECT id, nama_spesifikasi FROM spesifikasi_bahan WHERE id = ? LIMIT 1");
        $get->bind_param("i", $id);
        $get->execute();
        $before = $get->get_result()->fetch_assoc();
        $get->close();
        if (!$before) {
            header("Location: spesifikasi-bahan-list.php?msg=notfound");
            exit;
        }

        // cek duplikat selain id ini
        $cek = $conn->prepare("SELECT id FROM spesifikasi_bahan WHERE nama_spesifikasi = ? AND id <> ? LIMIT 1");
        $cek->bind_param("si", $nama, $id);
        $cek->execute();
        $dup = $cek->get_result()->fetch_assoc();
        $cek->close();
        if ($dup) backFail('Nama spesifikasi sudah dipakai spesifikasi lain.');

        $st = $conn->prepare("UPDATE spesifikasi_bahan SET nama_spesifikasi = ? WHERE id = ?");
        $st->bind_param("si", $nama, $id);
        $st->execute();
        $st->close();

        logActivity(
            'spesifikasi_bahan',
            'update',
            'Update spesifikasi bahan',
            $username,
            json_encode($before),
            json_encode(['id' => $id, 'nama_spesifikasi' => $nama])
        );

        header("Location: spesifikasi-bahan-list.php?msg=updated");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) backFail('ID tidak valid.');

        $get = $conn->prepare("SELECT id, nama_spesifikasi FROM spesifikasi_bahan WHERE id = ? LIMIT 1");
        $get->bind_param("i", $id);
        $get->execute();
        $before = $get->get_result()->fetch_assoc();
        $get->close();

        if (!$before) {
            header("Location: spesifikasi-bahan-list.php?msg=notfound");
            exit;
        }

        $del = $conn->prepare("DELETE FROM spesifikasi_bahan WHERE id = ?");
        $del->bind_param("i", $id);
        $del->execute();
        $del->close();

        logActivity(
            'spesifikasi_bahan',
            'delete',
            'Hapus spesifikasi bahan',
            $username,
            json_encode($before),
            null
        );

        header("Location: spesifikasi-bahan-list.php?msg=deleted");
        exit;
    }

    backFail('Aksi tidak dikenali.');

} catch (mysqli_sql_exception $e) {
    backFail("Database error: " . $e->getMessage());
}
