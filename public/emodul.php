<?php
// public/emodul.php  — Halaman publik E-Modul (tanpa login)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Ambil semua e-modul
$res  = $conn->query("SELECT e.*, k.nama AS kelas_nama FROM e_modul e LEFT JOIN kelas k ON k.id = e.kelas_id ORDER BY e.tanggal DESC, e.id DESC");
$rows = [];
if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;

$bgVideoUrl = "/mavis/templates/vid/papan.mp4";
$bgPoster   = "/mavis/templates/img/bg1.jpg";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>E-Modul Praktikum — MAVIS</title>
  <link href="/mavis/assets/sbadmin2/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    html, body { height: 100%; margin: 0; }
    body {
      font-family: "Nunito", system-ui, -apple-system, sans-serif;
      color: #fff;
      overflow-x: hidden;
      min-height: 100%;
    }
    .bg-video {
      position: fixed; inset: 0;
      width: 100%; height: 100%;
      object-fit: cover; z-index: 0;
    }
    .bg-overlay {
      position: fixed; inset: 0; z-index: 0;
      pointer-events: none;
      background: radial-gradient(ellipse at center,
        rgba(0,0,0,.28) 0%, rgba(0,0,0,.5) 70%, rgba(0,0,0,.65) 100%);
    }
    .page {
      position: relative; z-index: 1;
      min-height: 100%;
      padding: 28px 16px 50px;
      display: flex; flex-direction: column; align-items: center;
    }
    .glass-card {
      width: min(1100px, 100%);
      border-radius: 18px;
      border: 1px solid rgba(255,255,255,.28);
      background: rgba(255,255,255,.15);
      backdrop-filter: blur(18px) saturate(140%);
      padding: 28px 32px;
      box-shadow: 0 8px 32px rgba(0,0,0,.35);
      animation: fadeUp .5s ease both;
    }
    @keyframes fadeUp {
      from { opacity:0; transform:translateY(20px); }
      to   { opacity:1; transform:translateY(0); }
    }
    .page-title {
      font-size: 26px;
      font-weight: 800;
      text-align: center;
      margin-bottom: 6px;
      letter-spacing: .5px;
    }
    .page-subtitle {
      text-align: center;
      font-size: 13px;
      opacity: .75;
      margin-bottom: 24px;
    }
    /* TABEL */
    .table-wrap { overflow-x: auto; }
    table {
      width: 100%;
      border-collapse: collapse;
      background: rgba(255,255,255,.07);
      border-radius: 12px;
      overflow: hidden;
    }
    thead tr { background: rgba(255,255,255,.22); }
    th, td {
      padding: 10px 14px;
      text-align: left;
      font-size: 13.5px;
      border-bottom: 1px solid rgba(255,255,255,.12);
    }
    th { font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; }
    tbody tr:hover { background: rgba(255,255,255,.1); }
    tbody tr:last-child td { border-bottom: none; }

    /* GAMBAR thumbnail */
    .cover-img {
      width: 64px; height: 48px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid rgba(255,255,255,.3);
    }
    .no-img {
      width: 64px; height: 48px;
      background: rgba(255,255,255,.12);
      border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px; opacity: .4;
    }

    /* TOMBOL */
    .btn {
      display: inline-block;
      padding: 5px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 700;
      text-decoration: none;
      border: none; cursor: pointer;
      transition: opacity .2s;
    }
    .btn:hover { opacity: .85; }
    .btn-upload { background: rgba(255,255,255,.85); color: #333; }

    /* Empty state */
    .empty { text-align: center; padding: 50px 0; opacity: .55; }
    .empty i { font-size: 40px; margin-bottom: 12px; display: block; }

    /* Back to nav */
    .nav-bar {
      width: min(1100px,100%);
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }
    .nav-bar a {
      color: rgba(255,255,255,.8);
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
    }
    .nav-bar a:hover { color: #fff; }

    @media(max-width:600px){
      .glass-card { padding: 16px; }
      .page-title { font-size: 20px; }
    }
  </style>
</head>
<body>

<video class="bg-video" autoplay muted loop playsinline poster="<?= h($bgPoster) ?>">
  <source src="<?= h($bgVideoUrl) ?>" type="video/mp4">
</video>
<div class="bg-overlay"></div>

<div class="page">
  <div class="nav-bar">
    <a href="/mavis/login.php"><i class="fas fa-sign-in-alt mr-1"></i> Login Admin</a>
    <a href="/mavis/public/unggah-mandiri.php"><i class="fas fa-upload mr-1"></i> Unggah Mandiri</a>
  </div>

  <div class="glass-card">
    <div class="page-title">
      <i class="fas fa-book-open mr-2"></i> E-Modul Praktikum
    </div>
    <div class="page-subtitle">SMA Taruna Nusantara — Laboratorium Kimia</div>

    <?php if (!$rows): ?>
      <div class="empty">
        <i class="fas fa-folder-open"></i>
        Belum ada e-modul yang diunggah.
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Cover</th>
              <th>Judul E-Modul</th>
              <th>Penulis</th>
              <th>Kelas</th>
              <th>Tanggal</th>
              <th class="text-center">Kerjakan</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $i => $row): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td>
                  <?php if ($row['gambar_path']): ?>
                    <img src="/mavis/<?= h($row['gambar_path']) ?>"
                         alt="Cover" class="cover-img">
                  <?php else: ?>
                    <div class="no-img"><i class="fas fa-image"></i></div>
                  <?php endif; ?>
                </td>
                <td><strong><?= h($row['judul']) ?></strong></td>
                <td><?= h($row['penulis']) ?></td>
                <td><?= h($row['kelas_nama'] ?? '-') ?></td>
                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                <td class="text-center">
                  <a href="/mavis/public/emodul-detail.php?id=<?= (int)$row['id'] ?>"
                     class="btn btn-upload">
                    <i class="fas fa-play-circle mr-1"></i> Kerjakan
                  </a>
                </td>
                <td style="font-size:13px;opacity:.85;"><?= nl2br(h($row['keterangan'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
