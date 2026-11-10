<?php
// pages/emodul-add.php
include dirname(__DIR__) . "/templates/header.php";
requireAdmin();

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

// Ambil daftar kelas
$kelasList = [];
$qk = $conn->query("SELECT id, nama, aktif FROM kelas ORDER BY nama ASC");
if ($qk) while ($r = $qk->fetch_assoc()) $kelasList[] = $r;
?>

<div class="container-fluid">

  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Upload E-Modul Praktikum</h1>
    <a href="emodul-list.php" class="btn btn-sm btn-secondary shadow-sm">
      <i class="fas fa-arrow-left fa-sm"></i> Kembali ke Daftar
    </a>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fas fa-check-circle mr-1"></i> E-Modul berhasil disimpan!
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="fas fa-exclamation-circle mr-1"></i> <?= h($error) ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php endif; ?>

  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">
        <i class="fas fa-book-open mr-1"></i> Form Upload E-Modul
      </h6>
    </div>

    <form action="emodul-add-process.php" method="POST" enctype="multipart/form-data">
      <div class="card-body">
        <div class="row">

          <!-- KOLOM KIRI -->
          <div class="col-md-6">

            <div class="form-group">
              <label class="font-weight-bold">Judul E-Modul <span class="text-danger">*</span></label>
              <input type="text" name="judul" class="form-control" required
                     placeholder="Contoh: Titrasi Asam Basa">
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Penulis <span class="text-danger">*</span></label>
              <input type="text" name="penulis" class="form-control" required
                     placeholder="Nama penulis / guru">
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Tanggal <span class="text-danger">*</span></label>
              <input type="date" name="tanggal" class="form-control" required
                     value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Kelas yang Dituju <span class="text-danger">*</span></label>
              <select name="kelas_id" class="form-control" required>
                <option value="">-- Pilih Kelas --</option>
                <?php foreach ($kelasList as $k): ?>
                  <option value="<?= (int)$k['id'] ?>">
                    <?= h($k['nama']) ?><?= ((int)$k['aktif'] === 0 ? ' (Nonaktif)' : '') ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted">Pilih kelas yang akan menggunakan e-modul ini.</small>
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Link Pre-Test (Google Form)</label>
              <input type="url" name="link_pretest" class="form-control"
                     placeholder="https://docs.google.com/forms/...">
              <small class="text-muted">Opsional. Langkah 1 sebelum membuka modul.</small>
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Link Post-Test (Google Form)</label>
              <input type="url" name="link_posttest" class="form-control"
                     placeholder="https://docs.google.com/forms/...">
              <small class="text-muted">Opsional. Langkah 3 setelah membaca modul.</small>
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Link Angket (Google Form)</label>
              <input type="url" name="link_angket" class="form-control"
                     placeholder="https://docs.google.com/forms/...">
              <small class="text-muted">Opsional. Langkah 4 setelah post-test.</small>
            </div>

            <div class="form-group">
              <label class="font-weight-bold">URL Video Demonstrasi</label>
              <input type="url" name="link_video_emodul" class="form-control"
                     placeholder="https://www.youtube.com/watch?v=...">
              <small class="text-muted">Opsional. Tombol "Lihat Video" akan muncul di langkah Baca Modul. Salin URL ini ke link di dalam PDF.</small>
            </div>

          </div>

          <!-- KOLOM KANAN -->
          <div class="col-md-6">

            <div class="form-group">
              <label class="font-weight-bold">Keterangan</label>
              <textarea name="keterangan" class="form-control" rows="3"
                        placeholder="Contoh: Dikerjakan sebelum praktikum pertemuan ke-3"></textarea>
              <small class="text-muted">Opsional. Ditampilkan di halaman publik e-modul.</small>
            </div>

            <div class="form-group">
              <label class="font-weight-bold">Gambar Cover</label>
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="inputGambar"
                       name="gambar" accept="image/jpeg,image/png,image/webp">
                <label class="custom-file-label" for="inputGambar">Pilih gambar...</label>
              </div>
              <small class="text-muted">Format: JPG, PNG, WEBP. Maks 5 MB. Opsional.</small>
              <div class="mt-2 text-center" id="previewGambarWrap" style="display:none;">
                <img id="previewGambar" src="#" alt="Preview"
                     class="img-thumbnail" style="max-height:160px;">
              </div>
            </div>

            <div class="form-group">
              <label class="font-weight-bold">File E-Modul (PDF / PPT / PPTX) <span class="text-danger">*</span></label>
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="inputFile"
                       name="file_emodul"
                       accept="application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                       required>
                <label class="custom-file-label" for="inputFile">Pilih file PDF / PPT / PPTX...</label>
              </div>
              <small class="text-muted">Format: PDF, PPT, PPTX. Maks 20 MB.</small>
            </div>

          </div>
        </div>
      </div>

      <div class="card-footer text-right">
        <button type="submit" class="btn btn-primary px-4">
          <i class="fas fa-upload mr-1"></i> Upload E-Modul
        </button>
        <a href="emodul-list.php" class="btn btn-secondary ml-2">Batal</a>
      </div>
    </form>
  </div>

</div>

<?php include dirname(__DIR__) . "/templates/footer.php"; ?>

<script>
// Label file
document.querySelectorAll('.custom-file-input').forEach(function(input){
  input.addEventListener('change', function(){
    var label = this.nextElementSibling;
    label.textContent = this.files[0] ? this.files[0].name : 'Pilih file...';
  });
});

// Preview gambar
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
