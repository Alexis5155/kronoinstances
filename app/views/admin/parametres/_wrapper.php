<?php include __DIR__ . '/../../layouts/header.php'; ?>

<div class="container py-4">
    <!-- En-tête de la page Paramètres -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3 px-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-2">
                    <li class="breadcrumb-item"><a href="<?= URLROOT ?>/admin" class="text-decoration-none">Administration</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Configuration Système</li>
                </ol>
            </nav>
            <h2 class="fw-bold mt-2 mb-0">Configuration Système ⚙️</h2>
        </div>
        <span class="badge bg-white text-dark border px-3 py-2 fw-bold shadow-sm" style="font-size: 0.8rem;">
            Version <?= defined('APP_VERSION') ? APP_VERSION : 'Inconnue' ?>
        </span>
    </div>

    <div class="row g-4 px-2">
        <!-- INCLUSION DU MENU LATÉRAL -->
        <?php include __DIR__ . '/_nav.php'; ?>

        <!-- COLONNE DE CONTENU -->
        <div class="col-lg-9">
            <?php 
                $viewFile = __DIR__ . '/' . $section . '.php';
                
                if (file_exists($viewFile)) {
                    include $viewFile;
                } else {
                    echo "<div class='alert alert-danger shadow-sm border-0'><i class='bi bi-exclamation-triangle-fill me-2'></i> La vue pour la section <strong>" . htmlspecialchars($section) . "</strong> est introuvable.</div>";
                }
            ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
