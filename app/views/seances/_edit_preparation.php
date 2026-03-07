<?php
$isBrouillon = ($seance['statut'] === 'brouillon');
$isDateFixee = ($seance['statut'] === 'date_fixee');
$isOdjValide = ($seance['statut'] === 'odj_valide');
$isDossier   = ($seance['statut'] === 'dossier_disponible');
$isLive      = ($seance['statut'] === 'en_cours');

$canEditInfos      = $isBrouillon;
$canEditLieu       = ($isBrouillon || $isDateFixee);
$canAddPoints      = ($isBrouillon || $isDateFixee);
$canEditPointsData = ($isBrouillon || $isDateFixee || $isOdjValide);
?>

<style>
.card-tool {
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    border: 1px solid rgba(0,0,0,0.05) !important;
}
.card-tool:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
}
.point-item {
    transition: all 0.2s ease;
    border: 1px solid rgba(0,0,0,0.05) !important;
}
.point-item:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.05) !important; }

.ql-toolbar.ql-snow {
    border-radius: 0.5rem 0.5rem 0 0;
    background-color: #f8f9fa;
    border-color: #e9ecef;
}
.ql-container.ql-snow {
    border-radius: 0 0 0.5rem 0.5rem;
    border-color: #e9ecef;
}
.sortable-ghost {
    opacity: 0.5;
    background-color: #f8f9fa;
    border: 2px dashed #0d6efd !important;
    border-radius: 0.5rem;
}
.drag-handle { cursor: grab; }
.drag-handle:active { cursor: grabbing; }
.group-hover:hover { background-color: #f8f9fa !important; }
.z-index-2 { z-index: 2; position: relative; }
.point-item .accordion-button::after { margin-left: 0.5rem; }

/* ─── VERROUILLAGE COLLABORATIF ────────────────────────────── */
.point-lock-overlay {
    display: none;
    position: absolute;
    inset: 0;
    z-index: 20;
    background: rgba(248, 249, 250, 0.94);
    backdrop-filter: blur(3px);
    border-radius: 0 0 1rem 1rem;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.point-lock-overlay.active { display: flex; }

@keyframes pulse-opacity {
    0%, 100% { opacity: 0.5; }
    50%       { opacity: 1; }
}
.editing-indicator { animation: pulse-opacity 2s ease-in-out infinite; }

/* ─── NOTE INTERNE ──────────────────────────────────────────── */
.note-interne-area {
    background: #fffdf0 !important;
    border-color: #ffe58f !important;
    border-radius: 0.5rem !important;
    font-size: 0.9rem;
    resize: vertical;
    min-height: 75px;
}
.note-interne-area:focus {
    background: #fffef5 !important;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.18) !important;
    border-color: #ffc107 !important;
}
</style>

<?php if($isLive): ?>
<div class="alert alert-warning border-warning shadow-sm mb-4 rounded-4 d-flex justify-content-between align-items-center">
    <div><strong>La séance est actuellement en cours.</strong> Rejoignez le live pour gérer les votes et rédiger les débats.</div>
    <a href="<?= URLROOT ?>/seances/live/<?= $seance['id'] ?>" class="btn btn-danger fw-bold shadow-sm rounded-pill px-4">Rejoindre le live</a>
</div>
<?php endif; ?>

<div class="row g-4 mb-5">

    <!-- ══════════════════════════════════════════════════════ -->
    <!-- COLONNE GAUCHE                                         -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div class="col-lg-4">

        <!-- INFOS GÉNÉRALES -->
        <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
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
                            <input type="text" name="lieu" class="form-control bg-light"
                                   value="<?= htmlspecialchars($seance['lieu'] ?? '') ?>"
                                   placeholder="Salle de réunion..."
                                   <?= !$canEditLieu ? 'readonly' : '' ?>>
                        </div>
                        <?php if($canEditLieu): ?>
                            <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm rounded-pill">
                                <i class="bi bi-save me-2"></i>Enregistrer
                            </button>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3 text-primary d-flex align-items-center justify-content-center" style="width:45px; height:45px;">
                            <i class="bi bi-calendar-event fs-5"></i>
                        </div>
                        <div>
                            <div class="small text-muted text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 0.7rem;">Date & Heure</div>
                            <div class="fw-bold fs-5 text-dark">
                                <?= date('d/m/Y', strtotime($seance['date_seance'])) ?>
                                <span class="fw-normal text-muted fs-6">à</span>
                                <?= date('H\hi', strtotime($seance['heure_debut'])) ?>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="bg-secondary bg-opacity-10 p-3 rounded-circle me-3 text-secondary d-flex align-items-center justify-content-center" style="width:45px; height:45px;">
                            <i class="bi bi-geo-alt fs-5"></i>
                        </div>
                        <div>
                            <div class="small text-muted text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 0.7rem;">Lieu</div>
                            <div class="fw-bold text-dark">
                                <?= !empty($seance['lieu']) ? htmlspecialchars($seance['lieu']) : '<em class="text-muted fw-normal">Non précisé</em>' ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CONVOCATIONS -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
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
                    <div class="text-center py-2">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-check2-all fs-2"></i>
                        </div>
                        <h6 class="fw-bold text-success mb-2">Envoyée aux membres</h6>
                        <a href="<?= URLROOT ?>/<?= htmlspecialchars($convocationDoc['chemin_fichier']) ?>" target="_blank"
                           class="btn btn-sm btn-outline-success w-100 fw-bold rounded-pill mt-2">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Voir le document
                        </a>
                    </div>
                <?php else: ?>
                    <?php if ($hasConvocSignee): ?>
                        <div class="d-flex align-items-center p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 mb-4">
                            <a href="<?= URLROOT ?>/<?= htmlspecialchars($convocationDoc['chemin_fichier']) ?>" target="_blank"
                               class="text-success text-decoration-none d-flex align-items-center flex-grow-1 me-2">
                                <i class="bi bi-file-earmark-pdf-fill fs-3 me-3 flex-shrink-0"></i>
                                <div>
                                    <div class="fw-bold small">PDF prêt</div>
                                    <div class="small opacity-75 text-decoration-underline">Consulter le fichier</div>
                                </div>
                            </a>
                            <?php if ($isBrouillon || $isDateFixee): ?>
                                <button type="button"
                                        onclick="showConfirmModal('<?= URLROOT ?>/seances/deleteDoc/<?= $convocationDoc['id'] ?>', 'Supprimer ce fichier ?')"
                                        class="btn btn-sm btn-outline-danger border-0 rounded-circle flex-shrink-0"
                                        style="padding:0; width:30px; height:30px;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if (!$isBrouillon && !$isDateFixee): ?>
                            <?php if (\app\models\User::can('manage_convocations')): ?>
                                <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/sendConvocationsManual/<?= $seance['id'] ?>', 'Envoyer les convocations à tous les membres par e-mail ?')"
                                   class="btn btn-info text-white w-100 fw-bold shadow-sm rounded-pill py-2">
                                    <i class="bi bi-send-fill me-2"></i> Diffuser l'ODJ
                                </a>
                            <?php else: ?>
                                <div class="alert alert-secondary py-2 small mb-0 text-center rounded-3">
                                    <i class="bi bi-shield-lock me-1"></i> Droits d'envoi requis
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning py-2 small mb-0 text-center rounded-3">
                                <i class="bi bi-info-circle me-1"></i> Validez l'ODJ pour envoyer
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <a href="<?= URLROOT ?>/seances/generateConvocation/<?= $seance['id'] ?>"
                               class="btn btn-light border d-flex align-items-center justify-content-center text-dark shadow-sm rounded-3 py-2">
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

    <!-- ══════════════════════════════════════════════════════ -->
    <!-- COLONNE DROITE – Points ODJ                           -->
    <!-- ══════════════════════════════════════════════════════ -->
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center" style="letter-spacing: -0.5px;">
                <div class="avatar-circle rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm me-3"
                     style="width: 45px; height: 45px; font-size: 1.2rem;">
                    <i class="bi bi-list-ol"></i>
                </div>
                Ordre du jour de la séance
            </h4>
            <?php if($canAddPoints): ?>
                <button class="btn btn-primary fw-bold shadow-sm rounded-pill px-4"
                        data-bs-toggle="modal" data-bs-target="#modalAddPoint">
                    <i class="bi bi-plus-lg me-2"></i>Ajouter un point
                </button>
            <?php endif; ?>
        </div>

        <div class="accordion d-flex flex-column gap-3" id="accordionOdj">
            <div id="sortable-points" class="d-flex flex-column gap-3 w-100">

                <?php foreach ($points as $i => $pt):
                    $isRetire = ($pt['retire'] ?? 0) == 1;

                    $typeBadgeClass = 'bg-secondary bg-opacity-10 text-secondary border-secondary';
                    $typeIcon       = 'bi-info-circle';
                    switch(strtolower($pt['type_point'] ?? '')) {
                        case 'information':  $typeBadgeClass = 'bg-info bg-opacity-10 text-info border-info';          $typeIcon = 'bi-info-circle';        break;
                        case 'deliberation': $typeBadgeClass = 'bg-primary bg-opacity-10 text-primary border-primary'; $typeIcon = 'bi-chat-left-text';     break;
                        case 'vote':         $typeBadgeClass = 'bg-danger bg-opacity-10 text-danger border-danger';    $typeIcon = 'bi-box-arrow-in-right';  break;
                        case 'divers':       $typeBadgeClass = 'bg-dark bg-opacity-10 text-dark border-dark';          $typeIcon = 'bi-three-dots';          break;
                    }
                ?>

                <div class="accordion-item rounded-4 shadow-sm border-0 point-item <?= $isRetire ? 'opacity-75 bg-light' : 'bg-white' ?>"
                     data-id="<?= $pt['id'] ?>" id="point-item-<?= $pt['id'] ?>">

                    <!-- ── EN-TÊTE ──────────────────────────────────────── -->
                    <div class="accordion-header d-flex align-items-center pe-3 py-0 m-0"
                         id="heading_pt_<?= $pt['id'] ?>">

                        <?php if (!$isRetire): ?>
                            <div class="px-3 py-4 text-muted drag-handle" title="Déplacer">
                                <i class="bi bi-grip-vertical fs-5"></i>
                            </div>
                        <?php else: ?>
                            <div class="px-3 py-4 text-muted"><i class="bi bi-dot fs-5"></i></div>
                        <?php endif; ?>

                        <button class="accordion-button collapsed shadow-none bg-transparent px-2 py-4 border-0 text-dark d-flex align-items-center w-100"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#col_pt_<?= $pt['id'] ?>"
                                aria-expanded="false" aria-controls="col_pt_<?= $pt['id'] ?>">

                            <!-- Numéro -->
                            <span class="point-number-display bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold me-3 flex-shrink-0 <?= $isRetire ? 'opacity-50' : '' ?>"
                                  style="width:28px; height:28px; font-size:0.78rem;">
                                <?= ($i+1) ?>
                            </span>

                            <!-- Titre -->
                            <span class="fw-bold me-auto text-truncate point-title-display <?= $isRetire ? 'text-decoration-line-through text-muted' : '' ?>"
                                  style="font-size:1rem;">
                                <?= htmlspecialchars($pt['titre']) ?>
                            </span>

                            <!-- Badges -->
                            <div class="d-flex align-items-center gap-2 flex-shrink-0 me-2">
                                <?php
                                $pointDocsCount = count(array_filter($documents, fn($d) => $d['point_odj_id'] == $pt['id']));
                                if($pointDocsCount > 0): ?>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-paperclip me-1"></i><?= $pointDocsCount ?>
                                    </span>
                                <?php endif; ?>

                                <?php if($isRetire): ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">
                                        <i class="bi bi-slash-circle me-1"></i>Retiré
                                    </span>
                                <?php else: ?>
                                    <span class="badge type-badge <?= $typeBadgeClass ?> border border-opacity-25 px-2 py-1">
                                        <i class="bi <?= $typeIcon ?> me-1"></i><?= ucfirst($pt['type_point'] ?? 'Information') ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </button>

                        <!-- Menu ⋮ -->
                        <div class="dropdown">
                            <button class="btn btn-light rounded-circle shadow-sm border" type="button"
                                    data-bs-toggle="dropdown"
                                    style="width:36px; height:36px; display:flex; align-items:center; justify-content:center;">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">

                                <?php if ($canEditPointsData && !$isRetire): ?>
                                    <li>
                                        <a class="dropdown-item py-2 fw-medium"
                                            href="#"
                                            data-point-id="<?= $pt['id'] ?>"
                                            data-point-titre="<?= htmlspecialchars($pt['titre'], ENT_QUOTES) ?>"
                                            data-point-type="<?= htmlspecialchars($pt['type_point'] ?? 'information', ENT_QUOTES) ?>"
                                            onclick="openEditMetaModal(this); return false;">
                                            <i class="bi bi-pencil me-2 text-primary"></i>Modifier titre / type
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                <?php endif; ?>

                                <?php if ($canAddPoints && !$isRetire): ?>
                                    <li>
                                        <a class="dropdown-item text-danger py-2 fw-medium" href="#"
                                           onclick="showConfirmModal('<?= URLROOT ?>/seances/deletePoint/<?= $pt['id'] ?>', 'Supprimer définitivement ce point de l\'ODJ ?')">
                                            <i class="bi bi-trash me-2"></i>Supprimer
                                        </a>
                                    </li>
                                <?php elseif (!$isBrouillon && !$isDateFixee): ?>
                                    <li>
                                        <a class="dropdown-item py-2 fw-medium <?= $isRetire ? 'text-success' : 'text-warning' ?>"
                                           href="<?= URLROOT ?>/seances/toggleRetirePoint/<?= $pt['id'] ?>">
                                            <i class="bi <?= $isRetire ? 'bi-arrow-counterclockwise' : 'bi-slash-circle' ?> me-2"></i>
                                            <?= $isRetire ? 'Rétablir' : 'Rayer' ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                            </ul>
                        </div>
                    </div>

                    <!-- ── CORPS DÉROULANT ──────────────────────────────── -->
                    <?php if (!$isRetire): ?>
                    <div id="col_pt_<?= $pt['id'] ?>"
                         class="accordion-collapse collapse"
                         aria-labelledby="heading_pt_<?= $pt['id'] ?>"
                         data-bs-parent="#accordionOdj">

                        <div class="accordion-body border-top p-4 bg-white rounded-bottom-4 position-relative">

                            <!-- ① OVERLAY DE VERROUILLAGE ──────────────── -->
                            <div class="point-lock-overlay" id="lock-overlay-<?= $pt['id'] ?>">
                                <div class="bg-white rounded-4 shadow-lg p-4 text-center" style="max-width:280px;">
                                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                         style="width:52px; height:52px;">
                                        <i class="bi bi-lock-fill fs-4"></i>
                                    </div>
                                    <div class="fw-bold text-dark mb-1">En cours d'édition</div>
                                    <div class="small text-muted" id="lock-user-name-<?= $pt['id'] ?>">par un collaborateur</div>
                                    <div class="small text-muted opacity-50 mt-2">Les modifications seront disponibles dès que la session sera libérée.</div>
                                </div>
                            </div>

                            <!-- ② EXPOSÉ DES MOTIFS ────────────────────── -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase d-flex align-items-center gap-2 m-0"
                                       style="letter-spacing:0.5px;">
                                    <i class="bi bi-text-paragraph text-primary"></i> Exposé des motifs
                                </label>
                                <?php if ($canEditPointsData): ?>
                                    <span id="save-status-<?= $pt['id'] ?>" style="opacity:0; transition:opacity 0.2s;"></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($canEditPointsData): ?>
                                <div class="mb-4">
                                    <div id="editor-desc-<?= $pt['id'] ?>" style="min-height:150px; font-size:0.95rem;">
                                        <?= $pt['description'] ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="bg-light p-4 rounded-3 text-dark mb-4" style="font-size:0.95rem; min-height:80px;">
                                    <?= !empty($pt['description']) ? $pt['description'] : '<em class="text-muted">Aucun exposé des motifs rédigé.</em>' ?>
                                </div>
                            <?php endif; ?>

                            <!-- ③ DOCUMENTS & ANNEXES ─────────────────── -->
                            <div class="d-flex justify-content-between align-items-center mb-3 mt-5">
                                <label class="form-label small fw-bold text-muted text-uppercase d-flex align-items-center gap-2 mb-0"
                                       style="letter-spacing:0.5px;">
                                    <i class="bi bi-paperclip text-info"></i> Documents annexés
                                </label>
                                <?php if ($canEditPointsData): ?>
                                    <button class="btn btn-sm btn-light border fw-bold text-primary rounded-pill px-3 shadow-sm"
                                            onclick="openAddDocModal(<?= $pt['id'] ?>)">
                                        <i class="bi bi-plus-lg me-1"></i> Ajouter
                                    </button>
                                <?php endif; ?>
                            </div>

                            <?php
                            $pointDocs = array_filter($documents, fn($d) => $d['point_odj_id'] == $pt['id']);
                            if (!empty($pointDocs)): ?>
                                <div class="row g-3">
                                    <?php foreach($pointDocs as $doc):
                                        $ext      = strtolower(pathinfo($doc['chemin_fichier'], PATHINFO_EXTENSION));
                                        $iconExt  = 'bi-file-earmark';
                                        $colorExt = 'text-secondary';
                                        if(in_array($ext, ['pdf']))                  { $iconExt = 'bi-filetype-pdf'; $colorExt = 'text-danger'; }
                                        elseif(in_array($ext, ['doc','docx','odt'])) { $iconExt = 'bi-filetype-doc'; $colorExt = 'text-primary'; }
                                        elseif(in_array($ext, ['xls','xlsx','csv'])) { $iconExt = 'bi-filetype-xls'; $colorExt = 'text-success'; }
                                        elseif(in_array($ext, ['jpg','jpeg','png'])) { $iconExt = 'bi-file-image';   $colorExt = 'text-info'; }
                                    ?>
                                    <div class="col-md-6 col-xl-4">
                                        <div class="d-flex align-items-center bg-light bg-opacity-50 p-2 rounded-3 border border-light shadow-sm position-relative group-hover h-100">
                                            <div class="bg-white p-2 rounded-3 shadow-sm me-3">
                                                <i class="bi <?= $iconExt ?> <?= $colorExt ?> fs-4"></i>
                                            </div>
                                            <div class="flex-grow-1 text-truncate me-2">
                                                <a href="<?= URLROOT ?>/<?= htmlspecialchars($doc['chemin_fichier']) ?>"
                                                   target="_blank"
                                                   class="text-decoration-none fw-bold text-dark stretched-link text-truncate d-block"
                                                   style="font-size:0.85rem;"
                                                   title="<?= htmlspecialchars($doc['nom'] ?: basename($doc['chemin_fichier'])) ?>">
                                                    <?= htmlspecialchars($doc['nom'] ?: basename($doc['chemin_fichier'])) ?>
                                                </a>
                                                <div class="small text-muted text-uppercase fw-medium"
                                                     style="font-size:0.65rem; letter-spacing:0.5px;">
                                                    <?= strtoupper($ext) ?>
                                                </div>
                                            </div>
                                            <?php if ($canEditPointsData): ?>
                                            <button type="button"
                                                    onclick="showConfirmModal('<?= URLROOT ?>/seances/deleteDoc/<?= $doc['id'] ?>', 'Supprimer ce document ?')"
                                                    class="btn btn-sm btn-white text-danger border-0 position-relative z-index-2 shadow-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                    style="width:28px; height:28px; padding:0;" title="Supprimer">
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

                            <!-- ④ NOTE INTERNE ────────────────────────── -->
                            <div class="mt-5 pt-4 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label small fw-bold text-muted text-uppercase d-flex align-items-center gap-2 mb-0"
                                           style="letter-spacing:0.5px;">
                                        <i class="bi bi-lock text-warning"></i>
                                        Note interne
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 fw-normal"
                                              style="font-size:0.6rem; letter-spacing:0;">
                                            Visible uniquement par les gestionnaires
                                        </span>
                                    </label>
                                        <span id="note-save-status-<?= $pt['id'] ?>" class="small" style="opacity:0; transition:opacity 0.2s;"></span>
                                </div>
                                <textarea class="form-control note-interne-area" id="note-interne-<?= $pt['id'] ?>"><?= htmlspecialchars($pt['note_interne'] ?? '') ?></textarea>
                            </div>

                        </div><!-- /accordion-body -->
                    </div><!-- /accordion-collapse -->
                    <?php endif; ?>

                </div><!-- /point-item -->
                <?php endforeach; ?>
            </div>
        </div>

        <?php if(empty($points)): ?>
            <div class="text-center p-5 bg-white rounded-4 border border-dashed text-muted mt-2 shadow-sm">
                <i class="bi bi-card-list display-1 d-block mb-3 opacity-25"></i>
                <h5 class="fw-bold text-dark">L'ordre du jour est vide</h5>
                <p class="mb-4">Commencez par ajouter les points qui seront discutés lors de cette séance.</p>
                <?php if($canAddPoints): ?>
                    <button class="btn btn-primary fw-bold shadow-sm rounded-pill px-4"
                            data-bs-toggle="modal" data-bs-target="#modalAddPoint">
                        <i class="bi bi-plus-lg me-2"></i>Ajouter un premier point
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>


<!-- ════════════════════════════════════════════════════════════ -->
<!-- MODALES                                                      -->
<!-- ════════════════════════════════════════════════════════════ -->

<!-- Modal : Ajouter un point -->
<?php if($canAddPoints): ?>
<div class="modal fade" id="modalAddPoint" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light px-4 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="bi bi-plus-circle-fill text-primary me-2"></i>Nouveau point
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= URLROOT ?>/seances/addPoint/<?= $seance['id'] ?>" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Titre du point <span class="text-danger">*</span></label>
                        <input type="text" name="titre" class="form-control bg-light"
                               placeholder="Ex: Approbation du budget..." required>
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

<!-- Modal : Ajouter un document -->
<?php if ($canEditPointsData): ?>
<div class="modal fade" id="modalAddDoc" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light px-4 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="bi bi-file-earmark-arrow-up-fill text-info me-2"></i>Ajouter une annexe
                </h5>
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
                    <button type="submit" class="btn btn-info text-white fw-bold px-4 shadow-sm rounded-pill">
                        <i class="bi bi-upload me-2"></i>Déposer le fichier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal : Modifier titre / type -->
<?php if ($canEditPointsData): ?>
<div class="modal fade" id="modalEditPointMeta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light px-4 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="bi bi-pencil-fill text-primary me-2"></i>Modifier le point
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="editMeta_pointId" value="">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">Titre <span class="text-danger">*</span></label>
                    <input type="text" id="editMeta_titre" class="form-control bg-light"
                           placeholder="Titre du point..." required>
                </div>
                <div class="mb-1">
                    <label class="form-label small fw-bold text-dark">Type de point</label>
                    <select id="editMeta_type" class="form-select bg-light">
                        <option value="information">Information</option>
                        <option value="deliberation">Délibération</option>
                        <option value="vote">Vote</option>
                        <option value="divers">Questions diverses</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary fw-bold px-4 shadow-sm rounded-pill"
                        onclick="savePointMeta()">
                    <i class="bi bi-save me-2"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<!-- ════════════════════════════════════════════════════════════ -->
<!-- SCRIPTS                                                      -->
<!-- ════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
const CURRENT_USER_ID = <?= (int)$_SESSION['user_id'] ?>;
const SEANCE_ID       = <?= (int)$seance['id'] ?>;

const TYPE_CONFIG = {
    'information':  { cls: 'bg-info bg-opacity-10 text-info border-info',          icon: 'bi-info-circle',        label: 'Information' },
    'deliberation': { cls: 'bg-primary bg-opacity-10 text-primary border-primary', icon: 'bi-chat-left-text',     label: 'Délibération' },
    'vote':         { cls: 'bg-danger bg-opacity-10 text-danger border-danger',     icon: 'bi-box-arrow-in-right', label: 'Vote' },
    'divers':       { cls: 'bg-dark bg-opacity-10 text-dark border-dark',           icon: 'bi-three-dots',         label: 'Divers' },
};

// ── Utilitaire statut de sauvegarde ────────────────────────────────
function showSaveStatus(el, state) {
    if (!el) return;
    const states = {
        editing: ['<i class="bi bi-pencil-square"></i> Modification...',                                                          'small text-muted',         '1'],
        saving:  ['<span class="spinner-border spinner-border-sm me-1" role="status"></span> Enregistrement...', 'small fw-bold text-muted',    '1'],
        saved:   ['<i class="bi bi-check2-all"></i> Enregistré',                                                                  'small fw-bold text-success','1'],
        error:   ['<i class="bi bi-exclamation-triangle"></i> Erreur',                                                            'small fw-bold text-danger', '1'],
    };
    const [html, cls, opacity] = states[state];
    el.innerHTML     = html;
    el.className     = cls;
    el.style.opacity = opacity;
    if (state === 'saved') setTimeout(() => { el.style.opacity = '0'; }, 3000);
}

function openAddDocModal(pointId) {
    document.getElementById('modal_doc_point_id').value = pointId;
    new bootstrap.Modal(document.getElementById('modalAddDoc')).show();
}

document.addEventListener("DOMContentLoaded", function () {

    // ── 1. Numéros d'ordre ──────────────────────────────────────────
    function updateOrderNumbers() {
        let index = 1;
        document.querySelectorAll('.point-item').forEach(pt => {
            const span = pt.querySelector('.point-number-display');
            if (span) span.innerText = index++;
        });
    }

    // ── 2. Drag & Drop ──────────────────────────────────────────────
    const sortableContainer = document.getElementById('sortable-points');
    if (sortableContainer && typeof Sortable !== 'undefined') {
        new Sortable(sortableContainer, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function () {
                updateOrderNumbers();
                const order = [...document.querySelectorAll('.point-item')]
                    .map(el => el.getAttribute('data-id')).filter(Boolean);
                if (order.length) {
                    fetch('<?= URLROOT ?>/seances/updateOrder', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order })
                    }).catch(err => console.error("Erreur ordre:", err));
                }
            }
        });
    }

    // ── 3. Verrouillage collaboratif ────────────────────────────────
    const lockHeartbeats = {};

    function sendLock(pointId) {
        fetch('<?= URLROOT ?>/seances/lockPoint/' + pointId, {
            method: 'POST', headers: { 'Content-Type': 'application/json' }
        }).catch(() => {});
    }

    function sendUnlock(pointId) {
        fetch('<?= URLROOT ?>/seances/unlockPoint/' + pointId, {
            method: 'POST', headers: { 'Content-Type': 'application/json' }
        }).catch(() => {});
    }

    function pollLocks() {
        fetch('<?= URLROOT ?>/seances/checkLocks/' + SEANCE_ID)
            .then(r => r.json())
            .then(data => {
                document.querySelectorAll('.point-item').forEach(item => {
                    const ptId    = item.getAttribute('data-id');
                    const overlay = document.getElementById('lock-overlay-' + ptId);
                    if (!overlay) return;
                    const lock = data[ptId];
                    // On ignore son propre verrou → jamais bloqué sur sa propre page
                    if (lock && parseInt(lock.user_id) !== CURRENT_USER_ID) {
                        const nameEl = document.getElementById('lock-user-name-' + ptId);
                        if (nameEl) nameEl.textContent = 'par ' + lock.user_name;
                        overlay.classList.add('active');
                    } else {
                        overlay.classList.remove('active');
                    }
                });
            }).catch(() => {});
    }

    setInterval(pollLocks, 10000);

    document.querySelectorAll('.point-item').forEach(item => {
        const ptId       = item.getAttribute('data-id');
        const collapseEl = document.getElementById('col_pt_' + ptId);
        if (!collapseEl) return;

        collapseEl.addEventListener('show.bs.collapse', () => {
            sendLock(ptId);
            lockHeartbeats[ptId] = setInterval(() => sendLock(ptId), 15000);
        });
        collapseEl.addEventListener('hide.bs.collapse', () => {
            clearInterval(lockHeartbeats[ptId]);
            delete lockHeartbeats[ptId];
            sendUnlock(ptId);
        });
    });

    window.addEventListener('beforeunload', () => {
        Object.keys(lockHeartbeats).forEach(ptId => {
            clearInterval(lockHeartbeats[ptId]);
            navigator.sendBeacon(
                '<?= URLROOT ?>/seances/unlockPoint/' + ptId,
                new Blob([JSON.stringify({})], { type: 'application/json' })
            );
        });
    });

    // ── 4. Quill (exposé des motifs) ──────────────────────────────────
    <?php if ($canEditPointsData): ?>
    setTimeout(function () {
        if (typeof Quill === 'undefined') { console.error("Quill JS non détecté."); return; }

        <?php foreach ($points as $pt): ?>
        <?php if (!isset($pt['retire']) || $pt['retire'] != 1): ?>
        (function (ptId) {
            const editorEl = document.getElementById('editor-desc-' + ptId);
            if (editorEl) {
                const q = new Quill(editorEl, {
                    theme: 'snow',
                    placeholder: 'Rédigez l\'exposé des motifs ici...',
                    modules: { toolbar: [
                        [{ 'header': [3, 4, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        ['link', 'clean']
                    ]}
                });
                const statusEl = document.getElementById('save-status-' + ptId);
                let timeout, isDirty = false;

                function saveDesc() {
                    if (!isDirty) return;
                    showSaveStatus(statusEl, 'saving');
                    fetch('<?= URLROOT ?>/seances/updateDescription/' + ptId, {
                        method: 'POST', keepalive: true,
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ description: q.root.innerHTML })
                    }).then(r => {
                        if (r.ok) { isDirty = false; showSaveStatus(statusEl, 'saved'); }
                        else showSaveStatus(statusEl, 'error');
                    }).catch(() => showSaveStatus(statusEl, 'error'));
                }

                q.on('text-change', () => {
                    isDirty = true;
                    showSaveStatus(statusEl, 'editing');
                    clearTimeout(timeout);
                    timeout = setTimeout(saveDesc, 1500);
                });
                q.root.addEventListener('blur', () => { clearTimeout(timeout); saveDesc(); });
                window.addEventListener('beforeunload', () => { if (isDirty) saveDesc(); });
            }
        })(<?= $pt['id'] ?>);
        <?php endif; ?>
        <?php endforeach; ?>
    }, 500);
    <?php endif; ?>

    // ── 5. Note interne ──────────────────
    setTimeout(function () {
        <?php foreach ($points as $pt): ?>
        <?php if (!isset($pt['retire']) || $pt['retire'] != 1): ?>
        (function (ptId) {
            const noteEl = document.getElementById('note-interne-' + ptId);
            const noteStatus = document.getElementById('note-save-status-' + ptId);
            if (noteEl) {
                let noteTimeout, noteDirty = false;

                function saveNote() {
                    if (!noteDirty) return;
                    showSaveStatus(noteStatus, 'saving');
                    fetch('<?= URLROOT ?>/seances/updateNoteInterne/' + ptId, {
                        method: 'POST', keepalive: true,
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ note_interne: noteEl.value })
                    }).then(r => {
                        if (r.ok) { noteDirty = false; showSaveStatus(noteStatus, 'saved'); }
                        else showSaveStatus(noteStatus, 'error');
                    }).catch(() => showSaveStatus(noteStatus, 'error'));
                }

                noteEl.addEventListener('input', () => {
                    noteDirty = true;
                    showSaveStatus(noteStatus, 'editing');
                    clearTimeout(noteTimeout);
                    noteTimeout = setTimeout(saveNote, 1500);
                });
                noteEl.addEventListener('blur', () => { clearTimeout(noteTimeout); saveNote(); });
                window.addEventListener('beforeunload', () => { if (noteDirty) saveNote(); });
            }
        })(<?= $pt['id'] ?>);
        <?php endif; ?>
        <?php endforeach; ?>
    }, 500);

}); // fin DOMContentLoaded


// ── Modal : Modifier titre / type ──────────────────────────────────
function openEditMetaModal(el) {
    const pointId = el.getAttribute('data-point-id');
    const titre   = el.getAttribute('data-point-titre');
    const type    = el.getAttribute('data-point-type');

    document.getElementById('editMeta_pointId').value = pointId;
    document.getElementById('editMeta_titre').value   = titre;
    document.getElementById('editMeta_type').value    = type;

    new bootstrap.Modal(document.getElementById('modalEditPointMeta')).show();
}


function savePointMeta() {
    const pointId = document.getElementById('editMeta_pointId').value;
    const titre   = document.getElementById('editMeta_titre').value.trim();
    const type    = document.getElementById('editMeta_type').value;
    const titreInput = document.getElementById('editMeta_titre');

    titreInput.classList.remove('is-invalid');
    if (!titre) { titreInput.classList.add('is-invalid'); return; }

    const btn     = document.querySelector('#modalEditPointMeta .btn-primary');
    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';

    fetch('<?= URLROOT ?>/seances/updatePointMeta/' + pointId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ titre, type_point: type })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const item = document.getElementById('point-item-' + pointId);
            if (item) {
                const titleSpan = item.querySelector('.point-title-display');
                if (titleSpan) titleSpan.textContent = titre;

                const badge  = item.querySelector('.type-badge');
                const config = TYPE_CONFIG[type] || TYPE_CONFIG['information'];
                if (badge) {
                    badge.className = 'badge type-badge ' + config.cls + ' border border-opacity-25 px-2 py-1';
                    badge.innerHTML = `<i class="bi ${config.icon} me-1"></i>${config.label}`;
                }
            }
            bootstrap.Modal.getInstance(document.getElementById('modalEditPointMeta')).hide();
            
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-save me-2"></i>Enregistrer';
        } else {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-save me-2"></i>Enregistrer';
        }
    })
    .catch(() => {
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-save me-2"></i>Enregistrer';
    });
}
</script>
