<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-4">

    <!-- EN-TÊTE -->
    <div class="page-header mb-4">
        <div class="d-flex align-items-center">
            <div class="page-header-avatar bg-info text-white shadow-sm me-3">
                <i class="bi bi-folder2-open"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Mes documents</h2>
                <p class="text-muted small mb-0">Retrouvez ici l'ensemble des documents qui vous ont été transmis.</p>
            </div>
        </div>
        <?php if ($canDeposit): ?>
        <button type="button" class="btn btn-primary fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
            <i class="bi bi-cloud-arrow-up-fill me-2"></i>Déposer un document
        </button>
        <?php endif; ?>
    </div>

    <!-- TABLEAU DOCUMENTS -->
    <div class="card-section card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-head-muted table-fixed" style="table-layout: fixed; min-width: 600px;">
                <thead class="text-muted border-bottom">
                    <tr>
                        <th class="ps-4 py-3" style="width: 40%;">Document</th>
                        <th class="py-3" style="width: 25%;">Auteur du dépôt</th>
                        <th class="py-3" style="width: 20%;">Date d'ajout</th>
                        <th class="text-end pe-4 py-3" style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mesDocuments)): ?>
                        <tr><td colspan="4">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
                                <h6>Votre espace est vide</h6>
                                <p>Aucun document ne vous a été transmis pour le moment.</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($mesDocuments as $doc):
                            $ext   = strtolower(pathinfo($doc['chemin_fichier'], PATHINFO_EXTENSION));
                            $icon  = 'bi-file-earmark text-secondary';
                            if ($ext === 'pdf') $icon = 'bi-filetype-pdf text-danger';
                            elseif (in_array($ext, ['doc','docx','odt'])) $icon = 'bi-filetype-docx text-primary';
                            elseif (in_array($ext, ['xls','xlsx','ods'])) $icon = 'bi-filetype-xlsx text-success';
                        ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi <?= $icon ?> fs-3 me-3 opacity-75 flex-shrink-0"></i>
                                    <div class="fw-bold text-dark text-truncate"><?= htmlspecialchars($doc['titre']) ?></div>
                                </div>
                            </td>
                            <td class="py-3">
                                <?php if ($doc['auteur'] === 'Système'): ?>
                                    <span class="badge bg-light border text-dark fw-normal">
                                        <i class="bi bi-gear me-1"></i>Système
                                    </span>
                                <?php else: ?>
                                    <span class="small fw-medium text-dark">
                                        <i class="bi bi-person-fill me-1 text-muted opacity-75"></i><?= htmlspecialchars($doc['auteur']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3">
                                <div class="small text-dark fw-medium"><?= date('d/m/Y', strtotime($doc['created_at'])) ?></div>
                                <div class="small text-muted opacity-75"><?= date('H:i', strtotime($doc['created_at'])) ?></div>
                            </td>
                            <td class="text-end pe-4 py-3">
                                <a href="<?= URLROOT ?>/documents/download/<?= $doc['id'] ?>"
                                   class="btn btn-sm btn-light border fw-bold rounded-pill px-3 me-1 hover-primary"
                                   title="Télécharger">
                                    <i class="bi bi-download"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                        title="Retirer de mon espace"
                                        onclick="openDeleteModal(<?= $doc['id'] ?>, '<?= htmlspecialchars(addslashes($doc['titre'])) ?>')">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODALE SUPPRESSION -->
<div class="modal fade" id="deleteDocModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-danger bg-opacity-10 border-0 rounded-top-4">
                <h5 class="modal-title fw-bold text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmer la suppression
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-1 text-muted">Êtes-vous sûr de vouloir retirer ce document de votre espace personnel ?</p>
                <p class="fw-bold text-dark mb-0" id="deleteDocTitle"></p>
                <div class="alert alert-warning border-0 small mt-3 mb-0 py-2 rounded-3">
                    <i class="bi bi-info-circle me-1"></i>Cette action est irréversible.
                </div>
            </div>
            <div class="modal-footer border-0 rounded-bottom-4 pt-0">
                <button type="button" class="btn btn-light fw-bold rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger fw-bold rounded-pill px-4">Supprimer</a>
            </div>
        </div>
    </div>
</div>

<!-- MODALE DÉPÔT -->
<?php if ($canDeposit): ?>
<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="<?= URLROOT ?>/documents/upload" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="modal-header bg-light border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-cloud-arrow-up-fill me-2 text-primary"></i>Déposer un document
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Titre du document <span class="text-danger">*</span></label>
                        <input type="text" name="titre" class="form-control" placeholder="Ex : Livret d'accueil 2026" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Fichier (PDF, Word, etc.) <span class="text-danger">*</span></label>
                        <input type="file" name="fichier" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold mb-2">Destinataires <span class="text-danger">*</span></label>
                        <div id="selectedUsersContainer" class="d-flex flex-wrap gap-2 mb-2 p-2 border rounded-3 bg-light" style="min-height: 46px;">
                            <span class="text-muted small align-self-center ms-1" id="noUserText">Aucun destinataire sélectionné</span>
                        </div>
                        <div class="position-relative">
                            <input type="text" id="userSearchInput" class="form-control"
                                   placeholder="Rechercher par nom, prénom ou identifiant…" autocomplete="off">
                            <div id="searchResults" class="position-absolute w-100 bg-white border rounded-3 shadow-sm mt-1 d-none"
                                 style="max-height: 200px; overflow-y: auto; z-index: 1000;"></div>
                        </div>
                        <div id="hiddenInputsContainer"></div>
                        <div class="form-text mt-2">
                            <i class="bi bi-info-circle me-1"></i>Recherchez et cliquez sur un utilisateur pour l'ajouter.
                        </div>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="notify" id="notifySwitch" value="1" checked>
                        <label class="form-check-label small fw-bold" for="notifySwitch">
                            Envoyer une notification applicative aux destinataires
                        </label>
                    </div>

                </div>
                <div class="modal-footer border-0 rounded-bottom-4 pt-0">
                    <button type="button" class="btn btn-light fw-bold rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4" id="submitUploadBtn">
                        Confirmer le dépôt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const allUsersData = <?= json_encode(array_map(function($u) {
    return [
        'id'       => $u['id'],
        'name'     => htmlspecialchars($u['prenom'] . ' ' . strtoupper($u['nom'])),
        'username' => htmlspecialchars($u['username'])
    ];
}, $allUsers ?? [])) ?>;
</script>
<?php endif; ?>

<script>
function openDeleteModal(id, title) {
    document.getElementById('deleteDocTitle').textContent = title;
    document.getElementById('confirmDeleteBtn').href = '<?= URLROOT ?>/documents/delete/' + id;
    new bootstrap.Modal(document.getElementById('deleteDocModal')).show();
}

<?php if ($canDeposit): ?>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput      = document.getElementById('userSearchInput');
    const searchResults    = document.getElementById('searchResults');
    const selectedContainer = document.getElementById('selectedUsersContainer');
    const hiddenInputs     = document.getElementById('hiddenInputsContainer');
    const noUserText       = document.getElementById('noUserText');
    const form             = document.getElementById('uploadForm');
    let selectedIds        = new Set();

    searchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        searchResults.innerHTML = '';
        if (query.length < 2) { searchResults.classList.add('d-none'); return; }

        const filtered = allUsersData.filter(u =>
            !selectedIds.has(u.id.toString()) &&
            (u.name.toLowerCase().includes(query) || u.username.toLowerCase().includes(query))
        );

        if (filtered.length > 0) {
            filtered.forEach(u => {
                const div = document.createElement('div');
                div.className = 'search-item p-2 border-bottom small';
                div.innerHTML = `<span class="fw-bold">${u.name}</span> <span class="text-muted">(${u.username})</span>`;
                div.onclick = () => addUserPill(u.id, u.name);
                searchResults.appendChild(div);
            });
        } else {
            searchResults.innerHTML = '<div class="p-2 text-muted small fst-italic">Aucun résultat</div>';
        }
        searchResults.classList.remove('d-none');
    });

    document.addEventListener('click', e => {
        if (e.target !== searchInput) searchResults.classList.add('d-none');
    });

    window.addUserPill = function (id, name) {
        id = id.toString();
        if (selectedIds.has(id)) return;
        selectedIds.add(id);
        noUserText.classList.add('d-none');
        searchInput.value = '';
        searchResults.classList.add('d-none');

        const pill = document.createElement('span');
        pill.className = 'badge bg-primary text-white d-flex align-items-center user-pill px-3 py-2 fw-normal';
        pill.id = 'pill_' + id;
        pill.innerHTML = `${name} <i class="bi bi-x-circle-fill ms-2" style="cursor:pointer;" onclick="removeUserPill('${id}')"></i>`;
        selectedContainer.appendChild(pill);

        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = 'users[]'; hidden.value = id; hidden.id = 'hidden_' + id;
        hiddenInputs.appendChild(hidden);
    };

    window.removeUserPill = function (id) {
        selectedIds.delete(id);
        document.getElementById('pill_' + id)?.remove();
        document.getElementById('hidden_' + id)?.remove();
        if (selectedIds.size === 0) noUserText.classList.remove('d-none');
    };

    form.addEventListener('submit', e => {
        if (selectedIds.size === 0) {
            e.preventDefault();
            alert('Veuillez sélectionner au moins un destinataire.');
        }
    });
});
<?php endif; ?>
</script>

<style>
    .search-item { cursor: pointer; transition: background-color 0.15s; }
    .search-item:hover { background-color: #f8f9fa; }
    .user-pill { animation: fadeIn 0.2s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
