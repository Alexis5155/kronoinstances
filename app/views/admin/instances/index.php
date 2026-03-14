<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<div class="container py-4">

    <!-- EN-TÊTE -->
    <div class="page-header mb-4">
        <div class="d-flex align-items-center">
            <div class="page-header-avatar bg-success text-white shadow-sm me-3">
                <i class="bi bi-diagram-3"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Instances paritaires</h2>
                <p class="text-muted small mb-0">Gestion des instances paritaires et de leur composition</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= URLROOT ?>/admin" class="btn btn-light border fw-bold rounded-pill px-4">
                <i class="bi bi-arrow-left me-2"></i>Admin
            </a>
            <a href="<?= URLROOT ?>/admin/instances/create" class="btn btn-primary fw-bold rounded-pill px-4">
                <i class="bi bi-plus-lg me-2"></i>Nouvelle instance
            </a>
        </div>
    </div>

    <!-- GRILLE -->
    <div class="row g-4 align-items-stretch">
        <?php if (empty($instances)): ?>
            <div class="col-12">
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="bi bi-diagram-3"></i></div>
                    <h6>Aucune instance configurée</h6>
                    <p>Cliquez sur "Nouvelle instance" pour commencer.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($instances as $inst): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 shadow-sm rounded-4 overflow-hidden position-relative stat-card bg-white">
                    <div class="card-body p-4 d-flex flex-column h-100">

                        <h5 class="fw-bold text-dark mb-2 text-truncate" title="<?= htmlspecialchars($inst['nom']) ?>">
                            <?= htmlspecialchars($inst['nom']) ?>
                        </h5>

                        <p class="card-text text-muted small card-desc-clamp mb-4" title="<?= htmlspecialchars($inst['description']) ?>">
                            <?= !empty($inst['description']) ? htmlspecialchars($inst['description']) : '<em class="opacity-50">Aucune description renseignée</em>' ?>
                        </p>

                        <div class="mt-auto d-flex flex-column gap-2 mb-4">
                            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-1">
                                <span class="small text-muted fw-medium"><i class="bi bi-people me-2"></i>Membres totaux</span>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2"><?= count($inst['membres']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span><i class="bi bi-person-check me-2"></i><?= $inst['nb_titulaires'] ?> Titulaires</span>
                                <span><i class="bi bi-person-dash me-2"></i><?= $inst['nb_suppleants'] ?> Suppléants</span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mt-1">
                                <span><i class="bi bi-shield-lock me-2"></i><?= count($inst['managers']) ?> Gestionnaire(s)</span>
                                <span><i class="bi bi-check2-circle me-2"></i>Quorum : <?= $inst['quorum_requis'] ?></span>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-2">
                            <a href="<?= URLROOT ?>/admin/instances/edit/<?= $inst['id'] ?>"
                               class="btn btn-light border text-primary fw-bold flex-grow-1 rounded-pill">
                                <i class="bi bi-pencil-square me-2"></i>Gérer
                            </a>
                            <button type="button"
                                    class="btn btn-outline-danger border-0 rounded-circle shadow-sm"
                                    style="width:42px;height:42px;display:flex;align-items:center;justify-content:center;"
                                    onclick="confirmDelete(<?= $inst['id'] ?>, '<?= htmlspecialchars(addslashes($inst['nom'])) ?>')"
                                    title="Supprimer">
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
.card-desc-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    min-height: 2.5rem;
}
</style>

<!-- MODAL SUPPRESSION -->
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
                    <div class="fw-bold mb-2 text-dark"><i class="bi bi-info-circle-fill me-1"></i>Conséquences :</div>
                    <ul class="mb-0 ps-3 text-dark opacity-75">
                        <li class="mb-1">Les séances archivées ne seront <strong>plus rattachées</strong> à cette instance.</li>
                        <li class="mb-1">Il ne sera <strong>plus possible de créer</strong> de nouvelles séances.</li>
                        <li>La composition sera <strong>définitivement perdue</strong>.</li>
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

<script>
function confirmDelete(id, nom) {
    document.getElementById('deleteInstanceName').innerText = '"' + nom + '"';
    document.getElementById('deleteLinkBtn').href = '<?= URLROOT ?>/admin/instances?delete_id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
