<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
/* --- CAPSULES (PILLBOX) --- */
.pillbox-container { border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.375rem; min-height: 42px; display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; background: #fff; position: relative; }
.pillbox-input { border: none; outline: none; background: transparent; flex-grow: 1; min-width: 150px; font-size: 0.9rem; }
.pillbox-dropdown { position: absolute; top: 100%; left: 0; right: 0; max-height: 200px; overflow-y: auto; background: #fff; border: 1px solid #ccc; border-radius: 0.375rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1050; display: none; margin-top: 2px; }
.pillbox-item { padding: 0.5rem 1rem; cursor: pointer; border-bottom: 1px solid #f1f1f1; transition: 0.2s; }
.pillbox-item:hover { background-color: #f8f9fa; }
.badge-pill { font-size: 0.85rem; padding: 0.4rem 0.6rem; display: flex; align-items: center; gap: 5px; }
.badge-pill i { cursor: pointer; transition: 0.2s; }
.badge-pill i:hover { opacity: 0.7; transform: scale(1.1); }

/* --- TABLEAUX FIXES & RESPONSIVES --- */
.fixed-table { table-layout: fixed; width: 100%; min-width: 600px; }
.fixed-table td { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; vertical-align: middle; }

/* --- VALIDATION INLINE --- */
.field-error { display: none; font-size: 0.8rem; color: #dc3545; margin-top: 3px; }
.is-invalid ~ .field-error { display: block; }

/* --- TRONCATURE DES DESCRIPTIONS (2 lignes max) --- */
.card-desc-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    min-height: 2.5rem; 
}

/* --- HARMONISATION VISUELLE --- */
.stat-card { z-index: 1; transition: transform 0.25s ease, box-shadow 0.25s ease; border: 1px solid rgba(0,0,0,0.05) !important; }
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important; }
.stat-icon { z-index: -1; right: -10px !important; top: 10px !important; pointer-events: none; }
@media (max-width: 1200px) { .stat-icon { opacity: 0.05 !important; } }
</style>

<!-- ============================================================ -->
<!-- LISTE DES INSTANCES                                           -->
<!-- ============================================================ -->
<div class="container py-4">
    <!-- EN-TÊTE -->
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div class="d-flex align-items-center">
            <div class="avatar-circle rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold shadow-sm me-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                <i class="bi bi-diagram-3"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Instances paritaires</h2>
                <p class="text-muted small mb-0">Gestion des instances paritaires et de leur composition</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= URLROOT ?>/admin" class="btn btn-light fw-bold shadow-sm px-3 rounded-pill border d-none d-sm-inline-block">
                <i class="bi bi-arrow-left me-2"></i> Admin
            </a>
            <button type="button" class="btn btn-primary fw-bold shadow-sm px-4 rounded-pill" onclick="openModal()">
                <i class="bi bi-plus-lg me-2"></i>Nouvelle Instance
            </button>
        </div>
    </div>

    <!-- GRILLE DE CARTES RESPONSIVE -->
    <div class="row g-4 align-items-stretch">
        <?php if(empty($instances)): ?>
            <div class="col-12">
                <div class="text-center py-5 text-muted bg-white rounded-4 shadow-sm border border-light">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-diagram-3 fs-1 opacity-50"></i>
                    </div>
                    <h6 class="fw-bold text-dark">Aucune instance configurée</h6>
                    <p class="small mb-0">Cliquez sur "Nouvelle Instance" pour commencer.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach($instances as $inst): ?>
            <?php 
                // Vérification PHP de l'existence du modèle pour CHAQUE instance
                $modelePath = 'uploads/modeles/modele_instance_' . $inst['id'] . '.odt';
                $inst['hasModele'] = file_exists($modelePath);
            ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 shadow-sm rounded-4 overflow-hidden position-relative stat-card bg-white">                  
                    <div class="card-body p-4 position-relative z-1 d-flex flex-column h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3" style="padding-right: 2rem;">
                            <h5 class="fw-bold text-dark mb-0 text-truncate" title="<?= htmlspecialchars($inst['nom']) ?>">
                                <?= htmlspecialchars($inst['nom']) ?>
                            </h5>
                        </div>
                        
                        <!-- On ajoute un padding droit pour éviter la zone de l'icône -->
                        <div class="pe-4 mb-4">
                            <p class="card-text text-muted small card-desc-clamp mb-0" title="<?= htmlspecialchars($inst['description']) ?>">
                                <?= !empty($inst['description']) ? htmlspecialchars($inst['description']) : '<em class="opacity-50">Aucune description renseignée</em>' ?>
                            </p>
                        </div>
                        
                        <!-- Badges d'informations avec un peu plus d'air -->
                        <div class="mt-auto d-flex flex-column gap-2 mb-4">
                            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-1">
                                <span class="small text-muted fw-medium"><i class="bi bi-people me-2"></i>Membres totaux</span>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2"><?= count($inst['membres']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span><i class="bi bi-person-check me-2"></i><?= $inst['nb_titulaires'] ?> Titulaires</span>
                                <span><i class="bi bi-person-dash me-2"></i><?= $inst['nb_suppleants'] ?> Suppléants</span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mt-2">
                                <span><i class="bi bi-shield-lock me-2"></i><?= count($inst['managers']) ?> Gestionnaire(s)</span>
                                <span><i class="bi bi-check2-circle me-2"></i>Quorum: <?= $inst['quorum_requis'] ?></span>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="d-flex gap-3 mt-2">
                            <button type="button" class="btn btn-light border text-primary fw-bold flex-grow-1 rounded-pill hover-primary shadow-sm py-2"
                                    onclick='openModal(<?= htmlspecialchars(json_encode($inst, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)'>
                                <i class="bi bi-pencil-square me-2"></i>Gérer
                            </button>
                            <button type="button" class="btn btn-outline-danger rounded-circle border-0 shadow-sm" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;"
                                    onclick="confirmDelete(<?= $inst['id'] ?>, '<?= htmlspecialchars(addslashes($inst['nom'])) ?>')" title="Supprimer">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .hover-primary { transition: all 0.2s; }
    .hover-primary:hover { background-color: #0d6efd !important; color: white !important; border-color: #0d6efd !important; }
</style>

<!-- ============================================================ -->
<!-- MODALE CONFIRMATION DE SUPPRESSION                           -->
<!-- ============================================================ -->
<div class="modal fade" id="deleteModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-danger bg-opacity-10 px-4 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Supprimer l'instance
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p>Vous êtes sur le point de supprimer l'instance <strong id="deleteInstanceName" class="text-dark"></strong>.</p>
                <div class="alert alert-warning border-0 py-3 small mb-0 rounded-3">
                    <div class="fw-bold mb-2 text-dark"><i class="bi bi-info-circle-fill me-1"></i>Conséquences de la suppression :</div>
                    <ul class="mb-0 ps-3 text-dark opacity-75">
                        <li class="mb-1">Les séances déjà archivées resteront accessibles mais <strong>ne seront plus rattachées</strong> à cette instance.</li>
                        <li class="mb-1">Il ne sera <strong>plus possible de créer</strong> de nouvelles séances pour cette instance.</li>
                        <li>La composition (membres, gestionnaires) sera <strong>définitivement perdue</strong>.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                <a id="deleteLinkBtn" href="#" class="btn btn-danger fw-bold px-4">
                    <i class="bi bi-trash me-1"></i>Supprimer
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODALE ÉDITION / CRÉATION D'UNE INSTANCE                     -->
<!-- ============================================================ -->
<div class="modal fade" id="instanceModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            
            <div class="modal-header bg-light border-0 px-4 py-3">
                <h5 class="modal-title fw-bold text-dark" id="modalTitle">Nouvelle Instance</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>

            <!-- ONGLETS -->
            <div class="bg-light px-4 border-bottom">
                <ul class="nav nav-tabs border-0" id="instanceTabs">
                    <li class="nav-item"><button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#tab-infos" type="button"><i class="bi bi-info-circle me-1"></i>Paramètres</button></li>
                    <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab-managers" type="button"><i class="bi bi-shield-lock me-1"></i>Gestionnaires</button></li>
                    <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab-membres" type="button" id="tabMembresTrigger"><i class="bi bi-people me-1"></i>Membres</button></li>
                    <li class="nav-item" id="tabConvocationsLi" style="display:none;"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab-convocations" type="button"><i class="bi bi-file-earmark-word me-1"></i>Modèle Convocation</button></li>
                </ul>
            </div>

            <div class="modal-body p-4 tab-content" style="background-color: #fcfcfc;">

                <!-- ONGLET 1 : PARAMÈTRES -->
                <div class="tab-pane fade show active" id="tab-infos">
                    <div class="bg-white p-4 rounded-3 shadow-sm border border-light">
                        <form id="instanceForm" novalidate>
                            <input type="hidden" name="instance_id" id="instance_id">
                            <input type="hidden" name="save_instance" value="1">
                            <input type="hidden" name="membres_json" id="membres_json" value="[]">

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Nom de l'instance <span class="text-danger">*</span></label>
                                <input type="text" id="nom" class="form-control bg-light" placeholder="Ex : Comité Social Territorial">
                                <div class="field-error" id="err-nom">Le nom de l'instance est obligatoire.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Description</label>
                                <textarea id="description" class="form-control bg-light" rows="2" placeholder="Description facultative..."></textarea>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Nb. Titulaires <span class="text-danger">*</span></label>
                                    <input type="number" id="nb_titulaires" class="form-control bg-light" min="1">
                                    <div class="field-error" id="err-titulaires">Valeur requise (min. 1).</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Nb. Suppléants <span class="text-danger">*</span></label>
                                    <input type="number" id="nb_suppleants" class="form-control bg-light" min="0">
                                    <div class="field-error" id="err-suppleants">Valeur requise (min. 0).</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Quorum requis <span class="text-danger">*</span></label>
                                    <input type="number" id="quorum" class="form-control bg-light" min="1">
                                    <div class="field-error" id="err-quorum">Valeur requise (min. 1).</div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ONGLET 2 : GESTIONNAIRES -->
                <div class="tab-pane fade" id="tab-managers">
                    <div class="bg-white p-4 rounded-3 shadow-sm border border-light">
                        <div class="alert alert-info border-0 py-2 small mb-4 rounded-3">
                            <i class="bi bi-info-circle-fill me-2"></i>Ces agents auront le droit de créer des séances et de gérer l'ordre du jour pour cette instance spécifique.
                        </div>
                        <label class="form-label small fw-bold text-dark">Agents autorisés</label>
                        <div id="pb-managers"></div>
                    </div>
                </div>

                <!-- ONGLET 3 : MEMBRES -->
                <div class="tab-pane fade" id="tab-membres">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-diagram-2 me-2 text-primary"></i>Composition de l'instance</h6>
                        <button type="button" class="btn btn-sm btn-primary fw-bold shadow-sm rounded-pill px-3" onclick="openMemberForm()">
                            <i class="bi bi-person-plus-fill me-1"></i>Ajouter un membre
                        </button>
                    </div>

                    <!-- FORMULAIRE DÉROULANT -->
                    <div class="collapse mb-4" id="addMemberCardCollapse">
                        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                            <div class="card-header bg-primary bg-opacity-10 border-0 py-2">
                                <h6 class="fw-bold text-primary mb-0 mt-1" id="m_form_title">Nouveau Membre</h6>
                            </div>
                            <div class="card-body bg-white">
                                <input type="hidden" id="m_edit_id" value="">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="text" id="m_nom" class="form-control form-control-sm bg-light" placeholder="Nom *">
                                        <div class="field-error" id="err-m-nom">Le nom est obligatoire.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" id="m_prenom" class="form-control form-control-sm bg-light" placeholder="Prénom *">
                                        <div class="field-error" id="err-m-prenom">Le prénom est obligatoire.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="email" id="m_email" class="form-control form-control-sm bg-light" placeholder="Email" onkeyup="checkEmailMatch()">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" id="m_qualite" class="form-control form-control-sm bg-light" placeholder="Qualité (ex : Président)">
                                    </div>
                                    <div class="col-md-4">
                                        <select id="m_college" class="form-select form-select-sm bg-light">
                                            <option value="administration">Collège Administration</option>
                                            <option value="personnel">Collège Personnel</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select id="m_mandat" class="form-select form-select-sm bg-light">
                                            <option value="titulaire">Titulaire</option>
                                            <option value="suppleant">Suppléant</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- ALERTE COMPTE TROUVÉ -->
                                <div id="m_account_alert" class="alert alert-success mt-3 py-2 small mb-0 align-items-center justify-content-between rounded-3" style="display:none !important;">
                                    <div><i class="bi bi-check-circle-fill me-2"></i>Compte utilisateur trouvé : <strong id="m_match_name"></strong></div>
                                    <div class="form-check form-switch ms-3 mb-0">
                                        <input class="form-check-input" type="checkbox" id="m_link_account" checked>
                                        <label class="form-check-label fw-bold" for="m_link_account">Lier le compte</label>
                                    </div>
                                    <input type="hidden" id="m_matched_user_id">
                                </div>

                                <div class="mt-3 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-sm btn-light border fw-bold px-3" onclick="closeMemberForm()">Annuler</button>
                                    <button type="button" class="btn btn-sm btn-success fw-bold px-3 shadow-sm" id="btnSubmitMember" onclick="saveMemberToList()">
                                        <i class="bi bi-check-lg me-1"></i>Enregistrer le membre
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TABLEAUX MEMBRES -->
                    <div class="bg-white p-3 rounded-3 shadow-sm border border-light mb-4">
                        <h6 class="text-dark fw-bold mb-3"><i class="bi bi-building me-2 text-secondary"></i>Collège Administration</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle fixed-table mb-0">
                                <thead class="table-light text-muted small text-uppercase" style="font-size: 0.7rem;">
                                    <tr>
                                        <th style="width:30%" class="ps-3 py-2">Nom Prénom</th>
                                        <th style="width:25%" class="py-2">Qualité</th>
                                        <th style="width:15%" class="py-2">Mandat</th>
                                        <th style="width:20%" class="py-2">Email</th>
                                        <th style="width:80px" class="text-end pe-3 py-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-administration"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white p-3 rounded-3 shadow-sm border border-light">
                        <h6 class="text-dark fw-bold mb-3"><i class="bi bi-people me-2 text-info"></i>Collège Représentants du Personnel</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle fixed-table mb-0">
                                <thead class="table-light text-muted small text-uppercase" style="font-size: 0.7rem;">
                                    <tr>
                                        <th style="width:30%" class="ps-3 py-2">Nom Prénom</th>
                                        <th style="width:25%" class="py-2">Qualité</th>
                                        <th style="width:15%" class="py-2">Mandat</th>
                                        <th style="width:20%" class="py-2">Email</th>
                                        <th style="width:80px" class="text-end pe-3 py-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-personnel"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ONGLET 4 : MODÈLE CONVOCATION -->
                <div class="tab-pane fade" id="tab-convocations">
                    <div class="bg-white p-4 rounded-3 shadow-sm border border-light">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-word me-2 text-primary"></i>Modèle de Convocation (Format .odt)</h6>
                        <div class="alert alert-secondary border-0 small text-dark mb-4 rounded-3">
                            <i class="bi bi-info-circle-fill me-2"></i>Utilisez les balises suivantes dans votre document LibreOffice/Word : 
                            <strong class="font-monospace ms-1">{{INSTANCE}}, {{DATE}}, {{HEURE}}, {{LIEU}}, {{ODJ}}</strong>. 
                            Elles seront remplacées automatiquement lors de la génération.
                        </div>
                        
                        <!-- Affichage dynamique via JS si le fichier existe -->
                        <div id="modele-status-badge"></div>
                        
                        <form action="<?= URLROOT ?>/admin/instances" method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-center mt-4 border-top pt-4">
                            <input type="hidden" name="upload_modele" value="1">
                            <input type="hidden" name="instance_id" id="upload_instance_id" value="">
                            <input type="file" name="modele_odt" class="form-control form-control-sm bg-light" accept=".odt" required>
                            <button type="submit" class="btn btn-sm btn-primary fw-bold px-3 shadow-sm text-nowrap"><i class="bi bi-upload me-1"></i>Déposer le modèle</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 bg-white px-4 py-3 border-top">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary fw-bold px-4 shadow-sm" id="btn-save-instance" onclick="submitInstanceForm()">
                    <i class="bi bi-floppy me-2"></i>Enregistrer
                </button>
            </div>
            
        </div>
    </div>
</div>

<!-- Formulaire de soumission caché (POST classique pour l'instance) -->
<form id="realSubmitForm" method="POST" action="<?= URLROOT ?>/admin/instances" style="display:none">
    <input type="hidden" name="save_instance" value="1">
    <input type="hidden" name="instance_id" id="fs_instance_id">
    <input type="hidden" name="nom" id="fs_nom">
    <input type="hidden" name="description" id="fs_description">
    <input type="hidden" name="nb_titulaires" id="fs_nb_titulaires">
    <input type="hidden" name="nb_suppleants" id="fs_nb_suppleants">
    <input type="hidden" name="quorum" id="fs_quorum">
    <input type="hidden" name="membres_json" id="fs_membres_json">
    <div id="fs_managers_container"></div>
</form>

<script>
const allUsers = <?= json_encode($all_users) ?>;
let currentMembers = [];
let memberCollapse = null;

// Initialisation au chargement de la page
document.addEventListener("DOMContentLoaded", function() {
    memberCollapse = new bootstrap.Collapse(document.getElementById('addMemberCardCollapse'), {
        toggle: false
    });
    
    // Gérer l'affichage du bouton "Enregistrer" selon l'onglet actif
    const instanceTabs = document.getElementById('instanceTabs');
    instanceTabs.addEventListener('shown.bs.tab', function (event) {
        const target = event.target.getAttribute('data-bs-target');
        const saveBtn = document.getElementById('btn-save-instance');
        if (target === '#tab-convocations') {
            saveBtn.style.display = 'none'; // Pas de sauvegarde générale pour les fichiers
        } else {
            saveBtn.style.display = 'block';
        }
    });
});

// ---------------------------------------------------------
// MODALE DE SUPPRESSION
// ---------------------------------------------------------
function confirmDelete(id, nom) {
    document.getElementById('deleteInstanceName').innerText = '"' + nom + '"';
    document.getElementById('deleteLinkBtn').href = '<?= URLROOT ?>/admin/instances?delete_id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// ---------------------------------------------------------
// VALIDATION INLINE & SOUMISSION DU FORMULAIRE PRINCIPAL
// ---------------------------------------------------------
function setFieldError(fieldId, errorId, condition) {
    const field = document.getElementById(fieldId);
    const error = document.getElementById(errorId);
    if (condition) {
        field.classList.add('is-invalid');
        error.style.display = 'block';
        return true;
    } else {
        field.classList.remove('is-invalid');
        error.style.display = 'none';
        return false;
    }
}

function submitInstanceForm() {
    let hasError = false;
    hasError |= setFieldError('nom', 'err-nom', !document.getElementById('nom').value.trim());
    hasError |= setFieldError('nb_titulaires', 'err-titulaires', !document.getElementById('nb_titulaires').value || document.getElementById('nb_titulaires').value < 1);
    hasError |= setFieldError('nb_suppleants', 'err-suppleants', document.getElementById('nb_suppleants').value === '');
    hasError |= setFieldError('quorum', 'err-quorum', !document.getElementById('quorum').value || document.getElementById('quorum').value < 1);

    if (hasError) {
        document.querySelector('#instanceTabs button[data-bs-target="#tab-infos"]').click();
        return;
    }

    document.getElementById('fs_instance_id').value = document.getElementById('instance_id').value;
    document.getElementById('fs_nom').value = document.getElementById('nom').value.trim();
    document.getElementById('fs_description').value = document.getElementById('description').value.trim();
    document.getElementById('fs_nb_titulaires').value = document.getElementById('nb_titulaires').value;
    document.getElementById('fs_nb_suppleants').value = document.getElementById('nb_suppleants').value;
    document.getElementById('fs_quorum').value = document.getElementById('quorum').value;
    document.getElementById('fs_membres_json').value = document.getElementById('membres_json').value;

    const managersContainer = document.getElementById('fs_managers_container');
    managersContainer.innerHTML = '';
    document.querySelectorAll('#pb-managers input[type="hidden"]').forEach(inp => {
        const clone = document.createElement('input');
        clone.type = 'hidden';
        clone.name = 'managers[]';
        clone.value = inp.value;
        managersContainer.appendChild(clone);
    });

    document.getElementById('realSubmitForm').submit();
}

// ---------------------------------------------------------
// GESTION DES MEMBRES (Onglet 3)
// ---------------------------------------------------------
function openMemberForm(isEdit = false) {
    if (!isEdit) {
        document.getElementById('m_edit_id').value = '';
        document.getElementById('m_form_title').innerText = "Nouveau Membre";
        document.getElementById('btnSubmitMember').innerHTML = '<i class="bi bi-check-lg me-1"></i>Ajouter à la liste';
        document.getElementById('m_nom').value = '';
        document.getElementById('m_prenom').value = '';
        document.getElementById('m_email').value = '';
        document.getElementById('m_qualite').value = '';
        document.getElementById('m_nom').classList.remove('is-invalid');
        document.getElementById('m_prenom').classList.remove('is-invalid');
        document.getElementById('m_account_alert').style.setProperty('display', 'none', 'important');
        document.getElementById('m_matched_user_id').value = '';
    }
    if(memberCollapse) memberCollapse.show();
}

function closeMemberForm() {
    if(memberCollapse) memberCollapse.hide();
}

function checkEmailMatch() {
    const email = document.getElementById('m_email').value.trim().toLowerCase();
    const alertBox = document.getElementById('m_account_alert');
    if (email.length < 3) { alertBox.style.setProperty('display', 'none', 'important'); return; }

    const match = allUsers.find(u => u.email && u.email.toLowerCase() === email);
    if (match) {
        document.getElementById('m_match_name').innerText = match.username;
        document.getElementById('m_matched_user_id').value = match.id;
        document.getElementById('m_link_account').checked = true;
        alertBox.style.setProperty('display', 'flex', 'important');
    } else {
        alertBox.style.setProperty('display', 'none', 'important');
        document.getElementById('m_matched_user_id').value = '';
    }
}

function saveMemberToList() {
    const nom = document.getElementById('m_nom').value.trim();
    const prenom = document.getElementById('m_prenom').value.trim();

    let err = false;
    if (!nom) { document.getElementById('m_nom').classList.add('is-invalid'); document.getElementById('err-m-nom').style.display = 'block'; err = true; } 
    else { document.getElementById('m_nom').classList.remove('is-invalid'); document.getElementById('err-m-nom').style.display = 'none'; }
    if (!prenom) { document.getElementById('m_prenom').classList.add('is-invalid'); document.getElementById('err-m-prenom').style.display = 'block'; err = true; }
    else { document.getElementById('m_prenom').classList.remove('is-invalid'); document.getElementById('err-m-prenom').style.display = 'none'; }
    if (err) return;

    const email = document.getElementById('m_email').value.trim();
    const qualite = document.getElementById('m_qualite').value.trim();
    const college = document.getElementById('m_college').value;
    const type_mandat = document.getElementById('m_mandat').value;

    let user_id = null, linkedName = null;
    if (document.getElementById('m_link_account').checked && document.getElementById('m_matched_user_id').value) {
        user_id = document.getElementById('m_matched_user_id').value;
        linkedName = document.getElementById('m_match_name').innerText;
    }

    const editId = document.getElementById('m_edit_id').value;
    if (editId) {
        const idx = currentMembers.findIndex(m => m.id === editId);
        if (idx > -1) currentMembers[idx] = { id: editId, nom, prenom, email, qualite, college, type_mandat, user_id, linkedName };
    } else {
        currentMembers.push({ id: 'temp_' + Date.now(), nom, prenom, email, qualite, college, type_mandat, user_id, linkedName });
    }

    renderMembersTables();
    closeMemberForm();
}

function editMember(memberId) {
    const m = currentMembers.find(x => x.id === memberId);
    if (!m) return;
    document.getElementById('m_edit_id').value = m.id;
    document.getElementById('m_form_title').innerText = "Modifier le membre";
    document.getElementById('btnSubmitMember').innerHTML = '<i class="bi bi-check-lg me-1"></i>Mettre à jour';
    document.getElementById('m_nom').value = m.nom;
    document.getElementById('m_prenom').value = m.prenom;
    document.getElementById('m_email').value = m.email || '';
    document.getElementById('m_qualite').value = m.qualite || '';
    document.getElementById('m_college').value = m.college;
    document.getElementById('m_mandat').value = m.type_mandat;

    if (m.user_id) {
        document.getElementById('m_matched_user_id').value = m.user_id;
        document.getElementById('m_match_name').innerText = m.linkedName || m.nom;
        document.getElementById('m_link_account').checked = true;
        document.getElementById('m_account_alert').style.setProperty('display', 'flex', 'important');
    } else {
        document.getElementById('m_matched_user_id').value = '';
        document.getElementById('m_account_alert').style.setProperty('display', 'none', 'important');
    }
    openMemberForm(true);
}

function removeMember(tempId) {
    currentMembers = currentMembers.filter(m => m.id !== tempId);
    renderMembersTables();
}

function renderMembersTables() {
    const tbodyAdmin = document.getElementById('tbody-administration');
    const tbodyPers = document.getElementById('tbody-personnel');
    tbodyAdmin.innerHTML = ''; tbodyPers.innerHTML = '';

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => bootstrap.Tooltip.getInstance(el)?.dispose());

    currentMembers.forEach(m => {
        const tr = document.createElement('tr');
        let emailDisplay = m.email
            ? `<span class="text-truncate d-inline-block align-middle" style="max-width:150px" title="${m.email}">${m.email}</span>`
            : '<span class="text-muted small opacity-50">—</span>';
        if (m.user_id) {
            emailDisplay += ` <i class="bi bi-person-check-fill text-success align-middle ms-1" style="font-size:1rem;cursor:default" data-bs-toggle="tooltip" data-bs-placement="top" title="Lié au compte : ${m.linkedName || m.nom}"></i>`;
        }
        
        const mandatBadge = m.type_mandat === 'titulaire'
            ? `<span class="badge bg-dark fw-normal">Titulaire</span>`
            : `<span class="badge bg-secondary bg-opacity-25 text-dark fw-normal">Suppléant</span>`;

        tr.innerHTML = `
            <td class="fw-bold text-truncate ps-3" title="${m.nom.toUpperCase()} ${m.prenom}">${m.nom.toUpperCase()} ${m.prenom}</td>
            <td class="text-truncate" title="${m.qualite || ''}">${m.qualite || ''}</td>
            <td>${mandatBadge}</td>
            <td>${emailDisplay}</td>
            <td class="text-end pe-3">
                <button type="button" class="btn btn-sm text-primary border-0 p-1 me-1 hover-opacity" onclick="editMember('${m.id}')"><i class="bi bi-pencil-square"></i></button>
                <button type="button" class="btn btn-sm text-danger border-0 p-1 hover-opacity" onclick="removeMember('${m.id}')"><i class="bi bi-trash3"></i></button>
            </td>`;

        if (m.college === 'administration') tbodyAdmin.appendChild(tr);
        else tbodyPers.appendChild(tr);
    });

    if (!tbodyAdmin.innerHTML) tbodyAdmin.innerHTML = '<tr><td colspan="5" class="text-center text-muted small py-4 opacity-50">Aucun membre dans ce collège</td></tr>';
    if (!tbodyPers.innerHTML) tbodyPers.innerHTML = '<tr><td colspan="5" class="text-center text-muted small py-4 opacity-50">Aucun membre dans ce collège</td></tr>';

    document.getElementById('membres_json').value = JSON.stringify(currentMembers);
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
}

// ---------------------------------------------------------
// COMPOSANT PILLBOX (Gestionnaires)
// ---------------------------------------------------------
class Pillbox {
    constructor(containerId, inputName, themeClass) {
        this.containerEl = document.getElementById(containerId);
        this.inputName = inputName; this.themeClass = themeClass;
        this.selectedIds = []; this.buildHTML();
    }
    buildHTML() {
        this.containerEl.innerHTML = '';
        this.wrapper = document.createElement('div'); this.wrapper.className = 'pillbox-container bg-light';
        this.pillsArea = document.createElement('div'); this.pillsArea.className = 'd-flex flex-wrap gap-1 align-items-center';
        this.input = document.createElement('input'); this.input.type = 'text'; this.input.className = 'pillbox-input'; this.input.placeholder = "Rechercher un agent...";
        this.dropdown = document.createElement('div'); this.dropdown.className = 'pillbox-dropdown';
        this.wrapper.appendChild(this.pillsArea); this.wrapper.appendChild(this.input); this.wrapper.appendChild(this.dropdown);
        this.containerEl.appendChild(this.wrapper);
        this.input.addEventListener('input', () => this.filterUsers());
        this.input.addEventListener('focus', () => this.filterUsers());
        document.addEventListener('click', (e) => { if (!this.wrapper.contains(e.target)) this.dropdown.style.display = 'none'; });
    }
    setSelection(idsArray) { this.selectedIds = idsArray.map(id => parseInt(id)); this.renderPills(); }
    renderPills() {
        this.pillsArea.innerHTML = '';
        this.selectedIds.forEach(id => {
            const user = allUsers.find(u => u.id === id);
            if (user) {
                const span = document.createElement('span'); span.className = `badge badge-pill ${this.themeClass} fw-bold shadow-sm`;
                span.innerHTML = `${user.username} <i class="bi bi-x-circle-fill ms-2" onclick="pbManagers.remove(${id})"></i>`;
                const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = `${this.inputName}[]`; hidden.value = id;
                this.pillsArea.appendChild(span); this.pillsArea.appendChild(hidden);
            }
        });
    }
    remove(id) { this.selectedIds = this.selectedIds.filter(i => i !== id); this.renderPills(); }
    filterUsers() {
        const query = this.input.value.toLowerCase();
        const matches = allUsers.filter(u =>
            (u.username.toLowerCase().includes(query) || (u.email && u.email.toLowerCase().includes(query)))
            && !this.selectedIds.includes(u.id)
        );
        this.dropdown.innerHTML = '';
        if (matches.length > 0) {
            this.dropdown.style.display = 'block';
            matches.forEach(u => {
                const div = document.createElement('div'); div.className = 'pillbox-item';
                div.innerHTML = `<div class="fw-bold">${u.username}</div><div class="small text-muted">${u.email || ''}</div>`;
                div.onclick = () => { this.selectedIds.push(u.id); this.input.value = ''; this.dropdown.style.display = 'none'; this.renderPills(); this.input.focus(); };
                this.dropdown.appendChild(div);
            });
        } else { this.dropdown.style.display = 'none'; }
    }
}
const pbManagers = new Pillbox('pb-managers', 'managers', 'bg-dark text-white');

// ---------------------------------------------------------
// OUVERTURE DE LA MODALE PRINCIPALE
// ---------------------------------------------------------
function openModal(inst = null) {
    document.querySelector('#instanceTabs button[data-bs-target="#tab-infos"]').click();
    closeMemberForm();

    // Reset erreurs
    ['nom','nb_titulaires','nb_suppleants','quorum'].forEach(id => {
        document.getElementById(id)?.classList.remove('is-invalid');
    });
    ['err-nom','err-titulaires','err-suppleants','err-quorum'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    const tabConvoc = document.getElementById('tabConvocationsLi');
    const statusBadge = document.getElementById('modele-status-badge');

    if (inst) {
        // Mode ÉDITION
        document.getElementById('modalTitle').innerText = "Modifier : " + inst.nom;
        document.getElementById('instance_id').value = inst.id;
        document.getElementById('nom').value = inst.nom;
        document.getElementById('description').value = inst.description || '';
        document.getElementById('nb_titulaires').value = inst.nb_titulaires;
        document.getElementById('nb_suppleants').value = inst.nb_suppleants;
        document.getElementById('quorum').value = inst.quorum_requis;
        
        pbManagers.setSelection(inst.managers || []);
        currentMembers = (inst.membres || []).map(m => {
            if (m.user_id) {
                const u = allUsers.find(user => user.id == m.user_id);
                if (u) m.linkedName = u.username;
            }
            return { ...m, id: 'db_' + m.id };
        });
        renderMembersTables();

        // Gérer l'onglet Modèle de convocation
        tabConvoc.style.display = 'block';
        document.getElementById('upload_instance_id').value = inst.id;
        
        // La propriété hasModele est passée par PHP dans la boucle foreach en haut du fichier !
        if (inst.hasModele) {
            statusBadge.innerHTML = `
                <div class="d-flex align-items-center bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 px-4 py-3">
                    <i class="bi bi-check-circle-fill fs-4 text-success me-3"></i>
                    <span class="fw-bold text-dark me-auto">Modèle en place</span>
                    <a href="<?= URLROOT ?>/uploads/modeles/modele_instance_${inst.id}.odt?v=<?= time() ?>" class="btn btn-sm btn-light border text-primary fw-bold shadow-sm rounded-pill px-3 me-2" target="_blank"><i class="bi bi-download me-1"></i>Télécharger</a>
                    <a href="<?= URLROOT ?>/admin/instances?delete_modele_id=${inst.id}" class="btn btn-sm btn-outline-danger border-0 rounded-circle" onclick="return confirm('Supprimer ce modèle ?')"><i class="bi bi-trash3"></i></a>
                </div>`;
        } else {
            statusBadge.innerHTML = `
                <div class="d-flex align-items-center bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-3 px-4 py-3">
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning me-3" style="color: #856404 !important;"></i>
                    <span class="fw-bold text-dark">Aucun modèle configuré</span>
                </div>`;
        }

    } else {
        // Mode CRÉATION
        document.getElementById('modalTitle').innerText = "Nouvelle Instance";
        document.getElementById('instance_id').value = '';
        document.getElementById('nom').value = '';
        document.getElementById('description').value = '';
        document.getElementById('nb_titulaires').value = 5;
        document.getElementById('nb_suppleants').value = 5;
        document.getElementById('quorum').value = 3;
        
        pbManagers.setSelection([]);
        currentMembers = [];
        renderMembersTables();

        // Cacher l'onglet Modèle de convocation
        tabConvoc.style.display = 'none';
        statusBadge.innerHTML = '';
    }

    new bootstrap.Modal(document.getElementById('instanceModal')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>