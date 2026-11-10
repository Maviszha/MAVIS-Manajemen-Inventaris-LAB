<?php
// pages/emodul-list.php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$success = $_GET['success'] ?? '';
$deleted = $_GET['deleted'] ?? '';

// Ambil semua e-modul
$sql  = "SELECT e.*, k.nama AS kelas_nama FROM e_modul e LEFT JOIN kelas k ON k.id = e.kelas_id ORDER BY e.tanggal DESC, e.id DESC";
$res  = $conn->query($sql);
$rows = [];
if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
?>

<div class="container-fluid">

  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Daftar E-Modul Praktikum</h1>
    <div>
      <a href="/mavis/public/emodul.php" target="_blank" class="btn btn-sm btn-outline-info shadow-sm mr-2">
        <i class="fas fa-external-link-alt fa-sm"></i> Lihat Halaman Publik
      </a>
      <a href="emodul-add.php" class="btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm"></i> Upload E-Modul
      </a>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fas fa-check-circle mr-1"></i> E-Modul berhasil disimpan!
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>
  <?php if ($deleted): ?>
    <div class="alert alert-warning alert-dismissible fade show">
      <i class="fas fa-trash mr-1"></i> E-Modul berhasil dihapus.
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>

  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">
        <i class="fas fa-book-open mr-1"></i> Data E-Modul
      </h6>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover" id="tblEmodul" width="100%" cellspacing="0">
          <thead class="thead-light">
            <tr>
              <th style="width:40px">#</th>
              <th>Judul E-Modul</th>
              <th>Penulis</th>
              <th>Kelas</th>
              <th>Tanggal</th>
              <th class="text-center" style="width:70px">Gambar</th>
              <th class="text-center" style="width:60px">Video</th>
              <th class="text-center" style="width:90px">File</th>
              <th>Keterangan</th>
              <th class="text-center" style="width:100px">Laporan</th>
              <th class="text-center" style="width:80px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$rows): ?>
              <tr><td colspan="10" class="text-center text-muted py-4">Belum ada e-modul.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $i => $row): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td class="font-weight-bold"><?= h($row['judul']) ?></td>
                <td><?= h($row['penulis']) ?></td>
                <td><?= h($row['kelas_nama'] ?? '-') ?></td>
                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>

                <!-- Ceklis Gambar -->
                <td class="text-center">
                  <?php if ($row['gambar_path']): ?>
                    <i class="fas fa-check-circle text-success fa-lg" title="Ada gambar"></i>
                  <?php else: ?>
                    <i class="fas fa-times-circle text-secondary fa-lg"></i>
                  <?php endif; ?>
                </td>

                <!-- Ceklis Video -->
                <td class="text-center">
                  <?php if (!empty($row['link_video_emodul'])): ?>
                    <a href="<?= h($row['link_video_emodul']) ?>" target="_blank" title="Buka Video">
                      <i class="fas fa-check-circle text-success fa-lg"></i>
                    </a>
                  <?php else: ?>
                    <i class="fas fa-times-circle text-secondary fa-lg"></i>
                  <?php endif; ?>
                </td>

                <!-- File E-Modul -->
                <td class="text-center">
                  <?php
                    $ext   = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));
                    $isPpt = in_array($ext, ['ppt','pptx']);
                  ?>
                  <?php if ($isPpt): ?>
                    <a href="/mavis/<?= h($row['file_path']) ?>" download
                       class="btn btn-xs btn-outline-warning py-0 px-2" title="Unduh PPT">
                      <i class="fas fa-file-powerpoint"></i> <?= strtoupper($ext) ?>
                    </a>
                  <?php else: ?>
                    <a href="/mavis/<?= h($row['file_path']) ?>" target="_blank"
                       class="btn btn-xs btn-outline-primary py-0 px-2" title="Buka PDF">
                      <i class="fas fa-file-pdf"></i> PDF
                    </a>
                  <?php endif; ?>
                </td>

                <!-- Keterangan -->
                <td style="font-size:13px;max-width:200px;">
                  <?= nl2br(h($row['keterangan'] ?? '')) ?>
                </td>

                <!-- Laporan Kelompok -->
                <td class="text-center">
                  <button class="btn btn-xs btn-info py-0 px-2"
                          onclick="bukaLaporan(<?= (int)$row['id'] ?>, '<?= h($row['judul']) ?>')">
                    <i class="fas fa-users"></i> Laporan
                  </button>
                </td>

                <!-- Aksi -->
                <td class="text-center">
                  <a href="emodul-edit.php?id=<?= (int)$row['id'] ?>"
                     class="btn btn-xs btn-warning py-0 px-2 mr-1" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  <a href="emodul-delete.php?id=<?= (int)$row['id'] ?>"
                     class="btn btn-xs btn-danger py-0 px-2"
                     onclick="return confirm('Yakin ingin menghapus e-modul ini? Semua laporan terkait juga akan dihapus.')">
                    <i class="fas fa-trash"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- ===== MODAL LAPORAN KELOMPOK ===== -->
<div class="modal fade" id="modalLaporan" tabindex="-1" role="dialog" aria-labelledby="modalLaporanLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="modalLaporanLabel">
          <i class="fas fa-users mr-1"></i> Laporan Kelompok —
          <span id="judulEmodul"></span>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="modalLaporanBody">
        <div class="text-center py-4">
          <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
          <p class="mt-2 text-muted">Memuat data...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ===== MODAL PREVIEW PDF LAPORAN ===== -->
<div class="modal fade" id="modalPdfLaporan" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content" style="height:90vh;">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-file-pdf mr-1 text-danger"></i> Laporan Praktikum</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-0" style="height:calc(100% - 60px);">
        <iframe id="iframePdf" src="" style="width:100%;height:100%;border:none;"></iframe>
      </div>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>

<style>
.btn-xs { font-size: .75rem; }
</style>

<script>
function bukaLaporan(emId, judul) {
  document.getElementById('judulEmodul').textContent = judul;
  document.getElementById('modalLaporanBody').innerHTML =
    '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">Memuat data...</p></div>';
  $('#modalLaporan').modal('show');

  fetch('/mavis/pages/emodul-laporan-ajax.php?emodul_id=' + emId)
    .then(r => r.text())
    .then(html => { document.getElementById('modalLaporanBody').innerHTML = html; })
    .catch(() => {
      document.getElementById('modalLaporanBody').innerHTML =
        '<div class="alert alert-danger">Gagal memuat data laporan.</div>';
    });
}

function bukaPdfLaporan(kelompokId, namaKelompok) {
  document.querySelector('#modalPdfLaporan .modal-title').innerHTML =
    '<i class="fas fa-file-pdf mr-1 text-danger"></i> Laporan: ' + namaKelompok;
  document.getElementById('iframePdf').src =
    '/mavis/pages/emodul-laporan-pdf.php?id=' + kelompokId;
  $('#modalPdfLaporan').modal('show');
}
</script>

<?php if (!$rows): ?>
<script>
$(document).ready(function(){ /* DataTables optional */ });
</script>
<?php else: ?>
<script>
$(document).ready(function(){
  $('#tblEmodul').DataTable({
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
    },
    order: [[3, 'desc']],
    columnDefs: [{ orderable: false, targets: [4,5,6,7,8] }]
  });
});
</script>
<?php endif; ?>
