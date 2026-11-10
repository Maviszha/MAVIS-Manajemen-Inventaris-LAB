<?php
// pages/import-alat.php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

$success = $_GET['success'] ?? '';
$fail    = $_GET['fail'] ?? '';
?>

<div class="container-fluid">

  <h1 class="h3 mb-4 text-gray-800">Unggah Data Alat Laboratorium (CSV)</h1>

  <?php if ($success): ?>
    <div class="alert alert-success shadow-sm">
      <i class="fas fa-check-circle mr-2"></i> Import selesai.
    </div>
  <?php endif; ?>

  <?php if ($fail): ?>
    <div class="alert alert-danger shadow-sm">
      <i class="fas fa-exclamation-triangle mr-2"></i> Import gagal: <?= htmlspecialchars($fail, ENT_QUOTES, 'UTF-8'); ?>
    </div>
  <?php endif; ?>

  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Form Import</h6>

      <!-- Template harus di sisi IMPORT -->
      <a class="btn btn-sm btn-outline-primary" href="import-alat-template.php">
        <i class="fas fa-download"></i> Download Template CSV
      </a>
    </div>

    <div class="card-body">
      <form id="importForm" action="import-alat-process.php" method="POST" enctype="multipart/form-data">

        <div class="form-group">
          <label>File CSV</label>
          <input type="file" name="csv" class="form-control" accept=".csv,text/csv" required>
          <small class="form-text text-muted">
            Kolom wajib mengikuti template. Kolom pertama adalah <b>no_inventaris</b>.
          </small>
        </div>

        <div class="form-group mt-3">
          <label>Baris pertama adalah header?</label><br>
          <label class="mr-2">
            <input type="checkbox" name="has_header" value="1" checked>
            <span class="ml-1">Ya</span>
          </label>
        </div>

        <button type="submit" class="btn btn-primary mt-2">
          <i class="fas fa-file-import"></i> Import Sekarang
        </button>

        <a href="alat-list.php" class="btn btn-secondary mt-2">
          Kembali ke Daftar Alat
        </a>

      </form>
    </div>
  </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>

<script>
document.getElementById("importForm").addEventListener("submit", function(e) {
  if (!confirm("Yakin ingin mengimpor data alat dari CSV?")) e.preventDefault();
});
</script>
