<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();
$error = $_GET['error'] ?? '';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Tambah Sumber Pengadaan</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Tambah Sumber</h6>
        </div>
        <div class="card-body">

            <?php if ($error === 'empty'): ?>
                <div class="alert alert-danger">Nama sumber tidak boleh kosong.</div>
            <?php endif; ?>

            <form action="sumber-add-process.php" method="POST">
                <div class="form-group">
                    <label>Nama Sumber</label>
                    <input type="text" name="nama_sumber" class="form-control" required
                           placeholder="Contoh: BOS, Dana Komite, Hibah Pemerintah">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="sumber-list.php" class="btn btn-secondary">Batal</a>
            </form>

        </div>
    </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
