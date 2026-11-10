<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();


// ==== GUARD ROLE (master kelas sebaiknya admin saja) ====
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  http_response_code(403);
  die("Akses ditolak.");
}

// Flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Ambil data kelas
$sql = "SELECT id, nama, aktif, created_at FROM kelas ORDER BY nama ASC";
$res = $conn->query($sql);
if (!$res) {
  die("Query gagal: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MAVIS - Master Kelas</title>

  <!-- ==== SESUAIKAN INCLUDE CSS SB ADMIN 2 ==== -->
  <link href="mavis/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="mavis/assets/css/sb-admin-2.min.css" rel="stylesheet">
  <link href="mavis/assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">

  <!-- ==== SESUAIKAN SIDEBAR KAMU ==== -->
  <?php if (file_exists(__DIR__."mavis/templates/sidebar.php")) include __DIR__."/partials/sidebar.php"; ?>

  <div id="content-wrapper" class="d-flex flex-column">
    <div id="content">

      <!-- ==== SESUAIKAN TOPBAR KAMU ==== -->
      <?php if (file_exists(__DIR__."mavis/templates/header.php")) include __DIR__."/partials/topbar.php"; ?>

      <div class="container-fluid">

        <div class="d-sm-flex align-items-center justify-content-between mb-4">
          <h1 class="h3 mb-0 text-gray-800">Master Kelas</h1>
          <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalTambah">
            <i class="fas fa-plus"></i> Tambah Kelas
          </button>
        </div>

        <?php if ($flash): ?>
          <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
        <?php endif; ?>

        <div class="card shadow mb-4">
          <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Kelas</h6>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered" id="tblKelas" width="100%" cellspacing="0">
                <thead>
                  <tr>
                    <th style="width:70px;">ID</th>
                    <th>Nama Kelas</th>
                    <th style="width:110px;">Status</th>
                    <th style="width:180px;">Dibuat</th>
                    <th style="width:170px;">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while($row = $res->fetch_assoc()): ?>
                    <tr>
                      <td><?= (int)$row['id'] ?></td>
                      <td><?= htmlspecialchars($row['nama']) ?></td>
                      <td>
                        <?php if ((int)$row['aktif'] === 1): ?>
                          <span class="badge badge-success">Aktif</span>
                        <?php else: ?>
                          <span class="badge badge-secondary">Nonaktif</span>
                        <?php endif; ?>
                      </td>
                      <td><?= htmlspecialchars($row['created_at']) ?></td>
                      <td>
                        <button
                          class="btn btn-warning btn-sm"
                          data-toggle="modal"
                          data-target="#modalEdit"
                          data-id="<?= (int)$row['id'] ?>"
                          data-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>"
                          data-aktif="<?= (int)$row['aktif'] ?>"
                        >
                          <i class="fas fa-edit"></i> Edit
                        </button>

                        <button
                          class="btn btn-danger btn-sm"
                          data-toggle="modal"
                          data-target="#modalHapus"
                          data-id="<?= (int)$row['id'] ?>"
                          data-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>"
                        >
                          <i class="fas fa-trash"></i> Hapus
                        </button>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>

            <small class="text-muted">
              Catatan: Hapus kelas akan ditolak bila sudah dipakai oleh data siswa (karena FK RESTRICT).
            </small>
          </div>
        </div>

      </div>
    </div>

    <!-- ==== SESUAIKAN FOOTER KAMU ==== -->
    <?php if (file_exists(__DIR__."mavis/templates/footer.php")) include __DIR__."/partials/footer.php"; ?>
  </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form action="kesiswaan_kelas_action.php" method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Kelas</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="form-group">
          <label>Nama Kelas</label>
          <input type="text" name="nama" class="form-control" placeholder="contoh: X RPL 1" required>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="aktif" class="form-control" required>
            <option value="1" selected>Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form action="kesiswaan_kelas_action.php" method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Kelas</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="edit_id">
        <div class="form-group">
          <label>Nama Kelas</label>
          <input type="text" name="nama" id="edit_nama" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="aktif" id="edit_aktif" class="form-control" required>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit">Update</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="modalHapus" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form action="kesiswaan_kelas_action.php" method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Hapus Kelas</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="hapus_id">
        <p>Yakin hapus kelas: <b id="hapus_nama"></b> ?</p>
        <small class="text-muted">Jika kelas sudah dipakai oleh siswa, penghapusan akan gagal.</small>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
        <button class="btn btn-danger" type="submit">Hapus</button>
      </div>
    </form>
  </div>
</div>

<!-- JS -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

<script>
  $(function () {
    $('#tblKelas').DataTable();

    $('#modalEdit').on('show.bs.modal', function (event) {
      const btn = $(event.relatedTarget);
      $('#edit_id').val(btn.data('id'));
      $('#edit_nama').val(btn.data('nama'));
      $('#edit_aktif').val(btn.data('aktif'));
    });

    $('#modalHapus').on('show.bs.modal', function (event) {
      const btn = $(event.relatedTarget);
      $('#hapus_id').val(btn.data('id'));
      $('#hapus_nama').text(btn.data('nama'));
    });
  });
</script>
</body>
</html>
