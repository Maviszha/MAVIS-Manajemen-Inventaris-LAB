<?php
// pages/template-siswa-process.php
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="template_import_siswa.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['nis','nama','kelas']);
fputcsv($out, ['20240101','Lee Jaehee','XII-A1']);
fputcsv($out, ['20240102','Yuki Kashiwagi','XII-A2']);
fputcsv($out, ['20240103','Siti Kondilatin','X23']);
fclose($out);
exit;
