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

$update = $_GET['update'] ?? '';
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-gray-800 mb-0">Laporan Presensi Praktikum</h1>
      <div class="text-muted">
        <b><?= htmlspecialchars($p['nama_kelas']); ?></b> · <?= htmlspecialchars($p['tanggal']); ?> · <?= htmlspecialchars($p['sesi']); ?>
      </div>
    </div>
    <div>
      <a href="presensi-praktikum-session.php?id=<?= (int)$id; ?>" class="btn btn-primary">
        <i class="fas fa-qrcode"></i> Kembali ke Scan
      </a>
      <a href="presensi-praktikum-list.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> List
      </a>
    </div>
  </div>

  <?php if ($update): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>Checklist laporan berhasil disimpan.</div>
  <?php endif; ?>

  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-success">Checklist Laporan + Catatan</h6>
    </div>

    <form method="POST" action="presensi-praktikum-report-process.php">
      <input type="hidden" name="presensi_id" value="<?= (int)$id; ?>">

      <div class="card-body">
        <div class="table-responsive">
          <table id="repTable" class="table table-bordered table-striped">
            <thead class="thead-light">
              <tr>
                <th>NIS</th>
                <th>Nama</th>
                <th>Hadir Praktikum</th>
                <th>Laporan</th>
                <th>Catatan</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $q = $conn->prepare("
                SELECT s.id AS siswa_id, s.nis, s.nama,
                       COALESCE(d.hadir_praktikum, 0) AS hadir_praktikum,
                       COALESCE(d.laporan, 0) AS laporan,
                       COALESCE(d.catatan, '') AS catatan,
                       COALESCE(d.keterangan_otomatis, '') AS keterangan_otomatis
                FROM siswa s
                LEFT JOIN presensi_praktikum_detail d
                  ON d.siswa_id = s.id AND d.presensi_id = ?
                WHERE s.kelas_id = ?
                ORDER BY s.nama ASC
              ");
              $q->bind_param("ii", $id, $p['kelas_id']);
              $q->execute();
              $rs = $q->get_result();
              $q->close();

              while($r = $rs->fetch_assoc()):
                $hadirBadge = $r['hadir_praktikum'] ? "<span class='badge badge-success'>YA</span>" : "<span class='badge badge-secondary'>-</span>";
              ?>
                <tr>
                  <td><?= htmlspecialchars($r['nis']); ?></td>
                  <td><?= htmlspecialchars($r['nama']); ?></td>
                  <td><?= $hadirBadge; ?></td>

                  <td class="text-center">
                    <input type="checkbox"
                           name="laporan[<?= (int)$r['siswa_id']; ?>]"
                           value="1" <?= $r['laporan'] ? 'checked' : ''; ?>>
                  </td>

                  <td>
                    <input type="text"
                           name="catatan[<?= (int)$r['siswa_id']; ?>]"
                           class="form-control form-control-sm"
                           value="<?= htmlspecialchars($r['catatan'] ?? '', ENT_QUOTES); ?>"
                           placeholder="Opsional...">
                  </td>

                  <td><?= htmlspecialchars($r['keterangan_otomatis']); ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-right">
        <button class="btn btn-success"><i class="fas fa-save"></i> Simpan Checklist Laporan</button>
      </div>
    </form>
  </div>
</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
<script>
$(function(){
  $('#repTable').DataTable({ pageLength: 25, autoWidth:false, dom:'lrtip' });
});
</script>
