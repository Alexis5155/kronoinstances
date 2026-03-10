<?php include __DIR__ . '/../../layouts/header.php'; ?>

<div class="container py-4">

    <!-- EN-TÊTE -->
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div class="d-flex align-items-center">
            <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold shadow-sm me-3" style="width:50px; height:50px; font-size:1.5rem;">
                <i class="bi bi-sliders2-vertical"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing:-0.5px;">Paramètres système</h2>
                <p class="text-muted small mb-0">Configuration technique de la plateforme</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-white text-dark border px-3 py-2 fw-bold shadow-sm" style="font-size:0.8rem;">
                Version <?= defined('APP_VERSION') ? APP_VERSION : 'Inconnue' ?>
            </span>
            <a href="<?= URLROOT ?>/admin" class="btn btn-light fw-bold shadow-sm px-4 rounded-pill border">
                <i class="bi bi-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- MENU LATÉRAL -->
        <?php include __DIR__ . '/_nav.php'; ?>

        <!-- CONTENU -->
        <div class="col-lg-9">
            <?php
                $viewFile = __DIR__ . '/' . $section . '.php';
                if (file_exists($viewFile)) {
                    include $viewFile;
                } else {
                    echo "<div class='alert alert-danger border-0 shadow-sm rounded-4'><i class='bi bi-exclamation-triangle-fill me-2'></i> La vue pour la section <strong>" . htmlspecialchars($section) . "</strong> est introuvable.</div>";
                }
            ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
