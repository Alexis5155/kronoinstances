<!-- PHASE DE FINALISATION -->
<div class="row g-4">
    <div class="col-lg-8">
        <!-- UPLOAD DU PROCÈS-VERBAL -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="bi bi-file-earmark-check me-2 text-success"></i>Procès-Verbal de la séance</h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($seance['proces_verbal_path'])): ?>
                    <div class="alert alert-success d-flex align-items-center mb-0 shadow-sm border-0">
                        <i class="bi bi-check-circle-fill fs-3 me-3 text-success"></i>
                        <div>
                            <div class="fw-bold text-dark">Le Procès-Verbal a été déposé avec succès.</div>
                            <a href="<?= URLROOT ?>/<?= htmlspecialchars($seance['proces_verbal_path']) ?>" target="_blank" class="small fw-bold text-success text-decoration-none">
                                <i class="bi bi-download me-1"></i> Consulter le document
                            </a>
                        </div>
                        <?php if ($seance['statut'] === 'finalisation'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/deletePv/<?= $seance['id'] ?>', 'Supprimer définitivement ce PV ?')" class="btn btn-sm btn-outline-danger ms-auto px-2">
                                <i class="bi bi-trash"></i> Retirer
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning border-0 mb-4 shadow-sm">
                        <i class="bi bi-info-circle me-2"></i> Aucun procès-verbal n'a été rattaché à cette séance.
                    </div>
                    
                    <?php if ($seance['statut'] === 'finalisation'): ?>
                        <form action="<?= URLROOT ?>/seances/uploadPv/<?= $seance['id'] ?>" method="POST" enctype="multipart/form-data" class="bg-light p-3 rounded border">
                            <label class="form-label small fw-bold text-muted text-uppercase">Importer le Procès-Verbal signé (PDF)</label>
                            <div class="d-flex gap-2">
                                <input type="file" name="pv_file" class="form-control" accept=".pdf" required>
                                <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-upload me-1"></i> Téléverser</button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- RÉCAPITULATIF POUR LE SECRÉTARIAT -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="bi bi-list-check me-2 text-primary"></i>Récapitulatif des points traités</h5>
            </div>
            <div class="card-body p-4">
                <ul class="list-group list-group-flush">
                    <?php if(empty($points)): ?>
                        <li class="list-group-item text-muted small">Aucun point n'a été enregistré.</li>
                    <?php else: ?>
                        <?php foreach ($points as $index => $pt): ?>
                            <li class="list-group-item px-0 py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="fw-bold text-dark"><?= ($index + 1) ?>. <?= htmlspecialchars($pt['titre']) ?></div>
                                    <span class="badge bg-secondary opacity-75 ms-2"><?= ucfirst($pt['type_point']) ?></span>
                                </div>
                                <?php if (!empty($pt['debats'])): ?>
                                    <div class="small text-muted mt-2 border-start border-3 border-info ps-2">
                                        <em>Note prise :</em> <?= strip_tags(mb_strimwidth($pt['debats'], 0, 150, '...')) ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- STATISTIQUES LATÉRALES -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 px-3 pb-2 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-muted text-uppercase">Statistiques de Séance</h6>
            </div>
            <div class="card-body px-3 pb-3">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item px-0 d-flex justify-content-between border-0 pb-2">
                        <span class="text-muted">Quorum</span>
                        <span class="badge <?= $seance['quorum_atteint'] ? 'bg-success' : 'bg-danger' ?>">
                            <?= $seance['quorum_atteint'] ? 'Atteint' : 'Non atteint' ?>
                        </span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between border-0 py-2">
                        <span class="text-muted">Membres rattachés</span>
                        <span class="fw-bold"><?= count($membres) ?></span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between border-0 pt-2">
                        <span class="text-muted">Points à l'ordre du jour</span>
                        <span class="fw-bold"><?= count($points) ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
