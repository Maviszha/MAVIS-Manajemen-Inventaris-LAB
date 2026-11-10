<?php
// /mavis/pages/user-edit.php
include dirname(__DIR__) . "/templates/header.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: user-list.php");
    exit;
}

$id = (int)$_GET['id'];

$res = $conn->query("SELECT * FROM users WHERE id = {$id} LIMIT 1");
if (!$res || $res->num_rows === 0) {
    header("Location: user-list.php");
    exit;
}
$user = $res->fetch_assoc();

$roles = [
    'admin' => 'Administrator',
    'guru'  => 'Guru',
    'siswa' => 'Siswa',
];

// kalau ada role lama 'laboran', tetap dimunculkan supaya bisa diganti
if ($user['role'] === 'laboran') {
    $roles = ['laboran' => 'Laboran (lama)'] + $roles;
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Edit User</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="user-edit-process.php" method="post">
                <input type="hidden" name="id" value="<?= (int)$user['id']; ?>">

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="full_name" class="form-control"
                           value="<?= htmlspecialchars($user['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control"
                           value="<?= htmlspecialchars($user['username']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($user['email']); ?>">
                </div>

                <div class="form-group">
                    <label>Telepon</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= htmlspecialchars($user['phone']); ?>">
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" class="form-control" required>
                        <?php foreach ($roles as $val => $label): ?>
                            <option value="<?= $val; ?>"
                                <?= ($user['role'] === $val ? 'selected' : ''); ?>>
                                <?= $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <hr>

                <div class="form-group">
                    <label>Password Baru (opsional)</label>
                    <input type="password" name="new_password" class="form-control"
                           placeholder="Kosongkan jika tidak ingin mengganti">
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
                <a href="user-list.php" class="btn btn-secondary">Kembali</a>

            </form>
        </div>
    </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
