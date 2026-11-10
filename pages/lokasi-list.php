<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();
// Query daftar lokasi + jumlah alat per lokasi
$sql = "
    SELECT 
        l.id,
        l.nama_lokasi,
        COALESCE(COUNT(a.id), 0) AS total_jenis,
        COALESCE(SUM(a.jumlah_total), 0) AS total_unit
    FROM lokasi_alat l
    LEFT JOIN alat_laboratorium a ON a.lokasi_id = l.id
    GROUP BY l.id, l.nama_lokasi
    ORDER BY l.nama_lokasi ASC
";
$result = $conn->query($sql);

// Ambil pesan (notifikasi)
$success  = $_GET['success']  ?? '';
$updated  = $_GET['updated']  ?? '';
$deleted  = $_GET['deleted']  ?? '';
$used     = $_GET['used']     ?? '';
$error    = $_GET['error']    ?? '';
?>

<div class="container-fluid">

    <!-- Judul Halaman -->
    <h1 class="h3 mb-4 text-gray-800">Daftar Lokasi Penyimpanan</h1>

    <!-- Notifikasi -->
    <?php if ($success): ?>
        <div class="alert alert-success">Lokasi berhasil ditambahkan.</div>
    <?php endif; ?>

    <?php if ($updated): ?>
        <div class="alert alert-success">Lokasi berhasil diperbarui.</div>
    <?php endif; ?>

    <?php if ($deleted): ?>
        <div class="alert alert-success">Lokasi berhasil dihapus.</div>
    <?php endif; ?>

    <?php if ($used): ?>
        <div class="alert alert-danger">
            Lokasi tidak dapat dihapus karena masih digunakan oleh <b><?= (int)$used; ?></b> data alat.
        </div>
    <?php endif; ?>

    <?php if ($error === 'invalid'): ?>
        <div class="alert alert-danger">Aksi tidak valid.</div>
    <?php endif; ?>

    <!-- Button Tambah -->
    <a href="lokasi-add.php" class="btn btn-primary mb-2">
        <i class="fas fa-plus"></i> Tambah Lokasi
    </a>
    <a href="alat-list.php" class="btn btn-secondary mb-2">
        <i class="fas fa-arrow-left"></i> Kembali ke Data Alat
    </a>
    <!-- Card Tabel -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Tabel Lokasi</h6>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" id="dataTable">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Nama Lokasi</th>
                            <th>Jumlah Jenis Alat</th>
                            <th>Total Unit</th>
                            <th style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): $no = 1; ?>
                            <?php while ($r = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($r['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= (int)$r['total_jenis']; ?></td>
                                    <td><?= (int)$r['total_unit']; ?></td>

                                    <td class="text-center">
                                        <a href="lokasi-edit.php?id=<?= $r['id']; ?>"
                                           class="btn btn-warning btn-sm mb-0">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <a href="lokasi-delete.php?id=<?= $r['id']; ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Yakin ingin menghapus lokasi ini?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Belum ada data lokasi.</td>
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
$(document).ready(function(){
    $('#dataTable').DataTable();
});
</script>
