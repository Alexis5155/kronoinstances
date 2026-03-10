<!-- Configuration SMTP -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white py-3 border-0 border-bottom">
        <h6 class="mb-0 fw-bold d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-2 d-inline-flex">
                <i class="bi bi-envelope-at"></i>
            </div>
            Serveur SMTP
        </h6>
    </div>
    <div class="card-body p-4">

        <div class="alert border-0 rounded-3 mb-4 d-flex align-items-center gap-3" style="background-color:#fff8e1;">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-5 flex-shrink-0"></i>
            <span class="small text-dark">Cette section modifie directement <code>app/config/config.php</code>.</span>
        </div>

        <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=smtp">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
            <input type="hidden" name="action" value="update_smtp">

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Serveur SMTP (Hôte)</label>
                    <input type="text" class="form-control bg-light" name="smtp_host"
                           value="<?= defined('MAIL_HOST') ? MAIL_HOST : '' ?>"
                           placeholder="ex : smtp.office365.com" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Port</label>
                    <input type="number" class="form-control bg-light" name="smtp_port"
                           value="<?= defined('MAIL_PORT') ? MAIL_PORT : '587' ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Utilisateur / Email de connexion</label>
                    <input type="text" class="form-control bg-light" name="smtp_user"
                           value="<?= defined('MAIL_USER') ? MAIL_USER : '' ?>"
                           placeholder="contact@macollectivite.fr" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Mot de passe</label>
                    <input type="password" class="form-control bg-light" name="smtp_pass"
                           placeholder="Laisser vide pour conserver l'actuel">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Adresse d'expédition (De)</label>
                    <input type="email" class="form-control bg-light" name="smtp_from"
                           value="<?= defined('MAIL_FROM') ? MAIL_FROM : '' ?>"
                           placeholder="ne-pas-repondre@macollectivite.fr" required>
                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary fw-bold px-5 shadow-sm rounded-pill">
                    <i class="bi bi-save me-2"></i>Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Test de la configuration -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 border-0 border-bottom">
        <h6 class="mb-0 fw-bold d-flex align-items-center">
            <div class="bg-success bg-opacity-10 text-success p-2 rounded-3 me-2 d-inline-flex">
                <i class="bi bi-send-check"></i>
            </div>
            Tester la configuration
        </h6>
    </div>
    <div class="card-body p-4">
        <p class="small text-muted mb-3">Saisissez une adresse e-mail pour vérifier que KronoInstances parvient bien à envoyer des messages avec les paramètres actuels.</p>
        <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=smtp" class="d-flex flex-column flex-sm-row gap-2">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
            <input type="hidden" name="action" value="test_email">
            <input type="email" class="form-control bg-light" name="test_email_address" placeholder="Votre adresse e-mail..." required>
            <button type="submit" class="btn btn-dark fw-bold text-nowrap px-4 rounded-pill shadow-sm">
                <i class="bi bi-paper-plane me-2"></i>Envoyer un test
            </button>
        </form>
    </div>
</div>