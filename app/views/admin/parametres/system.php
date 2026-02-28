<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-shield-lock me-2"></i>Base de données</h5>
    </div>
    <div class="card-body pt-0">
        <div class="alert alert-warning border-0 small mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> Cette section modifie directement le fichier <code>app/config/config.php</code>. En cas d'erreur lors de la modification, vous pourriez perdre l'accès à votre application.
        </div>
        <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=system">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
            <input type="hidden" name="action" value="update_system">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="small fw-bold text-muted text-uppercase" style="font-size: 0.6rem;">Hôte</label>
                    <input type="text" class="form-control" name="db_host" value="<?= defined('DB_HOST') ? DB_HOST : '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold text-muted text-uppercase" style="font-size: 0.6rem;">Base</label>
                    <input type="text" class="form-control" name="db_name" value="<?= defined('DB_NAME') ? DB_NAME : '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold text-muted text-uppercase" style="font-size: 0.6rem;">Utilisateur</label>
                    <input type="text" class="form-control" name="db_user" value="<?= defined('DB_USER') ? DB_USER : '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold text-muted text-uppercase" style="font-size: 0.6rem;">Nouveau mot de passe BDD (optionnel)</label>
                    <input type="password" class="form-control" name="db_pass" placeholder="Laisser vide pour ne pas modifier">
                </div>
            </div>
            
            <!-- Bouton déclenchant le modal de confirmation -->
            <button type="button" class="btn btn-danger fw-bold mt-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#confirmSystemModal">Appliquer les changements</button>
            
            <!-- Modal de confirmation -->
            <div class="modal fade" id="confirmSystemModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header bg-danger text-white border-0">
                            <h5 class="modal-title fw-bold">Action critique</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4 text-center">
                            <p>Confirmez votre <strong>mot de passe administrateur</strong> (celui de votre compte) pour valider la réécriture du fichier système :</p>
                            <input type="password" name="confirm_password" class="form-control form-control-lg text-center fw-bold" placeholder="••••••••" required>
                        </div>
                        <div class="modal-footer border-0 bg-light px-4">
                            <button type="submit" class="btn btn-danger fw-bold w-100 py-2 shadow-sm">Confirmer la réécriture</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
