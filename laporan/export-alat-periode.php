<?php
require_once "../config/db.php";
require_once "../config/auth.php";

$start = $_GET['start_date'] ?? '';
$end   = $_GET['end_date'] ?? '';

if ($start === '' || $end === '') {
    die("Parameter tanggal tidak lengkap.");
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
    die("Format tanggal tidak valid (gunakan YYYY-MM-DD).");
}

// Query data alat dalam rentang tahun_pengadaan
$sql = "
    SELECT 
        a.id,
        a.nama_alat,
        a.merk_alat,
        a.spesifikasi,
        a.tahun_pengadaan,
        l.nama_lokasi,
        s.nama_sumber,
        a.jumlah_total,
        a.jumlah_baik,
        a.jumlah_rusak_ringan,
        a.jumlah_rusak_berat,
        a.jumlah_hilang,
        a.keterangan
    FROM alat_laboratorium a
    LEFT JOIN lokasi_alat l ON a.lokasi_id = l.id
    LEFT JOIN sumber_pengadaan s ON a.sumber_pengadaan_id = s.id
    WHERE a.tahun_pengadaan BETWEEN ? AND ?
    ORDER BY a.tahun_pengadaan ASC, a.nama_alat ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Query error: " . $conn->error);
}
$stmt->bind_param("ss", $start, $end);
$stmt->execute();
$result = $stmt->get_result();

// Set header untuk CSV
$filename = "export_alat_{$start}_sampai_{$end}.csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Buka output
$output = fopen('php://output', 'w');

// Header kolom CSV
fputcsv($output, [
    'No',
    'Nama Alat',
    'Merk',
    'Spesifikasi',
    'Tahun Pengadaan',
    'Lokasi',
    'Sumber Pengadaan',
    'Jumlah Total',
    'Jumlah Baik',
    'Jumlah Rusak Ringan',
    'Jumlah Rusak Berat',
    'Jumlah Hilang',
    'Keterangan'
]);

$no = 1;
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $no++,
        $row['nama_alat'],
        $row['merk_alat'],
        $row['spesifikasi'],
        $row['tahun_pengadaan'],
        $row['nama_lokasi'],
        $row['nama_sumber'],
        $row['jumlah_total'],
        $row['jumlah_baik'],
        $row['jumlah_rusak_ringan'],
        $row['jumlah_rusak_berat'],
        $row['jumlah_hilang'],
        $row['keterangan'],
    ]);
}

fclose($output);
exit;
