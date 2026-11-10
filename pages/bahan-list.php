<?php
// pages/bahan-list.php
include dirname(__DIR__) . "/templates/header.php";

$role           = currentUserRole();
$canManageBahan = canManageAlat(); // admin only (selaras modul alat)

$success = $_GET['success'] ?? '';
$update  = $_GET['update'] ?? '';
$msg     = $_GET['msg'] ?? '';

$keyword    = trim($_GET['keyword'] ?? '');
$lokasi_val = $_GET['lokasi'] ?? '';          // dropdown seperti alat
$lokasi_id  = ($lokasi_val === '' ? null : (int)$lokasi_val);

// Dropdown lokasi (untuk filter)
$lokasiRes = $conn->query("SELECT id, nama_lokasi FROM lokasi_alat ORDER BY nama_lokasi ASC");

// ===== Query data bahan (prepared + filter lokasi terpisah) =====
$sql = "
    SELECT
        b.*,
        sb.nama_spesifikasi,
        sat.nama_satuan,
        sp.nama_sumber,
        la.nama_lokasi
    FROM bahan_laboratorium b
    LEFT JOIN spesifikasi_bahan sb ON b.spesifikasi_id = sb.id
    LEFT JOIN satuan_bahan sat ON b.satuan_id = sat.id
    LEFT JOIN sumber_pengadaan sp ON b.sumber_pengadaan_id = sp.id
    LEFT JOIN lokasi_alat la ON b.lokasi_id = la.id
    WHERE 1=1
";

$types  = "";
$params = [];

if ($keyword !== '') {
    $sql .= "
        AND (
            b.nama_bahan           LIKE CONCAT('%', ?, '%')
            OR b.no_inventaris     LIKE CONCAT('%', ?, '%')
            OR b.rumus_kimia       LIKE CONCAT('%', ?, '%')
            OR COALESCE(sb.nama_spesifikasi,'') LIKE CONCAT('%', ?, '%')
            OR COALESCE(sat.nama_satuan,'')     LIKE CONCAT('%', ?, '%')
            OR COALESCE(sp.nama_sumber,'')      LIKE CONCAT('%', ?, '%')
            OR COALESCE(b.volume,'')            LIKE CONCAT('%', ?, '%')
            OR DATE_FORMAT(b.tahun_pengadaan, '%Y-%m-%d') LIKE CONCAT('%', ?, '%')
            OR COALESCE(DATE_FORMAT(b.tanggal_kadaluarsa, '%Y-%m-%d'),'') LIKE CONCAT('%', ?, '%')
            OR COALESCE(b.keterangan,'') LIKE CONCAT('%', ?, '%')
        )
    ";
    // 10 parameter string
    $types .= str_repeat("s", 10);
    for ($i=0; $i<10; $i++) $params[] = $keyword;
}

// Filter lokasi (terpisah, seperti alat)
if ($lokasi_id !== null) {
    $sql .= " AND b.lokasi_id = ? ";
    $types .= "i";
    $params[] = $lokasi_id;
}

$sql .= " ORDER BY b.nama_bahan ASC ";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8'));
}

if ($types !== '') {
    // bind_param butuh reference
    $bindArgs = [];
    $bindArgs[] = $types;
    foreach ($params as $k => $v) {
        $bindArgs[] = &$params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindArgs);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<style>
    .badge-status { font-size: 13px; padding: 6px 10px; border-radius: 6px; }
    #bahanTable { width: 100% !important; }
    #bahanTable th, #bahanTable td { vertical-align: middle; }
    #bahanTable th.col-nama, #bahanTable td.col-nama { min-width: 160px; }
    #bahanTable th.col-ket,  #bahanTable td.col-ket  { min-width: 160px; }
    #bahanTable th.col-aksi, #bahanTable td.col-aksi { width: 72px; min-width: 72px; white-space: nowrap; }
    .aksi-btn-group { display:inline-flex; gap:4px; justify-content:center; align-items:center; flex-wrap:nowrap; }
    .aksi-btn-group a { display:inline-flex!important; align-items:center; justify-content:center; width:28px; height:28px; padding:0!important; font-size:.75rem; }
</style>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Data Bahan Laboratorium</h1>

    <!-- Toast Container -->
    <div class="toast-container position-fixed" style="top: 1rem; right: 1rem; z-index: 1060;">
        <?php if ($success): ?>
            <div class="alert alert-success alert-auto-close shadow-sm mb-2" role="alert">
                <i class="fas fa-check-circle mr-2"></i> Data bahan berhasil <strong>ditambahkan</strong>.
            </div>
        <?php endif; ?>

        <?php if ($update): ?>
            <div class="alert alert-info alert-auto-close shadow-sm mb-2" role="alert">
                <i class="fas fa-sync-alt mr-2"></i> Data bahan berhasil <strong>diperbarui</strong>.
            </div>
        <?php endif; ?>

        <?php if ($msg === 'deleted'): ?>
            <div class="alert alert-warning alert-auto-close shadow-sm mb-2" role="alert">
                <i class="fas fa-trash-alt mr-2"></i> Data bahan berhasil <strong>dihapus</strong>.
            </div>
        <?php endif; ?>

        <?php if ($msg === 'invalid'): ?>
            <div class="alert alert-danger alert-auto-close shadow-sm mb-2" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i> ID tidak valid.
            </div>
        <?php endif; ?>

        <?php if ($msg === 'notfound'): ?>
            <div class="alert alert-danger alert-auto-close shadow-sm mb-2" role="alert">
                <i class="fas fa-search-minus mr-2"></i> Data bahan tidak ditemukan.
            </div>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between">

                <!-- Kiri: tombol -->
                <div class="mb-2 mb-xl-0">
                    <?php if ($canManageBahan): ?>
                        <a href="bahan-add.php" class="btn btn-primary mr-2 mb-2 mb-sm-0">
                            <i class="fas fa-plus"></i> Tambah Bahan
                        </a>

                        <a href="spesifikasi-bahan-list.php" class="btn btn-success mr-2 mb-2 mb-sm-0">
                            <i class="fas fa-list"></i> Kelola Spesifikasi
                        </a>

                        <a href="lokasi-list.php" class="btn btn-info mr-2 mb-2 mb-sm-0">
                            <i class="fas fa-map-marker-alt"></i> Kelola Lokasi
                        </a>

                        <a href="satuan-bahan-list.php" class="btn btn-secondary mr-2 mb-2 mb-sm-0">
                            <i class="fas fa-ruler"></i> Kelola Satuan
                        </a>

                        <a href="sumber-list.php" class="btn btn-warning mb-2 mb-sm-0">
                            <i class="fas fa-coins"></i> Kelola Sumber Dana
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Kanan: keyword + dropdown lokasi (seperti alat) -->
                <form method="GET" class="form-inline mt-2 mt-xl-0">
                    <div class="form-group mr-2 mb-2 mb-sm-0">
                        <input type="text" name="keyword"
                               placeholder="Cari bahan (nama/no inv/sumber/dll)"
                               value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
                               class="form-control"
                               style="min-width:260px;">
                    </div>

                    <div class="form-group mr-2 mb-2 mb-sm-0">
                        <select name="lokasi" class="form-control" style="min-width:190px;">
                            <option value="">Semua Lokasi</option>
                            <?php if ($lokasiRes && $lokasiRes->num_rows > 0): ?>
                                <?php while ($l = $lokasiRes->fetch_assoc()): ?>
                                    <option value="<?= (int)$l['id']; ?>"
                                        <?= ($lokasi_val !== '' && (int)$lokasi_val === (int)$l['id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($l['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-search"></i> Cari
                    </button>

                    <?php if ($keyword !== '' || $lokasi_val !== ''): ?>
                        <a href="bahan-list.php" class="btn btn-secondary ml-2">Reset</a>
                    <?php endif; ?>
                </form>

            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="bahanTable" class="table table-bordered table-striped" cellspacing="0">
                    <thead class="thead-light">
                    <tr>
                        <th class="col-nama">Nama Bahan</th>
                        <th>Rumus Kimia</th>
                        <th>Spesifikasi</th>
                        <th>Lokasi</th>
                        <th>Vol.</th>
                        <th>Sat.</th>
                        <th>Thn Pengadaan</th>
                        <th>No Inventaris</th>
                        <th>Sumber Dana</th>
                        <th>Kadaluarsa</th>
                        <th>Ttl</th>
                        <th>Baik</th>
                        <th>RB</th>
                        <th>RR</th>
                        <th>H</th>
                        <th class="col-ket">Keterangan</th>
                        <?php if ($canManageBahan): ?>
                            <th class="col-aksi">Aksi</th>
                        <?php endif; ?>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            $badge_baik   = "<span class='badge badge-success badge-status'>".(int)$row['jumlah_baik']."</span>";
                            $badge_berat  = "<span class='badge badge-danger badge-status'>".(int)$row['jumlah_rusak_berat']."</span>";
                            $badge_ringan = "<span class='badge badge-warning badge-status'>".(int)$row['jumlah_rusak_ringan']."</span>";
                            $badge_hilang = "<span class='badge badge-secondary badge-status'>".(int)$row['jumlah_hilang']."</span>";
                            ?>
                            <tr>
                                <td class="col-nama"><?= htmlspecialchars($row['nama_bahan'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['rumus_kimia'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['nama_spesifikasi'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['nama_lokasi'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['volume'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['nama_satuan'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['tahun_pengadaan'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['no_inventaris'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['nama_sumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['tanggal_kadaluarsa'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>

                                <td><?= (int)$row['jumlah_total']; ?></td>
                                <td><?= $badge_baik; ?></td>
                                <td><?= $badge_berat; ?></td>
                                <td><?= $badge_ringan; ?></td>
                                <td><?= $badge_hilang; ?></td>

                                <td class="col-ket"><?= htmlspecialchars($row['keterangan'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>

                                <?php if ($canManageBahan): ?>
                                    <td class="text-center col-aksi">
                                        <div class="aksi-btn-group">
                                            <a href="bahan-edit.php?id=<?= (int)$row['id']; ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="bahan-delete-process.php" style="display:inline;"
											     onsubmit="return confirm('Yakin ingin menghapus data bahan ini?');">
												 <input type="hidden" name="id" value="<?= (int)$row['id']; ?>">
												 <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
												      <i class="fas fa-trash"></i>
											     </button>
											</form>	 
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $canManageBahan ? 17 : 16; ?>" class="text-center">
                                Belum ada data bahan.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>

<?php
$stmt->close();
include dirname(__DIR__) . "/templates/footer.php";
?>

<script>
$(document).ready(function () {
    var table = $('#bahanTable').DataTable({
        pageLength: 25,
        autoWidth: false,
        dom: 'lrtip'
    });

    setTimeout(function () {
        $('.alert-auto-close').fadeTo(500, 0).slideUp(500, function () {
            $(this).remove();
        });
    }, 4000);

    $(window).on('resize', function () {
        table.columns.adjust();
    });
});
</script>
