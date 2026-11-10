<?php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: sumber-list.php");
    exit;
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT id, nama_sumber FROM sumber_pengadaan WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $stmt->close();
    header("Location: sumber-list.php");
    exit;
}

$data = $result->fetch_assoc();
$stmt->close();

$error = $_GET['error'] ?? '';
?>

<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Edit Sumber Pengadaan</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-warning">Form Edit Sumber</h6>
        </div>
        <div class="card-body">

            <?php if ($error === 'empty'): ?>
                <div class="alert alert-danger">Nama sumber tidak boleh kosong.</div>
            <?php endif; ?>

            <form action="sumber-edit-process.php" method="POST">
                <input type="hidden" name="id" value="<?= (int)$data['id']; ?>">

                <div class="form-group">
                    <label>Nama Sumber</label>
                    <input type="text"
                           name="nama_sumber"
                           class="form-control"
                           value="<?= htmlspecialchars($data['nama_sumber'], ENT_QUOTES, 'UTF-8'); ?>"
                           required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="sumber-list.php" class="btn btn-secondary">Batal</a>
            </form>

        </div>
    </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
