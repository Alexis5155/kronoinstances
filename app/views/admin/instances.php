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
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    min-height: 2.5rem; /* Force la place pour 2 lignes même s'il n'y en a qu'une */
}
</style>

<!-- ============================================================ -->
<!-- LISTE DES INSTANCES                                           -->
<!-- ============================================================ -->
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Instances Paritaires</h2>
            <p class="text-muted small mb-0">Gestion des instances paritaires et de leur composition</p>
        </div>
        <button type="button" class="btn btn-primary shadow-sm" onclick="openModal()">
            <i class="bi bi-plus-lg me-2"></i>Nouvelle Instance
        </button>
    </div>

    <!-- GRILLE DE CARTES RESPONSIVE -->
    <div class="row g-3 align-items-stretch">
        <?php if(empty($instances)): ?>
            <div class="col-12">
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-diagram-3 fs-1 d-block mb-2 opacity-25"></i>
                    Aucune instance paritaire n'a été créée.
                </div>
            </div>
        <?php else: ?>
            <?php foreach($instances as $inst): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm d-flex flex-column">
                    <div class="card-body flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title fw-bold text-primary mb-0 text-truncate pe-2" title="<?= htmlspecialchars($inst['nom']) ?>">
                                <?= htmlspecialchars($inst['nom']) ?>
                            </h5>
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill flex-shrink-0">
                                <?= count($inst['membres']) ?> membre<?= count($inst['membres']) > 1 ? 's' : '' ?>
                            </span>
                        </div>
                        
                        <p class="card-text text-muted small mb-3 card-desc-clamp" title="<?= htmlspecialchars($inst['description']) ?>">
                            <?= !empty($inst['description']) ? htmlspecialchars($inst['description']) : '<em>Aucune description</em>' ?>
                        </p>
                        
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            <span class="badge bg-light text-dark border small">
                                <i class="bi bi-person-check me-1"></i><?= $inst['nb_titulaires'] ?> Titulaires
                            </span>
                            <span class="badge bg-light text-dark border small">
                                <i class="bi bi-person-dash me-1"></i><?= $inst['nb_suppleants'] ?> Suppléants
                            </span>
                            <span class="badge bg-light text-dark border small">
                                <i class="bi bi-people me-1"></i><?= count($inst['managers']) ?> Gestionnaire<?= count($inst['managers']) > 1 ? 's' : '' ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top-0 pt-0 pb-3 px-3 d-flex gap-2 mt-auto">
                        <!-- Le bouton éditer passe bien l'objet JSON complet via data-instance et appelle openModal(this) -->
                        <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1"
                                data-instance="<?= htmlspecialchars(json_encode($inst), ENT_QUOTES, 'UTF-8') ?>"
                                onclick="openModal(this)">
                            <i class="bi bi-pencil-square me-1"></i>Éditer
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="confirmDelete(<?= $inst['id'] ?>, '<?= htmlspecialchars($inst['nom'], ENT_QUOTES) ?>')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODALE CONFIRMATION DE SUPPRESSION                           -->
<!-- ============================================================ -->
<div class="modal fade" id="deleteModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 bg-danger bg-opacity-10">
                <h5 class="modal-title fw-bold text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Supprimer l'instance
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Vous êtes sur le point de supprimer l'instance <strong id="deleteInstanceName"></strong>.</p>
                <div class="alert alert-warning border-0 py-2 small mb-0">
                    <div class="fw-bold mb-1"><i class="bi bi-info-circle-fill me-1"></i>Conséquences de la suppression :</div>
                    <ul class="mb-0 ps-3">
                        <li>Les séances déjà archivées resteront accessibles mais <strong>ne seront plus rattachées</strong> à cette instance.</li>
                        <li>Il ne sera <strong>plus possible de créer</strong> de nouvelles séances pour cette instance.</li>
                        <li>La composition (membres, gestionnaires) sera <strong>définitivement perdue</strong>.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <a id="deleteLinkBtn" href="#" class="btn btn-danger fw-bold">
                    <i class="bi bi-trash me-1"></i>Supprimer définitivement
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
        <div class="modal-content border-0 shadow">
            <form id="instanceForm" novalidate>
                <input type="hidden" name="instance_id" id="instance_id">
                <input type="hidden" name="save_instance" value="1">
                <input type="hidden" name="membres_json" id="membres_json" value="[]">

                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold" id="modalTitle">Nouvelle Instance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <!-- ONGLETS -->
                <div class="bg-light px-4 border-bottom">
                    <ul class="nav nav-tabs border-0" id="instanceTabs">
                        <li class="nav-item"><button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#tab-infos" type="button"><i class="bi bi-info-circle me-1"></i>Paramètres</button></li>
                        <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab-managers" type="button"><i class="bi bi-shield-lock me-1"></i>Gestionnaires</button></li>
                        <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#tab-membres" type="button" id="tabMembresTrigger"><i class="bi bi-people me-1"></i>Membres</button></li>
                    </ul>
                </div>

                <div class="modal-body p-4 tab-content">

                    <!-- ONGLET 1 : PARAMÈTRES -->
                    <div class="tab-pane fade show active" id="tab-infos">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nom de l'instance <span class="text-danger">*</span></label>
                            <input type="text" id="nom" class="form-control" placeholder="Ex : Comité Social Territorial">
                            <div class="field-error" id="err-nom">Le nom de l'instance est obligatoire.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea id="description" class="form-control" rows="2" placeholder="Description facultative..."></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nb. Titulaires <span class="text-danger">*</span></label>
                                <input type="number" id="nb_titulaires" class="form-control" min="1">
                                <div class="field-error" id="err-titulaires">Valeur requise (min. 1).</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Nb. Suppléants <span class="text-danger">*</span></label>
                                <input type="number" id="nb_suppleants" class="form-control" min="0">
                                <div class="field-error" id="err-suppleants">Valeur requise (min. 0).</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Quorum requis <span class="text-danger">*</span></label>
                                <input type="number" id="quorum" class="form-control" min="1">
                                <div class="field-error" id="err-quorum">Valeur requise (min. 1).</div>
                            </div>
                        </div>
                    </div>

                    <!-- ONGLET 2 : GESTIONNAIRES -->
                    <div class="tab-pane fade" id="tab-managers">
                        <div class="alert alert-info border-0 py-2 small mb-4">
                            <i class="bi bi-info-circle-fill me-1"></i>Ces agents pourront créer des séances et rédiger l'ordre du jour.
                        </div>
                        <label class="form-label small fw-bold">Gestionnaires (agents ayant un compte)</label>
                        <div id="pb-managers"></div>
                    </div>

                    <!-- ONGLET 3 : MEMBRES -->
                    <div class="tab-pane fade" id="tab-membres">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-muted">Composition de l'instance</h6>
                            <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="openMemberForm()">
                                <i class="bi bi-person-plus-fill me-1"></i>Ajouter un membre
                            </button>
                        </div>

                        <!-- FORMULAIRE DÉROULANT -->
                        <div class="collapse mb-4" id="addMemberCardCollapse">
                            <div class="card bg-light border border-primary border-opacity-25 shadow-sm">
                                <div class="card-body">
                                    <h6 class="fw-bold text-primary mb-3" id="m_form_title">Nouveau Membre</h6>
                                    <input type="hidden" id="m_edit_id" value="">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <input type="text" id="m_nom" class="form-control form-control-sm" placeholder="Nom *">
                                            <div class="field-error" id="err-m-nom">Le nom est obligatoire.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" id="m_prenom" class="form-control form-control-sm" placeholder="Prénom *">
                                            <div class="field-error" id="err-m-prenom">Le prénom est obligatoire.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="email" id="m_email" class="form-control form-control-sm" placeholder="Email" onkeyup="checkEmailMatch()">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" id="m_qualite" class="form-control form-control-sm" placeholder="Qualité (ex : Président)">
                                        </div>
                                        <div class="col-md-4">
                                            <select id="m_college" class="form-select form-select-sm">
                                                <option value="employeur">Collège Employeur</option>
                                                <option value="personnel">Collège Personnel</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <select id="m_mandat" class="form-select form-select-sm">
                                                <option value="titulaire">Titulaire</option>
                                                <option value="suppleant">Suppléant</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- ALERTE COMPTE TROUVÉ -->
                                    <div id="m_account_alert" class="alert alert-success mt-3 py-2 small mb-0 align-items-center justify-content-between" style="display:none !important;">
                                        <div><i class="bi bi-check-circle-fill me-2"></i>Compte trouvé : <strong id="m_match_name"></strong></div>
                                        <div class="form-check form-switch ms-3 mb-0">
                                            <input class="form-check-input" type="checkbox" id="m_link_account" checked>
                                            <label class="form-check-label" for="m_link_account">Lier ce compte</label>
                                        </div>
                                        <input type="hidden" id="m_matched_user_id">
                                    </div>

                                    <div class="mt-3 text-end">
                                        <button type="button" class="btn btn-sm btn-light border me-1" onclick="closeMemberForm()">Annuler</button>
                                        <button type="button" class="btn btn-sm btn-success" id="btnSubmitMember" onclick="saveMemberToList()">
                                            <i class="bi bi-check-lg me-1"></i>Ajouter à la liste
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TABLEAUX MEMBRES -->
                        <h6 class="text-primary fw-bold"><i class="bi bi-building me-1"></i>Collège Employeur</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered bg-white fixed-table mb-0">
                                <thead class="table-light text-muted small">
                                    <tr>
                                        <th style="width:30%">Nom Prénom</th>
                                        <th style="width:25%">Qualité</th>
                                        <th style="width:15%">Mandat</th>
                                        <th style="width:20%">Email</th>
                                        <th style="width:80px" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-employeur"></tbody>
                            </table>
                        </div>

                        <h6 class="text-success fw-bold"><i class="bi bi-people me-1"></i>Collège Représentants du Personnel</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered bg-white fixed-table mb-0">
                                <thead class="table-light text-muted small">
                                    <tr>
                                        <th style="width:30%">Nom Prénom</th>
                                        <th style="width:25%">Qualité</th>
                                        <th style="width:15%">Mandat</th>
                                        <th style="width:20%">Email</th>
                                        <th style="width:80px" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-personnel"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary fw-bold px-4" onclick="submitInstanceForm()">
                        <i class="bi bi-floppy me-1"></i>Enregistrer tout
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Formulaire de soumission caché (POST classique) -->
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
    const tbodyEmp = document.getElementById('tbody-employeur');
    const tbodyPers = document.getElementById('tbody-personnel');
    tbodyEmp.innerHTML = ''; tbodyPers.innerHTML = '';

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => bootstrap.Tooltip.getInstance(el)?.dispose());

    currentMembers.forEach(m => {
        const tr = document.createElement('tr');
        let emailDisplay = m.email
            ? `<span class="text-truncate d-inline-block align-middle" style="max-width:110px" title="${m.email}">${m.email}</span>`
            : '<span class="text-muted small">—</span>';
        if (m.user_id) {
            emailDisplay += ` <i class="bi bi-link-45deg text-success align-middle" style="font-size:1.1rem;cursor:default" data-bs-toggle="tooltip" data-bs-placement="top" title="Compte lié : ${m.linkedName || m.nom}"></i>`;
        }
        const mandatBadge = m.type_mandat === 'titulaire'
            ? `<span class="badge bg-dark">Titulaire</span>`
            : `<span class="badge bg-secondary">Suppléant</span>`;

        tr.innerHTML = `
            <td class="fw-bold text-truncate" title="${m.nom.toUpperCase()} ${m.prenom}">${m.nom.toUpperCase()} ${m.prenom}</td>
            <td class="text-truncate" title="${m.qualite || ''}">${m.qualite || ''}</td>
            <td>${mandatBadge}</td>
            <td>${emailDisplay}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm text-primary border-0 p-0 me-2" onclick="editMember('${m.id}')"><i class="bi bi-pencil-square"></i></button>
                <button type="button" class="btn btn-sm text-danger border-0 p-0" onclick="removeMember('${m.id}')"><i class="bi bi-x-circle-fill"></i></button>
            </td>`;

        if (m.college === 'employeur') tbodyEmp.appendChild(tr);
        else tbodyPers.appendChild(tr);
    });

    if (!tbodyEmp.innerHTML) tbodyEmp.innerHTML = '<tr><td colspan="5" class="text-center text-muted small py-2">Aucun membre dans ce collège</td></tr>';
    if (!tbodyPers.innerHTML) tbodyPers.innerHTML = '<tr><td colspan="5" class="text-center text-muted small py-2">Aucun membre dans ce collège</td></tr>';

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
        this.wrapper = document.createElement('div'); this.wrapper.className = 'pillbox-container';
        this.pillsArea = document.createElement('div'); this.pillsArea.className = 'd-flex flex-wrap gap-1 align-items-center';
        this.input = document.createElement('input'); this.input.type = 'text'; this.input.className = 'pillbox-input'; this.input.placeholder = "Rechercher un compte agent...";
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
                const span = document.createElement('span'); span.className = `badge badge-pill ${this.themeClass}`;
                span.innerHTML = `${user.username} <i class="bi bi-x-circle-fill ms-1" onclick="pbManagers.remove(${id})"></i>`;
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
const pbManagers = new Pillbox('pb-managers', 'managers', 'bg-info text-dark');

// ---------------------------------------------------------
// OUVERTURE DE LA MODALE PRINCIPALE
// ---------------------------------------------------------
function openModal(btn = null) {
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

    if (btn) {
        const inst = JSON.parse(btn.dataset.instance);
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
    } else {
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
    }

    new bootstrap.Modal(document.getElementById('instanceModal')).show();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
