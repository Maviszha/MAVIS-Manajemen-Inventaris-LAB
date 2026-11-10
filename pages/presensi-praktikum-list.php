<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

$keyword  = trim($_GET['keyword'] ?? '');
$kelas_id = (int)($_GET['kelas_id'] ?? 0);

// dropdown kelas
$kelasRes = $conn->query("SELECT id, nama FROM kelas WHERE aktif = 1 OR aktif IS NULL ORDER BY nama ASC");

// query list
$sql = "
  SELECT p.id, p.tanggal, p.sesi, p.kelas_id, p.public_token,
         k.nama AS nama_kelas,
         (SELECT COUNT(*) FROM presensi_praktikum_detail d WHERE d.presensi_id=p.id AND d.hadir_praktikum=1) AS total_hadir,
         (SELECT COUNT(*) FROM presensi_praktikum_detail d WHERE d.presensi_id=p.id AND d.laporan=1) AS total_laporan,
         (SELECT COUNT(*) FROM presensi_praktikum_detail d WHERE d.presensi_id=p.id AND d.hadir_praktikum=1 AND d.laporan=1) AS total_lengkap
  FROM presensi_praktikum p
  JOIN kelas k ON p.kelas_id = k.id
  WHERE 1=1
";

$params = [];
$types  = "";

if ($keyword !== '') {
  $sql .= " AND (p.sesi LIKE ? OR k.nama LIKE ?) ";
  $kw = "%{$keyword}%";
  $params[] = $kw; $params[] = $kw;
  $types .= "ss";
}
if ($kelas_id > 0) {
  $sql .= " AND p.kelas_id = ? ";
  $params[] = $kelas_id;
  $types .= "i";
}

$sql .= " ORDER BY p.tanggal DESC, p.id DESC ";

$stmt = $conn->prepare($sql);
if ($types !== "") $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$msg = $_GET['msg'] ?? '';
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Presensi Praktikum</h1>

  <?php if ($msg === 'deleted'): ?>
    <div class="alert alert-warning alert-auto-close shadow-sm">
      <i class="fas fa-trash-alt mr-2"></i> Sesi presensi praktikum berhasil <b>dihapus</b>.
    </div>
  <?php elseif ($msg === 'notfound'): ?>
    <div class="alert alert-danger alert-auto-close shadow-sm">
      <i class="fas fa-exclamation-triangle mr-2"></i> Data sesi tidak ditemukan.
    </div>
  <?php endif; ?>

  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between">
        <div class="mb-2 mb-xl-0">
          <a href="presensi-praktikum-add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Sesi Praktikum
          </a>
        </div>

        <form method="GET" class="form-inline mt-2 mt-xl-0">
          <input type="text" name="keyword" value="<?= htmlspecialchars($keyword, ENT_QUOTES); ?>"
                 class="form-control mr-2 mb-2 mb-sm-0" placeholder="Cari sesi / kelas...">

          <select name="kelas_id" class="form-control mr-2 mb-2 mb-sm-0">
            <option value="0">Semua Kelas</option>
            <?php if ($kelasRes): while($k = $kelasRes->fetch_assoc()): ?>
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
        <table id="ppTable" class="table table-bordered table-striped">
          <thead class="thead-light">
            <tr>
              <th>Tanggal</th>
              <th>Sesi</th>
              <th>Kelas</th>
              <th>Hadir</th>
              <th>Laporan</th>
              <th>Lengkap</th>
              <th style="width:190px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($res && $res->num_rows>0): while($r=$res->fetch_assoc()): ?>
              <?php
                $token = $r['public_token'] ?? '';
                $scanUrl = $token ? ("/mavis/public/presensi-praktikum-scan.php?t=" . urlencode($token)) : ("presensi-praktikum-session.php?id=" . (int)$r['id']);
              ?>
              <tr>
                <td><?= htmlspecialchars($r['tanggal']); ?></td>
                <td><?= htmlspecialchars($r['sesi']); ?></td>
                <td><?= htmlspecialchars($r['nama_kelas']); ?></td>
                <td><?= (int)$r['total_hadir']; ?></td>
                <td><?= (int)$r['total_laporan']; ?></td>
                <td><?= (int)$r['total_lengkap']; ?></td>
                <td class="text-center">
                  <!-- Scan dibuka di TAB BARU dan menggunakan halaman PUBLIC (glass), bukan UI admin -->
                  <a class="btn btn-sm btn-primary" target="_blank" rel="noopener noreferrer"
                     href="<?= htmlspecialchars($scanUrl, ENT_QUOTES); ?>" title="Scan (Tab Baru)">
                    <i class="fas fa-qrcode"></i>
                  </a>

                  <!-- Laporan tetap di admin -->
                  <a class="btn btn-sm btn-success" target="_blank" rel="noopener noreferrer"
                     href="presensi-praktikum-report.php?id=<?= (int)$r['id']; ?>" title="Laporan (Tab Baru)">
                    <i class="fas fa-clipboard-check"></i>
                  </a>

                  <form method="POST" action="presensi-praktikum-delete-process.php" class="d-inline">
                    <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                            onclick="return confirm('Yakin ingin menghapus sesi presensi praktikum ini? Data detailnya juga akan terhapus.');"
                            title="Hapus">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="7" class="text-center">Belum ada sesi praktikum.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
<script>
$(function(){
  $('#ppTable').DataTable({ pageLength: 25, autoWidth:false, dom:'lrtip' });

  // Auto close alert
  setTimeout(function(){
    $('.alert-auto-close').fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
  }, 3500);
});
</script>
