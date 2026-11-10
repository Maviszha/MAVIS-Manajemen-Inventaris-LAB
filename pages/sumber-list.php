<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();
// Query daftar sumber + jumlah alat per sumber
$sql = "
    SELECT 
        s.id,
        s.nama_sumber,
        COALESCE(COUNT(a.id), 0) AS total_jenis,
        COALESCE(SUM(a.jumlah_total), 0) AS total_unit
    FROM sumber_pengadaan s
    LEFT JOIN alat_laboratorium a ON a.sumber_pengadaan_id = s.id
    GROUP BY s.id, s.nama_sumber
    ORDER BY s.nama_sumber ASC
";
$result = $conn->query($sql);

// Notifikasi
$success  = $_GET['success']  ?? '';
$updated  = $_GET['updated']  ?? '';
$deleted  = $_GET['deleted']  ?? '';
$used     = $_GET['used']     ?? '';
$error    = $_GET['error']    ?? '';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Daftar Sumber Pengadaan</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">Sumber berhasil ditambahkan.</div>
    <?php endif; ?>

    <?php if ($updated): ?>
        <div class="alert alert-success">Sumber berhasil diperbarui.</div>
    <?php endif; ?>

    <?php if ($deleted): ?>
        <div class="alert alert-success">Sumber berhasil dihapus.</div>
    <?php endif; ?>

    <?php if ($used): ?>
        <div class="alert alert-danger">
            Sumber tidak dapat dihapus karena masih digunakan oleh <b><?= (int)$used; ?></b> data alat.
        </div>
    <?php endif; ?>

    <?php if ($error === 'invalid'): ?>
        <div class="alert alert-danger">Aksi tidak valid.</div>
    <?php endif; ?>

    <a href="sumber-add.php" class="btn btn-primary mb-2">
        <i class="fas fa-plus"></i> Tambah Sumber
    </a>
    <a href="alat-list.php" class="btn btn-secondary mb-2">
        <i class="fas fa-arrow-left"></i> Kembali ke Data Alat
    </a>
	<a href="bahan-list.php" class="btn btn-secondary mb-2">
        <i class="fas fa-arrow-left"></i> Kembali ke Data Bahan
    </a>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Tabel Sumber Pengadaan</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%">
                    <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Nama Sumber</th>
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
                                <td><?= htmlspecialchars($r['nama_sumber'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= (int)$r['total_jenis']; ?></td>
                                <td><?= (int)$r['total_unit']; ?></td>
                                <td class="text-center">
                                    <a href="sumber-edit.php?id=<?= (int)$r['id']; ?>" class="btn btn-warning btn-sm mb-0">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="sumber-delete.php?id=<?= (int)$r['id']; ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Yakin ingin menghapus sumber ini?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">Belum ada data sumber.</td></tr>
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
