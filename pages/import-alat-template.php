<?php
// pages/import-alat-template.php
include dirname(__DIR__) . "/config/db.php";
include dirname(__DIR__) . "/config/auth.php";
requireAdmin();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="template_import_alat.csv"');

$out = fopen('php://output', 'w');

fputcsv($out, [
  'no_inventaris','nama_alat','merk_alat','spesifikasi','lokasi',
  'tahun_pengadaan','sumber_pengadaan',
  'jumlah_total','jumlah_baik','jumlah_rusak_berat','jumlah_rusak_ringan','jumlah_hilang',
  'keterangan'
]);

fputcsv($out, [
  'INV-001','Mikroskop','Olympus','Pembesaran 1000x','Lemari 1',
  '2024-01-10','BOS',
  '5','4','0','1','0',
  'Unit cadangan'
]);

fputcsv($out, [
  'INV-002','Gelas Ukur 100ml','Pyrex','Kaca tahan panas','Rak A',
  '2023-08-05','Komite',
  '10','10','0','0','0',
  ''
]);

fclose($out);
exit;
