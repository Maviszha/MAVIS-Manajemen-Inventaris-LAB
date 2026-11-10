<?php
// pages/unggah-mandiri-list.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../templates/header.php';

// Admin only
$role = strtolower((string)($_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '')));
if ($role !== 'admin') {
  http_response_code(403);
  echo "<div class='container-fluid'><div class='alert alert-danger'>Akses ditolak (admin saja).</div></div>";
  require_once __DIR__ . '/../templates/footer.php';
  exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
  die("<div class='container-fluid'><div class='alert alert-danger'>Koneksi database (\$conn) tidak tersedia.</div></div>");
}

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// filter
$kelas_id = (isset($_GET['kelas_id']) && $_GET['kelas_id'] !== '') ? (int)$_GET['kelas_id'] : null;
// keyword bisa NIS / Nama / Judul
$keyword  = trim((string)($_GET['keyword'] ?? ''));

// daftar kelas untuk filter dropdown
$kelas = [];
$qk = $conn->query("SELECT id, nama FROM kelas ORDER BY nama ASC");
if ($qk) while ($r = $qk->fetch_assoc()) $kelas[] = $r;

// =========================
// ambil data upload
// - unggah_mandiri join siswa via siswa_id
// - kelas join via s.kelas_id
// =========================
$where = [];
$params = [];
$types  = '';

$sql = "SELECT 
          u.id,
          u.judul,
          u.poster_path,
          u.fulltext_path,
          u.created_at,
          s.nis AS nis,
          s.nama AS siswa_nama,
          s.kelas_id AS kelas_id,
          k.nama AS kelas_nama
        FROM unggah_mandiri u
        INNER JOIN siswa s ON s.id = u.siswa_id
        LEFT JOIN kelas k ON k.id = s.kelas_id";

if ($kelas_id !== null) {
  $where[] = "s.kelas_id = ?";
  $types  .= "i";
  $params[] = $kelas_id;
}

if ($keyword !== '') {
  // cari partial di NIS / Nama / Judul
  $where[] = "(s.nis LIKE ? OR s.nama LIKE ? OR u.judul LIKE ?)";
  $kw = '%' . $keyword . '%';
  $types  .= "sss";
  $params[] = $kw;
  $params[] = $kw;
  $params[] = $kw;
}

if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY u.created_at DESC, u.id DESC LIMIT 2000";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  die("<div class='container-fluid'><div class='alert alert-danger'>Prepare gagal: ".h($conn->error)."</div></div>");
}
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$res  = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<style>
  th.col-kelas, td.col-kelas { width: 140px; white-space: nowrap; }
  th.col-file,  td.col-file  { width: 240px; }
  th.col-aksi,  td.col-aksi  { width: 150px; white-space: nowrap; }

  .file-box { display:flex; flex-direction:column; gap:6px; }
  .file-item { display:flex; align-items:center; justify-content:space-between; gap:10px; }
  .file-left { display:flex; align-items:center; gap:8px; min-width:0; }
  .file-left .label { font-weight:600; }
  .file-left .hint { font-size:12px; color:#6c757d; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:130px; }
  .btn-mini { padding:.15rem .4rem; font-size:.75rem; line-height:1.2; }
  .badge-mini { font-size:.7rem; padding:.25em .5em; }

  .bulk-actions { display:flex; gap:8px; justify-content:flex-end; align-items:center; }
  .bulk-actions .btn { white-space: nowrap; }

  .chk-wrap { display:inline-flex; align-items:center; gap:10px; }
  .chk-wrap input[type="checkbox"] { transform: scale(1.1); }
</style>

<div class="container-fluid">

  <div class="d-sm-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0 text-gray-800">Daftar Upload Mandiri</h1>
    <a href="/mavis/pages/data-siswa.php" class="btn btn-secondary btn-sm">
      <i class="fas fa-arrow-left"></i> Kembali ke Data Siswa
    </a>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= h($flash['type'] ?? 'info') ?> shadow-sm">
      <?= h($flash['message'] ?? '') ?>
    </div>
  <?php endif; ?>

  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
      <h6 class="m-0 font-weight-bold text-primary mb-2 mb-md-0">Data Upload (maks 2000 data terakhir)</h6>

      <form method="get" class="form-inline">
        <div class="form-group mr-2 mb-2 mb-md-0">
          <label class="mr-2 small text-muted">Kelas</label>
          <select name="kelas_id" class="form-control form-control-sm" style="min-width:190px;">
            <option value="">Semua</option>
            <?php foreach ($kelas as $k): ?>
              <option value="<?= (int)$k['id'] ?>" <?= ($kelas_id !== null && $kelas_id === (int)$k['id']) ? 'selected' : '' ?>>
                <?= h($k['nama']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group mr-2 mb-2 mb-md-0">
          <label class="mr-2 small text-muted">Cari</label>
          <input type="text" name="keyword" value="<?= h($keyword) ?>" class="form-control form-control-sm"
                 placeholder="NIS / Nama / Judul..." style="min-width:220px;">
        </div>

        <button type="submit" class="btn btn-sm btn-info mb-2 mb-md-0">
          <i class="fas fa-filter"></i> Terapkan
        </button>
      </form>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm" id="tblUpload" width="100%" cellspacing="0">
          <thead class="thead-light">
            <tr>
              <th style="width:60px;">ID</th>
              <th style="width:140px;">Waktu</th>
              <th style="width:120px;">NIS</th>
              <th style="width:200px;">Nama</th>
              <th>Judul</th>
              <th class="col-kelas">Kelas</th>
              <th class="col-file">File</th>
              <th class="col-aksi">Aksi</th>
            </tr>
          </thead>

          <tbody>
            <?php if ($rows): ?>
              <?php foreach ($rows as $r): ?>
                <?php
                  $id = (int)$r['id'];
                  $poster = (string)($r['poster_path'] ?? '');
                  $pdf    = (string)($r['fulltext_path'] ?? '');

                  $posterUrl  = $poster !== '' ? '/mavis/' . ltrim($poster, '/') : '';
                  $pdfUrl     = $pdf !== '' ? '/mavis/' . ltrim($pdf, '/') : '';

                  $posterName = $poster !== '' ? basename($poster) : '';
                  $pdfName    = $pdf !== '' ? basename($pdf) : '';
                ?>
                <tr>
                  <td><?= $id ?></td>
                  <td><?= h($r['created_at'] ?? '-') ?></td>
                  <td><?= h($r['nis'] ?? '-') ?></td>
                  <td><?= h($r['siswa_nama'] ?? '-') ?></td>
                  <td><?= h($r['judul'] ?? '-') ?></td>
                  <td class="col-kelas"><?= h($r['kelas_nama'] ?? '-') ?></td>

                  <td class="col-file">
                    <div class="file-box">

                      <div class="file-item">
                        <div class="file-left">
                          <i class="far fa-image text-primary"></i>
                          <div style="min-width:0;">
                            <div class="label">Poster</div>
                            <div class="hint"><?= $posterName ? h($posterName) : '-' ?></div>
                          </div>
                        </div>
                        <?php if ($posterUrl): ?>
                          <a class="btn btn-outline-primary btn-mini" href="<?= h($posterUrl) ?>" target="_blank" rel="noopener" title="Lihat Poster">
                            <i class="fas fa-eye"></i>
                          </a>
                        <?php else: ?>
                          <span class="badge badge-secondary badge-mini">N/A</span>
                        <?php endif; ?>
                      </div>

                      <div class="file-item">
                        <div class="file-left">
                          <i class="far fa-file-pdf text-danger"></i>
                          <div style="min-width:0;">
                            <div class="label">Fulltext</div>
                            <div class="hint"><?= $pdfName ? h($pdfName) : '-' ?></div>
                          </div>
                        </div>
                        <?php if ($pdfUrl): ?>
                          <a class="btn btn-outline-danger btn-mini" href="<?= h($pdfUrl) ?>" target="_blank" rel="noopener" title="Lihat PDF">
                            <i class="fas fa-eye"></i>
                          </a>
                        <?php else: ?>
                          <span class="badge badge-secondary badge-mini">N/A</span>
                        <?php endif; ?>
                      </div>

                    </div>
                  </td>

                  <td class="col-aksi text-center">
                    <div class="chk-wrap justify-content-center">
                      <input type="checkbox" class="row-check" value="<?= $id ?>" title="Tandai">
                      <button type="button" class="btn btn-danger btn-sm btn-delete-one" data-id="<?= $id ?>" title="Hapus">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="8" class="text-center text-muted">Belum ada data upload.</td></tr>
            <?php endif; ?>
          </tbody>

        </table>
      </div>

      <small class="text-muted">Search bawaan DataTables sengaja dihilangkan. Gunakan filter Kelas + Cari (NIS/Nama/Judul) di atas.</small>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (!(window.$ && $.fn.DataTable)) return;

  const bulkHtml = `
    <div class="bulk-actions">
      <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSelectAll">
        <i class="fas fa-check-square"></i> Pilih semua
      </button>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearAll">
        <i class="far fa-square"></i> Bersihkan
      </button>
      <button type="button" class="btn btn-sm btn-danger" id="btnDeleteSelected" disabled>
        <i class="fas fa-trash"></i> Hapus yang ditandai
      </button>
    </div>
  `;

  $('#tblUpload').DataTable({
    pageLength: 25,
    autoWidth: false,
    searching: false,
    dom: '<"row mb-2"<"col-md-6"l><"col-md-6 text-right"<"bulkWrap">>>rtip',
    initComplete: function () {
      $('.bulkWrap').html(bulkHtml);
      bindBulkButtons();
      bindRowButtons();
      updateDeleteSelectedState();
    }
  });

  function getCheckedIds() {
    return Array.from(document.querySelectorAll('.row-check:checked')).map(ch => ch.value);
  }

  function updateDeleteSelectedState() {
    const ids = getCheckedIds();
    const btn = document.getElementById('btnDeleteSelected');
    if (!btn) return;
    btn.disabled = (ids.length === 0);
    btn.innerHTML = `<i class="fas fa-trash"></i> Hapus yang ditandai (${ids.length})`;
  }

  function bindBulkButtons() {
    document.getElementById('btnSelectAll')?.addEventListener('click', () => {
      document.querySelectorAll('.row-check').forEach(ch => ch.checked = true);
      updateDeleteSelectedState();
    });

    document.getElementById('btnClearAll')?.addEventListener('click', () => {
      document.querySelectorAll('.row-check').forEach(ch => ch.checked = false);
      updateDeleteSelectedState();
    });

    document.getElementById('btnDeleteSelected')?.addEventListener('click', async () => {
      const ids = getCheckedIds();
      if (ids.length === 0) return;
      if (!confirm(`Hapus ${ids.length} data yang ditandai? File poster/PDF juga akan ikut dihapus.`)) return;

      const fd = new FormData();
      ids.forEach(id => fd.append('ids[]', id));

      try {
        const res = await fetch('/mavis/public/unggah-mandiri-action.php?action=delete_selected', {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: fd
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.message || 'Gagal hapus terpilih');
        window.location.reload();
      } catch (e) {
        alert(e.message || 'Gagal hapus terpilih');
      }
    });

    document.querySelectorAll('.row-check').forEach(ch => {
      ch.addEventListener('change', updateDeleteSelectedState);
    });
  }

  function bindRowButtons() {
    document.querySelectorAll('.btn-delete-one').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.getAttribute('data-id');
        if (!id) return;
        if (!confirm('Hapus data upload ini? File poster/PDF juga akan ikut dihapus.')) return;

        const fd = new FormData();
        fd.append('id', id);

        try {
          const res = await fetch('/mavis/public/unggah-mandiri-action.php?action=delete_one', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
          });
          const data = await res.json();
          if (!data.ok) throw new Error(data.message || 'Gagal hapus');
          window.location.reload();
        } catch (e) {
          alert(e.message || 'Gagal hapus');
        }
      });
    });
  }
});
</script>
