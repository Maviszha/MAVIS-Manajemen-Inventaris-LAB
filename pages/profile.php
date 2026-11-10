<?php
// Halaman profil user (foto + biodata + ganti password)
include dirname(__DIR__) . "/templates/header.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /mavis/login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

$successPhoto = '';
$errorPhoto   = '';

$successBio = '';
$errorBio   = '';

$successPass = '';
$errorPass   = '';

// Ambil data user dari DB
$stmt = $conn->prepare("SELECT username, full_name, email, phone, role, foto FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res  = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

// Fallback kalau user tidak ditemukan
if (!$user) {
    echo "<div class='container-fluid mt-4'><div class='alert alert-danger'>User tidak ditemukan.</div></div>";
    include dirname(__DIR__) . "/templates/footer.php";
    exit;
}

$currentPhoto = "/mavis/templates/img/user-default.png";
if (!empty($user['foto'])) {
    $currentPhoto = $user['foto'];
}

// Isi default biodata
$currentFullName = $user['full_name'] ?? '';
$currentEmail    = $user['email'] ?? '';
$currentPhone    = $user['phone'] ?? '';
$currentUsername = $user['username'] ?? '';
$currentRole     = $user['role'] ?? '-';

// ================== PROSES FORM ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';

    // ---------- 1) GANTI FOTO ----------
    if ($formType === 'photo') {

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                $errorPhoto = "Terjadi kesalahan saat mengupload file.";
            } else {
                $fileTmp  = $_FILES['foto']['tmp_name'];
                $fileName = $_FILES['foto']['name'];
                $fileSize = $_FILES['foto']['size'];

                $maxSize = 2 * 1024 * 1024; // 2MB
                if ($fileSize > $maxSize) {
                    $errorPhoto = "Ukuran file maksimal 2MB.";
                } else {
                    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    if (!in_array($ext, $allowedExt)) {
                        $errorPhoto = "Format file harus JPG, JPEG, PNG, atau WEBP.";
                    } else {
                        $newFileName = "user_" . $userId . "_" . time() . "." . $ext;
                        $uploadDir   = dirname(__DIR__) . "/templates/img/profile/";

                        if (!is_dir($uploadDir)) {
                            @mkdir($uploadDir, 0777, true);
                        }

                        $destPath     = $uploadDir . $newFileName;
                        $relativePath = "/mavis/templates/img/profile/" . $newFileName;

                        if (move_uploaded_file($fileTmp, $destPath)) {
                            $stmtUp = $conn->prepare("UPDATE users SET foto = ? WHERE id = ?");
                            $stmtUp->bind_param("si", $relativePath, $userId);
                            if ($stmtUp->execute()) {
                                $successPhoto = "Foto profil berhasil diperbarui.";
                                $currentPhoto = $relativePath;
                            } else {
                                $errorPhoto = "Gagal menyimpan ke database.";
                            }
                            $stmtUp->close();
                        } else {
                            $errorPhoto = "Gagal memindahkan file upload.";
                        }
                    }
                }
            }
        } else {
            $errorPhoto = "Silakan pilih file foto terlebih dahulu.";
        }

    // ---------- 2) UPDATE BIODATA ----------
    } elseif ($formType === 'biodata') {

        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');

        if ($full_name === '') {
            $errorBio = "Nama lengkap tidak boleh kosong.";
        } else {
            $stmtBio = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmtBio->bind_param("sssi", $full_name, $email, $phone, $userId);
            if ($stmtBio->execute()) {
                $successBio      = "Biodata berhasil diperbarui.";
                $currentFullName = $full_name;
                $currentEmail    = $email;
                $currentPhone    = $phone;
            } else {
                $errorBio = "Gagal memperbarui biodata.";
            }
            $stmtBio->close();
        }

    // ---------- 3) GANTI PASSWORD ----------
    } elseif ($formType === 'password') {

        $password_lama  = $_POST['password_lama'] ?? '';
        $password_baru  = $_POST['password_baru'] ?? '';
        $password_konf  = $_POST['password_konfirmasi'] ?? '';

        if ($password_lama === '' || $password_baru === '' || $password_konf === '') {
            $errorPass = "Semua kolom password wajib diisi.";
        } else {
            // Ambil hash password dari DB
            $stmtPass = $conn->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
            $stmtPass->bind_param("i", $userId);
            $stmtPass->execute();
            $rowPass = $stmtPass->get_result()->fetch_assoc();
            $stmtPass->close();

            if (!$rowPass) {
                $errorPass = "User tidak ditemukan.";
            } elseif (!password_verify($password_lama, $rowPass['password_hash'])) {
                $errorPass = "Password lama tidak sesuai.";
            } elseif (strlen($password_baru) < 8) {
                $errorPass = "Password baru minimal 8 karakter.";
            } elseif ($password_baru !== $password_konf) {
                $errorPass = "Konfirmasi password baru tidak sama.";
            } else {
                $newHash = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmtUpPass = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmtUpPass->bind_param("si", $newHash, $userId);
                if ($stmtUpPass->execute()) {
                    $successPass = "Password berhasil diubah.";
                } else {
                    $errorPass = "Gagal mengubah password.";
                }
                $stmtUpPass->close();
            }
        }
    }
}
?>

<div class="container-fluid">

    <!-- Judul Halaman -->
    <h1 class="h3 mb-4 text-gray-800">Profil Akun</h1>

    <div class="row">

        <!-- Kartu Profil + Ganti Foto -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <img src="<?= htmlspecialchars($currentPhoto, ENT_QUOTES, 'UTF-8'); ?>"
                         alt="Foto Profil"
                         class="img-profile rounded-circle mb-3"
                         style="width: 120px; height: 120px; object-fit: cover;">

                    <h5 class="mb-1">
                        <?= htmlspecialchars($currentFullName !== '' ? $currentFullName : $currentUsername, ENT_QUOTES, 'UTF-8'); ?>
                    </h5>
                    <p class="text-muted mb-0">
                        Username:
                        <strong><?= htmlspecialchars($currentUsername, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </p>
                    <p class="text-muted mb-0">
                        Role:
                        <strong><?= htmlspecialchars($currentRole, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </p>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ganti Foto Profil</h6>
                </div>
                <div class="card-body">

                    <?php if ($successPhoto): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($successPhoto, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($errorPhoto): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($errorPhoto, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_type" value="photo">
                        <div class="form-group">
                            <label for="foto">Pilih Foto Baru</label>
                            <input type="file"
                                   class="form-control-file"
                                   id="foto"
                                   name="foto"
                                   accept=".jpg,.jpeg,.png,.webp"
                                   required>
                            <small class="form-text text-muted">
                                Maksimal ukuran 2MB. Format: JPG, JPEG, PNG, WEBP.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> Simpan Foto
                        </button>
                    </form>

                </div>
            </div>
        </div>

        <!-- Biodata + Password -->
        <div class="col-lg-8 mb-4">

            <!-- Biodata -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Biodata Akun</h6>
                </div>
                <div class="card-body">

                    <?php if ($successBio): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($successBio, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($errorBio): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($errorBio, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <input type="hidden" name="form_type" value="biodata">

                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text"
                                   name="full_name"
                                   class="form-control"
                                   value="<?= htmlspecialchars($currentFullName, ENT_QUOTES, 'UTF-8'); ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Alamat Email</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   value="<?= htmlspecialchars($currentEmail, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label>No. Handphone</label>
                            <input type="text"
                                   name="phone"
                                   class="form-control"
                                   value="<?= htmlspecialchars($currentPhone, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Biodata
                        </button>
                    </form>

                </div>
            </div>

            <!-- Ganti Password -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">Ganti Password</h6>
                </div>
                <div class="card-body">

                    <?php if ($successPass): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($successPass, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($errorPass): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($errorPass, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <input type="hidden" name="form_type" value="password">

                        <div class="form-group">
                            <label>Password Lama</label>
                            <input type="password"
                                   name="password_lama"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password"
                                   name="password_baru"
                                   class="form-control"
                                   required>
                            <small class="form-text text-muted">
                                Minimal 8 karakter.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Konfirmasi Password Baru</label>
                            <input type="password"
                                   name="password_konfirmasi"
                                   class="form-control"
                                   required>
                        </div>

                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-key"></i> Ganti Password
                        </button>
                    </form>

                </div>
            </div>

        </div>

    </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>
