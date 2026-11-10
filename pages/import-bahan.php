<?php
// pages/import-bahan.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";
requireAdmin();

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function table_exists(mysqli $conn, string $table): bool {
  $t = $conn->real_escape_string($table);
  $res = $conn->query("SHOW TABLES LIKE '{$t}'");
  return $res && $res->num_rows > 0;
}

function pick_table(mysqli $conn, array $candidates): ?string {
  foreach ($candidates as $t) {
    if (table_exists($conn, $t)) return $t;
  }
  return null;
}

/**
 * DOWNLOAD TEMPLATE CSV
 */
if (isset($_GET['template']) && $_GET['template'] === '1') {
  $headers = [
    'nama_bahan','rumus_kimia','spesifikasi','lokasi','volume','satuan',
    'tahun_pengadaan','no_inventaris','sumber_pengadaan','tanggal_kadaluarsa',
    'jumlah_total','jumlah_baik','jumlah_rusak_berat','jumlah_rusak_ringan','jumlah_hilang','keterangan'
  ];
  $example = [
    'Asam Sulfat','H2SO4','Cair pekat','Gudang Kimia','1000','ml',
    '2024-01-10','INV-001','BOS','2026-12-31',
    '10','10','0','0','0','Contoh bahan'
  ];

  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="template-import-bahan.csv"');
  header('Pragma: no-cache');
  header('Expires: 0');

  $out = fopen('php://output', 'w');
  fwrite($out, "\xEF\xBB\xBF");
  fputcsv($out, $headers);
  fputcsv($out, $example);
  fclose($out);
  exit;
}

require_once dirname(__DIR__) . "/templates/header.php";

$success = isset($_GET['success']) ? (int)$_GET['success'] : 0;
$fail    = trim((string)($_GET['fail'] ?? ''));

$limit = 50;
$rows = [];

/**
 * Preview: join jika tabel master ada, kalau tidak ada tampilkan ID saja
 */
$tblSpes   = pick_table($conn, ['spesifikasi_bahan','spesifikasi_alat','spesifikasi','spesifikasi_lab']);
$tblSatuan = pick_table($conn, ['satuan_bahan','satuan_alat','satuan_lab','satuan_laboratorium','satuan']);
$tblSumber = pick_table($conn, ['sumber_pengadaan','sumber_dana','sumber_pengadaan_alat','sumber_pengadaan_bahan']);
$tblLokasi = 'lokasi_alat'; // fix sesuai kamu

$joinSpes   = $tblSpes   ? "LEFT JOIN `$tblSpes` sb ON sb.id = b.spesifikasi_id" : "";
$joinSatuan = $tblSatuan ? "LEFT JOIN `$tblSatuan` st ON st.id = b.satuan_id" : "";
$joinSumber = $tblSumber ? "LEFT JOIN `$tblSumber` sp ON sp.id = b.sumber_pengadaan_id" : "";
$joinLokasi = table_exists($conn, $tblLokasi) ? "LEFT JOIN `$tblLokasi` la ON la.id = b.lokasi_id" : "";

$colSpes   = $tblSpes   ? (table_exists($conn, $tblSpes) ? "sb.nama_spesifikasi" : "NULL") : "NULL";
$colSatuan = $tblSatuan ? "st.nama_satuan" : "NULL";
$colSumber = $tblSumber ? (table_exists($conn, $tblSumber) ? "sp.nama_sumber" : "NULL") : "NULL";
$colLokasi = table_exists($conn, $tblLokasi) ? "la.nama_lokasi" : "NULL";

try {
  $sql = "
    SELECT
      b.nama_bahan,
      b.rumus_kimia,
      {$colSpes}   AS spesifikasi,
      {$colLokasi} AS lokasi,
      b.volume,
      {$colSatuan} AS satuan,
      b.tahun_pengadaan,
      b.no_inventaris,
      {$colSumber} AS sumber_pengadaan,
      b.tanggal_kadaluarsa,
      b.jumlah_total,
      b.jumlah_baik,
      b.jumlah_rusak_berat,
      b.jumlah_rusak_ringan,
      b.jumlah_hilang,
      b.keterangan,
      b.spesifikasi_id,
      b.satuan_id,
      b.lokasi_id,
      b.sumber_pengadaan_id
    FROM bahan_laboratorium b
    {$joinSpes}
    {$joinLokasi}
    {$joinSatuan}
    {$joinSumber}
    ORDER BY b.id DESC
    LIMIT ?
  ";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
  }
} catch (Throwable $e) {
  $rows = [];
}
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Import Bahan Laboratorium (CSV)</h1>

  <?php if ($success): ?>
    <div class="alert alert-success shadow-sm">
      <i class="fas fa-check-circle mr-2"></i> Import selesai.
    </div>
  <?php endif; ?>

  <?php if ($fail !== ''): ?>
    <div class="alert alert-danger shadow-sm">
      <i class="fas fa-exclamation-triangle mr-2"></i> Import gagal: <?= h($fail) ?>
    </div>
  <?php endif; ?>

  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Format CSV (Semua Wajib)</h6>

      <a class="btn btn-sm btn-outline-primary" href="import-bahan.php?template=1" target="_blank" rel="noopener">
        <i class="fas fa-download"></i> Download Template CSV
      </a>
    </div>

    <div class="card-body">
      <div class="alert alert-info shadow-sm">
        Header wajib:
        <br>
        <code>
          nama_bahan, rumus_kimia, spesifikasi, lokasi, volume, satuan, tahun_pengadaan, no_inventaris,
          sumber_pengadaan, tanggal_kadaluarsa, jumlah_total, jumlah_baik, jumlah_rusak_berat,
          jumlah_rusak_ringan, jumlah_hilang, keterangan
        </code>
        <br><br>
        Validasi:
        <ul class="mb-0">
          <li><b>Semua kolom wajib terisi</b></li>
          <li><b>tahun_pengadaan</b> harus tanggal valid dan minimal tahun 2000</li>
          <li><b>tanggal_kadaluarsa</b> harus tanggal valid</li>
          <li><b>jumlah_baik + RB + RR + H = jumlah_total</b></li>
        </ul>
      </div>

      <form id="importForm" action="import-bahan-process.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label>File CSV</label>
          <input type="file" name="csv" class="form-control" accept=".csv,text/csv" required>
        </div>

        <div class="form-group">
          <label>
            <input type="checkbox" name="has_header" value="1" checked>
            Baris pertama adalah header
          </label>
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fas fa-file-import"></i> Import Sekarang
        </button>
        <a href="bahan-list.php" class="btn btn-secondary">Kembali</a>
      </form>
    </div>
  </div>

  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Preview Data Terbaru</h6>
      <small class="text-muted">Menampilkan <?= (int)$limit ?> data terakhir</small>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped" id="tblBahan" width="100%" cellspacing="0">
          <thead class="thead-light">
            <tr>
              <th>Nama</th><th>Rumus</th><th>Spesifikasi</th><th>Lokasi</th><th>Vol</th><th>Sat</th><th>Pengadaan</th>
              <th>No Inventaris</th><th>Sumber Dana</th><th>Kadaluarsa</th><th>Total</th><th>Baik</th>
              <th>RB</th><th>RR</th><th>H</th><th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$rows): ?>
              <tr><td colspan="16" class="text-center text-muted">Belum ada data / preview tidak tersedia.</td></tr>
            <?php else: ?>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= h($r['nama_bahan']) ?></td>
                  <td><?= h($r['rumus_kimia']) ?></td>
                  <td><?= h($r['spesifikasi'] ?? ('#'.$r['spesifikasi_id'])) ?></td>
                  <td><?= h($r['lokasi'] ?? ('#'.$r['lokasi_id'])) ?></td>
                  <td><?= h($r['volume']) ?></td>
                  <td><?= h($r['satuan'] ?? ('#'.$r['satuan_id'])) ?></td>
                  <td><?= h($r['tahun_pengadaan']) ?></td>
                  <td><b><?= h($r['no_inventaris']) ?></b></td>
                  <td><?= h($r['sumber_pengadaan'] ?? ('#'.$r['sumber_pengadaan_id'])) ?></td>
                  <td><?= h($r['tanggal_kadaluarsa']) ?></td>
                  <td><?= h($r['jumlah_total']) ?></td>
                  <td><?= h($r['jumlah_baik']) ?></td>
                  <td><?= h($r['jumlah_rusak_berat']) ?></td>
                  <td><?= h($r['jumlah_rusak_ringan']) ?></td>
                  <td><?= h($r['jumlah_hilang']) ?></td>
                  <td><?= h($r['keterangan']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php require_once dirname(__DIR__) . "/templates/footer.php"; ?>

<script>
document.getElementById("importForm").addEventListener("submit", function(e){
  if(!confirm("Yakin ingin mengimpor data bahan dari CSV?")) e.preventDefault();
});
document.addEventListener('DOMContentLoaded', function() {
  if (window.$ && $.fn.DataTable) {
    $('#tblBahan').DataTable({ pageLength: 25, autoWidth: false, order: [] });
  }
});
</script>
