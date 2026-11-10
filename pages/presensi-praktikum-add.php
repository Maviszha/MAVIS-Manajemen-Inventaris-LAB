<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

// dropdown kelas
$kelasRes = $conn->query("SELECT id, nama FROM kelas WHERE aktif = 1 OR aktif IS NULL ORDER BY nama ASC");

// notif
$success = $_GET['success'] ?? '';
$fail    = $_GET['fail'] ?? '';
$token   = $_GET['token'] ?? '';
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Buat Sesi Presensi Praktikum</h1>

  <?php if ($success): ?>
    <div class="alert alert-success shadow-sm">
      <i class="fas fa-check-circle mr-2"></i>
      Sesi praktikum berhasil dibuat.
      <?php if ($token): ?>
        <a class="btn btn-sm btn-light ml-2"
           target="_blank"
           rel="noopener noreferrer"
           href="/mavis/public/presensi-praktikum-scan.php?t=<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
          <i class="fas fa-external-link-alt mr-1"></i> Buka Halaman Scan
        </a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($fail): ?>
    <div class="alert alert-danger shadow-sm">
      <i class="fas fa-exclamation-triangle mr-2"></i>
      <?= htmlspecialchars($fail, ENT_QUOTES, 'UTF-8'); ?>
    </div>
  <?php endif; ?>

  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Form Sesi Praktikum</h6>
      <a href="presensi-praktikum-list.php" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
    </div>

    <form method="POST" action="presensi-praktikum-add-process.php" id="formBuatSesi">
      <div class="card-body">
        <div class="form-group">
          <label>Tanggal Praktikum <span class="text-danger">*</span></label>
          <input type="date" name="tanggal" class="form-control" required>
        </div>

        <div class="form-group">
          <label>Sesi Praktikum <span class="text-danger">*</span></label>
          <input type="text" name="sesi" class="form-control" placeholder="Contoh: Praktikum 1 - Asam Basa" required>
        </div>

        <div class="form-group">
          <label>Kelas <span class="text-danger">*</span></label>
          <select name="kelas_id" class="form-control" required>
            <option value="">-- Pilih Kelas --</option>
            <?php if ($kelasRes): while($k=$kelasRes->fetch_assoc()): ?>
              <option value="<?= (int)$k['id']; ?>"><?= htmlspecialchars($k['nama'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endwhile; endif; ?>
          </select>
        </div>
      </div>

      <div class="card-footer text-right">
        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Buat Sesi</button>
      </div>
    </form>
  </div>
</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
