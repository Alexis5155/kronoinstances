<?php include __DIR__ . '/../layouts/header.php'; ?>
<?php use app\models\User; ?>

<style>
    /* Harmonisation avec le dashboard */
    .stat-card { z-index: 1; }
    .stat-icon { z-index: -1; right: -10px !important; top: 10px !important; }
    @media (max-width: 1200px) { .stat-icon { opacity: 0.05 !important; } }

    /* Cartes des outils admin */
    .card-tool { 
        border-radius: 1rem; 
        transition: transform 0.25s ease, box-shadow 0.25s ease; 
        border: 1px solid rgba(0,0,0,0.05) !important; 
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .card-tool:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
    }
    
    .icon-box-admin {
        width: 50px; height: 50px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 12px; font-size: 1.5rem; margin-bottom: 1.2rem;
    }
</style>

<div class="container py-4">
    <!-- EN-TÊTE -->
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div class="d-flex align-items-center">
            <div class="avatar-circle rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold shadow-sm me-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                <i class="bi bi-shield-lock"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Administration</h2>
                <p class="text-muted small mb-0">Pilotage des accès et configuration de la plateforme</p>
            </div>
        </div>
        <div>
            <a href="<?= URLROOT ?>/dashboard" class="btn btn-light fw-bold shadow-sm px-4 rounded-pill border">
                <i class="bi bi-arrow-left me-2"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- STATISTIQUES GLOBALES -->
    <div class="row g-4 mb-5">
        <!-- Widget Utilisateurs -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative stat-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);">
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

        <!-- Widget Instances -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative stat-card" style="background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);">
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

        <!-- Widget Membres -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative stat-card" style="background: linear-gradient(135deg, #ffffff 0%, #fffbf0 100%);">
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

    <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-tools me-2 text-secondary opacity-75"></i>Outils d'administration</h5>

    <!-- GRILLE D'OUTILS -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">

        <!-- GESTION DES UTILISATEURS -->
        <?php if(User::can('manage_users')): ?>
        <div class="col">
            <div class="card card-tool bg-white">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="icon-box-admin bg-primary bg-opacity-10 text-primary position-relative">
                        <i class="bi bi-person-gear"></i>
                        <?php if (!empty($count_pending) && $count_pending > 0): ?>
                            <span class="position-absolute top-0 end-0 translate-middle badge rounded-pill bg-warning text-dark" style="font-size:0.65rem;">
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

        <!-- GESTION DES INSTANCES -->
        <?php if(User::can('manage_instances')): ?>
        <div class="col">
            <div class="card card-tool bg-white">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="icon-box-admin bg-success bg-opacity-10 text-success"><i class="bi bi-diagram-3-fill"></i></div>
                    <h5 class="fw-bold text-dark">Instances & Collèges</h5>
                    <p class="text-muted small flex-grow-1">Paramétrer la composition des instances, désigner les gestionnaires et importer les listes de membres.</p>
                    <a href="<?= URLROOT ?>/admin/instances" class="btn btn-light border w-100 fw-bold text-success rounded-pill mt-2">Configurer les instances</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- JOURNAL D'AUDIT -->
        <?php if(User::can('view_logs')): ?>
        <div class="col">
            <div class="card card-tool bg-white">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="icon-box-admin bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-fingerprint"></i></div>
                    <h5 class="fw-bold text-dark">Journal d'audit</h5>
                    <p class="text-muted small flex-grow-1">Visualiser l'historique complet et sécurisé des actions effectuées par l'ensemble des utilisateurs.</p>
                    <a href="<?= URLROOT ?>/admin/logs" class="btn btn-light border w-100 fw-bold text-secondary rounded-pill mt-2">Consulter les logs</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- PARAMÈTRES SYSTÈME -->
        <?php if(User::can('manage_system')): ?>
        <div class="col">
            <div class="card card-tool bg-white">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="icon-box-admin bg-dark bg-opacity-10 text-dark"><i class="bi bi-sliders2-vertical"></i></div>
                    <h5 class="fw-bold text-dark">Paramètres système</h5>
                    <p class="text-muted small flex-grow-1">Gérer la configuration technique de KronoInstances, le nom de la collectivité et l'envoi d'e-mails.</p>
                    <a href="<?= URLROOT ?>/admin/parametres" class="btn btn-dark w-100 fw-bold rounded-pill mt-2 shadow-sm">Accéder aux réglages</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ENCART DE MISE À JOUR -->
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
                    <a href="<?= URLROOT ?>/admin/parametres?section=update" class="btn btn-light text-info fw-bold px-4 py-2 rounded-pill shadow-sm text-nowrap">Voir les détails</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
