<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Séances & Réunions</h2>
            <p class="text-muted small mb-0">Planification et gestion des réunions paritaires</p>
        </div>
        <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createSeanceModal">
            <i class="bi bi-calendar-plus me-2"></i>Planifier une séance
        </button>
    </div>

    <!-- LISTE DES SÉANCES -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small">
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
                        <?php if (empty($seances)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-25"></i>
                                    Aucune séance n'est planifiée pour le moment.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($seances as $seance): 
                                // Formatage de la date
                                $dateObj = new DateTime($seance['date_seance'] . ' ' . $seance['heure_debut']);
                                $dateFr = $dateObj->format('d/m/Y');
                                $heureFr = $dateObj->format('H\hi');
                                
                                // Badge de statut
                                $statutClass = 'bg-secondary';
                                $statutTexte = ucfirst($seance['statut']);
                                if ($seance['statut'] === 'planifiee') { $statutClass = 'bg-info text-dark'; $statutTexte = 'Planifiée'; }
                                if ($seance['statut'] === 'en cours') { $statutClass = 'bg-warning text-dark'; $statutTexte = 'En cours'; }
                                if ($seance['statut'] === 'terminee') { $statutClass = 'bg-success'; $statutTexte = 'Terminée'; }
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-primary"><i class="bi bi-calendar-event me-2"></i><?= $dateFr ?></div>
                                    <div class="small text-muted"><i class="bi bi-clock me-2"></i><?= $heureFr ?></div>
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($seance['instance_nom']) ?></td>
                                <td>
                                    <?php if(!empty($seance['lieu'])): ?>
                                        <i class="bi bi-geo-alt me-1 text-muted"></i> <?= htmlspecialchars($seance['lieu']) ?>
                                    <?php else: ?>
                                        <em class="text-muted small">Non défini</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= $seance['nb_points'] ?> point(s)
                                    </span>
                                </td>
                                <td><span class="badge <?= $statutClass ?>"><?= $statutTexte ?></span></td>
                                <td class="text-end pe-4">
                                    <!-- Lien vers le futur détail de la séance -->
                                    <a href="<?= URLROOT ?>/seances/view/<?= $seance['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Ouvrir
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
</div>

<!-- ============================================================ -->
<!-- MODALE : PLANIFIER UNE SÉANCE                                -->
<!-- ============================================================ -->
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
