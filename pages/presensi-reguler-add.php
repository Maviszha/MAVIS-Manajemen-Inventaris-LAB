<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

$kelasRes = $conn->query("SELECT id, nama FROM kelas WHERE aktif = 1 OR aktif IS NULL ORDER BY nama ASC");
$fail = $_GET['fail'] ?? '';
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Buat Presensi Reguler</h1>

  <?php if ($fail): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($fail, ENT_QUOTES); ?></div>
  <?php endif; ?>

  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Form Presensi Reguler</h6>
      <a href="presensi-reguler-list.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <form method="POST" action="presensi-reguler-add-process.php">
      <div class="card-body">
        <div class="form-group">
          <label>Tanggal <span class="text-danger">*</span></label>
          <input type="date" name="tanggal" class="form-control" required>
        </div>

        <div class="form-group">
          <label>Kegiatan <span class="text-danger">*</span></label>
          <input type="text" name="kegiatan" class="form-control" value="Presensi Reguler" required>
        </div>

        <div class="form-group">
          <label>Kelas <span class="text-danger">*</span></label>
          <select name="kelas_id" class="form-control" required>
            <option value="">-- Pilih Kelas --</option>
            <?php if ($kelasRes): while($k=$kelasRes->fetch_assoc()): ?>
              <option value="<?= (int)$k['id']; ?>"><?= htmlspecialchars($k['nama'], ENT_QUOTES); ?></option>
            <?php endwhile; endif; ?>
          </select>
        </div>
      </div>

      <div class="card-footer text-right">
        <button class="btn btn-primary"><i class="fas fa-save"></i> Buat Presensi</button>
      </div>
    </form>
  </div>
</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
