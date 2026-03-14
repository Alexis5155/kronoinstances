<?php 
include __DIR__ . '/layouts/header.php'; 
use app\models\User;

$canManageInstances   = User::can('manage_instances');
$canManageConvocations = User::can('manage_convocations');
$hasAdminAccess = User::can('view_logs') || User::can('manage_users') || User::can('manage_system');
$isManager = $canManageInstances || $canManageConvocations || User::can('create_seances');
?>

<div class="container py-4">

    <!-- EN-TÊTE -->
    <div class="page-header mb-5">
        <div class="d-flex align-items-center">
            <div class="page-header-avatar bg-primary text-white fw-bold shadow-sm me-3">
                <?= strtoupper(substr($_SESSION['prenom'] ?? $_SESSION['username'] ?? 'U', 0, 1)) ?>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">
                    Bonjour, <?= htmlspecialchars($_SESSION['prenom'] ?? $_SESSION['username']) ?> 👋
                </h2>
                <p class="text-muted small mb-0">Bienvenue sur votre espace personnel KronoInstances.</p>
            </div>
        </div>
        <?php if(User::can('create_seances')): ?>
        <a href="<?= URLROOT ?>/seances" class="btn btn-primary fw-bold rounded-pill shadow-sm px-4">
            <i class="bi bi-calendar-plus me-2"></i>Planifier une séance
        </a>
        <?php endif; ?>
    </div>

    <!-- WIDGETS STATISTIQUES -->
    <div class="row g-4 mb-5">

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative stat-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);">
                <div class="position-absolute top-0 end-0 p-3 opacity-10 stat-icon">
                    <i class="bi bi-calendar-event display-1"></i>
                </div>
                <div class="card-body p-4 position-relative z-1">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-3">
                            <i class="bi bi-calendar-week fs-5"></i>
                        </div>
                        <span class="section-label mb-0">À venir</span>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark display-6"><?= count($prochainesSeances) ?></h2>
                    <span class="small text-muted fw-medium">réunions programmées</span>
                </div>
            </div>
        </div>

        <?php if($isManager): ?>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative stat-card" style="background: linear-gradient(135deg, #ffffff 0%, #fffbf0 100%);">
                <div class="position-absolute top-0 end-0 p-3 opacity-10 text-warning stat-icon">
                    <i class="bi bi-envelope-paper display-1"></i>
                </div>
                <div class="card-body p-4 position-relative z-1">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-warning bg-opacity-10 text-warning p-2 rounded-3 me-3">
                            <i class="bi bi-envelope-exclamation fs-5"></i>
                        </div>
                        <span class="section-label mb-0">À convoquer</span>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark display-6"><?= $nbSeancesPlanifiees ?></h2>
                    <span class="small text-muted fw-medium">en attente de publication</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative stat-card" style="background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);">
                <div class="position-absolute top-0 end-0 p-3 opacity-10 text-success stat-icon">
                    <i class="bi bi-diagram-3 display-1"></i>
                </div>
                <div class="card-body p-4 position-relative z-1">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 text-success p-2 rounded-3 me-3">
                            <i class="bi bi-people fs-5"></i>
                        </div>
                        <span class="section-label mb-0">Instances actives</span>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark display-6"><?= $nbInstances ?></h2>
                    <span class="small text-muted fw-medium">configurées sur la plateforme</span>
                </div>
            </div>
        </div>

        <?php else: ?>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative stat-card" style="background: linear-gradient(135deg, #ffffff 0%, #f4f6f8 100%);">
                <div class="position-absolute top-0 end-0 p-3 opacity-10 text-dark stat-icon">
                    <i class="bi bi-person-vcard display-1"></i>
                </div>
                <div class="card-body p-4 position-relative z-1">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-dark bg-opacity-10 text-dark p-2 rounded-3 me-3">
                            <i class="bi bi-person-badge fs-5"></i>
                        </div>
                        <span class="section-label mb-0">Mes mandats</span>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark display-6"><?= count($userInstances) ?></h2>
                    <span class="small text-muted fw-medium">instances auxquelles j'appartiens</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative stat-card" style="background: linear-gradient(135deg, #ffffff 0%, #e0f2fe 100%);">
                <div class="position-absolute top-0 end-0 p-3 opacity-10 text-info stat-icon">
                    <i class="bi bi-folder2-open display-1"></i>
                </div>
                <div class="card-body p-4 position-relative z-1">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info bg-opacity-10 text-info p-2 rounded-3 me-3">
                            <i class="bi bi-file-earmark-text fs-5"></i>
                        </div>
                        <span class="section-label mb-0">Espace Documents</span>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark display-6"><?= $nbDocuments ?></h2>
                    <span class="small text-muted fw-medium">documents disponibles</span>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <div class="row g-4">

        <!-- COLONNE GAUCHE -->
        <div class="col-lg-8 d-flex flex-column gap-4">

            <!-- BLOC AGENDA -->
            <div class="card-section card overflow-hidden">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold mb-0">
                        <i class="bi bi-calendar-check me-2 text-primary"></i>Vos prochaines séances
                    </h5>
                    <a href="<?= URLROOT ?>/seances" class="btn btn-sm btn-light border fw-bold rounded-pill px-3 hover-primary">
                        Tout voir <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0 mt-2">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-head-muted">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="ps-4 py-3">Date</th>
                                    <th class="py-3">Instance</th>
                                    <th class="py-3">Statut</th>
                                    <th class="text-end pe-4 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($prochainesSeances)): ?>
                                    <tr>
                                        <td colspan="4">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="bi bi-calendar-x"></i>
                                                </div>
                                                <h6>Aucune séance programmée</h6>
                                                <p>Votre calendrier est vide pour le moment.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($prochainesSeances as $seance):
                                        $dateObj = new DateTime($seance['date_seance']);
                                        $statutClass = 'bg-secondary';
                                        $statutTexte = ucfirst(str_replace('_', ' ', $seance['statut']));
                                        if ($seance['statut'] === 'date_fixee')         { $statutClass = 'bg-info text-dark';    $statutTexte = 'Date fixée'; }
                                        if ($seance['statut'] === 'odj_valide')         { $statutClass = 'bg-primary';           $statutTexte = 'ODJ Validé'; }
                                        if ($seance['statut'] === 'dossier_disponible') { $statutClass = 'bg-warning text-dark'; $statutTexte = 'Dossier Prêt'; }
                                        if ($seance['statut'] === 'en_cours')           { $statutClass = 'bg-danger';            $statutTexte = 'En cours'; }
                                    ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="fw-bold text-dark"><?= $dateObj->format('d/m/Y') ?></div>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1 opacity-50"></i><?= substr($seance['heure_debut'], 0, 5) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary mb-1"><?= htmlspecialchars($seance['instance_nom']) ?></div>
                                            <div class="small text-muted">
                                                <i class="bi bi-geo-alt me-1 opacity-50"></i><?= htmlspecialchars($seance['lieu'] ?: 'Lieu non défini') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-statut <?= $statutClass ?>"><?= mb_strtoupper($statutTexte) ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php $link = User::can('create_seances') ? 'edit' : 'view'; ?>
                                            <a href="<?= URLROOT ?>/seances/<?= $link ?>/<?= $seance['id'] ?>"
                                            class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3">
                                                Ouvrir
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- BLOC DOCUMENTS -->
            <div class="card-section card">
                <div class="card-header d-flex justify-content-between rounded-4 align-items-center">
                    <h5 class="card-title fw-bold mb-0">
                        <i class="bi bi-folder2-open me-2 text-info"></i>Derniers documents reçus
                    </h5>
                    <a href="<?= URLROOT ?>/documents" class="btn btn-sm btn-light border fw-bold rounded-pill px-3 hover-primary">
                        Tout voir <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-4 pt-3">
                    <?php if(empty($mesDocumentsPersonnels)): ?>
                        <div class="empty-state border rounded-4 bg-light py-4">
                            <div class="empty-state-icon">
                                <i class="bi bi-inbox"></i>
                            </div>
                            <p>Aucun document dans votre espace.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush rounded-3 border">
                            <?php foreach($mesDocumentsPersonnels as $doc):
                                $ext  = strtolower(pathinfo($doc['chemin_fichier'], PATHINFO_EXTENSION));
                                $icon = 'bi-file-earmark-text text-secondary';
                                if ($ext === 'pdf') $icon = 'bi-filetype-pdf text-danger';
                                elseif (in_array($ext, ['doc','docx','odt'])) $icon = 'bi-filetype-docx text-primary';
                                elseif (in_array($ext, ['xls','xlsx','ods'])) $icon = 'bi-filetype-xlsx text-success';
                            ?>
                            <a href="<?= URLROOT ?>/<?= htmlspecialchars($doc['chemin_fichier']) ?>" 
                               target="_blank" 
                               class="list-group-item list-group-item-action p-3 d-flex align-items-center">
                                <i class="bi <?= $icon ?> fs-4 me-3 opacity-75"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($doc['titre']) ?></div>
                                    <div class="text-muted small">
                                        <i class="bi bi-person me-1"></i>Transmis par <?= htmlspecialchars($doc['auteur']) ?>
                                        &nbsp;•&nbsp;<?= date('d/m/Y', strtotime($doc['created_at'])) ?>
                                    </div>
                                </div>
                                <i class="bi bi-download text-primary ms-2 opacity-75"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- COLONNE DROITE : ACCÈS RAPIDE -->
        <div class="col-lg-4">
            <div class="card-section card sticky-top rounded-4" style="top: 20px;">
                <div class="card-header pb-2">
                    <h6 class="fw-bold mb-0 section-label">
                        <i class="bi bi-lightning-charge me-1"></i>Accès rapide
                    </h6>
                </div>
                <div class="card-body p-4 pt-2">
                    <div class="d-flex flex-column gap-2">

                        <a href="<?= URLROOT ?>/seances" class="shortcut-btn btn btn-light border-0 p-3 rounded-3 text-start d-flex align-items-center shadow-sm">
                            <div class="bg-white p-2 rounded text-primary me-3 shadow-sm"><i class="bi bi-calendar-range fs-5"></i></div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;">Toutes les séances</div>
                                <div class="small text-muted" style="font-size: 0.75rem;">Planning et historique</div>
                            </div>
                        </a>

                        <a href="<?= URLROOT ?>/documents" class="shortcut-btn btn btn-light border-0 p-3 rounded-3 text-start d-flex align-items-center shadow-sm">
                            <div class="bg-white p-2 rounded text-info me-3 shadow-sm"><i class="bi bi-folder2-open fs-5"></i></div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;">Mes documents</div>
                                <div class="small text-muted" style="font-size: 0.75rem;">Consulter mes fichiers reçus</div>
                            </div>
                        </a>

                        <a href="<?= URLROOT ?>/compte" class="shortcut-btn btn btn-light border-0 p-3 rounded-3 text-start d-flex align-items-center shadow-sm">
                            <div class="bg-white p-2 rounded text-secondary me-3 shadow-sm"><i class="bi bi-person-badge fs-5"></i></div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;">Mon profil</div>
                                <div class="small text-muted" style="font-size: 0.75rem;">Modifier mes informations personnelles</div>
                            </div>
                        </a>

                        <?php if($canManageInstances): ?>
                        <a href="<?= URLROOT ?>/admin/instances" class="shortcut-btn btn btn-light border-0 p-3 rounded-3 text-start d-flex align-items-center shadow-sm">
                            <div class="bg-white p-2 rounded text-success me-3 shadow-sm"><i class="bi bi-diagram-3 fs-5"></i></div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;">Gérer les instances</div>
                                <div class="small text-muted" style="font-size: 0.75rem;">Membres et collèges</div>
                            </div>
                        </a>
                        <?php endif; ?>

                        <?php if($hasAdminAccess): ?>
                        <hr class="my-2 border-light">
                        <a href="<?= URLROOT ?>/admin" class="shortcut-btn shortcut-btn-admin btn bg-dark text-white border-0 p-3 rounded-3 text-start d-flex align-items-center shadow-sm">
                            <div class="bg-white bg-opacity-10 p-2 rounded text-white me-3"><i class="bi bi-shield-lock-fill fs-5"></i></div>
                            <div>
                                <div class="fw-bold" style="font-size: 0.9rem;">Panel administration</div>
                                <div class="small text-white-50" style="font-size: 0.75rem;">Gestion globale du système</div>
                            </div>
                        </a>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>
