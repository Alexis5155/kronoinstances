<?php include __DIR__ . '/../header.php'; ?>
<?php use app\models\User; ?>

<style>
    .role-card { transition: all 0.2s ease-in-out; border: 1px solid #dee2e6 !important; }
    .role-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important; }
    
    /* Zone d'action fixe pour l'alignement */
    .action-area { min-height: 90px; display: flex; flex-direction: column; justify-content: center; }

    /* Système de sélection des permissions */
    .perm-box {
        position: relative; cursor: pointer; transition: all 0.2s;
        border: 1px solid #dee2e6; background-color: #fff;
        border-radius: 10px; height: 100%;
    }
    .perm-box:hover { background-color: #f8f9fa; border-color: #0d6efd; }
    .perm-box.active { background-color: #f0f7ff; border-color: #0d6efd; }
    
    .perm-check-input {
        position: absolute; top: 1.2rem; right: 1.2rem;
        transform: scale(1.2); cursor: pointer;
    }
    
    .perm-code {
        font-family: monospace; font-size: 0.65rem; font-weight: 700;
        text-transform: uppercase; color: #6c757d; background: #f1f3f5; 
        padding: 3px 8px; border-radius: 4px; display: inline-block; margin-top: 8px;
    }
    
    .cat-title {
        font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
        color: #adb5bd; letter-spacing: 1.5px; margin-bottom: 1.2rem;
        padding-bottom: 0.5rem; border-bottom: 2px solid #f8f9fa; margin-top: 1.5rem;
    }

    /* Style du bloc restreint pour qu'il soit centré et remplisse l'espace */
    .restricted-box {
        background-color: #f8f9fa; border: 1px dashed #dee2e6;
        border-radius: 8px; height: 100%; display: flex;
        align-items: center; justify-content: center;
        color: #adb5bd; flex-direction: column;
    }
</style>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3 px-2">
        <div>
            <a href="<?= URLROOT ?>/admin" class="text-decoration-none small text-primary fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Administration
            </a>
            <h2 class="fw-bold mt-2 mb-0">Gestion des Rôles 🛡️</h2>
            <p class="text-muted mb-0 small">Définition des niveaux d'accès et des permissions</p>
        </div>
        <button type="button" class="btn btn-primary fw-bold shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#modalAddRole">
            <i class="bi bi-shield-plus me-2"></i>Nouveau Rôle
        </button>
    </div>

    <div class="row g-4 px-2">
        <?php foreach($roles as $role): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100 role-card border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-0">
                    <h5 class="fw-bold mb-0 text-primary small text-uppercase" style="letter-spacing: 0.5px;">
                        <i class="bi bi-shield-shaded me-2"></i><?= htmlspecialchars($role['nom']) ?>
                    </h5>
                    <span class="badge bg-light text-dark border fw-bold px-2 py-1" style="font-size: 0.65rem;">NIV. <?= $role['power'] ?></span>
                </div>
                
                <div class="card-body d-flex flex-column pt-0">
                    <div class="flex-grow-1 mb-3">
                        <p class="text-muted small mb-0 lh-sm">
                            <?= !empty($role['description']) ? htmlspecialchars($role['description']) : '<span class="opacity-50 fst-italic">Aucune description.</span>' ?>
                        </p>
                    </div>
                    
                    <div class="action-area">
                        <?php if($role['power'] >= $_SESSION['user_power']): ?>
                            <div class="restricted-box">
                                <i class="bi bi-lock-fill mb-1"></i>
                                <span class="text-uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 1px;">Accès restreint</span>
                            </div>
                        <?php else: ?>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-dark btn-sm fw-bold py-2 shadow-none" data-bs-toggle="modal" data-bs-target="#modalPerms<?= $role['id'] ?>">
                                    <i class="bi bi-ui-checks me-2"></i>Gérer les permissions
                                </button>
                                <?php if(!$role['is_immutable']): ?>
                                    <button type="button" class="btn btn-link btn-sm text-danger text-decoration-none fw-bold p-0" 
                                            data-bs-toggle="modal" data-bs-target="#modalDeleteRole"
                                            data-id="<?= $role['id'] ?>" data-name="<?= htmlspecialchars($role['nom']) ?>">
                                        Supprimer le rôle
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if($role['power'] < $_SESSION['user_power']): ?>
        <div class="modal fade" id="modalPerms<?= $role['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-bottom-0 pb-0 ps-4 pt-4">
                        <div>
                            <h5 class="modal-title fw-bold">Configuration des accès</h5>
                            <span class="badge bg-primary bg-opacity-10 text-primary border-primary border-opacity-25 fw-bold text-uppercase px-3 py-2 mt-2" style="font-size: 0.7rem;">
                                <?= htmlspecialchars($role['nom']) ?>
                            </span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <form id="formPerms<?= $role['id'] ?>" method="POST" action="<?= URLROOT ?>/admin/roles">
                            <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                            <input type="hidden" name="update_permissions" value="1">

                            <?php 
                                $activePerms = $roleModel->getPermissionIds($role['id']); 
                                $permsByCat = [];
                                foreach($permissions as $p) { $permsByCat[$p['category']][] = $p; }
                                
                                foreach($permsByCat as $catName => $catPerms): 
                            ?>
                                <div class="cat-title"><?= htmlspecialchars($catName) ?></div>
                                <div class="row g-3 mb-4">
                                    <?php foreach($catPerms as $perm): 
                                        if($perm['slug'] === 'manage_system') continue;
                                        $isChecked = in_array($perm['slug'], $activePerms);
                                        $checkId = "p_" . $role['id'] . "_" . $perm['slug'];
                                    ?>
                                    <div class="col-md-6">
                                        <label class="perm-box p-3 d-block <?= $isChecked ? 'active' : '' ?>" for="<?= $checkId ?>">
                                            <div class="pe-5">
                                                <div class="fw-bold text-dark small"><?= htmlspecialchars($perm['nom']) ?></div>
                                                <div class="text-muted small mt-1 lh-sm" style="font-size: 0.75rem;">
                                                    <?= htmlspecialchars($perm['description']) ?>
                                                </div>
                                                <div class="perm-code"><i class="bi bi-key-fill me-1"></i><?= $perm['slug'] ?></div>
                                            </div>
                                            <input class="form-check-input perm-check-input perm-dependency-check" 
                                                   type="checkbox" name="permissions[]" value="<?= $perm['slug'] ?>" 
                                                   id="<?= $checkId ?>" data-slug="<?= $perm['slug'] ?>" data-role="<?= $role['id'] ?>"
                                                   <?= $isChecked ? 'checked' : '' ?>
                                                   onchange="this.closest('.perm-box').classList.toggle('active', this.checked)">
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </form>
                    </div>
                    
                    <div class="modal-footer border-top bg-light p-3 px-4">
                        <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" form="formPerms<?= $role['id'] ?>" class="btn btn-primary fw-bold px-4 shadow-sm">Enregistrer les droits</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="modalAddRole" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 ps-4 pt-4">
                <h5 class="modal-title fw-bold">Créer un nouveau rôle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= URLROOT ?>/admin/roles">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Nom du rôle</label>
                        <input type="text" name="nom" class="form-control" placeholder="ex: Stagiaire" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Facultatif..."></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small text-muted text-uppercase">Niveau de Puissance (0-100)</label>
                        <input type="number" name="power" class="form-control" min="0" max="99" value="0" required>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light p-3 px-4">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="create_role" class="btn btn-primary fw-bold px-4 shadow-sm">Créer le rôle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDeleteRole" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 ps-4 pt-4">
                <h5 class="modal-title fw-bold text-danger">Supprimer le rôle ?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p>Confirmez-vous la suppression du rôle <strong id="delete_role_display"></strong> ?</p>
                <div class="alert alert-danger border-0 small mb-0"><i class="bi bi-exclamation-triangle me-2"></i> Action irréversible.</div>
            </div>
            <div class="modal-footer border-0 bg-light p-3">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" action="<?= URLROOT ?>/admin/roles">
                    <input type="hidden" name="delete_role" value="1">
                    <input type="hidden" name="role_id" id="input_delete_role_id">
                    <button type="submit" class="btn btn-danger fw-bold px-4">Confirmer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalDeleteRole = document.getElementById('modalDeleteRole');
    if (modalDeleteRole) {
        modalDeleteRole.addEventListener('show.bs.modal', function(event) {
            var btn = event.relatedTarget;
            document.getElementById('delete_role_display').textContent = btn.getAttribute('data-name');
            document.getElementById('input_delete_role_id').value = btn.getAttribute('data-id');
        });
    }

    const checkboxes = document.querySelectorAll('.perm-dependency-check');
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const roleId = this.getAttribute('data-role');
            const slug = this.getAttribute('data-slug');
            const isChecked = this.checked;

            const togglePerm = (targetSlug, state) => {
                const target = document.querySelector(`.perm-dependency-check[data-role="${roleId}"][data-slug="${targetSlug}"]`);
                if (target && target.checked !== state) {
                    target.checked = state;
                    target.closest('.perm-box').classList.toggle('active', state);
                }
            };

            if (isChecked) {
                if (slug === 'edit_service_actes') togglePerm('view_service_actes', true);
                if (slug === 'edit_all_actes') togglePerm('view_all_actes', true);
                if (slug === 'delete_acte') { togglePerm('edit_all_actes', true); togglePerm('view_all_actes', true); }
                if (slug === 'delete_users') togglePerm('manage_users', true);
            } else {
                if (slug === 'view_service_actes') togglePerm('edit_service_actes', false);
                if (slug === 'view_all_actes') { togglePerm('edit_all_actes', false); togglePerm('delete_acte', false); }
                if (slug === 'edit_all_actes') togglePerm('delete_acte', false);
                if (slug === 'manage_users') togglePerm('delete_users', false);
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../footer.php'; ?>