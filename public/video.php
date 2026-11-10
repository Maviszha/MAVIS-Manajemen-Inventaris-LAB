<?php
// public/video.php — Relay URL video dari link di dalam PDF
// Penggunaan: /mavis/public/video.php?v=YOUTUBE_ID atau ?url=FULL_URL
if (session_status() === PHP_SESSION_NONE) session_start();

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function getEmbedUrl(): ?string {
    // Mode 1: ?v=VIDEO_ID (YouTube ID langsung)
    if (!empty($_GET['v'])) {
        $id = preg_replace('/[^A-Za-z0-9_\-]/', '', $_GET['v']);
        return 'https://www.youtube.com/embed/' . $id . '?autoplay=1&rel=0';
    }

    // Mode 2: ?url=FULL_URL (URL lengkap YouTube/Vimeo)
    if (!empty($_GET['url'])) {
        $url = trim($_GET['url']);
        // YouTube
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_\-]{11})/', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1] . '?autoplay=1&rel=0';
        }
        // Vimeo
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1';
        }
    }

    return null;
}

function getTitle(): string {
    return trim($_GET['title'] ?? 'Video Demonstrasi');
}

$embedUrl = getEmbedUrl();
$title    = getTitle();

// Jika tidak ada parameter valid, redirect ke halaman utama emodul
if (!$embedUrl) {
    header("Location: emodul.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($title) ?> — MAVIS</title>
  <link href="/mavis/assets/sbadmin2/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    html, body {
      margin: 0; padding: 0; height: 100%;
      background: #0a0a0a;
      font-family: "Nunito", system-ui, sans-serif;
      color: #fff;
    }

    .page {
      min-height: 100vh;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      padding: 20px;
      background: radial-gradient(ellipse at center, #1a1a2e 0%, #0a0a0a 100%);
    }

    .video-container {
      width: min(900px, 100%);
      animation: fadeUp .4s ease both;
    }
    @keyframes fadeUp {
      from { opacity:0; transform:translateY(20px); }
      to   { opacity:1; transform:translateY(0); }
    }

    .video-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 12px; gap: 12px;
    }
    .video-title {
      font-size: 16px; font-weight: 800;
      opacity: .9;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .btn-close {
      background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.2);
      color: #fff; border-radius: 8px;
      padding: 7px 16px; font-size: 13px; font-weight: 700;
      cursor: pointer; flex-shrink: 0;
      text-decoration: none;
      display: inline-flex; align-items: center; gap: 6px;
      transition: background .2s;
    }
    .btn-close:hover { background: rgba(255,255,255,.2); color: #fff; }

    .video-wrap {
      position: relative;
      width: 100%;
      padding-top: 56.25%; /* 16:9 */
      background: #000;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0,0,0,.7);
    }
    .video-wrap iframe {
      position: absolute;
      inset: 0; width: 100%; height: 100%;
      border: none;
    }

    .video-footer {
      margin-top: 14px;
      text-align: center;
      font-size: 12px;
      opacity: .45;
    }
  </style>
</head>
<body>
<div class="page">
  <div class="video-container">
    <div class="video-header">
      <div class="video-title">
        <i class="fas fa-play-circle mr-2" style="color:#ff0000;"></i>
        <?= h($title) ?>
      </div>
      <a href="javascript:window.close()" class="btn-close">
        <i class="fas fa-times"></i> Tutup
      </a>
    </div>

    <div class="video-wrap">
      <iframe src="<?= h($embedUrl) ?>"
              allowfullscreen
              allow="autoplay; encrypted-media; picture-in-picture"
              title="<?= h($title) ?>">
      </iframe>
    </div>

    <div class="video-footer">
      Halaman ini otomatis memutar video. Tutup tab ini setelah selesai menonton.
    </div>
  </div>
</div>
</body>
</html>
