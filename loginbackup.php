<?php
session_start();
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/log.php";

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: /mavis/index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Username dan password wajib diisi.";
    } else {
        // Prepared statement lebih aman
        $stmt = $conn->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            logActivity(
    'auth',
    'login_success',
    "Login berhasil untuk user {$user['username']}"
);

            header("Location: /mavis/index.php");
            exit;
        } else {
            $error = "Username atau password salah.";
        }
    logActivity(
    'auth',
    'login_failed',
    "Login gagal. Username: {$username}"
);

	}
}

/*
 * ===========================
 *  PENGATURAN TAMPILAN LOGIN
 * ===========================
 * Ganti sesuai kebutuhan.
 */

// Logo di atas form
$logoPath = "/mavis/templates/img/logo.png";

// Judul & subjudul
$appTitle   = "MAVIS (MANAJEMEN DAN INVENTARIS)";
$subTitle   = "LABORATORIUM KIMIA SMA TARUNA NUSANTARA";

// Gambar background slideshow (5 gambar)
$bgImages = [
    "/mavis/templates/img/bg1.jpg",
    "/mavis/templates/img/bg2.jpg",
    "/mavis/templates/img/bg3.jpeg",
    "/mavis/templates/img/bg4.jpeg",
	"/mavis/templates/img/bg5.jpg",
	"/mavis/templates/img/bg6.jpeg"
];

// Ubah array PHP jadi JS
$bgImagesJson = json_encode($bgImages);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Login - MAVIS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- SB Admin 2 CSS -->
    <link href="/mavis/assets/sbadmin2/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="/mavis/assets/sbadmin2/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            font-family: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow: hidden; /* hilangkan scroll, background full */
        }

        /* LAYER BACKGROUND SLIDESHOW */
        .bg-slideshow {
            position: fixed;
            inset: 0;
            background-position: center center;
            background-size: cover;
            background-repeat: no-repeat;
            transition: opacity 1s ease, background-image 1s ease;
            z-index: 0;
        }

        .bg-slideshow::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.4); /* gelapkan sedikit */
        }

        /* WRAPPER CONTENT */
        .login-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* CARD LOGIN (glass effect) */
        .login-card {
            max-width: 420px;
            width: 100%;
            background: rgba(255, 255, 255, 0.93);
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            border-radius: 12px;
            backdrop-filter: blur(4px);
        }

        .login-logo-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .login-title {
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .login-subtitle {
            font-size: 0.9rem;
            color: #6c757d;
        }

        @media (max-width: 576px) {
            .login-card {
                max-width: 100%;
            }
            .login-title {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>

<!-- Background slide fullscreen -->
<div class="bg-slideshow" id="bgSlideshow"></div>

<div class="login-wrapper">
    <div class="card login-card border-0">
        <div class="card-body p-4">

            <!-- Logo + Judul -->
            <div class="text-center mb-4">
                <img src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8'); ?>"
                     alt="Logo"
                     class="login-logo-img mb-2">
                <div class="login-title">
                    <?= htmlspecialchars($appTitle, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="login-subtitle">
                    <?= htmlspecialchars($subTitle, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>

            <!-- Pesan error -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <!-- FORM LOGIN -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <input type="text"
                               name="username"
                               id="username"
                               class="form-control"
                               placeholder="Masukkan username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               required>
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <input type="password"
                               name="password"
                               id="password"
                               class="form-control"
                               placeholder="Masukkan password"
                               required>
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Remember me (opsional) -->
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="remember" disabled>
                    <label class="form-check-label text-muted" for="remember">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

        </div>
    </div>
</div>

<!-- JS -->
<script src="/mavis/assets/sbadmin2/vendor/jquery/jquery.min.js"></script>
<script src="/mavis/assets/sbadmin2/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/mavis/assets/sbadmin2/js/sb-admin-2.min.js"></script>

<script>
    // Slideshow background
    (function () {
        const bgEl     = document.getElementById('bgSlideshow');
        const images   = <?= $bgImagesJson; ?>;
        let idx        = 0;

        function setBg(i) {
            if (!bgEl || !images.length) return;
            bgEl.style.opacity = 0;
            setTimeout(function () {
                bgEl.style.backgroundImage = "url('" + images[i] + "')";
                bgEl.style.opacity = 1;
            }, 400);
        }

        setBg(idx);

        if (images.length > 1) {
            setInterval(function () {
                idx = (idx + 1) % images.length;
                setBg(idx);
            }, 5000); // ganti gambar setiap 5 detik
        }
    })();
</script>

</body>
</html>
