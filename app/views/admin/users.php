<?php include __DIR__ . '/../layouts/header.php'; ?>
<?php
$page = (int)($page ?? 1);
$totalPages = (int)($totalPages ?? 1);
$limit = (int)($limit ?? 20);
$totalUsers = (int)($totalUsers ?? 0);
$baseUrl = URLROOT . '/admin/users';
$pageUrl = function(int $p) use ($baseUrl) { return $baseUrl . '?page=' . max(1, $p); };
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-end mb-4 px-2">
        <div>
            <a href="<?= URLROOT ?>/admin" class="text-decoration-none small text-primary fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Administration
            </a>
            <h2 class="fw-bold mt-2 mb-0">Gestion des utilisateurs</h2>
        </div>
        <a href="<?= URLROOT ?>/admin/userAdd" class="btn btn-primary fw-bold shadow-sm px-4">
            <i class="bi bi-person-plus-fill me-2"></i>Nouvel utilisateur
        </a>
    </div>

    <div class="card shadow-sm border-0 mx-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-uppercase text-muted small fw-bold" style="letter-spacing: 0.5px;">
                        <th class="ps-4">Agent / Connexion</th>
                        <th class="text-center">Email</th>
                        <th class="text-center">Permissions</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="small">
                <?php if (empty($users)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Aucun utilisateur.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): 
                        $uid = (int)($u['id']);
                        $uPerms = $u['permissions'] ?? [];
                        $isMe = ($uid === (int)($_SESSION['user_id'] ?? 0));
                        $isRoot = in_array('manage_system', $uPerms, true);
                        $displayName = trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''));
                    ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle p-2 me-3 text-dark">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                            <?= $displayName !== '' ? htmlspecialchars($displayName) : 'Nom non renseigné' ?>
                                            <?php if ($isRoot): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-1 py-0">Système</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <i class="bi bi-box-arrow-in-right me-1"></i><?= htmlspecialchars($u['username'] ?? '') ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center text-muted"><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                            <td class="text-center"><span class="badge bg-white text-dark border px-2 py-1"><?= count($uPerms) ?> perm.</span></td>
                            <td class="text-end pe-4">
                                <a href="<?= URLROOT ?>/admin/userEdit/<?= $uid ?>" class="btn btn-sm btn-outline-primary border-0" title="Éditer">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <?php if ($isMe): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary border-0 disabled"><i class="bi bi-trash"></i></button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger border-0" title="Supprimer" onclick="if(confirm('Supprimer cet utilisateur ?')) document.getElementById('deleteForm<?= $uid ?>').submit();">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <form id="deleteForm<?= $uid ?>" method="POST" action="<?= URLROOT ?>/admin/users" class="d-none">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
                                        <input type="hidden" name="user_id" value="<?= $uid ?>">
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

        <?php if ($totalPages > 1): ?>
            <div class="card-body bg-white border-top py-3 d-flex justify-content-center">
                <nav><ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>"><a class="page-link" href="<?= htmlspecialchars($pageUrl($page - 1)) ?>"><i class="bi bi-chevron-left"></i></a></li>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>"><a class="page-link" href="<?= htmlspecialchars($pageUrl($i)) ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>"><a class="page-link" href="<?= htmlspecialchars($pageUrl($page + 1)) ?>"><i class="bi bi-chevron-right"></i></a></li>
                </ul></nav>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>