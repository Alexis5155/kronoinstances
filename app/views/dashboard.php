<?php 
include __DIR__ . '/layouts/header.php'; 
use app\models\User;

// Détermination des droits
$canManageInstances = User::can('manage_instances');
$canManageConvocations = User::can('manage_convocations');
$hasAdminAccess = User::can('view_logs') || User::can('manage_users') || User::can('manage_system');

// Le profil a-t-il des droits de gestion métier ?
$isManager = $canManageInstances || $canManageConvocations || User::can('create_seances');
?>

<div class="container py-4">
    
    <!-- En-tête de bienvenue moderne -->
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div class="d-flex align-items-center">
            <div class="avatar-circle rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm me-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                <?= strtoupper(substr($_SESSION['prenom'] ?? $_SESSION['username'] ?? 'U', 0, 1)) ?>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Bonjour, <?= htmlspecialchars($_SESSION['prenom'] ?? $_SESSION['username']) ?> 👋</h2>
                <p class="text-muted small mb-0">Bienvenue sur votre espace personnel KronoInstances.</p>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            <?php if(User::can('create_seances')): ?>
            <a href="<?= URLROOT ?>/seances" class="btn btn-primary fw-bold shadow-sm px-4">
                <i class="bi bi-calendar-plus me-2"></i> Planifier une séance
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- WIDGETS STATISTIQUES (S'ADAPTENT AU PROFIL) -->
    <div class="row g-4 mb-5">
        
        <!-- Widget 1 : Toujours visible (Prochaines séances) -->
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
                        <h6 class="text-muted mb-0 fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">À venir</h6>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark display-6"><?= count($prochainesSeances) ?></h2>
                    <span class="small text-muted fw-medium">réunions programmées</span>
                </div>
            </div>
        </div>

        <?php if($isManager): ?>
        <!-- WIDGETS GESTIONNAIRE -->
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
                        <h6 class="text-muted mb-0 fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">À convoquer</h6>
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
                        <h6 class="text-muted mb-0 fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">Instances actives</h6>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark display-6"><?= $nbInstances ?></h2>
                    <span class="small text-muted fw-medium">configurées sur la plateforme</span>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- WIDGETS LECTEUR -->
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
                        <h6 class="text-muted mb-0 fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">Mes mandats</h6>
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
                        <h6 class="text-muted mb-0 fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">Espace Documents</h6>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark display-6"><?= $nbDocuments ?></h2>
                    <span class="small text-muted fw-medium">documents disponibles</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <!-- Colonne Gauche : Agenda & Documents -->
        <div class="col-lg-8 d-flex flex-column gap-4">
            
            <!-- BLOC : AGENDA -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white p-4 border-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title fw-bold mb-0"><i class="bi bi-calendar-check me-2 text-primary"></i>Vos prochaines séances</h5>
                        <a href="<?= URLROOT ?>/seances" class="btn btn-sm btn-light border text-dark fw-bold rounded-pill px-3 hover-primary">
                            Tout voir <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0 mt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
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
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                                <i class="bi bi-calendar-x fs-2 opacity-50"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark">Aucune séance programmée</h6>
                                            <p class="small mb-0">Votre calendrier est vide pour le moment.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($prochainesSeances as $seance): 
                                        $dateObj = new DateTime($seance['date_seance']);
                                        
                                        $statutClass = 'bg-secondary';
                                        $statutTexte = ucfirst(str_replace('_', ' ', $seance['statut']));
                                        
                                        if ($seance['statut'] === 'date_fixee') { $statutClass = 'bg-info text-dark'; $statutTexte = 'Date fixée'; }
                                        if ($seance['statut'] === 'odj_valide') { $statutClass = 'bg-primary'; $statutTexte = 'ODJ Validé'; }
                                        if ($seance['statut'] === 'dossier_disponible') { $statutClass = 'bg-warning text-dark'; $statutTexte = 'Dossier Prêt'; }
                                        if ($seance['statut'] === 'en_cours') { $statutClass = 'bg-danger'; $statutTexte = 'En cours'; }
                                    ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="fw-bold text-dark"><?= $dateObj->format('d/m/Y') ?></div>
                                            <small class="text-muted fw-medium"><i class="bi bi-clock me-1 opacity-50"></i><?= substr($seance['heure_debut'], 0, 5) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary mb-1"><?= htmlspecialchars($seance['instance_nom']) ?></div>
                                            <div class="small text-muted">
                                                <i class="bi bi-geo-alt me-1 opacity-50"></i><?= htmlspecialchars($seance['lieu'] ?: 'Lieu non défini') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?= $statutClass ?> px-2 py-1 fw-bold" style="font-size: 0.7rem;"><?= mb_strtoupper($statutTexte) ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php $link = User::can('create_seances') ? 'edit' : 'view'; ?>
                                            <a href="<?= URLROOT ?>/seances/<?= $link ?>/<?= $seance['id'] ?>" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3 shadow-sm">
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

            <!-- BLOC : DERNIERS DOCUMENTS DE L'ESPACE PERSONNEL -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white p-4 border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold mb-0"><i class="bi bi-folder2-open me-2 text-info"></i>Derniers documents reçus</h5>
                    <a href="<?= URLROOT ?>/documents" class="btn btn-sm btn-light border text-dark fw-bold rounded-pill px-3 hover-info">
                        Tout voir <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-4 pt-3">
                    <?php if(empty($mesDocumentsPersonnels)): ?>
                        <div class="text-center py-4 text-muted border rounded-4 bg-light">
                            <i class="bi bi-inbox fs-3 d-block mb-2 opacity-25"></i>
                            <p class="small mb-0">Aucun document dans votre espace.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush rounded-3 border">
                            <?php foreach($mesDocumentsPersonnels as $doc): 
                                // Icône dynamique selon l'extension
                                $ext = strtolower(pathinfo($doc['chemin_fichier'], PATHINFO_EXTENSION));
                                $icon = 'bi-file-earmark-text text-secondary';
                                if ($ext === 'pdf') $icon = 'bi-filetype-pdf text-danger';
                                elseif (in_array($ext, ['doc', 'docx', 'odt'])) $icon = 'bi-filetype-docx text-primary';
                                elseif (in_array($ext, ['xls', 'xlsx', 'ods'])) $icon = 'bi-filetype-xlsx text-success';
                            ?>
                            <a href="<?= URLROOT ?>/<?= htmlspecialchars($doc['chemin_fichier']) ?>" target="_blank" class="list-group-item list-group-item-action p-3 d-flex align-items-center">
                                <i class="bi <?= $icon ?> fs-4 me-3 opacity-75"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($doc['titre']) ?></div>
                                    <div class="text-muted small">
                                        <i class="bi bi-person me-1"></i>Transmis par <?= htmlspecialchars($doc['auteur']) ?> • <?= date('d/m/Y', strtotime($doc['created_at'])) ?>
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

        <!-- Colonne Droite : Outils Rapides -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4 rounded-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-white p-4 pb-2 border-0">
                    <h6 class="fw-bold mb-0 text-uppercase small text-muted" style="letter-spacing: 0.5px;"><i class="bi bi-lightning-charge me-1"></i> Accès Rapide</h6>
                </div>
                <div class="card-body p-4 pt-2">
                    <div class="d-flex flex-column gap-2">
                        
                        <!-- Raccourci vers les séances -->
                        <a href="<?= URLROOT ?>/seances" class="btn btn-light border-0 p-3 rounded-3 text-start d-flex align-items-center shadow-sm shortcut-btn">
                            <div class="bg-white p-2 rounded text-primary me-3 shadow-sm"><i class="bi bi-calendar-range fs-5"></i></div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;">Toutes les séances</div>
                                <div class="small text-muted" style="font-size: 0.75rem;">Planning et historique</div>
                            </div>
                        </a>
                        
                        <!-- Raccourci Espace Documents -->
                        <a href="<?= URLROOT ?>/documents" class="btn btn-light border-0 p-3 rounded-3 text-start d-flex align-items-center shadow-sm shortcut-btn">
                            <div class="bg-white p-2 rounded text-info me-3 shadow-sm"><i class="bi bi-folder2-open fs-5"></i></div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;">Mes documents</div>
                                <div class="small text-muted" style="font-size: 0.75rem;">Consulter mes fichiers reçus</div>
                            </div>
                        </a>

                        <!-- Raccourci vers le profil -->
                        <a href="<?= URLROOT ?>/compte" class="btn btn-light border-0 p-3 rounded-3 text-start d-flex align-items-center shadow-sm shortcut-btn">
                            <div class="bg-white p-2 rounded text-secondary me-3 shadow-sm"><i class="bi bi-person-badge fs-5"></i></div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;">Mon profil</div>
                                <div class="small text-muted" style="font-size: 0.75rem;">Modifier mes informations personnelles</div>
                            </div>
                        </a>

                        <!-- Raccourci Instances (Conditionnel) -->
                        <?php if($canManageInstances): ?>
                        <a href="<?= URLROOT ?>/admin/instances" class="btn btn-light border-0 p-3 rounded-3 text-start d-flex align-items-center shadow-sm shortcut-btn mt-1">
                            <div class="bg-white p-2 rounded text-success me-3 shadow-sm"><i class="bi bi-diagram-3 fs-5"></i></div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;">Gérer les instances</div>
                                <div class="small text-muted" style="font-size: 0.75rem;">Membres et collèges</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        
                        <!-- Raccourci Panel Admin (Conditionnel) -->
                        <?php if($hasAdminAccess): ?>
                        <hr class="my-2 border-light">
                        <a href="<?= URLROOT ?>/admin" class="btn bg-dark text-white border-0 p-3 rounded-3 text-start d-flex align-items-center shadow-sm btn-admin-hover">
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

<style>
    /* Empêche les icônes de fond des widgets de passer devant le texte sur petits écrans */
    .stat-card { z-index: 1; }
    .stat-icon { z-index: -1; right: -10px !important; top: 10px !important; }
    @media (max-width: 1200px) {
        .stat-icon { opacity: 0.05 !important; }
    }

    .hover-primary { transition: all 0.2s; }
    .hover-primary:hover { background-color: #0d6efd !important; color: white !important; }
    .hover-info { transition: all 0.2s; }
    .hover-info:hover { background-color: #0dcaf0 !important; color: white !important; }
    
    .shortcut-btn { transition: transform 0.2s, background-color 0.2s; }
    .shortcut-btn:hover { background-color: #f8f9fa !important; transform: translateX(5px); }

    .btn-admin-hover { transition: background-color 0.2s; }
    .btn-admin-hover:hover { background-color: #1a1e21 !important; }
</style>

<?php include __DIR__ . '/layouts/footer.php'; ?>
