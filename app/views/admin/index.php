<?php include __DIR__ . '/../layouts/header.php'; ?>
<?php use app\models\User; ?>

<div class="container py-4">

    <!-- EN-TÊTE -->
    <div class="page-header mb-5">
        <div class="d-flex align-items-center">
            <div class="page-header-avatar bg-dark text-white shadow-sm me-3">
                <i class="bi bi-shield-lock"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Administration</h2>
                <p class="text-muted small mb-0">Pilotage des accès et configuration de la plateforme</p>
            </div>
        </div>
        <a href="<?= URLROOT ?>/dashboard" class="btn btn-light border fw-bold rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i>Dashboard
        </a>
    </div>

    <!-- STATISTIQUES -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);">
                <div class="position-absolute top-0 end-0 p-3 opacity-10 text-primary stat-icon">
                    <i class="bi bi-person-vcard display-1"></i>
                </div>
                <div class="card-body p-4 position-relative z-1">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-3">
                            <i class="bi bi-person-badge fs-5"></i>
                        </div>
                        <h6 class="text-muted mb-0 fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">Comptes Utilisateurs</h6>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark display-6"><?= $count_users ?></h2>
                    <span class="small text-muted fw-medium">inscrits sur le portail</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative" style="background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);">
                <div class="position-absolute top-0 end-0 p-3 opacity-10 text-success stat-icon">
                    <i class="bi bi-diagram-3 display-1"></i>
                </div>
                <div class="card-body p-4 position-relative z-1">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 text-success p-2 rounded-3 me-3">
                            <i class="bi bi-diagram-3 fs-5"></i>
                        </div>
                        <h6 class="text-muted mb-0 fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">Instances actives</h6>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark display-6"><?= $count_instances ?></h2>
                    <span class="small text-muted fw-medium">configurées</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative" style="background: linear-gradient(135deg, #ffffff 0%, #fffbf0 100%);">
                <div class="position-absolute top-0 end-0 p-3 opacity-10 text-warning stat-icon">
                    <i class="bi bi-people display-1"></i>
                </div>
                <div class="card-body p-4 position-relative z-1">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-warning bg-opacity-10 text-warning p-2 rounded-3 me-3" style="color: #856404 !important;">
                            <i class="bi bi-people-fill fs-5"></i>
                        </div>
                        <h6 class="text-muted mb-0 fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">Membres Répartis</h6>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark display-6"><?= $count_membres ?></h2>
                    <span class="small text-muted fw-medium">siégeant dans les instances</span>
                </div>
            </div>
        </div>
    </div>

    <!-- OUTILS -->
    <h5 class="fw-bold mb-4 text-dark">
        <i class="bi bi-tools me-2 text-secondary opacity-75"></i>Outils d'administration
    </h5>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">

        <?php if (User::can('manage_users')): ?>
        <div class="col">
            <div class="card card-tool">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="admin-tool-icon bg-primary bg-opacity-10 text-primary position-relative">
                        <i class="bi bi-person-gear"></i>
                        <?php if (!empty($count_pending) && $count_pending > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.65rem;">
                                <?= $count_pending ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-bold text-dark">Comptes & Permissions</h5>
                    <p class="text-muted small flex-grow-1">Créer des comptes pour les agents, modifier les identifiants et attribuer les droits d'administration.</p>
                    <a href="<?= URLROOT ?>/admin/users" class="btn btn-light border w-100 fw-bold text-primary rounded-pill mt-2">Gérer les accès</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (User::can('manage_instances')): ?>
        <div class="col">
            <div class="card card-tool">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="admin-tool-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-diagram-3-fill"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Instances & Collèges</h5>
                    <p class="text-muted small flex-grow-1">Paramétrer la composition des instances, désigner les gestionnaires et importer les listes de membres.</p>
                    <a href="<?= URLROOT ?>/admin/instances" class="btn btn-light border w-100 fw-bold text-success rounded-pill mt-2">Configurer les instances</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (User::can('view_logs')): ?>
        <div class="col">
            <div class="card card-tool">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="admin-tool-icon bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-fingerprint"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Journal d'audit</h5>
                    <p class="text-muted small flex-grow-1">Visualiser l'historique complet et sécurisé des actions effectuées par l'ensemble des utilisateurs.</p>
                    <a href="<?= URLROOT ?>/admin/logs" class="btn btn-light border w-100 fw-bold text-secondary rounded-pill mt-2">Consulter les logs</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (User::can('manage_system')): ?>
        <div class="col">
            <div class="card card-tool">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="admin-tool-icon bg-dark bg-opacity-10 text-dark">
                        <i class="bi bi-sliders2-vertical"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Paramètres système</h5>
                    <p class="text-muted small flex-grow-1">Gérer la configuration technique de KronoInstances, le nom de la collectivité et l'envoi d'e-mails.</p>
                    <a href="<?= URLROOT ?>/admin/parametres" class="btn btn-dark w-100 fw-bold rounded-pill mt-2 shadow-sm">Accéder aux réglages</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ENCART MISE À JOUR -->
    <?php if (!empty($has_update) && User::can('manage_system')): ?>
    <div class="mt-5">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #0dcaf0 0%, #087990 100%);">
            <div class="card-body p-4 text-white d-flex align-items-center position-relative">
                <i class="bi bi-cloud-arrow-down-fill display-3 position-absolute end-0 opacity-25 me-4"></i>
                <div class="bg-white bg-opacity-25 p-3 rounded-circle me-4 d-none d-md-flex">
                    <i class="bi bi-stars fs-2"></i>
                </div>
                <div class="flex-grow-1 z-1">
                    <h5 class="fw-bold mb-1">Mise à jour disponible (v<?= htmlspecialchars($new_v_name) ?>) !</h5>
                    <p class="mb-0 small opacity-75">Installez la dernière version de KronoInstances pour bénéficier des correctifs et nouveautés.</p>
                </div>
                <div class="ms-3 z-1">
                    <a href="<?= URLROOT ?>/admin/parametres?section=update" class="btn btn-light text-info fw-bold px-4 py-2 rounded-pill shadow-sm text-nowrap">
                        Voir les détails
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
