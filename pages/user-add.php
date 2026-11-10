<?php
include dirname(__DIR__) . "/templates/header.php";
include dirname(__DIR__) . "/config/log.php";

requireAdmin();

$roles = [
    'admin' => 'Administrator',
    'guru'  => 'Guru',
    'siswa' => 'Siswa',
];
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Tambah User</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="user-add-process.php" method="post">

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Username (unik)</label>
                    <input type="text" name="username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Email (opsional)</label>
                    <input type="email" name="email" class="form-control">
                </div>

                <div class="form-group">
                    <label>Telepon (opsional)</label>
                    <input type="text" name="phone" class="form-control">
                </div>

                <div class="form-group">
                    <label>Password Awal</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" class="form-control" required>
                        <?php foreach ($roles as $val => $label): ?>
                            <option value="<?= $val; ?>"><?= $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="user-list.php" class="btn btn-secondary">Kembali</a>

            </form>
        </div>
    </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
