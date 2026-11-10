<?php
// Header SB Admin 2 (sudah include db & auth)
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

$openModal   = $_GET['open'] ?? '';
$lokasiAdded = $_GET['lokasi_added'] ?? '';
$lokasiUpd   = $_GET['lokasi_updated'] ?? '';
$lokasiDel   = $_GET['lokasi_deleted'] ?? '';
$lokasiUsed  = $_GET['lokasi_used'] ?? '';
$sumberAdded = $_GET['sumber_added'] ?? '';
$sumberUpd   = $_GET['sumber_updated'] ?? '';
$sumberDel   = $_GET['sumber_deleted'] ?? '';
$sumberUsed  = $_GET['sumber_used'] ?? '';

// Ambil lokasi
$lokasiResult = $conn->query("SELECT id, nama_lokasi FROM lokasi_alat ORDER BY nama_lokasi ASC");

// Ambil sumber pengadaan
$sumberResult = $conn->query("SELECT id, nama_sumber FROM sumber_pengadaan ORDER BY id ASC");
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Tambah Data Alat Laboratorium</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Input Data Alat</h6>
        </div>

        <form action="alat-add-process.php" method="POST">
            <div class="card-body">
                <div class="row">

                    <!-- =================== KOLOM KIRI =================== -->
                    <div class="col-md-6">

                        <div class="form-group">
                            <label>Nama Alat</label>
                            <input type="text" name="nama_alat" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Merk</label>
                            <input type="text" name="merk_alat" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Spesifikasi</label>
                            <textarea name="spesifikasi" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Lokasi Penyimpanan</label>
                            <div class="input-group">
                                <select id="lokasiSelect" name="lokasi_id" class="form-control" required>
                                    <option value="">-- Pilih Lokasi --</option>
                                    <?php if ($lokasiResult && $lokasiResult->num_rows > 0): ?>
                                        <?php while ($l = $lokasiResult->fetch_assoc()): ?>
                                            <option value="<?= (int)$l['id']; ?>">
                                                <?= htmlspecialchars($l['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="input-group-append">
                                    <button type="button"
                                            class="btn btn-info"
                                            data-toggle="modal"
                                            data-target="#modalLokasi">
                                        Kelola Lokasi
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Tahun Pengadaan</label>
                            <input type="date"
                                   name="tahun_pengadaan"
                                   class="form-control"
                                   min="2000-01-01"
                                   max="2099-12-31"
                                   required>
                        </div>

                    </div>

                    <!-- =================== KOLOM KANAN =================== -->
                    <div class="col-md-6">

                        <!-- ✅ NO INVENTARIS -->
                        <div class="form-group">
                            <label>No Inventaris</label>
                            <input type="text"
                                   name="no_inventaris"
                                   class="form-control"
                                   maxlength="100"
                                   placeholder="Contoh: INV-2025-001">
                            <small class="text-muted">Opsional. Boleh dikosongkan.</small>
                        </div>

                        <!-- ✅ TOTAL OTOMATIS -->
                        <div class="form-group">
                            <label>Jumlah Total</label>
                            <input type="number"
                                   name="jumlah_total"
                                   id="jumlah_total"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="0"
                                   readonly>
                            <small class="text-muted">Terisi otomatis dari Baik + RB + RR + Hilang</small>
                        </div>

                        <div class="form-group">
                            <label>Jumlah Baik</label>
                            <input type="number"
                                   name="jumlah_baik"
                                   id="jumlah_baik"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="0"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Rusak Berat</label>
                            <input type="number"
                                   name="jumlah_rusak_berat"
                                   id="jumlah_rusak_berat"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="0">
                        </div>

                        <div class="form-group">
                            <label>Rusak Ringan</label>
                            <input type="number"
                                   name="jumlah_rusak_ringan"
                                   id="jumlah_rusak_ringan"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="0">
                        </div>

                        <div class="form-group">
                            <label>Hilang</label>
                            <input type="number"
                                   name="jumlah_hilang"
                                   id="jumlah_hilang"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="0">
                        </div>

                        <div class="form-group">
                            <label>Sumber Pengadaan</label>
                            <div class="input-group">
                                <select name="sumber_pengadaan_id" class="form-control" required>
                                    <option value="">-- Pilih Sumber --</option>
                                    <?php if ($sumberResult && $sumberResult->num_rows > 0): ?>
                                        <?php while ($s = $sumberResult->fetch_assoc()): ?>
                                            <option value="<?= (int)$s['id']; ?>">
                                                <?= htmlspecialchars($s['nama_sumber'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="input-group-append">
                                    <button type="button"
                                            class="btn btn-info"
                                            data-toggle="modal"
                                            data-target="#modalSumber">
                                        Kelola Sumber
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3"></textarea>
                        </div>

                    </div>

                </div>
            </div>

            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="alat-list.php" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
        </form>
    </div>

</div>

<!-- ===================== MODAL KELOLA LOKASI ===================== -->
<div class="modal fade" id="modalLokasi" tabindex="-1" role="dialog" aria-labelledby="modalLokasiLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLokasiLabel">Kelola Lokasi Penyimpanan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <?php if ($lokasiAdded): ?>
            <div class="alert alert-success py-1 mb-2">Lokasi baru berhasil ditambahkan.</div>
        <?php endif; ?>

        <?php if ($lokasiUpd): ?>
            <div class="alert alert-success py-1 mb-2">Lokasi berhasil diperbarui.</div>
        <?php endif; ?>

        <?php if ($lokasiDel): ?>
            <div class="alert alert-success py-1 mb-2">Lokasi berhasil dihapus.</div>
        <?php endif; ?>

        <?php if ($lokasiUsed): ?>
            <div class="alert alert-danger py-1 mb-2">
                Lokasi tidak dapat dihapus karena digunakan oleh <?= (int)$lokasiUsed; ?> alat.
            </div>
        <?php endif; ?>

        <form action="lokasi-add-process.php" method="POST" class="form-inline mb-3">
          <input type="hidden" name="from" value="alat-add">
          <div class="form-group mr-2 mb-2">
            <label class="sr-only">Nama Lokasi</label>
            <input type="text" name="nama_lokasi" class="form-control" placeholder="Nama lokasi baru" required>
          </div>
          <button type="submit" class="btn btn-primary mb-2">
            <i class="fas fa-plus"></i> Tambah
          </button>
        </form>

        <hr>

        <div class="table-responsive">
          <table class="table table-bordered table-sm">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Nama Lokasi</th>
                <th style="width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $lokasiModalRes = $conn->query("SELECT id, nama_lokasi FROM lokasi_alat ORDER BY nama_lokasi ASC");
            if ($lokasiModalRes && $lokasiModalRes->num_rows > 0):
                $no = 1;
                while ($lok = $lokasiModalRes->fetch_assoc()):
            ?>
              <tr>
                <td><?= $no++; ?></td>
                <td>
                  <form action="lokasi-edit-process.php" method="POST" class="form-inline">
                    <input type="hidden" name="from" value="alat-add">
                    <input type="hidden" name="id" value="<?= (int)$lok['id']; ?>">
                    <input type="text"
                           name="nama_lokasi"
                           class="form-control form-control-sm mr-2"
                           value="<?= htmlspecialchars($lok['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?>"
                           required>
                    <button type="submit" class="btn btn-sm btn-warning">
                      <i class="fas fa-save"></i>
                    </button>
                  </form>
                </td>
                <td class="text-center">
                  <a href="lokasi-delete.php?id=<?= (int)$lok['id']; ?>&from=alat-add"
                     class="btn btn-sm btn-danger"
                     onclick="return confirm('Yakin ingin menghapus lokasi ini?');">
                    <i class="fas fa-trash"></i>
                  </a>
                </td>
              </tr>
            <?php
                endwhile;
            else:
            ?>
              <tr><td colspan="3" class="text-center">Belum ada lokasi.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
      <div class="modal-footer">
        <a href="lokasi-list.php" class="btn btn-outline-secondary">Kelola Lengkap</a>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== MODAL KELOLA SUMBER ===================== -->
<div class="modal fade" id="modalSumber" tabindex="-1" role="dialog" aria-labelledby="modalSumberLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSumberLabel">Kelola Sumber Pengadaan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <?php if ($sumberAdded): ?><div class="alert alert-success py-1 mb-2">Sumber baru berhasil ditambahkan.</div><?php endif; ?>
        <?php if ($sumberUpd): ?><div class="alert alert-success py-1 mb-2">Sumber berhasil diperbarui.</div><?php endif; ?>
        <?php if ($sumberDel): ?><div class="alert alert-success py-1 mb-2">Sumber berhasil dihapus.</div><?php endif; ?>
        <?php if ($sumberUsed): ?>
            <div class="alert alert-danger py-1 mb-2">
                Sumber tidak dapat dihapus karena digunakan oleh <?= (int)$sumberUsed; ?> alat.
            </div>
        <?php endif; ?>

        <form action="sumber-add-process.php" method="POST" class="form-inline mb-3">
          <input type="hidden" name="from" value="alat-add">
          <div class="form-group mr-2 mb-2">
            <label class="sr-only">Nama Sumber</label>
            <input type="text" name="nama_sumber" class="form-control" placeholder="Nama sumber baru" required>
          </div>
          <button type="submit" class="btn btn-primary mb-2">
            <i class="fas fa-plus"></i> Tambah
          </button>
        </form>

        <hr>

        <div class="table-responsive">
          <table class="table table-bordered table-sm">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Nama Sumber</th>
                <th style="width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $sumberModalRes = $conn->query("SELECT id, nama_sumber FROM sumber_pengadaan ORDER BY nama_sumber ASC");
            if ($sumberModalRes && $sumberModalRes->num_rows > 0):
                $no = 1;
                while ($sm = $sumberModalRes->fetch_assoc()):
            ?>
              <tr>
                <td><?= $no++; ?></td>
                <td>
                  <form action="sumber-edit-process.php" method="POST" class="form-inline">
                    <input type="hidden" name="from" value="alat-add">
                    <input type="hidden" name="id" value="<?= (int)$sm['id']; ?>">
                    <input type="text"
                           name="nama_sumber"
                           class="form-control form-control-sm mr-2"
                           value="<?= htmlspecialchars($sm['nama_sumber'], ENT_QUOTES, 'UTF-8'); ?>"
                           required>
                    <button type="submit" class="btn btn-sm btn-warning">
                      <i class="fas fa-save"></i>
                    </button>
                  </form>
                </td>
                <td class="text-center">
                  <a href="sumber-delete.php?id=<?= (int)$sm['id']; ?>&from=alat-add"
                     class="btn btn-sm btn-danger"
                     onclick="return confirm('Yakin ingin menghapus sumber ini?');">
                    <i class="fas fa-trash"></i>
                  </a>
                </td>
              </tr>
            <?php
                endwhile;
            else:
            ?>
              <tr><td colspan="3" class="text-center">Belum ada sumber.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
      <div class="modal-footer">
        <a href="sumber-list.php" class="btn btn-outline-secondary">Kelola Lengkap</a>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>

<script>
function recalcTotalAlat() {
    var elBaik = document.getElementById('jumlah_baik');
    var elRB   = document.getElementById('jumlah_rusak_berat');
    var elRR   = document.getElementById('jumlah_rusak_ringan');
    var elH    = document.getElementById('jumlah_hilang');
    var elTot  = document.getElementById('jumlah_total');

    var baik   = parseInt(elBaik ? elBaik.value : '0', 10) || 0;
    var rb     = parseInt(elRB ? elRB.value : '0', 10) || 0;
    var rr     = parseInt(elRR ? elRR.value : '0', 10) || 0;
    var hilang = parseInt(elH ? elH.value : '0', 10) || 0;

    var total = baik + rb + rr + hilang;
    if (elTot) elTot.value = total;
}

['jumlah_baik','jumlah_rusak_berat','jumlah_rusak_ringan','jumlah_hilang'].forEach(function(id){
    var el = document.getElementById(id);
    if (el) {
        el.addEventListener('input', recalcTotalAlat);
        el.addEventListener('change', recalcTotalAlat);
    }
});

recalcTotalAlat();
</script>

<?php if ($openModal === 'lokasi'): ?>
<script>
$(document).ready(function () { $('#modalLokasi').modal('show'); });
</script>
<?php endif; ?>

<?php if ($openModal === 'sumber'): ?>
<script>
$(document).ready(function () { $('#modalSumber').modal('show'); });
</script>
<?php endif; ?>
