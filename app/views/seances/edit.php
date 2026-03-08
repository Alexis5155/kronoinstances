<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<?php
$dateObj  = new DateTime($seance['date_seance'] . ' ' . $seance['heure_debut']);

$convocationDoc = null;
foreach($documents as $doc) {
    if (isset($doc['type_doc']) && $doc['type_doc'] === 'convocation') {
        $convocationDoc = $doc; break;
    }
}
$hasConvocSignee = ($convocationDoc !== null);
$convocationsEnvoyees = isset($seance['convocations_envoyees']) && $seance['convocations_envoyees'] == 1;

// Tri des points : les "retirés" vont à la fin
usort($points, function($a, $b) {
    $retA = $a['retire'] ?? 0;
    $retB = $b['retire'] ?? 0;
    if ($retA != $retB) return $retA <=> $retB;
    return $a['ordre_affichage'] <=> $b['ordre_affichage'];
});

$steps = [
    'brouillon'          => 'Brouillon', 
    'date_fixee'         => 'Date fixée', 
    'odj_valide'         => 'ODJ validé', 
    'dossier_disponible' => 'Dossier', 
    'en_cours'           => 'En Live', 
    'finalisation'       => 'PV', 
    'terminee'           => 'Terminée'
];

$currentStatusIndex = array_search($seance['statut'], array_keys($steps));
if ($currentStatusIndex === false) $currentStatusIndex = 0;

// Si la séance est ajournée, on considère qu'elle n'est plus dans le flux classique,
// on met la barre à 0, mais on garde l'affichage global.
$isAjournee = ($seance['statut'] === 'ajournee');
$progressPct = $isAjournee ? 0 : ($currentStatusIndex / (count($steps) - 1)); 

?>

<style>
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

/* Timeline responsive avec overflow scroll sur mobile */
.timeline-wrapper {
    width: 100%;
    overflow-x: auto;
    padding-bottom: 10px;
}
.timeline-container {
    position: relative;
    min-width: 600px;
    margin: 0 auto;
}
.timeline-track { background-color: #e9ecef; }
.timeline-progress { background-color: #0d6efd; transition: width 0.5s ease; }
.timeline-step { transition: all 0.3s ease; }

.timeline-wrapper::-webkit-scrollbar { height: 4px; }
.timeline-wrapper::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 10px; }

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

.ajournee-badge {
    background: repeating-linear-gradient(45deg, #dc3545, #dc3545 10px, #c82333 10px, #c82333 20px);
    color: white;
}
</style>

<div class="container py-3">
    <!-- Breadcrumb / Header Actions -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center text-muted fw-bold fs-5">
            <i class="bi bi-gear-fill text-primary me-2"></i> Préparation de la séance
        </div>
        <a href="<?= URLROOT ?>/seances/view/<?= $seance['id'] ?>" class="btn btn-apercu shadow-sm fw-bold rounded-pill px-4">
            <i class="bi bi-eye me-2"></i>Aperçu côté membres
        </a>
    </div>

    <!-- EN-TÊTE WORKFLOW -->
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden <?= $isAjournee ? 'border-danger border-2' : '' ?>">
        <!-- Bandeau supérieur -->
        <div class="<?= $isAjournee ? 'bg-danger' : 'bg-primary' ?>" style="height: 5px; width: 100%;"></div>
        
        <div class="card-body p-3 p-md-4">
            <?php if ($isAjournee): ?>
                <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4 d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Séance ajournée</h6>
                        <p class="mb-0 small">Cette séance a été suspendue. Vous pouvez la reprendre au stade de brouillon si vous souhaitez la reprogrammer.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row align-items-center">
                <!-- Info Instance -->
                <div class="col-lg-5 mb-3 mb-lg-0">
                    <h3 class="fw-bold mb-1 text-dark d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 42px; height: 42px;">
                            <i class="bi bi-building fs-5"></i>
                        </div>
                        <span class="text-truncate"><?= htmlspecialchars($seance['instance_nom']) ?></span>
                    </h3>
                    <p class="mb-0 text-muted ms-5 ps-2 fw-medium" style="font-size: 0.95rem;">
                        <i class="bi bi-calendar-event me-2 <?= $isAjournee ? 'text-danger' : 'text-secondary' ?>"></i>
                        <?= $dateObj->format('d/m/Y') ?> <span class="mx-1 text-light-muted">à</span> <?= $dateObj->format('H\hi') ?>
                    </p>
                </div>
                
                <!-- Actions -->
                <div class="col-lg-7 text-lg-end border-start-lg border-light">
                    <div class="d-flex justify-content-lg-end gap-2 flex-wrap">
                        
                        <?php if ($isAjournee): ?>
                            <button type="button" class="btn btn-outline-secondary fw-bold rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#stepReprendreModal"><i class="bi bi-arrow-counterclockwise me-1"></i> Reprendre en brouillon</button>

                        <?php elseif ($seance['statut'] === 'brouillon'): ?>
                            <button type="button" class="btn btn-info text-white fw-bold rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#stepDateFixeeModal">Étape 2 : Date fixée <i class="bi bi-arrow-right ms-1"></i></button>
                        
                        <?php elseif ($seance['statut'] === 'date_fixee'): ?>
                            <button type="button" class="btn btn-light border fw-bold rounded-circle shadow-sm" style="width:40px; height:40px; padding:0; display:flex; align-items:center; justify-content:center;" title="Retour en brouillon" data-bs-toggle="modal" data-bs-target="#stepRetourBrouillonModal"><i class="bi bi-arrow-left"></i></button>
                            <button type="button" class="btn btn-primary fw-bold rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#stepOdjModal">Étape 3 : Valider l'ODJ <i class="bi bi-arrow-right ms-1"></i></button>
                        
                        <?php elseif ($seance['statut'] === 'odj_valide'): ?>
                            <button type="button" class="btn btn-white text-danger border border-danger border-opacity-25 fw-bold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#stepAjournerModal">Ajourner</button>
                            <button type="button" class="btn btn-success fw-bold rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#stepDossierModal">Étape 4 : Valider le dossier <i class="bi bi-arrow-right ms-1"></i></button>
                        
                        <?php elseif ($seance['statut'] === 'dossier_disponible'): ?>
                            <button type="button" class="btn btn-white text-danger border border-danger border-opacity-25 fw-bold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#stepAjournerModal">Ajourner</button>
                            <button type="button" class="btn btn-warning text-dark fw-bold rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#stepLiveModal">Démarrer le live <i class="bi bi-play-fill ms-1"></i></button>
                        
                        <?php elseif ($seance['statut'] === 'en_cours'): ?>
                            <button type="button" class="btn btn-white text-danger border border-danger border-opacity-25 fw-bold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#stepAjournerModal">Interrompre la séance</button>
                            <a href="<?= URLROOT ?>/seances/live/<?= $seance['id'] ?>" class="btn btn-danger fw-bold rounded-pill shadow-sm px-4 d-inline-flex align-items-center"><span class="live-indicator bg-white me-2" style="animation: none; transform: scale(1); box-shadow: 0 0 0 0 rgba(255,255,255,0.7);"></span> Aller au Live</a>
                            <button type="button" class="btn btn-info text-white fw-bold rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#stepPvModal"><i class="bi bi-stop-fill me-1"></i>Passer au PV</button>
                        
                        <?php elseif ($seance['statut'] === 'finalisation'): ?>
                            <button type="button" class="btn btn-dark fw-bold rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#stepTermineeModal"><i class="bi bi-check-circle-fill me-2"></i> Terminer la séance</button>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
            
            <hr class="text-muted my-3 opacity-10">
            
            <!-- TIMELINE DES ÉTAPES RESPONSIVE -->
            <div class="timeline-wrapper mt-3 mx-md-2" style="<?= $isAjournee ? 'opacity:0.5; pointer-events:none;' : '' ?>">
                <div class="timeline-container">
                    <!-- Barres de fond -->
                    <div class="position-absolute timeline-track rounded-pill" style="top: 15px; left: 40px; right: 40px; height: 4px; z-index: 1;"></div>
                    <div class="position-absolute timeline-progress rounded-pill" style="top: 15px; left: 40px; width: calc((100% - 80px) * <?= $progressPct ?>); height: 4px; z-index: 2;"></div>
                    
                    <div class="d-flex justify-content-between position-relative" style="z-index: 3;">
                        <?php $stepIndex = 0; foreach ($steps as $key => $label): 
                            $isCompleted = !$isAjournee && ($stepIndex < $currentStatusIndex); 
                            $isActive = !$isAjournee && ($stepIndex === $currentStatusIndex);
                            
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

<!-- ==========================================
     MODALES HARMONISÉES DE CHANGEMENT D'ÉTAPE 
     ========================================== -->

<!-- Modale : Ajourner -->
<div class="modal fade" id="stepAjournerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>" method="GET">
                <input type="hidden" name="statut" value="ajournee">
                <div class="modal-header bg-light border-0 px-4 py-3 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-x-circle-fill text-danger me-2"></i>Ajourner la séance</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-danger border-danger shadow-sm rounded-3 d-flex align-items-center mb-0">
                        <i class="bi bi-exclamation-triangle fs-3 me-3"></i>
                        <div>
                            <strong class="d-block">Attention</strong>
                            <span class="small">L'ajournement mettra la séance en pause. Vous pourrez la reprendre ultérieurement en la repassant à l'état de brouillon.</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-white px-4 pb-4 pt-0 rounded-bottom-4">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4 shadow-sm rounded-pill"><i class="bi bi-x-circle me-2"></i>Confirmer l'ajournement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modale : Reprendre (depuis Ajournée) -->
<div class="modal fade" id="stepReprendreModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>" method="GET">
                <input type="hidden" name="statut" value="brouillon">
                <div class="modal-header bg-light border-0 px-4 py-3 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-arrow-counterclockwise text-secondary me-2"></i>Reprendre la séance</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-0">La séance va repasser à l'état de <strong>brouillon</strong>. Vous pourrez modifier la date, l'heure et l'ordre du jour avant de relancer le processus de validation.</p>
                </div>
                <div class="modal-footer border-0 bg-white px-4 pb-4 pt-0 rounded-bottom-4">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-outline-secondary fw-bold px-4 shadow-sm rounded-pill"><i class="bi bi-check-lg me-2"></i>Reprendre en brouillon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modale : Retourner en brouillon (depuis Date fixée) -->
<div class="modal fade" id="stepRetourBrouillonModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>" method="GET">
                <input type="hidden" name="statut" value="brouillon">
                <div class="modal-header bg-light border-0 px-4 py-3 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-arrow-left-circle text-secondary me-2"></i>Retour au brouillon</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-0">Vous allez déverrouiller la date et l'heure pour repasser la séance en statut brouillon.</p>
                </div>
                <div class="modal-footer border-0 bg-white px-4 pb-4 pt-0 rounded-bottom-4">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-outline-secondary fw-bold px-4 shadow-sm rounded-pill"><i class="bi bi-check-lg me-2"></i>Confirmer le retour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modale : Étape 2 (Date fixée) -->
<div class="modal fade" id="stepDateFixeeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>" method="GET">
                <input type="hidden" name="statut" value="date_fixee">
                <div class="modal-header bg-light border-0 px-4 py-3 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-calendar-check text-info me-2"></i>Verrouiller la Date</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-0">La date et l'heure seront verrouillées, et vous pourrez procéder à la publication de l'ordre du jour à la prochaine étape.</p>
                </div>
                <div class="modal-footer border-0 bg-white px-4 pb-4 pt-0 rounded-bottom-4">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info text-white fw-bold px-4 shadow-sm rounded-pill"><i class="bi bi-arrow-right me-2"></i>Passer à l'étape 2</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modale : Étape 3 (ODJ Validé) -->
<div class="modal fade" id="stepOdjModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>" method="GET">
                <input type="hidden" name="statut" value="odj_valide">
                <div class="modal-header bg-light border-0 px-4 py-3 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-send-check-fill text-primary me-2"></i>Valider l'Ordre du Jour</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-4">L'ordre du jour deviendra figé. Les membres pourront consulter les points prévus à la séance depuis leur espace.</p>
                    
                    <?php if (!$hasConvocSignee): ?>
                        <div class="alert alert-warning border-warning shadow-sm rounded-3 d-flex align-items-center mb-4">
                            <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                            <div>
                                <strong class="d-block">Document manquant</strong>
                                <span class="small">Le PDF de la convocation n'a pas encore été déposé sur la plateforme.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (\app\models\User::can('manage_convocations')): ?>
                    <div class="card border-0 bg-light rounded-3">
                        <div class="card-body p-3">
                            <div class="form-check form-switch d-flex align-items-center m-0 p-0">
                                <input class="form-check-input ms-0 me-3 shadow-sm" type="checkbox" id="sendConvocs" name="send_convocs" value="1" role="switch" style="width: 2.5rem; height: 1.25rem;" <?= (!$hasConvocSignee || $convocationsEnvoyees) ? 'disabled' : 'checked' ?>>
                                <label class="form-check-label fw-bold text-dark mb-0" style="cursor:pointer;" for="sendConvocs">
                                    Envoyer les convocations par e-mail
                                    <?php if ($convocationsEnvoyees): ?>
                                        <div class="small text-success fw-normal"><i class="bi bi-check-circle-fill"></i> Déjà envoyées</div>
                                    <?php else: ?>
                                        <div class="small text-muted fw-normal">Notifier les membres dès maintenant</div>
                                    <?php endif; ?>
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

<!-- Modale : Étape 4 (Dossier disponible) -->
<div class="modal fade" id="stepDossierModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>" method="GET">
                <input type="hidden" name="statut" value="dossier_disponible">
                <div class="modal-header bg-light border-0 px-4 py-3 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-folder-check text-success me-2"></i>Valider le Dossier Complet</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-4">La préparation de la séance est terminée. Les membres auront accès à l'intégralité des documents annexés à l'ODJ.</p>

                    <?php if (!$convocationsEnvoyees): ?>
                        <div class="alert alert-danger border-danger shadow-sm rounded-3 d-flex align-items-center mb-0">
                            <i class="bi bi-envelope-x-fill fs-3 me-3"></i>
                            <div>
                                <strong class="d-block">Attention : Convocations non envoyées</strong>
                                <span class="small">Les membres n'ont toujours pas été notifiés de cette séance.</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0 bg-white px-4 pb-4 pt-0 rounded-bottom-4">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm rounded-pill"><i class="bi bi-arrow-right me-2"></i>Valider le dossier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modale : Étape 5 (Live) -->
<div class="modal fade" id="stepLiveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>" method="GET">
                <input type="hidden" name="statut" value="en_cours">
                <div class="modal-header bg-light border-0 px-4 py-3 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-play-circle-fill text-warning me-2"></i>Démarrer le Live</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-4">La séance va passer en mode "Live". Vous pourrez ouvrir les votes électroniques et saisir les débats en temps réel.</p>
                    
                    <?php if (!$convocationsEnvoyees): ?>
                        <div class="alert alert-danger border-danger shadow-sm rounded-3 d-flex align-items-center mb-0">
                            <i class="bi bi-envelope-x-fill fs-3 me-3"></i>
                            <div>
                                <strong class="d-block">Alerte : Convocations manquantes</strong>
                                <span class="small">La séance va débuter mais les membres n'ont jamais été convoqués officiellement via la plateforme.</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0 bg-white px-4 pb-4 pt-0 rounded-bottom-4">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4 shadow-sm rounded-pill"><i class="bi bi-play-fill me-2"></i>Démarrer le Live</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modale : Étape 6 (PV) -->
<div class="modal fade" id="stepPvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>" method="GET">
                <input type="hidden" name="statut" value="finalisation">
                <div class="modal-header bg-light border-0 px-4 py-3 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-file-earmark-text-fill text-info me-2"></i>Passer au PV</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-0">La séance en direct sera clôturée. Vous pourrez finaliser le Procès-Verbal et consigner les décisions avant archivage.</p>
                </div>
                <div class="modal-footer border-0 bg-white px-4 pb-4 pt-0 rounded-bottom-4">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info text-white fw-bold px-4 shadow-sm rounded-pill"><i class="bi bi-stop-fill me-2"></i>Clôturer le Live</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modale : Étape 7 (Terminée) -->
<div class="modal fade" id="stepTermineeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>" method="GET">
                <input type="hidden" name="statut" value="terminee">
                <div class="modal-header bg-light border-0 px-4 py-3 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-check-circle-fill text-dark me-2"></i>Terminer la séance</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-0">La séance sera définitivement clôturée et archivée. Le PV validé sera mis à disposition des membres de l'instance.</p>
                </div>
                <div class="modal-footer border-0 bg-white px-4 pb-4 pt-0 rounded-bottom-4">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-dark fw-bold px-4 shadow-sm rounded-pill"><i class="bi bi-archive-fill me-2"></i>Archiver la séance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
