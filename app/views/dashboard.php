<?php include __DIR__ . '/layouts/header.php'; ?>

<div class="container py-4">
    
    <!-- En-tête de bienvenue -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Bonjour, <?= htmlspecialchars($username) ?> 👋</h2>
            <p class="text-muted small mb-0">Bienvenue sur votre espace de gestion des instances.</p>
        </div>
        
        <!-- Bouton d'action rapide (Visible seulement pour Admin/RH) -->
        <?php if(\app\models\User::hasPower(50)): ?>
        <a href="<?= URLROOT ?>/seances/create" class="btn btn-primary fw-bold shadow-sm px-4">
            <i class="bi bi-calendar-plus me-2"></i> Planifier une séance
        </a>
        <?php endif; ?>
    </div>

    <!-- Widgets Statistiques -->
    <div class="row g-3 mb-4">
        <!-- Widget 1 : Prochaines Séances -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-calendar-event text-primary fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">À venir</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?= count($prochainesSeances) ?></h3>
                        <span class="small text-muted">réunions programmées</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widget 2 : Séances à convoquer -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-envelope-exclamation text-warning fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">À convoquer</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?= $nbSeancesPlanifiees ?></h3>
                        <span class="small text-muted">en attente d'envoi</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widget 3 : Instances actives -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-diagram-3 text-success fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0 small text-uppercase fw-bold">Instances</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?= $nbInstances ?></h3>
                        <span class="small text-muted">configurées</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Colonne Gauche : Agenda -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="card-title fw-bold mb-0"><i class="bi bi-calendar-week me-2 text-primary"></i>Agenda des prochaines séances</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase text-muted">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Instance</th>
                                    <th>Lieu</th>
                                    <th>Statut</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($prochainesSeances)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-calendar-x display-4 d-block mb-3 opacity-25"></i>
                                            Aucune séance programmée pour le moment.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($prochainesSeances as $seance): 
                                        $dateObj = new DateTime($seance['date_seance']);
                                        $badges = [
                                            'planifiee' => 'bg-secondary',
                                            'convoquee' => 'bg-warning text-dark',
                                            'en_cours'  => 'bg-success',
                                            'close'     => 'bg-dark',
                                            'annulee'   => 'bg-danger'
                                        ];
                                        $badgeClass = $badges[$seance['statut']] ?? 'bg-secondary';
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?= $dateObj->format('d/m/Y') ?></div>
                                            <small class="text-muted"><?= substr($seance['heure_debut'], 0, 5) ?></small>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-primary"><?= htmlspecialchars($seance['instance_nom']) ?></span>
                                        </td>
                                        <td class="small text-muted">
                                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($seance['lieu'] ?: 'Non défini') ?>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill <?= $badgeClass ?>"><?= ucfirst($seance['statut']) ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="<?= URLROOT ?>/seances/gestion/<?= $seance['id'] ?>" class="btn btn-sm btn-outline-primary border-0 bg-primary bg-opacity-10 text-primary fw-bold">
                                                Gérer <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if(!empty($prochainesSeances)): ?>
                <div class="card-footer bg-white border-0 text-center py-3">
                    <a href="<?= URLROOT ?>/seances" class="text-decoration-none fw-bold small text-muted">Voir tout le calendrier <i class="bi bi-arrow-right"></i></a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Colonne Droite : Outils Rapides -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 text-uppercase small text-muted">Accès Rapide</h6>
                    <div class="d-grid gap-2">
                        <a href="<?= URLROOT ?>/seances/archives" class="btn btn-light text-start border-0 py-2 small">
                            <i class="bi bi-archive me-2 text-muted"></i> Consulter les archives (PV)
                        </a>
                        <a href="<?= URLROOT ?>/compte" class="btn btn-light text-start border-0 py-2 small">
                            <i class="bi bi-person-gear me-2 text-muted"></i> Mon profil
                        </a>
                        
                        <?php if(\app\models\User::hasPower(100)): ?>
                        <hr class="my-1 text-muted opacity-25">
                        <a href="<?= URLROOT ?>/admin/instances" class="btn btn-outline-dark text-start border-0 py-2 shadow-sm small">
                            <i class="bi bi-gear-fill me-2"></i> Configurer les instances
                        </a>
                        <a href="<?= URLROOT ?>/admin/users" class="btn btn-outline-dark text-start border-0 py-2 shadow-sm small">
                            <i class="bi bi-people-fill me-2"></i> Gérer les utilisateurs
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Carte Info (Optionnelle) -->
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title fw-bold"><i class="bi bi-info-circle me-2"></i>Besoin d'aide ?</h5>
                    <p class="card-text small opacity-75">Pour planifier une nouvelle instance, assurez-vous d'abord que les membres sont bien configurés dans la section administration.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>
