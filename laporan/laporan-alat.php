<?php
include "../templates/header.php";

// ==============================
// 1) Rekap total alat per lokasi
// ==============================
$sqlLokasi = "
    SELECT 
        l.id AS lokasi_id, 
        l.nama_lokasi,
        COALESCE(SUM(a.jumlah_total), 0) AS total_unit,
        COUNT(a.id) AS total_jenis
    FROM lokasi_alat l
    LEFT JOIN alat_laboratorium a ON a.lokasi_id = l.id
    GROUP BY l.id, l.nama_lokasi
    ORDER BY l.nama_lokasi ASC
";
$lokasiResult = $conn->query($sqlLokasi);

// ====================================
// 2) Rekap kondisi alat untuk grafik
// ====================================
$sqlKondisi = "
    SELECT
        COALESCE(SUM(jumlah_baik), 0) AS baik,
        COALESCE(SUM(jumlah_rusak_ringan), 0) AS rusak_ringan,
        COALESCE(SUM(jumlah_rusak_berat), 0) AS rusak_berat,
        COALESCE(SUM(jumlah_hilang), 0) AS hilang
    FROM alat_laboratorium
";
$kondisiRow = $conn->query($sqlKondisi)->fetch_assoc();

$labelsKondisi = ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang'];
$dataKondisi = [
    (int)$kondisiRow['baik'],
    (int)$kondisiRow['rusak_ringan'],
    (int)$kondisiRow['rusak_berat'],
    (int)$kondisiRow['hilang'],
];

$labelsKondisiJson = json_encode($labelsKondisi);
$dataKondisiJson   = json_encode($dataKondisi);
?>

<!-- CONTENT WRAPPER -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Laporan & Rekap Alat Laboratorium</h1>
        </div>
    </section>

    <section class="content">

        <!-- REKAP PER LOKASI -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Rekap Total Alat per Lokasi</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Lokasi</th>
                        <th>Jumlah Jenis Alat</th>
                        <th>Total Unit Alat</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($lokasiResult && $lokasiResult->num_rows > 0):
                        $no = 1;
                        while ($row = $lokasiResult->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($row['nama_lokasi']); ?></td>
                                <td><?= (int)$row['total_jenis']; ?></td>
                                <td><?= (int)$row['total_unit']; ?></td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="4" class="text-center">Tidak ada data.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- GRAFIK KONDISI + EXPORT -->
        <div class="row">
            <!-- Grafik -->
            <div class="col-md-6">
                <div class="card card-success">
                    <div class="card-header"><h3 class="card-title">Grafik Kondisi Alat</h3></div>
                    <div class="card-body">
                        <canvas id="chartKondisi"></canvas>
                    </div>
                </div>
            </div>

            <!-- Export -->
            <div class="col-md-6">
                <div class="card card-warning">
                    <div class="card-header"><h3 class="card-title">Export Berdasarkan Periode</h3></div>
                    <div class="card-body">
                        <form method="get" action="export-alat-periode.php" target="_blank">
                            <div class="form-group">
                                <label>Dari Tanggal (tahun_pengadaan)</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Sampai Tanggal</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                            <button class="btn btn-warning"><i class="fas fa-file-export"></i> Export CSV</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>

    </section>
</div>

<?php include "../templates/footer.php"; ?>

<!-- CHARTJS -->
<scri
