        </div>
        <!-- End of Main Content -->

        <!-- Footer -->
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>© <?= date('Y') ?> As`ad | MAVIS – Manajemen & Inventaris</span>
                </div>
            </div>
        </footer>

    </div>
</div>

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- SB Admin 2 JS -->
<script src="/mavis/assets/sbadmin2/vendor/jquery/jquery.min.js"></script>
<script src="/mavis/assets/sbadmin2/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/mavis/assets/sbadmin2/vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="/mavis/assets/sbadmin2/js/sb-admin-2.min.js"></script>

<!-- DataTables scripts -->
<script src="/mavis/assets/sbadmin2/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="/mavis/assets/sbadmin2/vendor/datatables/dataTables.bootstrap4.min.js"></script>


<!-- MAVIS Compact Mode JS -->
<script>
(function () {
  function applyCompact(isCompact) {
    var body = document.body;
    if (!body) return;
    if (isCompact) body.classList.add('mavis-compact');
    else body.classList.remove('mavis-compact');

    var icon = document.getElementById('compactToggleIcon');
    var btn  = document.getElementById('compactToggleTop');
    if (icon) icon.className = 'fas ' + (isCompact ? 'fa-expand' : 'fa-compress');
    if (btn)  btn.title = isCompact ? 'Matikan Compact Mode' : 'Aktifkan Compact Mode';
  }

  function getSaved() {
    var v = localStorage.getItem('mavis_compact');
    if (v === null) {
      // Default ON (opsi 1)
      localStorage.setItem('mavis_compact', '1');
      return true;
    }
    return v === '1';
  }

  function save(val) {
    localStorage.setItem('mavis_compact', val ? '1' : '0');
  }

  document.addEventListener('DOMContentLoaded', function () {
    // restore
    applyCompact(getSaved());

    // bind click
    var btn = document.getElementById('compactToggleTop');
    if (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var next = !document.body.classList.contains('mavis-compact');
        applyCompact(next);
        save(next);
        // optional: if DataTables exists, adjust columns after layout changes
        try {
          if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
            jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
          }
        } catch (err) {}
      });
    }

    // enable bootstrap tooltips (if available)
    try {
      if (window.jQuery) {
        jQuery(function () {
          jQuery('[data-toggle="tooltip"]').tooltip();
        });
      }
    } catch (err) {}
  });
})();
</script>

</body>
</html>
