<?php
// Dashboard utama SB Admin 2 di root /mavis
include __DIR__ . "/templates/header.php";

/* =================== RINGKASAN CARD (ALAT) =================== */

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


/* =================== RINGKASAN CARD (BAHAN) =================== */

// Total jenis bahan
$qTotalBahan = $conn->query("SELECT COUNT(*) AS jml FROM bahan_laboratorium");
$totalJenisBahan = (int)($qTotalBahan->fetch_assoc()['jml'] ?? 0);

// Total unit bahan
$qTotalUnitBahan = $conn->query("SELECT COALESCE(SUM(jumlah_total),0) AS jml FROM bahan_laboratorium");
$totalUnitBahan = (int)($qTotalUnitBahan->fetch_assoc()['jml'] ?? 0);

// Total unit bahan kadaluarsa (<= hari ini) - aman walau ada '' / NULL / string invalid
$qExpiredBahan = $conn->query("
    SELECT COALESCE(SUM(jumlah_total),0) AS jml
    FROM bahan_laboratorium
    WHERE tanggal_kadaluarsa IS NOT NULL
      AND TRIM(tanggal_kadaluarsa) <> ''
      AND STR_TO_DATE(tanggal_kadaluarsa, '%Y-%m-%d') IS NOT NULL
      AND STR_TO_DATE(tanggal_kadaluarsa, '%Y-%m-%d') <= CURDATE()
");
$totalUnitBahanExpired = (int)($qExpiredBahan->fetch_assoc()['jml'] ?? 0);


/* =================== DATA GRAFIK (ALAT) =================== */

$sqlKondisiAlat = "
    SELECT
        COALESCE(SUM(jumlah_baik), 0) AS baik,
        COALESCE(SUM(jumlah_rusak_ringan), 0) AS rusak_ringan,
        COALESCE(SUM(jumlah_rusak_berat), 0) AS rusak_berat,
        COALESCE(SUM(jumlah_hilang), 0) AS hilang
    FROM alat_laboratorium
";
$alatRow = $conn->query($sqlKondisiAlat)->fetch_assoc() ?: ['baik'=>0,'rusak_ringan'=>0,'rusak_berat'=>0,'hilang'=>0];

$labelsKondisiAlat = ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang'];
$dataKondisiAlat   = [
    (int)$alatRow['baik'],
    (int)$alatRow['rusak_ringan'],
    (int)$alatRow['rusak_berat'],
    (int)$alatRow['hilang'],
];

$labelsKondisiAlatJson = json_encode($labelsKondisiAlat);
$dataKondisiAlatJson   = json_encode($dataKondisiAlat);


/* =================== DATA GRAFIK (ALAT PER LOKASI) =================== */

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


/* =================== DATA GRAFIK (BAHAN) =================== */

$sqlKondisiBahan = "
    SELECT
        COALESCE(SUM(jumlah_baik), 0) AS baik,
        COALESCE(SUM(jumlah_rusak_ringan), 0) AS rusak_ringan,
        COALESCE(SUM(jumlah_rusak_berat), 0) AS rusak_berat,
        COALESCE(SUM(jumlah_hilang), 0) AS hilang
    FROM bahan_laboratorium
";
$bahanRow = $conn->query($sqlKondisiBahan)->fetch_assoc() ?: ['baik'=>0,'rusak_ringan'=>0,'rusak_berat'=>0,'hilang'=>0];

$labelsKondisiBahan = ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang'];
$dataKondisiBahan   = [
    (int)$bahanRow['baik'],
    (int)$bahanRow['rusak_ringan'],
    (int)$bahanRow['rusak_berat'],
    (int)$bahanRow['hilang'],
];

$labelsKondisiBahanJson = json_encode($labelsKondisiBahan);
$dataKondisiBahanJson   = json_encode($dataKondisiBahan);
?>

<style>
/* supaya chart tidak “ngegas” tinggi dan nabrak footer */
.chart-fixed {
    position: relative;
    height: 320px; /* bisa kamu naikkan 360 kalau mau */
}
.chart-fixed canvas{
    width: 100% !important;
    height: 100% !important;
}
</style>

<div class="container-fluid">

    <!-- Judul Dashboard -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    </div>

    <!-- Row Cards (ALAT) -->
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

    <!-- Row Cards (BAHAN) -> di bawah card alat -->
    <div class="row">

        <!-- Total Jenis Bahan -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Jenis Bahan
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($totalJenisBahan); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-vials fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Unit Bahan -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Unit Bahan
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($totalUnitBahan); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-flask fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Unit Bahan Kadaluarsa -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Total Unit Bahan Kadaluarsa
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($totalUnitBahanExpired); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Row Grafik (ALAT) -->
    <div class="row">

        <!-- Grafik Kondisi Alat -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Kondisi Alat (Unit)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-fixed">
                        <canvas id="chartKondisiAlat"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafik Total Unit Alat per Lokasi -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Total Unit Alat per Lokasi</h6>
                </div>
                <div class="card-body">
                    <div class="chart-fixed">
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

    <!-- Row Grafik (BAHAN) -->
    <div class="row">

        <!-- Grafik Kondisi Bahan -->
        <div class="col-xl-12 col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Kondisi Bahan (Unit)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-fixed">
                        <canvas id="chartKondisiBahan"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<?php include __DIR__ . "/templates/footer.php"; ?>

<!-- Chart.js -->
<script src="/mavis/assets/sbadmin2/vendor/chart.js/Chart.min.js"></script>

<script>
$(document).ready(function () {

    const labelsKondisiAlat = <?= $labelsKondisiAlatJson; ?>;
    const dataKondisiAlat   = <?= $dataKondisiAlatJson; ?>;

    const labelsLokasi      = <?= $labelsLokasiJson; ?>;
    const dataLokasi        = <?= $dataLokasiJson; ?>;

    const labelsKondisiBahan = <?= $labelsKondisiBahanJson; ?>;
    const dataKondisiBahan   = <?= $dataKondisiBahanJson; ?>;

    // Bar: Kondisi Alat (Chart.js v2)
    (function () {
        const ctx = document.getElementById('chartKondisiAlat');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labelsKondisiAlat,
                datasets: [{
                    data: dataKondisiAlat
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function (value) {
                                if (Number.isInteger(value)) return value;
                            }
                        }
                    }]
                }
            }
        });
    })();

    // Bar: Total Unit per Lokasi (Chart.js v2)
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
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    xAxes: [{
                        ticks: {
                            autoSkip: false,
                            maxRotation: 60,
                            minRotation: 0
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function (value) {
                                if (Number.isInteger(value)) return value;
                            }
                        }
                    }]
                }
            }
        });
    })();

    // Bar: Kondisi Bahan (Chart.js v2)
    (function () {
        const ctx = document.getElementById('chartKondisiBahan');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labelsKondisiBahan,
                datasets: [{
                    data: dataKondisiBahan
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function (value) {
                                if (Number.isInteger(value)) return value;
                            }
                        }
                    }]
                }
            }
        });
    })();

});
</script>
