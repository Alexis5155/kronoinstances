<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 border-0 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold d-flex align-items-center">
            <div class="bg-danger bg-opacity-10 text-danger p-2 rounded-3 me-2 d-inline-flex">
                <i class="bi bi-database-gear"></i>
            </div>
            Base de données
        </h6>
        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1 fw-medium" style="font-size:0.7rem;">
            <i class="bi bi-exclamation-triangle me-1"></i>Zone critique
        </span>
    </div>
    <div class="card-body p-4">

        <div class="alert border-0 rounded-3 mb-4 d-flex align-items-center gap-3" style="background-color:#fff8e1;">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-5 flex-shrink-0"></i>
            <span class="small text-dark">Cette section modifie directement <code>app/config/config.php</code>. En cas d'erreur, vous pourriez perdre l'accès à l'application.</span>
        </div>

        <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=system">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
            <input type="hidden" name="action" value="update_system">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Hôte</label>
                    <input type="text" class="form-control bg-light" name="db_host"
                           value="<?= defined('DB_HOST') ? DB_HOST : '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Base de données</label>
                    <input type="text" class="form-control bg-light" name="db_name"
                           value="<?= defined('DB_NAME') ? DB_NAME : '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Utilisateur</label>
                    <input type="text" class="form-control bg-light" name="db_user"
                           value="<?= defined('DB_USER') ? DB_USER : '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Nouveau mot de passe (optionnel)</label>
                    <input type="password" class="form-control bg-light" name="db_pass"
                           placeholder="Laisser vide pour ne pas modifier">
                </div>
            </div>

            <!-- Bouton déclenchant le modal de confirmation -->
            <div class="mt-4">
                <button type="button" class="btn btn-danger fw-bold shadow-sm rounded-pill px-4"
                        data-bs-toggle="modal" data-bs-target="#confirmSystemModal">
                    <i class="bi bi-shield-exclamation me-2"></i>Appliquer les changements
                </button>
            </div>

            <!-- Modal de confirmation critique -->
            <div class="modal fade" id="confirmSystemModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-body p-4 text-center">
                            <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;">
                                <i class="bi bi-shield-exclamation" style="font-size:1.8rem;"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">Action critique</h5>
                            <p class="text-muted small mb-3">Confirmez votre <strong>mot de passe administrateur</strong> pour valider la réécriture du fichier système.</p>
                            <input type="password" name="confirm_password"
                                   class="form-control bg-light text-center fw-bold mb-3"
                                   placeholder="••••••••" required>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-light fw-bold flex-grow-1 rounded-pill" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-danger fw-bold flex-grow-1 shadow-sm rounded-pill">Confirmer</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>