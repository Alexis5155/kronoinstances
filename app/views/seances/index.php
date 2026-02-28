<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-0">Séances & Réunions</h2>
            <p class="text-muted small mb-0">Planification et gestion des réunions paritaires</p>
        </div>
        <button type="button" class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#createSeanceModal">
            <i class="bi bi-calendar-plus me-2"></i>Planifier une séance
        </button>
    </div>

    <!-- BARRE DE RECHERCHE / FILTRES -->
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="<?= URLROOT ?>/seances" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label text-uppercase text-muted fw-bold small mb-1" style="font-size: 0.65rem;">Filtrer par instance</label>
                    <select name="search_instance" class="form-select form-select-sm">
                        <option value="">Toutes les instances</option>
                        <?php foreach ($instances as $inst): ?>
                            <option value="<?= $inst['id'] ?>" <?= ($search_instance ?? '') == $inst['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($inst['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-uppercase text-muted fw-bold small mb-1" style="font-size: 0.65rem;">Filtrer par date</label>
                    <input type="date" name="search_date" class="form-control form-control-sm" value="<?= htmlspecialchars($search_date ?? '') ?>">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold">Rechercher</button>
                    <a href="<?= URLROOT ?>/seances" class="btn btn-sm btn-outline-secondary" title="Réinitialiser les filtres">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- ONGLETS DE NAVIGATION -->
    <ul class="nav nav-pills mb-3 gap-2" id="seancesTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4 fw-bold shadow-sm" id="futures-tab" data-bs-toggle="pill" data-bs-target="#futures" type="button" role="tab">
                <i class="bi bi-calendar-event me-2"></i>À venir (<?= count($seances_futures) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 fw-bold text-muted border border-light" id="passees-tab" data-bs-toggle="pill" data-bs-target="#passees" type="button" role="tab" style="background-color: white;">
                <i class="bi bi-archive me-2"></i>Historique (<?= count($seances_passees) ?>)
            </button>
        </li>
    </ul>

    <!-- CONTENU DES ONGLETS -->
    <div class="tab-content" id="seancesTabContent">
        
        <!-- ONGLET : SÉANCES À VENIR -->
        <div class="tab-pane fade show active" id="futures" role="tabpanel">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                            <tr>
                                <th class="ps-4">Date & Heure</th>
                                <th>Instance</th>
                                <th>Lieu</th>
                                <th>Points à l'ODJ</th>
                                <th>Statut</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($seances_futures)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-x display-4 d-block mb-3 opacity-25"></i>
                                        <p class="mb-0">Aucune séance programmée pour le moment.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($seances_futures as $seance): 
                                    renderSeanceRow($seance);
                                endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ONGLET : SÉANCES PASSÉES -->
        <div class="tab-pane fade" id="passees" role="tabpanel">
            <div class="card border-0 shadow-sm overflow-hidden opacity-75 hover-opacity-100 transition-opacity">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                            <tr>
                                <th class="ps-4">Date & Heure</th>
                                <th>Instance</th>
                                <th>Lieu</th>
                                <th>Points à l'ODJ</th>
                                <th>Statut</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($seances_passees)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-archive fs-1 d-block mb-3 opacity-25"></i>
                                        <p class="mb-0">Aucun historique disponible.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($seances_passees as $seance): 
                                    renderSeanceRow($seance);
                                endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php 
// Fonction d'aide pour ne pas dupliquer le code HTML d'une ligne du tableau
function renderSeanceRow($seance) {
    $dateObj = new DateTime($seance['date_seance'] . ' ' . $seance['heure_debut']);
    $dateFr = $dateObj->format('d/m/Y');
    $heureFr = $dateObj->format('H\hi');
    
    // Badge de statut
    $statutClass = 'bg-secondary';
    $statutTexte = ucfirst(str_replace('_', ' ', $seance['statut']));
    
    if ($seance['statut'] === 'brouillon') { $statutClass = 'bg-secondary'; }
    if ($seance['statut'] === 'date_fixee') { $statutClass = 'bg-info text-dark'; $statutTexte = 'Date fixée'; }
    if ($seance['statut'] === 'odj_valide') { $statutClass = 'bg-primary'; $statutTexte = 'ODJ Validé'; }
    if ($seance['statut'] === 'dossier_disponible') { $statutClass = 'bg-warning text-dark'; $statutTexte = 'Dossier Prêt'; }
    if ($seance['statut'] === 'en_cours') { $statutClass = 'bg-danger'; $statutTexte = 'En cours'; }
    if ($seance['statut'] === 'terminee') { $statutClass = 'bg-success'; $statutTexte = 'Terminée'; }
?>
    <tr>
        <td class="ps-4">
            <div class="fw-bold text-dark"><i class="bi bi-calendar-event me-2 text-primary opacity-75"></i><?= $dateFr ?></div>
            <div class="small text-muted"><i class="bi bi-clock me-2 opacity-50"></i><?= $heureFr ?></div>
        </td>
        <td>
            <div class="fw-bold text-dark"><?= htmlspecialchars($seance['instance_nom']) ?></div>
        </td>
        <td>
            <?php if(!empty($seance['lieu'])): ?>
                <div class="small text-muted"><i class="bi bi-geo-alt me-1 opacity-50"></i><?= htmlspecialchars($seance['lieu']) ?></div>
            <?php else: ?>
                <em class="text-muted small opacity-50">Non défini</em>
            <?php endif; ?>
        </td>
        <td>
            <span class="badge bg-light text-dark border fw-normal px-2 py-1">
                <?= $seance['nb_points'] ?> point(s)
            </span>
        </td>
        <td><span class="badge <?= $statutClass ?> fw-bold px-2 py-1" style="font-size: 0.65rem; letter-spacing: 0.5px;"><?= mb_strtoupper($statutTexte) ?></span></td>
        <td class="text-end pe-4">
            <a href="<?= URLROOT ?>/seances/view/<?= $seance['id'] ?>" class="btn btn-sm btn-light border text-primary fw-bold shadow-sm" style="border-radius: 8px;">
                Ouvrir <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </td>
    </tr>
<?php } ?>

<style>
    .transition-opacity { transition: opacity 0.3s ease; }
    .hover-opacity-100:hover { opacity: 1 !important; }
    .nav-pills .nav-link.active {
        background-color: #212529 !important;
        color: white !important;
    }
</style>

<!-- MODALE : PLANIFIER UNE SÉANCE -->
<div class="modal fade" id="createSeanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= URLROOT ?>/seances/create" method="POST">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-calendar-plus me-2 text-primary"></i>Planifier une séance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Instance concernée <span class="text-danger">*</span></label>
                        <select name="instance_id" class="form-select" required>
                            <option value="">-- Sélectionner une instance --</option>
                            <?php foreach ($instances as $inst): ?>
                                <option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date_seance" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Heure de début <span class="text-danger">*</span></label>
                            <input type="time" name="heure_debut" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Lieu de la réunion</label>
                        <input type="text" name="lieu" class="form-control" placeholder="Ex: Salle du Conseil, Visioconférence...">
                    </div>

                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="bi bi-check-lg me-1"></i>Planifier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>