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
$bgVideoFile = "Labs.mp4";
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

        @media (max-width: 576px){
            .weather-widget{ transform: scale(.92); transform-origin: top left; }
            .clock-widget{ transform: scale(.92); transform-origin: bottom right; }
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
</script>

</body>
</html>
