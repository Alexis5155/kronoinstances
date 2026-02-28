<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte - KronoInstances</title>
    <style>
        /* Avatar stylisé */
        .avatar-circle {
            width: 80px;
            height: 80px;
            font-size: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.2);
        }

        /* Harmonisation des sections */
        .section-title {
            font-size: 0.75rem;
            letter-spacing: 1px;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }

        .form-label {
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light text-dark">
    <?php include __DIR__ . '/../layouts/header.php'; ?>
    
    <div class="container py-4">
        <div class="mb-4 px-2">
            <a href="<?= URLROOT ?>/dashboard" class="text-decoration-none small text-primary fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Retour au tableau de bord
            </a>
            <h2 class="fw-bold mt-2">Mon profil 👤</h2>
            <p class="text-muted small">Gérez vos informations et la sécurité de votre accès</p>
        </div>

        <div class="row g-4 px-2">
            
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold mb-0 text-uppercase small text-muted" style="letter-spacing: 0.5px;">
                            <i class="bi bi-person-lines-fill me-2"></i> Paramètres du compte
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="<?= URLROOT ?>/compte">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            
                            <!-- Ligne Nom / Prénom en lecture seule -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-muted fw-bold small">Prénom</label>
                                    <input type="text" class="form-control bg-light text-muted" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-muted fw-bold small">Nom</label>
                                    <input type="text" class="form-control bg-light text-muted" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" readonly>
                                </div>
                                <div class="col-12 mt-1">
                                    <div class="form-text x-small italic text-muted">
                                        <i class="bi bi-info-circle me-1"></i> Ces informations sont définies par l'administration. En cas d'erreur, veuillez contacter un administrateur.
                                    </div>
                                </div>
                            </div>

                            <hr class="border-light my-4">

                            <!-- Ligne Identifiant / Email -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-muted fw-bold small">Identifiant de connexion</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-at"></i></span>
                                        <input type="text" class="form-control bg-light border-start-0 fw-bold text-muted" value="<?= htmlspecialchars($user['username'] ?? '') ?>" readonly>
                                    </div>
                                    <div class="form-text x-small italic text-muted mt-2">
                                        <i class="bi bi-lock me-1"></i> Non-modifiable
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-muted fw-bold small">Adresse email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="section-title text-uppercase text-muted fw-bold mb-4 mt-5 small">
                                <i class="bi bi-shield-lock me-2 text-primary"></i> Sécurité du compte
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-uppercase text-muted fw-bold small">Changer le mot de passe</label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-key"></i></span>
                                    <input type="password" name="password" class="form-control border-start-0" placeholder="Laissez vide pour ne pas changer" autocomplete="new-password">
                                </div>
                                <div class="form-text x-small text-muted italic">
                                    <i class="bi bi-shield-check me-1"></i> Pour une sécurité optimale, utilisez au moins 8 caractères avec chiffres et symboles.
                                </div>
                            </div>

                            <div class="d-flex justify-content-end border-top pt-4">
                                <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm">
                                    <i class="bi bi-check-lg me-2"></i> Mettre à jour mon profil
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 bg-white sticky-top" style="top: 20px; z-index: 10;">
                    <div class="card-body text-center p-4">
                        <div class="mb-4 pt-2">
                            <div class="avatar-circle rounded-circle mx-auto">
                                <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
                            </div>
                        </div>
                        
                        <h4 class="fw-bold mb-1 text-dark">
                            <?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?>
                        </h4>
                        <p class="text-muted small mb-4">@<?= htmlspecialchars($user['username'] ?? '') ?></p>
                        
                        <hr class="w-25 mx-auto my-4 opacity-10">

                        <!-- Badge Admin qui ne s'affiche QUE si l'utilisateur est admin -->
                        <?php if($isAdmin): ?>
                        <div class="text-center mb-4">
                            <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-2 rounded-pill small fw-bold">
                                <i class="bi bi-shield-lock-fill me-1"></i> Administrateur
                            </span>
                        </div>
                        <?php endif; ?>

                        <!-- Affichage des permissions / Droits en capsules -->
                        <div class="text-center mt-4">
                            <label class="text-uppercase text-muted fw-bold d-block mb-3" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                Vos droits d'accès
                            </label>
                            
                            <?php if (!empty($userPermissionsNames)): ?>
                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    <?php foreach($userPermissionsNames as $permData): ?>
                                        <span class="badge rounded-pill bg-light text-dark border fw-normal px-3 py-2" title="<?= htmlspecialchars($permData['desc'] ?? '') ?>">
                                            <i class="bi bi-check2-circle text-success me-1"></i> <?= htmlspecialchars($permData['nom'] ?? 'Droit inconnu') ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="bg-primary bg-opacity-10 border border-primary border-opacity-25 p-3 rounded-3 text-start">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-book text-primary fs-5 me-2"></i>
                                        <span class="fw-bold text-primary small">Profil Lecteur</span>
                                    </div>
                                    <p class="x-small text-dark mb-0 lh-base">
                                        Vous avez un accès en lecture seule. Vous pouvez consulter et télécharger les documents des séances pour les instances dont vous êtes membre.
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>