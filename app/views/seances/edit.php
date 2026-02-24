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
    'brouillon'          => ['label' => 'Brouillon',         'class' => 'bg-secondary', 'icon' => 'bi-pencil-square'],
    'date_fixee'         => ['label' => 'Date fixée',        'class' => 'bg-info text-dark', 'icon' => 'bi-calendar-check'],
    'odj_valide'         => ['label' => 'ODJ validé',        'class' => 'bg-primary', 'icon' => 'bi-list-check'],
    'dossier_disponible' => ['label' => 'Dossier complet',   'class' => 'bg-success', 'icon' => 'bi-folder-check'],
    'en_cours'           => ['label' => 'Séance en cours',   'class' => 'bg-warning text-dark', 'icon' => 'bi-play-circle-fill'],
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
// 1. Recherche du document de type "convocation"
$convocationDoc = null;
foreach($documents as $doc) {
    if (isset($doc['type_doc']) && $doc['type_doc'] === 'convocation') {
        $convocationDoc = $doc;
        break;
    }
}
$hasConvocSignee = ($convocationDoc !== null);

// 2. Variables de permissions dynamiques
$isOdjEditable = in_array($seance['statut'], ['brouillon', 'date_fixee']); 
$isDossierEditable = in_array($seance['statut'], ['brouillon', 'date_fixee', 'odj_valide']);
?>

<div class="container py-4">
    
    <!-- TOP ACTIONS : Tag et Aperçu -->
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
                    
                    <!-- BOUTONS D'ACTION DU WORKFLOW -->
                    <div class="d-flex justify-content-md-end gap-2 flex-wrap mt-3">
                        
                        <!-- ÉTAPE 0 : BROUILLON -->
                        <?php if ($seance['statut'] === 'brouillon'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=date_fixee', 'Confirmer la date de la séance ? Celle-ci deviendra visible pour les membres.')" class="btn btn-info fw-bold shadow-sm text-dark">
                                Étape 1 : Confirmer la date <i class="bi bi-arrow-right ms-1"></i>
                            </a>

                        <!-- ÉTAPE 1 : DATE FIXÉE -->
                        <?php elseif ($seance['statut'] === 'date_fixee'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=brouillon', 'Repasser en brouillon ? La séance ne sera plus visible par les membres.')" class="btn btn-outline-secondary fw-bold shadow-sm">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Brouillon
                            </a>
                            <?php 
                            if ($hasConvocSignee) {
                                $msgPublierOdj = "Valider l\'Ordre du jour ? Un mail va être envoyé aux membres avec l\'ODJ et la convocation sera mise à disposition sur leur espace.";
                            } else {
                                $msgPublierOdj = "ATTENTION : Aucune convocation n\'a été ajoutée ! Êtes-vous sûr de vouloir publier l\'Ordre du jour sans la convocation ?";
                            }
                            ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=odj_valide', '<?= $msgPublierOdj ?>')" class="btn btn-primary fw-bold shadow-sm">
                                Étape 2 : Publier l'ODJ <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                            
                        <!-- ÉTAPE 2 : ODJ VALIDÉ -->
                        <?php elseif ($seance['statut'] === 'odj_valide'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=date_fixee', 'Annuler la publication de l\'ODJ ? Les membres ne verront plus les points.')" class="btn btn-outline-secondary fw-bold shadow-sm">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Rétrograder
                            </a>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=dossier_disponible', 'Publier le dossier complet ? Les membres auront accès aux exposés et aux pièces jointes.')" class="btn btn-success fw-bold shadow-sm">
                                Étape 3 : Publier le dossier <i class="bi bi-arrow-right ms-1"></i>
                            </a>

                        <!-- ÉTAPE 3 : DOSSIER PUBLIÉ -->
                        <?php elseif ($seance['statut'] === 'dossier_disponible'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=odj_valide', 'Masquer le dossier complet ? Les PJ et les exposés des motifs seront à nouveau masqués.')" class="btn btn-outline-secondary fw-bold shadow-sm">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Rétrograder
                            </a>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=en_cours', 'Démarrer la séance maintenant et ouvrir le bureau en direct ?')" class="btn btn-warning fw-bold shadow-sm text-dark">
                                Démarrer la séance <i class="bi bi-play-fill ms-1"></i>
                            </a>

                        <!-- ÉTAPE 4 : SÉANCE EN COURS (LIVE) -->
                        <?php elseif ($seance['statut'] === 'en_cours'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=dossier_disponible', 'Annuler le démarrage ? La séance repassera en mode préparation.')" class="btn btn-outline-secondary fw-bold shadow-sm">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Quitter Live
                            </a>
                            <a href="<?= URLROOT ?>/seances/live/<?= $seance['id'] ?>" class="btn btn-danger fw-bold shadow-sm">
                                <i class="bi bi-record-circle-fill me-1" style="animation: pulse-red 2s infinite;"></i>Reprendre Live
                            </a>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=terminee', 'Clôturer la séance définitivement ?')" class="btn btn-dark fw-bold shadow-sm">
                                <i class="bi bi-stop-fill me-1"></i>Clôturer
                            </a>

                        <!-- ÉTAPE 5 : TERMINÉE -->
                        <?php elseif ($seance['statut'] === 'terminee'): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/changeStatut/<?= $seance['id'] ?>?statut=en_cours', 'Rouvrir la séance ? Vous pourrez à nouveau éditer les votes et le PV.')" class="btn btn-outline-danger fw-bold shadow-sm">
                                <i class="bi bi-unlock-fill me-1"></i> Rouvrir la séance
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <hr class="text-muted my-4 opacity-25">
            
            <!-- BARRE DE PROGRESSION VISUELLE (STEPPER) -->
            <?php 
            $steps = [
                'brouillon'          => 'Brouillon',
                'date_fixee'         => 'Date fixée',
                'odj_valide'         => 'ODJ validé',
                'dossier_disponible' => 'Dossier',
                'en_cours'           => 'En Live',
                'terminee'           => 'Terminée'
            ];
            $currentStatusIndex = array_search($seance['statut'], array_keys($steps));
            // Pourcentage de progression de la ligne bleue
            $progressPct = ($currentStatusIndex / (count($steps) - 1)); 
            ?>
            <div class="position-relative mt-2 mb-2">
                <!-- Ligne grise de fond -->
                <div class="position-absolute" style="top: 15px; left: 40px; right: 40px; height: 3px; background-color: #e9ecef; z-index: 1;"></div>
                
                <!-- Ligne bleue de progression dynamique -->
                <div class="position-absolute rounded" style="top: 15px; left: 40px; width: calc((100% - 80px) * <?= $progressPct ?>); height: 3px; background-color: #0d6efd; z-index: 2; transition: width 0.5s ease;"></div>
                
                <!-- Les points -->
                <div class="d-flex justify-content-between position-relative" style="z-index: 3;">
                    <?php 
                    $stepIndex = 0;
                    foreach ($steps as $key => $label): 
                        $isCompleted = $stepIndex < $currentStatusIndex;
                        $isActive = $stepIndex === $currentStatusIndex;
                        
                        $circleClass = '';
                        $borderStyle = '';
                        
                        if ($isCompleted) {
                            $circleClass = 'bg-primary border-primary text-white';
                        } elseif ($isActive) {
                            $circleClass = 'bg-primary border-primary text-white shadow';
                        } else {
                            $circleClass = 'bg-white text-muted';
                            $borderStyle = 'border-color: #ced4da !important;';
                        }
                        
                        $iconOrNum = $isCompleted ? '<i class="bi bi-check-lg"></i>' : ($stepIndex + 1);
                        $labelClass = ($isCompleted || $isActive) ? 'text-dark fw-bold' : 'text-muted opacity-75';
                    ?>
                        <div class="text-center" style="width: 80px;">
                            <div class="rounded-circle border border-2 d-flex align-items-center justify-content-center mx-auto mb-2 <?= $circleClass ?>" 
                                 style="width: 32px; height: 32px; transition: all 0.3s ease; <?= $borderStyle ?>">
                                <?= $iconOrNum ?>
                            </div>
                            <div class="small <?= $labelClass ?>" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px;"><?= $label ?></div>
                        </div>
                    <?php 
                        $stepIndex++;
                    endforeach; 
                    ?>
                </div>
            </div>

        </div>
    </div>

    <!-- ORDRE DU JOUR (ÉDITION) -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="bi bi-list-ol me-2 text-primary"></i>Édition de l'Ordre du Jour</h4>
        <?php if ($isOdjEditable): ?>
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addPointModal">
                <i class="bi bi-plus-lg me-1"></i>Nouveau point
            </button>
        <?php endif; ?>
    </div>
    
    <div class="row g-4">
        <div class="col-lg-8">
            <?php if (empty($points)): ?>
                <div class="card border-0 shadow-sm text-center py-5 text-muted border-dashed">
                    <i class="bi bi-journal-x fs-1 opacity-25 d-block mb-3"></i>
                    L'ordre du jour est vide.<br>
                    <small>Ajoutez des points pour structurer la séance.</small>
                </div>
            <?php else: ?>
                <div class="accordion shadow-sm" id="accordionODJEdit">
                    <?php foreach ($points as $i => $pt): 
                        // Exclure la convocation pour ne compter que les vraies PJ du point
                        $docsPoint = array_filter($documents, fn($d) => $d['point_odj_id'] == $pt['id'] && (!isset($d['type_doc']) || $d['type_doc'] !== 'convocation'));
                        $tcfg = $typeCfg[$pt['type_point']] ?? ['label' => $pt['type_point'], 'class' => 'bg-secondary'];
                    ?>
                    
                    <!-- data-id est utilisé par SortableJS -->
                    <div class="accordion-item border-0 border-bottom bg-white" data-id="<?= $pt['id'] ?>">
                        <div class="accordion-header d-flex align-items-center pe-3">
                            <!-- Poignée Drag & Drop (Toujours active sauf séance terminée) -->
                            <?php if ($seance['statut'] !== 'terminee'): ?>
                                <div class="px-3 py-3 text-muted drag-handle" style="cursor: grab;" title="Glisser pour réorganiser">
                                    <i class="bi bi-grip-vertical fs-5"></i>
                                </div>
                            <?php else: ?>
                                <div class="px-3 py-3 text-muted">
                                    <i class="bi bi-dot fs-5"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Bouton Accordéon -->
                            <button class="accordion-button collapsed flex-grow-1 border-0 shadow-none bg-transparent px-2" type="button" data-bs-toggle="collapse" data-bs-target="#col_edit_<?= $pt['id'] ?>" style="width: auto;">
                                <span class="fw-bold text-dark text-truncate" style="max-width: 75%;"><?= htmlspecialchars($pt['titre']) ?></span>
                                <span class="badge <?= $tcfg['class'] ?> ms-3 small fw-normal"><?= $tcfg['label'] ?></span>
                                <?php if(count($docsPoint) > 0): ?>
                                    <span class="badge bg-light text-dark border ms-2"><i class="bi bi-paperclip me-1"></i><?= count($docsPoint) ?></span>
                                <?php endif; ?>
                            </button>
                            
                            <!-- Bouton Supprimer FIXE ET TOUJOURS VISIBLE -->
                            <?php if ($isOdjEditable): ?>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/deletePoint/<?= $pt['id'] ?>', 'Supprimer ce point et ses documents associés ?')" class="btn btn-sm btn-outline-danger border-0 ms-2" title="Supprimer le point">
                                <i class="bi bi-trash fs-6"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <div id="col_edit_<?= $pt['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#accordionODJEdit">
                            <div class="accordion-body bg-light border-top border-light">
                                
                                <!-- EXPOSÉ DES MOTIFS (WYSIWYG) -->
                                <div class="mb-4 bg-white p-3 rounded shadow-sm border">
                                    <h6 class="fw-bold text-secondary mb-2" style="font-size: 0.85rem; text-transform: uppercase;">Exposé des motifs</h6>
                                    
                                    <?php if ($isDossierEditable): ?>
                                        <!-- Conteneur pour l'éditeur Quill -->
                                        <div id="editor-<?= $pt['id'] ?>" style="height: 150px; background: #fff;">
                                            <?= $pt['description'] ?> <!-- On affiche le HTML tel quel -->
                                        </div>
                                        
                                        <div class="d-flex justify-content-end align-items-center mt-2">
                                            <span id="save-msg-<?= $pt['id'] ?>" class="text-success small fw-bold me-3 d-none">
                                                <i class="bi bi-check-lg me-1"></i>Sauvegardé
                                            </span>
                                            <button class="btn btn-sm btn-primary fw-bold" onclick="saveDescription(<?= $pt['id'] ?>)">
                                                <i class="bi bi-save me-1"></i>Enregistrer le texte
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="rich-text-container">
                                            <?= !empty(trim(strip_tags($pt['description']))) ? $pt['description'] : '<em class="text-muted">Aucun exposé des motifs fourni.</em>' ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- PIÈCES JOINTES -->
                                <div class="d-flex justify-content-between align-items-center mb-2 mt-4">
                                    <h6 class="fw-bold mb-0 text-secondary" style="font-size: 0.85rem; text-transform: uppercase;">Pièces jointes</h6>
                                    <?php if ($isDossierEditable): ?>
                                    <button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.75rem;" onclick="openDocModal(<?= $pt['id'] ?>)">
                                        <i class="bi bi-upload me-1"></i>Ajouter un document
                                    </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($docsPoint)): ?>
                                    <div class="list-group list-group-flush border rounded overflow-hidden shadow-sm">
                                        <?php foreach($docsPoint as $doc): 
                                            $icon = getFileIcon($doc['chemin_fichier']);
                                        ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center p-3 bg-white border-bottom border-light">
                                                <a href="<?= URLROOT ?>/<?= $doc['chemin_fichier'] ?>" target="_blank" class="text-decoration-none text-dark d-flex align-items-center flex-grow-1">
                                                    <div class="bg-light p-2 rounded d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                        <i class="bi <?= $icon['class'] ?> <?= $icon['color'] ?> fs-5"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold small mb-0"><?= htmlspecialchars($doc['nom']) ?></div>
                                                        <div class="text-muted" style="font-size:0.65rem;">Ajouté le <?= date('d/m/Y', strtotime($doc['uploaded_at'] ?? 'now')) ?></div>
                                                    </div>
                                                </a>
                                                <?php if ($isDossierEditable): ?>
                                                <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/deleteDoc/<?= $doc['id'] ?>', 'Retirer ce document ?')" class="btn btn-sm btn-light text-danger border rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                                    <i class="bi bi-x-lg"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-white border text-center small text-muted py-3 mb-0 shadow-sm">
                                        Aucun document rattaché à ce point.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            
            <!-- ENCART CONVOCATION OFFICIELLE -->
            <div class="card border-0 shadow-sm mb-4 border-top border-4 border-warning">
                <div class="card-header bg-white border-0 pt-3 px-3 pb-0">
                    <h6 class="fw-bold mb-0 text-dark text-uppercase"><i class="bi bi-file-earmark-check text-warning me-2"></i>Convocation</h6>
                </div>
                <div class="card-body px-3 pb-3">
                    
                    <?php if ($isOdjEditable): ?>
                        <?php if (!$hasConvocSignee): ?>
                            <!-- ÉTAPE 1 : Génération de l'ODT -->
                            <div class="mb-3 border-bottom pb-3 mt-2">
                                <p class="small text-muted mb-2">Générez la base de votre convocation pour mise au parapheur.</p>
                                <a href="<?= URLROOT ?>/seances/generateConvocation/<?= $seance['id'] ?>" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="bi bi-file-earmark-word me-1"></i> Générer via le modèle
                                </a>
                            </div>
                            
                            <!-- ÉTAPE 2 : Upload du PDF en utilisant nativement uploadDoc -->
                            <div>
                                <p class="small text-muted mb-2">Une fois signée, téléversez la version finale au format PDF.</p>
                                <form action="<?= URLROOT ?>/seances/uploadDoc/<?= $seance['id'] ?>" method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                                    <input type="hidden" name="type_doc" value="convocation">
                                    <input type="hidden" name="nom" value="Convocation officielle signée">
                                    <input type="file" name="fichier" class="form-control form-control-sm bg-light" accept=".pdf" required>
                                    <button type="submit" class="btn btn-sm btn-primary fw-bold"><i class="bi bi-upload"></i></button>
                                </form>
                            </div>
                        <?php else: ?>
                            <!-- CONVOCATION UPLOADÉE MAIS ENCORE ÉDITABLE (avant ODJ validé) -->
                            <div class="alert alert-success bg-success bg-opacity-10 border-0 d-flex align-items-center mt-3 mb-0">
                                <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                                <div>
                                    <div class="fw-bold text-dark small">Convocation prête</div>
                                    <a href="<?= URLROOT ?>/<?= $convocationDoc['chemin_fichier'] ?>" target="_blank" class="small text-decoration-none text-success fw-bold">Voir le PDF</a>
                                </div>
                                <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/deleteDoc/<?= $convocationDoc['id'] ?>', 'Retirer cette convocation ?')" class="btn btn-sm text-danger ms-auto px-1" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- ZONE FIGÉE (Dès ODJ Validé) -->
                        <?php if ($hasConvocSignee): ?>
                            <div class="alert alert-secondary bg-light border border-secondary border-opacity-25 d-flex align-items-center mt-2 mb-0">
                                <i class="bi bi-lock-fill text-muted fs-4 me-3"></i>
                                <div>
                                    <div class="fw-bold text-dark small">Zone figée</div>
                                    <a href="<?= URLROOT ?>/<?= $convocationDoc['chemin_fichier'] ?>" target="_blank" class="small text-decoration-none text-primary fw-bold">Consulter le PDF publié</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning border-0 small mb-0 mt-2">
                                <i class="bi bi-exclamation-triangle me-1"></i> Aucune convocation n'a été déposée avant la publication.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                </div>
            </div>

            <!-- RAPPEL DES MEMBRES -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-3 px-3 pb-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-muted text-uppercase">Convoqués</h6>
                    <span class="badge bg-light text-dark border"><?= count($membres) ?> membres</span>
                </div>
                <div class="card-body px-3 pb-3 pt-0" style="max-height: 250px; overflow-y: auto;">
                    <?php if (empty($membres)): ?>
                        <p class="text-muted small text-center py-3">Aucun membre rattaché.</p>
                    <?php else: ?>
                        <?php
                        $admins    = array_filter($membres, fn($m) => $m['college'] === 'administration');
                        $personnel = array_filter($membres, fn($m) => $m['college'] === 'personnel');
                        ?>
                        <?php if (!empty($admins)): ?>
                            <div class="small fw-bold text-primary mt-2 mb-2 border-bottom pb-1" style="font-size: 0.75rem;">Collège Administration</div>
                            <ul class="list-unstyled mb-3 small">
                                <?php foreach ($admins as $m): ?>
                                    <li class="d-flex justify-content-between align-items-center py-1">
                                        <span class="text-truncate pe-2"><i class="bi bi-person text-muted me-1"></i><?= htmlspecialchars(strtoupper($m['nom']) . ' ' . $m['prenom']) ?></span>
                                        <span class="badge <?= $m['type_mandat'] === 'titulaire' ? 'bg-dark' : 'bg-secondary' ?> bg-opacity-75" style="font-size: 0.65rem;"><?= $m['type_mandat'] === 'titulaire' ? 'T' : 'S' ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!empty($personnel)): ?>
                            <div class="small fw-bold text-success mb-2 border-bottom pb-1" style="font-size: 0.75rem;">Collège Personnel</div>
                            <ul class="list-unstyled mb-0 small">
                                <?php foreach ($personnel as $m): ?>
                                    <li class="d-flex justify-content-between align-items-center py-1">
                                        <span class="text-truncate pe-2"><i class="bi bi-person text-muted me-1"></i><?= htmlspecialchars(strtoupper($m['nom']) . ' ' . $m['prenom']) ?></span>
                                        <span class="badge <?= $m['type_mandat'] === 'titulaire' ? 'bg-dark' : 'bg-secondary' ?> bg-opacity-75" style="font-size: 0.65rem;"><?= $m['type_mandat'] === 'titulaire' ? 'T' : 'S' ?></span>
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

<!-- MODALE D'AJOUT DE DOCUMENT -->
<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= URLROOT ?>/seances/uploadDoc/<?= $seance['id'] ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="point_odj_id" id="upload_point_id" value="">
                <input type="hidden" name="type_doc" value="annexe">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cloud-arrow-up text-primary me-2"></i>Ajouter un document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Fichier <span class="text-danger">*</span></label>
                        <input type="file" name="fichier" class="form-control form-control-lg bg-light" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-muted text-uppercase">Titre d'affichage (optionnel)</label>
                        <input type="text" name="nom" class="form-control" placeholder="Laisser vide pour utiliser le nom du fichier">
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Téléverser</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODALE DE CRÉATION DE POINT -->
<div class="modal fade" id="addPointModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= URLROOT ?>/seances/addPoint/<?= $seance['id'] ?>" method="POST">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-journal-plus me-2 text-primary"></i>Créer un point</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Titre du point <span class="text-danger">*</span></label>
                        <input type="text" name="titre" class="form-control" placeholder="Ex : Budget primitif..." required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Nature du point</label>
                            <select name="type_point" class="form-select">
                                <option value="information">Information simple</option>
                                <option value="deliberation">Délibération</option>
                                <option value="vote">Soumis au vote</option>
                                <option value="divers">Questions diverse</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Direction d'origine</label>
                            <input type="text" name="direction_origine" class="form-control" placeholder="Ex: RH, Finances...">
                        </div>
                    </div>
                    <!-- Le textarea description a été volontairement supprimé ici -->
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Créer</button>
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

/* Styles repris de la vue consultation pour l'affichage de texte enrichi quand figé */
.rich-text-container {
    font-size: 0.875em;
    color: #6c757d;
    margin-bottom: 1rem;
}
.rich-text-container p, .rich-text-container ul, .rich-text-container ol { margin-bottom: 0.5rem; }
.rich-text-container p:last-child, .rich-text-container ul:last-child { margin-bottom: 0; }
.rich-text-container a { color: #0d6efd; }
</style>

<!-- Librairies externes JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
// 1. GESTION DE LA MODALE DE CONFIRMATION GLOBALE
function showConfirmModal(url, message) {
    document.getElementById('confirmModalText').innerText = message;
    document.getElementById('confirmModalBtn').href = url;
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}

// Modale d'upload
function openDocModal(pointId) {
    document.getElementById('upload_point_id').value = pointId;
    new bootstrap.Modal(document.getElementById('uploadDocModal')).show();
}

// 2. INITIALISATION DES ÉDITEURS DE TEXTE (QuillJS)
const quillEditors = {};
document.addEventListener("DOMContentLoaded", function() {
    // Initialiser un éditeur pour chaque point de l'ODJ
    document.querySelectorAll('[id^="editor-"]').forEach(function(el) {
        let pointId = el.id.split('-')[1];
        quillEditors[pointId] = new Quill('#editor-' + pointId, {
            theme: 'snow',
            placeholder: 'Rédigez l\'exposé des motifs ici...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'color': [] }],
                    ['clean']
                ]
            }
        });
    });

    // 3. INITIALISATION DU DRAG & DROP (SortableJS)
    const accordionODJ = document.getElementById('accordionODJEdit');
    if (accordionODJ) {
        new Sortable(accordionODJ, {
            handle: '.drag-handle', // Uniquement cliquable sur les petits points à gauche
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function (evt) {
                // Récupérer le nouvel ordre des data-id
                const newOrder = [];
                accordionODJ.querySelectorAll('.accordion-item').forEach(function(item) {
                    newOrder.push(item.getAttribute('data-id'));
                });

                // Envoyer en AJAX
                fetch('<?= URLROOT ?>/seances/updateOrder', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order: newOrder })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // Optionnel: Afficher un toast de succès discret
                        console.log("Ordre sauvegardé");
                    }
                });
            }
        });
    }
});

// 4. SAUVEGARDE DU TEXTE ENRICHI
function saveDescription(pointId) {
    // On récupère le code HTML généré par Quill
    const htmlContent = document.querySelector('#editor-' + pointId + ' .ql-editor').innerHTML;
    
    fetch('<?= URLROOT ?>/seances/updateDescription/' + pointId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ description: htmlContent })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const msg = document.getElementById('save-msg-' + pointId);
            msg.classList.remove('d-none');
            setTimeout(() => { msg.classList.add('d-none'); }, 3000);
        }
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
