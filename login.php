<?php
session_start();
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/log.php";

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: /mavis/index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Username dan password wajib diisi.";
        logActivity('auth', 'login_failed', "Login gagal (kosong). Username: {$username}");
    } else {
        $stmt = $conn->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            logActivity('auth', 'login_success', "Login berhasil untuk user {$user['username']}");

            header("Location: /mavis/index.php");
            exit;
        } else {
            $error = "Username atau password salah.";
            logActivity('auth', 'login_failed', "Login gagal. Username: {$username}");
        }
    }
}

/*
 * ===========================
 *  PENGATURAN TAMPILAN LOGIN
 * ===========================
 */
$logoPath = "/mavis/templates/img/logo.png";
$appTitle = "MAVIS (MANAJEMEN DAN INVENTARIS)";
$subTitle = "LABORATORIUM KIMIA SMA TARUNA NUSANTARA";

// VIDEO BACKGROUND
$bgVideoFile = "papan.mp4";
$bgVideoUrl  = "/mavis/templates/vid/" . rawurlencode($bgVideoFile);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Login - MAVIS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- SB Admin 2 CSS -->
    <link href="/mavis/assets/sbadmin2/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="/mavis/assets/sbadmin2/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        html, body { height: 100%; }

        body {
            margin: 0;
            font-family: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow: hidden;
        }

        /* VIDEO BACKGROUND */
        .bg-video{
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }
        .bg-overlay{
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.35);
            z-index: 0;
            pointer-events: none;
        }

        /* WRAPPER CONTENT */
        .login-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Spotlight lembut */
        .login-wrapper::before{
            content:"";
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background: radial-gradient(circle at center,
                rgba(255,255,255,0.08) 0%,
                rgba(255,255,255,0.00) 55%);
        }
        .login-wrapper > *{ position: relative; z-index: 1; }

        /* CARD LOGIN (glass effect) */
        .login-card {
            max-width: 420px;
            width: 100%;
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(14px) saturate(140%);
            -webkit-backdrop-filter: blur(14px) saturate(140%);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow:
                0 20px 40px rgba(0,0,0,0.35),
                inset 0 1px 0 rgba(255,255,255,0.25);
            color: #ffffff;
            animation: popIn .5s ease-out;
        }
        @keyframes popIn{
            from{ transform: translateY(10px); opacity: 0; }
            to{ transform: translateY(0); opacity: 1; }
        }

        .login-card label,
        .login-card .login-title,
        .login-card .login-subtitle { color: #ffffff; }
        .login-card .login-subtitle { opacity: 0.85; }

        .login-logo-img { width: 80px; height: 80px; object-fit: contain; }
        .login-title { font-size: 1.05rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        .login-subtitle { font-size: 0.9rem; }

        /* INPUT STYLE GLASS */
        .login-card .form-control{
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.30);
            color: #ffffff;
        }
        .login-card .form-control::placeholder{ color: rgba(255,255,255,0.70); }
        .login-card .form-control:focus{
            background: rgba(255,255,255,0.22);
            border-color: rgba(255,255,255,0.70);
            box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.18);
            color: #ffffff;
        }
        .login-card .input-group-text{
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.30);
            color: rgba(255,255,255,0.85);
        }
        .login-card .form-check-label{ color: rgba(255,255,255,0.85) !important; }

        /* BUTTON KONTRAS */
        .login-card .btn-primary{
            background: rgba(0,123,255,0.88);
            border: none;
            box-shadow: 0 10px 22px rgba(0,0,0,0.22);
        }
        .login-card .btn-primary:hover{ background: rgba(0,123,255,1); }
        .login-card .btn-primary:focus{ box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.18); }

        @media (max-width: 576px) {
            .login-card { max-width: 100%; }
            .login-title { font-size: 0.95rem; }
        }

        /* =========================
           GLASS WIDGET BASE
           ========================= */
        .glass-widget{
            background: rgba(255,255,255,0.18);
            backdrop-filter: blur(14px) saturate(140%);
            -webkit-backdrop-filter: blur(14px) saturate(140%);
            border: 1px solid rgba(255,255,255,0.28);
            border-radius: 16px;
            box-shadow: 0 10px 26px rgba(0,0,0,0.30), inset 0 1px 0 rgba(255,255,255,0.25);
            color: #fff;
        }

        /* =========================
           WEATHER WIDGET (TOP LEFT)
           ========================= */
        .weather-widget{
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 3; /* pastikan selalu di atas card login */
            width: 270px;
            padding: 14px 14px 12px;
            font-size: 12px;
        }

        .ww-top{
            display:flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
        }
        .ww-location{ font-weight: 800; font-size: 13px; }
        .ww-sub{ opacity:.85; font-size: 11px; margin-top: 2px; }

        .ww-right{
            display:flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
        }

        /* tombol minimize/maximize */
        .ww-toggle{
            appearance: none;
            border: 1px solid rgba(255,255,255,0.25);
            background: rgba(0,0,0,0.10);
            color: rgba(255,255,255,0.92);
            border-radius: 10px;
            padding: 4px 8px;
            font-size: 12px;
            line-height: 1;
            cursor: pointer;
            user-select: none;
        }
        .ww-toggle:focus{ outline: none; box-shadow: 0 0 0 0.15rem rgba(255,255,255,0.15); }

        .ww-now{ display:flex; align-items:center; gap: 8px; }
        .ww-icon{
            font-size: 28px;
            display:inline-block;
            animation: wwFloat 3s ease-in-out infinite;
        }
        @keyframes wwFloat{
            0%,100%{ transform: translateY(0); }
            50%{ transform: translateY(-4px); }
        }
        .ww-temp{ font-weight: 900; font-size: 20px; letter-spacing: .2px; }

        .ww-desc{ margin-top: 6px; opacity:.9; }

        .ww-metrics{
            display:grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
        }
        .ww-metric{
            padding: 8px 8px;
            border-radius: 12px;
            background: rgba(0,0,0,0.12);
            border: 1px solid rgba(255,255,255,0.16);
        }
        .ww-metric span{ display:block; opacity:.8; font-size: 11px; }
        .ww-metric b{ display:block; margin-top: 2px; font-size: 12px; }

        /* SUNRISE/SUNSET + UV */
        .ww-sun{
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid rgba(255,255,255,0.22);
        }
        .ww-sun-split{
            display: grid;
            grid-template-columns: 1fr 110px;
            gap: 10px;
            align-items: center;
        }
        .ww-sun-left{
            display: grid;
            gap: 8px;
        }

        /* sunrise/sunset jadi "card" */
        .ww-sunrow{
            display:flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 10px;
            border-radius: 12px;
            background: rgba(0,0,0,0.12);
            border: 1px solid rgba(255,255,255,0.16);
            opacity: .95;
        }
        .ww-sunrow span{ opacity: .9; }
        .ww-sunrow b{ font-size: 12px; }

        /* UV box sekarang punya background card (seragam) */
        .ww-uvbox{
            height: 64px;
            border-radius: 12px;
            background: rgba(0,0,0,0.12);
            border: 1px solid rgba(255,255,255,0.16);
            display: grid;
            place-items: center;
            text-align: center;
            padding: 6px 6px;
        }
        .ww-uvbox .uv-label{ font-size: 10px; opacity: .9; margin-top: 2px; }
        .ww-uvbox .uv-value{ font-size: 22px; font-weight: 900; line-height: 1; margin-top: 2px; }
        .ww-uvbox .uv-cat{ font-size: 10px; opacity: .88; margin-bottom: 2px; letter-spacing: .2px; }

        .ww-aqi{
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid rgba(255,255,255,0.22);
        }
        .ww-aqirow{
            display:flex;
            align-items:baseline;
            gap: 8px;
        }
        .ww-aqirow b{ font-size: 13px; }
        .ww-aqicat{ opacity:.9; font-size: 11px; }

        /* Prediksi 5 jam */
        .ww-next5{
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid rgba(255,255,255,0.22);
            display:flex;
            gap: 6px;
            justify-content: space-between;
        }
        .ww-hour{
            width: 46px;
            text-align:center;
            padding: 6px 6px;
            border-radius: 12px;
            background: rgba(0,0,0,0.12);
            border: 1px solid rgba(255,255,255,0.16);
        }
        .ww-hour .t{ font-size: 10px; opacity:.85; }
        .ww-hour .i{
            font-size: 16px;
            display:inline-block;
            animation: wwPulse 1.8s ease-in-out infinite;
        }
        @keyframes wwPulse{
            0%,100%{ transform: scale(1); opacity:.95; }
            50%{ transform: scale(1.08); opacity: 1; }
        }
        .ww-hour .p{ font-size: 10px; opacity:.9; margin-top: 2px; }

        /* mode minimize: sembunyikan detail */
        .weather-widget.is-collapsed .ww-desc,
        .weather-widget.is-collapsed .ww-metrics,
        .weather-widget.is-collapsed .ww-sun,
        .weather-widget.is-collapsed .ww-aqi,
        .weather-widget.is-collapsed .ww-next5{
            display:none;
        }

        /* =========================
           CLOCK WIDGET (BOTTOM RIGHT)
           ========================= */
        .clock-widget{
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 3;
            padding: 12px 14px;
            width: 190px;
            text-align: center;
        }
        .cw-time{
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 1px;
        }
        .cw-date{
            margin-top: 4px;
            font-size: 12px;
            opacity: .9;
        }

        /* =========================
           RIGHT SIDEBAR (MENU PUBLIK)
           ========================= */
        .right-sidebar {
            position: fixed;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 3;
            width: 210px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .rsb-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .8px;
            opacity: .55;
            margin-bottom: 2px;
            padding: 0 4px;
        }
        .rsb-menu {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .rsb-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 14px;
            border-radius: 12px;
            background: rgba(255,255,255,.13);
            border: 1px solid rgba(255,255,255,.22);
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: background .2s, transform .15s;
            backdrop-filter: blur(10px);
        }
        .rsb-item:hover {
            background: rgba(255,255,255,.22);
            color: #fff;
            text-decoration: none;
            transform: translateX(-3px);
        }
        .rsb-item .rsb-icon {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .rsb-item.unggah .rsb-icon { background: rgba(52,152,219,.35); }
        .rsb-item.emodul  .rsb-icon { background: rgba(46,204,113,.35); }
        .rsb-item .rsb-text { line-height: 1.2; }
        .rsb-item .rsb-sub  { font-size: 10px; font-weight: 500; opacity: .65; margin-top: 1px; }

        @media (max-width: 1100px) {
            .right-sidebar { display: none; }
        }

        @media (max-width: 576px){
            .weather-widget{ transform: scale(.92); transform-origin: top left; }
            .clock-widget{ transform: scale(.92); transform-origin: bottom right; }
        }
    
        /* =========================
           TODO WIDGET (BOTTOM LEFT)
           ========================= */
        .todo-widget{
          position: fixed;
          left: 16px;
          bottom: 16px;
          z-index: 3;
          width: 300px;
          padding: 12px 12px 10px;
          font-size: 12px;
        }

        .todo-top{
          display:flex;
          justify-content: space-between;
          align-items: center;
          gap: 10px;
        }

        .todo-title{
          font-weight: 900;
          letter-spacing: .6px;
          font-size: 12px;
          text-transform: uppercase;
          opacity: .95;
        }

        .todo-sub{
          margin-top: 2px;
          font-size: 11px;
          opacity: .82;
        }

        .todo-toggle{
          appearance: none;
          border: 1px solid rgba(255,255,255,0.25);
          background: rgba(0,0,0,0.10);
          color: rgba(255,255,255,0.92);
          border-radius: 10px;
          padding: 4px 8px;
          font-size: 12px;
          line-height: 1;
          cursor: pointer;
          user-select: none;
        }
        .todo-toggle:focus{ outline: none; box-shadow: 0 0 0 0.15rem rgba(255,255,255,0.15); }

        .todo-body{ margin-top: 10px; }

        .todo-input{
          display:flex;
          gap: 8px;
          align-items: center;
        }

        .todo-input input{
          flex: 1;
          height: 34px;
          border-radius: 12px;
          padding: 0 10px;
          background: rgba(255,255,255,0.15);
          border: 1px solid rgba(255,255,255,0.28);
          color: #fff;
        }
        .todo-input input::placeholder{ color: rgba(255,255,255,0.70); }
        .todo-input input:focus{
          outline: none;
          background: rgba(255,255,255,0.22);
          border-color: rgba(255,255,255,0.60);
          box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.12);
        }

        .todo-add{
          height: 34px;
          min-width: 44px;
          border-radius: 12px;
          border: 1px solid rgba(255,255,255,0.25);
          background: rgba(0,123,255,0.88);
          color: #fff;
          cursor: pointer;
          box-shadow: 0 10px 18px rgba(0,0,0,0.18);
        }
        .todo-add:hover{ background: rgba(0,123,255,1); }
        .todo-add:focus{ outline:none; box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.15); }

        .todo-progress-wrap{
          margin-top: 10px;
          padding: 8px 8px;
          border-radius: 12px;
          background: rgba(0,0,0,0.12);
          border: 1px solid rgba(255,255,255,0.16);
        }

        .todo-progress-top{
          display:flex;
          justify-content: space-between;
          align-items: baseline;
          gap: 10px;
          font-size: 11px;
          opacity: .9;
        }

        .todo-bar{
          margin-top: 8px;
          height: 8px;
          border-radius: 999px;
          background: rgba(255,255,255,0.14);
          overflow: hidden;
          border: 1px solid rgba(255,255,255,0.18);
        }
        .todo-bar > div{
          height: 100%;
          width: 0%;
          background: rgba(120, 200, 255, 0.9);
          border-radius: 999px;
          transition: width .25s ease;
        }

        .todo-list{
          margin-top: 10px;
          max-height: 210px;
          overflow: auto;
          padding-right: 2px;
        }

        .todo-item{
          display:flex;
          align-items: center;
          gap: 8px;
          padding: 8px 8px;
          border-radius: 12px;
          background: rgba(0,0,0,0.12);
          border: 1px solid rgba(255,255,255,0.16);
          margin-bottom: 8px;
        }

        .todo-check{
          width: 16px;
          height: 16px;
          cursor: pointer;
        }

        .todo-text{
          flex: 1;
          color: rgba(255,255,255,0.95);
          line-height: 1.2;
          word-break: break-word;
        }

        .todo-item.is-done .todo-text{
          opacity: .65;
          text-decoration: line-through;
        }

        .todo-del{
          border: 1px solid rgba(255,255,255,0.18);
          background: rgba(0,0,0,0.10);
          color: rgba(255,255,255,0.92);
          border-radius: 10px;
          height: 28px;
          width: 34px;
          cursor: pointer;
        }
        .todo-del:hover{
          background: rgba(255, 60, 60, 0.28);
          border-color: rgba(255, 60, 60, 0.35);
        }

        .todo-empty{
          padding: 10px 8px;
          border-radius: 12px;
          background: rgba(0,0,0,0.10);
          border: 1px dashed rgba(255,255,255,0.18);
          opacity: .85;
          text-align: center;
        }

        /* collapsed mode */
        .todo-widget.is-collapsed .todo-body{
          display:none;
        }

        /* responsive */
        @media (max-width: 576px){
          .todo-widget{ transform: scale(.92); transform-origin: bottom left; width: 270px; }
        }

        /* confetti canvas */
        #todo-confetti{
          position: fixed;
          inset: 0;
          pointer-events: none;
          z-index: 9999;
        }

    </style>
</head>
<body>

<!-- Video background fullscreen -->
<video class="bg-video" autoplay muted loop playsinline preload="metadata">
    <source src="<?= htmlspecialchars($bgVideoUrl, ENT_QUOTES, 'UTF-8') ?>" type="video/mp4">
</video>
<div class="bg-overlay"></div>

<!-- WEATHER WIDGET (pojok kiri atas) -->
<div id="weather-widget" class="glass-widget weather-widget">
    <div class="ww-top">
        <div class="ww-title">
            <div class="ww-location">Mertoyudan, Magelang</div>
            <div class="ww-sub" id="ww-updated">Memuat...</div>
        </div>

        <div class="ww-right">
            <button type="button" class="ww-toggle" id="ww-toggle" aria-label="Minimize widget">–</button>
            <div class="ww-now">
                <span class="ww-icon" id="ww-icon">⛅</span>
                <div class="ww-temp" id="ww-temp">--°C</div>
            </div>
        </div>
    </div>

    <div class="ww-desc" id="ww-desc">Menunggu data cuaca…</div>

    <div class="ww-metrics">
        <div class="ww-metric"><span>Angin</span><b id="ww-wind">--</b></div>
        <div class="ww-metric"><span>Kelemb.</span><b id="ww-hum">--</b></div>
    </div>

    <div class="ww-sun">
        <div class="ww-sun-split">
            <div class="ww-sun-left">
                <div class="ww-sunrow"><span>Sunrise</span><b id="ww-sunrise">--:--</b></div>
                <div class="ww-sunrow"><span>Sunset</span><b id="ww-sunset">--:--</b></div>
            </div>

            <div class="ww-uvbox" aria-label="UV Index">
                <div class="uv-label">UV Index</div>
                <div class="uv-value" id="ww-uv">--</div>
                <div class="uv-cat" id="ww-uvcat">-</div>
            </div>
        </div>
    </div>

    <div class="ww-aqi">
        <div class="ww-aqirow">
            <span>AQI (EU)</span><b id="ww-aqi">--</b>
            <span class="ww-aqicat" id="ww-aqicat">-</span>
        </div>
    </div>

    <div class="ww-next5" id="ww-next5"></div>
</div>

<!-- CLOCK WIDGET (pojok kanan bawah) -->
<div id="clock-widget" class="glass-widget clock-widget">
    <div class="cw-time" id="cw-time">--:--:--</div>
    <div class="cw-date" id="cw-date">-- ---- ----</div>
</div>


<!-- TODO WIDGET (pojok kiri bawah) -->
<div id="todo-widget" class="glass-widget todo-widget" aria-label="To-do list">
    <div class="todo-top">
        <div>
            <div class="todo-title">Daftar Kerja</div>
            <div class="todo-sub" id="todo-sub">0 tugas</div>
        </div>
        <button type="button" class="todo-toggle" id="todo-toggle" aria-label="Minimize todo">–</button>
    </div>

    <div class="todo-body">
        <div class="todo-input">
            <input id="todo-input" type="text" placeholder="Tambah tugas..." autocomplete="off" />
            <button type="button" class="todo-add" id="todo-add" aria-label="Tambah">+</button>
        </div>

        <div class="todo-progress-wrap">
            <div class="todo-progress-top">
                <span>Progress</span>
                <b id="todo-progress-text">0%</b>
            </div>
            <div class="todo-bar"><div id="todo-progress-bar"></div></div>
        </div>

        <div class="todo-list" id="todo-list"></div>
    </div>
</div>

<canvas id="todo-confetti"></canvas>


<!-- RIGHT SIDEBAR -->
<div class="right-sidebar">
  <div class="rsb-title">Menu Publik</div>
  <div class="rsb-menu">

    <a href="/mavis/public/unggah-mandiri.php" class="rsb-item unggah" target="_blank" rel="noopener">
      <div class="rsb-icon">
        <i class="fas fa-upload"></i>
      </div>
      <div class="rsb-text">
        Unggah Mandiri
        <div class="rsb-sub">Upload karya siswa</div>
      </div>
    </a>

    <a href="/mavis/public/emodul.php" class="rsb-item emodul" target="_blank" rel="noopener">
      <div class="rsb-icon">
        <i class="fas fa-book-open"></i>
      </div>
      <div class="rsb-text">
        Daftar E-Modul
        <div class="rsb-sub">Buka & kerjakan modul</div>
      </div>
    </a>

  </div>
</div>

<!-- LOGIN -->
<div class="login-wrapper">
    <div class="card login-card border-0">
        <div class="card-body p-4">

            <div class="text-center mb-4">
                <img src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8'); ?>"
                     alt="Logo"
                     class="login-logo-img mb-2">
                <div class="login-title">
                    <?= htmlspecialchars($appTitle, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="login-subtitle">
                    <?= htmlspecialchars($subTitle, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <input type="text"
                               name="username"
                               id="username"
                               class="form-control"
                               placeholder="Masukkan username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               required>
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <input type="password"
                               name="password"
                               id="password"
                               class="form-control"
                               placeholder="Masukkan password"
                               required>
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="remember" disabled>
                    <label class="form-check-label" for="remember">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

        </div>
    </div>
</div>

<!-- JS -->
<script src="/mavis/assets/sbadmin2/vendor/jquery/jquery.min.js"></script>
<script src="/mavis/assets/sbadmin2/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/mavis/assets/sbadmin2/js/sb-admin-2.min.js"></script>

<script>
(function(){
    const LAT = -7.516046242677497;
    const LON = 110.19803296689165;
    const TZ  = "Asia/Jakarta";
    const el = (id) => document.getElementById(id);

    // ===== Minimize / Maximize widget (ingat state) =====
    const ww = el("weather-widget");
    const wwToggle = el("ww-toggle");
    const storageKey = "mavis_weather_collapsed";

    function setCollapsed(collapsed){
        ww.classList.toggle("is-collapsed", collapsed);
        wwToggle.textContent = collapsed ? "+" : "–";
        wwToggle.setAttribute("aria-label", collapsed ? "Maximize widget" : "Minimize widget");
        try { localStorage.setItem(storageKey, collapsed ? "1" : "0"); } catch(e){}
    }

    // init state
    try {
        setCollapsed(localStorage.getItem(storageKey) === "1");
    } catch(e) {
        setCollapsed(false);
    }

    wwToggle.addEventListener("click", function(){
        setCollapsed(!ww.classList.contains("is-collapsed"));
    });

    // CLOCK
    function updateClock(){
        const now = new Date();
        el("cw-time").textContent =
            now.toLocaleTimeString("id-ID", { hour:"2-digit", minute:"2-digit", second:"2-digit" });
        el("cw-date").textContent =
            now.toLocaleDateString("id-ID", { weekday:"long", year:"numeric", month:"long", day:"numeric" });
    }
    setInterval(updateClock, 1000);
    updateClock();

    async function fetchWeather(){
        const url =
            `https://api.open-meteo.com/v1/forecast?latitude=${LAT}&longitude=${LON}` +
            `&timezone=${encodeURIComponent(TZ)}` +
            `&current_weather=true` +
            `&hourly=weather_code,precipitation_probability,wind_speed_10m,relative_humidity_2m,uv_index,temperature_2m` +
            `&daily=sunrise,sunset`;
        const res = await fetch(url);
        if (!res.ok) throw new Error("Weather fetch failed");
        return res.json();
    }

    async function fetchAirQuality(){
        const url =
            `https://air-quality-api.open-meteo.com/v1/air-quality?latitude=${LAT}&longitude=${LON}` +
            `&timezone=${encodeURIComponent(TZ)}` +
            `&current=european_aqi`;
        const res = await fetch(url);
        if (!res.ok) throw new Error("AQ fetch failed");
        return res.json();
    }

    function weatherTextFromCode(code){
        if (code === 0) return "Cerah";
        if (code <= 2) return "Cerah Berawan";
        if (code === 3) return "Berawan";
        if (code <= 48) return "Berkabut";
        if (code <= 67) return "Hujan";
        if (code <= 77) return "Salju";
        if (code <= 82) return "Hujan Ringan";
        if (code >= 95) return "Badai / Petir";
        return "Cuaca";
    }

    function weatherIconFromCode(code, precipProb){
        if (precipProb >= 70) return "🌧️";
        if (code === 0) return "☀️";
        if (code <= 2) return "⛅";
        if (code === 3) return "☁️";
        if (code <= 48) return "🌫️";
        if (code <= 67) return "🌧️";
        if (code <= 77) return "❄️";
        if (code <= 82) return "🌦️";
        if (code >= 95) return "⛈️";
        return "🌡️";
    }

    function aqiCategoryEU(aqi){
        if (aqi <= 20) return "Good";
        if (aqi <= 40) return "Fair";
        if (aqi <= 60) return "Moderate";
        if (aqi <= 80) return "Poor";
        if (aqi <= 100) return "Very Poor";
        return "Extremely Poor";
    }

    function uvCategory(u){
        if (u <= 2) return "Low";
        if (u <= 5) return "Moderate";
        if (u <= 7) return "High";
        if (u <= 10) return "Very High";
        return "Extreme";
    }

    function fmtTimeHHMM(isoString){
        const d = new Date(isoString);
        return d.toLocaleTimeString("id-ID", { hour:"2-digit", minute:"2-digit" });
    }

    function fmtHourLabel(isoString){
        const d = new Date(isoString);
        return d.toLocaleTimeString("id-ID", { hour:"2-digit", minute:"2-digit" });
    }

    function buildNext5(weather){
        const times = weather.hourly.time;
        const code  = weather.hourly.weather_code;
        const pp    = weather.hourly.precipitation_probability;

        const nowISO = weather.current_weather.time;
        let idx = times.indexOf(nowISO);

        if (idx === -1){
            const now = new Date(nowISO).getTime();
            let best = 0, bestDiff = Infinity;
            for (let i=0;i<times.length;i++){
                const diff = Math.abs(new Date(times[i]).getTime() - now);
                if (diff < bestDiff){ bestDiff = diff; best = i; }
            }
            idx = best;
        }

        const host = el("ww-next5");
        host.innerHTML = "";

        for (let k=0;k<5;k++){
            const i = idx + k;
            if (i >= times.length) break;

            const hour = fmtHourLabel(times[i]);
            const p = pp[i] ?? 0;
            const ico = weatherIconFromCode(code[i], p);

            const item = document.createElement("div");
            item.className = "ww-hour";
            item.innerHTML = `
                <div class="t">${hour}</div>
                <div class="i">${ico}</div>
                <div class="p">${Math.round(p)}%</div>
            `;
            host.appendChild(item);
        }
    }

    async function refreshAll(){
        try{
            const [w, aq] = await Promise.all([fetchWeather(), fetchAirQuality()]);

            const temp = Math.round(w.current_weather.temperature);
            const wind = Math.round(w.current_weather.windspeed);
            const nowISO = w.current_weather.time;

            const times = w.hourly.time;
            let idx = times.indexOf(nowISO);
            if (idx === -1) idx = 0;

            const hum = w.hourly.relative_humidity_2m?.[idx];
            const uv  = w.hourly.uv_index?.[idx];
            const codeNow = w.current_weather.weathercode;
            const ppNow = w.hourly.precipitation_probability?.[idx] ?? 0;

            el("ww-temp").textContent = `${temp}°C`;
            el("ww-wind").textContent = `${wind} km/h`;
            el("ww-hum").textContent  = (hum === undefined ? "--" : `${Math.round(hum)}%`);

            const uvRounded = (uv === undefined || uv === null) ? null : Math.round(uv);
            el("ww-uv").textContent = (uvRounded === null ? "--" : `${uvRounded}`);
            el("ww-uvcat").textContent = (uvRounded === null ? "-" : uvCategory(uvRounded));

            el("ww-icon").textContent = weatherIconFromCode(codeNow, ppNow);
            el("ww-desc").textContent = `${weatherTextFromCode(codeNow)} • Peluang hujan ${Math.round(ppNow)}%`;

            const sr = w.daily.sunrise?.[0];
            const ss = w.daily.sunset?.[0];
            el("ww-sunrise").textContent = sr ? fmtTimeHHMM(sr) : "--:--";
            el("ww-sunset").textContent  = ss ? fmtTimeHHMM(ss) : "--:--";

            const aqiVal = aq.current?.european_aqi;
            el("ww-aqi").textContent = (aqiVal === undefined ? "--" : Math.round(aqiVal));
            el("ww-aqicat").textContent = (aqiVal === undefined ? "-" : aqiCategoryEU(aqiVal));

            el("ww-updated").textContent =
                `Update: ${new Date().toLocaleTimeString("id-ID",{hour:"2-digit",minute:"2-digit"})} WIB`;

            buildNext5(w);

        } catch (e){
            el("ww-desc").textContent = "Gagal memuat cuaca / AQI";
            el("ww-updated").textContent = "Cek koneksi internet";
        }
    }

    refreshAll();
    setInterval(refreshAll, 10 * 60 * 1000);
})();



// =========================
// TODO APP (pojok kiri bawah)
// =========================
(function initTodo(){
    const todoWidget = document.getElementById("todo-widget");
    const todoToggle = document.getElementById("todo-toggle");
    const todoInput  = document.getElementById("todo-input");
    const todoAdd    = document.getElementById("todo-add");
    const todoList   = document.getElementById("todo-list");
    const todoSub    = document.getElementById("todo-sub");
    const bar        = document.getElementById("todo-progress-bar");
    const barText    = document.getElementById("todo-progress-text");

    if (!todoWidget) return;

    const KEY_ITEMS = "mavis_todo_items";
    const KEY_COLL  = "mavis_todo_collapsed";
    const KEY_ALLDONE = "mavis_todo_alldone_flag";

    function safeJSONParse(v, fallback){
        try{ return JSON.parse(v); }catch(e){ return fallback; }
    }

    let items = safeJSONParse(localStorage.getItem(KEY_ITEMS), []);
    if (!Array.isArray(items)) items = [];

    function setCollapsed(collapsed){
        todoWidget.classList.toggle("is-collapsed", collapsed);
        todoToggle.textContent = collapsed ? "+" : "–";
        todoToggle.setAttribute("aria-label", collapsed ? "Maximize todo" : "Minimize todo");
        try{ localStorage.setItem(KEY_COLL, collapsed ? "1" : "0"); }catch(e){}
    }
    try{ setCollapsed(localStorage.getItem(KEY_COLL) === "1"); }catch(e){ setCollapsed(false); }

    todoToggle.addEventListener("click", () => {
        setCollapsed(!todoWidget.classList.contains("is-collapsed"));
    });

    function save(){
        try{ localStorage.setItem(KEY_ITEMS, JSON.stringify(items)); }catch(e){}
    }

    function uid(){
        return Math.random().toString(16).slice(2) + Date.now().toString(16);
    }

    function escapeHtml(str){
        return String(str)
            .replaceAll("&","&amp;")
            .replaceAll("<","&lt;")
            .replaceAll(">","&gt;")
            .replaceAll('"',"&quot;")
            .replaceAll("'","&#039;");
    }

    function stats(){
        const total = items.length;
        const done = items.filter(x => x.done).length;
        const pct = total === 0 ? 0 : Math.round((done/total)*100);
        return { total, done, pct };
    }

    // ---- Confetti sederhana (tanpa library) ----
    const canvas = document.getElementById("todo-confetti");
    const ctx = canvas ? canvas.getContext("2d") : null;

    function resizeCanvas(){
        if (!canvas) return;
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    window.addEventListener("resize", resizeCanvas);
    resizeCanvas();

    function confettiOnce(){
        if (!canvas || !ctx) return;

        const duration = 1400;
        const start = performance.now();
        const W = canvas.width, H = canvas.height;

        const parts = Array.from({length: 120}).map(() => ({
            x: Math.random() * W,
            y: -20 - Math.random()*H*0.2,
            vx: (Math.random()-0.5) * 2.2,
            vy: 2.2 + Math.random()*3.2,
            r: 2 + Math.random()*3,
            a: 0.7 + Math.random()*0.3,
            rot: Math.random()*Math.PI,
            vr: (Math.random()-0.5)*0.2
        }));

        function frame(t){
            const p = (t - start) / duration;
            ctx.clearRect(0,0,W,H);

            parts.forEach(o => {
                o.x += o.vx;
                o.y += o.vy;
                o.rot += o.vr;
                o.vy += 0.02;

                ctx.save();
                ctx.globalAlpha = o.a;
                ctx.translate(o.x, o.y);
                ctx.rotate(o.rot);
                ctx.fillStyle = `hsla(${Math.random()*360}, 80%, 65%, ${o.a})`;
                ctx.fillRect(-o.r, -o.r, o.r*2.2, o.r*1.6);
                ctx.restore();
            });

            if (p < 1){
                requestAnimationFrame(frame);
            } else {
                ctx.clearRect(0,0,W,H);
            }
        }
        requestAnimationFrame(frame);
    }

    function render(){
        const { total, done, pct } = stats();
        if (todoSub) todoSub.textContent = `${total} tugas • ${done} selesai`;

        if (bar) bar.style.width = `${pct}%`;
        if (barText) barText.textContent = `${pct}%`;

        if (!todoList) return;

        if (total === 0){
            todoList.innerHTML = `<div class="todo-empty">Belum ada tugas. Tambahkan yang pertama 🙂</div>`;
        } else {
            todoList.innerHTML = items.map(it => `
                <div class="todo-item ${it.done ? "is-done" : ""}" data-id="${it.id}">
                    <input class="todo-check" type="checkbox" ${it.done ? "checked" : ""} aria-label="Selesai" />
                    <div class="todo-text">${escapeHtml(it.text)}</div>
                    <button type="button" class="todo-del" aria-label="Hapus">✕</button>
                </div>
            `).join("");
        }

        const allDone = total > 0 && done === total;
        const lastFlag = localStorage.getItem(KEY_ALLDONE) === "1";
        if (allDone && !lastFlag){
            confettiOnce();
            try{ localStorage.setItem(KEY_ALLDONE, "1"); }catch(e){}
        }
        if (!allDone && lastFlag){
            try{ localStorage.setItem(KEY_ALLDONE, "0"); }catch(e){}
        }
    }

    function addTask(text){
        const t = (text || "").trim();
        if (!t) return;
        items.unshift({ id: uid(), text: t, done: false, createdAt: Date.now() });
        save();
        render();
        if (todoInput) { todoInput.value = ""; todoInput.focus(); }
    }

    if (todoAdd){
        todoAdd.addEventListener("click", () => addTask(todoInput ? todoInput.value : ""));
    }

    if (todoInput){
        todoInput.addEventListener("keydown", (e) => {
            if (e.key === "Enter"){
                e.preventDefault(); // jangan submit form login
                addTask(todoInput.value);
            }
        });
    }

    if (todoList){
        todoList.addEventListener("click", (e) => {
            const itemEl = e.target.closest(".todo-item");
            if (!itemEl) return;
            const id = itemEl.getAttribute("data-id");
            const idx = items.findIndex(x => x.id === id);
            if (idx < 0) return;

            if (e.target.classList.contains("todo-del")){
                items.splice(idx, 1);
                save();
                render();
                return;
            }
        });

        todoList.addEventListener("change", (e) => {
            const itemEl = e.target.closest(".todo-item");
            if (!itemEl) return;
            if (!e.target.classList.contains("todo-check")) return;
            const id = itemEl.getAttribute("data-id");
            const idx = items.findIndex(x => x.id === id);
            if (idx < 0) return;
            items[idx].done = !!e.target.checked;
            save();
            render();
        });
    }

    render();
})();

</script>

</body>
</html>
