<?php
// pages/emodul-laporan-ajax.php
// Dipanggil via fetch(), menghasilkan HTML tabel laporan kelompok
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$role = strtolower((string)($_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '')));
if ($role !== 'admin') {
    echo '<div class="alert alert-danger">Akses ditolak.</div>';
    exit;
}

$emId = (int)($_GET['emodul_id'] ?? 0);
if (!$emId) {
    echo '<div class="alert alert-warning">ID e-modul tidak valid.</div>';
    exit;
}

// Ambil semua laporan kelompok untuk e-modul ini
$stmt = $conn->prepare(
    "SELECT k.id, k.nama_kelompok, k.judul_praktikum, k.created_at
     FROM laporan_kelompok k
     WHERE k.emodul_id = ?
     ORDER BY k.created_at DESC"
);
$stmt->bind_param('i', $emId);
$stmt->execute();
$res  = $stmt->get_result();
$rows = [];
if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
?>

<?php if (!$rows): ?>
  <div class="text-center py-5 text-muted">
    <i class="fas fa-folder-open fa-3x mb-3"></i>
    <p>Belum ada laporan yang dikumpulkan untuk e-modul ini.</p>
  </div>
<?php else: ?>

<div class="table-responsive">
  <table class="table table-bordered table-hover table-sm">
    <thead class="thead-light">
      <tr>
        <th style="width:40px">#</th>
        <th>Nama Kelompok</th>
        <th>Judul Praktikum</th>
        <th>Anggota (NIS / Nama / Kelas)</th>
        <th class="text-center" style="width:120px">Laporan</th>
        <th style="width:100px">Dikumpulkan</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $i => $row):
        // Ambil anggota kelompok
        $sAngg = $conn->prepare(
          "SELECT nis, nama, kelas FROM laporan_kelompok_anggota
           WHERE kelompok_id = ? ORDER BY urutan ASC"
        );
        $sAngg->bind_param('i', $row['id']);
        $sAngg->execute();
        $resAngg = $sAngg->get_result();
        $anggota = [];
        if ($resAngg) while ($a = $resAngg->fetch_assoc()) $anggota[] = $a;
      ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td class="font-weight-bold"><?= h($row['nama_kelompok']) ?></td>
          <td><?= h($row['judul_praktikum']) ?></td>
          <td>
            <?php if ($anggota): ?>
              <ul class="list-unstyled mb-0 small">
                <?php foreach ($anggota as $a): ?>
                  <li>
                    <span class="badge badge-secondary"><?= h($a['nis']) ?></span>
                    <?= h($a['nama']) ?>
                    <span class="text-muted">(<?= h($a['kelas']) ?>)</span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <button class="btn btn-sm btn-danger"
                    onclick="bukaPdfLaporan(<?= (int)$row['id'] ?>, '<?= h($row['nama_kelompok']) ?>')">
              <i class="fas fa-file-pdf mr-1"></i> Lihat PDF
            </button>
          </td>
          <td class="small text-muted">
            <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>
