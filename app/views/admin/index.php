<?php include __DIR__ . '/../header.php'; ?>
<?php use app\models\User; ?>

<style>
    /* Harmonisation des cartes d'outils */
    .card-tool { 
        border-radius: 12px; 
        transition: all 0.25s ease-in-out; 
        border: 1px solid #dee2e6 !important; 
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .card-tool:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important;
    }
    
    /* Bordures hautes de 5px comme à l'origine */
    .b-top-primary { border-top: 5px solid #0d6efd !important; }
    .b-top-success { border-top: 5px solid #198754 !important; }
    .b-top-warning { border-top: 5px solid #ffc107 !important; }
    .b-top-dark    { border-top: 5px solid #212529 !important; }
    .b-top-purple  { border-top: 5px solid #6f42c1 !important; }
    .b-top-secondary { border-top: 5px solid #6c757d !important; }

    .icon-box-admin {
        width: 45px; height: 45px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 10px; font-size: 1.3rem; margin-bottom: 1rem;
    }

    /* Ajustement de la lisibilité pour le jaune/orange sur fond blanc */
    .text-warning-dark { color: #856404 !important; }
</style>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3 px-2">
        <div>
            <a href="<?= URLROOT ?>/dashboard" class="text-decoration-none small text-primary fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            <h2 class="fw-bold mt-2 mb-0">⚙️ Administration</h2>
            <p class="text-muted mb-0 small">Pilotage et configuration du système</p>
        </div>
    </div>

    <div class="row g-3 mb-5 px-2">
        <div class="col-12 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="bg-light p-3 rounded-3 me-3 text-dark"><i class="bi bi-people fs-4"></i></div>
                    <div>
                        <p class="text-uppercase text-muted fw-bold small mb-0" style="letter-spacing: 0.5px;">Utilisateurs</p>
                        <h3 class="fw-bold mb-0"><?= $count_users ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="bg-light p-3 rounded-3 me-3 text-dark"><i class="bi bi-calendar-check fs-4"></i></div>
                    <div>
                        <p class="text-uppercase text-muted fw-bold small mb-0" style="letter-spacing: 0.5px;">Actes <?= $annee ?></p>
                        <h3 class="fw-bold mb-0"><?= $count_year ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="bg-light p-3 rounded-3 me-3 text-dark"><i class="bi bi-archive fs-4"></i></div>
                    <div>
                        <p class="text-uppercase text-muted fw-bold small mb-0" style="letter-spacing: 0.5px;">Base totale</p>
                        <h3 class="fw-bold mb-0"><?= $count_total ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 px-2">
        
        <?php if(User::can('export_registre')): ?>
        <div class="col">
            <div class="card card-tool shadow-sm b-top-success">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="icon-box-admin bg-success bg-opacity-10 text-success"><i class="bi bi-download"></i></div>
                    <h5 class="fw-bold text-success">Exportation</h5>
                    <p class="text-muted small flex-grow-1">Générer les registres annuels officiels ou sauvegarder la base de données.</p>
                    <a href="<?= URLROOT ?>/admin/export" class="btn btn-success text-white w-100 fw-bold shadow-sm">Gérer les exports</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if(User::can('manage_users')): ?>
        <div class="col">
            <div class="card card-tool shadow-sm b-top-primary">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="icon-box-admin bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                    <h5 class="fw-bold text-primary">Utilisateurs</h5>
                    <p class="text-muted small flex-grow-1">Créer des comptes, réinitialiser des mots de passe et gérer les accès.</p>
                    <a href="<?= URLROOT ?>/admin/users" class="btn btn-primary w-100 fw-bold shadow-sm">Gérer les agents</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if(User::can('manage_roles')): ?>
        <div class="col">
            <div class="card card-tool shadow-sm b-top-purple">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="icon-box-admin bg-purple bg-opacity-10" style="background-color: rgba(111, 66, 193, 0.1); color: #6f42c1;"><i class="bi bi-shield-lock"></i></div>
                    <h5 class="fw-bold" style="color: #6f42c1;">Permissions</h5>
                    <p class="text-muted small flex-grow-1">Définir les rôles et les droits d'accès associés à chaque profil.</p>
                    <a href="<?= URLROOT ?>/admin/roles" class="btn text-white w-100 fw-bold shadow-sm" style="background-color: #6f42c1;">Configurer les rôles</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if(User::can('view_logs')): ?>
        <div class="col">
            <div class="card card-tool shadow-sm b-top-secondary">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="icon-box-admin bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-fingerprint"></i></div>
                    <h5 class="fw-bold text-secondary">Journal d'audit</h5>
                    <p class="text-muted small flex-grow-1">Visualiser l'historique technique des actions pour audit de sécurité.</p>
                    <a href="<?= URLROOT ?>/admin/logs" class="btn btn-secondary text-white w-100 fw-bold shadow-sm">Voir les logs</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if(User::can('manage_services') || User::can('manage_signataires') || User::can('manage_system')): ?>
        <div class="col">
            <div class="card card-tool shadow-sm b-top-warning">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="icon-box-admin bg-warning bg-opacity-10 text-warning-dark"><i class="bi bi-sliders"></i></div>
                    <h5 class="fw-bold text-warning-dark">Paramètres</h5>
                    <p class="text-muted small flex-grow-1">Configuration générale, liste des services et des signataires.</p>
                    <a href="<?= URLROOT ?>/admin/parametres" class="btn btn-warning w-100 fw-bold text-dark shadow-sm">Accéder aux réglages</a>
                </div>
            </div>
        </div>
        <?php endif; ?>



    </div>

    <?php if ($has_update && User::can('manage_system')): ?>
    <div class="mt-5 px-2">
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center p-4 rounded-4" role="alert">
            <div class="bg-info bg-opacity-10 p-3 rounded-circle mb-0 me-4 text-info fs-3 d-none d-md-flex">
                <i class="bi bi-stars"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="fw-bold mb-1">Mise à jour disponible (v<?= $new_v_name ?>) !</h5>
                <p class="mb-0 text-muted small">Améliorez votre expérience en installant la dernière version de KronoActes.</p>
            </div>
            <a href="<?= URLROOT ?>/admin/parametres?section=update" class="btn btn-primary fw-bold px-4 py-2 shadow-sm ms-3">Mettre à jour</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../footer.php'; ?>