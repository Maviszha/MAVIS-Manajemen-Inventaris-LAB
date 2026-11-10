<?php
// pages/data-siswa-exim.php
if (session_status() === PHP_SESSION_NONE) session_start();

include dirname(__DIR__) . "/templates/header.php";

// Guard role
$role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? null);
if (!in_array($role, ['admin','guru'], true)) {
  http_response_code(403);
  exit('Akses ditolak.');
}

function h($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// kelas aktif untuk filter export
$kelas = [];
$q = $conn->query("SELECT id, nama FROM kelas WHERE aktif=1 ORDER BY nama ASC");
if ($q) while ($r = $q->fetch_assoc()) $kelas[] = $r;
?>

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800">Export / Import Data Siswa</h1>
    <a href="/mavis/pages/data-siswa.php" class="btn btn-secondary btn-sm">
      <i class="fas fa-arrow-left"></i> Kembali ke Data Siswa
    </a>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= h($flash['type'] ?? 'info') ?> shadow-sm">
      <?= h($flash['message'] ?? '') ?>
    </div>
  <?php endif; ?>

  <div class="row">

    <!-- EXPORT -->
    <div class="col-lg-6 mb-4">
      <div class="card shadow">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
          <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-file-export mr-1"></i> Export CSV
          </h6>
        </div>

        <div class="card-body">
          <form id="exportSiswaForm" action="export-siswa-process.php" method="POST">

            <div class="form-group">
              <label>Format Unduh</label>
              <select name="format" class="form-control" required>
                <option value="csv" selected>CSV</option>
              </select>
              <small class="form-text text-muted">
                Sementara CSV dulu, lagian export ini kayake ga butuh2 banget kan dah punya data siswa, ya kan,,,</b>.
              </small>
            </div>

            <div class="form-group mt-3">
              <label>Filter Kelas (opsional)</label>
              <select name="kelas_id" class="form-control">
                <option value="">Semua Kelas</option>
                <?php foreach($kelas as $k): ?>
                  <option value="<?= (int)$k['id'] ?>"><?= h($k['nama']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group mt-3">
              <label>Keyword (opsional)</label>
              <input type="text" name="keyword" class="form-control" placeholder="Cari NIS / Nama...">
            </div>

            <div class="form-group mt-3">
              <label>Sertakan Nama Kolom di Baris Pertama?</label><br>
              <label class="mr-2">
                <input type="checkbox" name="include_header" value="1" checked>
                <span class="ml-1">Ya, sertakan</span>
              </label>
            </div>

            <button type="submit" class="btn btn-danger mt-3">
              <i class="fas fa-download"></i> Download CSV (Export)
            </button>

          </form>
        </div>
      </div>
    </div>

    <!-- IMPORT -->
    <div class="col-lg-6 mb-4">
      <div class="card shadow">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
          <h6 class="m-0 font-weight-bold text-success">
            <i class="fas fa-file-import mr-1"></i> Import CSV
          </h6>
        </div>

        <div class="card-body">

          <!-- Template ada di IMPORT -->
          <a href="template-siswa-process.php" class="btn btn-warning btn-block mb-3">
            <i class="fas fa-file-download"></i> Download Template CSV
          </a>

          <form id="importSiswaForm" action="import-siswa-process.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
              <label>Pilih File CSV</label>
              <input type="file" name="csv" accept=".csv" class="form-control" required>
              <small class="form-text text-muted">
                Format?? gausah lah ya, kita semua males bikin file manual, pake tempalet aja tuh di atas tinggal copas isiannya.
              </small>
            </div>

            <button type="submit" class="btn btn-success btn-block mt-3">
              <i class="fas fa-upload"></i> Import Sekarang
            </button>
          </form>

        </div>
      </div>
    </div>

  </div>
</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>

<script>
document.getElementById("exportSiswaForm")?.addEventListener("submit", function(e) {
  if (!confirm("Yakin ingin mengekspor data siswa sekarang?")) e.preventDefault();
});
document.getElementById("importSiswaForm")?.addEventListener("submit", function(e) {
  if (!confirm("Yakin ingin mengimpor data siswa sekarang?")) e.preventDefault();
});
</script>
