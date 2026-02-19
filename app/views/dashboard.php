<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - KronoActes</title>
    <style>
        /* Harmonisation globale */
        .search-bar { max-width: 700px; margin: 0 auto 2rem auto; }
        
        .nav-tabs .nav-link { border: none; color: #6c757d; font-weight: 600; padding: 1rem 1.25rem; font-size: 0.9rem; }
        .nav-tabs .nav-link.active { color: #0d6efd; border-bottom: 3px solid #0d6efd; background: none; }
        
        .nav-link.disabled-custom { color: #adb5bd !important; cursor: not-allowed !important; opacity: 0.6; }
        
        /* Badge service cohérent */
        .badge-service { font-size: 0.75rem; letter-spacing: 0.5px; }

        /* Colonnes fixes pour les tableaux */
        .col-num { width: 130px; }
        .col-action { width: 60px; }

        /* Style des boutons outils classiques */
        .btn-quick-tool { font-size: 0.85rem; font-weight: 500; padding: 0.6rem 1rem; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light text-dark">
    <?php include 'header.php'; ?>
    <?php use app\models\User; ?>
    
    <div class="container py-4">
        <div class="search-bar px-2">
            <form action="liste" method="GET" class="input-group shadow-sm border rounded-pill overflow-hidden bg-white">
                <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-0 py-2 shadow-none" placeholder="Numéro, titre, mot-clé...">
                <button class="btn btn-dark px-4 fw-bold" type="submit">Rechercher</button>
            </form>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 px-2">
            <div>
                <h2 class="fw-bold mb-0">Bonjour, <?= htmlspecialchars($username) ?> 👋</h2>
                <div class="mt-1 d-flex align-items-center">
                    <span class="badge bg-white text-primary border shadow-sm px-3 py-2 badge-service text-uppercase fw-bold">
                        <i class="bi bi-building me-1"></i> <?= htmlspecialchars($my_service_nom) ?>
                    </span>
                </div>
            </div>
            
            <?php if(User::can('create_acte')): ?>
            <a href="<?= URLROOT ?>/nouveau" class="btn btn-primary fw-bold shadow-sm px-4 py-2">
                <i class="bi bi-plus-lg me-1"></i> Nouveau numéro
            </a>
            <?php endif; ?>
        </div>

        <div class="row g-3 mb-4 px-2">
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3"><i class="bi bi-collection text-primary fs-4"></i></div>
                        <div>
                            <p class="text-uppercase text-muted fw-bold small mb-0" style="letter-spacing: 0.5px;">Mes actes</p>
                            <h3 class="fw-bold mb-0"><?= $stats['total'] ?></h3>
                        </div>
                        <a href="liste" class="stretched-link"></a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-3 me-3"><i class="bi bi-hourglass-split text-warning fs-4"></i></div>
                        <div>
                            <p class="text-uppercase text-muted fw-bold small mb-0" style="letter-spacing: 0.5px;">En cours</p>
                            <h3 class="fw-bold mb-0 text-warning"><?= $stats['pending'] ?></h3>
                        </div>
                        <a href="liste?f_statut=en+cours" class="stretched-link"></a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 p-3 rounded-3 me-3"><i class="bi bi-check2-all text-success fs-4"></i></div>
                        <div>
                            <p class="text-uppercase text-muted fw-bold small mb-0" style="letter-spacing: 0.5px;">Achevés</p>
                            <h3 class="fw-bold mb-0 text-success"><?= $stats['done'] ?></h3>
                        </div>
                        <a href="liste?f_statut=achevé" class="stretched-link"></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 px-2">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-white p-0 border-bottom">
                        <ul class="nav nav-tabs border-0" id="dashTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#me-content" type="button" role="tab">
                                    <i class="bi bi-person-fill me-1"></i> Mes dossiers
                                </button>
                            </li>
                            <li class="nav-item">
                                <?php if ($my_service_id && User::can('view_service_actes')): ?>
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#service-content" type="button" role="tab">
                                        <i class="bi bi-buildings-fill me-1"></i> Mon service
                                    </button>
                                <?php else: ?>
                                    <button class="nav-link disabled-custom" type="button">
                                        <i class="bi bi-lock-fill me-1"></i> Service
                                    </button>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="me-content" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="text-uppercase text-muted small fw-bold" style="letter-spacing: 0.5px;">
                                            <th class="col-num ps-4">Numéro</th>
                                            <th>Titre de l'acte</th>
                                            <th class="text-end pe-4 col-action"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        <?php if(empty($pending_list)): ?>
                                            <tr><td colspan="3" class="text-center py-5 text-muted">Aucun dossier en cours.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($pending_list as $row): ?>
                                            <tr>
                                                <td class="fw-bold text-dark ps-4">
                                                    <?php if($row['num_complet']): ?>
                                                        <?= $row['num_complet'] ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-dark border me-1">#<?= $row['id'] ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-truncate" style="max-width: 250px;">
                                                    <!-- AJOUT DU BADGE WORKFLOW DANS LA CELLULE TITRE -->
                                                    <?php 
                                                    $st = $row['statut_workflow'] ?? 'brouillon';
                                                    if($st === 'en_validation'): ?>
                                                        <span class="badge bg-primary me-1" style="font-size:0.7em;">WORKFLOW</span>
                                                    <?php elseif($st === 'rejete'): ?>
                                                        <span class="badge bg-danger me-1" style="font-size:0.7em;">REJETÉ</span>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($row['titre']) ?>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <!-- LOGIQUE DE BOUTON ADAPTÉE AU WORKFLOW -->
                                                    <?php if(isset($row['statut_workflow']) && $row['statut_workflow'] === 'en_validation'): ?>
                                                        <a href="<?= URLROOT ?>/workflow/view/<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary border-0 py-1" title="Suivre">
                                                            <i class="bi bi-diagram-3"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="<?= URLROOT ?>/modifier/<?= $row['id'] ?>?from=dashboard" class="btn btn-sm btn-outline-secondary border-0 py-1" title="Modifier">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if($stats['pending'] > 5): ?>
                                <a href="liste?view=me&f_statut=en+cours" class="card-footer d-block text-center py-3 small text-decoration-none fw-bold text-primary bg-white border-top">
                                    Voir tous mes dossiers en cours →
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ($my_service_id && User::can('view_service_actes')): ?>
                        <div class="tab-pane fade" id="service-content" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="text-uppercase text-muted small fw-bold" style="letter-spacing: 0.5px;">
                                            <th class="col-num ps-4">Numéro</th>
                                            <th>Titre</th>
                                            <th class="d-none d-md-table-cell">Auteur</th>
                                            <th class="text-end pe-4 col-action"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        <?php if(empty($service_list)): ?>
                                            <tr><td colspan="4" class="text-center py-5 text-muted">Aucun dossier en cours dans le service.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($service_list as $row): ?>
                                            <tr>
                                                <td class="fw-bold text-dark ps-4">
                                                    <?php if($row['num_complet']): ?>
                                                        <?= $row['num_complet'] ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-dark border me-1">#<?= $row['id'] ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-truncate" style="max-width: 200px;">
                                                    <?php if(isset($row['statut_workflow']) && $row['statut_workflow'] === 'en_validation'): ?>
                                                        <span class="badge bg-primary me-1" style="font-size:0.7em;">VALIDATION</span>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($row['titre']) ?>
                                                </td>
                                                <td class="d-none d-md-table-cell text-muted"><?= htmlspecialchars($row['username']) ?></td>
                                                <td class="text-end pe-4">
                                                    <?php if(isset($row['statut_workflow']) && $row['statut_workflow'] === 'en_validation'): ?>
                                                        <a href="<?= URLROOT ?>/workflow/view/<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary border-0 py-1">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    <?php elseif(User::can('edit_service_actes') || User::can('edit_all_actes')): ?>
                                                        <a href="modifier/<?= $row['id'] ?>?from=dashboard" class="btn btn-sm btn-outline-secondary border-0 py-1"><i class="bi bi-pencil-square"></i></a>
                                                    <?php else: ?>
                                                        <i class="bi bi-lock text-muted"></i>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3 text-uppercase small text-muted" style="letter-spacing: 0.5px;">Outils rapides</h6>
                        <div class="d-grid gap-2">
                            <a href="liste" class="btn btn-outline-secondary btn-quick-tool text-start">
                                <i class="bi bi-list-ul me-2"></i> Toutes mes archives
                            </a>
                            <a href="compte" class="btn btn-outline-secondary btn-quick-tool text-start">
                                <i class="bi bi-person-gear me-2"></i> Gérer mon compte
                            </a>
                            <?php if(User::can('export_registre')): ?>
                                <a href="bilan" class="btn btn-outline-secondary btn-quick-tool text-start">
                                    <i class="bi bi-file-earmark-pdf me-2"></i> Exporter mon bilan
                                </a>
                            <?php endif; ?>
                            
                            <?php if(User::can('view_logs') || User::can('manage_users') || User::can('manage_system')): ?>
                                <hr class="my-2 opacity-10">
                                <a href="admin" class="btn btn-dark fw-bold text-start py-2 px-3 small shadow-sm">
                                    <i class="bi bi-shield-lock me-2"></i> Administration système
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
