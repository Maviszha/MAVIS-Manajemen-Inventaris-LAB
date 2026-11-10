<?php
// public/emodul-detail.php — Halaman detail e-modul dengan stepper
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function embedUrl(string $url): string {
    if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_\-]{11})/', $url, $m))
        return 'https://www.youtube.com/embed/' . $m[1] . '?autoplay=1&rel=0';
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $m))
        return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1';
    return $url;
}

function formEmbedUrl(string $url): string {
    // Google Form: pastikan pakai /viewform?embedded=true
    if (strpos($url, 'docs.google.com/forms') !== false) {
        $url = preg_replace('/\/viewform.*$/', '/viewform', $url);
        $url = preg_replace('/\/edit.*$/', '/viewform', $url);
        return $url . '?embedded=true';
    }
    return $url . (strpos($url,'?') === false ? '?' : '&') . 'embedded=true';
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: emodul.php"); exit; }

$stmt = $conn->prepare(
    "SELECT e.*, k.nama AS kelas_nama
     FROM e_modul e LEFT JOIN kelas k ON k.id = e.kelas_id
     WHERE e.id = ? LIMIT 1"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$em = $stmt->get_result()->fetch_assoc();
if (!$em) { header("Location: emodul.php"); exit; }

$ext   = strtolower(pathinfo($em['file_path'], PATHINFO_EXTENSION));
$isPpt = in_array($ext, ['ppt','pptx']);
$fileUrl = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 's':'') . '://' . $_SERVER['HTTP_HOST'] . '/mavis/' . $em['file_path'];

$bgVideoUrl = "/mavis/templates/vid/papan.mp4";
$bgPoster   = "/mavis/templates/img/bg1.jpg";

// Definisi langkah — skip jika link kosong
$steps = [];
if ($em['link_pretest'])  $steps[] = ['key'=>'pretest',  'label'=>'Pre-Test',     'icon'=>'📝', 'type'=>'form',   'link'=>$em['link_pretest']];
$steps[]                            = ['key'=>'modul',   'label'=>'Baca Modul',   'icon'=>'📖', 'type'=>'modul',  'link'=>null];
if ($em['link_posttest']) $steps[] = ['key'=>'posttest', 'label'=>'Post-Test',    'icon'=>'✅', 'type'=>'form',   'link'=>$em['link_posttest']];
if ($em['link_angket'])   $steps[] = ['key'=>'angket',   'label'=>'Angket',       'icon'=>'📋', 'type'=>'form',   'link'=>$em['link_angket']];
$steps[]                            = ['key'=>'laporan', 'label'=>'Unggah Laporan','icon'=>'📤','type'=>'laporan','link'=>null];

$totalSteps = count($steps);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($em['judul']) ?> — MAVIS</title>
  <link href="/mavis/assets/sbadmin2/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    html, body { margin: 0; min-height: 100%; }
    body {
      font-family: "Nunito", system-ui, sans-serif;
      color: #fff;
      overflow-x: hidden;
    }
    .bg-video { position:fixed; inset:0; width:100%; height:100%; object-fit:cover; z-index:0; }
    .bg-overlay {
      position:fixed; inset:0; z-index:0; pointer-events:none;
      background: radial-gradient(ellipse at center, rgba(0,0,0,.3) 0%, rgba(0,0,0,.6) 100%);
    }
    .page {
      position:relative; z-index:1;
      padding: 24px 16px 60px;
      display:flex; flex-direction:column; align-items:center;
    }

    /* NAV */
    .nav-bar {
      width: min(860px,100%);
      display:flex; justify-content:space-between; align-items:center;
      margin-bottom:16px; font-size:13px;
    }
    .nav-bar a { color:rgba(255,255,255,.75); text-decoration:none; font-weight:700; }
    .nav-bar a:hover { color:#fff; }

    /* HEADER MODUL */
    .modul-header {
      width: min(860px,100%);
      background: rgba(255,255,255,.13);
      border: 1px solid rgba(255,255,255,.22);
      backdrop-filter: blur(18px);
      border-radius: 18px;
      padding: 20px 24px;
      display: flex; gap: 18px; align-items: center;
      margin-bottom: 24px;
    }
    .modul-cover {
      width: 80px; height: 60px; flex-shrink:0;
      object-fit:cover; border-radius:10px;
      border:1px solid rgba(255,255,255,.25);
    }
    .modul-cover-empty {
      width:80px; height:60px; flex-shrink:0;
      background:rgba(255,255,255,.1); border-radius:10px;
      display:flex; align-items:center; justify-content:center;
      font-size:26px; opacity:.4;
    }
    .modul-info h1 { margin:0 0 4px; font-size:18px; font-weight:800; }
    .modul-info p  { margin:0; font-size:12px; opacity:.7; }

    /* STEPPER */
    .stepper {
      width: min(860px,100%);
      display: flex; align-items: flex-start; justify-content: center;
      margin-bottom: 28px;
      gap: 0;
    }
    .step-item {
      display: flex; flex-direction: column; align-items: center;
      flex: 1; position: relative;
    }
    .step-item:not(:last-child)::after {
      content:'';
      position:absolute; top:19px; left:calc(50% + 19px);
      width:calc(100% - 38px); height:2px;
      background: rgba(255,255,255,.2);
      transition: background .4s;
    }
    .step-item.done:not(:last-child)::after  { background: #2ecc71; }
    .step-item.active:not(:last-child)::after { background: rgba(255,255,255,.2); }

    .step-circle {
      width:38px; height:38px; border-radius:50%;
      display:flex; align-items:center; justify-content:center;
      font-size:16px; font-weight:800;
      border: 2px solid rgba(255,255,255,.25);
      background: rgba(255,255,255,.1);
      transition: all .3s; cursor:default; flex-shrink:0;
      position: relative; z-index:1;
    }
    .step-item.done   .step-circle { background:#2ecc71; border-color:#2ecc71; }
    .step-item.active .step-circle { background:rgba(255,255,255,.9); color:#333; border-color:#fff; box-shadow:0 0 0 4px rgba(255,255,255,.2); }
    .step-item.locked .step-circle { opacity:.4; }

    .step-label {
      font-size:10px; font-weight:700; text-transform:uppercase;
      letter-spacing:.4px; margin-top:6px; text-align:center;
      opacity:.7; transition: opacity .3s;
    }
    .step-item.active .step-label { opacity:1; }
    .step-item.done   .step-label { opacity:.9; }
    .step-item.locked .step-label { opacity:.35; }

    /* CONTENT CARD */
    .step-card {
      width: min(860px,100%);
      background: rgba(255,255,255,.13);
      border: 1px solid rgba(255,255,255,.22);
      backdrop-filter: blur(18px);
      border-radius: 18px;
      overflow: hidden;
      animation: fadeUp .35s ease both;
    }
    @keyframes fadeUp {
      from { opacity:0; transform:translateY(16px); }
      to   { opacity:1; transform:translateY(0); }
    }
    .step-card-header {
      padding: 16px 24px;
      background: rgba(255,255,255,.08);
      border-bottom: 1px solid rgba(255,255,255,.12);
      display:flex; align-items:center; gap:12px;
    }
    .step-card-header .step-emoji { font-size:24px; }
    .step-card-header h2 { margin:0; font-size:17px; font-weight:800; }
    .step-card-header p  { margin:4px 0 0; font-size:12px; opacity:.65; }

    .step-card-body { padding: 24px; }

    /* FORM EMBED */
    .form-wrap {
      position: relative;
      width: 100%; padding-top: 75%;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
    }
    .form-wrap iframe {
      position:absolute; inset:0;
      width:100%; height:100%; border:none;
    }
    .form-loading {
      position:absolute; inset:0;
      display:flex; flex-direction:column; align-items:center; justify-content:center;
      background:#f8f9fa; color:#555; font-size:14px; gap:10px; z-index:1;
    }
    .spinner-dark {
      width:32px; height:32px;
      border:3px solid #ddd; border-top-color:#555;
      border-radius:50%; animation:spin .7s linear infinite;
    }
    @keyframes spin { to { transform:rotate(360deg); } }

    /* MODUL VIEWER */
    .modul-actions { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px; }

    /* PDF EMBED */
    .pdf-embed-wrap {
      width: 100%;
      border-radius: 12px;
      overflow: hidden;
      background: #fff;
      margin-bottom: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,.4);
    }
    .pdf-embed-wrap iframe {
      width: 100%;
      height: 70vh;
      border: none;
      display: block;
    }
    .pdf-embed-wrap.ppt-mode {
      background: rgba(255,255,255,.1);
      border: 1px solid rgba(255,255,255,.2);
      padding: 20px;
      text-align: center;
    }

    /* SELESAI BACA checklist */
    .selesai-baca {
      background: rgba(46,204,113,.15);
      border: 1.5px solid rgba(46,204,113,.5);
      border-radius:12px; padding:16px 20px;
      display:flex; align-items:center; gap:14px;
    }
    .selesai-baca input[type=checkbox] {
      width:22px; height:22px; cursor:pointer; flex-shrink:0; accent-color:#2ecc71;
    }
    .selesai-baca label { margin:0; font-size:14px; font-weight:700; cursor:pointer; }

    /* TOMBOL NAVIGASI */
    .step-nav {
      display:flex; justify-content:space-between; align-items:center;
      margin-top:20px; flex-wrap:wrap; gap:10px;
    }
    .btn {
      display:inline-flex; align-items:center; gap:6px;
      padding:10px 22px; border-radius:10px;
      font-size:13px; font-weight:800; border:none; cursor:pointer;
      text-decoration:none; transition: opacity .2s, transform .1s;
    }
    .btn:hover { opacity:.88; }
    .btn:active { transform:scale(.97); }
    .btn-next    { background:rgba(255,255,255,.9); color:#222; }
    .btn-next:disabled, .btn-next[disabled] {
      opacity:.35; cursor:not-allowed; transform:none;
    }
    .btn-back    { background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.3); }
    .btn-finish  { background:linear-gradient(135deg,#2ecc71,#27ae60); color:#fff; }
    .btn-video-relay {
      background: linear-gradient(135deg, #ff416c, #ff4b2b);
      color: #fff; border: none;
    }

    /* SELESAI */
    .finish-card {
      text-align:center; padding:40px 24px;
    }
    .finish-card .finish-icon { font-size:64px; margin-bottom:16px; }
    .finish-card h2 { font-size:22px; font-weight:800; margin:0 0 8px; }
    .finish-card p  { opacity:.75; font-size:14px; }

    @media(max-width:600px){
      .step-label { font-size:9px; }
      .step-circle { width:32px; height:32px; font-size:13px; }
      .step-card-body { padding:16px; }
      .modul-header { flex-direction:column; align-items:flex-start; }
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
    <a href="emodul.php"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar</a>
    <span style="font-size:12px;opacity:.6;"><?= h($em['kelas_nama'] ?? '') ?></span>
  </div>

  <!-- HEADER MODUL -->
  <div class="modul-header">
    <?php if ($em['gambar_path']): ?>
      <img src="/mavis/<?= h($em['gambar_path']) ?>" class="modul-cover" alt="Cover">
    <?php else: ?>
      <div class="modul-cover-empty">📖</div>
    <?php endif; ?>
    <div class="modul-info">
      <h1><?= h($em['judul']) ?></h1>
      <p>✍️ <?= h($em['penulis']) ?> &nbsp;•&nbsp; 📅 <?= date('d/m/Y', strtotime($em['tanggal'])) ?></p>
    </div>
  </div>

  <!-- STEPPER -->
  <div class="stepper" id="stepper">
    <?php foreach ($steps as $idx => $step): ?>
      <div class="step-item <?= $idx === 0 ? 'active' : 'locked' ?>"
           id="step-item-<?= $idx ?>" data-idx="<?= $idx ?>">
        <div class="step-circle">
          <?= $step['icon'] ?>
        </div>
        <div class="step-label"><?= h($step['label']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- CONTENT CARD (dirender via JS) -->
  <div class="step-card" id="stepCard"></div>

</div>

<script>
// ===== DATA DARI PHP =====
const STEPS = <?= json_encode($steps) ?>;
const EMODUL_ID = <?= (int)$em['id'] ?>;
const FILE_URL  = <?= json_encode($fileUrl) ?>;
const IS_PPT    = <?= $isPpt ? 'true' : 'false' ?>;
const VIDEO_RELAY = <?= !empty($em['link_video_emodul'])
    ? json_encode('/mavis/public/video.php?title=' . urlencode($em['judul']) . '&url=' . urlencode($em['link_video_emodul']))
    : 'null' ?>;
const TOTAL = STEPS.length;

// ===== STATE =====
let currentStep = 0;
let doneSteps   = new Set();

function loadState() {
  // Selalu mulai dari awal — tidak menyimpan progress antar sesi
  currentStep = 0;
  doneSteps   = new Set();
}

function saveState() {
  // Tidak menyimpan ke localStorage — progress reset setiap buka halaman
}

// ===== RENDER STEPPER =====
function renderStepper() {
  STEPS.forEach((s, i) => {
    const el = document.getElementById('step-item-' + i);
    if (!el) return;
    el.className = 'step-item ' + (
      doneSteps.has(i) && i !== currentStep ? 'done' :
      i === currentStep ? 'active' : 'locked'
    );
    const circle = el.querySelector('.step-circle');
    circle.innerHTML = doneSteps.has(i) && i !== currentStep ? '✓' : s.icon;
  });
}

// ===== RENDER CARD =====
function renderCard() {
  const step = STEPS[currentStep];
  const card = document.getElementById('stepCard');
  card.innerHTML = '';
  card.style.animation = 'none';
  requestAnimationFrame(() => {
    card.style.animation = '';
    card.innerHTML = buildCard(step, currentStep);
    initCard(step, currentStep);
  });
}

function buildCard(step, idx) {
  const isFirst = idx === 0;
  const isLast  = idx === TOTAL - 1;
  const backBtn = `<div></div>`;

  if (step.type === 'form') {
    return `
      <div class="step-card-header">
        <span class="step-emoji">${step.icon}</span>
        <div>
          <h2>${step.label}</h2>
          <p>Isi form di bawah, lalu klik <strong>"Saya sudah mengerjakan"</strong> untuk lanjut.</p>
        </div>
      </div>
      <div class="step-card-body">
        <div class="form-wrap" id="formWrap">
          <div class="form-loading" id="formLoading">
            <div class="spinner-dark"></div>
            <span>Memuat form...</span>
          </div>
          <iframe id="formFrame" src="" onload="onFormLoad()"
                  style="position:absolute;inset:0;width:100%;height:100%;border:none;"></iframe>
        </div>
        <div class="step-nav" style="margin-top:20px;">
          ${backBtn}
          <button class="btn btn-next" id="btnLanjut" onclick="markDoneAndNext(${idx})" disabled>
            ✅ Saya sudah mengerjakan →
          </button>
        </div>
      </div>`;
  }

  if (step.type === 'modul') {
    const pdfSection = IS_PPT
      ? `<div class="pdf-embed-wrap ppt-mode">
           <i class="fas fa-file-powerpoint" style="font-size:48px;color:#d04a02;margin-bottom:12px;display:block;"></i>
           <p style="margin:0 0 16px;opacity:.85;">File e-modul berformat PPT/PPTX.</p>
           <a href="${escAttr(FILE_URL)}" download class="btn btn-back">
             <i class="fas fa-download mr-1"></i> Unduh File PPT
           </a>
         </div>`
      : `<div class="pdf-embed-wrap">
           <iframe src="${escAttr(FILE_URL)}" title="E-Modul PDF"></iframe>
         </div>
         <div class="modul-actions">
           <a href="${escAttr(FILE_URL)}" target="_blank" class="btn btn-back" style="font-size:12px;padding:7px 16px;">
             <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
           </a>
         </div>`;

    return `
      <div class="step-card-header">
        <span class="step-emoji">${step.icon}</span>
        <div>
          <h2>Baca E-Modul</h2>
          <p>Baca modul di bawah sampai selesai, lalu centang konfirmasi untuk lanjut.</p>
        </div>
      </div>
      <div class="step-card-body">
        ${pdfSection}
        <div class="selesai-baca">
          <input type="checkbox" id="checkSelesai" onchange="onCheckSelesai()">
          <label for="checkSelesai">Saya sudah membaca modul sampai selesai</label>
        </div>
        <div class="step-nav">
          ${backBtn}
          <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
            ${VIDEO_RELAY ? `<button class="btn btn-video-relay" onclick="bukaVideoRelay()">
              <i class="fas fa-play-circle"></i> Lihat Video
            </button>` : ''}
            <button class="btn btn-next" id="btnLanjutModul" onclick="markDoneAndNext(${idx})" disabled>
              Lanjut → ${STEPS[idx+1] ? STEPS[idx+1].label : ''}
            </button>
          </div>
        </div>
      </div>`;
  }

  if (step.type === 'laporan') {
    return `
      <div class="step-card-header">
        <span class="step-emoji">${step.icon}</span>
        <div>
          <h2>Unggah Laporan Praktikum</h2>
          <p>Langkah terakhir — isi dan kumpulkan laporan kelompok.</p>
        </div>
      </div>
      <div class="step-card-body">
        <p style="opacity:.8;font-size:14px;line-height:1.7;">
          Klik tombol di bawah untuk mengisi data kelompok dan laporan hasil praktikum.
          Laporan akan tersimpan dan dapat dilihat oleh admin/guru.
        </p>
        <div class="step-nav">
          ${backBtn}
          <a href="/mavis/public/emodul-laporan.php?emodul_id=${EMODUL_ID}"
             class="btn btn-finish" onclick="markDone(${idx})">
            📤 Isi & Kumpulkan Laporan →
          </a>
        </div>
      </div>`;
  }

  return '';
}

function initCard(step, idx) {
  if (step.type === 'form') {
    // Load iframe setelah card muncul
    setTimeout(() => {
      const frame = document.getElementById('formFrame');
      if (frame) frame.src = step.link + (step.link.includes('?') ? '&' : '?') + 'embedded=true';
    }, 150);

    // Aktifkan tombol lanjut setelah 8 detik (asumsi sudah baca)
    setTimeout(() => {
      const btn = document.getElementById('btnLanjut');
      if (btn) btn.disabled = false;
    }, 8000);
  }
}

function onFormLoad() {
  const loading = document.getElementById('formLoading');
  if (loading) loading.style.display = 'none';
  // Aktifkan tombol segera setelah form ter-load
  const btn = document.getElementById('btnLanjut');
  if (btn) btn.disabled = false;
}

function onCheckSelesai() {
  const cb  = document.getElementById('checkSelesai');
  const btn = document.getElementById('btnLanjutModul');
  if (btn) btn.disabled = !cb.checked;
}

// ===== NAVIGASI =====
function goStep(idx) {
  // Hanya boleh ke langkah yang sudah selesai atau langkah aktif berikutnya
  if (idx > currentStep && !doneSteps.has(currentStep)) return;
  if (idx > currentStep + 1) return; // tidak boleh loncat
  currentStep = Math.max(0, Math.min(idx, TOTAL - 1));
  saveState();
  renderStepper();
  renderCard();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function markDoneAndNext(idx) {
  markDone(idx);
  if (idx + 1 < TOTAL) goStep(idx + 1);
}

function markDone(idx) {
  doneSteps.add(idx);
  saveState();
  renderStepper();
}

// ===== HELPERS =====
function escJs(s)   { return String(s).replace(/'/g,"\\'").replace(/\\/g,'\\\\'); }
function escAttr(s) { return String(s).replace(/"/g,'&quot;'); }

function bukaVideoRelay() {
  if (!VIDEO_RELAY) return;
  // Buka popup 1280x720, tengah layar
  const w = 1280, h = 720;
  const left = Math.max(0, Math.round((screen.width  - w) / 2));
  const top  = Math.max(0, Math.round((screen.height - h) / 2));
  window.open(
    VIDEO_RELAY,
    'video_relay',
    `width=${w},height=${h},left=${left},top=${top},resizable=yes,scrollbars=no,toolbar=no,menubar=no,location=no,status=no`
  );
}

// ===== INIT =====
loadState();
renderStepper();
renderCard();
</script>
</body>
</html>