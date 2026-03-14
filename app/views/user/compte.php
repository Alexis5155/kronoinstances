<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-4">

    <!-- EN-TÊTE -->
    <div class="page-header mb-4">
        <div class="d-flex align-items-center">
            <div class="page-header-avatar bg-primary text-white shadow-sm me-3">
                <i class="bi bi-person-badge"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Mon profil</h2>
                <p class="text-muted small mb-0">Gérez vos informations et la sécurité de votre accès</p>
            </div>
        </div>
        <a href="<?= URLROOT ?>/dashboard" class="btn btn-light border fw-bold rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i>Dashboard
        </a>
    </div>

    <div class="row g-4">

        <!-- COLONNE GAUCHE : FORMULAIRE -->
        <div class="col-lg-8">
            <div class="card-section card">
                <div class="card-header">
                    <h6 class="fw-bold mb-0 section-label">
                        <i class="bi bi-person-lines-fill me-2"></i>Paramètres du compte
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="<?= URLROOT ?>/compte">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

                        <!-- Nom / Prénom (lecture seule) -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="section-label">Prénom</label>
                                <input type="text" class="form-control bg-light text-muted"
                                       value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="section-label">Nom</label>
                                <input type="text" class="form-control bg-light text-muted"
                                       value="<?= htmlspecialchars($user['nom'] ?? '') ?>" readonly>
                            </div>
                            <p class="form-text small text-muted mb-0">
                                <i class="bi bi-info-circle me-1"></i>Ces informations sont définies par l'administration. En cas d'erreur, contactez un administrateur.
                            </p>
                        </div>

                        <hr class="my-4 border-light">

                        <!-- Identifiant / Email -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="section-label">Identifiant de connexion</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-at text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control bg-light border-start-0 fw-bold text-muted"
                                           value="<?= htmlspecialchars($user['username'] ?? '') ?>" readonly>
                                </div>
                                <p class="form-text small text-muted mt-1 mb-0">
                                    <i class="bi bi-lock me-1"></i>Non modifiable
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="section-label">Adresse e-mail</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <hr class="my-4 border-light">

                        <!-- Mot de passe -->
                        <h6 class="fw-bold section-label mb-3">
                            <i class="bi bi-shield-lock me-2 text-primary"></i>Sécurité du compte
                        </h6>
                        <div class="mb-4">
                            <label class="section-label">Changer le mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-key text-muted"></i>
                                </span>
                                <input type="password" name="password" class="form-control border-start-0"
                                       placeholder="Laissez vide pour ne pas changer" autocomplete="new-password">
                            </div>
                            <p class="form-text small text-muted mt-1 mb-0">
                                <i class="bi bi-shield-check me-1"></i>Pour une sécurité optimale, utilisez au moins 8 caractères avec chiffres et symboles.
                            </p>
                        </div>

                        <div class="d-flex justify-content-end border-top pt-4">
                            <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">
                                <i class="bi bi-check-lg me-2"></i>Mettre à jour mon profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE : CARTE PROFIL -->
        <div class="col-lg-4">
            <div class="card-section card sticky-top" style="top: 20px;">
                <div class="card-body p-4 text-center">

                    <!-- Avatar -->
                    <div class="mb-3">
                        <div class="rounded-circle bg-primary text-white fw-bold shadow-sm mx-auto d-flex align-items-center justify-content-center"
                             style="width: 80px; height: 80px; font-size: 2rem; background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;">
                            <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-0 text-dark">
                        <?= htmlspecialchars(trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))) ?>
                    </h5>
                    <p class="text-muted small mb-3">@<?= htmlspecialchars($user['username'] ?? '') ?></p>

                    <?php if ($isAdmin): ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3 py-2 small fw-bold mb-3">
                        <i class="bi bi-shield-lock-fill me-1"></i>Administrateur
                    </span>
                    <?php endif; ?>

                    <hr class="border-light my-3">

                    <!-- Droits d'accès -->
                    <label class="section-label mb-3">Vos droits d'accès</label>

                    <?php if (!empty($userPermissionsNames)): ?>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <?php foreach ($userPermissionsNames as $permData): ?>
                                <span class="badge rounded-pill bg-light text-dark border fw-normal px-3 py-2"
                                      title="<?= htmlspecialchars($permData['desc'] ?? '') ?>">
                                    <i class="bi bi-check2-circle text-success me-1"></i><?= htmlspecialchars($permData['nom'] ?? 'Droit inconnu') ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-primary bg-opacity-10 border border-primary border-opacity-25 p-3 rounded-3 text-start">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-book text-primary fs-5 me-2"></i>
                                <span class="fw-bold text-primary small">Profil Lecteur</span>
                            </div>
                            <p class="small text-dark mb-0 lh-base">
                                Vous avez un accès en lecture seule. Vous pouvez consulter et télécharger les documents des séances pour les instances dont vous êtes membre.
                            </p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
