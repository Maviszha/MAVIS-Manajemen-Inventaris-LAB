<?php
// Dashboard utama SB Admin 2
// header.php sudah include: db, auth, sidebar, topbar, CSS
include dirname(__DIR__) . "/templates/header.php";

// =================== RINGKASAN CARD ===================

// Total jenis alat
$qTotalAlat = $conn->query("SELECT COUNT(*) AS jml FROM alat_laboratorium");
$totalJenisAlat = (int)($qTotalAlat->fetch_assoc()['jml'] ?? 0);

// Total unit alat
$qTotalUnit = $conn->query("SELECT COALESCE(SUM(jumlah_total),0) AS jml FROM alat_laboratorium");
$totalUnitAlat = (int)($qTotalUnit->fetch_assoc()['jml'] ?? 0);

// Total lokasi
$qLokasi = $conn->query("SELECT COUNT(*) AS jml FROM lokasi_alat");
$totalLokasi = (int)($qLokasi->fetch_assoc()['jml'] ?? 0);

// Total sumber pengadaan
$qSumber = $conn->query("SELECT COUNT(*) AS jml FROM sumber_pengadaan");
$totalSumber = (int)($qSumber->fetch_assoc()['jml'] ?? 0);


// =================== DATA GRAFIK KONDISI ===================

$sqlKondisi = "
    SELECT
        COALESCE(SUM(jumlah_baik), 0) AS baik,
        COALESCE(SUM(jumlah_rusak_ringan), 0) AS rusak_ringan,
        COALESCE(SUM(jumlah_rusak_berat), 0) AS rusak_berat,
        COALESCE(SUM(jumlah_hilang), 0) AS hilang
    FROM alat_laboratorium
";
$kRow = $conn->query($sqlKondisi)->fetch_assoc();

$labelsKondisi = ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang'];
$dataKondisi   = [
    (int)$kRow['baik'],
    (int)$kRow['rusak_ringan'],
    (int)$kRow['rusak_berat'],
    (int)$kRow['hilang'],
];

$labelsKondisiJson = json_encode($labelsKondisi);
$dataKondisiJson   = json_encode($dataKondisi);


// =================== DATA GRAFIK UNIT PER LOKASI ===================

$sqlLokasi = "
    SELECT 
        l.nama_lokasi,
        COALESCE(SUM(a.jumlah_total), 0) AS total_unit
    FROM lokasi_alat l
    LEFT JOIN alat_laboratorium a ON a.lokasi_id = l.id
    GROUP BY l.id, l.nama_lokasi
    ORDER BY l.nama_lokasi ASC
";
$resLokasi = $conn->query($sqlLokasi);

$labelsLokasi = [];
$dataLokasi   = [];

if ($resLokasi && $resLokasi->num_rows > 0) {
    while ($row = $resLokasi->fetch_assoc()) {
        $labelsLokasi[] = $row['nama_lokasi'];
        $dataLokasi[]   = (int)$row['total_unit'];
    }
}

$labelsLokasiJson = json_encode($labelsLokasi);
$dataLokasiJson   = json_encode($dataLokasi);
?>

<div class="container-fluid">

    <!-- Judul Dashboard -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    </div>

    <!-- Row Cards -->
    <div class="row">

        <!-- Total Jenis Alat -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Jenis Alat
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($totalJenisAlat); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-flask fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Unit Alat -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Unit Alat
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($totalUnitAlat); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Lokasi -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Lokasi Penyimpanan
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($totalLokasi); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-map-marker-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Sumber Pengadaan -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Sumber Pengadaan
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($totalSumber); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Row Grafik -->
    <div class="row">

        <!-- Grafik Kondisi Alat -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Kondisi Alat (Unit)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="chartKondisi"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafik Total Unit per Lokasi -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Total Unit Alat per Lokasi</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="chartLokasi"></canvas>
                    </div>
                    <?php if (empty($labelsLokasi)): ?>
                        <p class="mt-2 mb-0 text-muted" style="font-size: 0.85rem;">
                            Belum ada data lokasi / alat yang bisa ditampilkan.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>

<!-- Chart.js (sesuaikan path dengan punya Anda) -->
<script src="/mavis/assets/adminlte/plugins/chart.js/Chart.min.js"></script>

<script>
    // Data dari PHP
    const labelsKondisi = <?= $labelsKondisiJson; ?>;
    const dataKondisi   = <?= $dataKondisiJson; ?>;

    const labelsLokasi  = <?= $labelsLokasiJson; ?>;
    const dataLokasi    = <?= $dataLokasiJson; ?>;

    // BAR: Kondisi Alat
    (function () {
        const ctx = document.getElementById('chartKondisi');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labelsKondisi,
                datasets: [{
                    data: dataKondisi
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    })();

    // BAR: Total Unit per Lokasi
    (function () {
        const ctx = document.getElementById('chartLokasi');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labelsLokasi,
                datasets: [{
                    data: dataLokasi
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 60,
                            minRotation: 0
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    })();
</script>
