<?php
// pages/data-siswa.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../templates/header.php';

// ===== Guard role (admin/guru) =====
$role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? null);
if (!in_array($role, ['admin', 'guru'], true)) {
  http_response_code(403);
  echo "<div class='container-fluid'><div class='alert alert-danger'>Akses ditolak.</div></div>";
  require_once __DIR__ . '/../templates/footer.php';
  exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
  die("<div class='container-fluid'><div class='alert alert-danger'>Koneksi database (\$conn) tidak tersedia.</div></div>");
}

function h($v): string {
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// ===== Ambil data kelas =====
$kelas = [];
$qk = $conn->query("SELECT id, nama, aktif FROM kelas ORDER BY nama ASC");
if ($qk) while ($r = $qk->fetch_assoc()) $kelas[] = $r;

// ===== Filter/search =====
$kelas_id = (isset($_GET['kelas_id']) && $_GET['kelas_id'] !== '') ? (int)$_GET['kelas_id'] : null;
$keyword  = trim((string)($_GET['keyword'] ?? $_GET['q'] ?? ''));

// ===== Query list siswa (dinamis) =====
$where = [];
$params = [];
$types  = '';

$sql = "SELECT s.id, s.nis, s.nama, s.kelas_id, k.nama AS kelas_nama, s.foto
        FROM siswa s
        LEFT JOIN kelas k ON k.id = s.kelas_id";

if ($kelas_id !== null) {
  $where[] = "s.kelas_id = ?";
  $types  .= "i";
  $params[] = $kelas_id;
}

if ($keyword !== '') {
  $where[] = "(s.nis LIKE ? OR s.nama LIKE ?)";
  $kw = '%' . $keyword . '%';
  $types  .= "ss";
  $params[] = $kw;
  $params[] = $kw;
}

if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY s.nama ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  die("<div class='container-fluid'><div class='alert alert-danger'>Prepare gagal: " . h($conn->error) . "</div></div>");
}
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$siswa = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// ===== Flash message =====
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<style>
  .aksi-btn-group{display:flex;gap:6px;justify-content:center;align-items:center;}
  .aksi-btn-group button{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;padding:0 !important;}

  .modal-toast{display:none;padding:10px 12px;border-radius:8px;margin-bottom:12px;border:1px solid transparent;}
  .modal-toast.success{display:block;background:#e8f7ee;border-color:#b8e6c9;color:#1f6b3b;}
  .modal-toast.danger{display:block;background:#fdeaea;border-color:#f5b8b8;color:#8a1f1f;}
</style>

<div class="container-fluid">
  <div class="d-sm-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0 text-gray-800">Data Siswa</h1>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= h($flash['type'] ?? 'info') ?> shadow-sm">
      <?= h($flash['message'] ?? '') ?>
    </div>
  <?php endif; ?>

  <?php if (count($kelas) === 0): ?>
    <div class="alert alert-warning shadow-sm">
      <i class="fas fa-exclamation-triangle mr-2"></i>
      Data <b>kelas</b> masih kosong. Tambahkan lewat tombol <b>Kelola Kelas</b> (hijau).
    </div>
  <?php endif; ?>

  <div class="card shadow mb-4">

    <div class="card-header py-3">
      <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between">

        <!-- kiri -->
        <div class="mb-2 mb-xl-0 d-flex align-items-center">
          <button class="btn btn-primary mr-2 mb-2 mb-xl-0" data-toggle="modal" data-target="#modalTambah" type="button">
            <i class="fas fa-plus"></i> Tambah Siswa
          </button>

          <button class="btn btn-success mb-2 mb-xl-0" data-toggle="modal" data-target="#modalKelolaKelas" type="button">
            <i class="fas fa-school"></i> Kelola Kelas
          </button>
        </div>

        <!-- kanan -->
        <form method="get" class="form-inline mt-2 mt-xl-0 ml-xl-auto">
          <?php
            $keyword_val = $_GET['keyword'] ?? ($_GET['q'] ?? '');
            $kelas_val   = $_GET['kelas_id'] ?? '';
          ?>
          <div class="form-group mr-2 mb-2 mb-xl-0">
            <input type="text" name="keyword"
                   placeholder="Cari NIS / Nama..."
                   value="<?= h($keyword_val) ?>"
                   class="form-control"
                   style="min-width:260px;">
          </div>

          <div class="form-group mr-2 mb-2 mb-xl-0">
            <select name="kelas_id" class="form-control" style="min-width:190px;">
              <option value="">Semua Kelas</option>
              <?php foreach ($kelas as $k): ?>
                <?php $selected = ($kelas_val !== '' && (int)$kelas_val === (int)$k['id']) ? 'selected' : ''; ?>
                <option value="<?= (int)$k['id'] ?>" <?= $selected; ?>>
                  <?= h($k['nama']) ?><?= ((int)$k['aktif']===0 ? ' (Nonaktif)' : '') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="submit" class="btn btn-info mb-2 mb-xl-0">
            <i class="fas fa-search"></i> Cari
          </button>
        </form>

      </div>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped" id="tblSiswa" width="100%" cellspacing="0">
          <thead class="thead-light">
            <tr>
              <th style="width:80px;">Foto</th>
              <th>NIS</th>
              <th>Nama</th>
              <th>Kelas</th>
              <th style="width:100px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($siswa as $s): ?>
              <tr>
                <td class="text-center">
                  <?php if (!empty($s['foto'])): ?>
                    <img src="<?= h('../' . ltrim((string)$s['foto'], '/')) ?>" alt="foto"
                         style="width:48px;height:48px;object-fit:cover;border-radius:8px;">
                  <?php else: ?>
                    <div class="text-muted">-</div>
                  <?php endif; ?>
                </td>
                <td><?= h($s['nis']) ?></td>
                <td><?= h($s['nama']) ?></td>
                <td><?= h($s['kelas_nama'] ?? '-') ?></td>
                <td class="text-center">
                  <div class="aksi-btn-group">
                    <!-- IMPORTANT: add class btn-edit for vanilla JS handler -->
                    <button class="btn btn-warning btn-sm mb-0 btn-edit"
                      data-toggle="modal"
                      data-target="#modalEdit"
                      data-id="<?= (int)$s['id'] ?>"
                      data-nis="<?= h($s['nis']) ?>"
                      data-nama="<?= h($s['nama']) ?>"
                      data-kelas_id="<?= (int)$s['kelas_id'] ?>"
                      title="Edit"
                      type="button">
                      <i class="fas fa-edit"></i>
                    </button>

                    <form method="post" action="data-siswa-action.php?action=delete"
                      style="display:inline-block;"
                      onsubmit="return confirm('Hapus siswa ini?');">
                      <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                      <button class="btn btn-danger btn-sm" title="Hapus" type="submit">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>

            <?php if (count($siswa) === 0): ?>
              <tr><td colspan="5" class="text-center text-muted">Belum ada data siswa.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- ===========================
     MODAL KELOLA KELAS
=========================== -->
<div class="modal fade" id="modalKelolaKelas" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Kelola Kelas</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <div class="modal-body">
        <div id="kelasToast" class="modal-toast"></div>

        <!-- PENTING: action TANPA ajax=1 (biar tidak pernah tampil JSON di browser) -->
        <form id="formKelasCreate"
              class="form-inline mb-3"
              method="post"
              action="data-siswa-action.php?action=kelas_create">
          <div class="form-group mr-2 mb-2 mb-sm-0" style="min-width:320px; width:100%;">
            <input type="text" name="nama_kelas" class="form-control" placeholder="Nama kelas (contoh: X IPA 1)" style="width:100%;" required>
          </div>
          <div class="form-group mr-2 mb-2 mb-sm-0">
            <select name="aktif" class="form-control">
              <option value="1" selected>Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </div>
          <button class="btn btn-primary mb-2 mb-sm-0" type="submit">
            <i class="fas fa-plus"></i> Tambah
          </button>
        </form>

        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead class="thead-light">
              <tr>
                <th style="width:70px;">ID</th>
                <th>Nama</th>
                <th style="width:110px;">Status</th>
                <th style="width:140px;">Aksi</th>
              </tr>
            </thead>

            <!-- fallback isi dari PHP -->
            <tbody id="kelasTbody">
              <?php if (count($kelas) === 0): ?>
                <tr><td colspan="4" class="text-center text-muted">Belum ada data kelas.</td></tr>
              <?php else: ?>
                <?php foreach ($kelas as $k): ?>
                  <tr>
                    <td><?= (int)$k['id'] ?></td>
                    <td>
                      <!-- action TANPA ajax=1 -->
                      <form class="form-inline kelasUpdateForm"
                            method="post"
                            action="data-siswa-action.php?action=kelas_update">
                        <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
                        <input type="text" name="nama_kelas" class="form-control mr-2"
                               value="<?= h($k['nama']) ?>" required style="min-width:260px;">
                        <select name="aktif" class="form-control mr-2">
                          <option value="1" <?= ((int)$k['aktif']===1?'selected':'') ?>>Aktif</option>
                          <option value="0" <?= ((int)$k['aktif']===0?'selected':'') ?>>Nonaktif</option>
                        </select>
                        <button class="btn btn-info btn-sm" type="submit" title="Simpan">
                          <i class="fas fa-save"></i>
                        </button>
                      </form>
                    </td>
                    <td class="text-center">
                      <?php if ((int)$k['aktif']===1): ?>
                        <span class="badge badge-success">Aktif</span>
                      <?php else: ?>
                        <span class="badge badge-secondary">Nonaktif</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <button class="btn btn-danger btn-sm btnKelasDelete"
                              type="button"
                              data-id="<?= (int)$k['id'] ?>"
                              data-nama="<?= h($k['nama']) ?>"
                              title="Hapus">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>

          </table>
        </div>

        <small class="text-muted d-block mt-2">
          Tips: kalau kelas dipakai oleh siswa, penghapusan bisa ditolak (FK). Set Nonaktif saja.
        </small>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal" type="button">Tutup</button>
      </div>

    </div>
  </div>
</div>

<!-- ===========================
     MODAL TAMBAH SISWA
=========================== -->
<div class="modal fade" id="modalTambah" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form class="modal-content" method="post" action="data-siswa-action.php?action=create" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Siswa</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>NIS</label>
          <input type="text" name="nis" class="form-control" maxlength="32" required>
        </div>

        <div class="form-group">
          <label>Nama</label>
          <input type="text" name="nama" class="form-control" maxlength="120" required>
        </div>

        <div class="form-group">
          <label>Kelas</label>
          <select name="kelas_id" class="form-control" id="selectKelasTambah" required>
            <option value="" selected disabled>Pilih kelas</option>
            <?php foreach ($kelas as $k): ?>
              <?php if ((int)$k['aktif'] === 1): ?>
                <option value="<?= (int)$k['id'] ?>"><?= h($k['nama']) ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Foto (opsional)</label>
          <input type="file" name="foto" accept="image/*" class="form-control">
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal" type="button">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- ===========================
     MODAL EDIT SISWA
=========================== -->
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form class="modal-content" method="post" action="data-siswa-action.php?action=update" enctype="multipart/form-data">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-header">
        <h5 class="modal-title">Edit Siswa</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>NIS</label>
          <input type="text" name="nis" id="edit_nis" class="form-control" maxlength="32" required>
        </div>

        <div class="form-group">
          <label>Nama</label>
          <input type="text" name="nama" id="edit_nama" class="form-control" maxlength="120" required>
        </div>

        <div class="form-group">
          <label>Kelas</label>
          <select name="kelas_id" id="edit_kelas_id" class="form-control" required>
            <option value="" disabled>Pilih kelas</option>
            <?php foreach ($kelas as $k): ?>
              <option value="<?= (int)$k['id'] ?>">
                <?= h($k['nama']) ?><?= ((int)$k['aktif'] === 1 ? '' : ' (nonaktif)') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Ganti Foto (opsional)</label>
          <input type="file" name="foto" accept="image/*" class="form-control">
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal" type="button">Batal</button>
        <button class="btn btn-primary" type="submit">Update</button>
      </div>
    </form>
  </div>
</div>

<script>
  // DataTables siswa
  document.addEventListener('DOMContentLoaded', function() {
    if (window.$ && $.fn.DataTable) {
      $('#tblSiswa').DataTable({ pageLength: 25, autoWidth: false });
    }
  });

  // ==========================================
  // Isi modal edit TANPA jQuery (aman untuk DataTables + urutan load script)
  // ==========================================
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-edit');
    if (!btn) return;

    const id = btn.getAttribute('data-id') || '';
    const nis = btn.getAttribute('data-nis') || '';
    const nama = btn.getAttribute('data-nama') || '';
    const kelasId = btn.getAttribute('data-kelas_id') || '';

    const elId = document.getElementById('edit_id');
    const elNis = document.getElementById('edit_nis');
    const elNama = document.getElementById('edit_nama');
    const elKelas = document.getElementById('edit_kelas_id');

    if (elId) elId.value = id;
    if (elNis) elNis.value = nis;
    if (elNama) elNama.value = nama;

    if (elKelas) {
      elKelas.value = kelasId;
      if (String(elKelas.value) !== String(kelasId)) {
        setTimeout(() => { elKelas.value = kelasId; }, 0);
      }
    }
  });

  // =========================
  // AJAX Kelola Kelas
  // =========================
  const kelasToast = document.getElementById('kelasToast');
  const kelasTbody = document.getElementById('kelasTbody');
  const formKelasCreate = document.getElementById('formKelasCreate');

  function showKelasToast(type, msg) {
    kelasToast.className = 'modal-toast ' + (type === 'success' ? 'success' : 'danger');
    kelasToast.textContent = msg;
    setTimeout(() => {
      kelasToast.className = 'modal-toast';
      kelasToast.textContent = '';
    }, 2500);
  }

  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
  }

  function withAjaxParam(url) {
    const u = new URL(url, window.location.origin);
    u.searchParams.set('ajax', '1');
    return u.toString();
  }

  async function fetchKelasList() {
    const url = withAjaxParam('data-siswa-action.php?action=kelas_list');
    const res = await fetch(url + '&_=' + Date.now(), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await res.json();
    if (!data.ok) throw new Error(data.message || 'Gagal memuat kelas');
    return data.kelas || [];
  }

  function renderKelasRows(list) {
    if (!Array.isArray(list) || list.length === 0) {
      kelasTbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Belum ada data kelas.</td></tr>`;
      return;
    }

    kelasTbody.innerHTML = list.map(k => {
      const badge = (parseInt(k.aktif) === 1)
        ? `<span class="badge badge-success">Aktif</span>`
        : `<span class="badge badge-secondary">Nonaktif</span>`;

      return `
        <tr>
          <td>${k.id}</td>
          <td>
            <form class="form-inline kelasUpdateForm" method="post" action="data-siswa-action.php?action=kelas_update">
              <input type="hidden" name="id" value="${k.id}">
              <input type="text" name="nama_kelas" class="form-control mr-2" value="${escapeHtml(k.nama)}" required style="min-width:260px;">
              <select name="aktif" class="form-control mr-2">
                <option value="1" ${parseInt(k.aktif)===1?'selected':''}>Aktif</option>
                <option value="0" ${parseInt(k.aktif)===0?'selected':''}>Nonaktif</option>
              </select>
              <button class="btn btn-info btn-sm" type="submit" title="Simpan"><i class="fas fa-save"></i></button>
            </form>
          </td>
          <td class="text-center">${badge}</td>
          <td class="text-center">
            <button class="btn btn-danger btn-sm btnKelasDelete" type="button" data-id="${k.id}" data-nama="${escapeHtml(k.nama)}" title="Hapus">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `;
    }).join('');

    bindKelasHandlers();
  }

  // IMPORTANT FIX:
  // - Tambah siswa: hanya kelas aktif
  // - Edit siswa: semua kelas (aktif + nonaktif)
  function refreshDropdownKelas(list) {
    const all = (list || []);
    const active = all.filter(k => parseInt(k.aktif) === 1);

    // Dropdown TAMBAH: hanya aktif
    const selTambah = document.getElementById('selectKelasTambah');
    if (selTambah) {
      const cur = selTambah.value;
      selTambah.innerHTML = `<option value="" selected disabled>Pilih kelas</option>` + active.map(k =>
        `<option value="${k.id}">${escapeHtml(k.nama)}</option>`
      ).join('');
      if (cur) selTambah.value = cur;
    }

    // Dropdown EDIT: semua kelas (aktif + nonaktif)
    const selEdit = document.getElementById('edit_kelas_id');
    if (selEdit) {
      const cur = selEdit.value;
      selEdit.innerHTML = `<option value="" disabled>Pilih kelas</option>` + all.map(k => {
        const label = escapeHtml(k.nama) + (parseInt(k.aktif) === 1 ? '' : ' (nonaktif)');
        return `<option value="${k.id}">${label}</option>`;
      }).join('');
      if (cur) selEdit.value = cur;
    }
  }

  async function reloadKelasUI() {
    const list = await fetchKelasList();
    renderKelasRows(list);
    refreshDropdownKelas(list);
  }

  async function postAjax(url, formData) {
    const ajaxUrl = withAjaxParam(url);
    const res = await fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: formData
    });
    const data = await res.json();
    if (!data.ok) throw new Error(data.message || 'Gagal');
    return data;
  }

  function bindKelasHandlers() {
    // update
    document.querySelectorAll('.kelasUpdateForm').forEach(f => {
      f.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
          const data = await postAjax(f.action, new FormData(f));
          showKelasToast('success', data.message || 'Kelas diperbarui');
          await reloadKelasUI();
        } catch (err) {
          showKelasToast('danger', err.message || 'Gagal update kelas');
        }
      });
    });

    // delete
    document.querySelectorAll('.btnKelasDelete').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.getAttribute('data-id');
        const nama = btn.getAttribute('data-nama');
        if (!confirm(`Hapus kelas "${nama}"? Jika dipakai siswa, bisa gagal.`)) return;

        try {
          const fd = new FormData();
          fd.append('id', id);
          const data = await postAjax('data-siswa-action.php?action=kelas_delete', fd);
          showKelasToast('success', data.message || 'Kelas dihapus');
          await reloadKelasUI();
        } catch (err) {
          showKelasToast('danger', err.message || 'Gagal hapus kelas');
        }
      });
    });
  }

  // bind awal untuk fallback rows
  document.addEventListener('DOMContentLoaded', () => bindKelasHandlers());

  // modal dibuka -> reload list
  if (window.$) {
    $('#modalKelolaKelas').on('shown.bs.modal', async function() {
      try { await reloadKelasUI(); }
      catch (e) { showKelasToast('danger', e.message || 'Gagal memuat kelas'); }
    });
  }

  // tambah kelas (AJAX) -> tetap di popup
  if (formKelasCreate) {
    formKelasCreate.addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        const data = await postAjax(formKelasCreate.action, new FormData(formKelasCreate));
        showKelasToast('success', data.message || 'Kelas ditambahkan');
        formKelasCreate.reset();
        await reloadKelasUI();
      } catch (err) {
        showKelasToast('danger', err.message || 'Gagal tambah kelas');
      }
    });
  }
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
