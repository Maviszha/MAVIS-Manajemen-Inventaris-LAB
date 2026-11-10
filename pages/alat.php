<?php
// pages/alat.php (konsolidasi: list/add/edit/delete + proses)
// Catatan: ini masih native PHP, hanya dirapikan jadi satu endpoint.

include dirname(__DIR__) . "/templates/header.php";

// helper role
$role          = currentUserRole();
$canManageAlat = canManageAlat();

// Untuk keamanan dasar: hanya admin/yang berhak yang boleh create/update/delete.
// List tetap boleh untuk role yang diizinkan via canManageAlat()/role menu (mengikuti behavior lama).
$action = $_GET['action'] ?? 'list';

// Control which modal should auto-open (e.g., open=lokasi or open=sumber)
$openModal = isset($_GET['open']) ? preg_replace('/[^a-z_]/i', '', (string)$_GET['open']) : '';
// Flash/status untuk modal lokasi (dipakai di form add/edit alat)
// Support dua gaya param: snake_case & camelCase agar kompatibel.
$lokasiAdded = !empty($_GET['lokasi_added'] ?? null) || !empty($_GET['lokasiAdded'] ?? null);
$lokasiUpd   = !empty($_GET['lokasi_updated'] ?? null) || !empty($_GET['lokasiUpd'] ?? null);
$lokasiDel   = !empty($_GET['lokasi_deleted'] ?? null) || !empty($_GET['lokasiDel'] ?? null);
$lokasiUsed  = (int)($_GET['lokasi_used'] ?? ($_GET['lokasiUsed'] ?? 0));

// Flash/status untuk modal sumber pengadaan
$sumberAdded = !empty($_GET['sumber_added'] ?? null) || !empty($_GET['sumberAdded'] ?? null);
$sumberUpd   = !empty($_GET['sumber_updated'] ?? null) || !empty($_GET['sumberUpd'] ?? null);
$sumberDel   = !empty($_GET['sumber_deleted'] ?? null) || !empty($_GET['sumberDel'] ?? null);
$sumberUsed  = (int)($_GET['sumber_used'] ?? ($_GET['sumberUsed'] ?? 0));

// Preload master data (lokasi & sumber) untuk dropdown add/edit.
// Pakai array supaya bisa dipakai berkali-kali (mysqli_result tidak bisa di-loop dua kali).
$lokasiOptions = [];
$lokasiRes = $conn->query("SELECT id, nama_lokasi FROM lokasi_alat ORDER BY nama_lokasi ASC");
if ($lokasiRes) {
    while ($row = $lokasiRes->fetch_assoc()) { $lokasiOptions[] = $row; }
}

$sumberOptions = [];
$sumberRes = $conn->query("SELECT id, nama_sumber FROM sumber_pengadaan ORDER BY nama_sumber ASC");
if ($sumberRes) {
    while ($row = $sumberRes->fetch_assoc()) { $sumberOptions[] = $row; }
}



// Helper: Normalisasi tanggal agar aman kalau ada input "2025" (jadi 2025-01-01)
// Support: YYYY, YYYY-MM-DD, MM/DD/YYYY
if (!function_exists('normalizeDateFlexible')) {
    function normalizeDateFlexible(?string $val): ?string {
        $val = trim((string)$val);
        if ($val === '') return null;

        if (preg_match('/^\d{4}$/', $val)) return $val . '-01-01';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) return $val;

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $val, $m)) {
            $mm = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $dd = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $yy = $m[3];
            return "{$yy}-{$mm}-{$dd}";
        }
        return null;
    }
}


// ------------------------
// Handlers (POST / action)
// ------------------------
function handle_alat_add() {
    global $conn;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: alat.php?action=add");
    exit;
}
    $fail = function(string $msg): void {
        header("Location: alat.php?action=add&fail=" . urlencode($msg));
        exit;
    };
// ===== Ambil input =====
$nama_alat     = trim($_POST['nama_alat'] ?? '');
$merk_alat     = trim($_POST['merk_alat'] ?? '');
$spesifikasi   = trim($_POST['spesifikasi'] ?? '');
$no_inventaris = trim($_POST['no_inventaris'] ?? '');

$lokasi_id           = isset($_POST['lokasi_id']) ? (int)$_POST['lokasi_id'] : 0;
$tahun_pengadaan_db  = normalizeDateFlexible($_POST['tahun_pengadaan'] ?? '');
$sumber_pengadaan_id = isset($_POST['sumber_pengadaan_id']) ? (int)$_POST['sumber_pengadaan_id'] : 0;

$jumlah_baik         = max(0, (int)($_POST['jumlah_baik'] ?? 0));
$jumlah_rusak_berat  = max(0, (int)($_POST['jumlah_rusak_berat'] ?? 0));
$jumlah_rusak_ringan = max(0, (int)($_POST['jumlah_rusak_ringan'] ?? 0));
$jumlah_hilang       = max(0, (int)($_POST['jumlah_hilang'] ?? 0));

// ✅ TOTAL OTOMATIS (abaikan input jumlah_total dari form)
$jumlah_total = $jumlah_baik + $jumlah_rusak_berat + $jumlah_rusak_ringan + $jumlah_hilang;

$keterangan = trim($_POST['keterangan'] ?? '');

// ===== Validasi wajib =====
if ($nama_alat === '') $fail('Nama alat wajib diisi.');
if ($lokasi_id <= 0) $fail('Lokasi wajib dipilih.');
if ($tahun_pengadaan_db === null) $fail('Tahun pengadaan wajib & harus valid.');
if ($sumber_pengadaan_id <= 0) $fail('Sumber pengadaan wajib dipilih.');

// No inventaris opsional
if ($no_inventaris === '') $no_inventaris = null;

// (Opsional) cek duplikat no_inventaris jika diisi
if ($no_inventaris !== null) {
    $cek = $conn->prepare("SELECT id FROM alat_laboratorium WHERE no_inventaris = ? LIMIT 1");
    $cek->bind_param("s", $no_inventaris);
    $cek->execute();
    $dup = $cek->get_result()->fetch_assoc();
    $cek->close();
    if ($dup) {
        $fail('No inventaris sudah dipakai alat lain.');
    }
}

// ===== Insert =====
$sql = "
    INSERT INTO alat_laboratorium
    (
        nama_alat,
        merk_alat,
        spesifikasi,
        lokasi_id,
        tahun_pengadaan,
        sumber_pengadaan_id,
        no_inventaris,
        jumlah_total,
        jumlah_baik,
        jumlah_rusak_berat,
        jumlah_rusak_ringan,
        jumlah_hilang,
        keterangan
    )
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $fail("Prepare gagal: " . $conn->error);
}

// 13 param: s s s i s i s i i i i i s
$stmt->bind_param(
    "sssisisiiiiis",
    $nama_alat,
    $merk_alat,
    $spesifikasi,
    $lokasi_id,
    $tahun_pengadaan_db,
    $sumber_pengadaan_id,
    $no_inventaris,
    $jumlah_total,
    $jumlah_baik,
    $jumlah_rusak_berat,
    $jumlah_rusak_ringan,
    $jumlah_hilang,
    $keterangan
);

$stmt->execute();
$newId = $stmt->insert_id;
$stmt->close();

// ===== Log =====
$username = $_SESSION['username'] ?? 'unknown';

logActivity(
    'alat',
    'create',
    'Tambah alat laboratorium',
    $username,
    null,
    json_encode([
        'id' => $newId,
        'nama_alat' => $nama_alat,
        'merk_alat' => $merk_alat,
        'spesifikasi' => $spesifikasi,
        'lokasi_id' => $lokasi_id,
        'tahun_pengadaan' => $tahun_pengadaan_db,
        'sumber_pengadaan_id' => $sumber_pengadaan_id,
        'no_inventaris' => $no_inventaris,
        'jumlah_total' => $jumlah_total,
        'jumlah_baik' => $jumlah_baik,
        'jumlah_rusak_berat' => $jumlah_rusak_berat,
        'jumlah_rusak_ringan' => $jumlah_rusak_ringan,
        'jumlah_hilang' => $jumlah_hilang,
        'keterangan' => $keterangan
    ])
);

header("Location: alat.php?action=list&success=1");
exit;
}

function handle_alat_edit() {
    global $conn;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: alat.php?action=list");
    exit;
}
    $fail = function(int $id, string $msg): void {
        header("Location: alat.php?action=edit&id={$id}&fail=" . urlencode($msg));
        exit;
    };
// ID wajib & numerik
$id = $_POST['id'] ?? '';
if ($id === '' || !ctype_digit((string)$id)) {
    header("Location: alat.php?action=list&msg=invalid");
    exit;
}
$id = (int)$id;

// ===== AMBIL DATA LAMA UNTUK LOG =====
$oldStmt = $conn->prepare("SELECT * FROM alat_laboratorium WHERE id = ? LIMIT 1");
$oldStmt->bind_param("i", $id);
$oldStmt->execute();
$oldRow = $oldStmt->get_result()->fetch_assoc();
$oldStmt->close();

if (!$oldRow) {
    header("Location: alat.php?action=list&msg=notfound");
    exit;
}

// ===== Ambil input =====
$nama_alat     = trim($_POST['nama_alat'] ?? '');
$merk_alat     = trim($_POST['merk_alat'] ?? '');
$spesifikasi   = trim($_POST['spesifikasi'] ?? '');
$no_inventaris = trim($_POST['no_inventaris'] ?? '');

$lokasi_id           = isset($_POST['lokasi_id']) ? (int)$_POST['lokasi_id'] : 0;
$tahun_pengadaan_db  = normalizeDateFlexible($_POST['tahun_pengadaan'] ?? '');
$sumber_pengadaan_id = isset($_POST['sumber_pengadaan_id']) ? (int)$_POST['sumber_pengadaan_id'] : 0;

$jumlah_baik         = max(0, (int)($_POST['jumlah_baik'] ?? 0));
$jumlah_rusak_berat  = max(0, (int)($_POST['jumlah_rusak_berat'] ?? 0));
$jumlah_rusak_ringan = max(0, (int)($_POST['jumlah_rusak_ringan'] ?? 0));
$jumlah_hilang       = max(0, (int)($_POST['jumlah_hilang'] ?? 0));

// ✅ TOTAL OTOMATIS (abaikan input jumlah_total)
$jumlah_total = $jumlah_baik + $jumlah_rusak_berat + $jumlah_rusak_ringan + $jumlah_hilang;

$keterangan = trim($_POST['keterangan'] ?? '');

// ===== Validasi =====
if ($nama_alat === '') $fail($id, 'Nama alat wajib diisi.');
if ($lokasi_id <= 0) $fail($id, 'Lokasi wajib dipilih.');
if ($tahun_pengadaan_db === null) $fail($id, 'Tanggal/Tahun pengadaan wajib & harus valid.');
if ($sumber_pengadaan_id <= 0) $fail($id, 'Sumber pengadaan wajib dipilih.');

// No inventaris opsional
if ($no_inventaris === '') $no_inventaris = null;

// (opsional) cek duplikat no_inventaris jika diisi
if ($no_inventaris !== null) {
    $cek = $conn->prepare("SELECT id FROM alat_laboratorium WHERE no_inventaris = ? AND id <> ? LIMIT 1");
    $cek->bind_param("si", $no_inventaris, $id);
    $cek->execute();
    $dup = $cek->get_result()->fetch_assoc();
    $cek->close();
    if ($dup) {
        $fail($id, 'No inventaris sudah dipakai alat lain.');
    }
}

// ===== UPDATE =====
$sql = "
    UPDATE alat_laboratorium SET
        nama_alat           = ?,
        merk_alat           = ?,
        spesifikasi         = ?,
        lokasi_id           = ?,
        tahun_pengadaan     = ?,
        sumber_pengadaan_id = ?,
        no_inventaris       = ?,
        jumlah_total        = ?,
        jumlah_baik         = ?,
        jumlah_rusak_berat  = ?,
        jumlah_rusak_ringan = ?,
        jumlah_hilang       = ?,
        keterangan          = ?
    WHERE id = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $fail($id, "Prepare gagal: " . $conn->error);
}

// 14 param
$stmt->bind_param(
    "sssisisiiiiisi",
    $nama_alat,
    $merk_alat,
    $spesifikasi,
    $lokasi_id,
    $tahun_pengadaan_db,
    $sumber_pengadaan_id,
    $no_inventaris,
    $jumlah_total,
    $jumlah_baik,
    $jumlah_rusak_berat,
    $jumlah_rusak_ringan,
    $jumlah_hilang,
    $keterangan,
    $id
);

$stmt->execute();
$stmt->close();

// ===== LOG =====
$username = $_SESSION['username'] ?? 'unknown';
$after = [
    'id' => $id,
    'nama_alat' => $nama_alat,
    'merk_alat' => $merk_alat,
    'spesifikasi' => $spesifikasi,
    'lokasi_id' => $lokasi_id,
    'tahun_pengadaan' => $tahun_pengadaan_db,
    'sumber_pengadaan_id' => $sumber_pengadaan_id,
    'no_inventaris' => $no_inventaris,
    'jumlah_total' => $jumlah_total,
    'jumlah_baik' => $jumlah_baik,
    'jumlah_rusak_berat' => $jumlah_rusak_berat,
    'jumlah_rusak_ringan' => $jumlah_rusak_ringan,
    'jumlah_hilang' => $jumlah_hilang,
    'keterangan' => $keterangan
];

logActivity(
    'alat',
    'update',
    'Update alat laboratorium',
    $username,
    json_encode($oldRow),
    json_encode($after)
);

header("Location: alat.php?action=list&update=1");
exit;
}

function handle_alat_delete() {
    global $conn;
// <-- tambahkan

// Pastikan ID dikirim & valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: alat.php?action=list&msg=invalid");
    exit;
}

$id = (int)$_GET['id'];

// ===== AMBIL DATA LAMA UNTUK LOG =====
$oldRow = null;
$oldRes = $conn->query("SELECT * FROM alat_laboratorium WHERE id = {$id} LIMIT 1");
if ($oldRes && $oldRes->num_rows > 0) {
    $oldRow = $oldRes->fetch_assoc();
}

// Hapus data pakai prepared statement
$sql  = "DELETE FROM alat_laboratorium WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Gagal menyiapkan query: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();

    // ===== LOG ACTIVITY: DELETE ALAT =====
    logActivity(
        'alat',
        'delete',
        "Hapus data alat ID: {$id}" . ($oldRow ? " ({$oldRow['nama_alat']})" : ''),
        $oldRow,
        null
    );

    header("Location: alat.php?action=list&msg=deleted");
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    echo "Gagal menghapus data: " . htmlspecialchars($error);
    exit;
}
}


// Dispatch proses berdasarkan method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        requireAdmin();
        handle_alat_add();
        exit;
    }
    if ($action === 'edit') {
        requireAdmin();
        handle_alat_edit();
        exit;
    }
}

// Proses delete via GET action=delete&id=...
if ($action === 'delete') {
    requireAdmin();
    handle_alat_delete();
    exit;
}

// ------------------------
// Views (GET)
// ------------------------
if ($action === 'add') {
    requireAdmin();
    // Ubah action form agar post ke file ini
    ?>
<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Tambah Data Alat Laboratorium</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Input Data Alat</h6>
        </div>

        <form action="alat.php?action=add" method="POST">
            <div class="card-body">
                <div class="row">

                    <!-- =================== KOLOM KIRI =================== -->
                    <div class="col-md-6">

                        <div class="form-group">
                            <label>Nama Alat</label>
                            <input type="text" name="nama_alat" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Merk</label>
                            <input type="text" name="merk_alat" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Spesifikasi</label>
                            <textarea name="spesifikasi" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Lokasi Penyimpanan</label>
                            <div class="input-group">
                                <select id="lokasiSelect" name="lokasi_id" class="form-control" required>
                                    <option value="">-- Pilih Lokasi --</option>
                                    <?php foreach ($lokasiOptions as $l): ?>
                                        <option value="<?= (int)$l['id']; ?>">
                                            <?= htmlspecialchars($l['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="input-group-append">
                                    <button type="button"
                                            class="btn btn-info"
                                            data-toggle="modal"
                                            data-target="#modalLokasi">
                                        Kelola Lokasi
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Tahun Pengadaan</label>
                            <input type="date"
                                   name="tahun_pengadaan"
                                   class="form-control"
                                   min="2000-01-01"
                                   max="2099-12-31"
                                   required>
                        </div>

                    </div>

                    <!-- =================== KOLOM KANAN =================== -->
                    <div class="col-md-6">

                        <!-- ✅ NO INVENTARIS -->
                        <div class="form-group">
                            <label>No Inventaris</label>
                            <input type="text"
                                   name="no_inventaris"
                                   class="form-control"
                                   maxlength="100"
                                   placeholder="Contoh: INV-2025-001">
                            <small class="text-muted">Opsional. Boleh dikosongkan.</small>
                        </div>

                        <!-- ✅ TOTAL OTOMATIS -->
                        <div class="form-group">
                            <label>Jumlah Total</label>
                            <input type="number"
                                   name="jumlah_total"
                                   id="jumlah_total"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="0"
                                   readonly>
                            <small class="text-muted">Terisi otomatis dari Baik + RB + RR + Hilang</small>
                        </div>

                        <div class="form-group">
                            <label>Jumlah Baik</label>
                            <input type="number"
                                   name="jumlah_baik"
                                   id="jumlah_baik"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="0"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Rusak Berat</label>
                            <input type="number"
                                   name="jumlah_rusak_berat"
                                   id="jumlah_rusak_berat"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="0">
                        </div>

                        <div class="form-group">
                            <label>Rusak Ringan</label>
                            <input type="number"
                                   name="jumlah_rusak_ringan"
                                   id="jumlah_rusak_ringan"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="0">
                        </div>

                        <div class="form-group">
                            <label>Hilang</label>
                            <input type="number"
                                   name="jumlah_hilang"
                                   id="jumlah_hilang"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="0">
                        </div>

                        <div class="form-group">
                            <label>Sumber Pengadaan</label>
                            <div class="input-group">
                                <select name="sumber_pengadaan_id" class="form-control" required>
                                    <option value="">-- Pilih Sumber --</option>
                                    <?php foreach ($sumberOptions as $s): ?>
                                        <option value="<?= (int)$s['id']; ?>">
                                            <?= htmlspecialchars($s['nama_sumber'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="input-group-append">
                                    <button type="button"
                                            class="btn btn-info"
                                            data-toggle="modal"
                                            data-target="#modalSumber">
                                        Kelola Sumber
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3"></textarea>
                        </div>

                    </div>

                </div>
            </div>

            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="alat.php?action=list" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
        </form>
    </div>

</div>

<!-- ===================== MODAL KELOLA LOKASI ===================== -->
<div class="modal fade" id="modalLokasi" tabindex="-1" role="dialog" aria-labelledby="modalLokasiLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLokasiLabel">Kelola Lokasi Penyimpanan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <?php if ($lokasiAdded): ?>
            <div class="alert alert-success py-1 mb-2">Lokasi baru berhasil ditambahkan.</div>
        <?php endif; ?>

        <?php if ($lokasiUpd): ?>
            <div class="alert alert-success py-1 mb-2">Lokasi berhasil diperbarui.</div>
        <?php endif; ?>

        <?php if ($lokasiDel): ?>
            <div class="alert alert-success py-1 mb-2">Lokasi berhasil dihapus.</div>
        <?php endif; ?>

        <?php if ($lokasiUsed): ?>
            <div class="alert alert-danger py-1 mb-2">
                Lokasi tidak dapat dihapus karena digunakan oleh <?= (int)$lokasiUsed; ?> alat.
            </div>
        <?php endif; ?>

        <form action="lokasi-add-process.php" method="POST" class="form-inline mb-3">
          <input type="hidden" name="from" value="alat-add">
          <div class="form-group mr-2 mb-2">
            <label class="sr-only">Nama Lokasi</label>
            <input type="text" name="nama_lokasi" class="form-control" placeholder="Nama lokasi baru" required>
          </div>
          <button type="submit" class="btn btn-primary mb-2">
            <i class="fas fa-plus"></i> Tambah
          </button>
        </form>

        <hr>

        <div class="table-responsive">
          <table class="table table-bordered table-sm">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Nama Lokasi</th>
                <th style="width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $lokasiModalRes = $conn->query("SELECT id, nama_lokasi FROM lokasi_alat ORDER BY nama_lokasi ASC");
            if ($lokasiModalRes && $lokasiModalRes->num_rows > 0):
                $no = 1;
                while ($lok = $lokasiModalRes->fetch_assoc()):
            ?>
              <tr>
                <td><?= $no++; ?></td>
                <td>
                  <form action="lokasi-edit-process.php" method="POST" class="form-inline">
                    <input type="hidden" name="from" value="alat-add">
                    <input type="hidden" name="id" value="<?= (int)$lok['id']; ?>">
                    <input type="text"
                           name="nama_lokasi"
                           class="form-control form-control-sm mr-2"
                           value="<?= htmlspecialchars($lok['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?>"
                           required>
                    <button type="submit" class="btn btn-sm btn-warning">
                      <i class="fas fa-save"></i>
                    </button>
                  </form>
                </td>
                <td class="text-center">
                  <a href="lokasi-delete.php?id=<?= (int)$lok['id']; ?>&from=alat-add"
                     class="btn btn-sm btn-danger"
                     onclick="return confirm('Yakin ingin menghapus lokasi ini?');">
                    <i class="fas fa-trash"></i>
                  </a>
                </td>
              </tr>
            <?php
                endwhile;
            else:
            ?>
              <tr><td colspan="3" class="text-center">Belum ada lokasi.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
      <div class="modal-footer">
        <a href="lokasi-list.php" class="btn btn-outline-secondary">Kelola Lengkap</a>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== MODAL KELOLA SUMBER ===================== -->
<div class="modal fade" id="modalSumber" tabindex="-1" role="dialog" aria-labelledby="modalSumberLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSumberLabel">Kelola Sumber Pengadaan</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <?php if ($sumberAdded): ?><div class="alert alert-success py-1 mb-2">Sumber baru berhasil ditambahkan.</div><?php endif; ?>
        <?php if ($sumberUpd): ?><div class="alert alert-success py-1 mb-2">Sumber berhasil diperbarui.</div><?php endif; ?>
        <?php if ($sumberDel): ?><div class="alert alert-success py-1 mb-2">Sumber berhasil dihapus.</div><?php endif; ?>
        <?php if ($sumberUsed): ?>
            <div class="alert alert-danger py-1 mb-2">
                Sumber tidak dapat dihapus karena digunakan oleh <?= (int)$sumberUsed; ?> alat.
            </div>
        <?php endif; ?>

        <form action="sumber-add-process.php" method="POST" class="form-inline mb-3">
          <input type="hidden" name="from" value="alat-add">
          <div class="form-group mr-2 mb-2">
            <label class="sr-only">Nama Sumber</label>
            <input type="text" name="nama_sumber" class="form-control" placeholder="Nama sumber baru" required>
          </div>
          <button type="submit" class="btn btn-primary mb-2">
            <i class="fas fa-plus"></i> Tambah
          </button>
        </form>

        <hr>

        <div class="table-responsive">
          <table class="table table-bordered table-sm">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Nama Sumber</th>
                <th style="width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $sumberModalRes = $conn->query("SELECT id, nama_sumber FROM sumber_pengadaan ORDER BY nama_sumber ASC");
            if ($sumberModalRes && $sumberModalRes->num_rows > 0):
                $no = 1;
                while ($sm = $sumberModalRes->fetch_assoc()):
            ?>
              <tr>
                <td><?= $no++; ?></td>
                <td>
                  <form action="sumber-edit-process.php" method="POST" class="form-inline">
                    <input type="hidden" name="from" value="alat-add">
                    <input type="hidden" name="id" value="<?= (int)$sm['id']; ?>">
                    <input type="text"
                           name="nama_sumber"
                           class="form-control form-control-sm mr-2"
                           value="<?= htmlspecialchars($sm['nama_sumber'], ENT_QUOTES, 'UTF-8'); ?>"
                           required>
                    <button type="submit" class="btn btn-sm btn-warning">
                      <i class="fas fa-save"></i>
                    </button>
                  </form>
                </td>
                <td class="text-center">
                  <a href="sumber-delete.php?id=<?= (int)$sm['id']; ?>&from=alat-add"
                     class="btn btn-sm btn-danger"
                     onclick="return confirm('Yakin ingin menghapus sumber ini?');">
                    <i class="fas fa-trash"></i>
                  </a>
                </td>
              </tr>
            <?php
                endwhile;
            else:
            ?>
              <tr><td colspan="3" class="text-center">Belum ada sumber.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
      <div class="modal-footer">
        <a href="sumber-list.php" class="btn btn-outline-secondary">Kelola Lengkap</a>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

?>

<script>
function recalcTotalAlat() {
    var elBaik = document.getElementById('jumlah_baik');
    var elRB   = document.getElementById('jumlah_rusak_berat');
    var elRR   = document.getElementById('jumlah_rusak_ringan');
    var elH    = document.getElementById('jumlah_hilang');
    var elTot  = document.getElementById('jumlah_total');

    var baik   = parseInt(elBaik ? elBaik.value : '0', 10) || 0;
    var rb     = parseInt(elRB ? elRB.value : '0', 10) || 0;
    var rr     = parseInt(elRR ? elRR.value : '0', 10) || 0;
    var hilang = parseInt(elH ? elH.value : '0', 10) || 0;

    var total = baik + rb + rr + hilang;
    if (elTot) elTot.value = total;
}

['jumlah_baik','jumlah_rusak_berat','jumlah_rusak_ringan','jumlah_hilang'].forEach(function(id){
    var el = document.getElementById(id);
    if (el) {
        el.addEventListener('input', recalcTotalAlat);
        el.addEventListener('change', recalcTotalAlat);
    }
});

recalcTotalAlat();
</script>

<?php if ($openModal === 'lokasi'): ?>
<script>
$(document).ready(function () { $('#modalLokasi').modal('show'); });
</script>
<?php endif; ?>

<?php if ($openModal === 'sumber'): ?>
<script>
$(document).ready(function () { $('#modalSumber').modal('show'); });
</script>
<?php endif; ?>
<?php
    include dirname(__DIR__) . "/templates/footer.php";
    exit;
}

if ($action === 'edit') {
    requireAdmin();
    ?>
<div class="container-fluid">

    <!-- Judul Halaman -->
    <h1 class="h3 mb-4 text-gray-800">Edit Data Alat Laboratorium</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-warning">Form Edit Data Alat</h6>
        </div>

        <form action="alat.php?action=edit" method="POST">
            <input type="hidden" name="id" value="<?= (int)$d['id']; ?>">

            <div class="card-body">
                <div class="row">
                    <!-- Kolom kiri -->
                    <div class="col-md-6">

                        <div class="form-group">
                            <label>Nama Alat</label>
                            <input type="text"
                                   name="nama_alat"
                                   class="form-control"
                                   value="<?= htmlspecialchars($d['nama_alat'], ENT_QUOTES, 'UTF-8'); ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Merk</label>
                            <input type="text"
                                   name="merk_alat"
                                   class="form-control"
                                   value="<?= htmlspecialchars($d['merk_alat'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label>Spesifikasi</label>
                            <textarea name="spesifikasi"
                                      class="form-control"
                                      rows="3"><?= htmlspecialchars($d['spesifikasi'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Lokasi Penyimpanan</label>
                            <select name="lokasi_id" class="form-control" required>
                                <option value="">-- Pilih Lokasi --</option>
                                <?php foreach ($lokasiOptions as $l): ?>
                                    <option value="<?= (int)$l['id']; ?>"
                                        <?= ((int)$l['id'] === (int)$d['lokasi_id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($l['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tanggal Pengadaan</label>
                            <input type="date"
                                   name="tahun_pengadaan"
                                   class="form-control"
                                   value="<?= htmlspecialchars($d['tahun_pengadaan'], ENT_QUOTES, 'UTF-8'); ?>"
                                   required>
                        </div>

                    </div>

                    <!-- Kolom kanan -->
                    <div class="col-md-6">

                        <!-- ✅ NO INVENTARIS -->
                        <div class="form-group">
                            <label>No Inventaris</label>
                            <input type="text"
                                   name="no_inventaris"
                                   class="form-control"
                                   maxlength="100"
                                   value="<?= htmlspecialchars($d['no_inventaris'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   placeholder="Contoh: INV-2025-001">
                            <small class="text-muted">Opsional. Boleh dikosongkan.</small>
                        </div>

                        <!-- ✅ TOTAL OTOMATIS (readonly) -->
                        <div class="form-group">
                            <label>Jumlah Total</label>
                            <input type="number"
                                   name="jumlah_total"
                                   id="jumlah_total"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="<?= (int)($d['jumlah_total'] ?? 0); ?>"
                                   readonly>
                            <small class="text-muted">Terisi otomatis dari Baik + RB + RR + Hilang</small>
                        </div>

                        <div class="form-group">
                            <label>Jumlah Baik</label>
                            <input type="number"
                                   name="jumlah_baik"
                                   id="jumlah_baik"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="<?= (int)$d['jumlah_baik']; ?>">
                        </div>

                        <div class="form-group">
                            <label class="mt-3">Rusak Berat</label>
                            <input type="number"
                                   name="jumlah_rusak_berat"
                                   id="jumlah_rusak_berat"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="<?= (int)$d['jumlah_rusak_berat']; ?>">
                        </div>

                        <div class="form-group">
                            <label class="mt-3">Rusak Ringan</label>
                            <input type="number"
                                   name="jumlah_rusak_ringan"
                                   id="jumlah_rusak_ringan"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="<?= (int)$d['jumlah_rusak_ringan']; ?>">
                        </div>

                        <div class="form-group">
                            <label class="mt-3">Hilang</label>
                            <input type="number"
                                   name="jumlah_hilang"
                                   id="jumlah_hilang"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="<?= (int)$d['jumlah_hilang']; ?>">
                        </div>

                        <div class="form-group">
                            <label class="mt-3">Sumber Pengadaan</label>
                            <select name="sumber_pengadaan_id" class="form-control" required>
                                <option value="">-- Pilih Sumber --</option>
                                <?php foreach ($sumberOptions as $s): ?>
                                    <option value="<?= (int)$s['id']; ?>"
                                        <?= ((int)$s['id'] === (int)$d['sumber_pengadaan_id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($s['nama_sumber'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="mt-3">Keterangan</label>
                            <textarea name="keterangan"
                                      class="form-control"
                                      rows="3"><?= htmlspecialchars($d['keterangan'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <p class="text-muted" style="font-size: 0.85rem;">
                            <strong>Catatan:</strong> Total dihitung otomatis dari Baik + RB + RR + H.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card-footer text-right">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="alat.php?action=list" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>

</div>

?>

<script>
function recalcTotalAlat() {
    var elBaik = document.getElementById('jumlah_baik');
    var elRB   = document.getElementById('jumlah_rusak_berat');
    var elRR   = document.getElementById('jumlah_rusak_ringan');
    var elH    = document.getElementById('jumlah_hilang');
    var elTot  = document.getElementById('jumlah_total');

    var baik   = parseInt(elBaik ? elBaik.value : '0', 10) || 0;
    var rb     = parseInt(elRB ? elRB.value : '0', 10) || 0;
    var rr     = parseInt(elRR ? elRR.value : '0', 10) || 0;
    var hilang = parseInt(elH ? elH.value : '0', 10) || 0;

    var total = baik + rb + rr + hilang;
    if (elTot) elTot.value = total;
}

['jumlah_baik','jumlah_rusak_berat','jumlah_rusak_ringan','jumlah_hilang'].forEach(function(id){
    var el = document.getElementById(id);
    if (el) {
        el.addEventListener('input', recalcTotalAlat);
        el.addEventListener('change', recalcTotalAlat);
    }
});

recalcTotalAlat();
</script>
<?php
    include dirname(__DIR__) . "/templates/footer.php";
    exit;
}

// default list
?>
<style>
    .badge-status {
        font-size: 13px;
        padding: 6px 10px;
        border-radius: 6px;
    }

     #alatTable {
        width: 100% !important;
    }

    #alatTable th,
    #alatTable td {
        vertical-align: middle;
    }

    /* Sedikit atur lebar beberapa kolom */
    #alatTable th.col-nama,
    #alatTable td.col-nama {
        min-width: 190px;
    }
    #alatTable th.col-lokasi,
    #alatTable td.col-lokasi {
        min-width: 150px;
    }
    #alatTable th.col-ket,
    #alatTable td.col-ket {
        min-width: 220px;
    }
	.aksi-btn-group {
        display: flex;
        gap: 6px;
        justify-content: center;
        align-items: center;
    }
    .aksi-btn-group a {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0 !important;
    }
</style>

<div class="container-fluid">

    <!-- Judul Halaman -->
    <h1 class="h3 mb-4 text-gray-800">Data Alat Laboratorium</h1>
  <?php
$success = $_GET['success'] ?? '';
$update  = $_GET['update'] ?? '';
$msg     = $_GET['msg'] ?? '';
?>

<!-- Toast Container (pojok kanan atas) -->
<div class="toast-container position-fixed" style="top: 1rem; right: 1rem; z-index: 1060;">

    <?php if ($success): ?>
        <!-- Tambah alat -->
        <div class="alert alert-success alert-auto-close shadow-sm mb-2" role="alert">
            <i class="fas fa-check-circle mr-2"></i>
            Data alat berhasil <strong>ditambahkan</strong>.
        </div>
    <?php endif; ?>

    <?php if ($update): ?>
        <!-- Update alat -->
        <div class="alert alert-info alert-auto-close shadow-sm mb-2" role="alert">
            <i class="fas fa-sync-alt mr-2"></i>
            Data alat berhasil <strong>diperbarui</strong>.
        </div>
    <?php endif; ?>

    <?php if ($msg === 'deleted'): ?>
        <!-- Hapus alat -->
        <div class="alert alert-warning alert-auto-close shadow-sm mb-2" role="alert">
            <i class="fas fa-trash-alt mr-2"></i>
            Data alat berhasil <strong>dihapus</strong>.
        </div>
    <?php endif; ?>

    <?php if ($msg === 'invalid'): ?>
        <!-- ID tidak valid -->
        <div class="alert alert-danger alert-auto-close shadow-sm mb-2" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            ID tidak valid.
        </div>
    <?php endif; ?>

    <?php if ($msg === 'notfound'): ?>
        <!-- Data tdk ditemukan -->
        <div class="alert alert-danger alert-auto-close shadow-sm mb-2" role="alert">
            <i class="fas fa-search-minus mr-2"></i>
            Data alat tidak ditemukan.
        </div>
    <?php endif; ?>

</div>

    <!-- Card utama -->
    <div class="card shadow mb-4">

        <!-- Header card: tombol tambah, kelola lokasi, filter -->
        <div class="card-header py-3">

            <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between">

                <!-- Kiri: tombol tambah & kelola (hanya admin) -->
                <div class="mb-2 mb-xl-0">
                    <?php if ($canManageAlat): ?>
                        <a href="alat.php?action=add" class="btn btn-primary mr-2 mb-2 mb-sm-0">
                            <i class="fas fa-plus"></i> Tambah Alat
                        </a>

                        <a href="lokasi-list.php" class="btn btn-success mr-1 mb-2 mb-sm-0">
                            <i class="fas fa-map-marker-alt"></i> Kelola Lokasi
                        </a>

                        <a href="sumber-list.php" class="btn btn-warning mb-2 mb-sm-0 text-white">
                            <i class="fas fa-database"></i> Kelola Sumber Dana
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Kanan: form filter -->
                <form method="GET" class="form-inline mt-2 mt-xl-0">
                    <?php
                    $keyword_val = $_GET['keyword'] ?? '';
                    $lokasi_val  = $_GET['lokasi'] ?? '';
                    ?>
                    <div class="form-group mr-2 mb-2 mb-sm-0">
                        <input type="text" name="keyword"
                               placeholder="Aku buat nyari lho, pakai aku...:)"
                               value="<?= htmlspecialchars($keyword_val, ENT_QUOTES, 'UTF-8'); ?>"
                               class="form-control"
                               style="min-width:260px;">
                    </div>

                    <div class="form-group mr-2 mb-2 mb-sm-0">
                        <select name="lokasi" class="form-control" style="min-width:190px;">
                            <option value="">Semua Lokasi</option>
                            <?php
                            $lokasiRes = $conn->query("SELECT * FROM lokasi_alat ORDER BY nama_lokasi ASC");
                            if ($lokasiRes && $lokasiRes->num_rows > 0):
                                while ($l = $lokasiRes->fetch_assoc()):
                                    $selected = ($lokasi_val !== '' && (int)$lokasi_val === (int)$l['id']) ? 'selected' : '';
                                    ?>
                                    <option value="<?= (int)$l['id']; ?>" <?= $selected; ?>>
                                        <?= htmlspecialchars($l['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php
                                endwhile;
                            endif;
                            ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-search"></i> Cari
                    </button>
                </form>
            </div>
        </div>

        <!-- Body card: tabel -->
        <div class="card-body">
            <div class="table-responsive">
                <table id="alatTable" class="table table-bordered table-striped" cellspacing="0">
                    <thead class="thead-light">
                    <tr>
                        <th class="col-nama">Nama Alat</th>
                        <th>Merk</th>
                        <th>Spesifikasi</th>
                        <th class="col-lokasi">Lokasi</th>
                        <th>Thn Pengadaan</th>
                        <th>Sumber Dana</th>
                        <th>No Inventaris</th>
                        <th>Total</th>
                        <th>Baik</th>
                        <th>RB</th>
                        <th>RR</th>
                        <th>H</th>
                        <th class="col-ket">Keterangan</th>
                        <?php if ($canManageAlat): ?>
                            <th style="width: 90px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>

                    <?php
                    // === Menangkap Input Pencarian ===
                    $keyword       = $_GET['keyword'] ?? '';
                    $lokasi_filter = $_GET['lokasi'] ?? '';

                    // === Base Query ===
                    $query = "
                        SELECT a.*,
                               l.nama_lokasi,
                               s.nama_sumber
                        FROM alat_laboratorium a
                        LEFT JOIN lokasi_alat l ON a.lokasi_id = l.id
                        LEFT JOIN sumber_pengadaan s ON a.sumber_pengadaan_id = s.id
                        WHERE 1=1
                    ";

                    // Filter keyword
                    if ($keyword !== '') {
                        $keywordEsc = $conn->real_escape_string($keyword);

                        $query .= "
                              AND (
                                   a.nama_alat       LIKE '%{$keywordEsc}%'
                                   OR a.merk_alat       LIKE '%{$keywordEsc}%'
                                   OR a.spesifikasi     LIKE '%{$keywordEsc}%'
                                   OR a.tahun_pengadaan LIKE '%{$keywordEsc}%'
                                   OR s.nama_sumber     LIKE '%{$keywordEsc}%'
                                   OR a.no_inventaris   LIKE '%{$keywordEsc}%'
                              )
                           ";
                      }

                    // Filter lokasi
                    if ($lokasi_filter !== '') {
                        $lokasi_id = (int)$lokasi_filter;
                        $query .= " AND a.lokasi_id = $lokasi_id ";
                    }

                    $query .= " ORDER BY a.nama_alat ASC ";

                    $result = $conn->query($query);

                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):

                            $badge_baik   = "<span class='badge badge-success badge-status'>".(int)$row['jumlah_baik']."</span>";
                            $badge_berat  = "<span class='badge badge-danger badge-status'>".(int)$row['jumlah_rusak_berat']."</span>";
                            $badge_ringan = "<span class='badge badge-warning badge-status'>".(int)$row['jumlah_rusak_ringan']."</span>";
                            $badge_hilang = "<span class='badge badge-secondary badge-status'>".(int)$row['jumlah_hilang']."</span>";
                            ?>
                            <tr>
                                <td class="col-nama">
                                    <?= htmlspecialchars($row['nama_alat'], ENT_QUOTES, 'UTF-8'); ?>
								</td>
                                <td><?= htmlspecialchars($row['merk_alat'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['spesifikasi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="col-lokasi">
                                    <?= htmlspecialchars($row['nama_lokasi'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td><?= htmlspecialchars($row['tahun_pengadaan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($row['nama_sumber'], ENT_QUOTES, 'UTF-8'); ?></td>

                                <td><?= htmlspecialchars($row['no_inventaris'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>

                                <td><?= (int)$row['jumlah_total']; ?></td>
                                <td><?= $badge_baik; ?></td>
                                <td><?= $badge_berat; ?></td>
                                <td><?= $badge_ringan; ?></td>
                                <td><?= $badge_hilang; ?></td>
                                <td class="col-ket">
                                    <?= htmlspecialchars($row['keterangan'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>

                                <?php if ($canManageAlat): ?>
                                    <td class="text-center">
                                        <div class="aksi-btn-group">
                                          <a href="alat.php?action=edit&id=<?= (int)$row['id']; ?>" class="btn btn-warning btn-sm mb-0" title="Edit">
                                              <i class="fas fa-edit"></i>
                                          </a>
                                          <a href="alat.php?action=delete&id=<?= (int)$row['id']; ?>"
                                             class="btn btn-danger btn-sm"
                                             title="Hapus"
                                             onclick="return confirm('Yakin ingin menghapus data ini?');">
                                              <i class="fas fa-trash"></i>
                                          </a>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php
                        endwhile;
                    else:
                        ?>
                        <tr>
                            <td colspan="<?= $canManageAlat ? 14 : 13; ?>" class="text-center">
                                Belum ada data alat.
                            </td>
                        </tr>
                    <?php endif; ?>

                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

?>

<script>
    $(document).ready(function () {
        $('#alatTable').DataTable({
            pageLength: 25,
            autoWidth: false,
            dom: 'lrtip'
        });

        // Auto close alert
        setTimeout(function () {
            $('.alert-auto-close').fadeTo(500, 0).slideUp(500, function () {
                $(this).remove();
            });
        }, 4000);
    });
</script>
<?php
include dirname(__DIR__) . "/templates/footer.php";