<?php include __DIR__ . '/../header.php'; ?>

<style>
    /* Harmonisation des cartes d'export */
    .card-export { 
        transition: all 0.25s ease-in-out; 
        border: 1px solid #dee2e6 !important; 
        border-radius: 12px;
    }
    .card-export:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important;
    }
    
    .b-top-primary { border-top: 5px solid #0d6efd !important; }
    .b-top-success { border-top: 5px solid #198754 !important; }

    .icon-circle {
        width: 60px; height: 60px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%; font-size: 1.5rem; margin-bottom: 1.5rem;
    }
</style>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3 px-2">
        <div>
            <a href="<?= URLROOT ?>/admin" class="text-decoration-none small text-primary fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Administration
            </a>
            <h2 class="fw-bold mt-2 mb-0">Extraction des données 📊</h2>
            <p class="text-muted mb-0 small">Générez des fichiers Excel/CSV pour vos registres officiels.</p>
        </div>
    </div>

    <div class="row g-4 px-2">
        <div class="col-md-6">
            <div class="card h-100 shadow-sm card-export b-top-primary border-0">
                <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                    <div class="icon-circle bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Registre de l'année</h5>
                    <p class="text-muted small flex-grow-1">Générez un export des arrêtés pour une année civile spécifique. Idéal pour le collationnement annuel.</p>
                    <button type="button" class="btn btn-primary w-100 fw-bold shadow-sm py-2" data-bs-toggle="modal" data-bs-target="#yearModal">
                        Choisir l'année
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 shadow-sm card-export b-top-success border-0">
                <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                    <div class="icon-circle bg-success bg-opacity-10 text-success">
                        <i class="bi bi-files"></i>
                    </div>
                    <h5 class="fw-bold text-success">Historique complet</h5>
                    <p class="text-muted small flex-grow-1">Téléchargez l'intégralité des données présentes dans la base sans distinction de date ou de service.</p>
                    <a href="<?= URLROOT ?>/admin/export?action=tout" class="btn btn-success text-white w-100 fw-bold shadow-sm py-2">
                        <i class="bi bi-download me-2"></i>Télécharger l'archive
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-5">
        <div class="p-3 d-inline-block rounded-pill bg-light border">
            <a href="<?= URLROOT ?>/admin/restaurer" class="text-muted small text-decoration-none fw-bold px-3">
                <i class="bi bi-shield-lock-fill me-2 text-warning"></i> 
                Outil de maintenance : Restaurer des données à partir d'un fichier CSV
            </a>
        </div>
    </div>
</div>

<div class="modal fade" id="yearModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <form action="<?= URLROOT ?>/admin/export" method="GET">
                <input type="hidden" name="action" value="annee">
                <div class="modal-header border-0 pb-0 ps-4 pt-4">
                    <h5 class="modal-title fw-bold">Export annuel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="form-label text-uppercase text-muted fw-bold small mb-2" style="font-size: 0.6rem;">Sélectionner l'année cible</label>
                    <select name="year" class="form-select form-select-lg fw-bold mb-3 shadow-sm">
                        <?php foreach($annees_dispo as $y): ?>
                            <option value="<?= $y['annee'] ?>" <?= $y['annee'] == date('Y') ? 'selected' : '' ?>>
                                <?= $y['annee'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                        Générer le fichier .csv
                    </button>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <p class="text-muted x-small mb-0 text-center w-100 italic">Format compatible Excel et LibreOffice</p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>