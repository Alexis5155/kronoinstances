<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<?php
$dateObj  = new DateTime($seance['date_seance'] . ' ' . $seance['heure_debut']);
$statutCfg = [
    'ajournee'           => ['label' => 'Ajournée',          'class' => 'bg-danger text-white', 'icon' => 'bi-calendar-x'],
    'brouillon'          => ['label' => 'Brouillon',         'class' => 'bg-secondary bg-opacity-10 text-secondary border-secondary', 'icon' => 'bi-pencil-square'],
    'date_fixee'         => ['label' => 'Date fixée',        'class' => 'bg-info bg-opacity-10 text-info border-info', 'icon' => 'bi-calendar-check'],
    'odj_valide'         => ['label' => 'ODJ validé',        'class' => 'bg-primary bg-opacity-10 text-primary border-primary', 'icon' => 'bi-list-check'],
    'dossier_disponible' => ['label' => 'Dossier complet',   'class' => 'bg-success bg-opacity-10 text-success border-success', 'icon' => 'bi-folder-check'],
    'en_cours'           => ['label' => 'Séance en cours',   'class' => 'bg-warning bg-opacity-10 text-warning border-warning pulse-bg', 'icon' => 'bi-play-circle-fill'],
    'finalisation'       => ['label' => 'Finalisation PV',   'class' => 'bg-info bg-opacity-10 text-info border-info', 'icon' => 'bi-file-earmark-text'],
    'terminee'           => ['label' => 'Terminée',          'class' => 'bg-dark bg-opacity-10 text-dark border-dark', 'icon' => 'bi-check-circle-fill'],
];
$s = $statutCfg[$seance['statut']] ?? $statutCfg['brouillon'];

$convocationDoc = null;
foreach($documents as $doc) {
    if (isset($doc['type_doc']) && $doc['type_doc'] === 'convocation') {
        $convocationDoc = $doc; break;
    }
}
$hasConvocSignee = ($convocationDoc !== null);

// Tri des points : les "retirés" vont à la fin
usort($points, function($a, $b) {
    $retA = $a['retire'] ?? 0;
    $retB = $b['retire'] ?? 0;
    if ($retA != $retB) return $retA <=> $retB;
    return $a['ordre_affichage'] <=> $b['ordre_affichage'];
});
?>

<style>
/* Animation pour le statut en cours */
@keyframes pulse-bg {
    0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
    70% { box-shadow: 0 0 0 6px rgba(255, 193, 7, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
}
.pulse-bg { animation: pulse-bg 2s infinite; }

/* Timeline design - Ligne resserrée */
.timeline-track { background-color: #e9ecef; }
.timeline-progress { background-color: #0d6efd; transition: width 0.5s ease; }
.timeline-step { transition: all 0.3s ease; }

/* Bouton Aperçu personnalisé */
.btn-apercu {
    background-color: #fff;
    border: 1px solid #dee2e6;
    color: #495057;
    transition: all 0.2s ease;
}
.btn-apercu:hover {
    background-color: #f8f9fa;
    border-color: #0d6efd;
    color: #0d6efd;
    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.1) !important;
}

/* LIVE ICON ANIMATION */
@keyframes pulse-red {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}
.live-indicator {
    display: inline-block; width: 12px; height: 12px; border-radius: 50%;
    background-color: #dc3545; animation: pulse-red 2s infinite;
}

</style>

<div class="container py-3">
    <!-- Breadcrumb / Header Actions -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center text-muted fw-bold fs-5">
            <i class="bi bi-gear-fill text-primary me-2"></i> Préparation de la séance
        </div>
        <a href="<?= URLROOT ?>/seances/view/<?= $seance['id'] ?>" class="btn btn-apercu shadow-sm fw-bold rounded-pill px-4">
            <i class="bi bi-eye me-2"></i>Aperçu côté membres
        </a>
    </div>

    <!-- EN-TÊTE WORKFLOW -->
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
        <!-- Bandeau supérieur discret -->
        <div class="bg-primary" style="height: 5px; width: 100%;"></div>
        
        <div class="card-body p-3 p-md-4">
            <div class="row align-items-center">
                <!-- Info Instance -->
                <div class="col-md-7">
                    <h3 class="fw-bold mb-1 text-dark d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px;">
                            <i class="bi bi-building fs-5"></i>
                        </div>
                        <?= htmlspecialchars($seance['instance_nom']) ?>
                    </h3>
                    <p class="mb-0 text-muted ms-5 ps-2 fw-medium" style="font-size: 0.95rem;">
                        <i class="bi bi-calendar-event me-2 text-secondary"></i><?= $dateObj->format('d/m/Y') ?> <span class="mx-1 text-light-muted">à</span> <?= $dateObj->format('H\hi') ?>
                    </p>
                </div>
                
                <!-- Statut & Actions -->
                <div class="col-md-5 text-md-end mt-3 mt-md-0 border-start-md border-light">
                    <div class="mb-2">
                        <span class="text-muted small text-uppercase fw-bold me-2" style="letter-spacing: 0.5px; font-size: 0.7rem;">Statut actuel</span>
                        <span class="badge <?= $s['class'] ?> border border-opacity-25 px-3 py-1 rounded-pill shadow-sm">
                            <i class="bi <?= $s['icon'] ?> me-1"></i> <?= $s['label'] ?>
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-md-end gap-2 flex-wrap mt-2">
                        <?php if ($seance['statut'] === 'ajournee'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=brouillon', 'Reprendre en brouillon ?')" class="btn btn-sm btn-outline-secondary fw-bold rounded-pill shadow-sm px-3 py-2">Reprendre en brouillon</a>
                        <?php elseif ($seance['statut'] === 'brouillon'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=date_fixee', 'Étape 2 : Verrouiller la date et l\'heure ?')" class="btn btn-info text-white fw-bold rounded-pill shadow-sm px-4">Étape 2 : Date fixée <i class="bi bi-arrow-right ms-1"></i></a>
                        <?php elseif ($seance['statut'] === 'date_fixee'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=brouillon', 'Retourner en brouillon ?')" class="btn btn-light border fw-bold rounded-pill shadow-sm px-3"><i class="bi bi-arrow-left"></i></a>
                            <button type="button" class="btn btn-primary fw-bold rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#publishOdjModal">Étape 3 : Valider l'ODJ <i class="bi bi-arrow-right ms-1"></i></button>
                        <?php elseif ($seance['statut'] === 'odj_valide'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=ajournee', 'Ajourner la séance ?')" class="btn btn-white text-danger border border-danger border-opacity-25 fw-bold rounded-pill shadow-sm">Ajourner</a>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=dossier_disponible', 'Étape 4 : Publier le dossier complet ?')" class="btn btn-success fw-bold rounded-pill shadow-sm px-4">Étape 4 : Valider le dossier <i class="bi bi-arrow-right ms-1"></i></a>
                        <?php elseif ($seance['statut'] === 'dossier_disponible'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=en_cours', 'Démarrer le Live ?')" class="btn btn-warning text-dark fw-bold rounded-pill shadow-sm px-4">Démarrer le Live <i class="bi bi-play-fill ms-1"></i></a>
                        <?php elseif ($seance['statut'] === 'en_cours'): ?>
                            <a href="<?= URLROOT ?>/seances/live/<?= $seance['id'] ?>" class="btn btn-danger fw-bold rounded-pill shadow-sm px-4 d-inline-flex align-items-center"><span class="live-indicator bg-white me-2" style="animation: none; transform: scale(1); box-shadow: 0 0 0 0 rgba(255,255,255,0.7);"></span> Aller au Live</a>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=finalisation', 'Clôturer la séance et passer au PV ?')" class="btn btn-info text-white fw-bold rounded-pill shadow-sm px-4"><i class="bi bi-stop-fill me-1"></i>Passer au PV</a>
                        <?php elseif ($seance['statut'] === 'finalisation'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=terminee', 'Clôturer définitivement la séance ? Le PV sera envoyé aux membres.')" class="btn btn-dark fw-bold rounded-pill shadow-sm px-4"><i class="bi bi-check-circle-fill me-2"></i> Terminer la séance</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <hr class="text-muted my-3 opacity-10">
            
            <!-- TIMELINE DES ÉTAPES -->
            <?php 
            $steps = ['brouillon'=>'Brouillon', 'date_fixee'=>'Date fixée', 'odj_valide'=>'ODJ validé', 'dossier_disponible'=>'Dossier', 'en_cours'=>'En Live', 'finalisation'=>'PV', 'terminee'=>'Terminée'];
            $currentStatusIndex = array_search($seance['statut'], array_keys($steps));
            if ($currentStatusIndex === false) $currentStatusIndex = 0;
            $progressPct = ($currentStatusIndex / (count($steps) - 1)); 
            ?>
            <div class="position-relative mt-3 mx-md-4">
                <!-- Barres de fond : left 40px et right 40px centrent exactement la ligne derrière les pastilles -->
                <div class="position-absolute timeline-track rounded-pill" style="top: 15px; left: 40px; right: 40px; height: 4px; z-index: 1;"></div>
                <div class="position-absolute timeline-progress rounded-pill" style="top: 15px; left: 40px; width: calc((100% - 80px) * <?= $progressPct ?>); height: 4px; z-index: 2;"></div>
                
                <div class="d-flex justify-content-between position-relative" style="z-index: 3;">
                    <?php $stepIndex = 0; foreach ($steps as $key => $label): 
                        $isCompleted = $stepIndex < $currentStatusIndex; 
                        $isActive = $stepIndex === $currentStatusIndex;
                        
                        $circleClass = 'bg-white text-muted border-light';
                        if ($isCompleted) $circleClass = 'bg-primary border-primary text-white';
                        if ($isActive) $circleClass = 'bg-white border-primary text-primary shadow-sm border-3';
                        
                        $iconOrNum = $isCompleted ? '<i class="bi bi-check-lg fw-bold"></i>' : ($stepIndex + 1);
                        if ($isActive && $key === 'en_cours') $iconOrNum = '<i class="live-indicator"></i>';
                    ?>
                        <div class="text-center timeline-step" style="width: 80px;">
                            <div class="rounded-circle border d-flex align-items-center justify-content-center mx-auto mb-2 <?= $circleClass ?>" style="width: 34px; height: 34px; font-weight: <?= $isActive ? 'bold' : 'normal' ?>;">
                                <?= $iconOrNum ?>
                            </div>
                            <div class="small <?= ($isCompleted || $isActive) ? 'text-dark fw-bold' : 'text-muted fw-medium' ?>" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                <?= $label ?>
                            </div>
                        </div>
                    <?php $stepIndex++; endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- VUES ENFANTS SELON L'ÉTAPE -->
    <?php
    if (in_array($seance['statut'], ['finalisation', 'terminee'])) {
        require_once __DIR__ . '/_edit_finalisation.php';
    } else {
        require_once __DIR__ . '/_edit_preparation.php';
    }
    ?>
</div>

<!-- MODALE PUBLICATION ODJ -->
<div class="modal fade" id="publishOdjModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>" method="GET">
                <input type="hidden" name="statut" value="odj_valide">
                
                <div class="modal-header bg-light border-0 px-4 py-3 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-send-check-fill text-primary me-2"></i>Valider l'Ordre du Jour</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <p class="text-muted mb-4">L'ordre du jour deviendra figé (plus d'ajout de point possible). Les membres pourront alors consulter les points prévus à la séance.</p>
                    
                    <?php if (!$hasConvocSignee): ?>
                        <div class="alert alert-warning border-warning shadow-sm rounded-3 d-flex align-items-center mb-4">
                            <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                            <div>
                                <strong class="d-block">Document manquant</strong>
                                <span class="small">Le PDF signé de la convocation n'a pas encore été déposé.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (\app\models\User::can('manage_convocations')): ?>
                    <div class="card border-0 bg-light rounded-3">
                        <div class="card-body p-3">
                            <div class="form-check form-switch d-flex align-items-center m-0 p-0">
                                <input class="form-check-input ms-0 me-3 shadow-sm" type="checkbox" id="sendConvocs" name="send_convocs" value="1" role="switch" style="width: 2.5rem; height: 1.25rem;" <?= !$hasConvocSignee ? 'disabled' : 'checked' ?>>
                                <label class="form-check-label fw-bold text-dark mb-0" style="cursor:pointer;" for="sendConvocs">
                                    Envoyer les convocations par e-mail
                                    <div class="small text-muted fw-normal">Notifier les membres dès maintenant</div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="modal-footer border-0 bg-white px-4 pb-4 pt-0 rounded-bottom-4">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm rounded-pill"><i class="bi bi-check-lg me-2"></i>Valider l'ODJ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODALE DE CONFIRMATION -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
            <div class="mb-3">
                <div class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle" style="width: 70px; height: 70px;">
                    <i class="bi bi-exclamation-triangle-fill fs-1"></i>
                </div>
            </div>
            <h5 class="fw-bold mb-4 text-dark" id="confirmModalText">Êtes-vous sûr ?</h5>
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-light fw-bold px-4 rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <a href="#" id="confirmModalBtn" class="btn btn-danger px-4 fw-bold shadow-sm rounded-pill">Confirmer</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
function showConfirmModal(url, message) {
    document.getElementById('confirmModalText').innerText = message;
    document.getElementById('confirmModalBtn').href = url;
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
