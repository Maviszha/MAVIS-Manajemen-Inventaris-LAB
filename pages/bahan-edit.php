<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

$id = $_GET['id'] ?? '';
if ($id === '' || !ctype_digit($id)) {
    header("Location: bahan-list.php?msg=invalid");
    exit;
}
$id = (int)$id;

// Ambil data bahan
$stmt = $conn->prepare("SELECT * FROM bahan_laboratorium WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: bahan-list.php?msg=notfound");
    exit;
}

// Dropdown master
$spesRes   = $conn->query("SELECT id, nama_spesifikasi FROM spesifikasi_bahan ORDER BY nama_spesifikasi ASC");
$lokRes    = $conn->query("SELECT id, nama_lokasi FROM lokasi_alat ORDER BY nama_lokasi ASC");
$satuanRes = $conn->query("SELECT id, nama_satuan FROM satuan_bahan WHERE aktif = 1 ORDER BY nama_satuan ASC");
$sumberRes = $conn->query("SELECT id, nama_sumber FROM sumber_pengadaan ORDER BY nama_sumber ASC");

$fail = $_GET['fail'] ?? '';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Edit Bahan Laboratorium</h1>

    <?php if ($fail): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?= htmlspecialchars($fail, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Form Edit Data Bahan</h6>
            <a href="bahan-list.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <form action="bahan-edit-process.php" method="POST" autocomplete="off">
            <input type="hidden" name="id" value="<?= (int)$row['id']; ?>">

            <div class="card-body">
                <div class="row">

                    <!-- KIRI -->
                    <div class="col-md-6">

                        <div class="form-group">
                            <label>Nama Bahan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_bahan" class="form-control" required
                                   value="<?= htmlspecialchars($row['nama_bahan'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label>No Inventaris</label>
                            <input type="text" name="no_inventaris" class="form-control" maxlength="50"
                                   value="<?= htmlspecialchars($row['no_inventaris'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label>Rumus Kimia</label>
                            <input type="text" name="rumus_kimia" class="form-control" maxlength="100"
                                   value="<?= htmlspecialchars($row['rumus_kimia'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label>Spesifikasi</label>
                            <div class="input-group">
                                <select name="spesifikasi_id" class="form-control">
                                    <option value="">-- Pilih Spesifikasi --</option>
                                    <?php if ($spesRes && $spesRes->num_rows > 0): ?>
                                        <?php while ($s = $spesRes->fetch_assoc()): ?>
                                            <option value="<?= (int)$s['id']; ?>"
                                                <?= ((int)($row['spesifikasi_id'] ?? 0) === (int)$s['id']) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($s['nama_spesifikasi'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="input-group-append">
                                    <a href="spesifikasi-bahan-list.php" class="btn btn-success">Kelola</a>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Lokasi</label>
                            <div class="input-group">
                                <select name="lokasi_id" class="form-control">
                                    <option value="">-- Pilih Lokasi --</option>
                                    <?php if ($lokRes && $lokRes->num_rows > 0): ?>
                                        <?php while ($l = $lokRes->fetch_assoc()): ?>
                                            <option value="<?= (int)$l['id']; ?>"
                                                <?= ((int)($row['lokasi_id'] ?? 0) === (int)$l['id']) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($l['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="input-group-append">
                                    <a href="lokasi-list.php" class="btn btn-info">Kelola</a>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Volume (text)</label>
                            <input type="text" name="volume" class="form-control"
                                   value="<?= htmlspecialchars($row['volume'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   placeholder="Contoh: 600 / 1 botol / 500 ml">
                        </div>

                        <div class="form-group">
                            <label>Satuan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="satuan_id" class="form-control" required>
                                    <option value="">-- Pilih Satuan --</option>
                                    <?php if ($satuanRes && $satuanRes->num_rows > 0): ?>
                                        <?php while ($st = $satuanRes->fetch_assoc()): ?>
                                            <option value="<?= (int)$st['id']; ?>"
                                                <?= ((int)($row['satuan_id'] ?? 0) === (int)$st['id']) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($st['nama_satuan'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="input-group-append">
                                    <a href="satuan-bahan-list.php" class="btn btn-secondary">Kelola</a>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- KANAN -->
                    <div class="col-md-6">

                        <div class="form-group">
                            <label>Tahun Pengadaan <span class="text-danger">*</span></label>
                            <input type="date" name="tahun_pengadaan" class="form-control" required
                                   value="<?= htmlspecialchars($row['tahun_pengadaan'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label>Sumber Pengadaan</label>
                            <div class="input-group">
                                <select name="sumber_pengadaan_id" class="form-control">
                                    <option value="">-- Pilih Sumber Pengadaan --</option>
                                    <?php if ($sumberRes && $sumberRes->num_rows > 0): ?>
                                        <?php while ($sp = $sumberRes->fetch_assoc()): ?>
                                            <option value="<?= (int)$sp['id']; ?>"
                                                <?= ((int)($row['sumber_pengadaan_id'] ?? 0) === (int)$sp['id']) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($sp['nama_sumber'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="input-group-append">
                                    <a href="sumber-list.php" class="btn btn-warning">Kelola</a>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Tanggal Kadaluarsa</label>
                            <input type="date" name="tanggal_kadaluarsa" class="form-control"
                                   value="<?= htmlspecialchars($row['tanggal_kadaluarsa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <hr>

                        <div class="form-group">
                            <label>Jumlah Total</label>
                            <input type="number" name="jumlah_total" id="jumlah_total"
                                   class="form-control" min="0" step="1" readonly
                                   value="<?= (int)($row['jumlah_total'] ?? 0); ?>">
                            <small class="text-muted">Terisi otomatis dari Baik + RB + RR + Hilang</small>
                        </div>

                        <div class="form-group">
                            <label>Jumlah Baik</label>
                            <input type="number" name="jumlah_baik" id="jumlah_baik"
                                   class="form-control" min="0" step="1" required
                                   value="<?= (int)($row['jumlah_baik'] ?? 0); ?>">
                        </div>

                        <div class="form-group">
                            <label>Rusak Berat (RB)</label>
                            <input type="number" name="jumlah_rusak_berat" id="jumlah_rusak_berat"
                                   class="form-control" min="0" step="1"
                                   value="<?= (int)($row['jumlah_rusak_berat'] ?? 0); ?>">
                        </div>

                        <div class="form-group">
                            <label>Rusak Ringan (RR)</label>
                            <input type="number" name="jumlah_rusak_ringan" id="jumlah_rusak_ringan"
                                   class="form-control" min="0" step="1"
                                   value="<?= (int)($row['jumlah_rusak_ringan'] ?? 0); ?>">
                        </div>

                        <div class="form-group">
                            <label>Hilang (H)</label>
                            <input type="number" name="jumlah_hilang" id="jumlah_hilang"
                                   class="form-control" min="0" step="1"
                                   value="<?= (int)($row['jumlah_hilang'] ?? 0); ?>">
                        </div>

                        <div class="form-group">
                            <label>Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($row['keterangan'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                    </div>
                </div>
            </div>

            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="bahan-list.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>

<script>
function recalcTotalBahan() {
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
        el.addEventListener('input', recalcTotalBahan);
        el.addEventListener('change', recalcTotalBahan);
    }
});

recalcTotalBahan();
</script>
