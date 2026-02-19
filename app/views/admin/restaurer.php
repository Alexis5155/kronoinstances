<?php include __DIR__ . '/../header.php'; ?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3 px-2">
        <div>
            <a href="<?= URLROOT ?>/admin/export" class="text-decoration-none small text-primary fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Retour aux exports
            </a>
            <h2 class="fw-bold mt-2 mb-0 text-danger">Restauration du registre ⚠️</h2>
            <p class="text-muted mb-0 small">Outil de maintenance : import massif de données.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 col-md-8 mx-auto mt-5">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-cloud-arrow-up fs-2"></i>
                </div>
                <h5 class="fw-bold">Sélectionnez le fichier CSV</h5>
                <p class="text-muted small">Le fichier doit respecter la structure d'exportation de KronoActes.</p>
            </div>

            <form action="<?= URLROOT ?>/admin/restaurer" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label text-uppercase text-muted fw-bold small" style="font-size: 0.6rem;">Fichier source (.csv)</label>
                    <input type="file" name="csv_restore" class="form-control form-control-lg shadow-sm" accept=".csv" required>
                </div>

                <div class="alert alert-warning border-0 small d-flex align-items-center mb-4">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                    <div>
                        <strong>Attention :</strong> Cette opération va <strong>écraser l'intégralité</strong> des données actuelles du registre. Assurez-vous d'avoir une sauvegarde.
                    </div>
                </div>

                <button type="button" class="btn btn-danger btn-lg w-100 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#confirmRestoreModal">
                    Lancer la restauration
                </button>

                <div class="modal fade" id="confirmRestoreModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header bg-danger text-white border-0 py-3 px-4">
                                <h5 class="modal-title fw-bold"><i class="bi bi-shield-lock-fill me-2"></i> ACTION IRRÉVERSIBLE</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body p-4 text-center">
                                <p class="mb-4">Vous êtes sur le point de remplacer toute la base de données. Pour confirmer cette action, veuillez saisir votre <strong>mot de passe administrateur</strong> :</p>
                                <input type="password" name="admin_password" class="form-control form-control-lg text-center fw-bold" placeholder="••••••••" required>
                            </div>
                            <div class="modal-footer border-0 bg-light p-3 px-4">
                                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-danger fw-bold px-4 shadow-sm">Écraser et restaurer</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>