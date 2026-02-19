<footer class="footer mt-auto py-4 bg-white border-top no-print">
    <div class="container">
        <div class="row align-items-center g-3">
            <div class="col-md-6 text-center text-md-start">
                <span class="text-muted small">
                    <i class="bi bi-cpu-fill me-1 opacity-50"></i> KronoActes <span class="opacity-75">v<?= APP_VERSION ?></span>
                </span>
            </div>
            
            <div class="col-md-6 text-center text-md-end">
                <a href="https://github.com/Alexis5155" target="_blank" class="text-decoration-none text-secondary small me-3">
                    <i class="bi bi-github me-1"></i>Code source
                </a>
                <span class="badge bg-light text-muted border fw-normal" style="font-size: 0.65rem;">GPLv3</span>
            </div>
        </div>
    </div>
</footer>

<div class="toast-container position-fixed bottom-0 end-0 p-3 no-print" style="z-index: 1100">
    <?php if (isset($_SESSION['toasts']) && !empty($_SESSION['toasts'])): ?>
        <?php foreach ($_SESSION['toasts'] as $t): ?>
            <div class="toast align-items-center text-white bg-dark border-0 shadow-lg mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body p-3">
                        <?php if($t['type'] === 'success'): ?>
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                        <?php elseif($t['type'] === 'danger'): ?>
                            <i class="bi bi-exclamation-octagon-fill text-danger me-2"></i>
                        <?php else: ?>
                            <i class="bi bi-info-circle-fill text-info me-2"></i>
                        <?php endif; ?>
                        
                        <span class="fw-medium"><?= $t['message'] ?></span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer"></button>
                </div>
            </div>
        <?php endforeach; unset($_SESSION['toasts']); ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toastElList = [].slice.call(document.querySelectorAll('.toast'));
        toastElList.map(function (toastEl) {
            var t = new bootstrap.Toast(toastEl, { delay: 4000, autohide: true });
            t.show();
            return t;
        });
    });
</script>
</body>
</html>