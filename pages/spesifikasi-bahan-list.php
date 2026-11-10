<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

$msg  = $_GET['msg'] ?? '';
$fail = $_GET['fail'] ?? '';

$edit_id = $_GET['edit_id'] ?? '';
$editRow = null;

if ($edit_id !== '' && ctype_digit($edit_id)) {
    $id = (int)$edit_id;
    $st = $conn->prepare("SELECT id, nama_spesifikasi, created_at FROM spesifikasi_bahan WHERE id = ? LIMIT 1");
    $st->bind_param("i", $id);
    $st->execute();
    $editRow = $st->get_result()->fetch_assoc();
    $st->close();
}

// List data
$res = $conn->query("SELECT id, nama_spesifikasi, created_at FROM spesifikasi_bahan ORDER BY nama_spesifikasi ASC");
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Master Spesifikasi Bahan</h1>

    <?php if ($fail): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?= htmlspecialchars($fail, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($msg === 'added'): ?>
        <div class="alert alert-success alert-auto-close">
            <i class="fas fa-check-circle mr-2"></i> Spesifikasi berhasil ditambahkan.
        </div>
    <?php elseif ($msg === 'updated'): ?>
        <div class="alert alert-info alert-auto-close">
            <i class="fas fa-sync-alt mr-2"></i> Spesifikasi berhasil diperbarui.
        </div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-warning alert-auto-close">
            <i class="fas fa-trash mr-2"></i> Spesifikasi berhasil dihapus.
        </div>
    <?php elseif ($msg === 'notfound'): ?>
        <div class="alert alert-danger alert-auto-close">
            <i class="fas fa-search-minus mr-2"></i> Data tidak ditemukan.
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <?= $editRow ? 'Edit Spesifikasi' : 'Tambah Spesifikasi' ?>
            </h6>
            <div>
                <a href="bahan-list.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Kembali ke Bahan
                </a>
            </div>
        </div>

        <div class="card-body">
            <form method="POST" action="spesifikasi-bahan-process.php" class="form-inline">
                <input type="hidden" name="action" value="<?= $editRow ? 'edit' : 'add' ?>">
                <?php if ($editRow): ?>
                    <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
                <?php endif; ?>

                <div class="form-group mr-2 mb-2">
                    <label class="mr-2">Nama Spesifikasi</label>
                    <input type="text"
                           name="nama_spesifikasi"
                           class="form-control"
                           style="min-width:320px;"
                           maxlength="100"
                           required
                           value="<?= htmlspecialchars($editRow['nama_spesifikasi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <button type="submit" class="btn btn-primary mb-2">
                    <i class="fas fa-save"></i> <?= $editRow ? 'Update' : 'Simpan' ?>
                </button>

                <?php if ($editRow): ?>
                    <a href="spesifikasi-bahan-list.php" class="btn btn-secondary mb-2 ml-2">
                        Batal
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Spesifikasi</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="spesTable" class="table table-bordered table-striped" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th>Nama Spesifikasi</th>
                            <th style="width:180px;">Dibuat</th>
                            <th style="width:120px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($res && $res->num_rows > 0): ?>
                        <?php while ($r = $res->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$r['id']; ?></td>
                                <td><?= htmlspecialchars($r['nama_spesifikasi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($r['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-center">
                                    <a href="spesifikasi-bahan-list.php?edit_id=<?= (int)$r['id']; ?>"
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form method="POST" action="spesifikasi-bahan-process.php" style="display:inline;"
                                          onsubmit="return confirm('Yakin ingin menghapus spesifikasi ini?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$r['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">Belum ada data spesifikasi.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>

<script>
$(document).ready(function () {
    $('#spesTable').DataTable({
        pageLength: 25,
        autoWidth: false
    });

    setTimeout(function () {
        $('.alert-auto-close').fadeTo(500, 0).slideUp(500, function () {
            $(this).remove();
        });
    }, 3500);
});
</script>
