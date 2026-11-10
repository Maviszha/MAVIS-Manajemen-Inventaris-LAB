<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

$keyword  = trim($_GET['keyword'] ?? '');
$kelas_id = (int)($_GET['kelas_id'] ?? 0);

$kelasRes = $conn->query("SELECT id, nama FROM kelas WHERE aktif = 1 OR aktif IS NULL ORDER BY nama ASC");

$sql = "
  SELECT p.id, p.tanggal, p.kegiatan, p.kelas_id, k.nama AS nama_kelas,
         (SELECT COUNT(*) FROM presensi_reguler_detail d WHERE d.presensi_id=p.id AND d.hadir=1) AS total_hadir
  FROM presensi_reguler p
  JOIN kelas k ON p.kelas_id = k.id
  WHERE 1=1
";

$params=[]; $types="";
if ($keyword!=='') {
  $sql .= " AND (p.kegiatan LIKE ? OR k.nama LIKE ?) ";
  $kw="%{$keyword}%";
  $params[]=$kw; $params[]=$kw;
  $types.="ss";
}
if ($kelas_id>0){
  $sql .= " AND p.kelas_id=? ";
  $params[]=$kelas_id;
  $types.="i";
}
$sql .= " ORDER BY p.tanggal DESC, p.id DESC";

$stmt=$conn->prepare($sql);
if($types!=="") $stmt->bind_param($types, ...$params);
$stmt->execute();
$res=$stmt->get_result();
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Presensi Reguler</h1>

  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between">
        <div class="mb-2 mb-xl-0">
          <a href="presensi-reguler-add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Presensi Reguler
          </a>
        </div>

        <form method="GET" class="form-inline mt-2 mt-xl-0">
          <input type="text" name="keyword" value="<?= htmlspecialchars($keyword, ENT_QUOTES); ?>"
                 class="form-control mr-2 mb-2 mb-sm-0" placeholder="Cari kegiatan / kelas...">

          <select name="kelas_id" class="form-control mr-2 mb-2 mb-sm-0">
            <option value="0">Semua Kelas</option>
            <?php if($kelasRes): while($k=$kelasRes->fetch_assoc()): ?>
              <option value="<?= (int)$k['id']; ?>" <?= $kelas_id===(int)$k['id']?'selected':''; ?>>
                <?= htmlspecialchars($k['nama'], ENT_QUOTES); ?>
              </option>
            <?php endwhile; endif; ?>
          </select>

          <button class="btn btn-info mb-2 mb-sm-0"><i class="fas fa-search"></i> Cari</button>
        </form>
      </div>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table id="prTable" class="table table-bordered table-striped">
          <thead class="thead-light">
            <tr>
              <th>Tanggal</th>
              <th>Kegiatan</th>
              <th>Kelas</th>
              <th>Total Hadir</th>
              <th style="width:120px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php if($res && $res->num_rows>0): while($r=$res->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($r['tanggal']); ?></td>
              <td><?= htmlspecialchars($r['kegiatan']); ?></td>
              <td><?= htmlspecialchars($r['nama_kelas']); ?></td>
              <td><?= (int)$r['total_hadir']; ?></td>
              <td class="text-center">
                <a class="btn btn-sm btn-success" href="presensi-reguler-session.php?id=<?= (int)$r['id']; ?>">
                  <i class="fas fa-clipboard-check"></i>
                </a>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="5" class="text-center">Belum ada presensi reguler.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
<script>
$(function(){ $('#prTable').DataTable({ pageLength: 25, autoWidth:false, dom:'lrtip' }); });
</script>
