<?php
// public/unggah-mandiri.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

if (!isset($conn) || !($conn instanceof mysqli)) {
  die("<h3>Koneksi database tidak tersedia.</h3>");
}

// Ambil kelas untuk dropdown (optional UI saja)
$kelas = [];
$qk = $conn->query("SELECT id, nama, aktif FROM kelas ORDER BY nama ASC");
if ($qk) while ($r = $qk->fetch_assoc()) $kelas[] = $r;

$flash = $_SESSION['flash_public'] ?? null;
unset($_SESSION['flash_public']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Unggah Mandiri - MAVIS</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial; background:#f6f7fb; margin:0; padding:32px;}
    .wrap{max-width:820px; margin:0 auto;}
    .card{background:#fff; border:1px solid #e6e8f0; border-radius:14px; padding:22px; box-shadow:0 6px 18px rgba(20,20,20,.06);}
    h1{margin:0 0 10px; font-size:28px;}
    .muted{color:#6b7280; font-size:13px; margin-bottom:18px;}
    .row{display:flex; gap:14px; flex-wrap:wrap;}
    .col{flex:1 1 240px;}
    label{display:block; font-weight:700; margin:10px 0 6px;}
    input,select{width:100%; padding:10px 11px; border:1px solid #d7dbe7; border-radius:10px; outline:none;}
    input:focus,select:focus{border-color:#7aa7ff; box-shadow:0 0 0 3px rgba(122,167,255,.18);}
    .btn{margin-top:14px; background:#2563eb; color:#fff; border:0; padding:11px 14px; border-radius:10px; cursor:pointer; font-weight:800;}
    .btn:hover{filter:brightness(.95);}
    .alert{padding:10px 12px; border-radius:10px; margin:0 0 12px; border:1px solid transparent;}
    .alert.ok{background:#e8f7ee; border-color:#b8e6c9; color:#1f6b3b;}
    .alert.err{background:#fdeaea; border-color:#f5b8b8; color:#8a1f1f;}

    /* box hasil cek NIS */
    .nis-box{margin-top:10px; border-radius:12px; padding:10px 12px; border:1px solid #e6e8f0; background:#fafbff; display:none;}
    .nis-box.ok{border-color:#b8e6c9; background:#e8f7ee;}
    .nis-box.err{border-color:#f5b8b8; background:#fdeaea;}
    .nis-title{font-weight:800; margin-bottom:4px;}
    .nis-sub{color:#4b5563; font-size:13px;}
    .spinner{display:inline-block; width:14px; height:14px; border:2px solid #cfd6ea; border-top-color:#2563eb; border-radius:50%; animation:spin .8s linear infinite; vertical-align:-2px; margin-right:6px;}
    @keyframes spin{to{transform:rotate(360deg)}}
    small{color:#6b7280;}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>Unggah Mandiri</h1>
      <div class="muted">
        Isi <b>NIS</b> untuk verifikasi. Jika NIS tidak ditemukan di sistem, upload ditolak.
        <br>Halaman ini bisa diakses tanpa login.
      </div>

      <?php if ($flash): ?>
        <div class="alert <?= ($flash['ok'] ?? false) ? 'ok' : 'err' ?>">
          <?= h($flash['message'] ?? '') ?>
        </div>
      <?php endif; ?>

      <form id="formUpload" action="/mavis/public/unggah-mandiri-action.php?action=submit" method="post" enctype="multipart/form-data">

        <div class="row">
          <div class="col">
            <label>NIS *</label>
            <input type="text" id="nisInput" name="nis" maxlength="32" placeholder="contoh: 20240101" required>
            <small>Wajib. Dipakai untuk validasi.</small>

            <div id="nisResult" class="nis-box">
              <div class="nis-title" id="nisTitle"></div>
              <div class="nis-sub" id="nisSub"></div>
            </div>
          </div>

          <div class="col">
            <label>Nama (opsional)</label>
            <input type="text" name="nama" maxlength="120" placeholder="boleh dikosongkan">
            <small>Diabaikan saat validasi (patokan NIS saja).</small>
          </div>

          <div class="col">
            <label>Kelas (opsional)</label>
            <select name="kelas_id">
              <option value="">Pilih kelas (opsional)</option>
              <?php foreach ($kelas as $k): ?>
                <option value="<?= (int)$k['id'] ?>">
                  <?= h($k['nama']) ?><?= ((int)$k['aktif']===0 ? ' (Nonaktif)' : '') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small>Diabaikan saat validasi (kelas diambil dari data siswa).</small>
          </div>
        </div>

        <label>Judul *</label>
        <input type="text" name="judul" maxlength="180" placeholder="Judul poster / karya" required>

        <div class="row">
          <div class="col">
            <label>Poster / Gambar (JPG/PNG) *</label>
            <input type="file" name="poster" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
            <small>Maks 3MB.</small>
          </div>
          <div class="col">
            <label>Fulltext (PDF) *</label>
            <input type="file" name="fulltext" accept=".pdf,application/pdf" required>
            <small>Maks 10MB.</small>
          </div>
        </div>

        <button class="btn" type="submit">Kirim Upload</button>
      </form>

      <div style="margin-top:12px;">
        <small>© <?= date('Y') ?> MAVIS</small>
      </div>
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
