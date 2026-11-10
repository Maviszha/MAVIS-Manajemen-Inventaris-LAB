<?php
ob_start();
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: satuan-bahan-list.php");
    exit;
}

$action = $_POST['action'] ?? '';
$username = $_SESSION['username'] ?? 'unknown';

function backFail(string $msg): void {
    header("Location: satuan-bahan-list.php?fail=" . urlencode($msg));
    exit;
}

try {
    if ($action === 'add') {
        $nama = trim($_POST['nama_satuan'] ?? '');
        $aktif = isset($_POST['aktif']) ? (int)$_POST['aktif'] : 1;

        if ($nama === '') backFail('Nama satuan wajib diisi.');
        if (strlen($nama) > 30) backFail('Nama satuan maksimal 30 karakter.');
        $aktif = ($aktif === 1) ? 1 : 0;

        // duplikat (case-insensitive sederhana)
        $cek = $conn->prepare("SELECT id FROM satuan_bahan WHERE nama_satuan = ? LIMIT 1");
        $cek->bind_param("s", $nama);
        $cek->execute();
        $dup = $cek->get_result()->fetch_assoc();
        $cek->close();
        if ($dup) backFail('Nama satuan sudah ada.');

        $st = $conn->prepare("INSERT INTO satuan_bahan (nama_satuan, aktif) VALUES (?, ?)");
        $st->bind_param("si", $nama, $aktif);
        $st->execute();
        $newId = $st->insert_id;
        $st->close();

        logActivity(
            'satuan_bahan',
            'create',
            'Tambah satuan bahan',
            $username,
            null,
            json_encode(['id' => $newId, 'nama_satuan' => $nama, 'aktif' => $aktif])
        );

        header("Location: satuan-bahan-list.php?msg=added");
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_satuan'] ?? '');
        $aktif = isset($_POST['aktif']) ? (int)$_POST['aktif'] : 1;

        if ($id <= 0) backFail('ID tidak valid.');
        if ($nama === '') backFail('Nama satuan wajib diisi.');
        if (strlen($nama) > 30) backFail('Nama satuan maksimal 30 karakter.');
        $aktif = ($aktif === 1) ? 1 : 0;

        $get = $conn->prepare("SELECT id, nama_satuan, aktif FROM satuan_bahan WHERE id = ? LIMIT 1");
        $get->bind_param("i", $id);
        $get->execute();
        $before = $get->get_result()->fetch_assoc();
        $get->close();
        if (!$before) {
            header("Location: satuan-bahan-list.php?msg=notfound");
            exit;
        }

        // cek duplikat selain id ini
        $cek = $conn->prepare("SELECT id FROM satuan_bahan WHERE nama_satuan = ? AND id <> ? LIMIT 1");
        $cek->bind_param("si", $nama, $id);
        $cek->execute();
        $dup = $cek->get_result()->fetch_assoc();
        $cek->close();
        if ($dup) backFail('Nama satuan sudah dipakai satuan lain.');

        $st = $conn->prepare("UPDATE satuan_bahan SET nama_satuan = ?, aktif = ? WHERE id = ?");
        $st->bind_param("sii", $nama, $aktif, $id);
        $st->execute();
        $st->close();

        logActivity(
            'satuan_bahan',
            'update',
            'Update satuan bahan',
            $username,
            json_encode($before),
            json_encode(['id' => $id, 'nama_satuan' => $nama, 'aktif' => $aktif])
        );

        header("Location: satuan-bahan-list.php?msg=updated");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) backFail('ID tidak valid.');

        $get = $conn->prepare("SELECT id, nama_satuan, aktif FROM satuan_bahan WHERE id = ? LIMIT 1");
        $get->bind_param("i", $id);
        $get->execute();
        $before = $get->get_result()->fetch_assoc();
        $get->close();

        if (!$before) {
            header("Location: satuan-bahan-list.php?msg=notfound");
            exit;
        }

        $del = $conn->prepare("DELETE FROM satuan_bahan WHERE id = ?");
        $del->bind_param("i", $id);
        $del->execute();
        $del->close();

        logActivity(
            'satuan_bahan',
            'delete',
            'Hapus satuan bahan',
            $username,
            json_encode($before),
            null
        );

        header("Location: satuan-bahan-list.php?msg=deleted");
        exit;
    }

    backFail('Aksi tidak dikenali.');

} catch (mysqli_sql_exception $e) {
    backFail("Database error: " . $e->getMessage());
}
