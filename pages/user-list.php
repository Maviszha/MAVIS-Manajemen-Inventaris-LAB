<?php
include dirname(__DIR__) . "/templates/header.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin(); // hanya admin yang boleh masuk

$sql = "SELECT id, full_name, username, email, phone, role, foto, created_at
        FROM users
        ORDER BY role ASC, full_name ASC";
$res = $conn->query($sql);
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Kelola User</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar User</h6>
            <a href="user-add.php" class="btn btn-primary btn-sm">
                <i class="fas fa-user-plus"></i> Tambah User
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="userTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Role</th>
                        <th>Foto</th>
                        <th>Dibuat</th>
                        <th style="width:100px;">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($res && $res->num_rows > 0): ?>
                        <?php while ($u = $res->fetch_assoc()): ?>
                            <?php
                            $badge = 'secondary';
                            $label = $u['role'];

                            switch ($u['role']) {
                                case 'admin':
                                    $badge = 'danger';
                                    $label = 'Admin';
                                    break;
                                case 'guru':
                                    $badge = 'info';
                                    $label = 'Guru';
                                    break;
                                case 'siswa':
                                    $badge = 'success';
                                    $label = 'Siswa';
                                    break;
                                case 'laboran':
                                    $badge = 'primary';
                                    $label = 'Laboran';
                                    break;
                            }

                            $isSelf = ($u['id'] == ($_SESSION['user_id'] ?? 0));
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($u['full_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($u['email'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($u['phone'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="badge badge-<?= $badge; ?>"><?= $label; ?></span></td>
                                <td>
                                    <?php if (!empty($u['foto'])): ?>
                                        <img src="<?= htmlspecialchars($u['foto']); ?>"
                                             alt="Foto"
                                             style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($u['created_at'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-center">
                                    <a href="user-edit.php?id=<?= (int)$u['id']; ?>" class="btn btn-sm btn-warning mb-1">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if (!$isSelf): ?>
                                        <a href="user-delete.php?id=<?= (int)$u['id']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Yakin ingin menghapus user ini?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">Belum ada user.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>

<script>
$(document).ready(function() {
    $('#userTable').DataTable({
        "pageLength": 25
    });
});
</script>
