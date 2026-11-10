<?php
include dirname(__DIR__) . "/templates/header.php";
include dirname(__DIR__) . "/config/log.php";

// Helper aman untuk output HTML (hindari Deprecated htmlspecialchars(NULL))
function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// Ambil role user
$role    = $_SESSION['role'] ?? '';
$keyword = $_GET['keyword'] ?? '';
$cleared = isset($_GET['cleared']) ? 1 : 0;

// Query log
$sql = "SELECT l.*, u.full_name 
        FROM system_log l
        LEFT JOIN users u ON l.user_id = u.id
        WHERE 1=1";

if ($keyword !== '') {
    $kw = '%' . $conn->real_escape_string($keyword) . '%';
    $sql .= " AND (
                l.module LIKE '$kw'
             OR l.action LIKE '$kw'
             OR l.username LIKE '$kw'
             OR l.description LIKE '$kw'
             )";
}

$sql .= " ORDER BY l.log_time DESC LIMIT 500";
$result = $conn->query($sql);
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">System Log</h1>

    <div class="card shadow mb-4">

        <!-- Header -->
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Aktivitas Sistem Terakhir</h6>

            <div class="d-flex align-items-center">

                <!-- Form Search -->
                <form class="form-inline mr-2" method="get">
                    <input type="text"
                           name="keyword"
                           class="form-control form-control-sm mr-2"
                           placeholder="Cari modul / user / aksi / deskripsi"
                           value="<?= h($keyword); ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-search"></i> Cari
                    </button>
                </form>

                <!-- Tombol Export & Clear Log (admin only) -->
                <?php if (strtolower((string)$role) === 'admin'): ?>
                    <a href="log-export.php?keyword=<?= urlencode((string)$keyword); ?>"
                       class="btn btn-sm btn-success mr-2">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>

                    <form method="post" action="log-clear.php"
                          onsubmit="return confirm('Yakin ingin menghapus SEMUA log? Tindakan ini tidak dapat dibatalkan.');">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash-alt"></i> Clear Log
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>

        <div class="card-body">

            <?php if ($cleared): ?>
                <div class="alert alert-success">Semua log berhasil dihapus.</div>
            <?php endif; ?>

            <!-- Data Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="logTable" width="100%" cellspacing="0">

                    <thead class="thead-light">
                        <tr>
                            <th>Waktu</th>
                            <th>User</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Deskripsi</th>
                            <th>IP</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>

                                <?php
                                // Tentukan warna berdasarkan action (aman jika NULL)
                                $action   = strtolower((string)($row['action'] ?? ''));
                                $rowClass = '';
                                $badgeCls = 'badge-secondary';

                                if (in_array($action, ['create', 'insert', 'login_success', 'import_csv', 'export_csv'], true)) {
                                    $rowClass = 'table-success';
                                    $badgeCls = 'badge-success';
                                } elseif (in_array($action, ['update', 'edit'], true)) {
                                    $rowClass = 'table-warning';
                                    $badgeCls = 'badge-warning';
                                } elseif (in_array($action, ['delete', 'clear', 'login_failed'], true)) {
                                    $rowClass = 'table-danger';
                                    $badgeCls = 'badge-danger';
                                } elseif (in_array($action, ['logout'], true)) {
                                    $rowClass = 'table-info';
                                    $badgeCls = 'badge-info';
                                }

                                $namaUser = $row['full_name'] ?? '';
                                $username = $row['username'] ?? '';
                                $displayUser = $namaUser !== '' ? $namaUser : ($username !== '' ? $username : '-');
                                ?>

                                <tr class="<?= h($rowClass); ?>">
                                    <td><?= h($row['log_time'] ?? ''); ?></td>
                                    <td><?= h($displayUser); ?></td>
                                    <td><?= h($row['module'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge <?= h($badgeCls); ?>">
                                            <?= h($row['action'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td><?= nl2br(h($row['description'] ?? '')); ?></td>
                                    <td><?= h($row['ip_address'] ?? ''); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada log.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>

            <small class="text-muted">
                Menampilkan maksimal 500 log terakhir.
            </small>

        </div>
    </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>

<!-- DataTables -->
<script>
$(document).ready(function() {
    $('#logTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25
    });
});
</script>
