<?php
// pages/export-alat.php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();
?>

<div class="container-fluid">

  <h1 class="h3 mb-4 text-gray-800">Unduh Data Alat Laboratorium</h1>

  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">Pengaturan Unduh</h6>
    </div>

    <div class="card-body">
      <form id="exportForm" action="export-alat-process.php" method="POST">

        <div class="form-group">
          <label>Format Unduh</label>
          <select name="format" class="form-control" required>
            <option value="csv" selected>CSV</option>
          </select>
          <small class="form-text text-muted">
            Export berisi kolom termasuk <b>No Inventaris</b>.
          </small>
        </div>

        <div class="form-group mt-3">
          <label>Filter Tanggal Pengadaan (berdasarkan kolom <code>tahun_pengadaan</code>)</label>
          <div class="row">
            <div class="col-md-6">
              <label class="small mb-1">Dari Tanggal</label>
              <input type="date" name="start_date" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="small mb-1">Sampai Tanggal</label>
              <input type="date" name="end_date" class="form-control">
            </div>
          </div>
          <small class="form-text text-muted">
            Jika dikosongkan, semua data akan diunduh tanpa filter tanggal.
          </small>
        </div>

        <div class="form-group mt-3">
          <label>Sertakan Nama Kolom di Baris Pertama?</label><br>
          <label class="mr-2">
            <input type="checkbox" name="include_header" value="1" checked>
            <span class="ml-1">Ya, sertakan</span>
          </label>
        </div>

        <button type="submit" class="btn btn-primary mt-3">
          <i class="fas fa-file-export"></i> Unduh Sekarang
        </button>

        <a href="alat-list.php" class="btn btn-secondary mt-3">
          Kembali ke Daftar Alat
        </a>

      </form>
    </div>
  </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>

<script>
document.getElementById("exportForm").addEventListener("submit", function(e) {
  if (!confirm("Yakin ingin mengekspor data alat sekarang?")) e.preventDefault();
});
</script>
