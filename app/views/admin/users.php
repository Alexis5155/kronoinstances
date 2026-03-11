<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
.stat-card { z-index: 1; transition: transform 0.25s ease, box-shadow 0.25s ease; border: 1px solid rgba(0,0,0,0.05) !important; }
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important; }
.status-badge-active    { background:#d1fae5; color:#065f46; }
.status-badge-pending-e { background:#fef3c7; color:#92400e; }
.status-badge-pending-a { background:#fee2e2; color:#991b1b; }
.status-badge-inactive  { background:#f3f4f6; color:#6b7280; }
.status-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:.7rem; font-weight:700; letter-spacing:.3px; }
.action-btn { width:34px; height:34px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; border:0; background:transparent; transition:all .2s; }
.action-btn:hover { background:#f0f4ff; color:#0d6efd; }
.action-btn.danger:hover { background:#fff1f1; color:#dc3545; }
</style>

<div class="container py-4">

    <!-- EN-TÊTE -->
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div class="d-flex align-items-center">
            <div class="avatar-circle rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm me-3"
                 style="width:50px;height:50px;font-size:1.5rem;">
                <i class="bi bi-people"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing:-0.5px;">Comptes &amp; Permissions</h2>
                <p class="text-muted small mb-0">Gérez les utilisateurs, leurs rôles et permissions</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= URLROOT ?>/admin" class="btn btn-light fw-bold shadow-sm px-3 rounded-pill border d-none d-sm-inline-block">
                <i class="bi bi-arrow-left me-2"></i>Admin
            </a>
            <button type="button"
                    class="btn fw-bold shadow-sm px-3 rounded-pill position-relative <?= ($count_pending ?? 0) > 0 ? 'btn-warning' : 'btn-outline-secondary' ?>"
                    data-bs-toggle="modal" data-bs-target="#approveModal"
                    title="Comptes en attente d'approbation">
                <i class="bi bi-person-check me-2"></i>Approbations
                <?php if (($count_pending ?? 0) > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.65rem;">
                        <?= (int)$count_pending ?>
                    </span>
                <?php endif; ?>
            </button>
            <a href="<?= URLROOT ?>/admin/userAdd" class="btn btn-primary fw-bold shadow-sm px-4 rounded-pill">
                <i class="bi bi-person-plus-fill me-2"></i>Nouvel utilisateur
            </a>
        </div>
    </div>

    <!-- TABLEAU UTILISATEURS -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size:.88rem;">
                <thead style="background:#f8f9fc;">
                    <tr class="text-uppercase text-muted fw-bold" style="letter-spacing:.5px;font-size:.7rem;">
                        <th class="ps-4 py-3">Agent</th>
                        <th class="py-3">E-mail</th>
                        <th class="py-3 text-center">Statut</th>
                        <th class="py-3 text-center">Permissions</th>
                        <th class="py-3 text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;">
                                <i class="bi bi-people fs-2 opacity-50"></i>
                            </div>
                            <div class="fw-bold text-dark">Aucun utilisateur</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u):
                        $uid = (int)$u['id'];
                        $uPerms = $u['permissions'] ?? [];
                        $isMe   = ($uid === (int)($_SESSION['user_id'] ?? 0));
                        $isRoot = in_array('manage_system', $uPerms, true);
                        $displayName = trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? '')) ?: 'Sans nom';
                        $initials = '';
                        if (!empty($u['prenom'])) $initials .= mb_strtoupper(mb_substr($u['prenom'],0,1));
                        if (!empty($u['nom']))    $initials .= mb_strtoupper(mb_substr($u['nom'],0,1));
                        if (!$initials) $initials = mb_strtoupper(mb_substr($u['username'],0,2));

                        $statusMap = [
                            'active'           => ['label'=>'Actif',              'class'=>'status-badge-active'],
                            'pending_email'    => ['label'=>'E-mail non vérifié', 'class'=>'status-badge-pending-e'],
                            'pending_approval' => ['label'=>'En attente',         'class'=>'status-badge-pending-a'],
                            'inactive'         => ['label'=>'Inactif',            'class'=>'status-badge-inactive'],
                        ];
                        $sl = $statusMap[$u['status'] ?? 'active'] ?? $statusMap['active'];
                    ?>
                    <tr>
                        <td class="ps-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-light text-dark d-flex align-items-center justify-content-center fw-bold border"
                                     style="width:38px;height:38px;font-size:.85rem;flex-shrink:0;">
                                    <?= htmlspecialchars($initials) ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark d-flex align-items-center gap-2" style="font-size:.9rem;">
                                        <?= htmlspecialchars($displayName) ?>
                                        <?php if ($isRoot): ?>
                                            <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger" style="font-size:.65rem;">Système</span>
                                        <?php endif; ?>
                                        <?php if ($isMe): ?>
                                            <span class="badge rounded-pill bg-light text-muted border" style="font-size:.65rem;">Vous</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted" style="font-size:.75rem;">
                                        <i class="bi bi-at"></i><?= htmlspecialchars($u['username'] ?? '') ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted py-3" style="font-size:.83rem;">
                            <?= htmlspecialchars($u['email'] ?? '—') ?>
                        </td>
                        <td class="text-center py-3">
                            <span class="status-badge <?= $sl['class'] ?>"><?= $sl['label'] ?></span>
                        </td>
                        <td class="text-center py-3">
                            <span class="badge rounded-pill bg-light text-dark border px-2"><?= count($uPerms) ?> perm.</span>
                        </td>
                        <td class="text-end pe-4 py-3">
                            <a href="<?= URLROOT ?>/admin/userEdit/<?= $uid ?>"
                               class="action-btn text-muted me-1" title="Éditer">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <?php if ($isMe): ?>
                                <button class="action-btn text-muted opacity-50" disabled title="Impossible de se supprimer soi-même">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            <?php else: ?>
                                <button type="button"
                                        class="action-btn danger text-muted"
                                        title="Supprimer"
                                        onclick="confirmDeleteUser(<?= $uid ?>, '<?= htmlspecialchars(addslashes($displayName)) ?>')">
                                    <i class="bi bi-trash3"></i>
                                </button>
                                <form id="df<?= $uid ?>" method="POST" action="<?= URLROOT ?>/admin/users" class="d-none">
                                    <input type="hidden" name="csrf"        value="<?= htmlspecialchars($csrf ?? '') ?>">
                                    <input type="hidden" name="user_id"     value="<?= $uid ?>">
                                    <input type="hidden" name="delete_user" value="1">
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (($totalPages ?? 1) > 1): ?>
        <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between bg-light rounded-bottom-4">
            <span class="text-muted small"><?= $totalUsers ?? 0 ?> utilisateur<?= ($totalUsers ?? 0) > 1 ? 's' : '' ?></span>
            <nav><ul class="pagination pagination-sm mb-0 gap-1">
                <li class="page-item <?= ($page ?? 1) <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link rounded-3 border-0" href="?page=<?= ($page ?? 1) - 1 ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                <?php for ($i = max(1, ($page ?? 1) - 2); $i <= min(($totalPages ?? 1), ($page ?? 1) + 2); $i++): ?>
                    <li class="page-item <?= $i === ($page ?? 1) ? 'active' : '' ?>">
                        <a class="page-link rounded-3 border-0" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page ?? 1) >= ($totalPages ?? 1) ? 'disabled' : '' ?>">
                    <a class="page-link rounded-3 border-0" href="?page=<?= ($page ?? 1) + 1 ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul></nav>
        </div>
        <?php endif; ?>
    </div>
</div>


<!-- ══ MODALE SUPPRESSION ══════════════════════════════════════════ -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-danger bg-opacity-10 px-4 py-3 rounded-top-4">
                <h5 class="modal-title fw-bold text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Supprimer l'utilisateur
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p>Vous êtes sur le point de supprimer le compte de <strong id="deleteUserName" class="text-dark"></strong>.</p>
                <div class="alert alert-warning border-0 py-3 small mb-0 rounded-3">
                    <div class="fw-bold mb-1 text-dark"><i class="bi bi-info-circle-fill me-1"></i>Cette action est irréversible.</div>
                    <ul class="mb-0 ps-3 text-dark opacity-75">
                        <li class="mb-1">Le compte sera définitivement supprimé.</li>
                        <li>Les données associées (permissions, liaisons membres) seront perdues.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light fw-bold rounded-pill" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="confirmDeleteUserBtn" class="btn btn-danger fw-bold px-4 rounded-pill">
                    <i class="bi bi-trash me-1"></i>Supprimer
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ══ MODALE APPROBATION ══════════════════════════════════════════ -->
<div class="modal fade" id="approveModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

            <div class="modal-header bg-light border-0 px-4 py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning bg-opacity-25 text-warning d-flex align-items-center justify-content-center"
                         style="width:40px;height:40px;font-size:1.1rem;">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Comptes en attente d'approbation</div>
                        <div class="text-muted small">
                            <?php $pendingCount = (int)($count_pending ?? 0); ?>
                            <?= $pendingCount > 0 ? $pendingCount . ' compte' . ($pendingCount > 1 ? 's' : '') . ' à traiter' : 'Aucun compte en attente' ?>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0" style="max-height:420px;overflow-y:auto;">
                <?php
                $pendingUsers = $pending_users ?? [];
                $pendingPages = (int)($pending_pages ?? 1);
                $pendingPage  = (int)($pending_page ?? 1);
                ?>
                <?php if (empty($pendingUsers)): ?>
                    <div class="text-center py-5 text-muted">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width:70px;height:70px;">
                            <i class="bi bi-check-circle fs-2 text-success opacity-75"></i>
                        </div>
                        <div class="fw-bold text-dark">Tout est à jour !</div>
                        <div class="small">Aucun compte n'attend d'approbation.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($pendingUsers as $pu):
                        $puid  = (int)$pu['id'];
                        $pName = trim(($pu['prenom'] ?? '') . ' ' . ($pu['nom'] ?? '')) ?: $pu['username'];
                        $pInit = '';
                        if (!empty($pu['prenom'])) $pInit .= mb_strtoupper(mb_substr($pu['prenom'],0,1));
                        if (!empty($pu['nom']))    $pInit .= mb_strtoupper(mb_substr($pu['nom'],0,1));
                        if (!$pInit) $pInit = mb_strtoupper(mb_substr($pu['username'],0,2));
                    ?>
                    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom hover-bg">
                        <div class="rounded-circle bg-warning bg-opacity-25 text-warning d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                             style="width:40px;height:40px;font-size:.9rem;">
                            <?= htmlspecialchars($pInit) ?>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div class="fw-bold text-dark" style="font-size:.88rem;"><?= htmlspecialchars($pName) ?></div>
                            <div class="text-muted" style="font-size:.75rem;">
                                <?= htmlspecialchars($pu['email'] ?? '') ?>
                                &nbsp;·&nbsp;
                                <i class="bi bi-clock me-1"></i><?= isset($pu['created_at']) ? date('d/m/Y', strtotime($pu['created_at'])) : '—' ?>
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <!-- Approuver -->
                            <form method="POST" action="<?= URLROOT ?>/admin/users" class="d-inline">
                                <input type="hidden" name="csrf"         value="<?= htmlspecialchars($csrf ?? '') ?>">
                                <input type="hidden" name="user_id"      value="<?= $puid ?>">
                                <input type="hidden" name="approve_user" value="1">
                                <button type="submit" class="btn btn-sm fw-bold px-3 rounded-pill"
                                        style="background:#d1fae5;color:#065f46;border:none;">
                                    <i class="bi bi-check-lg me-1"></i>Approuver
                                </button>
                            </form>
                            <!-- Refuser -->
                            <form method="POST" action="<?= URLROOT ?>/admin/users" class="d-inline">
                                <input type="hidden" name="csrf"        value="<?= htmlspecialchars($csrf ?? '') ?>">
                                <input type="hidden" name="user_id"     value="<?= $puid ?>">
                                <input type="hidden" name="delete_user" value="1">
                                <input type="hidden" name="from_pending" value="1">
                                <button type="submit"
                                        class="btn btn-sm fw-bold px-3 rounded-pill"
                                        style="background:#fee2e2;color:#991b1b;border:none;"
                                        onclick="return confirm('Refuser et supprimer ce compte ?')">
                                    <i class="bi bi-x-lg me-1"></i>Refuser
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if ($pendingPages > 1): ?>
                    <div class="d-flex justify-content-center gap-1 py-3 border-top bg-light">
                        <?php for ($i = 1; $i <= $pendingPages; $i++): ?>
                            <a href="?page=<?= $page ?? 1 ?>&pending_page=<?= $i ?>"
                               class="btn btn-sm rounded-pill <?= $i === $pendingPage ? 'btn-primary' : 'btn-light border' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="modal-footer border-0 bg-white px-4 py-3 border-top">
                <button type="button" class="btn btn-light fw-bold rounded-pill" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<style>
.hover-bg { transition: background .15s; }
.hover-bg:hover { background: #f8f9fc; }
</style>

<script>
let pendingDeleteFormId = null;

function confirmDeleteUser(id, name) {
    pendingDeleteFormId = id;
    document.getElementById('deleteUserName').innerText = '"' + name + '"';
    document.getElementById('confirmDeleteUserBtn').onclick = function() {
        document.getElementById('df' + pendingDeleteFormId).submit();
    };
    new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
}

// Réouverture auto de la modale approbation après une action
<?php if (!empty($open_pending_modal)): ?>
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('approveModal')).show();
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
