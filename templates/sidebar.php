<?php
// templates/sidebar.php (atau file sidebar kamu)
if (session_status() === PHP_SESSION_NONE) session_start();

$role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '');
$role = strtolower((string)$role);

// Helper: cek menu aktif (berdasarkan REQUEST_URI)
function is_active($needle): bool {
  $uri = $_SERVER['REQUEST_URI'] ?? '';
  return (strpos($uri, $needle) !== false);
}
?>

<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

  <!-- Brand -->
  <a class="sidebar-brand d-flex align-items-center justify-content-center" href="/mavis/index.php">
    <div class="sidebar-brand-icon">
      <img src="/mavis/templates/img/logo.png" alt="Logo MAVIS" class="img-fluid" style="height:50px;width:auto;">
    </div>
    <div class="sidebar-brand-text mx-1">SMA<br>TARUNA<br>NUSANTARA</div>
  </a>

  <hr class="sidebar-divider">

  <!-- Dashboard -->
  <li class="nav-item <?= is_active('/mavis/index.php') ? 'active' : '' ?>">
    <a class="nav-link" href="/mavis/index.php">
      <i class="fas fa-fw fa-tachometer-alt"></i>
      <span>Dashboard</span>
    </a>
  </li>

  <div class="sidebar-heading">INVENTARIS</div>

  <!-- Data Alat -->
  <?php $alatOpen = is_active('/mavis/pages/alat-list.php') || is_active('/mavis/pages/export-alat') || is_active('/mavis/pages/import-alat'); ?>
  <li class="nav-item <?= $alatOpen ? 'active' : '' ?>">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAlat"
       aria-expanded="<?= $alatOpen ? 'true' : 'false' ?>" aria-controls="collapseAlat">
      <i class="fas fa-fw fa-tools"></i>
      <span>Data Alat Lab</span>
    </a>
    <div id="collapseAlat" class="collapse <?= $alatOpen ? 'show' : '' ?>" aria-labelledby="headingAlat" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <a class="collapse-item <?= is_active('/mavis/pages/alat-list.php') ? 'active' : '' ?>" href="/mavis/pages/alat-list.php">Data Alat</a>
        <a class="collapse-item <?= is_active('/mavis/pages/export-alat') ? 'active' : '' ?>" href="/mavis/pages/export-alat.php">Unduh Data Alat</a>
        <a class="collapse-item <?= is_active('/mavis/pages/import-alat') ? 'active' : '' ?>" href="/mavis/pages/import-alat.php">Unggah Data Alat</a>
      </div>
    </div>
  </li>

  <!-- Data Bahan -->
  <?php $bahanOpen = is_active('/mavis/pages/bahan-list.php') || is_active('/mavis/pages/export-bahan') || is_active('/mavis/pages/import-bahan'); ?>
  <li class="nav-item <?= $bahanOpen ? 'active' : '' ?>">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBahan"
       aria-expanded="<?= $bahanOpen ? 'true' : 'false' ?>" aria-controls="collapseBahan">
      <i class="fas fa-fw fa-vial"></i>
      <span>Data Bahan Lab</span>
    </a>
    <div id="collapseBahan" class="collapse <?= $bahanOpen ? 'show' : '' ?>" aria-labelledby="headingBahan" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <a class="collapse-item <?= is_active('/mavis/pages/bahan-list.php') ? 'active' : '' ?>" href="/mavis/pages/bahan-list.php">Data Bahan</a>
        <a class="collapse-item <?= is_active('/mavis/pages/export-bahan') ? 'active' : '' ?>" href="/mavis/pages/export-bahan.php">Export Data Bahan</a>
        <a class="collapse-item <?= is_active('/mavis/pages/import-bahan') ? 'active' : '' ?>" href="/mavis/pages/import-bahan.php">Import Data Bahan</a>
      </div>
    </div>
  </li>

  <!-- Laporan -->
  <li class="nav-item <?= is_active('/mavis/laporan/laporan-alat.php') ? 'active' : '' ?>">
    <a class="nav-link" href="/mavis/laporan/laporan-alat.php">
      <i class="fas fa-fw fa-chart-bar"></i>
      <span>Laporan &amp; Rekap</span>
    </a>
  </li>

  <!-- ========================= -->
  <!-- KESISWAAN -->
  <!-- ========================= -->
  <hr class="sidebar-divider">
  <div class="sidebar-heading">KESISWAAN</div>

  <?php
    $presensiOpen = is_active('/mavis/pages/data-siswa.php')
      || is_active('/mavis/pages/data-siswa-exim.php')
      || is_active('/mavis/pages/presensi-praktikum')
      || is_active('/mavis/pages/presensi-reguler');
  ?>
  <li class="nav-item <?= $presensiOpen ? 'active' : '' ?>">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePresensi"
       aria-expanded="<?= $presensiOpen ? 'true' : 'false' ?>" aria-controls="collapsePresensi">
      <i class="fas fa-clipboard-check"></i>
      <span>Presensi</span>
    </a>
    <div id="collapsePresensi" class="collapse <?= $presensiOpen ? 'show' : '' ?>" aria-labelledby="headingPresensi" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Presensi:</h6>
        <a class="collapse-item <?= is_active('/mavis/pages/data-siswa.php') ? 'active' : '' ?>" href="/mavis/pages/data-siswa.php">Data Siswa</a>
        <a class="collapse-item <?= is_active('/mavis/pages/data-siswa-exim.php') ? 'active' : '' ?>" href="/mavis/pages/data-siswa-exim.php">Export / Import</a>

        <?php if ($role === 'admin' || $role === 'guru'): ?>
          <div class="dropdown-divider"></div>
          <h6 class="collapse-header">Presensi Lab:</h6>
          <a class="collapse-item <?= is_active('/mavis/pages/presensi-praktikum') ? 'active' : '' ?>" href="/mavis/pages/presensi-praktikum-list.php">Presensi Praktikum</a>
          <a class="collapse-item <?= is_active('/mavis/pages/presensi-reguler') ? 'active' : '' ?>" href="/mavis/pages/presensi-reguler-list.php">Presensi Reguler</a>
        <?php endif; ?>
      </div>
    </div>
  </li>

  <!-- ========================= -->
  <!-- RISET SISWA (BARU) -->
  <!-- ========================= -->
  <hr class="sidebar-divider">
  <div class="sidebar-heading">RISET SISWA</div>

  <?php
    $risetOpen = is_active('/mavis/public/unggah-mandiri.php') || is_active('/mavis/pages/unggah-mandiri-list.php');
  ?>
  <li class="nav-item <?= $risetOpen ? 'active' : '' ?>">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseRisetSiswa"
       aria-expanded="<?= $risetOpen ? 'true' : 'false' ?>" aria-controls="collapseRisetSiswa">
      <i class="fas fa-flask"></i>
      <span>Riset Siswa</span>
    </a>
    <div id="collapseRisetSiswa" class="collapse <?= $risetOpen ? 'show' : '' ?>" aria-labelledby="headingRisetSiswa" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <h6 class="collapse-header">Riset:</h6>

        <!-- Tanpa login -->
        <a class="collapse-item <?= is_active('/mavis/public/unggah-mandiri.php') ? 'active' : '' ?>"
           href="/mavis/public/unggah-mandiri.php" target="_blank" rel="noopener noreferrer">
          Unggah Mandiri
        </a>

        <!-- Admin only -->
        <?php if ($role === 'admin'): ?>
          <a class="collapse-item <?= is_active('/mavis/pages/unggah-mandiri-list.php') ? 'active' : '' ?>"
             href="/mavis/pages/unggah-mandiri-list.php">
            Data Unggah Mandiri
          </a>
        <?php endif; ?>
      </div>
    </div>
  </li>

  <!-- ========================= -->
  <!-- E-MODUL PRAKTIKUM -->
  <!-- ========================= -->
  <hr class="sidebar-divider">
  <div class="sidebar-heading">E-MODUL</div>

  <?php
    $emodulOpen = is_active('/mavis/pages/emodul-list.php')
               || is_active('/mavis/pages/emodul-add.php')
               || is_active('/mavis/public/emodul.php');
  ?>
  <li class="nav-item <?= $emodulOpen ? 'active' : '' ?>">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseEmodul"
       aria-expanded="<?= $emodulOpen ? 'true' : 'false' ?>" aria-controls="collapseEmodul">
      <i class="fas fa-book-open"></i>
      <span>E-Modul Praktikum</span>
    </a>
    <div id="collapseEmodul" class="collapse <?= $emodulOpen ? 'show' : '' ?>"
         aria-labelledby="headingEmodul" data-parent="#accordionSidebar">
      <div class="bg-white py-2 collapse-inner rounded">
        <!-- Publik tanpa login -->
        <a class="collapse-item <?= is_active('/mavis/public/emodul.php') ? 'active' : '' ?>"
           href="/mavis/public/emodul.php" target="_blank" rel="noopener noreferrer">
          Halaman E-Modul
        </a>
        <!-- Admin only -->
        <?php if ($role === 'admin'): ?>
          <div class="dropdown-divider"></div>
          <a class="collapse-item <?= is_active('/mavis/pages/emodul-list.php') ? 'active' : '' ?>"
             href="/mavis/pages/emodul-list.php">
            Kelola E-Modul
          </a>
          <a class="collapse-item <?= is_active('/mavis/pages/emodul-add.php') ? 'active' : '' ?>"
             href="/mavis/pages/emodul-add.php">
            Upload E-Modul
          </a>
        <?php endif; ?>
      </div>
    </div>
  </li>

  <!-- ========================= -->
  <!-- ADMINISTRATOR (hanya admin) -->
  <!-- ========================= -->
  <?php if ($role === 'admin'): ?>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">ADMINISTRATOR</div>

    <?php $adminOpen = is_active('/mavis/pages/user-list.php') || is_active('/mavis/pages/log-system.php'); ?>
    <li class="nav-item <?= $adminOpen ? 'active' : '' ?>">
      <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAdmin"
         aria-expanded="<?= $adminOpen ? 'true' : 'false' ?>" aria-controls="collapseAdmin">
        <i class="fas fa-user-cog"></i>
        <span>Administrator</span>
      </a>
      <div id="collapseAdmin" class="collapse <?= $adminOpen ? 'show' : '' ?>" aria-labelledby="headingAdmin" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
          <a class="collapse-item <?= is_active('/mavis/pages/user-list.php') ? 'active' : '' ?>" href="/mavis/pages/user-list.php">Kelola User</a>
          <a class="collapse-item <?= is_active('/mavis/pages/log-system.php') ? 'active' : '' ?>" href="/mavis/pages/log-system.php">Log Sistem</a>
        </div>
      </div>
    </li>
  <?php endif; ?>

  <!-- Logout -->
  <li class="nav-item">
    <a class="nav-link" href="/mavis/logout.php">
      <i class="fas fa-fw fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>
  </li>

  <hr class="sidebar-divider d-none d-md-block">
</ul>
