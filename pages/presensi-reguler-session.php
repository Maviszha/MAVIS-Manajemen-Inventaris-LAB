<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

$id = $_GET['id'] ?? '';
if ($id==='' || !ctype_digit($id)) { header("Location: presensi-reguler-list.php"); exit; }
$id = (int)$id;

$stmt = $conn->prepare("
  SELECT p.*, k.nama AS nama_kelas
  FROM presensi_reguler p
  JOIN kelas k ON p.kelas_id = k.id
  WHERE p.id=? LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$p){ header("Location: presensi-reguler-list.php"); exit; }

$update = $_GET['update'] ?? '';
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-gray-800 mb-0">Presensi Reguler</h1>
      <div class="text-muted">
        <b><?= htmlspecialchars($p['nama_kelas']); ?></b> · <?= htmlspecialchars($p['tanggal']); ?> · <?= htmlspecialchars($p['kegiatan']); ?>
      </div>
    </div>
    <div>
      <a href="presensi-reguler-list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> List</a>
    </div>
  </div>

  <?php if($update): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>Presensi reguler berhasil disimpan.</div>
  <?php endif; ?>

  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">Checklist Hadir + Catatan</h6>
    </div>

    <form method="POST" action="presensi-reguler-session-process.php">
      <input type="hidden" name="presensi_id" value="<?= (int)$id; ?>">

      <div class="card-body">
        <div class="table-responsive">
          <table id="regTable" class="table table-bordered table-striped">
            <thead class="thead-light">
              <tr>
                <th>NIS</th>
                <th>Nama</th>
                <th>Hadir</th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $q = $conn->prepare("
                SELECT s.id AS siswa_id, s.nis, s.nama, d.hadir, d.catatan
                FROM presensi_reguler_detail d
                JOIN siswa s ON d.siswa_id = s.id
                WHERE d.presensi_id = ?
                ORDER BY s.nama ASC
              ");
              $q->bind_param("i", $id);
              $q->execute();
              $rs = $q->get_result();
              $q->close();

              while($r=$rs->fetch_assoc()):
              ?>
                <tr>
                  <td><?= htmlspecialchars($r['nis']); ?></td>
                  <td><?= htmlspecialchars($r['nama']); ?></td>
                  <td class="text-center">
                    <input type="checkbox" name="hadir[<?= (int)$r['siswa_id']; ?>]" value="1" <?= $r['hadir']?'checked':''; ?>>
                  </td>
                  <td>
                    <input type="text" class="form-control form-control-sm"
                           name="catatan[<?= (int)$r['siswa_id']; ?>]"
                           value="<?= htmlspecialchars($r['catatan'] ?? '', ENT_QUOTES); ?>"
                           placeholder="Opsional...">
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-right">
        <button class="btn btn-primary"><i class="fas fa-save"></i> Simpan Presensi</button>
      </div>
    </form>
  </div>
</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
<script>
$(function(){ $('#regTable').DataTable({ pageLength: 25, autoWidth:false, dom:'lrtip' }); });
</script>
