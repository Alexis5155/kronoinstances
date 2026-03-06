<?php
$isBrouillon = ($seance['statut'] === 'brouillon');
$isDateFixee = ($seance['statut'] === 'date_fixee');
$isOdjValide = ($seance['statut'] === 'odj_valide');
$isDossier   = ($seance['statut'] === 'dossier_disponible');
$isLive      = ($seance['statut'] === 'en_cours');

$canEditInfos = $isBrouillon;
$canEditLieu  = ($isBrouillon || $isDateFixee);
$canAddPoints = ($isBrouillon || $isDateFixee);
$canEditPointsData = ($isBrouillon || $isDateFixee || $isOdjValide);
?>

<style>
/* Harmonisation du design avec les autres vues admin/dashboard */
.card-tool { 
    transition: transform 0.25s ease, box-shadow 0.25s ease; 
    border: 1px solid rgba(0,0,0,0.05) !important; 
}
.card-tool:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
}

/* Accordéons Points ODJ */
.point-item {
    transition: all 0.2s ease;
    border: 1px solid rgba(0,0,0,0.05) !important;
}
.point-item:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.05) !important;
}

/* Quill Editor custom styling pour intégration plus propre */
.ql-toolbar.ql-snow {
    border-radius: 0.5rem 0.5rem 0 0;
    background-color: #f8f9fa;
    border-color: #e9ecef;
}
.ql-container.ql-snow {
    border-radius: 0 0 0.5rem 0.5rem;
    border-color: #e9ecef;
}

/* Animations Drag & Drop */
.sortable-ghost { 
    opacity: 0.5; 
    background-color: #f8f9fa; 
    border: 2px dashed #0d6efd !important; 
    border-radius: 0.5rem;
}
.drag-handle { cursor: grab; }
.drag-handle:active { cursor: grabbing; }

/* Micro-interactions */
.group-hover:hover { background-color: #f8f9fa !important; }
.z-index-2 { z-index: 2; position: relative; }
</style>

<?php if($isLive): ?>
<div class="alert alert-warning border-warning shadow-sm mb-4 rounded-4 d-flex justify-content-between align-items-center">
    <div><strong> La séance est actuellement en cours.</strong> Rejoignez le live pour gérer les votes et rédiger les débats.</div>
    <a href="<?= URLROOT ?>/seances/live/<?= $seance['id'] ?>" class="btn btn-danger fw-bold shadow-sm rounded-pill px-4">Rejoindre le live</a>
</div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <!-- COLONNE GAUCHE (Meta) -->
    <div class="col-lg-4">
        
        <!-- INFOS GÉNÉRALES -->
        <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden card-tool">
            <div class="card-header bg-white py-3 border-0 border-bottom">
                <h6 class="mb-0 fw-bold d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-2 d-inline-flex">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    Détails logistiques
                </h6>
            </div>
            <div class="card-body p-4">
                <?php if ($canEditInfos || $canEditLieu): ?>
                    <form action="<?= URLROOT ?>/seances/edit/<?= $seance['id'] ?>" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing: 0.5px;">Date de la séance</label>
                            <?php if ($canEditInfos): ?>
                                <input type="date" name="date_seance" class="form-control bg-light" value="<?= $seance['date_seance'] ?>">
                            <?php else: ?>
                                <div class="form-control bg-light text-dark border-0"><?= date('d/m/Y', strtotime($seance['date_seance'])) ?></div>
                                <input type="hidden" name="date_seance" value="<?= $seance['date_seance'] ?>">
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing: 0.5px;">Heure</label>
                            <?php if ($canEditInfos): ?>
                                <input type="time" name="heure_debut" class="form-control bg-light" value="<?= date('H:i', strtotime($seance['heure_debut'])) ?>">
                            <?php else: ?>
                                <div class="form-control bg-light text-dark border-0"><?= date('H\hi', strtotime($seance['heure_debut'])) ?></div>
                                <input type="hidden" name="heure_debut" value="<?= date('H:i', strtotime($seance['heure_debut'])) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing: 0.5px;">Lieu</label>
                            <input type="text" name="lieu" class="form-control bg-light" value="<?= htmlspecialchars($seance['lieu'] ?? '') ?>" placeholder="Salle de réunion..." <?= !$canEditLieu ? 'readonly' : '' ?>>
                        </div>
                        <?php if($canEditLieu): ?>
                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm rounded-pill"><i class="bi bi-save me-2"></i>Enregistrer</button>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3 text-primary d-flex align-items-center justify-content-center" style="width:45px; height:45px;">
                            <i class="bi bi-calendar-event fs-5"></i>
                        </div>
                        <div>
                            <div class="small text-muted text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 0.7rem;">Date & Heure</div>
                            <div class="fw-bold fs-5 text-dark"><?= date('d/m/Y', strtotime($seance['date_seance'])) ?> <span class="fw-normal text-muted fs-6">à</span> <?= date('H\hi', strtotime($seance['heure_debut'])) ?></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="bg-secondary bg-opacity-10 p-3 rounded-circle me-3 text-secondary d-flex align-items-center justify-content-center" style="width:45px; height:45px;">
                            <i class="bi bi-geo-alt fs-5"></i>
                        </div>
                        <div>
                            <div class="small text-muted text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 0.7rem;">Lieu</div>
                            <div class="fw-bold text-dark"><?= !empty($seance['lieu']) ? htmlspecialchars($seance['lieu']) : '<em class="text-muted fw-normal">Non précisé</em>' ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CONVOCATIONS -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden card-tool">
            <div class="card-header bg-white py-3 border-0 border-bottom">
                <h6 class="mb-0 fw-bold d-flex align-items-center">
                    <div class="bg-info bg-opacity-10 text-info p-2 rounded-3 me-2 d-inline-flex">
                        <i class="bi bi-envelope"></i>
                    </div>
                    Convocation
                </h6>
            </div>
            <div class="card-body p-4">
                
                <?php if (isset($seance['convocations_envoyees']) && $seance['convocations_envoyees'] == 1): ?>
                    <!-- ÉTAT : CONVOCATIONS ENVOYÉES -->
                    <div class="text-center py-2">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-check2-all fs-2"></i>
                        </div>
                        <h6 class="fw-bold text-success mb-2">Envoyée aux membres</h6>
                        <a href="<?= URLROOT ?>/<?= htmlspecialchars($convocationDoc['chemin_fichier']) ?>" target="_blank" class="btn btn-sm btn-outline-success w-100 fw-bold rounded-pill mt-2">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Voir le document
                        </a>
                    </div>
                <?php else: ?>
                    <!-- ÉTAT : PRÉPARATION -->
                    
                    <?php if ($hasConvocSignee): ?>
                        <!-- PDF déposé -->
                        <div class="d-flex align-items-center p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 mb-4">
                            <i class="bi bi-file-earmark-pdf-fill text-success fs-3 me-3"></i>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-success small">PDF prêt</div>
                                <a href="<?= URLROOT ?>/<?= htmlspecialchars($convocationDoc['chemin_fichier']) ?>" target="_blank" class="text-success text-decoration-none small fw-medium stretched-link">Consulter</a>
                            </div>
                            <?php if ($isBrouillon || $isDateFixee): ?>
                                <button type="button" onclick="showConfirmModal('<?= URLROOT ?>/seances/deleteDoc/<?= $convocationDoc['id'] ?>', 'Supprimer ce fichier ?')" class="btn btn-sm btn-outline-danger border-0 position-relative z-index-2 rounded-circle" style="padding:0; width:30px; height:30px;"><i class="bi bi-trash"></i></button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!$isBrouillon && !$isDateFixee): ?>
                            <?php if (\app\models\User::can('manage_convocations')): ?>
                                <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/sendConvocationsManual/<?= $seance['id'] ?>', 'Envoyer les convocations à tous les membres par e-mail ?')" class="btn btn-info text-white w-100 fw-bold shadow-sm rounded-pill py-2">
                                    <i class="bi bi-send-fill me-2"></i> Diffuser l'ODJ
                                </a>
                            <?php else: ?>
                                <div class="alert alert-secondary py-2 small mb-0 text-center rounded-3"><i class="bi bi-shield-lock me-1"></i> Droits d'envoi requis</div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning py-2 small mb-0 text-center rounded-3"><i class="bi bi-info-circle me-1"></i> Validez l'ODJ pour envoyer</div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <!-- Brouillon & Upload -->
                        <div class="d-flex flex-column gap-3">
                            <a href="<?= URLROOT ?>/seances/generateConvocation/<?= $seance['id'] ?>" class="btn btn-light border d-flex align-items-center justify-content-center text-dark hover-primary shadow-sm rounded-3 py-2">
                                <i class="bi bi-file-earmark-word text-primary fs-5 me-2"></i> Générer brouillon (.odt)
                            </a>
                            
                            <div class="position-relative text-center my-1">
                                <hr class="text-muted opacity-25 m-0">
                                <span class="position-absolute top-50 start-50 translate-middle bg-white px-2 small text-muted" style="font-size:0.7rem;">ET / OU</span>
                            </div>
                            
                            <form action="<?= URLROOT ?>/seances/uploadDoc/<?= $seance['id'] ?>" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="type_doc" value="convocation">
                                <label class="form-label small text-muted fw-bold mb-1">Déposer le PDF final</label>
                                <div class="input-group input-group-sm shadow-sm rounded-3 overflow-hidden border">
                                    <input type="file" name="fichier" class="form-control bg-light border-0" accept=".pdf" required>
                                    <button class="btn btn-primary fw-bold px-3 border-0" type="submit"><i class="bi bi-upload"></i></button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- COLONNE DROITE (Points ODJ) -->
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center">
                <div class="avatar-circle rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm me-3" style="width: 45px; height: 45px; font-size: 1.2rem;">
                    <i class="bi bi-list-ol"></i>
                </div>
                Ordre du Jour
            </h4>
            <?php if($canAddPoints): ?>
                <button class="btn btn-primary fw-bold shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalAddPoint">
                    <i class="bi bi-plus-lg me-2"></i>Ajouter un point
                </button>
            <?php endif; ?>
        </div>

        <!-- CONTENEUR DES POINTS ODJ (Accordéon natif Bootstrap pour gestion ouverture unique + Drag and drop) -->
        <div class="accordion d-flex flex-column gap-3" id="accordionOdj">
            <div id="sortable-points" class="d-flex flex-column gap-3 w-100">
                <?php foreach ($points as $i => $pt): 
                    $isRetire = ($pt['retire'] ?? 0) == 1;
                    
                    $typeBadgeClass = 'bg-secondary bg-opacity-10 text-secondary border-secondary';
                    $typeIcon = 'bi-info-circle';
                    switch(strtolower($pt['type_point'] ?? '')) {
                        case 'information':  $typeBadgeClass = 'bg-info bg-opacity-10 text-info border-info'; $typeIcon = 'bi-info-circle'; break;
                        case 'deliberation': $typeBadgeClass = 'bg-primary bg-opacity-10 text-primary border-primary'; $typeIcon = 'bi-chat-left-text'; break;
                        case 'vote':         $typeBadgeClass = 'bg-danger bg-opacity-10 text-danger border-danger'; $typeIcon = 'bi-box-arrow-in-right'; break;
                        case 'divers':       $typeBadgeClass = 'bg-dark bg-opacity-10 text-dark border-dark'; $typeIcon = 'bi-three-dots'; break;
                    }
                ?>
                <div class="accordion-item rounded-4 shadow-sm border-0 point-item <?= $isRetire ? 'opacity-75 bg-light' : 'bg-white' ?>" data-id="<?= $pt['id'] ?>" id="point-item-<?= $pt['id'] ?>">
                    
                    <!-- En-tête cliquable pour dérouler (Collapse) -->
                    <div class="accordion-header d-flex align-items-center pe-3 py-0 m-0" id="heading_pt_<?= $pt['id'] ?>">
                        
                        <?php if ($canAddPoints && !$isRetire): ?>
                            <div class="px-3 py-4 text-muted drag-handle" title="Déplacer"><i class="bi bi-grip-vertical fs-5"></i></div>
                        <?php else: ?>
                            <div class="px-3 py-4 text-muted"><i class="bi bi-dot fs-5"></i></div>
                        <?php endif; ?>
                        
                        <!-- NOTE : data-bs-parent assure qu'un seul accordéon s'ouvre à la fois -->
                        <button class="accordion-button collapsed shadow-none bg-transparent px-2 py-4 border-0 text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#col_pt_<?= $pt['id'] ?>" aria-expanded="false" aria-controls="col_pt_<?= $pt['id'] ?>">
                            <span class="fw-bold me-3 point-number-display <?= $isRetire ? 'text-decoration-line-through text-muted' : '' ?>" style="font-size: 1.1rem; min-width: 25px;">
                                <?= ($i+1) ?>.
                            </span>
                            <span class="fw-bold me-auto text-truncate <?= $isRetire ? 'text-decoration-line-through text-muted' : '' ?>" style="font-size: 1.1rem; max-width: 50%;">
                                <?= htmlspecialchars($pt['titre']) ?>
                            </span>
                            
                            <!-- Badges alignés à droite juste avant le chevron (me-auto sur le titre + flex-shrink-0 ici) -->
                            <div class="d-flex align-items-center gap-2 flex-shrink-0 me-3">
                                <?php 
                                $pointDocsCount = count(array_filter($documents, fn($d) => $d['point_odj_id'] == $pt['id']));
                                if($pointDocsCount > 0): 
                                ?>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-paperclip me-1"></i><?= $pointDocsCount ?></span>
                                <?php endif; ?>

                                <?php if($isRetire): ?> 
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1"><i class="bi bi-slash-circle me-1"></i>Retiré</span> 
                                <?php else: ?>
                                    <span class="badge <?= $typeBadgeClass ?> border border-opacity-25 px-2 py-1"><i class="bi <?= $typeIcon ?> me-1"></i><?= ucfirst($pt['type_point'] ?? 'Information') ?></span>
                                <?php endif; ?>
                            </div>
                        </button>

                        <div class="dropdown">
                            <button class="btn btn-light rounded-circle shadow-sm border" type="button" data-bs-toggle="dropdown" style="width: 36px; height: 36px; display:flex; align-items:center; justify-content:center;"><i class="bi bi-three-dots-vertical"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                <?php if ($canAddPoints && !$isRetire): ?>
                                    <li><a class="dropdown-item text-danger py-2 fw-medium" href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/deletePoint/<?= $pt['id'] ?>', 'Supprimer définitivement ce point de l\'ODJ ?')"><i class="bi bi-trash me-2"></i>Supprimer</a></li>
                                <?php elseif (!$isBrouillon && !$isDateFixee): ?>
                                    <li><a class="dropdown-item py-2 fw-medium <?= $isRetire ? 'text-success' : 'text-warning' ?>" href="<?= URLROOT ?>/seances/toggleRetirePoint/<?= $pt['id'] ?>">
                                        <i class="bi <?= $isRetire ? 'bi-arrow-counterclockwise' : 'bi-slash-circle' ?> me-2"></i><?= $isRetire ? 'Rétablir' : 'Rayer' ?>
                                    </a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Contenu déroulant -->
                    <?php if (!$isRetire): ?>
                    <div id="col_pt_<?= $pt['id'] ?>" class="accordion-collapse collapse" aria-labelledby="heading_pt_<?= $pt['id'] ?>" data-bs-parent="#accordionOdj">
                        <div class="accordion-body border-top p-4 bg-white rounded-bottom-4">
                            
                            <!-- Header de la zone d'édition avec indicateur de sauvegarde intégré -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase d-flex align-items-center gap-2 m-0" style="letter-spacing: 0.5px;">
                                    <i class="bi bi-text-paragraph text-primary"></i> Exposé des motifs
                                </label>
                                <?php if ($canEditPointsData): ?>
                                    <span id="save-status-<?= $pt['id'] ?>" style="opacity: 0; transition: opacity 0.2s;"></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($canEditPointsData): ?>
                                <div class="mb-4">
                                    <div id="editor-desc-<?= $pt['id'] ?>" style="min-height: 150px; font-size:0.95rem;">
                                        <?= $pt['description'] ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="bg-light p-4 rounded-3 text-dark mb-4" style="font-size:0.95rem; min-height: 80px;">
                                    <?= !empty($pt['description']) ? $pt['description'] : '<em class="text-muted">Aucun exposé des motifs rédigé.</em>' ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3 mt-5">
                                <label class="form-label small fw-bold text-muted text-uppercase d-flex align-items-center gap-2 mb-0" style="letter-spacing: 0.5px;">
                                    <i class="bi bi-paperclip text-info"></i> Documents & Annexes
                                </label>
                                <?php if ($canEditPointsData): ?>
                                    <button class="btn btn-sm btn-light border fw-bold text-primary rounded-pill px-3 shadow-sm" onclick="openAddDocModal(<?= $pt['id'] ?>)">
                                        <i class="bi bi-plus-lg me-1"></i> Ajouter
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <?php 
                            $pointDocs = array_filter($documents, fn($d) => $d['point_odj_id'] == $pt['id']);
                            if (!empty($pointDocs)): 
                            ?>
                                <div class="row g-3">
                                    <?php foreach($pointDocs as $doc): 
                                        $ext = strtolower(pathinfo($doc['chemin_fichier'], PATHINFO_EXTENSION));
                                        $iconExt = 'bi-file-earmark';
                                        $colorExt = 'text-secondary';
                                        if(in_array($ext, ['pdf'])) { $iconExt = 'bi-filetype-pdf'; $colorExt = 'text-danger'; }
                                        elseif(in_array($ext, ['doc','docx','odt'])) { $iconExt = 'bi-filetype-doc'; $colorExt = 'text-primary'; }
                                        elseif(in_array($ext, ['xls','xlsx','csv'])) { $iconExt = 'bi-filetype-xls'; $colorExt = 'text-success'; }
                                        elseif(in_array($ext, ['jpg','jpeg','png'])) { $iconExt = 'bi-file-image'; $colorExt = 'text-info'; }
                                    ?>
                                    <div class="col-md-6 col-xl-4">
                                        <div class="d-flex align-items-center bg-light bg-opacity-50 p-2 rounded-3 border border-light shadow-sm position-relative group-hover h-100">
                                            <div class="bg-white p-2 rounded-3 shadow-sm me-3">
                                                <i class="bi <?= $iconExt ?> <?= $colorExt ?> fs-4"></i>
                                            </div>
                                            <div class="flex-grow-1 text-truncate me-2">
                                                <a href="<?= URLROOT ?>/<?= htmlspecialchars($doc['chemin_fichier']) ?>" target="_blank" class="text-decoration-none fw-bold text-dark stretched-link text-truncate d-block" style="font-size:0.85rem;" title="<?= htmlspecialchars($doc['nom'] ?: basename($doc['chemin_fichier'])) ?>">
                                                    <?= htmlspecialchars($doc['nom'] ?: basename($doc['chemin_fichier'])) ?>
                                                </a>
                                                <div class="small text-muted text-uppercase fw-medium" style="font-size:0.65rem; letter-spacing: 0.5px;"><?= strtoupper($ext) ?></div>
                                            </div>
                                            <?php if ($canEditPointsData): ?>
                                            <button type="button" onclick="showConfirmModal('<?= URLROOT ?>/seances/deleteDoc/<?= $doc['id'] ?>', 'Supprimer ce document ?')" class="btn btn-sm btn-white text-danger border-0 position-relative z-index-2 shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width:28px; height:28px; padding:0;" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center p-4 bg-light rounded-4 mb-2 text-muted small border border-dashed">
                                    <i class="bi bi-inbox fs-3 d-block mb-1 opacity-25"></i>
                                    Aucun document rattaché.
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if(empty($points)): ?>
            <div class="text-center p-5 bg-white rounded-4 border border-dashed text-muted mt-2 shadow-sm">
                <i class="bi bi-card-list display-1 d-block mb-3 opacity-25"></i>
                <h5 class="fw-bold text-dark">L'ordre du jour est vide</h5>
                <p class="mb-4">Commencez par ajouter les points qui seront discutés lors de cette séance.</p>
                <?php if($canAddPoints): ?>
                    <button class="btn btn-primary fw-bold shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalAddPoint">
                        <i class="bi bi-plus-lg me-2"></i>Ajouter un premier point
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>


<!-- ========================================== -->
<!-- MODALES -->
<!-- ========================================== -->

<!-- Modal Ajouter un point -->
<?php if($canAddPoints): ?>
<div class="modal fade" id="modalAddPoint" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light px-4 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Nouveau point</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= URLROOT ?>/seances/addPoint/<?= $seance['id'] ?>" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Titre du point <span class="text-danger">*</span></label>
                        <input type="text" name="titre" class="form-control bg-light" placeholder="Ex: Approbation du budget..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Type de point</label>
                        <select name="type_point" class="form-select bg-light">
                            <option value="information">Information</option>
                            <option value="deliberation">Délibération</option>
                            <option value="vote">Vote</option>
                            <option value="divers">Questions diverses</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm rounded-pill">Créer le point</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Ajouter un document -->
<?php if ($canEditPointsData): ?>
<div class="modal fade" id="modalAddDoc" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light px-4 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-file-earmark-arrow-up-fill text-info me-2"></i>Ajouter une annexe</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= URLROOT ?>/seances/uploadDoc/<?= $seance['id'] ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="point_odj_id" id="modal_doc_point_id" value="">
                <input type="hidden" name="type_doc" value="annexe">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Fichier <span class="text-danger">*</span></label>
                        <input type="file" name="fichier" class="form-control bg-light" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Nom d'affichage (facultatif)</label>
                        <input type="text" name="nom" class="form-control bg-light" placeholder="Ex: Annexe financière V2">
                        <div class="form-text small">Laissé vide, le nom original du fichier sera utilisé.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info text-white fw-bold px-4 shadow-sm rounded-pill"><i class="bi bi-upload me-2"></i>Déposer le fichier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddDocModal(pointId) {
    document.getElementById('modal_doc_point_id').value = pointId;
    new bootstrap.Modal(document.getElementById('modalAddDoc')).show();
}
</script>
<?php endif; ?>


<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {

    // 1. MISE À JOUR VISUELLE DES NUMÉROS D'ORDRE
    function updateOrderNumbers() {
        const points = document.querySelectorAll('.point-item');
        let index = 1;
        points.forEach(pt => {
            const displaySpan = pt.querySelector('.point-number-display');
            if (displaySpan) {
                displaySpan.innerText = index + '.';
            }
            index++;
        });
    }

    // 2. INITIALISATION SORTABLEJS
    const sortableContainer = document.getElementById('sortable-points');
    if (sortableContainer && typeof Sortable !== 'undefined') {
        new Sortable(sortableContainer, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function (evt) {
                updateOrderNumbers(); // Mise à jour visuelle instantanée en JS
                
                let order = [];
                document.querySelectorAll('.point-item').forEach(el => {
                    if (el.getAttribute('data-id')) {
                        order.push(el.getAttribute('data-id'));
                    }
                });
                
                if(order.length > 0) {
                    fetch('<?= URLROOT ?>/seances/updateOrder', {
                        method: 'POST', 
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order: order })
                    }).catch(err => console.error("Erreur mise à jour ordre:", err));
                }
            }
        });
    }

    // 3. INITIALISATION DE QUILL & SAUVEGARDE ROBUSTE
    <?php if ($canEditPointsData): ?>
    
    setTimeout(function() {
        if (typeof Quill === 'undefined') {
            console.error("Quill JS non détecté.");
            return;
        }

        <?php foreach ($points as $pt): ?>
            <?php if (!isset($pt['retire']) || $pt['retire'] != 1): ?>
            
            let editorEl_<?= $pt['id'] ?> = document.getElementById('editor-desc-<?= $pt['id'] ?>');
            
            if (editorEl_<?= $pt['id'] ?>) {
                let q_<?= $pt['id'] ?> = new Quill(editorEl_<?= $pt['id'] ?>, { 
                    theme: 'snow', 
                    placeholder: 'Rédigez l\'exposé des motifs ici...',
                    modules: { 
                        toolbar: [
                            [{ 'header': [3, 4, false] }],
                            ['bold', 'italic', 'underline', 'strike'], 
                            [{'list':'ordered'}, {'list':'bullet'}], 
                            ['link', 'clean']
                        ] 
                    }
                });
                
                let timeout_<?= $pt['id'] ?>;
                let isDirty_<?= $pt['id'] ?> = false;
                const statusEl_<?= $pt['id'] ?> = document.getElementById('save-status-<?= $pt['id'] ?>');

                function savePoint_<?= $pt['id'] ?>() {
                    if (!isDirty_<?= $pt['id'] ?>) return;
                    
                    // État "Enregistrement en cours..."
                    statusEl_<?= $pt['id'] ?>.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> <span class="opacity-75">Enregistrement...</span>';
                    statusEl_<?= $pt['id'] ?>.className = "small fw-bold text-muted";
                    statusEl_<?= $pt['id'] ?>.style.opacity = '1';

                    fetch('<?= URLROOT ?>/seances/updateDescription/<?= $pt['id'] ?>', {
                        method: 'POST', 
                        headers: { 'Content-Type': 'application/json' },
                        // keepalive permet de s'assurer que la requête passe même si on quitte la page (beforeunload)
                        keepalive: true,
                        body: JSON.stringify({ description: q_<?= $pt['id'] ?>.root.innerHTML })
                    }).then(res => {
                        if(res.ok) {
                            isDirty_<?= $pt['id'] ?> = false;
                            // État "Enregistré"
                            statusEl_<?= $pt['id'] ?>.innerHTML = '<i class="bi bi-check2-all"></i> Enregistré';
                            statusEl_<?= $pt['id'] ?>.className = "small fw-bold text-success";
                            setTimeout(() => { 
                                if (!isDirty_<?= $pt['id'] ?>) statusEl_<?= $pt['id'] ?>.style.opacity = '0'; 
                            }, 3000);
                        }
                    }).catch(err => {
                        statusEl_<?= $pt['id'] ?>.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Erreur';
                        statusEl_<?= $pt['id'] ?>.className = "small fw-bold text-danger";
                    });
                }

                // Sauvegarde après avoir arrêté de taper
                q_<?= $pt['id'] ?>.on('text-change', function() {
                    isDirty_<?= $pt['id'] ?> = true;
                    statusEl_<?= $pt['id'] ?>.innerHTML = '<i class="bi bi-pencil-square"></i> Modification...';
                    statusEl_<?= $pt['id'] ?>.className = "small text-muted";
                    statusEl_<?= $pt['id'] ?>.style.opacity = '1';

                    clearTimeout(timeout_<?= $pt['id'] ?>);
                    timeout_<?= $pt['id'] ?> = setTimeout(savePoint_<?= $pt['id'] ?>, 1500); 
                });

                // SAUVEGARDE TRANSPARENTE ET ROBUSTE :
                
                // 1. Sur la perte de focus (clic ailleurs)
                q_<?= $pt['id'] ?>.root.addEventListener('blur', function() {
                    clearTimeout(timeout_<?= $pt['id'] ?>);
                    savePoint_<?= $pt['id'] ?>();
                });

                // 2. À la fermeture ou au rechargement de la page
                window.addEventListener('beforeunload', function() {
                    if (isDirty_<?= $pt['id'] ?>) {
                        savePoint_<?= $pt['id'] ?>();
                    }
                });
            }
            
            <?php endif; ?>
        <?php endforeach; ?>
        
    }, 500);
    
    <?php endif; ?>
});
</script>
