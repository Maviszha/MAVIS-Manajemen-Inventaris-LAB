<?php
// Header SB Admin 2 (sudah include db + auth + sidebar)
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();
?>

<div class="container-fluid">

    <!-- Judul Halaman -->
    <h1 class="h3 mb-4 text-gray-800">Unduh Data bahan Laboratorium</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Pengaturan Unduh</h6>
        </div>
        <div class="card-body">

            <form id="exportForm" action="export-bahan-process.php" method="POST">

                <!-- Format Export (hanya CSV) -->
                <div class="form-group">
                    <label>Format Unduh</label>
                    <select name="format" class="form-control" required>
                        <option value="csv" selected>CSV</option>
                    </select>
                    <small class="form-text text-muted">
                        Saat ini unduhan hanya tersedia dalam format CSV.(Karena saya males bikin format lain) :)
                    </small>
                </div>

                <!-- Filter Tanggal Pengadaan -->
                <div class="form-group mt-3">
                    <label>Filter Tanggal Pengadaan (berdasarkan kolom <code>tahun_pengadaan</code>)</label>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="small mb-1">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="small mb-1">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        Jika dikosongkan, semua data akan diunduh tanpa filter tanggal.
                    </small>
                </div>

                <!-- Opsi Header -->
                <div class="form-group mt-3">
                    <label>Sertakan Nama Kolom di Baris Pertama?</label><br>
                    <label class="mr-2">
                        <input type="checkbox" name="include_header" value="1" checked>
                        <span class="ml-1">Ya, sertakan</span>
                    </label>
                </div>

                <!-- Tombol Export -->
                <button type="submit" class="btn btn-primary mt-3">
                    <i class="fas fa-file-export"></i> Unduh Sekarang
                </button>
                <a href="bahan-list.php" class="btn btn-secondary mt-3">
                    Kembali ke Daftar bahan
                </a>

            </form>

        </div>
    </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>

<script>
document.getElementById("exportForm").addEventListener("submit", function(e) {
    const ok = confirm("Yakin ingin mengekspor data bahan sekarang?");
    if (!ok) {
        e.preventDefault();
    }
});
</script>
