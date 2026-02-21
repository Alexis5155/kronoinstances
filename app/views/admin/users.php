<?php include __DIR__ . '/../header.php'; ?>
<?php use app\models\User; ?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3 px-2">
        <div>
            <a href="<?= URLROOT ?>/admin" class="text-decoration-none small text-primary fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Administration
            </a>
            <h2 class="fw-bold mt-2 mb-0">Gestion des utilisateurs 👥</h2>
            <p class="text-muted mb-0 small">Administration des comptes agents et des accès</p>
        </div>
        <button type="button" class="btn btn-primary fw-bold shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#modalAddUser">
            <i class="bi bi-person-plus-fill me-2"></i>Nouvel agent
        </button>
    </div>

    <div class="card shadow-sm border-0 overflow-hidden mx-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-uppercase text-muted small fw-bold" style="letter-spacing: 0.5px;">
                        <th class="ps-4">Identifiant / Agent</th>
                        <th class="text-center">Service</th>
                        <th class="text-center">Rôle</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="small">
                <?php foreach($users as $u): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle p-2 me-3 d-none d-sm-flex text-dark">
                                    <i class="bi bi-person"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($u['username']) ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($u['email'] ?? '') ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold text-primary"><?= htmlspecialchars($u['service_nom'] ?? '—') ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-white text-dark border fw-bold text-uppercase px-2 py-1" style="font-size: 0.65rem;">
                                <?= htmlspecialchars($u['role_nom']) ?>
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group btn-group-sm">
                                <?php if($u['id'] == $_SESSION['user_id'] || $u['role_power'] < $_SESSION['user_power']): ?>
                                    <button type="button" class="btn btn-outline-primary border-0" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditUser"
                                            data-id="<?= $u['id'] ?>"
                                            data-username="<?= htmlspecialchars($u['username']) ?>"
                                            data-email="<?= htmlspecialchars($u['email']) ?>"
                                            data-role="<?= $u['role_id'] ?>" 
                                            data-role-power="<?= $u['role_power'] ?>"
                                            data-service="<?= $u['service_id'] ?>"
                                            title="Modifier">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm text-muted border-0 disabled" title="Verrouillé (Hiérarchie)">
                                        <i class="bi bi-lock-fill"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if($u['id'] != $_SESSION['user_id'] && $u['role_power'] < $_SESSION['user_power']): ?>
                                    <button type="button" class="btn btn-outline-danger border-0" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalDeleteUser"
                                            data-id="<?= $u['id'] ?>"
                                            data-username="<?= htmlspecialchars($u['username']) ?>"
                                            title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php elseif($u['id'] == $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-sm text-muted border-0 disabled" title="Vous ne pouvez pas vous supprimer">
                                        <i class="bi bi-person-x"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 ps-4">
                <h5 class="modal-title fw-bold mt-2">Nouveau compte agent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= URLROOT ?>/admin/users">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Identifiant de connexion</label>
                        <input type="text" name="username" class="form-control" placeholder="ex: j.dupont" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Adresse Email</label>
                        <input type="email" name="email" class="form-control" placeholder="agent@collectivite.fr" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Mot de passe provisoire</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Rattachement Service</label>
                            <select name="service_id" class="form-select">
                                <option value="">-- Aucun --</option>
                                <?php foreach($services as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Rôle attribué</label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach($roles as $r): ?>
                                    <?php if($r['power'] <= $_SESSION['user_power']): ?>
                                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light px-4">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="add_user" class="btn btn-primary fw-bold px-4 shadow-sm">Créer le compte</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 ps-4">
                <h5 class="modal-title fw-bold mt-2">Modifier l'agent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= URLROOT ?>/admin/users">
                <input type="hidden" name="user_id" id="input_edit_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Identifiant (Non modifiable)</label>
                        <input type="text" id="input_edit_username" class="form-control bg-light fw-bold" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Email</label>
                        <input type="email" name="email" id="input_edit_email" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Changer le mot de passe</label>
                        <input type="password" name="password" class="form-control" placeholder="Laisser vide pour conserver">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Service</label>
                            <select name="service_id" id="input_edit_service" class="form-select">
                                <option value="">-- Aucun --</option>
                                <?php foreach($services as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Rôle</label>
                            <select name="role_id" id="input_edit_role" class="form-select">
                                 <?php foreach($roles as $r): ?>
                                    <?php if($r['power'] <= $_SESSION['user_power']): ?>
                                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div id="role_warning" class="alert alert-warning mt-3 mb-0 py-2 small d-none">
                        <i class="bi bi-lock-fill me-1"></i> Vous ne pouvez pas modifier votre propre rôle ou celui d'un supérieur.
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light px-4">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="edit_user" class="btn btn-primary fw-bold px-4 shadow-sm">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDeleteUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 ps-4">
                <h5 class="modal-title fw-bold text-danger">Supprimer l'utilisateur ?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-start">
                <p class="mb-2">Confirmez-vous la suppression définitive de l'agent <strong id="delete_user_display"></strong> ?</p>
                <div class="alert alert-danger border-0 small mb-0 d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                    Toutes ses informations d'accès seront révoquées immédiatement.
                </div>
            </div>
            <div class="modal-footer border-0 bg-light p-3">
                <button type="button" class="btn btn-light fw-bold px-3" data-bs-dismiss="modal">Annuler</button>
                <a href="#" id="link_confirm_delete" class="btn btn-danger fw-bold px-4 shadow-sm">Confirmer la suppression</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const myId = "<?= $_SESSION['user_id'] ?>";
    const myPower = parseInt("<?= $_SESSION['user_power'] ?>");

    var modalEdit = document.getElementById('modalEditUser');
    if (modalEdit) {
        modalEdit.addEventListener('show.bs.modal', function(event) {
            var btn = event.relatedTarget;
            var targetId = btn.getAttribute('data-id');
            var targetRolePower = parseInt(btn.getAttribute('data-role-power'));

            document.getElementById('input_edit_id').value = targetId;
            document.getElementById('input_edit_username').value = btn.getAttribute('data-username');
            document.getElementById('input_edit_email').value = btn.getAttribute('data-email');
            document.getElementById('input_edit_service').value = btn.getAttribute('data-service');
            
            var roleSelect = document.getElementById('input_edit_role');
            var roleWarning = document.getElementById('role_warning');
            roleSelect.value = btn.getAttribute('data-role');

            // LOGIQUE DE PROTECTION RÔLE (Inchangée)
            if (targetId === myId || targetRolePower >= myPower) {
                roleSelect.setAttribute('disabled', 'disabled');
                var hiddenRole = document.createElement('input');
                hiddenRole.type = 'hidden';
                hiddenRole.name = 'role_id';
                hiddenRole.value = btn.getAttribute('data-role');
                hiddenRole.id = 'temp_hidden_role';
                roleSelect.parentNode.appendChild(hiddenRole);
                roleWarning.classList.remove('d-none');
            } else {
                roleSelect.removeAttribute('disabled');
                roleWarning.classList.add('d-none');
                var oldHidden = document.getElementById('temp_hidden_role');
                if(oldHidden) oldHidden.remove();
            }
        });
    }

    var modalDelete = document.getElementById('modalDeleteUser');
    if (modalDelete) {
        modalDelete.addEventListener('show.bs.modal', function(event) {
            var btn = event.relatedTarget;
            document.getElementById('delete_user_display').textContent = btn.getAttribute('data-username');
            document.getElementById('link_confirm_delete').href = "<?= URLROOT ?>/admin/users?delete_id=" + btn.getAttribute('data-id');
        });
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>