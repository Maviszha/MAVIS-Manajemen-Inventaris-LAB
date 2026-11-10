<?php
// public/emodul-laporan.php  — Form input laporan kelompok (tanpa login)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$emId = (int)($_GET['emodul_id'] ?? 0);
if (!$emId) { header("Location: emodul.php"); exit; }

// Ambil data e-modul
$stmt = $conn->prepare("SELECT id, judul, penulis FROM e_modul WHERE id = ?");
$stmt->bind_param('i', $emId);
$stmt->execute();
$emodul = $stmt->get_result()->fetch_assoc();
if (!$emodul) { header("Location: emodul.php"); exit; }

$flash = $_SESSION['flash_emodul'] ?? null;
unset($_SESSION['flash_emodul']);

// Ambil daftar kelas untuk dropdown
$kelasList = [];
$qk = $conn->query("SELECT id, nama, aktif FROM kelas ORDER BY nama ASC");
if ($qk) while ($r = $qk->fetch_assoc()) $kelasList[] = $r;

$bgVideoUrl = "/mavis/templates/vid/papan.mp4";
$bgPoster   = "/mavis/templates/img/bg1.jpg";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Input Laporan — <?= h($emodul['judul']) ?></title>
  <link href="/mavis/assets/sbadmin2/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    html, body { height: 100%; margin: 0; }
    body {
      font-family: "Nunito", system-ui, sans-serif;
      color: #fff;
      min-height: 100%;
      overflow-x: hidden;
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
        rgba(0,0,0,.28) 0%, rgba(0,0,0,.5) 70%, rgba(0,0,0,.68) 100%);
    }
    .page {
      position: relative; z-index: 1;
      padding: 28px 16px 50px;
      display: flex; flex-direction: column; align-items: center;
    }
    .glass-card {
      width: min(760px, 100%);
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
    .page-title { font-size: 22px; font-weight: 800; margin-bottom: 4px; }
    .page-subtitle { font-size: 13px; opacity: .75; margin-bottom: 22px; }

    label { font-weight: 700; font-size: 13px; display: block; margin-bottom: 5px; }
    input[type=text], textarea, select {
      width: 100%;
      padding: 9px 13px;
      border-radius: 9px;
      border: 1.5px solid rgba(255,255,255,.35);
      background: rgba(255,255,255,.12);
      color: #fff;
      font-size: 13px;
      outline: none;
      transition: border .2s;
      font-family: inherit;
    }
    input[type=text]::placeholder,
    textarea::placeholder { color: rgba(255,255,255,.45); }
    input[type=text]:focus,
    textarea:focus,
    select:focus { border-color: rgba(255,255,255,.75); background: rgba(255,255,255,.18); }

    /* Select option agar terbaca di dropdown */
    select option { color: #111 !important; background: #fff; }

    .form-group { margin-bottom: 16px; }

    /* NIS result box — mirip unggah-mandiri */
    .nis-box {
      margin-top: 6px;
      border-radius: 10px;
      padding: 8px 12px;
      font-size: 12px;
      font-weight: 700;
      display: none;
      line-height: 1.4;
    }
    .nis-box.ok  {
      display: block;
      background: rgba(40,167,69,.25);
      border: 1.5px solid rgba(40,167,69,.7);
      color: #d4edda;
    }
    .nis-box.err {
      display: block;
      background: rgba(220,53,69,.25);
      border: 1.5px solid rgba(220,53,69,.7);
      color: #f8d7da;
    }
    .nis-box.loading {
      display: block;
      background: rgba(255,255,255,.1);
      border: 1.5px solid rgba(255,255,255,.3);
      color: rgba(255,255,255,.7);
    }
    .nis-box .box-title { font-size: 13px; font-weight: 800; }
    .nis-box .box-sub   { font-size: 11.5px; font-weight: 600; opacity: .85; margin-top: 1px; }
    .spinner {
      display: inline-block; width: 10px; height: 10px;
      border: 2px solid rgba(255,255,255,.4);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin .6s linear infinite;
      vertical-align: middle; margin-right: 4px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .section-label {
      font-size: 11px; text-transform: uppercase;
      font-weight: 800; letter-spacing: .6px;
      opacity: .65;
      margin: 20px 0 10px;
      padding-bottom: 4px;
      border-bottom: 1px solid rgba(255,255,255,.2);
    }

    .btn-submit {
      width: 100%;
      padding: 12px;
      background: rgba(255,255,255,.9);
      color: #333;
      font-weight: 800;
      font-size: 14px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      margin-top: 10px;
      transition: opacity .2s;
    }
    .btn-submit:hover { opacity: .85; }

    /* === DYNAMIC ANGGOTA ROWS === */
    .anggota-row {
      background: rgba(255,255,255,.07);
      border: 1px solid rgba(255,255,255,.15);
      border-radius: 12px;
      padding: 14px 14px 10px;
      margin-bottom: 10px;
      opacity: 0;
      transform: translateY(8px);
      transition: opacity .25s ease, transform .25s ease;
    }
    .anggota-row.visible  { opacity: 1; transform: translateY(0); }
    .anggota-row.removing { opacity: 0; transform: translateY(-6px); }

    .anggota-row-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }
    .anggota-row-num {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .5px;
      opacity: .6;
    }
    .btn-hapus-row {
      background: rgba(220,53,69,.35);
      border: 1px solid rgba(220,53,69,.6);
      color: #f8d7da;
      border-radius: 7px;
      padding: 2px 9px;
      font-size: 12px;
      cursor: pointer;
      transition: background .2s;
    }
    .btn-hapus-row:hover { background: rgba(220,53,69,.55); }

    .anggota-row-fields {
      display: grid;
      grid-template-columns: 130px 1fr 150px;
      gap: 8px;
      align-items: start;
    }
    @media(max-width:560px){
      .anggota-row-fields { grid-template-columns: 1fr; }
    }

    .anggota-field label {
      font-size: 11px; opacity: .7; margin-bottom: 4px;
    }

    .btn-tambah {
      width: 100%;
      padding: 10px;
      background: rgba(255,255,255,.1);
      border: 1.5px dashed rgba(255,255,255,.35);
      color: rgba(255,255,255,.8);
      font-weight: 700;
      font-size: 13px;
      border-radius: 10px;
      cursor: pointer;
      margin-top: 4px;
      margin-bottom: 4px;
      transition: background .2s, border-color .2s;
    }
    .btn-tambah:hover {
      background: rgba(255,255,255,.18);
      border-color: rgba(255,255,255,.6);
      color: #fff;
    }

    .alert-success {
      background: rgba(40,167,69,.35);
      border: 1px solid rgba(40,167,69,.6);
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 18px;
      font-size: 13.5px;
    }
    .alert-danger {
      background: rgba(220,53,69,.35);
      border: 1px solid rgba(220,53,69,.6);
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 18px;
      font-size: 13.5px;
    }

    .nav-back {
      width: min(760px,100%);
      margin-bottom: 14px;
    }
    .nav-back a {
      color: rgba(255,255,255,.75);
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
    }
    .nav-back a:hover { color: #fff; }

    @media(max-width:600px){
      .glass-card { padding: 16px; }
      .page-title { font-size: 18px; }
    }
  </style>
</head>
<body>

<video class="bg-video" autoplay muted loop playsinline poster="<?= h($bgPoster) ?>">
  <source src="<?= h($bgVideoUrl) ?>" type="video/mp4">
</video>
<div class="bg-overlay"></div>

<div class="page">
  <div class="nav-back">
    <a href="emodul.php"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar E-Modul</a>
  </div>

  <div class="glass-card">
    <div class="page-title"><i class="fas fa-clipboard-list mr-2"></i> Input Laporan Kelompok</div>
    <div class="page-subtitle">
      E-Modul: <strong><?= h($emodul['judul']) ?></strong> — <?= h($emodul['penulis']) ?>
    </div>

    <?php if ($flash && $flash['type'] === 'success'): ?>
      <div class="alert-success">
        <i class="fas fa-check-circle mr-1"></i> <?= h($flash['msg']) ?>
      </div>
    <?php elseif ($flash && $flash['type'] === 'error'): ?>
      <div class="alert-danger">
        <i class="fas fa-exclamation-circle mr-1"></i> <?= h($flash['msg']) ?>
      </div>
    <?php endif; ?>

    <form action="/mavis/public/emodul-laporan-action.php" method="POST" enctype="multipart/form-data" id="formLaporan">
      <input type="hidden" name="emodul_id" value="<?= (int)$emId ?>">

      <!-- NAMA KELOMPOK -->
      <div class="form-group">
        <label><i class="fas fa-users mr-1"></i> Nama Kelompok <span style="color:#f8d7da">*</span></label>
        <input type="text" name="nama_kelompok" required
               placeholder="Contoh: Kelompok 3 / Avogadro">
      </div>

      <!-- ANGGOTA KELOMPOK -->
      <div class="section-label" style="display:flex;align-items:center;justify-content:space-between;">
        <span>Anggota Kelompok</span>
        <span id="anggota-count" style="font-size:11px;opacity:.6;font-weight:600;">1 siswa</span>
      </div>

      <div id="anggota-wrap">
        <!-- Baris pertama dirender via JS agar konsisten -->
      </div>

      <button type="button" id="btn-tambah-anggota" class="btn-tambah">
        <i class="fas fa-plus mr-1"></i> Tambah Anggota
      </button>

      <!-- JUDUL PRAKTIKUM -->
      <div class="section-label" style="margin-top:24px;">Detail Praktikum</div>
      <div class="form-group">
        <label>Judul Praktikum <span style="color:#f8d7da">*</span></label>
        <input type="text" name="judul_praktikum" required
               placeholder="Contoh: Titrasi Asam Basa dengan Indikator Fenolftalein">
      </div>

      <!-- FILE LAPORAN -->
<div class="form-group">

  <label>
    Upload File Laporan
    <span style="color:#f8d7da">*</span>
  </label>

  <input
      type="file"
      name="file_laporan"
      accept=".pdf,.doc,.docx"
      required
      style="
        width:100%;
        padding:10px;
        border-radius:9px;
        border:1.5px solid rgba(255,255,255,.35);
        background:rgba(255,255,255,.12);
        color:#fff;
      "
  >

  <small
      style="
        opacity:.7;
        font-size:12px;
        display:block;
        margin-top:6px;
      "
  >
    Format file:
    PDF, DOC, DOCX
    <br>
    Maksimal ukuran:
    10 MB
  </small>

</div>
      <button type="submit" class="btn-submit">
        <i class="fas fa-paper-plane mr-2"></i> Kumpulkan Laporan
      </button>
    </form>
  </div>
</div>

<script>
(function(){
  // Daftar kelas dari PHP
  const kelasList = <?= json_encode(array_map(fn($k) => [
    'nama'  => $k['nama'],
    'aktif' => (int)$k['aktif']
  ], $kelasList)) ?>;

  let rowCount = 0;

  // ---- Render opsi kelas ----
  function buildKelasOptions() {
    let html = '<option value="">-- Kelas --</option>';
    kelasList.forEach(k => {
      const label = k.nama + (k.aktif === 0 ? ' (Nonaktif)' : '');
      html += `<option value="${escHtml(k.nama)}">${escHtml(label)}</option>`;
    });
    return html;
  }

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ---- Buat baris anggota ----
  function buatBaris(isFirst) {
    rowCount++;
    const idx = rowCount;

    const row = document.createElement('div');
    row.className = 'anggota-row';
    row.dataset.idx = idx;

    row.innerHTML = `
      <div class="anggota-row-header">
        <span class="anggota-row-num">Siswa <span class="urutan">${idx}</span></span>
        ${!isFirst ? `<button type="button" class="btn-hapus-row" onclick="hapusBaris(this)" title="Hapus">
          <i class="fas fa-times"></i>
        </button>` : ''}
      </div>
      <div class="anggota-row-fields">
        <div class="anggota-field">
          <label>NIS ${isFirst ? '<span style="color:#f8d7da">*</span>' : ''}</label>
          <input type="text" name="nis[${idx}]" id="nis_${idx}"
                 placeholder="NIS" autocomplete="off"
                 ${isFirst ? 'required' : ''}>
          <div class="nis-box" id="nis_box_${idx}">
            <div class="box-title" id="nis_box_title_${idx}"></div>
            <div class="box-sub"   id="nis_box_sub_${idx}"></div>
          </div>
        </div>
        <div class="anggota-field">
          <label>Nama Siswa ${isFirst ? '<span style="color:#f8d7da">*</span>' : ''}</label>
          <input type="text" name="nama[${idx}]" id="nama_${idx}"
                 placeholder="Nama lengkap" ${isFirst ? 'required' : ''}>
        </div>
        <div class="anggota-field anggota-field-kelas">
          <label>Kelas ${isFirst ? '<span style="color:#f8d7da">*</span>' : ''}</label>
          <select name="kelas[${idx}]" id="kelas_${idx}" ${isFirst ? 'required' : ''}>
            ${buildKelasOptions()}
          </select>
        </div>
      </div>
    `;

    document.getElementById('anggota-wrap').appendChild(row);
    setupNisRow(idx);
    updateCount();

    // Animasi masuk
    requestAnimationFrame(() => row.classList.add('visible'));
  }

  // ---- Hapus baris ----
  window.hapusBaris = function(btn) {
    const row = btn.closest('.anggota-row');
    row.classList.add('removing');
    row.addEventListener('transitionend', () => {
      row.remove();
      renumberBaris();
      updateCount();
    }, { once: true });
  };

  function renumberBaris() {
    document.querySelectorAll('#anggota-wrap .anggota-row').forEach((row, i) => {
      const el = row.querySelector('.urutan');
      if (el) el.textContent = i + 1;
    });
  }

  function updateCount() {
    const n = document.querySelectorAll('#anggota-wrap .anggota-row').length;
    document.getElementById('anggota-count').textContent = n + ' siswa';
  }

  // ---- NIS autocomplete ----
  async function cekNis(nis) {
    const url = '/mavis/public/unggah-mandiri-action.php?action=cek_siswa&ajax=1&nis=' + encodeURIComponent(nis);
    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    return res.json();
  }

  function showBox(i, type, title, sub) {
    const box = document.getElementById('nis_box_' + i);
    if (!box) return;
    box.className = 'nis-box ' + type;
    document.getElementById('nis_box_title_' + i).innerHTML = title;
    document.getElementById('nis_box_sub_'   + i).textContent = sub;
  }

  function hideBox(i) {
    const box = document.getElementById('nis_box_' + i);
    if (!box) return;
    box.className = 'nis-box';
    document.getElementById('nis_box_title_' + i).textContent = '';
    document.getElementById('nis_box_sub_'   + i).textContent = '';
  }

  function setupNisRow(i) {
    const nisInput    = document.getElementById('nis_' + i);
    const namaInput   = document.getElementById('nama_' + i);
    const kelasSelect = document.getElementById('kelas_' + i);
    if (!nisInput) return;

    let timer = null, lastNis = '';

    nisInput.addEventListener('input', function(){
      const nis = (this.value || '').trim();
      if (timer) clearTimeout(timer);
      if (!nis) { hideBox(i); lastNis = ''; return; }

      timer = setTimeout(async () => {
        if (nis === lastNis) return;
        lastNis = nis;
        showBox(i, 'loading', '<span class="spinner"></span> Mencari...', '');
        try {
          const data = await cekNis(nis);
          if (!data.ok) {
            showBox(i, 'err', '❌ NIS tidak ditemukan', 'Pastikan NIS benar sesuai data siswa.');
            return;
          }
          const s = data.siswa || {};
          if (namaInput && s.nama) namaInput.value = s.nama;
          if (kelasSelect && s.kelas_nama) {
            for (let opt of kelasSelect.options) {
              if (opt.value.trim() === s.kelas_nama.trim()) { opt.selected = true; break; }
            }
          }
          showBox(i, 'ok', '✅ Data ditemukan', 'Nama: ' + (s.nama||'-') + ' • Kelas: ' + (s.kelas_nama||'-'));
        } catch(e) {
          showBox(i, 'err', '❌ Gagal cek NIS', 'Coba refresh halaman.');
        }
      }, 400);
    });
  }

  // ---- Init ----
  document.getElementById('btn-tambah-anggota').addEventListener('click', () => buatBaris(false));

  // Render baris pertama
  buatBaris(true);
})();
</script>
</body>
</html>
