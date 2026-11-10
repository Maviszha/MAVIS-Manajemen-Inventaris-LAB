<?php
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";
include dirname(__DIR__) . "/config/log.php";

/* ===========================
   PENGATURAN TEKS HEADER ATAS
   (edit di sini kalau mau ganti)
==============================*/
$schoolTitle = "MAVIS";  // baris atas
$labSubtitle = "Manajemen & Inventaris Laboratorium Kimia";   // baris bawah

/* ===========================
   Ambil info user login
==============================*/

// ID user
$userId = $_SESSION['user_id'] ?? 0;

// Default
$userFullName = "Pengguna";
$userUsername = $_SESSION['username'] ?? '';
$userPhoto    = "/mavis/templates/img/user-default.png";

// Ambil biodata + foto dari database
if ($userId && isset($conn)) {
    $stmt = $conn->prepare("SELECT full_name, username, foto FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res) {
        // Jika full_name kosong → pakai username
        if (!empty($res['full_name'])) {
            $userFullName = $res['full_name'];
        } else {
            $userFullName = $res['username'];
        }

        $userUsername = $res['username'];

        if (!empty($res['foto'])) {
            $userPhoto = $res['foto'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MAVIS - Manajemen Inventaris</title>

    <!-- Font Awesome -->
    <link href="/mavis/assets/sbadmin2/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">

    <!-- SB Admin 2 CSS -->
    <link href="/mavis/assets/sbadmin2/css/sb-admin-2.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link href="/mavis/assets/sbadmin2/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">


    <!-- MAVIS Compact Mode CSS -->
    <style>
      /* Compact mode (mirip zoom ~80% tapi aman) */
      body.mavis-compact { font-size: 0.9rem; }
      body.mavis-compact .container-fluid { padding-left: 0.75rem; padding-right: 0.75rem; }
      body.mavis-compact .card-body { padding: 0.9rem; }
      body.mavis-compact .form-control,
      body.mavis-compact .custom-select,
      body.mavis-compact select.form-control {
        height: calc(1.5em + 0.55rem + 2px);
        padding: 0.275rem 0.55rem;
        font-size: 0.9rem;
      }
      body.mavis-compact textarea.form-control { height: auto; }
      body.mavis-compact .btn { padding: 0.28rem 0.6rem; font-size: 0.9rem; }
      body.mavis-compact .table td, body.mavis-compact .table th { padding: 0.45rem; }
      body.mavis-compact .h1, body.mavis-compact .h2, body.mavis-compact .h3 { font-size: 1.35rem; }
      body.mavis-compact .topbar { padding-top: 0.25rem; padding-bottom: 0.25rem; }
      body.mavis-compact .sidebar .nav-item .nav-link { padding-top: 0.6rem; padding-bottom: 0.6rem; }
      body.mavis-compact .sidebar .nav-item .nav-link span { font-size: 0.85rem; }
    </style>

</head>

<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">

<?php include dirname(__DIR__) . "/templates/sidebar.php"; ?>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">

    <!-- Main Content -->
    <div id="content">

        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

            
            <!-- Sidebar Toggle (Topbar) -->
            <button id="sidebarToggleTop"
                    class="btn btn-link rounded-circle mr-2"
                    type="button"
                    aria-label="Toggle Sidebar"
                    title="Tampilkan/Sembunyikan Sidebar">
                <i class="fas fa-bars"></i>
            </button>
<!-- Judul di kiri (2 baris) -->
            <div class="d-none d-md-flex flex-column justify-content-center ml-2">
                <span class="text-gray-800 font-weight-bold" style="font-size: 1rem;">
                    <?= htmlspecialchars($schoolTitle, ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <span class="text-muted" style="font-size: 0.8rem;">
                    <?= htmlspecialchars($labSubtitle, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>

            <!-- Spacer kecil -->
            <div class="ml-3"></div>

            <!-- Right side: user dropdown -->
            <ul class="navbar-nav ml-auto">

                <!-- Compact Mode Toggle (kanan) -->
                <li class="nav-item no-arrow d-flex align-items-center mr-1">
                    <button id="compactToggleTop"
                            class="btn btn-link rounded-circle"
                            type="button"
                            aria-label="Toggle Compact Mode"
                            title="Aktifkan Compact Mode"
                            style="width: 36px; height: 36px; padding: 0;">
                        <i id="compactToggleIcon" class="fas fa-compress"></i>
                    </button>
                </li>



                <div class="topbar-divider d-none d-sm-block"></div>

                <!-- USER DROPDOWN -->
                <li class="nav-item dropdown no-arrow">

                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                        <!-- Nama lengkap -->
                        <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                            <?= htmlspecialchars($userFullName, ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <!-- User Photo -->
                        <img class="img-profile rounded-circle"
                             src="<?= htmlspecialchars($userPhoto, ENT_QUOTES, 'UTF-8'); ?>"
                             style="width: 35px; height: 35px; object-fit: cover;">
                    </a>

                    <!-- Dropdown menu -->
                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                         aria-labelledby="userDropdown">

                        <a class="dropdown-item" href="/mavis/pages/profile.php">
                            <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profil Akun
                        </a>

                        <div class="dropdown-divider"></div>

                        <a class="dropdown-item" href="/mavis/logout.php"
                           onclick="return confirm('Yakin ingin logout?');">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
                        </a>

                    </div>
                </li>

            </ul>
        </nav>
