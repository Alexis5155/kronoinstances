<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte - KronoActes</title>
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
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-muted fw-bold small">Identifiant</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-at"></i></span>
                                        <input type="text" class="form-control bg-light border-start-0 fw-bold" value="<?= htmlspecialchars($user['username']) ?>" readonly disabled>
                                    </div>
                                    <div class="form-text x-small italic text-muted mt-2">
                                        <i class="bi bi-info-circle me-1"></i> Non-modifiable
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-uppercase text-muted fw-bold small">Adresse email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
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
                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                            </div>
                        </div>
                        
                        <h4 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($user['username']) ?></h4>
                        <p class="text-muted small mb-4"><?= htmlspecialchars($user['email']) ?></p>
                        
                        <hr class="w-25 mx-auto my-4 opacity-10">

                        <div class="text-start mb-4">
                            <div class="mb-4">
                                <label class="text-uppercase text-muted fw-bold d-block mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Rôle système</label>
                                <span class="badge bg-white text-primary border border-primary px-3 py-2 rounded-pill small fw-bold">
                                    <i class="bi bi-shield-shaded me-1"></i> <?= htmlspecialchars($user['role_name'] ?? 'Utilisateur') ?>
                                </span>
                            </div>

                            <div class="mb-0">
                                <label class="text-uppercase text-muted fw-bold d-block mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Rattachement administratif</label>
                                <div class="d-flex align-items-center fw-bold text-dark small">
                                    <?php if(!empty($user['service_nom'])): ?>
                                        <div class="bg-light rounded p-2 me-2 text-primary"><i class="bi bi-building"></i></div>
                                        <?= htmlspecialchars($user['service_nom']) ?>
                                    <?php else: ?>
                                        <div class="bg-light rounded p-2 me-2 text-muted"><i class="bi bi-building"></i></div>
                                        <span class="text-muted italic fw-normal">Aucun service</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="bg-light p-3 rounded text-start mt-4">
                            <p class="x-small text-muted mb-0 lh-base italic">
                                <i class="bi bi-info-square-fill me-1 text-primary"></i> 
                                Vos permissions dépendent de votre rôle. Contactez un administrateur pour toute modification de service ou de permissions.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>
</body>
</html>

