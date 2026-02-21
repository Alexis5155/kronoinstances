<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php
$dateObj  = new DateTime($seance['date_seance'] . ' ' . $seance['heure_debut']);
$dateFr   = $dateObj->format('l d F Y');
$heureFr  = $dateObj->format('H\hi');

$statutCfg = [
    'planifiee' => ['label' => 'Planifiée',  'class' => 'bg-info text-dark',    'icon' => 'bi-calendar-check'],
    'en_cours'  => ['label' => 'En cours',   'class' => 'bg-warning text-dark', 'icon' => 'bi-play-circle-fill'],
    'terminee'  => ['label' => 'Terminée',   'class' => 'bg-success',           'icon' => 'bi-check-circle-fill'],
];
$s = $statutCfg[$seance['statut']] ?? ['label' => ucfirst($seance['statut']), 'class' => 'bg-secondary', 'icon' => 'bi-circle'];

$typeCfg = [
    'information'  => ['label' => 'Information',  'class' => 'bg-info text-dark'],
    'deliberation' => ['label' => 'Délibération', 'class' => 'bg-primary'],
    'vote'         => ['label' => 'Vote',          'class' => 'bg-danger'],
    'divers'       => ['label' => 'Divers',        'class' => 'bg-secondary'],
];
?>

<div class="container py-4">

    <!-- EN-TÊTE SÉANCE -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <a href="<?= URLROOT ?>/seances" class="text-muted small text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i>Retour aux séances
                    </a>
                    <h3 class="fw-bold mt-2 mb-1 text-primary">
                        <i class="bi bi-building me-2"></i><?= htmlspecialchars($seance['instance_nom']) ?>
                    </h3>
                    <p class="mb-0 text-muted">
                        <i class="bi bi-calendar-event me-2"></i><?= ucfirst($dateFr) ?> à <?= $heureFr ?>
                        <?php if (!empty($seance['lieu'])): ?>
                            &nbsp;·&nbsp;<i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($seance['lieu']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge <?= $s['class'] ?> fs-6 px-3 py-2">
                        <i class="bi <?= $s['icon'] ?> me-1"></i><?= $s['label'] ?>
                    </span>
                    
                    <?php if ($seance['statut'] === 'planifiee'): ?>
                        <a href="<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=en_cours" 
                           class="btn btn-warning btn-sm fw-bold"
                           onclick="return confirm('Démarrer la séance maintenant ? Vous serez redirigé vers le bureau en direct.')">
                            <i class="bi bi-play-fill me-1"></i>Démarrer la séance
                        </a>
                        
                    <?php elseif ($seance['statut'] === 'en_cours'): ?>
                        <!-- NOUVEAU BOUTON : Accès au Live -->
                        <a href="<?= URLROOT ?>/seances/live/<?= $seance['id'] ?>" class="btn btn-danger btn-sm fw-bold">
                            <i class="bi bi-record-circle-fill me-1" style="animation: blink 2s infinite;"></i>Accéder au Live
                        </a>
                        
                        <a href="<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=terminee" class="btn btn-success btn-sm fw-bold">
                            <i class="bi bi-stop-fill me-1"></i>Clôturer la séance
                        </a>
                    <?php endif; ?>

                    <!-- Supprimer la séance (seulement si planifiée) -->
                    <?php if ($seance['statut'] === 'planifiee'): ?>
                        <a href="<?= URLROOT ?>/seances/delete/<?= $seance['id'] ?>"
                           class="btn btn-outline-danger btn-sm"
                           onclick="return confirm('Supprimer définitivement cette séance et tous ses points ODJ ?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Petit ajout CSS pour faire clignoter le point rouge du Live -->
                <style>
                    @keyframes blink { 50% { opacity: 0.4; } }
                </style>

            </div>

            <!-- QUORUM -->
            <?php if ($seance['quorum_requis']): ?>
            <div class="mt-3 pt-3 border-top d-flex align-items-center gap-3 flex-wrap">
                <span class="small text-muted fw-bold">Quorum requis : <?= $seance['quorum_requis'] ?> membre(s)</span>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="quorumCheck" 
                           <?= $seance['quorum_atteint'] ? 'checked' : '' ?>
                           onchange="toggleQuorum(this, <?= $seance['id'] ?>)">
                    <label class="form-check-label small fw-bold <?= $seance['quorum_atteint'] ? 'text-success' : 'text-muted' ?>" for="quorumCheck">
                        Quorum <?= $seance['quorum_atteint'] ? 'atteint ✓' : 'non atteint' ?>
                    </label>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">

        <!-- COLONNE PRINCIPALE : ORDRE DU JOUR -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-list-ol me-2 text-primary"></i>Ordre du jour
                        <span class="badge bg-primary bg-opacity-10 text-primary ms-2"><?= count($points) ?> point(s)</span>
                    </h5>
                    <?php if ($seance['statut'] !== 'terminee'): ?>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPointModal">
                        <i class="bi bi-plus-lg me-1"></i>Ajouter un point
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    <?php if (empty($points)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-journal-x fs-1 d-block mb-2 opacity-25"></i>
                            L'ordre du jour est vide.<br>
                            <small>Ajoutez des points pour structurer la séance.</small>
                        </div>
                    <?php else: ?>
                        <ol class="list-group list-group-numbered list-group-flush">
                            <?php foreach ($points as $i => $pt):
                                $tcfg = $typeCfg[$pt['type_point']] ?? ['label' => $pt['type_point'], 'class' => 'bg-secondary'];
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start px-0 py-3 border-bottom">
                                <div class="ms-2 me-auto" style="max-width: calc(100% - 120px);">
                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($pt['titre']) ?></span>
                                        <span class="badge <?= $tcfg['class'] ?> small"><?= $tcfg['label'] ?></span>
                                    </div>
                                    <?php if (!empty($pt['description'])): ?>
                                        <p class="small text-muted mb-1"><?= nl2br(htmlspecialchars($pt['description'])) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($pt['direction_origine'])): ?>
                                        <small class="text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($pt['direction_origine']) ?></small>
                                    <?php endif; ?>
                                </div>
                                <?php if ($seance['statut'] !== 'terminee'): ?>
                                <a href="<?= URLROOT ?>/seances/deletePoint/<?= $pt['id'] ?>"
                                   class="btn btn-sm btn-outline-danger border-0 ms-2 flex-shrink-0"
                                   onclick="return confirm('Supprimer ce point ?')">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- COLONNE LATÉRALE : MEMBRES -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-people me-2 text-success"></i>Membres
                        <span class="badge bg-success bg-opacity-10 text-success ms-2"><?= count($membres) ?></span>
                    </h5>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    <?php if (empty($membres)): ?>
                        <p class="text-muted small text-center py-3">Aucun membre défini pour cette instance.</p>
                    <?php else: ?>
                        <?php
                        $admins    = array_filter($membres, fn($m) => $m['college'] === 'administration');
                        $personnel = array_filter($membres, fn($m) => $m['college'] === 'personnel');
                        ?>

                        <?php if (!empty($admins)): ?>
                        <p class="small fw-bold text-muted text-uppercase mb-2" style="font-size:0.7rem; letter-spacing:1px;">Collège Administration</p>
                        <ul class="list-unstyled mb-3">
                            <?php foreach ($admins as $m): ?>
                            <li class="d-flex align-items-center gap-2 py-1">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:30px;height:30px;font-size:0.75rem;font-weight:700;">
                                    <?= strtoupper(substr($m['prenom'], 0, 1) . substr($m['nom'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="small fw-bold lh-1"><?= htmlspecialchars(strtoupper($m['nom']) . ' ' . $m['prenom']) ?></div>
                                    <?php if (!empty($m['qualite'])): ?>
                                        <div style="font-size:0.7rem;" class="text-muted"><?= htmlspecialchars($m['qualite']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <span class="ms-auto badge <?= $m['type_mandat'] === 'titulaire' ? 'bg-dark' : 'bg-secondary' ?> small"><?= $m['type_mandat'] === 'titulaire' ? 'T' : 'S' ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>

                        <?php if (!empty($personnel)): ?>
                        <p class="small fw-bold text-muted text-uppercase mb-2" style="font-size:0.7rem; letter-spacing:1px;">Collège Personnel</p>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($personnel as $m): ?>
                            <li class="d-flex align-items-center gap-2 py-1">
                                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width:30px;height:30px;font-size:0.75rem;font-weight:700;">
                                    <?= strtoupper(substr($m['prenom'], 0, 1) . substr($m['nom'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="small fw-bold lh-1"><?= htmlspecialchars(strtoupper($m['nom']) . ' ' . $m['prenom']) ?></div>
                                    <?php if (!empty($m['qualite'])): ?>
                                        <div style="font-size:0.7rem;" class="text-muted"><?= htmlspecialchars($m['qualite']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <span class="ms-auto badge <?= $m['type_mandat'] === 'titulaire' ? 'bg-dark' : 'bg-secondary' ?> small"><?= $m['type_mandat'] === 'titulaire' ? 'T' : 'S' ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODALE : AJOUTER UN POINT ODJ -->
<div class="modal fade" id="addPointModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= URLROOT ?>/seances/addPoint/<?= $seance['id'] ?>" method="POST">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-journal-plus me-2 text-primary"></i>Ajouter un point</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Titre du point <span class="text-danger">*</span></label>
                        <input type="text" name="titre" class="form-control" placeholder="Ex : Bilan annuel de la formation..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Type de point</label>
                        <select name="type_point" class="form-select">
                            <option value="information">Information</option>
                            <option value="deliberation">Délibération</option>
                            <option value="vote">Vote</option>
                            <option value="divers">Divers</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Direction / Service à l'origine</label>
                        <input type="text" name="direction_origine" class="form-control" placeholder="Ex : DRH, Direction Générale...">
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-bold">Description / développement</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Contexte, détails..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="bi bi-check-lg me-1"></i>Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleQuorum(checkbox, seanceId) {
    const label = checkbox.nextElementSibling;
    const attained = checkbox.checked ? 1 : 0;
    
    fetch('<?= URLROOT ?>/seances/quorum/' + seanceId + '?attained=' + attained)
        .then(() => {
            label.textContent = attained ? 'Quorum atteint ✓' : 'Quorum non atteint';
            label.className = 'form-check-label small fw-bold ' + (attained ? 'text-success' : 'text-muted');
        });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
