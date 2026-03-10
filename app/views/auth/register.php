<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background:linear-gradient(135deg,#f8f9ff 0%,#eef2ff 100%);">
    <div class="w-100" style="max-width:480px;">

        <div class="text-center mb-4">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;">
                <i class="bi bi-person-plus fs-3"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Créer un compte</h3>
            <p class="text-muted small">Renseignez vos informations pour accéder à la plateforme.</p>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-4">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 rounded-3 small mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= URLROOT ?>/register">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Prénom</label>
                            <input type="text" class="form-control bg-light" name="prenom"
                                   value="<?= htmlspecialchars($old['prenom'] ?? '') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Nom</label>
                            <input type="text" class="form-control bg-light" name="nom"
                                   value="<?= htmlspecialchars($old['nom'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Adresse e-mail</label>
                        <input type="email" class="form-control bg-light" name="email"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                        <div class="form-text small"><i class="bi bi-info-circle me-1 text-primary"></i>Utilisez votre e-mail professionnel pour être lié automatiquement aux instances.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Nom d'utilisateur</label>
                        <input type="text" class="form-control bg-light" name="username"
                               value="<?= htmlspecialchars($old['user'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Mot de passe</label>
                        <input type="password" class="form-control bg-light" name="password" required minlength="8">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1" style="letter-spacing:0.5px;">Confirmer le mot de passe</label>
                        <input type="password" class="form-control bg-light" name="password2" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm py-2">
                        <i class="bi bi-person-check me-2"></i>Créer mon compte
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-muted small mt-3">
            Déjà un compte ? <a href="<?= URLROOT ?>/login" class="fw-bold text-decoration-none">Se connecter</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
