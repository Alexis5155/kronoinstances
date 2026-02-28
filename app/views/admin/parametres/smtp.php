<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-envelope-at me-2 text-primary"></i>Serveur SMTP</h5>
    </div>
    <div class="card-body pt-0">
        <div class="alert alert-info border-0 small mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> Cette section modifie directement le fichier <code>app/config/config.php</code>. En cas d'erreur lors de la modification, vous pourriez perdre l'accès à votre application.
        </div>
        <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=smtp">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
            <input type="hidden" name="action" value="update_smtp">
            
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.65rem;">Serveur SMTP (Hôte)</label>
                    <input type="text" class="form-control" name="smtp_host" value="<?= defined('MAIL_HOST') ? MAIL_HOST : '' ?>" placeholder="ex: smtp.office365.com" required>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.65rem;">Port</label>
                    <input type="number" class="form-control" name="smtp_port" value="<?= defined('MAIL_PORT') ? MAIL_PORT : '587' ?>" required>
                </div>
                
                <div class="col-md-6 mt-3">
                    <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.65rem;">Utilisateur / Email de connexion</label>
                    <input type="text" class="form-control" name="smtp_user" value="<?= defined('MAIL_USER') ? MAIL_USER : '' ?>" placeholder="contact@macollectivite.fr" required>
                </div>
                <div class="col-md-6 mt-3">
                    <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.65rem;">Mot de passe de l'email</label>
                    <input type="password" class="form-control" name="smtp_pass" placeholder="Laissez vide pour conserver l'actuel">
                </div>
                
                <div class="col-12 mt-3">
                    <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.65rem;">Adresse d'expédition affichée (De)</label>
                    <input type="email" class="form-control" name="smtp_from" value="<?= defined('MAIL_FROM') ? MAIL_FROM : '' ?>" placeholder="ne-pas-repondre@macollectivite.fr" required>
                </div>
            </div>
            
            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm">Enregistrer la configuration</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm bg-light">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-send-check me-2"></i> Tester la configuration</h6>
        <p class="small text-muted mb-3">Saisissez une adresse email pour vérifier que KronoInstances parvient bien à envoyer des messages avec les paramètres actuels.</p>
        
        <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=smtp" class="d-flex flex-column flex-sm-row gap-2">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
            <input type="hidden" name="action" value="test_email">
            
            <input type="email" class="form-control" name="test_email_address" placeholder="Votre adresse email personnelle..." required>
            <button type="submit" class="btn btn-dark fw-bold text-nowrap px-4"><i class="bi bi-paper-plane me-2"></i> Envoyer un test</button>
        </form>
    </div>
</div>
