<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

$id = $_GET['id'] ?? '';
if ($id==='' || !ctype_digit($id)) { header("Location: presensi-praktikum-list.php"); exit; }
$id = (int)$id;

$stmt = $conn->prepare("
  SELECT p.*, k.nama AS nama_kelas
  FROM presensi_praktikum p
  JOIN kelas k ON p.kelas_id = k.id
  WHERE p.id = ? LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$p){ header("Location: presensi-praktikum-list.php"); exit; }

$msg    = $_GET['msg'] ?? '';
$scanok = $_GET['scan'] ?? '';
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-gray-800 mb-0">Scan Presensi Praktikum</h1>
      <div class="text-muted">
        <b><?= htmlspecialchars($p['nama_kelas']); ?></b> · <?= htmlspecialchars($p['tanggal']); ?> · <?= htmlspecialchars($p['sesi']); ?>
      </div>
    </div>
    <div>
      <a href="presensi-praktikum-report.php?id=<?= (int)$id; ?>" class="btn btn-success">
        <i class="fas fa-clipboard-check"></i> Laporan & Checklist Laporan
      </a>
      <a href="presensi-praktikum-list.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
    </div>
  </div>

  <?php if ($scanok): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>Scan OK: <?= htmlspecialchars($scanok, ENT_QUOTES); ?></div>
  <?php endif; ?>
  <?php if ($msg): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($msg, ENT_QUOTES); ?></div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-4">
      <div class="card shadow mb-4">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-primary">Scan Barcode (NIS)</h6>
        </div>
        <div class="card-body">
          <form method="POST" action="presensi-praktikum-scan-process.php">
            <input type="hidden" name="presensi_id" value="<?= (int)$id; ?>">
            <div class="form-group">
              <input type="text" name="barcode" id="barcode"
                     class="form-control form-control-lg"
                     placeholder="Scan NIS..." autofocus required>
              <small class="text-muted">Scanner biasanya mengirim Enter. Fokuskan ke input ini.</small>
            </div>
            <button class="btn btn-primary btn-block"><i class="fas fa-qrcode"></i> Proses Scan</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card shadow mb-4">
        <div class="card-header py-3">
          <h6 class="m-0 font-weight-bold text-primary">Daftar Siswa (Status)</h6>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="scanTable" class="table table-bordered table-striped">
              <thead class="thead-light">
                <tr>
                  <th>NIS</th>
                  <th>Nama</th>
                  <th>Hadir Praktikum</th>
                  <th>Laporan</th>
                  <th>Keterangan</th>
                </tr>
              </thead>
              <tbody>
              <?php
              $q = $conn->prepare("
                SELECT s.nis, s.nama,
                       d.hadir_praktikum, d.laporan, d.keterangan_otomatis
                FROM presensi_praktikum_detail d
                JOIN siswa s ON d.siswa_id = s.id
                WHERE d.presensi_id = ?
                ORDER BY s.nama ASC
              ");
              $q->bind_param("i", $id);
              $q->execute();
              $rs = $q->get_result();
              $q->close();

              while($r = $rs->fetch_assoc()):
                $b1 = $r['hadir_praktikum'] ? "<span class='badge badge-success'>YA</span>" : "<span class='badge badge-secondary'>-</span>";
                $b2 = $r['laporan'] ? "<span class='badge badge-info'>YA</span>" : "<span class='badge badge-secondary'>-</span>";
              ?>
                <tr>
                  <td><?= htmlspecialchars($r['nis']); ?></td>
                  <td><?= htmlspecialchars($r['nama']); ?></td>
                  <td><?= $b1; ?></td>
                  <td><?= $b2; ?></td>
                  <td><?= htmlspecialchars($r['keterangan_otomatis']); ?></td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
<script>
$(function(){
  $('#scanTable').DataTable({ pageLength: 25, autoWidth:false, dom:'lrtip' });
  setTimeout(function(){ $('#barcode').focus(); }, 200);
});
</script>
