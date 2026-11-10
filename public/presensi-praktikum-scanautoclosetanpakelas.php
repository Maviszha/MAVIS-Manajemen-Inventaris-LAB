<?php
// /mavis/public/presensi-praktikum-scan.php
// PUBLIC - Scan Presensi Praktikum (Glass UI + Video Background)

include dirname(__DIR__) . "/config/db.php";

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$token = trim($_GET['t'] ?? '');
if ($token === '' || !ctype_xdigit($token)) {
  http_response_code(400);
  echo "Token tidak valid.";
  exit;
}

/*
 * ===========================
 * THEME (VIDEO BACKGROUND)
 * ===========================
 * Ikuti pola unggah-mandiri.php: bgVideoUrl + poster fallback
 */
$bgVideoFile = "papan.mp4";
$bgVideoUrl  = "/mavis/templates/vid/" . rawurlencode($bgVideoFile);
$bgPoster    = "/mavis/templates/img/bg1.jpg"; // fallback saat video loading / error

$stmt = $conn->prepare(
  "SELECT p.id, p.tanggal, p.sesi, p.kelas_id, p.public_token, k.nama AS nama_kelas
   FROM presensi_praktikum p
   JOIN kelas k ON p.kelas_id = k.id
   WHERE p.public_token = ?
   LIMIT 1"
);
$stmt->bind_param("s", $token);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$session) {
  http_response_code(404);
  echo "Sesi presensi tidak ditemukan.";
  exit;
}

$msg = trim($_GET['msg'] ?? '');
$ok  = trim($_GET['ok'] ?? '');

// Ambil siswa terakhir yang discan (untuk tampilkan foto) via querystring sid
$lastSid = (int)($_GET['sid'] ?? 0);
$lastStudent = null;



function buildFotoUrl(?string $foto): ?string {
    $foto = trim((string)$foto);
    if ($foto === '') return null;

    // Normalisasi separator Windows -> URL style
    $foto = str_replace('\\', '/', $foto);

    // Jika sudah URL absolut
    if (preg_match('~^https?://~i', $foto)) {
        return $foto;
    }

    // Jika DB menyimpan path relatif seperti 'uploads/siswa/nama.jpg' atau '/uploads/siswa/nama.jpg'
    if (stripos($foto, 'uploads/siswa/') !== false) {
        $foto = ltrim($foto, '/');          // uploads/siswa/xxx
        return '/mavis/' . $foto;           // /mavis/uploads/siswa/xxx
    }

    // Jika sudah path yang mengandung 'mavis/' atau diawali '/mavis'
    if (stripos($foto, 'mavis/') !== false || substr($foto, 0, 6) === '/mavis' || substr($foto, 0, 5) === 'mavis') {
        $foto = ltrim($foto, '/');
        return '/' . $foto;
    }

    // Default: DB hanya simpan nama file -> arahkan ke folder uploads/siswa
    $fname = basename($foto);
    return '/mavis/uploads/siswa/' . rawurlencode($fname);
}

if ($lastSid > 0) {

  $sql = "SELECT id, nis, nama, foto FROM siswa WHERE id = ? LIMIT 1";
  $st = $conn->prepare($sql);
  $st->bind_param("i", $lastSid);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  $st->close();

  if ($row) {
    $lastStudent = [
      'nis' => $row['nis'] ?? '',
      'nama' => $row['nama'] ?? '',
      'foto_url' => buildFotoUrl($row['foto'] ?? null),
    ];
  }
}
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Scan Presensi Praktikum - MAVIS</title>

  <style>
    :root{
      --glass: rgba(255,255,255,.14);
      --glass2: rgba(255,255,255,.10);
      --border: rgba(255,255,255,.18);
      --text: rgba(255,255,255,.92);
      --muted: rgba(255,255,255,.70);
      --primary: #1e88ff;
      --success: #37d67a;
      --danger: #ff5a6a;
    }

    *{box-sizing:border-box}
    html,body{height:100%}

    body{
      margin:0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      color: var(--text);
      background: #0f1013;
      overflow:auto;
      min-height:100%;
    }

    /* VIDEO BACKGROUND (samakan pola unggah-mandiri) */
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
      z-index: 0;
      pointer-events: none;
      background: radial-gradient(ellipse at center,
        rgba(0,0,0,0.22) 0%,
        rgba(0,0,0,0.42) 60%,
        rgba(0,0,0,0.58) 100%);
    }

    /* Wrapper di atas video */
    .wrap{
      position: relative;
      z-index: 1;
      min-height:100%;
      display:flex;
      align-items:center;
      justify-content:center;
      padding: 28px 16px;
    }

    /* Spotlight lembut */
    .wrap::before{
      content:"";
      position:absolute;
      inset:0;
      z-index:0;
      pointer-events:none;
      background: radial-gradient(circle at 50% 20%,
        rgba(255,255,255,0.09) 0%,
        rgba(255,255,255,0.00) 55%);
    }
    .wrap > *{ position:relative; z-index:1; }

    .panel{
      width:min(1180px, 100%);
      border-radius:18px;
      background: var(--glass);
      border: 1px solid var(--border);
      box-shadow: 0 18px 60px rgba(0,0,0,.45);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      overflow:hidden;
    }

    .panel-head{
      padding: 20px 24px 14px;
      border-bottom:1px solid rgba(255,255,255,.12);
    }

    .title{
      margin:0 0 6px 0;
      font-size:28px;
      font-weight:800;
      letter-spacing:.2px;
    }

    .subtitle{
      margin:0;
      font-size:14px;
      color: var(--muted);
      line-height:1.45;
    }

    .meta{
      margin-top: 14px;
      display:flex;
      gap:10px;
      flex-wrap:wrap;
    }

    .pill{
      padding: 7px 10px;
      border-radius: 12px;
      background: rgba(0,0,0,.25);
      border: 1px solid rgba(255,255,255,.14);
      color: rgba(255,255,255,.88);
      font-size: 13px;
    }

    .panel-body{ padding: 18px 24px 22px; }

    .grid{
      display:grid;
      grid-template-columns: 420px 1fr;
      gap:16px;
      align-items:start;
    }

    .card{
      border-radius:16px;
      background: var(--glass2);
      border: 1px solid rgba(255,255,255,.14);
      padding:16px;
      box-shadow: 0 10px 30px rgba(0,0,0,.25);
    }

    .card h3{
      margin:0 0 10px 0;
      font-size:16px;
      font-weight:800;
      letter-spacing:.2px;
    }

    .alert{
      border-radius:14px;
      padding:12px 14px;
      margin-bottom:14px;
      border: 1px solid rgba(255,255,255,.16);
      background: rgba(0,0,0,.22);
      display:flex;
      gap:10px;
      align-items:flex-start;
      font-size:14px;
    }
    .alert.success{ border-color: rgba(55,214,122,.35); background: rgba(55,214,122,.14); }
    .alert.danger{ border-color: rgba(255,90,106,.35); background: rgba(255,90,106,.14); }

    
/* Preview siswa (foto + nama) setelah scan */
.student-preview{
  display:flex;
  align-items:center;
  gap:12px;
  padding:10px 12px;
  border-radius:14px;
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.12);
  margin: 6px 0 12px 0;
}
.student-photo{
  width:64px;
  height:64px;
  border-radius:14px;
  object-fit:cover;
  background: rgba(0,0,0,.25);
  border: 1px solid rgba(255,255,255,.12);
  flex: 0 0 auto;
}
.student-photo.placeholder{
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:28px;
  color: rgba(255,255,255,.8);
}
.student-meta{min-width:0}
.student-name{
  font-weight:800;
  line-height:1.2;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
.student-nis{
  font-size:12px;
  color: rgba(255,255,255,.78);
  margin-top:2px;
}

.label{
      display:block;
      margin: 0 0 8px 0;
      font-size:13px;
      font-weight:800;
      color: rgba(255,255,255,.92);
    }

    .input{
      width:100%;
      height:44px;
      border-radius:12px;
      border: 1px solid rgba(255,255,255,.22);
      background: rgba(255,255,255,.12);
      color:#fff;
      padding: 0 14px;
      outline:none;
      font-size:15px;
    }
    .input::placeholder{ color: rgba(255,255,255,.65); }
    .input:focus{
      border-color: rgba(30,136,255,.55);
      box-shadow: 0 0 0 3px rgba(30,136,255,.18);
    }

    .help{
      margin-top:10px;
      font-size:12.5px;
      color: rgba(255,255,255,.75);
      line-height:1.45;
    }

    .btn{
      width:100%;
      height:46px;
      border-radius:12px;
      border:none;
      cursor:pointer;
      font-weight:900;
      font-size:15px;
      letter-spacing:.2px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:10px;
      margin-top:12px;
      background: var(--primary);
      color:#fff;
      box-shadow: 0 10px 24px rgba(30,136,255,.35);
    }
    .btn:active{ transform: translateY(1px); }

    .table-wrap{
      max-height: 420px;
      overflow:auto;
      border-radius:12px;
      border: 1px solid rgba(255,255,255,.12);
      background: rgba(0,0,0,.18);
    }

    table{
      width:100%;
      border-collapse:collapse;
      font-size:13.5px;
      color: rgba(255,255,255,.90);
    }
    thead th{
      position:sticky;
      top:0;
      z-index:2;
      text-align:left;
      padding:10px 12px;
      background: rgba(255,255,255,.10);
      border-bottom: 1px solid rgba(255,255,255,.14);
      font-weight:900;
    }
    tbody td{
      padding:10px 12px;
      border-bottom: 1px solid rgba(255,255,255,.10);
      vertical-align:middle;
    }
    tbody tr:hover td{ background: rgba(255,255,255,.06); }

    .tag{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-width:34px;
      padding:6px 10px;
      border-radius:999px;
      font-size:12px;
      font-weight:900;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(0,0,0,.22);
    }
    .tag.ok{ background: rgba(55,214,122,.16); border-color: rgba(55,214,122,.28); }
    .tag.no{ background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.12); color: rgba(255,255,255,.70); }

    .foot{
      padding: 14px 24px;
      border-top: 1px solid rgba(255,255,255,.12);
      color: rgba(255,255,255,.62);
      font-size:12.5px;
    }

    @media (max-width: 980px){
      .grid{ grid-template-columns: 1fr; }
      .table-wrap{ max-height: 360px; }
      .title{ font-size: 22px; }
    }

    /* Prefers reduced motion: kalau user set reduce motion, matikan video */
    @media (prefers-reduced-motion: reduce){
      .bg-video{ display:none; }
      body{ background:#111; }
    }
  
    .rightcol{display:flex; flex-direction:column; gap:12px;}
</style>
</head>
<body>

  <!-- Video background fullscreen (ikut pola unggah-mandiri) -->
  <video class="bg-video" autoplay muted loop playsinline preload="metadata" poster="<?= h($bgPoster) ?>">
    <source src="<?= h($bgVideoUrl) ?>" type="video/mp4">
  </video>
  <div class="bg-overlay"></div>

  <div class="wrap">
    <div class="panel">
      <div class="panel-head">
        <h1 class="title">Scan Presensi Praktikum</h1>
        <p class="subtitle">
          Arahkan barcode scanner ke kolom di bawah. Scanner biasanya otomatis menekan <b>Enter</b> setelah scan.
        </p>

        <div class="meta">
          <div class="pill"><b>Kelas:</b> <?= h($session['nama_kelas']); ?></div>
          <div class="pill"><b>Tanggal:</b> <?= h($session['tanggal']); ?></div>
          <div class="pill"><b>Sesi:</b> <?= h($session['sesi']); ?></div>
        </div>
      </div>

      <div class="panel-body">
<div class="grid">
          <!-- Card Scan -->
          <div class="card">
            <h3>Scan Barcode (NIS)</h3>

<?php if ($lastStudent && ($lastStudent['foto_url'] || $lastStudent['nama'])): ?>
  <div id="studentPreview" class="student-preview">
    <?php if ($lastStudent['foto_url']): ?>
      <img class="student-photo" src="<?= h($lastStudent['foto_url']); ?>" alt="Foto siswa">
    <?php else: ?>
      <div class="student-photo placeholder">👤</div>
    <?php endif; ?>
    <div class="student-meta">
      <div class="student-name"><?= h($lastStudent['nama']); ?></div>
      <div class="student-nis">NIS: <?= h($lastStudent['nis']); ?></div>
    </div>
  </div>
<?php endif; ?>

            <form method="POST" action="/mavis/public/presensi-praktikum-scan-process.php" autocomplete="off">
              <input type="hidden" name="t" value="<?= h($token); ?>">
              <label class="label" for="barcode">NIS <span style="color:#ffd27a">*</span></label>
              <input class="input" type="text" id="barcode" name="barcode" placeholder="Arahkan scanner..." autofocus required>
              <div class="help">Tips: jika scanner tidak otomatis submit, tekan tombol <b>Proses</b>.</div>
              <button class="btn" type="submit">☁️ Proses</button>
            </form>
          </div>


<!-- Notifikasi (kanan) -->
<div id="notifWrap" style="display:flex; flex-direction:column; gap:10px;">
  <?php if ($ok !== ''): ?>
    <div id="alertSuccess" class="alert success">✅ <div>Berhasil presensi: <b><?= h($ok); ?></b></div></div>
  <?php endif; ?>

  <?php if ($msg !== ''): ?>
    <div id="alertDanger" class="alert danger">⚠️ <div><?= h($msg); ?></div></div>
  <?php endif; ?>
</div>


          
        </div>
      </div>

      <div class="foot">
        © <?= date('Y'); ?> MAVIS (Manajemen & Inventaris) · Presensi Praktikum
      </div>
    </div>
  </div>

  <script>
    // fokus selalu kembali ke input barcode
    (function(){
      function refocus(){ var el=document.getElementById('barcode'); if(el) el.focus(); }
      setTimeout(refocus, 150);
      document.addEventListener('click', function(){ setTimeout(refocus, 50); });
    })();
  </script>
<script>
(function () {
  function collapseOut(el, delayMs) {
    if (!el) return;

    // Prepare for height animation
    el.style.overflow = 'hidden';
    el.style.willChange = 'max-height, opacity, margin, padding';
    el.style.opacity = '1';

    // Set current height as max-height so transition works from an explicit value
    const h = el.scrollHeight;
    el.style.maxHeight = h + 'px';

    // Ensure we have a computed style before transitioning
    requestAnimationFrame(() => {
      el.style.transition = 'max-height 280ms ease, opacity 200ms ease, margin 280ms ease, padding 280ms ease';
    });

    setTimeout(() => {
      // Trigger collapse
      el.style.opacity = '0';
      el.style.maxHeight = '0px';
      el.style.marginTop = '0';
      el.style.marginBottom = '0';
      el.style.paddingTop = '0';
      el.style.paddingBottom = '0';

      // Remove from layout after animation
      setTimeout(() => {
        el.style.display = 'none';
      }, 320);
    }, delayMs);
  }

  collapseOut(document.getElementById('alertSuccess'), 3000);
  collapseOut(document.getElementById('alertDanger'), 3000);
  collapseOut(document.getElementById('studentPreview'), 3000);
})();
</script>

</body>
</html>
