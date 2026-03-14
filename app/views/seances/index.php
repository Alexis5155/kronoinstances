<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-4">

    <!-- EN-TÊTE -->
    <div class="page-header mb-4">
        <div class="d-flex align-items-center">
            <div class="page-header-avatar bg-primary text-white shadow-sm me-3">
                <i class="bi bi-calendar-range"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Liste des séances</h2>
                <p class="text-muted small mb-0">Planification et gestion des réunions paritaires</p>
            </div>
        </div>
        <button type="button" class="btn btn-primary fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createSeanceModal">
            <i class="bi bi-calendar-plus me-2"></i>Planifier une séance
        </button>
    </div>

    <!-- FILTRES -->
    <div class="card-section card mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= URLROOT ?>/seances" class="row g-2 align-items-end">

                <div class="col-md-3">
                    <label class="section-label">Période</label>
                    <select name="search_periode" class="form-select form-select-sm">
                        <option value=""      <?= ($search_periode ?? '') === ''        ? 'selected' : '' ?>>Toutes les séances</option>
                        <option value="futur" <?= ($search_periode ?? '') === 'futur'   ? 'selected' : '' ?>>À venir</option>
                        <option value="passe" <?= ($search_periode ?? '') === 'passe'   ? 'selected' : '' ?>>Historique</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="section-label">Instance</label>
                    <select name="search_instance" class="form-select form-select-sm">
                        <option value="">Toutes les instances</option>
                        <?php foreach ($instances as $inst): ?>
                            <option value="<?= $inst['id'] ?>" <?= ($search_instance ?? '') == $inst['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($inst['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="section-label">Du</label>
                    <input type="date" name="search_date_debut" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($search_date_debut ?? '') ?>">
                </div>

                <div class="col-md-2">
                    <label class="section-label">Au</label>
                    <input type="date" name="search_date_fin" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($search_date_fin ?? '') ?>">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-dark fw-bold w-100 rounded-pill">
                        <i class="bi bi-search me-1"></i>Filtrer
                    </button>
                    <a href="<?= URLROOT ?>/seances" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Réinitialiser">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>

            </form>
        </div>
    </div>

    <!-- TABLEAU UNIFIÉ -->
    <div class="card-section card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-head-muted table-fixed" style="table-layout: fixed; min-width: 700px;">
                <thead class="text-muted border-bottom">
                    <tr>
                        <th class="ps-4 py-3" style="width: 160px;">Date & Heure</th>
                        <th class="py-3" style="width: 200px;">Instance</th>
                        <th class="py-3" style="width: 180px;">Lieu</th>
                        <th class="py-3" style="width: 110px;">Points ODJ</th>
                        <th class="py-3" style="width: 130px;">Statut</th>
                        <th class="text-end pe-4 py-3" style="width: 110px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($seances)): ?>
                        <tr><td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                                <h6>Aucune séance trouvée</h6>
                                <p>Modifiez les filtres ou planifiez une nouvelle réunion.</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($seances as $seance): renderSeanceRow($seance); endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php
function renderSeanceRow($seance) {
    $dateObj    = new DateTime($seance['date_seance'] . ' ' . $seance['heure_debut']);
    $dateFr     = $dateObj->format('d/m/Y');
    $heureFr    = $dateObj->format('H\hi');

    $statutClass = 'bg-secondary';
    $statutTexte = ucfirst(str_replace('_', ' ', $seance['statut']));
    if ($seance['statut'] === 'brouillon')          { $statutClass = 'bg-secondary'; }
    if ($seance['statut'] === 'date_fixee')         { $statutClass = 'bg-info text-dark';    $statutTexte = 'Date fixée'; }
    if ($seance['statut'] === 'odj_valide')         { $statutClass = 'bg-primary';           $statutTexte = 'ODJ Validé'; }
    if ($seance['statut'] === 'dossier_disponible') { $statutClass = 'bg-warning text-dark'; $statutTexte = 'Dossier Prêt'; }
    if ($seance['statut'] === 'en_cours')           { $statutClass = 'bg-danger';            $statutTexte = 'En cours'; }
    if ($seance['statut'] === 'terminee')           { $statutClass = 'bg-success';           $statutTexte = 'Terminée'; }
?>
    <tr>
        <td class="ps-4 py-3">
            <div class="fw-bold text-dark">
                <i class="bi bi-calendar-event me-2 text-primary opacity-75"></i><?= $dateFr ?>
            </div>
            <div class="small text-muted">
                <i class="bi bi-clock me-2 opacity-50"></i><?= $heureFr ?>
            </div>
        </td>
        <td class="py-3">
            <div class="fw-bold text-dark"><?= htmlspecialchars($seance['instance_nom']) ?></div>
        </td>
        <td class="py-3">
            <?php if (!empty($seance['lieu'])): ?>
                <div class="small text-muted">
                    <i class="bi bi-geo-alt me-1 opacity-50"></i><?= htmlspecialchars($seance['lieu']) ?>
                </div>
            <?php else: ?>
                <span class="small text-muted opacity-50 fst-italic">Non défini</span>
            <?php endif; ?>
        </td>
        <td class="py-3">
            <span class="badge bg-light text-dark border fw-normal px-2 py-1">
                <?= $seance['nb_points'] ?> point<?= $seance['nb_points'] > 1 ? 's' : '' ?>
            </span>
        </td>
        <td class="py-3">
            <span class="badge badge-statut <?= $statutClass ?>"><?= mb_strtoupper($statutTexte) ?></span>
        </td>
        <td class="text-end pe-4 py-3">
            <a href="<?= URLROOT ?>/seances/view/<?= $seance['id'] ?>" class="btn btn-sm btn-light border text-primary fw-bold rounded-pill px-3">
                Ouvrir <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </td>
    </tr>
<?php } ?>

<!-- MODALE : PLANIFIER UNE SÉANCE -->
<div class="modal fade" id="createSeanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="<?= URLROOT ?>/seances/create" method="POST">
                <div class="modal-header bg-light border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-calendar-plus me-2 text-primary"></i>Planifier une séance
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Instance concernée <span class="text-danger">*</span></label>
                        <select name="instance_id" class="form-select" required>
                            <option value="">— Sélectionner une instance —</option>
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
                        <input type="text" name="lieu" class="form-control" placeholder="Ex : Salle du Conseil, Visioconférence...">
                    </div>

                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">
                        <i class="bi bi-check-lg me-1"></i>Planifier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
