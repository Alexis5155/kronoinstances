<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- CSS pour l'éditeur de texte enrichi -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<?php
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'pdf': return ['class' => 'bi-file-earmark-pdf-fill', 'color' => 'text-danger'];
        case 'doc': case 'docx': case 'odt': return ['class' => 'bi-file-earmark-word-fill', 'color' => 'text-primary'];
        case 'xls': case 'xlsx': case 'ods': return ['class' => 'bi-file-earmark-excel-fill', 'color' => 'text-success'];
        case 'ppt': case 'pptx': case 'odp': return ['class' => 'bi-file-earmark-ppt-fill', 'color' => 'text-warning'];
        case 'zip': case 'rar': case '7z': case 'tar': return ['class' => 'bi-file-earmark-zip-fill', 'color' => 'text-secondary'];
        default: return ['class' => 'bi-file-earmark-fill', 'color' => 'text-muted'];
    }
}

$dateObj  = new DateTime($seance['date_seance'] . ' ' . $seance['heure_debut']);
$statutCfg = [
    'ajournee'           => ['label' => 'Ajournée',          'class' => 'bg-danger', 'icon' => 'bi-calendar-x'],
    'brouillon'          => ['label' => 'Brouillon',         'class' => 'bg-secondary', 'icon' => 'bi-pencil-square'],
    'date_fixee'         => ['label' => 'Date fixée',        'class' => 'bg-info text-dark', 'icon' => 'bi-calendar-check'],
    'odj_valide'         => ['label' => 'ODJ validé',        'class' => 'bg-primary', 'icon' => 'bi-list-check'],
    'dossier_disponible' => ['label' => 'Dossier complet',   'class' => 'bg-success', 'icon' => 'bi-folder-check'],
    'en_cours'           => ['label' => 'Séance en cours',   'class' => 'bg-warning text-dark', 'icon' => 'bi-play-circle-fill'],
    'finalisation'       => ['label' => 'Finalisation PV',   'class' => 'bg-info text-dark', 'icon' => 'bi-file-earmark-text'],
    'terminee'           => ['label' => 'Terminée',          'class' => 'bg-dark', 'icon' => 'bi-check-circle-fill'],
];
$s = $statutCfg[$seance['statut']] ?? $statutCfg['brouillon'];

$typeCfg = [
    'information'  => ['label' => 'Information',  'class' => 'bg-info text-dark'],
    'deliberation' => ['label' => 'Délibération', 'class' => 'bg-primary'],
    'vote'         => ['label' => 'Vote',          'class' => 'bg-danger'],
    'divers'       => ['label' => 'Divers',        'class' => 'bg-secondary'],
];

// --- CYCLE DE VIE & VERROUILLAGES ---
$convocationDoc = null;
foreach($documents as $doc) {
    if (isset($doc['type_doc']) && $doc['type_doc'] === 'convocation') {
        $convocationDoc = $doc;
        break;
    }
}
$hasConvocSignee = ($convocationDoc !== null);

$isOdjEditable = in_array($seance['statut'], ['brouillon', 'date_fixee', 'ajournee']); 
$isDossierEditable = in_array($seance['statut'], ['brouillon', 'date_fixee', 'odj_valide', 'ajournee']);
?>

<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2 fs-6 shadow-sm">
            <i class="bi bi-gear-fill me-2"></i>Gestion de Séance
        </span>
        <a href="<?= URLROOT ?>/seances/view/<?= $seance['id'] ?>" class="btn btn-outline-secondary shadow-sm fw-bold bg-white">
            <i class="bi bi-eye me-2"></i>Aperçu côté membres
        </a>
    </div>

    <!-- EN-TÊTE GESTION AVEC WORKFLOW -->
    <div class="card border-0 shadow-sm mb-4 border-top border-4 border-primary">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h3 class="fw-bold mb-1"><i class="bi bi-building me-2 text-primary"></i><?= htmlspecialchars($seance['instance_nom']) ?></h3>
                    <p class="mb-0 text-muted fs-5">
                        <i class="bi bi-calendar-event me-2"></i><?= $dateObj->format('d/m/Y') ?> à <?= $dateObj->format('H\hi') ?>
                    </p>
                </div>
                
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <div class="mb-2">Statut : <span class="badge <?= $s['class'] ?> fs-6"><i class="bi <?= $s['icon'] ?> me-1"></i><?= $s['label'] ?></span></div>
                    
                    <div class="d-flex justify-content-md-end gap-2 flex-wrap mt-3">
                        
                        <!-- ÉTAPE : AJOURNÉE -->
                        <?php if ($seance['statut'] === 'ajournee'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=brouillon', 'Reprendre la préparation de la séance ?')" class="btn btn-secondary fw-bold shadow-sm">
                                <i class="bi bi-arrow-repeat me-1"></i> Reprendre en brouillon
                            </a>

                        <!-- ÉTAPE 0 : BROUILLON -->
                        <?php elseif ($seance['statut'] === 'brouillon'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=date_fixee', 'Confirmer la date de la séance ? Celle-ci deviendra visible pour les membres.')" class="btn btn-info fw-bold shadow-sm text-dark">
                                Étape 1 : Confirmer la date <i class="bi bi-arrow-right ms-1"></i>
                            </a>

                        <!-- ÉTAPE 1 : DATE FIXÉE -->
                        <?php elseif ($seance['statut'] === 'date_fixee'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=brouillon', 'Repasser en brouillon ? La séance ne sera plus visible par les membres.')" class="btn btn-outline-secondary fw-bold shadow-sm">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Brouillon
                            </a>
                            <button type="button" class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#publishOdjModal">
                                Étape 2 : Publier l'ODJ <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                            
                        <!-- ÉTAPE 2 : ODJ VALIDÉ -->
                        <?php elseif ($seance['statut'] === 'odj_valide'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=ajournee', 'Ajourner la séance ? Les membres seront informés du report.')" class="btn btn-outline-danger fw-bold shadow-sm">
                                <i class="bi bi-calendar-x me-1"></i> Ajourner
                            </a>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=dossier_disponible', 'Publier le dossier complet ? Les membres auront accès aux exposés et aux pièces jointes.')" class="btn btn-success fw-bold shadow-sm">
                                Étape 3 : Publier le dossier <i class="bi bi-arrow-right ms-1"></i>
                            </a>

                        <!-- ÉTAPE 3 : DOSSIER PUBLIÉ -->
                        <?php elseif ($seance['statut'] === 'dossier_disponible'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=ajournee', 'Ajourner la séance ? Les membres seront informés du report.')" class="btn btn-outline-danger fw-bold shadow-sm">
                                <i class="bi bi-calendar-x me-1"></i> Ajourner
                            </a>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=en_cours', 'Démarrer la séance maintenant et ouvrir le bureau en direct ?')" class="btn btn-warning fw-bold shadow-sm text-dark">
                                Démarrer la séance <i class="bi bi-play-fill ms-1"></i>
                            </a>

                        <!-- ÉTAPE 4 : SÉANCE EN COURS (LIVE) -->
                        <?php elseif ($seance['statut'] === 'en_cours'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=ajournee', 'Ajourner la séance en cours ?')" class="btn btn-outline-danger fw-bold shadow-sm">
                                <i class="bi bi-calendar-x me-1"></i> Ajourner
                            </a>
                            <a href="<?= URLROOT ?>/seances/live/<?= $seance['id'] ?>" class="btn btn-danger fw-bold shadow-sm">
                                <i class="bi bi-record-circle-fill me-1" style="animation: pulse-red 2s infinite;"></i>Reprendre Live
                            </a>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=finalisation', 'Terminer la réunion et passer à la rédaction du PV ?')" class="btn btn-info fw-bold shadow-sm text-dark">
                                <i class="bi bi-stop-fill me-1"></i>Clôturer le Live
                            </a>

                        <!-- ÉTAPE 5 : FINALISATION -->
                        <?php elseif ($seance['statut'] === 'finalisation'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=en_cours', 'Rouvrir le Live ?')" class="btn btn-outline-secondary fw-bold shadow-sm">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Retour au Live
                            </a>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=terminee', 'Clôturer la séance définitivement et mettre à disposition le PV ?')" class="btn btn-dark fw-bold shadow-sm">
                                <i class="bi bi-check-circle-fill me-1"></i> Terminer la séance
                            </a>

                        <!-- ÉTAPE 6 : TERMINÉE -->
                        <?php elseif ($seance['statut'] === 'terminee'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=finalisation', 'Rouvrir la séance en mode finalisation pour modifier le PV ?')" class="btn btn-outline-danger fw-bold shadow-sm">
                                <i class="bi bi-unlock-fill me-1"></i> Rouvrir la séance
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <hr class="text-muted my-4 opacity-25">
            
            <!-- BARRE DE PROGRESSION VISUELLE -->
            <?php 
            $steps = [
                'brouillon'          => 'Brouillon',
                'date_fixee'         => 'Date fixée',
                'odj_valide'         => 'ODJ validé',
                'dossier_disponible' => 'Dossier',
                'en_cours'           => 'En Live',
                'finalisation'       => 'PV',
                'terminee'           => 'Terminée'
            ];
            
            if ($seance['statut'] === 'ajournee') {
                echo '<div class="alert alert-danger shadow-sm border-0 mb-0"><i class="bi bi-exclamation-octagon-fill me-2"></i> <strong>La séance a été ajournée.</strong> Les travaux sont suspendus. Repassez en brouillon pour planifier une nouvelle date.</div>';
            } else {
                $currentStatusIndex = array_search($seance['statut'], array_keys($steps));
                if ($currentStatusIndex === false) $currentStatusIndex = 0;
                $progressPct = ($currentStatusIndex / (count($steps) - 1)); 
            ?>
            <div class="position-relative mt-2 mb-2">
                <div class="position-absolute" style="top: 15px; left: 40px; right: 40px; height: 3px; background-color: #e9ecef; z-index: 1;"></div>
                <div class="position-absolute rounded" style="top: 15px; left: 40px; width: calc((100% - 80px) * <?= $progressPct ?>); height: 3px; background-color: #0d6efd; z-index: 2; transition: width 0.5s ease;"></div>
                
                <div class="d-flex justify-content-between position-relative" style="z-index: 3;">
                    <?php 
                    $stepIndex = 0;
                    foreach ($steps as $key => $label): 
                        $isCompleted = $stepIndex < $currentStatusIndex;
                        $isActive = $stepIndex === $currentStatusIndex;
                        
                        $circleClass = $isCompleted ? 'bg-primary border-primary text-white' : ($isActive ? 'bg-primary border-primary text-white shadow' : 'bg-white text-muted');
                        $borderStyle = (!$isCompleted && !$isActive) ? 'border-color: #ced4da !important;' : '';
                        $iconOrNum = $isCompleted ? '<i class="bi bi-check-lg"></i>' : ($stepIndex + 1);
                        $labelClass = ($isCompleted || $isActive) ? 'text-dark fw-bold' : 'text-muted opacity-75';
                    ?>
                        <div class="text-center" style="width: 80px;">
                            <div class="rounded-circle border border-2 d-flex align-items-center justify-content-center mx-auto mb-2 <?= $circleClass ?>" style="width: 32px; height: 32px; transition: all 0.3s ease; <?= $borderStyle ?>">
                                <?= $iconOrNum ?>
                            </div>
                            <div class="small <?= $labelClass ?>" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px;"><?= $label ?></div>
                        </div>
                    <?php $stepIndex++; endforeach; ?>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- INCLUSION DYNAMIQUE SELON L'ÉTAPE -->
    <?php
    if (in_array($seance['statut'], ['finalisation', 'terminee'])) {
        require_once __DIR__ . '/_edit_finalisation.php';
    } else {
        require_once __DIR__ . '/_edit_preparation.php';
    }
    ?>

</div>

<!-- MODALE PUBLICATION ODJ & CONVOCATIONS -->
<div class="modal fade" id="publishOdjModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>" method="GET">
                <input type="hidden" name="statut" value="odj_valide">
                
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-send-check text-primary me-2"></i>Publier l'Ordre du Jour</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <?php if (!$hasConvocSignee): ?>
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-triangle me-2"></i> Aucune convocation n'a été déposée !
                        </div>
                    <?php endif; ?>
                    <p class="mb-2">La validation de l'ordre du jour le rendra visible pour tous les membres convoqués.</p>
                    
                    <div class="form-check form-switch mt-3 bg-light p-3 rounded border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" id="sendConvocs" name="send_convocs" value="1" checked style="cursor: pointer;">
                        <label class="form-check-label fw-bold ms-1" for="sendConvocs" style="cursor: pointer;">
                            Envoyer les convocations par e-mail
                        </label>
                        <div class="small text-muted mt-1 ms-4">Un e-mail contenant l'ODJ sera expédié aux membres titulaires et suppléants.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Valider & Publier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODALE GÉNÉRIQUE DE CONFIRMATION -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-triangle-fill text-warning display-4 d-block mb-3"></i>
                <h5 class="fw-bold mb-4" id="confirmModalText">Êtes-vous sûr ?</h5>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Annuler</button>
                    <a href="#" id="confirmModalBtn" class="btn btn-danger px-4 fw-bold">Confirmer</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-dashed { border: 2px dashed #dee2e6 !important; }
.card-body::-webkit-scrollbar { width: 6px; }
.card-body::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
.card-body::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
.card-body::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
.drag-handle:active { cursor: grabbing !important; }
.sortable-ghost { opacity: 0.4; background-color: #f8f9fa; border: 2px dashed #0d6efd !important; }
.rich-text-container { font-size: 0.875em; color: #6c757d; margin-bottom: 1rem; }
.rich-text-container p, .rich-text-container ul, .rich-text-container ol { margin-bottom: 0.5rem; }
.rich-text-container p:last-child, .rich-text-container ul:last-child { margin-bottom: 0; }
.rich-text-container a { color: #0d6efd; }
</style>

<!-- Librairies externes JS -->
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
