<?php
// Header SB Admin 2, sudah include db.php + auth.php + sidebar
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

// Pastikan ID ada & valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: alat-list.php");
    exit;
}

$id = (int)$_GET['id'];

// Ambil data alat dengan prepared statement
$stmt = $conn->prepare("SELECT * FROM alat_laboratorium WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $stmt->close();
    echo "<script>alert('Data tidak ditemukan!'); window.location='alat-list.php';</script>";
    exit;
}

$d = $result->fetch_assoc();
$stmt->close();

// Ambil lokasi
$lokasiResult = $conn->query("SELECT id, nama_lokasi FROM lokasi_alat ORDER BY nama_lokasi ASC");

// Ambil sumber pengadaan
$sumberResult = $conn->query("SELECT id, nama_sumber FROM sumber_pengadaan ORDER BY id ASC");
?>

<div class="container-fluid">

    <!-- Judul Halaman -->
    <h1 class="h3 mb-4 text-gray-800">Edit Data Alat Laboratorium</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-warning">Form Edit Data Alat</h6>
        </div>

        <form action="alat-edit-process.php" method="POST">
            <input type="hidden" name="id" value="<?= (int)$d['id']; ?>">

            <div class="card-body">
                <div class="row">
                    <!-- Kolom kiri -->
                    <div class="col-md-6">

                        <div class="form-group">
                            <label>Nama Alat</label>
                            <input type="text"
                                   name="nama_alat"
                                   class="form-control"
                                   value="<?= htmlspecialchars($d['nama_alat'], ENT_QUOTES, 'UTF-8'); ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Merk</label>
                            <input type="text"
                                   name="merk_alat"
                                   class="form-control"
                                   value="<?= htmlspecialchars($d['merk_alat'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label>Spesifikasi</label>
                            <textarea name="spesifikasi"
                                      class="form-control"
                                      rows="3"><?= htmlspecialchars($d['spesifikasi'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Lokasi Penyimpanan</label>
                            <select name="lokasi_id" class="form-control" required>
                                <option value="">-- Pilih Lokasi --</option>
                                <?php if ($lokasiResult && $lokasiResult->num_rows > 0): ?>
                                    <?php while ($l = $lokasiResult->fetch_assoc()): ?>
                                        <option value="<?= (int)$l['id']; ?>"
                                            <?= ((int)$l['id'] === (int)$d['lokasi_id']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($l['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tanggal Pengadaan</label>
                            <input type="date"
                                   name="tahun_pengadaan"
                                   class="form-control"
                                   value="<?= htmlspecialchars($d['tahun_pengadaan'], ENT_QUOTES, 'UTF-8'); ?>"
                                   required>
                        </div>

                    </div>

                    <!-- Kolom kanan -->
                    <div class="col-md-6">

                        <!-- ✅ NO INVENTARIS -->
                        <div class="form-group">
                            <label>No Inventaris</label>
                            <input type="text"
                                   name="no_inventaris"
                                   class="form-control"
                                   maxlength="100"
                                   value="<?= htmlspecialchars($d['no_inventaris'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   placeholder="Contoh: INV-2025-001">
                            <small class="text-muted">Opsional. Boleh dikosongkan.</small>
                        </div>

                        <!-- ✅ TOTAL OTOMATIS (readonly) -->
                        <div class="form-group">
                            <label>Jumlah Total</label>
                            <input type="number"
                                   name="jumlah_total"
                                   id="jumlah_total"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="<?= (int)($d['jumlah_total'] ?? 0); ?>"
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
                                   value="<?= (int)$d['jumlah_baik']; ?>">
                        </div>

                        <div class="form-group">
                            <label class="mt-3">Rusak Berat</label>
                            <input type="number"
                                   name="jumlah_rusak_berat"
                                   id="jumlah_rusak_berat"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="<?= (int)$d['jumlah_rusak_berat']; ?>">
                        </div>

                        <div class="form-group">
                            <label class="mt-3">Rusak Ringan</label>
                            <input type="number"
                                   name="jumlah_rusak_ringan"
                                   id="jumlah_rusak_ringan"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="<?= (int)$d['jumlah_rusak_ringan']; ?>">
                        </div>

                        <div class="form-group">
                            <label class="mt-3">Hilang</label>
                            <input type="number"
                                   name="jumlah_hilang"
                                   id="jumlah_hilang"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="<?= (int)$d['jumlah_hilang']; ?>">
                        </div>

                        <div class="form-group">
                            <label class="mt-3">Sumber Pengadaan</label>
                            <select name="sumber_pengadaan_id" class="form-control" required>
                                <option value="">-- Pilih Sumber --</option>
                                <?php if ($sumberResult && $sumberResult->num_rows > 0): ?>
                                    <?php while ($s = $sumberResult->fetch_assoc()): ?>
                                        <option value="<?= (int)$s['id']; ?>"
                                            <?= ((int)$s['id'] === (int)$d['sumber_pengadaan_id']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($s['nama_sumber'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="mt-3">Keterangan</label>
                            <textarea name="keterangan"
                                      class="form-control"
                                      rows="3"><?= htmlspecialchars($d['keterangan'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <p class="text-muted" style="font-size: 0.85rem;">
                            <strong>Catatan:</strong> Total dihitung otomatis dari Baik + RB + RR + H.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card-footer text-right">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="alat-list.php" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
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
