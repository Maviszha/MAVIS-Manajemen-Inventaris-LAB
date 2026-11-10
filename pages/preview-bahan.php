<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

// VALIDASI FILE
if (!isset($_FILES['file_csv']) || $_FILES['file_csv']['error'] !== UPLOAD_ERR_OK) {
    header("Location: import-bahan.php");
    exit;
}

$fileTmp = $_FILES['file_csv']['tmp_name'];
$file = fopen($fileTmp, 'r');

if (!$file) {
    die("Gagal membuka file CSV.");
}

$header = fgetcsv($file);

$data = [];
while (($row = fgetcsv($file)) !== false) {
    if (count($row) < 12) continue;
    $data[] = $row;
}
fclose($file);

$_SESSION['preview_bahan'] = $data;

$today = new DateTime();
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Preview Import Data Bahan Laboratorium</h1>

    <div class="alert alert-info">
        <i class="fas fa-info-circle mr-1"></i>
        Periksa data sebelum diimpor.
        <span class="badge badge-warning">Kuning</span> = hampir kadaluarsa (≤ 7 hari),
        <span class="badge badge-danger">Merah</span> = sudah kadaluarsa.
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-light">
                        <tr>
                            <th>Nama Bahan</th>
                            <th>Rumus Kimia</th>
                            <th>Spesifikasi</th>
                            <th>Volume</th>
                            <th>Satuan</th>
                            <th>Tahun Pengadaan</th>
                            <th>Kadaluarsa</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if (empty($data)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    Tidak ada data valid di dalam CSV.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($data as $row): ?>
                            <?php
                            // Mapping kolom CSV
                            $kadaluarsa = new DateTime($row[6]);
                            $selisih    = $today->diff($kadaluarsa)->days;

                            $isExpired = ($kadaluarsa < $today);
                            $isSoon    = (!$isExpired && $selisih <= 7);

                            $rowClass = '';
                            if ($isExpired) {
                                $rowClass = 'table-danger';
                            } elseif ($isSoon) {
                                $rowClass = 'table-warning';
                            }
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td><?= htmlspecialchars($row[0], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row[2], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row[3], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row[4], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row[5], ENT_QUOTES, 'UTF-8'); ?></td>

                                <!-- Kolom Kadaluarsa + Badge -->
                                <td>
                                    <?= htmlspecialchars($row[6], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($isExpired): ?>
                                        <span class="badge badge-danger ml-1">Expired</span>
                                    <?php elseif ($isSoon): ?>
                                        <span class="badge badge-warning ml-1">≤ 7 hari</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status -->
                                <td>
                                    <?php if ($isExpired): ?>
                                        <span class="badge badge-danger">Kadaluarsa</span>
                                    <?php elseif ($isSoon): ?>
                                        <span class="badge badge-warning">Hampir Kadaluarsa</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Aman</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

            <!-- Tombol Aksi -->
            <div class="mt-4 d-flex justify-content-between">
                <a href="import-bahan.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>

                <form action="import-bahan-process.php" method="POST">
                    <button type="submit"
                            class="btn btn-success"
                            onclick="return confirm('Yakin ingin mengimpor seluruh data bahan ini?');">
                        <i class="fas fa-check"></i> Lanjut Import
                    </button>
                </form>
            </div>

        </div>
    </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
