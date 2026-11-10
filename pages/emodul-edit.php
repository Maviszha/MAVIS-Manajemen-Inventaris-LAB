<?php
// pages/emodul-edit.php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: emodul-list.php"); exit; }

// Ambil data e-modul
$stmt = $conn->prepare("SELECT * FROM e_modul WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) { header("Location: emodul-list.php"); exit; }

// Ambil daftar kelas
$kelasList = [];
$qk = $conn->query("SELECT id, nama, aktif FROM kelas ORDER BY nama ASC");
if ($qk) while ($r = $qk->fetch_assoc()) $kelasList[] = $r;

$error = $_GET['error'] ?? '';
?>

<div class="container-fluid">

  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit E-Modul Praktikum</h1>
    <a href="emodul-list.php" class="btn btn-sm btn-secondary shadow-sm">
      <i class="fas fa-arrow-left fa-sm"></i> Kembali ke Daftar
    </a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="fas fa-exclamation-circle mr-1"></i> <?= h($error) ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>

  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">
        <i class="fas fa-edit mr-1"></i> Form Edit E-Modul
      </h6>
    </div>

    <form action="emodul-edit-process.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <div class="card-body">
        <div class="row">

          <!-- KOLOM KIRI -->
          <div class="col-md-6">

            <div class="form-group">
              <label class="font-weight-bold">Judul E-Modul <span class="text-danger">*</span></label>
              <input type="text" name="judul" class="form-control" required
                     value="<?= h($row['judul']) ?>">
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Penulis <span class="text-danger">*</span></label>
              <input type="text" name="penulis" class="form-control" required
                     value="<?= h($row['penulis']) ?>">
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Tanggal <span class="text-danger">*</span></label>
              <input type="date" name="tanggal" class="form-control" required
                     value="<?= h($row['tanggal']) ?>">
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Kelas yang Dituju <span class="text-danger">*</span></label>
              <select name="kelas_id" class="form-control" required>
                <option value="">-- Pilih Kelas --</option>
                <?php foreach ($kelasList as $k): ?>
                  <option value="<?= (int)$k['id'] ?>"
                    <?= ((int)$row['kelas_id'] === (int)$k['id']) ? 'selected' : '' ?>>
                    <?= h($k['nama']) ?><?= ((int)$k['aktif'] === 0 ? ' (Nonaktif)' : '') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Link Pre-Test (Google Form)</label>
              <input type="url" name="link_pretest" class="form-control"
                     placeholder="https://docs.google.com/forms/..."
                     value="<?= h($row['link_pretest'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Link Post-Test (Google Form)</label>
              <input type="url" name="link_posttest" class="form-control"
                     placeholder="https://docs.google.com/forms/..."
                     value="<?= h($row['link_posttest'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Link Angket (Google Form)</label>
              <input type="url" name="link_angket" class="form-control"
                     placeholder="https://docs.google.com/forms/..."
                     value="<?= h($row['link_angket'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label class="font-weight-bold">URL Video Demonstrasi</label>
              <input type="url" name="link_video_emodul" class="form-control"
                     placeholder="https://www.youtube.com/watch?v=..."
                     value="<?= h($row['link_video_emodul'] ?? '') ?>">
              <small class="text-muted">Opsional. Tombol "Lihat Video" muncul di langkah Baca Modul. Salin URL ini ke link di dalam PDF.</small>
            </div>

          </div>

          <!-- KOLOM KANAN -->
          <div class="col-md-6">

            <div class="form-group">
              <label class="font-weight-bold">Keterangan</label>
              <textarea name="keterangan" class="form-control" rows="3"
                        placeholder="Contoh: Dikerjakan sebelum praktikum pertemuan ke-3"><?= h($row['keterangan'] ?? '') ?></textarea>
              <small class="text-muted">Opsional. Ditampilkan di halaman publik e-modul.</small>
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Gambar Cover</label>
              <?php if ($row['gambar_path']): ?>
                <div class="mb-2">
                  <img src="/mavis/<?= h($row['gambar_path']) ?>" alt="Cover saat ini"
                       class="img-thumbnail" style="max-height:120px;">
                  <br><small class="text-muted">Gambar saat ini. Upload baru untuk mengganti.</small>
                </div>
              <?php endif; ?>
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="inputGambar"
                       name="gambar" accept="image/jpeg,image/png,image/webp">
                <label class="custom-file-label" for="inputGambar">
                  <?= $row['gambar_path'] ? 'Ganti gambar...' : 'Pilih gambar...' ?>
                </label>
              </div>
              <small class="text-muted">Format: JPG, PNG, WEBP. Maks 5 MB. Biarkan kosong jika tidak ingin mengganti.</small>
              <div class="mt-2 text-center" id="previewGambarWrap" style="display:none;">
                <img id="previewGambar" src="#" alt="Preview"
                     class="img-thumbnail" style="max-height:160px;">
              </div>
            </div>

            <div class="form-group">
              <label class="font-weight-bold">File E-Modul (PDF / PPT / PPTX)</label>
              <?php if ($row['file_path']): ?>
                <div class="mb-2">
                  <?php
                    $ext = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));
                    $isPpt = in_array($ext, ['ppt','pptx']);
                  ?>
                  <?php if ($isPpt): ?>
                    <a href="/mavis/<?= h($row['file_path']) ?>" download
                       class="btn btn-sm btn-outline-warning">
                      <i class="fas fa-file-powerpoint mr-1"></i> Unduh File Saat Ini (<?= strtoupper($ext) ?>)
                    </a>
                  <?php else: ?>
                    <a href="/mavis/<?= h($row['file_path']) ?>" target="_blank"
                       class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-file-pdf mr-1"></i> Lihat PDF Saat Ini
                    </a>
                  <?php endif; ?>
                  <br><small class="text-muted">Upload baru untuk mengganti file.</small>
                </div>
              <?php endif; ?>
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="inputFile"
                       name="file_emodul"
                       accept="application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation">
                <label class="custom-file-label" for="inputFile">
                  <?= $row['file_path'] ? 'Ganti file...' : 'Pilih file PDF / PPT / PPTX...' ?>
                </label>
              </div>
              <small class="text-muted">Format: PDF, PPT, PPTX. Maks 20 MB. Biarkan kosong jika tidak ingin mengganti.</small>
            </div>

          </div>
        </div>
      </div>

      <div class="card-footer text-right">
        <button type="submit" class="btn btn-warning px-4">
          <i class="fas fa-save mr-1"></i> Simpan Perubahan
        </button>
        <a href="emodul-list.php" class="btn btn-secondary ml-2">Batal</a>
      </div>
    </form>
  </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>

<script>
document.querySelectorAll('.custom-file-input').forEach(function(input){
  input.addEventListener('change', function(){
    var label = this.nextElementSibling;
    label.textContent = this.files[0] ? this.files[0].name : this.getAttribute('data-default') || 'Pilih file...';
  });
});

document.getElementById('inputGambar').addEventListener('change', function(){
  var file = this.files[0];
  if (file) {
    var reader = new FileReader();
    reader.onload = function(e){
      document.getElementById('previewGambar').src = e.target.result;
      document.getElementById('previewGambarWrap').style.display = 'block';
    };
    reader.readAsDataURL(file);
  } else {
    document.getElementById('previewGambarWrap').style.display = 'none';
  }
});
</script>
