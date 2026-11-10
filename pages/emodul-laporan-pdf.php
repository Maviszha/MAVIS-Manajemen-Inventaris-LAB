<?php
// pages/emodul-laporan-pdf.php
// Menampilkan laporan praktikum kelompok sebagai halaman HTML yang bisa di-print / dilihat di iframe
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$role = strtolower((string)($_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '')));
if ($role !== 'admin') {
    http_response_code(403);
    echo '<h3>Akses ditolak.</h3>';
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo '<h3>ID tidak valid.</h3>'; exit; }

// Ambil data laporan
$stmt = $conn->prepare(
    "SELECT k.*, e.judul AS judul_emodul
     FROM laporan_kelompok k
     JOIN e_modul e ON e.id = k.emodul_id
     WHERE k.id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$laporan = $stmt->get_result()->fetch_assoc();

if (!$laporan) { echo '<h3>Laporan tidak ditemukan.</h3>'; exit; }

// Ambil anggota
$sAngg = $conn->prepare(
    "SELECT nis, nama, kelas FROM laporan_kelompok_anggota
     WHERE kelompok_id = ? ORDER BY urutan ASC"
);
$sAngg->bind_param('i', $id);
$sAngg->execute();
$anggota = $sAngg->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Praktikum - <?= h($laporan['nama_kelompok']) ?></title>
  <style>
    @page { margin: 2cm; }
    * { box-sizing: border-box; }
    body {
      font-family: "Times New Roman", Times, serif;
      font-size: 12pt;
      color: #111;
      background: #fff;
      margin: 0;
      padding: 20px 40px;
    }
    .kop {
      text-align: center;
      border-bottom: 3px double #000;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }
    .kop h2 { margin:0; font-size: 16pt; text-transform: uppercase; }
    .kop p  { margin:4px 0; font-size: 11pt; }
    .judul-laporan {
      text-align: center;
      font-size: 14pt;
      font-weight: bold;
      margin: 16px 0 8px;
      text-transform: uppercase;
    }
    table.info {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 16px;
    }
    table.info td {
      padding: 4px 8px;
      font-size: 12pt;
    }
    table.info td:first-child { width: 160px; font-weight: bold; }
    table.info td:nth-child(2) { width: 10px; }

    table.anggota {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    table.anggota th, table.anggota td {
      border: 1px solid #333;
      padding: 6px 10px;
      font-size: 11pt;
    }
    table.anggota th { background: #e8e8e8; text-align: center; }
    table.anggota td:first-child { text-align: center; }

    .section-title {
      font-weight: bold;
      font-size: 12pt;
      border-bottom: 1px solid #555;
      padding-bottom: 4px;
      margin: 20px 0 10px;
      text-transform: uppercase;
    }
    .hasil-content {
      text-align: justify;
      line-height: 1.8;
      white-space: pre-wrap;
      font-size: 12pt;
      border: 1px solid #ccc;
      padding: 14px;
      background: #fafafa;
      min-height: 200px;
    }
    .ttd-area {
      margin-top: 40px;
      display: flex;
      justify-content: space-between;
    }
    .ttd-box { text-align: center; }
    .ttd-box .ttd-line {
      margin-top: 60px;
      border-top: 1px solid #000;
      width: 180px;
      margin-left: auto;
      margin-right: auto;
    }
    .print-btn {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: #4e73df;
      color: #fff;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 13px;
      box-shadow: 0 3px 8px rgba(0,0,0,.3);
    }
    @media print {
      .print-btn { display: none; }
      body { padding: 0; }
    }
  </style>
</head>
<body>

<div class="kop">
  <h2>SMA Taruna Nusantara</h2>
  <p>Laboratorium IPA — Kimia</p>
  <p>MAVIS — Manajemen Inventaris Laboratorium</p>
</div>

<div class="judul-laporan">Laporan Praktikum</div>

<table class="info">
  <tr>
    <td>E-Modul</td>
    <td>:</td>
    <td><?= h($laporan['judul_emodul']) ?></td>
  </tr>
  <tr>
    <td>Nama Kelompok</td>
    <td>:</td>
    <td><?= h($laporan['nama_kelompok']) ?></td>
  </tr>
  <tr>
    <td>Judul Praktikum</td>
    <td>:</td>
    <td><?= h($laporan['judul_praktikum']) ?></td>
  </tr>
  <tr>
    <td>Tanggal Pengumpulan</td>
    <td>:</td>
    <td><?= date('d F Y H:i', strtotime($laporan['created_at'])) ?></td>
  </tr>
</table>

<div class="section-title">Anggota Kelompok</div>
<table class="anggota">
  <thead>
    <tr>
      <th width="40">#</th>
      <th>NIS</th>
      <th>Nama Siswa</th>
      <th>Kelas</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($anggota as $i => $a): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= h($a['nis']) ?></td>
        <td><?= h($a['nama']) ?></td>
        <td><?= h($a['kelas']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php for ($x = count($anggota); $x < 6; $x++): ?>
      <tr>
        <td><?= $x + 1 ?></td>
        <td>-</td><td>-</td><td>-</td>
      </tr>
    <?php endfor; ?>
  </tbody>
</table>

<div class="section-title">Hasil Praktikum</div>
<div class="hasil-content"><?= h($laporan['hasil_praktikum']) ?></div>

<div class="ttd-area">
  <div class="ttd-box">
    <p>Mengetahui,<br>Guru / Pembimbing</p>
    <div class="ttd-line"></div>
    <p>(.......................................)</p>
  </div>
  <div class="ttd-box">
    <p>Dibuat oleh,<br>Ketua Kelompok</p>
    <div class="ttd-line"></div>
    <p>(<?= h($laporan['nama_kelompok']) ?>)</p>
  </div>
</div>

<button class="print-btn" onclick="window.print()">
  🖨️ Cetak / Simpan PDF
</button>

</body>
</html>
