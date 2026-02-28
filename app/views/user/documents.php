<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-4">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-folder2-open me-2 text-primary"></i>Mes documents</h2>
            <p class="text-muted small mb-0">Retrouvez ici l'ensemble des documents qui vous ont été transmis.</p>
        </div>
        
        <?php if ($canDeposit): ?>
        <button type="button" class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
            <i class="bi bi-cloud-arrow-up-fill me-2"></i>Déposer un document
        </button>
        <?php endif; ?>
    </div>

    <!-- Liste des documents -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase text-muted small" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        <tr>
                            <th class="ps-4 py-3">Document</th>
                            <th class="py-3">Auteur du dépôt</th>
                            <th class="py-3">Date d'ajout</th>
                            <th class="text-end pe-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mesDocuments)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                        <i class="bi bi-inbox fs-1 opacity-50"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark">Votre espace est vide</h6>
                                    <p class="small mb-0">Aucun document ne vous a été transmis pour le moment.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mesDocuments as $doc): ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <?php 
                                            // Icône selon extension basique
                                            $ext = strtolower(pathinfo($doc['chemin_fichier'], PATHINFO_EXTENSION));
                                            $icon = 'bi-file-earmark';
                                            $color = 'text-secondary';
                                            if ($ext === 'pdf') { $icon = 'bi-filetype-pdf'; $color = 'text-danger'; }
                                            if (in_array($ext, ['doc', 'docx', 'odt'])) { $icon = 'bi-filetype-doc'; $color = 'text-primary'; }
                                        ?>
                                        <i class="bi <?= $icon ?> <?= $color ?> fs-3 me-3 opacity-75"></i>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($doc['titre']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($doc['auteur'] === 'Système'): ?>
                                        <span class="badge bg-light border text-dark fw-normal"><i class="bi bi-robot me-1 text-primary"></i> Automatique</span>
                                    <?php else: ?>
                                        <span class="small fw-medium text-dark"><i class="bi bi-person-fill me-1 text-muted"></i> <?= htmlspecialchars($doc['auteur']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small text-muted fw-medium">
                                        <?= date('d/m/Y', strtotime($doc['created_at'])) ?> <br>
                                        <span class="opacity-50"><?= date('H:i', strtotime($doc['created_at'])) ?></span>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="<?= URLROOT ?>/documents/download/<?= $doc['id'] ?>" class="btn btn-sm btn-light border text-primary fw-bold shadow-sm rounded-pill px-3 me-2 hover-primary" title="Télécharger">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <!-- Bouton de suppression déclenchant la Modale -->
                                    <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle" title="Retirer de mon espace" 
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
</div>

<!-- ========================================== -->
<!-- MODALE DE SUPPRESSION (REMPLACE CONFIRM)   -->
<!-- ========================================== -->
<div class="modal fade" id="deleteDocModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger bg-opacity-10 border-0 px-4 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmer la suppression</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-1">Êtes-vous sûr de vouloir retirer ce document de votre espace personnel ?</p>
                <p class="fw-bold text-dark mb-0" id="deleteDocTitle"></p>
                <div class="alert alert-warning border-0 small mt-3 mb-0 py-2">
                    <i class="bi bi-info-circle me-1"></i> Cette action est irréversible.
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger fw-bold px-4">Supprimer</a>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODALE DE DÉPÔT MANUEL AVEC CAPSULES       -->
<!-- ========================================== -->
<?php if ($canDeposit): ?>
<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= URLROOT ?>/documents/upload" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="modal-header bg-light border-0 px-4 py-3 rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cloud-arrow-up-fill me-2 text-primary"></i>Déposer un document</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Titre du document <span class="text-danger">*</span></label>
                        <input type="text" name="titre" class="form-control" placeholder="Ex: Livret d'accueil 2026" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Fichier (PDF, Word, etc.) <span class="text-danger">*</span></label>
                        <input type="file" name="fichier" class="form-control" required>
                    </div>

                    <!-- SYSTÈME DE RECHERCHE ET CAPSULES -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold mb-2">Destinataires <span class="text-danger">*</span></label>
                        
                        <!-- Zone où s'afficheront les capsules -->
                        <div id="selectedUsersContainer" class="d-flex flex-wrap gap-2 mb-2 p-2 border rounded bg-light min-h-50" style="min-height: 46px;">
                            <span class="text-muted small align-self-center ms-1" id="noUserText">Aucun destinataire sélectionné</span>
                        </div>
                        
                        <!-- Barre de recherche -->
                        <div class="position-relative">
                            <input type="text" id="userSearchInput" class="form-control" placeholder="Rechercher un utilisateur par nom, prénom ou identifiant..." autocomplete="off">
                            <div id="searchResults" class="position-absolute w-100 bg-white border rounded shadow-sm mt-1 d-none" style="max-height: 200px; overflow-y: auto; z-index: 1000;">
                                <!-- Les résultats de recherche s'injecteront ici en JS -->
                            </div>
                        </div>
                        
                        <!-- Container caché pour stocker les inputs réels envoyés au serveur -->
                        <div id="hiddenInputsContainer"></div>
                        
                        <div class="form-text x-small mt-2"><i class="bi bi-info-circle me-1"></i> Recherchez et cliquez sur un utilisateur pour l'ajouter à la liste des destinataires.</div>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="notify" id="notifySwitch" value="1" checked>
                        <label class="form-check-label small fw-bold" for="notifySwitch">Envoyer une notification applicative aux destinataires</label>
                    </div>

                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4" id="submitUploadBtn">Confirmer le dépôt</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- On prépare les données JSON des utilisateurs pour la recherche JS -->
<script>
    const allUsersData = <?= json_encode(array_map(function($u) {
        return [
            'id' => $u['id'],
            'name' => htmlspecialchars($u['prenom'] . ' ' . strtoupper($u['nom'])),
            'username' => htmlspecialchars($u['username'])
        ];
    }, $allUsers ?? [])) ?>;
</script>
<?php endif; ?>

<style>
    .hover-primary { transition: all 0.2s; }
    .hover-primary:hover { background-color: #0d6efd !important; color: white !important; }
    .search-item { cursor: pointer; transition: background-color 0.2s; }
    .search-item:hover { background-color: #f8f9fa; }
    .user-pill { animation: fadeIn 0.2s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>

<script>
// --- LOGIQUE SUPPRESSION MODALE ---
function openDeleteModal(id, title) {
    document.getElementById('deleteDocTitle').textContent = title;
    document.getElementById('confirmDeleteBtn').href = '<?= URLROOT ?>/documents/delete/' + id;
    var myModal = new bootstrap.Modal(document.getElementById('deleteDocModal'));
    myModal.show();
}

// --- LOGIQUE CAPSULES DESTINATAIRES ---
<?php if ($canDeposit): ?>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearchInput');
    const searchResults = document.getElementById('searchResults');
    const selectedContainer = document.getElementById('selectedUsersContainer');
    const hiddenInputs = document.getElementById('hiddenInputsContainer');
    const noUserText = document.getElementById('noUserText');
    const form = document.getElementById('uploadForm');

    // Tableau stockant les ID déjà sélectionnés
    let selectedIds = new Set();

    // Fonction de recherche
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        searchResults.innerHTML = '';
        
        if (query.length < 2) {
            searchResults.classList.add('d-none');
            return;
        }

        const filtered = allUsersData.filter(u => 
            !selectedIds.has(u.id.toString()) && 
            (u.name.toLowerCase().includes(query) || u.username.toLowerCase().includes(query))
        );

        if (filtered.length > 0) {
            filtered.forEach(u => {
                const div = document.createElement('div');
                div.className = 'search-item p-2 border-bottom small';
                div.innerHTML = `<span class="fw-bold">${u.name}</span> <span class="text-muted opacity-75">(${u.username})</span>`;
                div.onclick = function() { addUserPill(u.id, u.name); };
                searchResults.appendChild(div);
            });
            searchResults.classList.remove('d-none');
        } else {
            searchResults.innerHTML = '<div class="p-2 text-muted small italic">Aucun résultat</div>';
            searchResults.classList.remove('d-none');
        }
    });

    // Cacher les résultats si clic à l'extérieur
    document.addEventListener('click', function(e) {
        if (e.target !== searchInput && e.target !== searchResults) {
            searchResults.classList.add('d-none');
        }
    });

    // Ajouter une capsule (Pill)
    window.addUserPill = function(id, name) {
        id = id.toString();
        if (selectedIds.has(id)) return;

        selectedIds.add(id);
        noUserText.classList.add('d-none');
        searchInput.value = '';
        searchResults.classList.add('d-none');

        // Création visuelle de la capsule
        const pill = document.createElement('span');
        pill.className = 'badge bg-primary text-white d-flex align-items-center user-pill px-3 py-2 fw-normal';
        pill.id = 'pill_' + id;
        pill.innerHTML = `
            ${name} 
            <i class="bi bi-x-circle-fill ms-2" style="cursor:pointer;" onclick="removeUserPill('${id}')"></i>
        `;
        selectedContainer.appendChild(pill);

        // Création de l'input caché pour le formulaire PHP
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'users[]';
        hidden.value = id;
        hidden.id = 'hidden_' + id;
        hiddenInputs.appendChild(hidden);
    };

    // Retirer une capsule
    window.removeUserPill = function(id) {
        selectedIds.delete(id);
        document.getElementById('pill_' + id).remove();
        document.getElementById('hidden_' + id).remove();
        
        if (selectedIds.size === 0) {
            noUserText.classList.remove('d-none');
        }
    };

    // Validation du formulaire pour obliger d'avoir au moins 1 destinataire
    form.addEventListener('submit', function(e) {
        if (selectedIds.size === 0) {
            e.preventDefault();
            alert("Veuillez sélectionner au moins un destinataire.");
        }
    });
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
