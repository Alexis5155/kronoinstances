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
.ql-editor p, .point-content-container p {
    margin-bottom: 0 !important;
}
.sortable-ghost {
    opacity: 0.5;
    background-color: #f8f9fa;
    border: 2px dashed #0d6efd !important;
    border-radius: 0.5rem;
}
.drag-handle { 
    cursor: grab; 
    z-index: 10;
    position: relative;
}
.drag-handle:active { cursor: grabbing; }
.group-hover:hover { background-color: #f8f9fa !important; }
.z-index-2 { z-index: 2; position: relative; }

/* ─── CHARGEMENT SKELETON ET ANIMATION DE HAUTEUR ─── */
.skeleton-box, .skeleton-line {
    background: linear-gradient(90deg, #f0f2f5 25%, #f8f9fa 37%, #f0f2f5 63%);
    background-size: 400% 100%;
    animation: skeleton-shimmer 1.5s ease-in-out infinite;
}
.skeleton-box { border-radius: 8px; }
.skeleton-line { border-radius: 4px; }

@keyframes skeleton-shimmer{
    0%{ background-position: 100% 0; }
    100%{ background-position: 0 0; }
}

/* Conteneur principal pour l'animation de hauteur */
.content-wrapper {
    transition: height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    /* L'overflow est géré dynamiquement en JS pour ne pas bloquer les tooltips/dropdowns */
}

/* Effet d'apparition en fondu pour le vrai contenu */
.fade-in-content {
    animation: fadeIn 0.3s ease-in forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.is-locked-blur {
    filter: blur(5px);
    opacity: 0.5;
    pointer-events: none;
    user-select: none;
}

/* ─── VERROUILLAGE COLLABORATIF ────────────────────────────── */
.point-lock-overlay {
    display: none;
    position: absolute;
    inset: 0;
    z-index: 20;
    background: rgba(255, 255, 255, 0.3);
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
                                <i class="bi bi-file-earmark-word text-primary fs-5 me-2"></i> Générer le brouillon (.odt)
                            </a>
                            <div class="position-relative text-center my-1">
                                <hr class="text-muted opacity-25 m-0">
                                <span class="position-absolute top-50 start-50 translate-middle bg-white px-2 small text-muted" style="font-size:0.7rem;">PUIS</span>
                            </div>
                            <form action="<?= URLROOT ?>/seances/uploadDoc/<?= $seance['id'] ?>" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="type_doc" value="convocation">
                                <label class="form-label small text-muted fw-bold mb-1">Déposer le PDF signé</label>
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
                    <div class="accordion-header d-flex align-items-center pe-2 py-0 m-0"
                         id="heading_pt_<?= $pt['id'] ?>">

                        <?php if (!$isRetire): ?>
                            <div class="px-3 py-4 text-muted drag-handle flex-shrink-0" title="Déplacer">
                                <i class="bi bi-grip-vertical fs-5"></i>
                            </div>
                        <?php else: ?>
                            <div class="px-3 py-4 text-muted flex-shrink-0"><i class="bi bi-dot fs-5"></i></div>
                        <?php endif; ?>

                        <button class="accordion-button collapsed shadow-none bg-transparent px-2 py-4 border-0 text-dark w-100"
                                style="display: flex; flex-wrap: nowrap; align-items: center; min-width: 0;"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#col_pt_<?= $pt['id'] ?>"
                                aria-expanded="false" aria-controls="col_pt_<?= $pt['id'] ?>">

                            <span class="point-number-display bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0 me-3 <?= $isRetire ? 'opacity-50' : '' ?>"
                                  style="width:28px; height:28px; font-size:0.78rem;">
                                <?= ($i+1) ?>
                            </span>

                            <span class="fw-bold text-truncate point-title-display flex-grow-1 <?= $isRetire ? 'text-decoration-line-through text-muted' : '' ?>"
                                  style="font-size:1rem; min-width:0; margin-right: 15px;"
                                  title="<?= htmlspecialchars($pt['titre']) ?>">
                                <?= htmlspecialchars($pt['titre']) ?>
                            </span>

                            <div class="d-flex align-items-center gap-2 flex-shrink-0" style="margin-right: 10px;">
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
                                
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 d-none"
                                      id="lock-badge-<?= $pt['id'] ?>">
                                  <i class="bi bi-lock-fill me-1"></i><span class="lock-badge-text">Verrouillé</span>
                                </span>
                            </div>
                        </button>

                        <!-- Menu ⋮ -->
                        <div class="dropdown flex-shrink-0">
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
                                           onclick="showConfirmModal('<?= URLROOT ?>/seances/deletePoint/<?= $pt['id'] ?>', 'Supprimer définitivement ce point de l\'ordre du jour ?')">
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

                        <!-- IMPORTANT : J'ai remis le p-4 natif de Bootstrap ici et retiré p-0 -->
                        <div class="accordion-body border-top p-4 bg-white rounded-bottom-4 position-relative">
                            
                            <!-- Wrapper animé pour la transition de hauteur (n'a plus de padding interne) -->
                            <div class="content-wrapper" id="wrapper-<?= $pt['id'] ?>">
                                
                                <!-- PLACEHOLDER -->
                                <div class="point-placeholder-content" id="placeholder-<?= $pt['id'] ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="skeleton-line" style="width: 150px; height: 16px;"></div>
                                    </div>
                                    <div class="skeleton-box mb-4" style="height: 180px;"></div>
                                    <div class="d-flex justify-content-between align-items-center mb-3 mt-5">
                                        <div class="skeleton-line" style="width: 180px; height: 16px;"></div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6"><div class="skeleton-box" style="height: 60px;"></div></div>
                                        <div class="col-md-6"><div class="skeleton-box" style="height: 60px;"></div></div>
                                    </div>
                                    <div class="mt-5 pt-4 border-top">
                                        <div class="skeleton-line mb-3" style="width: 120px; height: 16px;"></div>
                                        <div class="skeleton-box" style="height: 80px;"></div>
                                    </div>
                                </div>

                                <!-- CONTENEUR VIDE À REMPLIR EN AJAX -->
                                <div class="point-content-container fade-in-content" id="content-container-<?= $pt['id'] ?>" style="display: none;">
                                    <!-- Rempli par le script -->
                                </div>
                                
                            </div> <!-- /content-wrapper -->

                            <!-- OVERLAY DE VERROUILLAGE -->
                            <div class="point-lock-overlay" id="lock-overlay-<?= $pt['id'] ?>">
                                <div class="bg-white rounded-4 shadow-lg p-4 text-center" style="max-width:300px; position: relative;">
                                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                         style="width:52px; height:52px;">
                                        <i class="bi bi-lock-fill fs-4"></i>
                                    </div>
                                    <div class="fw-bold text-dark mb-1" id="lock-status-title-<?= $pt['id'] ?>">En cours d'édition</div>
                                    <div class="small text-muted" id="lock-user-name-<?= $pt['id'] ?>">par un collaborateur</div>
                                    <div class="small text-muted opacity-50 mt-2">Ce bloc s'actualisera automatiquement et s'ouvrira dès que la session sera libérée.</div>
                                </div>
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
const CURRENT_USER_ID   = <?= (int)$_SESSION['user_id'] ?>;
const SEANCE_ID         = <?= (int)$seance['id'] ?>;
const CAN_EDIT_POINTS   = <?= $canEditPointsData ? 'true' : 'false' ?>;
const CAN_REORDER_POINTS= <?= $canAddPoints ? 'true' : 'false' ?>;
const REQUIRE_LOCK      = CAN_EDIT_POINTS; 
const URLROOT           = '<?= URLROOT ?>';

const TYPE_CONFIG = {
    'information':  { cls: 'bg-info bg-opacity-10 text-info border-info',          icon: 'bi-info-circle',        label: 'Information' },
    'deliberation': { cls: 'bg-primary bg-opacity-10 text-primary border-primary', icon: 'bi-chat-left-text',     label: 'Délibération' },
    'vote':         { cls: 'bg-danger bg-opacity-10 text-danger border-danger',     icon: 'bi-box-arrow-in-right', label: 'Vote' },
    'divers':       { cls: 'bg-dark bg-opacity-10 text-dark border-dark',           icon: 'bi-three-dots',         label: 'Divers' },
};

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

    // ── 1. Drag & Drop ──────────────────────────────────────────
    function updateOrderNumbers() {
        let index = 1;
        document.querySelectorAll('.point-item').forEach(pt => {
            const span = pt.querySelector('.point-number-display');
            if (span) span.innerText = index++;
        });
    }

    const sortableContainer = document.getElementById('sortable-points');
    if (sortableContainer && typeof Sortable !== 'undefined' && CAN_REORDER_POINTS) {
        new Sortable(sortableContainer, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function () {
                updateOrderNumbers();
                const order = [...document.querySelectorAll('.point-item')]
                    .map(el => el.getAttribute('data-id')).filter(Boolean);
                if (order.length) {
                    fetch(URLROOT + '/seances/updateOrder', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order })
                    }).catch(err => console.error("Erreur ordre:", err));
                }
            }
        });
    }

    // ── 2. Verrouillage & Variables d'État ───────────────────────
    const lockHeartbeats   = {};
    const lockRetryTimers  = {}; 
    const lockHeld         = {};          
    const closeGuard       = {};          
    const pointRuntime     = {};          
    const pointDataCache   = {};

    function getRuntime(ptId){
        if(!pointRuntime[ptId]){
            pointRuntime[ptId] = {
                quill: null, descDirty: false, descTimeout: null, saveDesc: null,
                noteDirty: false, noteTimeout: null, saveNote: null
            };
        }
        return pointRuntime[ptId];
    }

    function setLockOverlay(ptId, active, userName = '', title = 'En cours d\'édition'){
        const overlay = document.getElementById('lock-overlay-' + ptId);
        const placeholder = document.getElementById('placeholder-' + ptId);
        if (!overlay) return;
        
        if (active) {
            const titleEl = document.getElementById('lock-status-title-' + ptId);
            const nameEl = document.getElementById('lock-user-name-' + ptId);
            if (titleEl) titleEl.textContent = title;
            if (nameEl) nameEl.textContent = userName ? 'par ' + userName : '';
            overlay.classList.add('active');
            if(placeholder) placeholder.classList.add('is-locked-blur');
        } else {
            overlay.classList.remove('active');
            if(placeholder) placeholder.classList.remove('is-locked-blur');
        }
    }

    function setHeaderLockBadge(ptId, lock){
        const badge = document.getElementById('lock-badge-' + ptId);
        if (!badge) return;
        if (lock && parseInt(lock.user_id) !== CURRENT_USER_ID) {
            badge.classList.remove('d-none');
            const txt = badge.querySelector('.lock-badge-text');
            if (txt) txt.textContent = 'Verrouillé (' + (lock.user_name || 'collaborateur') + ')';
        } else {
            badge.classList.add('d-none');
        }
    }

    function tryLock(pointId){
        return fetch(URLROOT + '/seances/lockPoint/' + pointId, {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({})
        }).then(async (r) => {
            if (r.status === 423) {
                const data = await r.json().catch(() => ({}));
                return { granted: false, lock: data };
            }
            if (!r.ok) throw new Error('lockPoint failed');
            return { granted: true, data: await r.json().catch(() => ({})) };
        });
    }

    function sendUnlock(pointId){
        return fetch(URLROOT + '/seances/unlockPoint/' + pointId, {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({})
        }).catch(() => {});
    }

    function withTimeout(promise, ms){
        return new Promise((resolve) => {
            let done = false;
            const t = setTimeout(() => { if(!done){ done = true; resolve({ timeout:true }); } }, ms);
            promise.then((v) => { if(!done){ done = true; clearTimeout(t); resolve(v); } })
                   .catch(() => { if(!done){ done = true; clearTimeout(t); resolve(null); } });
        });
    }

    function pollLocks(){
        fetch(URLROOT + '/seances/checkLocks/' + SEANCE_ID)
            .then(r => r.json())
            .then(data => {
                document.querySelectorAll('.point-item').forEach(item => {
                    const ptId = item.getAttribute('data-id');
                    if (!lockHeld[ptId]) {
                        setHeaderLockBadge(ptId, data[ptId]);
                    }
                });
            }).catch(() => {});
    }
    
    if (REQUIRE_LOCK) {
        pollLocks();
        setInterval(pollLocks, 20000);
    }

    // ── 3. Génération HTML & Animation de transition ────────────────────
    function renderPointContent(ptId, data) {
        const container = document.getElementById('content-container-' + ptId);
        if (!container) return;

        let html = '';

        // -- Exposé des motifs --
        html += `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="form-label small fw-bold text-muted text-uppercase d-flex align-items-center gap-2 m-0" style="letter-spacing:0.5px;">
                <i class="bi bi-text-paragraph text-primary"></i> Exposé des motifs
            </label>
            ${CAN_EDIT_POINTS ? `<span id="save-status-${ptId}" style="opacity:0; transition:opacity 0.2s;"></span>` : ''}
        </div>`;

        if (CAN_EDIT_POINTS) {
            html += `<div class="mb-4"><div id="editor-desc-${ptId}" style="min-height:150px; font-size:0.95rem;">${data.description || ''}</div></div>`;
        } else {
            // Modification ici : suppression du min-height et ajustement du padding (px-4 py-3)
            html += `<div class="bg-light px-4 py-3 rounded-3 text-dark mb-4" style="font-size:0.95rem;">
                ${data.description ? data.description : '<em class="text-muted">Aucun exposé des motifs rédigé.</em>'}
            </div>`;
        }

        // -- Documents annexés --
        html += `
        <div class="d-flex justify-content-between align-items-center mb-3 mt-5">
            <label class="form-label small fw-bold text-muted text-uppercase d-flex align-items-center gap-2 mb-0" style="letter-spacing:0.5px;">
                <i class="bi bi-paperclip text-info"></i> Documents annexés
            </label>
            ${CAN_EDIT_POINTS ? `<button class="btn btn-sm btn-light border fw-bold text-primary rounded-pill px-3 shadow-sm" onclick="openAddDocModal(${ptId})"><i class="bi bi-plus-lg me-1"></i> Ajouter</button>` : ''}
        </div>`;

        if (data.documents && data.documents.length > 0) {
            html += `<div class="row g-3">`;
            data.documents.forEach(doc => {
                const ext = doc.chemin_fichier.split('.').pop().toLowerCase();
                let iconExt = 'bi-file-earmark', colorExt = 'text-secondary';
                if (['pdf'].includes(ext)) { iconExt = 'bi-filetype-pdf'; colorExt = 'text-danger'; }
                else if (['doc','docx','odt'].includes(ext)) { iconExt = 'bi-filetype-doc'; colorExt = 'text-primary'; }
                else if (['xls','xlsx','csv'].includes(ext)) { iconExt = 'bi-filetype-xls'; colorExt = 'text-success'; }
                else if (['jpg','jpeg','png'].includes(ext)) { iconExt = 'bi-file-image'; colorExt = 'text-info'; }

                const docName = doc.nom || doc.chemin_fichier.split('/').pop();
                const docUrl = URLROOT + '/' + doc.chemin_fichier;

                html += `
                <div class="col-md-6 col-xl-4">
                    <div class="d-flex align-items-center bg-light bg-opacity-50 p-2 rounded-3 border border-light shadow-sm position-relative group-hover h-100">
                        <div class="bg-white p-2 rounded-3 shadow-sm me-3"><i class="bi ${iconExt} ${colorExt} fs-4"></i></div>
                        <div class="flex-grow-1 text-truncate me-2">
                            <a href="${docUrl}" target="_blank" class="text-decoration-none fw-bold text-dark stretched-link text-truncate d-block" style="font-size:0.85rem;" title="${docName}">${docName}</a>
                            <div class="small text-muted text-uppercase fw-medium" style="font-size:0.65rem; letter-spacing:0.5px;">${ext.toUpperCase()}</div>
                        </div>
                        ${CAN_EDIT_POINTS ? `<button type="button" onclick="showConfirmModal('${URLROOT}/seances/deleteDoc/${doc.id}', 'Supprimer ce document ?')" class="btn btn-sm btn-white text-danger border-0 position-relative z-index-2 shadow-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px; height:28px; padding:0;" title="Supprimer"><i class="bi bi-trash"></i></button>` : ''}
                    </div>
                </div>`;
            });
            html += `</div>`;
        } else {
            html += `<div class="text-center p-4 bg-light rounded-4 mb-2 text-muted small border border-dashed">
                <i class="bi bi-inbox fs-3 d-block mb-1 opacity-25"></i> Aucun document rattaché.
            </div>`;
        }

        // -- Note interne --
        html += `
        <div class="mt-5 pt-4 border-top">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="form-label small fw-bold text-muted text-uppercase d-flex align-items-center gap-2 mb-0" style="letter-spacing:0.5px;">
                    <i class="bi bi-lock text-warning"></i> Note interne
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 fw-normal" style="font-size:0.6rem; letter-spacing:0;">Visible uniquement par les gestionnaires</span>
                </label>
                <span id="note-save-status-${ptId}" class="small" style="opacity:0; transition:opacity 0.2s;"></span>
            </div>
            <textarea class="form-control note-interne-area" id="note-interne-${ptId}">${data.note_interne || ''}</textarea>
        </div>`;

        container.innerHTML = html;
    }

    function transitionToContent(ptId) {
        const wrapper = document.getElementById('wrapper-' + ptId);
        const placeholder = document.getElementById('placeholder-' + ptId);
        const container = document.getElementById('content-container-' + ptId);
        const data = pointDataCache[ptId];

        if (!wrapper || !placeholder || !container || !data) return;

        // Étape 1 : Figer la hauteur actuelle (celle du skeleton)
        const initialHeight = wrapper.getBoundingClientRect().height;
        wrapper.style.overflow = 'hidden'; // On cache l'overflow juste pour l'animation
        wrapper.style.height = initialHeight + 'px';

        // Étape 2 : Injecter le vrai contenu et le préparer
        renderPointContent(ptId, data);
        if (REQUIRE_LOCK) initQuillIfNeeded(ptId);
        initNoteIfNeeded(ptId);
        setEditorsEnabled(ptId, true);

        // Étape 3 : Cacher le skeleton, afficher le contenu (invisible le temps de mesurer)
        placeholder.style.display = 'none';
        container.classList.remove('fade-in-content');
        void container.offsetWidth;
        container.classList.add('fade-in-content');
        container.style.display = 'block';
        container.style.visibility = 'hidden'; 

        
        // Étape 4 : Mesurer la nouvelle hauteur cible
        const targetHeight = container.getBoundingClientRect().height;
        container.style.visibility = ''; // On le rend visible
        
        // Force le reflow
        wrapper.offsetHeight;

        // Étape 5 : Animer vers la nouvelle hauteur
        wrapper.style.height = targetHeight + 'px';

        // Nettoyage après transition : libérer la hauteur ET l'overflow (important pour les dropdowns Quill)
        setTimeout(() => {
            wrapper.style.height = 'auto'; 
            wrapper.style.overflow = 'visible'; 
            if (REQUIRE_LOCK) startHeartbeat(ptId);
        }, 300); 
    }

    function unloadPointContent(ptId) {
        const container = document.getElementById('content-container-' + ptId);
        const placeholder = document.getElementById('placeholder-' + ptId);
        const wrapper = document.getElementById('wrapper-' + ptId);
        
        if (container) {
            container.innerHTML = '';
            container.style.display = 'none';
            container.classList.remove('fade-in-content');
        }
        if (placeholder) {
            placeholder.style.display = 'block';
            placeholder.classList.remove('is-locked-blur');
        }
        if (wrapper) {
            wrapper.style.height = 'auto';
            wrapper.style.overflow = 'visible';
        }
        
        delete pointDataCache[ptId];

        if (pointRuntime[ptId]) {
            clearTimeout(pointRuntime[ptId].descTimeout);
            clearTimeout(pointRuntime[ptId].noteTimeout);
            pointRuntime[ptId] = { quill: null, descDirty: false, descTimeout: null, saveDesc: null, noteDirty: false, noteTimeout: null, saveNote: null };
        }
    }

    // ── 4. Éditeurs & Timers ──────────────────────────────────
    function setEditorsEnabled(ptId, enabled){
        const rt = getRuntime(ptId);
        if (rt.quill) try { rt.quill.enable(!!enabled); } catch(e) {}
        const noteEl = document.getElementById('note-interne-' + ptId);
        if (noteEl) noteEl.disabled = !enabled;
    }

    function initQuillIfNeeded(ptId){
        if (!CAN_EDIT_POINTS) return;
        const rt = getRuntime(ptId);
        if (rt.quill) return;

        const editorEl = document.getElementById('editor-desc-' + ptId);
        if (!editorEl) return;

        const q = new Quill(editorEl, {
            theme: 'snow', placeholder: 'Rédigez l\'exposé des motifs ici...',
            modules: { toolbar: [ [{ 'header': [3, 4, false] }], ['bold', 'italic', 'underline', 'strike'], [{ 'list': 'ordered' }, { 'list': 'bullet' }], ['link', 'clean'] ]}
        });
        rt.quill = q;

        const statusEl = document.getElementById('save-status-' + ptId);
        rt.saveDesc = function(){
            if (!rt.descDirty) return Promise.resolve(true);
            showSaveStatus(statusEl, 'saving');
            return fetch(URLROOT + '/seances/updateDescription/' + ptId, {
                method: 'POST', keepalive: true, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ description: q.root.innerHTML })
            }).then(r => {
                if (r.ok) { rt.descDirty = false; showSaveStatus(statusEl, 'saved'); return true; }
                showSaveStatus(statusEl, 'error'); return false;
            }).catch(() => { showSaveStatus(statusEl, 'error'); return false; });
        };

        q.on('text-change', () => {
            rt.descDirty = true; showSaveStatus(statusEl, 'editing');
            clearTimeout(rt.descTimeout);
            rt.descTimeout = setTimeout(() => { rt.saveDesc && rt.saveDesc(); }, 1500);
        });

        q.root.addEventListener('blur', () => {
            clearTimeout(rt.descTimeout); rt.saveDesc && rt.saveDesc();
        });
    }

    function initNoteIfNeeded(ptId){
        const rt = getRuntime(ptId);
        if (rt.saveNote) return;

        const noteEl = document.getElementById('note-interne-' + ptId);
        const noteStatus = document.getElementById('note-save-status-' + ptId);
        if (!noteEl) return;

        rt.saveNote = function(){
            if (!rt.noteDirty) return Promise.resolve(true);
            showSaveStatus(noteStatus, 'saving');
            return fetch(URLROOT + '/seances/updateNoteInterne/' + ptId, {
                method: 'POST', keepalive: true, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ note_interne: noteEl.value })
            }).then(r => {
                if (r.ok) { rt.noteDirty = false; showSaveStatus(noteStatus, 'saved'); return true; }
                showSaveStatus(noteStatus, 'error'); return false;
            }).catch(() => { showSaveStatus(noteStatus, 'error'); return false; });
        };

        noteEl.addEventListener('input', () => {
            rt.noteDirty = true; showSaveStatus(noteStatus, 'editing');
            clearTimeout(rt.noteTimeout);
            rt.noteTimeout = setTimeout(() => { rt.saveNote && rt.saveNote(); }, 1500);
        });

        noteEl.addEventListener('blur', () => {
            clearTimeout(rt.noteTimeout); rt.saveNote && rt.saveNote();
        });
    }

    function startHeartbeat(ptId){
        if (lockHeartbeats[ptId]) return;
        lockHeartbeats[ptId] = setInterval(() => {
            if (!lockHeld[ptId]) return;
            tryLock(ptId).then(res => {
                if (!res.granted) {
                    lockHeld[ptId] = false; clearInterval(lockHeartbeats[ptId]); delete lockHeartbeats[ptId];
                    setEditorsEnabled(ptId, false);
                    setLockOverlay(ptId, true, res.lock?.user_name, 'Verrouillage perdu');
                    startLockRetryTimer(ptId);
                }
            }).catch(() => {});
        }, 15000);
    }

    function stopHeartbeat(ptId){
        if (!lockHeartbeats[ptId]) return;
        clearInterval(lockHeartbeats[ptId]); delete lockHeartbeats[ptId];
    }

    function startLockRetryTimer(ptId) {
        if (lockRetryTimers[ptId]) return;
        lockRetryTimers[ptId] = setInterval(() => {
            if (lockHeld[ptId]) { stopLockRetryTimer(ptId); return; }
            
            tryLock(ptId).then(res => {
                if (res.granted) {
                    stopLockRetryTimer(ptId);
                    lockHeld[ptId] = true;
                    setHeaderLockBadge(ptId, null);
                    setLockOverlay(ptId, false);
                    
                    fetch(URLROOT + '/seances/getPointData/' + ptId)
                        .then(r => r.json())
                        .then(data => {
                            pointDataCache[ptId] = data;
                            transitionToContent(ptId);
                        }).catch(() => {});
                }
            }).catch(() => {});
        }, 20000);
    }

    function stopLockRetryTimer(ptId) {
        if (lockRetryTimers[ptId]) {
            clearInterval(lockRetryTimers[ptId]);
            delete lockRetryTimers[ptId];
        }
    }

    // ── 5. Hooks Accordéons avec gestion d'animation ──────────────────────────────────────────
    document.querySelectorAll('.point-item').forEach(item => {
        const ptId = item.getAttribute('data-id');
        const collapseEl = document.getElementById('col_pt_' + ptId);
        if (!collapseEl) return;
        
        let pointState = {
            animationFinished: false,
            dataLoaded: false
        };

        // DÉBUT D'OUVERTURE
        collapseEl.addEventListener('show.bs.collapse', () => {
            pointState.animationFinished = false;
            pointState.dataLoaded = false;
            setLockOverlay(ptId, false);

            const performFetch = () => {
                fetch(URLROOT + '/seances/getPointData/' + ptId)
                    .then(r => r.json())
                    .then(data => {
                        pointDataCache[ptId] = data;
                        pointState.dataLoaded = true;
                        if (pointState.animationFinished) {
                            transitionToContent(ptId);
                        }
                    }).catch(() => {
                        if (REQUIRE_LOCK) {
                            lockHeld[ptId] = false;
                            setLockOverlay(ptId, true, '', 'Erreur de chargement');
                            startLockRetryTimer(ptId);
                        }
                    });
            };

            if (REQUIRE_LOCK) {
                tryLock(ptId).then(res => {
                    if (res.granted) {
                        lockHeld[ptId] = true;
                        setHeaderLockBadge(ptId, null);
                        performFetch();
                    } else {
                        lockHeld[ptId] = false;
                        setLockOverlay(ptId, true, res.lock?.user_name || 'un collaborateur');
                        startLockRetryTimer(ptId);
                    }
                }).catch(() => {
                    lockHeld[ptId] = false;
                    setLockOverlay(ptId, true, '', 'Erreur réseau');
                    startLockRetryTimer(ptId);
                });
            } else {
                performFetch();
            }
        });

        // FIN D'OUVERTURE
        collapseEl.addEventListener('shown.bs.collapse', () => {
            pointState.animationFinished = true;
            if (pointState.dataLoaded && (!REQUIRE_LOCK || lockHeld[ptId])) {
                transitionToContent(ptId);
            }
        });

        // DÉBUT DE FERMETURE
        collapseEl.addEventListener('hide.bs.collapse', (e) => {
            if (REQUIRE_LOCK) stopLockRetryTimer(ptId); 

            if (REQUIRE_LOCK && !lockHeld[ptId]) return;
            if (closeGuard[ptId]) { closeGuard[ptId] = false; return; }

            const rt = getRuntime(ptId);
            const hasDirty = !!(rt.descDirty || rt.noteDirty);

            if (hasDirty) {
                e.preventDefault();
                const saves = [];
                if (REQUIRE_LOCK && rt.saveDesc) saves.push(rt.saveDesc());
                if (rt.saveNote) saves.push(rt.saveNote());

                withTimeout(Promise.allSettled(saves), 3000).then(() => {
                    closeGuard[ptId] = true;
                    bootstrap.Collapse.getOrCreateInstance(collapseEl).hide();
                });
            }
        });

        // FIN DE FERMETURE
        collapseEl.addEventListener('hidden.bs.collapse', () => {
            if (REQUIRE_LOCK) {
                stopLockRetryTimer(ptId);
                if (lockHeld[ptId]) {
                    stopHeartbeat(ptId);
                    lockHeld[ptId] = false;
                    sendUnlock(ptId);
                }
            }
            unloadPointContent(ptId);
        });
    });

    window.addEventListener('beforeunload', () => {
        if (!REQUIRE_LOCK) return;
        Object.keys(lockHeld).forEach(ptId => {
            if (!lockHeld[ptId]) return;
            stopHeartbeat(ptId);
            navigator.sendBeacon(URLROOT + '/seances/unlockPoint/' + ptId, new Blob([JSON.stringify({})], { type: 'application/json' }));
        });
    });

}); 

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
    const titre = document.getElementById('editMeta_titre').value.trim();
    const type = document.getElementById('editMeta_type').value;

    const titreInput = document.getElementById('editMeta_titre');
    titreInput.classList.remove('is-invalid');

    if (!titre) {
        titreInput.classList.add('is-invalid');
        return;
    }

    const btn = document.querySelector('#modalEditPointMeta .btn-primary');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';

    fetch(URLROOT + '/seances/updatePointMeta/' + pointId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ titre: titre, type_point: type })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const item = document.getElementById('point-item-' + pointId);
            if (item) {
                // Mise à jour de l'affichage
                const titleSpan = item.querySelector('.point-title-display');
                if (titleSpan) {
                    titleSpan.textContent = titre;
                    titleSpan.title = titre;
                }

                const badge = item.querySelector('.type-badge');
                const config = TYPE_CONFIG[type] || TYPE_CONFIG['information'];
                if (badge) {
                    badge.className = 'badge type-badge ' + config.cls + ' border border-opacity-25 px-2 py-1';
                    badge.innerHTML = '<i class="bi ' + config.icon + ' me-1"></i>' + config.label;
                }

                // NOUVEAU : MISE À JOUR DES DATA-ATTRIBUTS DU BOUTON POUR LA PROCHAINE OUVERTURE
                const editBtn = item.querySelector('a.dropdown-item[onclick^="openEditMetaModal"]');
                if (editBtn) {
                    editBtn.setAttribute('data-point-titre', titre);
                    editBtn.setAttribute('data-point-type', type);
                }
            }

            bootstrap.Modal.getInstance(document.getElementById('modalEditPointMeta')).hide();
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save me-2"></i>Enregistrer';
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save me-2"></i>Enregistrer';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-2"></i>Enregistrer';
    });
}
</script>
