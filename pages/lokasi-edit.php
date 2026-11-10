<?php
// Header SB Admin 2 (sudah include db + auth + sidebar)
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();
// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: lokasi-list.php");
    exit;
}

$id = (int)$_GET['id'];

// Ambil data lokasi dengan prepared statement
$stmt = $conn->prepare("SELECT id, nama_lokasi FROM lokasi_alat WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $stmt->close();
    header("Location: lokasi-list.php");
    exit;
}

$data = $result->fetch_assoc();
$stmt->close();

// Baca pesan error jika ada
$error = $_GET['error'] ?? '';
?>

<div class="container-fluid">

    <!-- Judul Halaman -->
    <h1 class="h3 mb-4 text-gray-800">Edit Lokasi Penyimpanan</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-warning">Form Edit Lokasi</h6>
        </div>

        <div class="card-body">

            <?php if ($error === 'empty'): ?>
                <div class="alert alert-danger">
                    Nama lokasi tidak boleh kosong.
                </div>
            <?php endif; ?>

            <form action="lokasi-edit-process.php" method="POST">
                <input type="hidden" name="id" value="<?= (int)$data['id']; ?>">

                <div class="form-group">
                    <label>Nama Lokasi</label>
                    <input type="text"
                           name="nama_lokasi"
                           class="form-control"
                           value="<?= htmlspecialchars($data['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?>"
                           required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="lokasi-list.php" class="btn btn-secondary">
                    Batal
                </a>
            </form>
        </div>
    </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
