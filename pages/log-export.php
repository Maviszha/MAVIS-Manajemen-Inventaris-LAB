<?php
session_start();
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

$role = $_SESSION['role'] ?? '';

if (strtolower($role) !== 'admin') {
    die("Akses ditolak.");
}

$keyword = $_GET['keyword'] ?? '';

// Query sama seperti di log-system, tapi tanpa LIMIT
$sql = "SELECT l.*, u.full_name 
        FROM system_log l
        LEFT JOIN users u ON l.user_id = u.id
        WHERE 1=1";

if ($keyword !== '') {
    $kw = '%' . $conn->real_escape_string($keyword) . '%';
    $sql .= " AND (
                l.module LIKE '$kw'
             OR l.action LIKE '$kw'
             OR l.username LIKE '$kw'
             OR l.description LIKE '$kw'
             )";
}

$sql .= " ORDER BY l.log_time DESC";

$result = $conn->query($sql);
$rowCount = ($result) ? $result->num_rows : 0;

// Catat ke log bahwa admin melakukan export log
logActivity(
    'system_log',
    'export_csv',
    "Export system log ke CSV. Jumlah baris: {$rowCount}",
    null,
    ['rows' => $rowCount, 'keyword' => $keyword]
);

// Header CSV
$now = date('Ymd_His');
$filename = "system_log_{$now}.csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');

$output = fopen('php://output', 'w');

// Header kolom
fputcsv($output, [
    'waktu',
    'user',
    'module',
    'action',
    'deskripsi',
    'ip_address',
    'user_agent'
]);

// Isi data
if ($result && $rowCount > 0) {
    while ($row = $result->fetch_assoc()) {
        $user = $row['full_name'] ?: $row['username'] ?: '-';

        fputcsv($output, [
            $row['log_time'],
            $user,
            $row['module'],
            $row['action'],
            $row['description'],
            $row['ip_address'],
            $row['user_agent']
        ]);
    }
}

fclose($output);
exit;
