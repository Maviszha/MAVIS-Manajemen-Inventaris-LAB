<?php
// pages/import-alat-process.php
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: import-alat.php");
    exit;
}

function fail($msg) {
    header("Location: import-alat.php?fail=" . urlencode($msg));
    exit;
}

function get_or_create_lokasi(mysqli $conn, string $nama): int {
    $nama = trim($nama);
    if ($nama === '') return 0;

    $stmt = $conn->prepare("SELECT id FROM lokasi_alat WHERE nama_lokasi = ? LIMIT 1");
    if (!$stmt) throw new RuntimeException("Prepare lokasi select gagal: " . $conn->error);
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $stmt->close();
        return (int)$row['id'];
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO lokasi_alat (nama_lokasi) VALUES (?)");
    if (!$stmt) throw new RuntimeException("Prepare lokasi insert gagal: " . $conn->error);
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $id = (int)$stmt->insert_id;
    $stmt->close();
    return $id;
}

function get_or_create_sumber(mysqli $conn, string $nama): int {
    $nama = trim($nama);
    if ($nama === '') return 0;

    $stmt = $conn->prepare("SELECT id FROM sumber_pengadaan WHERE nama_sumber = ? LIMIT 1");
    if (!$stmt) throw new RuntimeException("Prepare sumber select gagal: " . $conn->error);
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $stmt->close();
        return (int)$row['id'];
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO sumber_pengadaan (nama_sumber) VALUES (?)");
    if (!$stmt) throw new RuntimeException("Prepare sumber insert gagal: " . $conn->error);
    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $id = (int)$stmt->insert_id;
    $stmt->close();
    return $id;
}

if (empty($_FILES['csv']['tmp_name'])) {
    fail("File CSV tidak ditemukan.");
}

$hasHeader = isset($_POST['has_header']);
$fh = fopen($_FILES['csv']['tmp_name'], 'r');
if (!$fh) fail("Gagal membaca file CSV.");

$conn->begin_transaction();

try {
    $rowNum = 0;
    $ok = 0;
    $skip = 0;

    while (($row = fgetcsv($fh)) !== false) {
        $rowNum++;
        if ($rowNum === 1 && $hasHeader) continue;

        [
            $no_inventaris,
            $nama_alat,
            $merk_alat,
            $spesifikasi,
            $lokasi_nama,
            $tahun_pengadaan,
            $sumber_nama,
            $jumlah_total,
            $jumlah_baik,
            $jumlah_rb,
            $jumlah_rr,
            $jumlah_hilang,
            $keterangan
        ] = array_pad($row, 13, '');

        $no_inventaris   = trim((string)$no_inventaris);
        $nama_alat       = trim((string)$nama_alat);
        $merk_alat       = trim((string)$merk_alat);
        $spesifikasi     = trim((string)$spesifikasi);
        $lokasi_nama     = trim((string)$lokasi_nama);
        $tahun_pengadaan = trim((string)$tahun_pengadaan);
        $sumber_nama     = trim((string)$sumber_nama);
        $keterangan      = trim((string)$keterangan);

        if ($nama_alat === '') { $skip++; continue; }
        if ($no_inventaris === '') { $skip++; continue; } // patokan utama

        $lokasi_id = get_or_create_lokasi($conn, $lokasi_nama);
        $sumber_id = get_or_create_sumber($conn, $sumber_nama);

        $jumlah_baik   = max(0, (int)$jumlah_baik);
        $jumlah_rb     = max(0, (int)$jumlah_rb);
        $jumlah_rr     = max(0, (int)$jumlah_rr);
        $jumlah_hilang = max(0, (int)$jumlah_hilang);
        $jumlah_total  = max(0, (int)$jumlah_total);

        if ($jumlah_total <= 0) {
            $jumlah_total = $jumlah_baik + $jumlah_rb + $jumlah_rr + $jumlah_hilang;
        }

        // cek berdasarkan no_inventaris
        $cek = $conn->prepare("SELECT id FROM alat_laboratorium WHERE no_inventaris = ? LIMIT 1");
        if (!$cek) throw new RuntimeException("Prepare cek alat gagal: " . $conn->error);
        $cek->bind_param("s", $no_inventaris);
        $cek->execute();
        $res = $cek->get_result();

        if ($res && $res->num_rows > 0) {
            // UPDATE (13 parameter)
            $upd = $conn->prepare("
                UPDATE alat_laboratorium SET
                    nama_alat=?,
                    merk_alat=?,
                    spesifikasi=?,
                    lokasi_id=?,
                    tahun_pengadaan=?,
                    sumber_pengadaan_id=?,
                    jumlah_total=?,
                    jumlah_baik=?,
                    jumlah_rusak_berat=?,
                    jumlah_rusak_ringan=?,
                    jumlah_hilang=?,
                    keterangan=?
                WHERE no_inventaris=?
            ");
            if (!$upd) throw new RuntimeException("Prepare update gagal: " . $conn->error);

            // FIX: TANPA SPASI & JUMLAH TYPE HARUS PAS (13)
            $upd->bind_param(
                "sssisiiiiiiss",
                $nama_alat,
                $merk_alat,
                $spesifikasi,
                $lokasi_id,
                $tahun_pengadaan,
                $sumber_id,
                $jumlah_total,
                $jumlah_baik,
                $jumlah_rb,
                $jumlah_rr,
                $jumlah_hilang,
                $keterangan,
                $no_inventaris
            );

            if (!$upd->execute()) {
                throw new RuntimeException("Gagal update no_inventaris {$no_inventaris}: " . $upd->error);
            }
            $upd->close();
            $ok++;
        } else {
            // INSERT (13 parameter)
            $ins = $conn->prepare("
                INSERT INTO alat_laboratorium
                (
                    no_inventaris,
                    nama_alat,
                    merk_alat,
                    spesifikasi,
                    lokasi_id,
                    tahun_pengadaan,
                    sumber_pengadaan_id,
                    jumlah_total,
                    jumlah_baik,
                    jumlah_rusak_berat,
                    jumlah_rusak_ringan,
                    jumlah_hilang,
                    keterangan
                )
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            if (!$ins) throw new RuntimeException("Prepare insert gagal: " . $conn->error);

            // FIX: TANPA SPASI & JUMLAH TYPE HARUS PAS (13)
            $ins->bind_param(
                "ssssisiiiiiis",
                $no_inventaris,
                $nama_alat,
                $merk_alat,
                $spesifikasi,
                $lokasi_id,
                $tahun_pengadaan,
                $sumber_id,
                $jumlah_total,
                $jumlah_baik,
                $jumlah_rb,
                $jumlah_rr,
                $jumlah_hilang,
                $keterangan
            );

            if (!$ins->execute()) {
                throw new RuntimeException("Gagal insert no_inventaris {$no_inventaris}: " . $ins->error);
            }
            $ins->close();
            $ok++;
        }

        $cek->close();
    }

    fclose($fh);
    $conn->commit();

    if (function_exists('logActivity')) {
        logActivity('alat', 'import_csv', "Import CSV alat: berhasil={$ok}, dilewati={$skip}");
    }

    header("Location: import-alat.php?success=1");
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    if (is_resource($fh)) fclose($fh);
    fail("Error: " . $e->getMessage());
}
