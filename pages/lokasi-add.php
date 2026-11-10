<?php
// Header SB Admin 2 (sudah include db + auth + sidebar)
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();
// Baca pesan error (kalau ada)
$error = $_GET['error'] ?? '';
?>

<div class="container-fluid">

    <!-- Judul Halaman -->
    <h1 class="h3 mb-4 text-gray-800">Tambah Lokasi Penyimpanan</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Tambah Lokasi</h6>
        </div>

        <div class="card-body">

            <?php if ($error === 'empty'): ?>
                <div class="alert alert-danger">
                    Nama lokasi tidak boleh kosong.
                </div>
            <?php endif; ?>

            <form action="lokasi-add-process.php" method="POST">
                <div class="form-group">
                    <label>Nama Lokasi</label>
                    <input type="text"
                           name="nama_lokasi"
                           class="form-control"
                           required
                           placeholder="Contoh: Lab 1 Lemari A1">
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="lokasi-list.php" class="btn btn-secondary">
                    Batal
                </a>
            </form>
        </div>
    </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
