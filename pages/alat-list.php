<?php
// Header SB Admin 2 (sudah include db.php + auth + sidebar)
include dirname(__DIR__) . "/templates/header.php";

// helper role
$role          = currentUserRole();
$canManageAlat = canManageAlat();

?>

<style>
    .badge-status {
        font-size: 13px;
        padding: 6px 10px;
        border-radius: 6px;
    }

     #alatTable {
        width: 100% !important;
    }

    #alatTable th,
    #alatTable td {
        vertical-align: middle;
    }

    /* Sedikit atur lebar beberapa kolom */
    #alatTable th.col-nama,
    #alatTable td.col-nama {
        min-width: 190px;
    }
    #alatTable th.col-lokasi,
    #alatTable td.col-lokasi {
        min-width: 150px;
    }
    #alatTable th.col-ket,
    #alatTable td.col-ket {
        min-width: 220px;
    }
	.aksi-btn-group {
        display: flex;
        gap: 6px;
        justify-content: center;
        align-items: center;
    }
    .aksi-btn-group a {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0 !important;
    }
</style>

<div class="container-fluid">

    <!-- Judul Halaman -->
    <h1 class="h3 mb-4 text-gray-800">Data Alat Laboratorium</h1>
  <?php
$success = $_GET['success'] ?? '';
$update  = $_GET['update'] ?? '';
$msg     = $_GET['msg'] ?? '';
?>

<!-- Toast Container (pojok kanan atas) -->
<div class="toast-container position-fixed" style="top: 1rem; right: 1rem; z-index: 1060;">

    <?php if ($success): ?>
        <!-- Tambah alat -->
        <div class="alert alert-success alert-auto-close shadow-sm mb-2" role="alert">
            <i class="fas fa-check-circle mr-2"></i>
            Data alat berhasil <strong>ditambahkan</strong>.
        </div>
    <?php endif; ?>

    <?php if ($update): ?>
        <!-- Update alat -->
        <div class="alert alert-info alert-auto-close shadow-sm mb-2" role="alert">
            <i class="fas fa-sync-alt mr-2"></i>
            Data alat berhasil <strong>diperbarui</strong>.
        </div>
    <?php endif; ?>

    <?php if ($msg === 'deleted'): ?>
        <!-- Hapus alat -->
        <div class="alert alert-warning alert-auto-close shadow-sm mb-2" role="alert">
            <i class="fas fa-trash-alt mr-2"></i>
            Data alat berhasil <strong>dihapus</strong>.
        </div>
    <?php endif; ?>

    <?php if ($msg === 'invalid'): ?>
        <!-- ID tidak valid -->
        <div class="alert alert-danger alert-auto-close shadow-sm mb-2" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            ID tidak valid.
        </div>
    <?php endif; ?>

    <?php if ($msg === 'notfound'): ?>
        <!-- Data tdk ditemukan -->
        <div class="alert alert-danger alert-auto-close shadow-sm mb-2" role="alert">
            <i class="fas fa-search-minus mr-2"></i>
            Data alat tidak ditemukan.
        </div>
    <?php endif; ?>

</div>

    <!-- Card utama -->
    <div class="card shadow mb-4">

        <!-- Header card: tombol tambah, kelola lokasi, filter -->
        <div class="card-header py-3">

            <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between">

                <!-- Kiri: tombol tambah & kelola (hanya admin) -->
                <div class="mb-2 mb-xl-0">
                    <?php if ($canManageAlat): ?>
                        <a href="alat-add.php" class="btn btn-primary mr-2 mb-2 mb-sm-0">
                            <i class="fas fa-plus"></i> Tambah Alat
                        </a>

                        <a href="lokasi-list.php" class="btn btn-success mr-1 mb-2 mb-sm-0">
                            <i class="fas fa-map-marker-alt"></i> Kelola Lokasi
                        </a>

                        <a href="sumber-list.php" class="btn btn-warning mb-2 mb-sm-0 text-white">
                            <i class="fas fa-database"></i> Kelola Sumber Dana
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Kanan: form filter -->
                <form method="GET" class="form-inline mt-2 mt-xl-0">
                    <?php
                    $keyword_val = $_GET['keyword'] ?? '';
                    $lokasi_val  = $_GET['lokasi'] ?? '';
                    ?>
                    <div class="form-group mr-2 mb-2 mb-sm-0">
                        <input type="text" name="keyword"
                               placeholder="Aku buat nyari lho, pakai aku...:)"
                               value="<?= htmlspecialchars($keyword_val, ENT_QUOTES, 'UTF-8'); ?>"
                               class="form-control"
                               style="min-width:260px;">
                    </div>

                    <div class="form-group mr-2 mb-2 mb-sm-0">
                        <select name="lokasi" class="form-control" style="min-width:190px;">
                            <option value="">Semua Lokasi</option>
                            <?php
                            $lokasiRes = $conn->query("SELECT * FROM lokasi_alat ORDER BY nama_lokasi ASC");
                            if ($lokasiRes && $lokasiRes->num_rows > 0):
                                while ($l = $lokasiRes->fetch_assoc()):
                                    $selected = ($lokasi_val !== '' && (int)$lokasi_val === (int)$l['id']) ? 'selected' : '';
                                    ?>
                                    <option value="<?= (int)$l['id']; ?>" <?= $selected; ?>>
                                        <?= htmlspecialchars($l['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php
                                endwhile;
                            endif;
                            ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-search"></i> Cari
                    </button>
                </form>
            </div>
        </div>

        <!-- Body card: tabel -->
        <div class="card-body">
            <div class="table-responsive">
                <table id="alatTable" class="table table-bordered table-striped" cellspacing="0">
                    <thead class="thead-light">
                    <tr>
                        <th class="col-nama">Nama Alat</th>
                        <th>Merk</th>
                        <th>Spesifikasi</th>
                        <th class="col-lokasi">Lokasi</th>
                        <th>Thn Pengadaan</th>
                        <th>Sumber Dana</th>
                        <th>No Inventaris</th>
                        <th>Total</th>
                        <th>Baik</th>
                        <th>RB</th>
                        <th>RR</th>
                        <th>H</th>
                        <th class="col-ket">Keterangan</th>
                        <?php if ($canManageAlat): ?>
                            <th style="width: 90px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>

                    <?php
                    // === Menangkap Input Pencarian ===
                    $keyword       = $_GET['keyword'] ?? '';
                    $lokasi_filter = $_GET['lokasi'] ?? '';

                    // === Base Query ===
                    $query = "
                        SELECT a.*,
                               l.nama_lokasi,
                               s.nama_sumber
                        FROM alat_laboratorium a
                        LEFT JOIN lokasi_alat l ON a.lokasi_id = l.id
                        LEFT JOIN sumber_pengadaan s ON a.sumber_pengadaan_id = s.id
                        WHERE 1=1
                    ";

                    // Filter keyword
                    if ($keyword !== '') {
                        $keywordEsc = $conn->real_escape_string($keyword);

                        $query .= "
                              AND (
                                   a.nama_alat       LIKE '%{$keywordEsc}%'
                                   OR a.merk_alat       LIKE '%{$keywordEsc}%'
                                   OR a.spesifikasi     LIKE '%{$keywordEsc}%'
                                   OR a.tahun_pengadaan LIKE '%{$keywordEsc}%'
                                   OR s.nama_sumber     LIKE '%{$keywordEsc}%'
                                   OR a.no_inventaris   LIKE '%{$keywordEsc}%'
                              )
                           ";
                      }

                    // Filter lokasi
                    if ($lokasi_filter !== '') {
                        $lokasi_id = (int)$lokasi_filter;
                        $query .= " AND a.lokasi_id = $lokasi_id ";
                    }

                    $query .= " ORDER BY a.nama_alat ASC ";

                    $result = $conn->query($query);

                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):

                            $badge_baik   = "<span class='badge badge-success badge-status'>".(int)$row['jumlah_baik']."</span>";
                            $badge_berat  = "<span class='badge badge-danger badge-status'>".(int)$row['jumlah_rusak_berat']."</span>";
                            $badge_ringan = "<span class='badge badge-warning badge-status'>".(int)$row['jumlah_rusak_ringan']."</span>";
                            $badge_hilang = "<span class='badge badge-secondary badge-status'>".(int)$row['jumlah_hilang']."</span>";
                            ?>
                            <tr>
                                <td class="col-nama">
                                    <?= htmlspecialchars($row['nama_alat'], ENT_QUOTES, 'UTF-8'); ?>
								</td>
                                <td><?= htmlspecialchars($row['merk_alat'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['spesifikasi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="col-lokasi">
                                    <?= htmlspecialchars($row['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td><?= htmlspecialchars($row['tahun_pengadaan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['nama_sumber'], ENT_QUOTES, 'UTF-8'); ?></td>

                                <td><?= htmlspecialchars($row['no_inventaris'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>

                                <td><?= (int)$row['jumlah_total']; ?></td>
                                <td><?= $badge_baik; ?></td>
                                <td><?= $badge_berat; ?></td>
                                <td><?= $badge_ringan; ?></td>
                                <td><?= $badge_hilang; ?></td>
                                <td class="col-ket">
                                    <?= htmlspecialchars($row['keterangan'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>

                                <?php if ($canManageAlat): ?>
                                    <td class="text-center">
                                        <div class="aksi-btn-group">
                                          <a href="alat-edit.php?id=<?= (int)$row['id']; ?>" class="btn btn-warning btn-sm mb-0" title="Edit">
                                              <i class="fas fa-edit"></i>
                                          </a>
                                          <a href="alat-delete.php?id=<?= (int)$row['id']; ?>"
                                             class="btn btn-danger btn-sm"
                                             title="Hapus"
                                             onclick="return confirm('Yakin ingin menghapus data ini?');">
                                              <i class="fas fa-trash"></i>
                                          </a>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php
                        endwhile;
                    else:
                        ?>
                        <tr>
                            <td colspan="<?= $canManageAlat ? 14 : 13; ?>" class="text-center">
                                Belum ada data alat.
                            </td>
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
    $(document).ready(function () {
        $('#alatTable').DataTable({
            pageLength: 25,
            autoWidth: false,
            dom: 'lrtip'
        });

        // Auto close alert
        setTimeout(function () {
            $('.alert-auto-close').fadeTo(500, 0).slideUp(500, function () {
                $(this).remove();
            });
        }, 4000);
    });
</script>
