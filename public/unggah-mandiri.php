<?php
// public/unggah-mandiri.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

if (!isset($conn) || !($conn instanceof mysqli)) {
  die('<h3 style="font-family:system-ui">Koneksi database tidak tersedia.</h3>');
}

// Ambil kelas untuk dropdown (optional UI saja)
$kelas = [];
$qk = $conn->query("SELECT id, nama, aktif FROM kelas ORDER BY nama ASC");
if ($qk) while ($r = $qk->fetch_assoc()) $kelas[] = $r;

$flash = $_SESSION['flash_public'] ?? null;
unset($_SESSION['flash_public']);

/*
 * ===========================
 *  THEME (SAMAKAN DENGAN LOGIN)
 * ===========================
 */
$bgVideoFile = "papan.mp4";
$bgVideoUrl  = "/mavis/templates/vid/" . rawurlencode($bgVideoFile);
// fallback saat video loading
$bgPoster = "/mavis/templates/img/bg1.jpg";
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Unggah Mandiri - MAVIS</title>

  <!-- (Opsional) ikutkan FontAwesome agar konsisten dengan halaman login -->
  <link href="/mavis/assets/sbadmin2/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

  <style>
    *,*::before,*::after{ box-sizing:border-box; }

    html,body{ height:100%; }

    body{
      margin:0;
      font-family:"Nunito", system-ui,-apple-system,Segoe UI,Roboto,Arial;
      color:#fff;
      overflow:auto; /* form bisa lebih panjang dari layar */
      min-height:100%;
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
    /* Overlay: vignette halus biar nyaman dibaca */
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

    /* WRAPPER */
    .page{
      position: relative;
      z-index: 1;
      min-height: 100%;
      padding: 28px 16px 40px;
      display:flex;
      align-items: flex-start;
      justify-content:center;
    }

    /* Spotlight lembut */
    .page::before{
      content:"";
      position:absolute;
      inset:0;
      z-index:0;
      pointer-events:none;
      background: radial-gradient(circle at 50% 20%,
        rgba(255,255,255,0.09) 0%,
        rgba(255,255,255,0.00) 55%);
    }
    .page > *{ position:relative; z-index:1; }

    /* GLASS CARD (samakan feel dengan login) */
    .glass-card{
      width: min(980px, 100%);
      border-radius: 18px;
      border: 1px solid rgba(255,255,255,0.28);
      background: rgba(255,255,255,0.18);
      backdrop-filter: blur(16px) saturate(140%);
      -webkit-backdrop-filter: blur(16px) saturate(140%);
      box-shadow: 0 20px 40px rgba(0,0,0,0.35), inset 0 1px 0 rgba(255,255,255,0.25);
      padding: 18px;
      animation: popIn .45s ease-out;
    }
    @keyframes popIn{
      from{ transform: translateY(10px); opacity: 0; }
      to{ transform: translateY(0); opacity: 1; }
    }

    .head{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap: 12px;
      padding: 6px 6px 14px;
    }
    .title{
      margin:0;
      font-size: 20px;
      font-weight: 900;
      letter-spacing: .4px;
    }
    .subtitle{
      margin-top: 6px;
      font-size: 12px;
      opacity: .86;
      line-height: 1.35;
    }
/* ALERT */
    .alert{
      padding: 10px 12px;
      border-radius: 12px;
      margin: 0 6px 12px;
      border: 1px solid rgba(255,255,255,0.18);
      background: rgba(0,0,0,0.16);
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.12);
      font-size: 13px;
    }
    .alert.ok{
      border-color: rgba(120,255,170,0.35);
      background: rgba(60, 200, 120, 0.14);
    }
    .alert.err{
      border-color: rgba(255,120,120,0.35);
      background: rgba(220, 60, 60, 0.14);
    }

    /* FORM LAYOUT: pakai grid agar tidak bertumpuk */
    form{ margin: 0; padding: 0 6px 6px; }
    .grid{
      display:grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 14px;
      margin-top: 6px;
    }
    .field{
      display:flex;
      flex-direction:column;
      gap: 6px;
      min-width:0;
    }
    label{
      font-weight: 800;
      font-size: 12px;
      letter-spacing: .2px;
      opacity: .95;
      margin: 0;
    }
    .help{
      font-size: 11px;
      opacity: .78;
      line-height: 1.25;
    }

    input, select{
      width: 100%;
      height: 38px;
      border-radius: 12px;
      padding: 0 10px;
      color: #fff;
      background: rgba(255,255,255,0.15);
      border: 1px solid rgba(255,255,255,0.28);
      outline:none;
    }
    input::placeholder{ color: rgba(255,255,255,0.70); }
    input:focus, select:focus{
      background: rgba(255,255,255,0.22);
      border-color: rgba(255,255,255,0.65);
      box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.12);
    }

    /* file input: lebih rapi dan tidak overflow */
    input[type="file"]{ height: auto; padding: 8px 10px; line-height: 1.2; }

    
    /* File input seragam (glass + radius seperti login) */
    input[type="file"]{
      height: 38px;
      padding: 6px 10px;
      cursor: pointer;
    }
    input[type="file"]::file-selector-button{
      height: 28px;
      border-radius: 10px;
      border: 1px solid rgba(255,255,255,0.30);
      background: rgba(255,255,255,0.15);
      color: rgba(255,255,255,0.92);
      padding: 0 10px;
      margin-right: 10px;
      cursor: pointer;
    }
    input[type="file"]::file-selector-button:hover{
      background: rgba(255,255,255,0.22);
      border-color: rgba(255,255,255,0.45);
    }
    input[type="file"]::-webkit-file-upload-button{
      height: 28px;
      border-radius: 10px;
      border: 1px solid rgba(255,255,255,0.30);
      background: rgba(255,255,255,0.15);
      color: rgba(255,255,255,0.92);
      padding: 0 10px;
      margin-right: 10px;
      cursor: pointer;
    }
    input[type="file"]::-webkit-file-upload-button:hover{
      background: rgba(255,255,255,0.22);
      border-color: rgba(255,255,255,0.45);
    }

    /* Dropdown: option hitam agar terbaca */
    select option{
      color: #111 !important;
      background: #fff;
    }
.section{
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid rgba(255,255,255,0.20);
    }

    /* NIS result box: kecil, glass */
    .nis-box{
      margin-top: 10px;
      border-radius: 14px;
      padding: 10px 12px;
      border: 1px solid rgba(255,255,255,0.20);
      background: rgba(0,0,0,0.12);
      display:none;
    }
    .nis-box.ok{
      border-color: rgba(120,255,170,0.35);
      background: rgba(60, 200, 120, 0.12);
    }
    .nis-box.err{
      border-color: rgba(255,120,120,0.35);
      background: rgba(220, 60, 60, 0.12);
    }
    .nis-title{ font-weight: 900; margin-bottom: 4px; }
    .nis-sub{ opacity: .88; font-size: 12px; }

    .spinner{
      display:inline-block;
      width:14px;
      height:14px;
      border:2px solid rgba(255,255,255,0.30);
      border-top-color: rgba(0,123,255,0.95);
      border-radius:50%;
      animation: spin .8s linear infinite;
      vertical-align:-2px;
      margin-right:6px;
    }
    @keyframes spin{ to{ transform: rotate(360deg) } }

    /* BUTTON */
    .btn{
      margin-top: 14px;
      width: 100%;
      height: 40px;
      border-radius: 12px;
      border: none;
      cursor: pointer;
      font-weight: 900;
      letter-spacing: .3px;
      color: #fff;
      background: rgba(0,123,255,0.88);
      box-shadow: 0 10px 22px rgba(0,0,0,0.22);
    }
    .btn:hover{ background: rgba(0,123,255,1); }
    .btn:focus{ outline:none; box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.15); }
    .btn i{ margin-right: 8px; }

    .footer{
      margin-top: 12px;
      padding: 0 6px 4px;
      display:flex;
      justify-content:space-between;
      gap: 10px;
      align-items:center;
      font-size: 11px;
      opacity: .85;
      flex-wrap:wrap;
    }
    .footer a{
      color: rgba(255,255,255,0.9);
      text-decoration: none;
      border-bottom: 1px dashed rgba(255,255,255,0.35);
    }
    .footer a:hover{ opacity: .9; }

    @media (max-width: 480px){
      .glass-card{ padding: 14px; }
      .title{ font-size: 18px; }
    }

    /* Prefers reduced motion */
    @media (prefers-reduced-motion: reduce){
      .bg-video{ display:none; }
      body{ background:#111; }
      .glass-card{ animation:none !important; }
      .spinner{ animation:none !important; }
    }
  </style>
</head>
<body>

<!-- Video background fullscreen (sama dengan halaman login) -->
<video class="bg-video" autoplay muted loop playsinline preload="metadata" poster="<?= h($bgPoster) ?>">
  <source src="<?= h($bgVideoUrl) ?>" type="video/mp4">
</video>
<div class="bg-overlay"></div>

<div class="page">
  <div class="glass-card">
    <div class="head">
      <div>
        <h1 class="title">Unggah Mandiri Karya Siswa</h1>
        <div class="subtitle">
          Isi semua kolom di bawah untuk verifikasi. Jika NIS tidak ditemukan di sistem, upload ditolak. Silahkan menghubungi laboran terkait<br>
        </div>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert <?= ($flash['ok'] ?? false) ? 'ok' : 'err' ?>">
        <?= h($flash['message'] ?? '') ?>
      </div>
    <?php endif; ?>

    <form id="formUpload" action="/mavis/public/unggah-mandiri-action.php?action=submit" method="post" enctype="multipart/form-data">

      <div class="grid">
        <div class="field">
          <label for="nisInput">NIS *</label>
          <input type="text" id="nisInput" name="nis" maxlength="32" placeholder="Contoh: 10140024" required>
            <div id="nisResult" class="nis-box">
            <div class="nis-title" id="nisTitle"></div>
            <div class="nis-sub" id="nisSub"></div>
          </div>
        </div>

        <div class="field">
          <label for="namaInput">Nama</label>
          <input type="text" id="namaInput" name="nama" maxlength="120" placeholder="Isi Nama Lengkap">
        </div>

        <div class="field">
          <label for="kelasSelect">Kelas</label>
          <select id="kelasSelect" name="kelas_id">
            <option value="">Pilih kelas</option>
            <?php foreach ($kelas as $k): ?>
              <option value="<?= (int)$k['id'] ?>">
                <?= h($k['nama']) ?><?= ((int)$k['aktif']===0 ? ' (Nonaktif)' : '') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="help">Pilih sesuai kelas</div>
        </div>
      </div>

      <div class="section">
        <div class="grid">
          <div class="field" style="grid-column: 1 / -1;">
            <label for="judulInput">Judul *</label>
            <input type="text" id="judulInput" name="judul" maxlength="180" placeholder="Judul poster / karya siswa" required>
            <div class="help">Isi judul yang jelas dan lengkap.</div>
          </div>
        </div>

        <div class="grid">
          <div class="field">
            <label for="posterInput">Poster / Gambar (JPG/PNG) *</label>
            <input id="posterInput" type="file" name="poster" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
            <div class="help">Maks 3MB.</div>
          </div>

          <div class="field">
            <label for="fulltextInput">Fulltext (PDF) *</label>
            <input id="fulltextInput" type="file" name="fulltext" accept=".pdf,application/pdf" required>
            <div class="help">Maks 10MB.</div>
          </div>
        </div>

        <button class="btn" type="submit"><i class="fas fa-cloud-upload-alt"></i> Kirim Upload</button>
      </div>

      <div class="footer">
        <div>© <?= date('Y') ?> MAVIS (Manajemen & Inventaris) Lab. Kimia SMA TARUNA NUSANTARA</div>
        
      </div>

    </form>
  </div>
</div>

<script>
(function(){
  const nisInput = document.getElementById('nisInput');
  const box = document.getElementById('nisResult');
  const title = document.getElementById('nisTitle');
  const sub = document.getElementById('nisSub');

  let t = null;
  let last = '';

  function showBox(type, tText, sText) {
    box.style.display = 'block';
    box.className = 'nis-box ' + (type || '');
    title.innerHTML = tText || '';
    sub.textContent = sText || '';
  }

  function hideBox() {
    box.style.display = 'none';
    box.className = 'nis-box';
    title.textContent = '';
    sub.textContent = '';
  }

  async function cekNis(nis) {
    const url = '/mavis/public/unggah-mandiri-action.php?action=cek_siswa&ajax=1&nis=' + encodeURIComponent(nis);
    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    return data;
  }

  nisInput.addEventListener('input', function(){
    const nis = (nisInput.value || '').trim();

    if (t) clearTimeout(t);

    if (nis.length === 0) {
      last = '';
      hideBox();
      return;
    }

    // debounce 350ms biar gak spam
    t = setTimeout(async () => {
      if (nis === last) return;
      last = nis;

      showBox('', '<span class="spinner"></span> Mengecek NIS...', '');

      try {
        const data = await cekNis(nis);
        if (!data.ok) {
          showBox('err', '❌ NIS tidak ditemukan', 'Pastikan NIS benar sesuai data siswa.');
          return;
        }

        const s = data.siswa || {};
        const nama = s.nama || '-';
        const kelas = s.kelas_nama || '-';
        showBox('ok', '✅ Data ditemukan', `Nama: ${nama} • Kelas: ${kelas}`);
      } catch (e) {
        showBox('err', '❌ Gagal cek NIS', 'Coba refresh halaman.');
      }
    }, 350);
  });
})();
</script>
</body>
</html>
