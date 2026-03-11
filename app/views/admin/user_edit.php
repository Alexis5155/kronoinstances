<?php include __DIR__ . '/../layouts/header.php'; ?>
<?php
$displayName = trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''));
if (empty($displayName)) $displayName = $u['username'];
$initials = '';
if (!empty($u['prenom'])) $initials .= mb_strtoupper(mb_substr($u['prenom'],0,1));
if (!empty($u['nom']))    $initials .= mb_strtoupper(mb_substr($u['nom'],0,1));
if (!$initials) $initials = mb_strtoupper(mb_substr($u['username'],0,2));

$status            = $u['status'] ?? 'active';
$isPendingApproval = ($status === 'pending_approval');
$isPendingEmail    = ($status === 'pending_email');

$avatarColors = [
    'active'           => ['bg'=>'#eef2ff','color'=>'#4f46e5'],
    'pending_email'    => ['bg'=>'#fef9c3','color'=>'#854d0e'],
    'pending_approval' => ['bg'=>'#fee2e2','color'=>'#991b1b'],
    'inactive'         => ['bg'=>'#f3f4f6','color'=>'#6b7280'],
];
$ac = $avatarColors[$status] ?? $avatarColors['active'];

$statusLabels = [
    'active'           => ['label'=>'Actif',              'bg'=>'#d1fae5','color'=>'#065f46'],
    'pending_email'    => ['label'=>'E-mail non vérifié', 'bg'=>'#fef3c7','color'=>'#92400e'],
    'pending_approval' => ['label'=>'En attente',         'bg'=>'#fee2e2','color'=>'#991b1b'],
    'inactive'         => ['label'=>'Inactif',            'bg'=>'#f3f4f6','color'=>'#6b7280'],
];
$sl = $statusLabels[$status] ?? $statusLabels['active'];
?>

<style>
.stat-card { z-index:1; transition: transform .25s ease, box-shadow .25s ease; border: 1px solid rgba(0,0,0,0.05) !important; }
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,.08) !important; }
.ki-section-icon { width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:.9rem; flex-shrink:0; }
.ki-form-label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#6b7280; margin-bottom:.4rem; display:block; }
.perm-card { border:1px solid rgba(0,0,0,.08); border-radius:11px; padding:.85rem 1rem; cursor:pointer; transition:all .2s; }
.perm-card:hover  { border-color:#0d6efd; background:#f0f4ff; }
.perm-card.checked{ border-color:#0d6efd; background:#e8f0fe; }
.v-nav-link { display:flex; align-items:center; gap:.6rem; padding:.8rem 1.25rem; font-size:.88rem; font-weight:600; color:#6b7280; text-decoration:none; cursor:pointer; background:none; border:none; border-left:3px solid transparent; transition:all .2s; width:100%; text-align:left; }
.v-nav-link:hover  { background:#f8f9fa; color:#111827; }
.v-nav-link.active { background:#f5f5ff; color:#4f46e5; border-left-color:#0d6efd; }
</style>

<div class="container py-4" style="max-width:1080px;">

    <!-- EN-TÊTE -->
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div class="d-flex align-items-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm me-3"
                 style="width:50px;height:50px;font-size:1.2rem;background:<?= $ac['bg'] ?>;color:<?= $ac['color'] ?>;">
                <?= htmlspecialchars($initials) ?>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="letter-spacing:-0.5px;">
                    Modifier le profil
                </h2>
            </div>
        </div>
        <a href="<?= URLROOT ?>/admin/users" class="btn btn-light fw-bold shadow-sm px-3 rounded-pill border d-none d-sm-inline-block">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
    </div>

    <!-- ── Alerte APPROBATION ── -->
    <?php if ($isPendingApproval): ?>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="border-left:4px solid #dc2626 !important;">
        <div class="card-body p-4">
            <div class="d-flex align-items-start gap-3">
                <div class="ki-section-icon flex-shrink-0" style="background:#fee2e2;color:#dc2626;width:38px;height:38px;">
                    <i class="bi bi-person-exclamation"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold text-danger mb-1">Compte en attente d'approbation</div>
                    <div class="text-muted small mb-3">Cet utilisateur a créé son compte mais attend la validation d'un administrateur avant de pouvoir se connecter.</div>
                    <div class="d-flex gap-2 flex-wrap">
                        <form method="POST" action="<?= URLROOT ?>/admin/userEdit/<?= $u['id'] ?>" class="d-inline">
                            <input type="hidden" name="csrf"         value="<?= htmlspecialchars($csrf ?? '') ?>">
                            <input type="hidden" name="approve_user" value="1">
                            <button type="submit" class="btn btn-sm fw-bold px-3 rounded-pill"
                                    style="background:#d1fae5;color:#065f46;border:none;">
                                <i class="bi bi-check-lg me-1"></i>Approuver le compte
                            </button>
                        </form>
                        <form method="POST" action="<?= URLROOT ?>/admin/users" class="d-inline">
                            <input type="hidden" name="csrf"        value="<?= htmlspecialchars($csrf ?? '') ?>">
                            <input type="hidden" name="user_id"     value="<?= $u['id'] ?>">
                            <input type="hidden" name="delete_user" value="1">
                            <button type="submit" class="btn btn-sm fw-bold px-3 rounded-pill"
                                    style="background:#fee2e2;color:#991b1b;border:none;"
                                    onclick="return confirm('Refuser et supprimer ce compte ?')">
                                <i class="bi bi-x-lg me-1"></i>Refuser &amp; supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Alerte E-MAIL NON VÉRIFIÉ ── -->
    <?php if ($isPendingEmail): ?>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="border-left:4px solid #d97706 !important;">
        <div class="card-body p-4">
            <div class="d-flex align-items-start gap-3">
                <div class="ki-section-icon flex-shrink-0" style="background:#fef3c7;color:#d97706;width:38px;height:38px;">
                    <i class="bi bi-envelope-exclamation"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold mb-1" style="color:#92400e;">Adresse e-mail non vérifiée</div>
                    <div class="text-muted small mb-3">
                        L'utilisateur n'a pas encore cliqué sur le lien de confirmation envoyé à
                        <strong><?= htmlspecialchars($u['email'] ?? '') ?></strong>.
                    </div>
                    <form method="POST" action="<?= URLROOT ?>/admin/userEdit/<?= $u['id'] ?>" class="d-inline">
                        <input type="hidden" name="csrf"               value="<?= htmlspecialchars($csrf ?? '') ?>">
                        <input type="hidden" name="force_verify_email" value="1">
                        <button type="submit" class="btn btn-sm fw-bold px-3 rounded-pill"
                                style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;">
                            <i class="bi bi-envelope-check me-1"></i>Forcer la validation de l'e-mail
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= URLROOT ?>/admin/userEdit/<?= $u['id'] ?>">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">

        <div class="row g-4">

            <!-- ── Colonne gauche ── -->
            <div class="col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden stat-card sticky-top" style="top:90px;">
                    <div class="p-4 text-center">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold mb-3"
                             style="width:72px;height:72px;font-size:1.5rem;background:<?= $ac['bg'] ?>;color:<?= $ac['color'] ?>;">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                        <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($displayName) ?></div>
                        <div class="text-muted small mb-2">@<?= htmlspecialchars($u['username']) ?></div>

                        <?php if (in_array('manage_system', $u['permissions'] ?? [])): ?>
                            <span class="badge rounded-pill px-3 py-1 mb-1" style="background:#fee2e2;color:#991b1b;font-size:.7rem;">
                                <i class="bi bi-shield-lock-fill me-1"></i>Système
                            </span><br>
                        <?php endif; ?>

                        <span class="badge rounded-pill px-3 py-1" style="background:<?= $sl['bg'] ?>;color:<?= $sl['color'] ?>;font-size:.7rem;">
                            <?= $sl['label'] ?>
                        </span>
                    </div>

                    <div class="border-top">
                        <button class="v-nav-link active" type="button" onclick="switchTab('infos', this)">
                            <i class="bi bi-person-vcard"></i> Informations
                        </button>
                        <button class="v-nav-link" type="button" onclick="switchTab('instances', this)">
                            <i class="bi bi-diagram-3"></i> Instances
                        </button>
                        <button class="v-nav-link" type="button" onclick="switchTab('perms', this)">
                            <i class="bi bi-key"></i> Permissions
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Colonne droite ── -->
            <div class="col-lg-9">

                <!-- ONGLET INFOS -->
                <div class="tab-ki" id="tab-infos">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden stat-card">
                        <div class="card-header bg-light border-0 px-4 py-3 d-flex align-items-center gap-2">
                            <div class="ki-section-icon" style="background:#eef2ff;color:#4f46e5;"><i class="bi bi-person-vcard"></i></div>
                            <span class="fw-bold text-dark">Identité &amp; Connexion</span>
                        </div>
                        <div class="card-body bg-white p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="ki-form-label">Prénom</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                        <input type="text" name="prenom" class="form-control bg-light border-start-0"
                                               value="<?= htmlspecialchars($u['prenom'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="ki-form-label">Nom</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                        <input type="text" name="nom" class="form-control bg-light border-start-0"
                                               value="<?= htmlspecialchars($u['nom'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="ki-form-label">Identifiant <span class="text-muted fw-normal">(lecture seule)</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-at text-muted"></i></span>
                                        <input type="text" class="form-control bg-light border-start-0" disabled
                                               value="<?= htmlspecialchars($u['username']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="ki-form-label">Adresse e-mail</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                        <input type="email" name="email" class="form-control bg-light border-start-0" required
                                               value="<?= htmlspecialchars($u['email'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="border-top pt-4">
                                        <label class="ki-form-label">Réinitialiser le mot de passe</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                            <input type="password" name="password" class="form-control bg-light border-start-0"
                                                   placeholder="Laisser vide pour conserver le mot de passe actuel">
                                        </div>
                                        <div class="text-muted mt-1" style="font-size:.75rem;">Si renseigné, le nouveau mot de passe prend effet immédiatement.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ONGLET INSTANCES -->
                <div class="tab-ki d-none" id="tab-instances">

                    <?php if (!empty($orphanMembres)): ?>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden stat-card mb-4">
                        <div class="card-header bg-light border-0 px-4 py-3 d-flex align-items-center gap-2">
                            <div class="ki-section-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-magic"></i></div>
                            <span class="fw-bold text-dark">Associer des profils membres existants</span>
                        </div>
                        <div class="card-body bg-white p-4">
                            <div class="alert alert-secondary border-0 small rounded-3 mb-3">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                Ces membres partagent l'adresse <strong><?= htmlspecialchars($u['email']) ?></strong>.
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($orphanMembres as $om): ?>
                                <div class="form-check bg-light rounded border p-2 mb-0 ps-5">
                                    <input class="form-check-input" type="checkbox" name="link_membres[]"
                                           value="<?= $om['id'] ?>" id="link_<?= $om['id'] ?>" style="margin-left:-1.8rem;">
                                    <label class="form-check-label small w-100" for="link_<?= $om['id'] ?>">
                                        <strong class="text-dark"><?= htmlspecialchars($om['instance_nom']) ?></strong>
                                        <span class="text-muted ms-2">— <?= htmlspecialchars($om['prenom'].' '.$om['nom']) ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-muted mt-3" style="font-size:.75rem;">
                                <i class="bi bi-info-circle me-1"></i>Cochez puis enregistrez pour valider l'association.
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden stat-card">
                        <div class="card-header bg-light border-0 px-4 py-3 d-flex align-items-center gap-2">
                            <div class="ki-section-icon" style="background:#ecfdf5;color:#059669;"><i class="bi bi-diagram-3"></i></div>
                            <span class="fw-bold text-dark">Rôle dans les instances</span>
                        </div>
                        <div class="card-body bg-white p-4">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="text-uppercase fw-bold small text-muted mb-2" style="font-size:.7rem;letter-spacing:.5px;">
                                        <i class="bi bi-star-fill text-warning me-1"></i>Gestionnaire
                                    </div>
                                    <?php if (empty($u['instances_manager'])): ?>
                                        <div class="text-muted small bg-light rounded p-3 text-center">Aucune instance gérée.</div>
                                    <?php else: ?>
                                        <div class="d-flex flex-column gap-2">
                                            <?php foreach ($u['instances_manager'] as $inst): ?>
                                            <div class="d-flex align-items-center gap-2 bg-light rounded p-2">
                                                <i class="bi bi-building text-muted"></i>
                                                <span class="fw-bold small text-dark"><?= htmlspecialchars($inst['nom']) ?></span>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-uppercase fw-bold small text-muted mb-2" style="font-size:.7rem;letter-spacing:.5px;">
                                        <i class="bi bi-people-fill text-primary me-1"></i>Membre convoqué
                                    </div>
                                    <?php if (empty($u['instances_membre'])): ?>
                                        <div class="text-muted small bg-light rounded p-3 text-center">Aucune instance.</div>
                                    <?php else: ?>
                                        <div class="d-flex flex-column gap-2">
                                            <?php foreach ($u['instances_membre'] as $inst): ?>
                                            <div class="d-flex align-items-center gap-2 bg-light rounded p-2">
                                                <i class="bi bi-building text-muted"></i>
                                                <span class="fw-bold small text-dark"><?= htmlspecialchars($inst['nom']) ?></span>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="alert alert-secondary border-0 small rounded-3 mt-4 mb-0">
                                <i class="bi bi-info-circle-fill me-2"></i>Pour ajouter/retirer cet utilisateur d'une instance, rendez-vous dans l'onglet <strong>Instances</strong> de la navigation principale.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ONGLET PERMISSIONS -->
                <div class="tab-ki d-none" id="tab-perms">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden stat-card">
                        <div class="card-header bg-light border-0 px-4 py-3 d-flex align-items-center gap-2">
                            <div class="ki-section-icon" style="background:#fdf4ff;color:#9333ea;"><i class="bi bi-shield-lock"></i></div>
                            <span class="fw-bold text-dark">Permissions d'administration</span>
                        </div>
                        <div class="card-body bg-white p-4">
                            <?php
                            $groupedCatalog = [];
                            foreach ($catalog as $slug => $meta) $groupedCatalog[$meta['cat'] ?? 'Autres'][$slug] = $meta;
                            foreach ($groupedCatalog as $cat => $items): ?>
                                <div class="text-uppercase fw-bold small text-muted mb-3 mt-4" style="letter-spacing:.6px;font-size:.7rem;">
                                    <?= htmlspecialchars($cat) ?>
                                </div>
                                <div class="row g-2 mb-2">
                                    <?php foreach ($items as $slug => $meta):
                                        $isChecked = in_array($slug, $u['permissions'] ?? [], true);
                                    ?>
                                    <div class="col-md-6 d-flex align-items-start">
                                        <label class="perm-card d-block w-100 <?= $isChecked ? 'checked' : '' ?>"
                                               for="perm_<?= htmlspecialchars($slug) ?>">
                                            <div class="d-flex align-items-start gap-2">
                                                <input class="form-check-input perm-check flex-shrink-0" style="margin-top:3px;" type="checkbox"
                                                       name="permissions[]"
                                                       id="perm_<?= htmlspecialchars($slug) ?>"
                                                       value="<?= htmlspecialchars($slug) ?>"
                                                       <?= $isChecked ? 'checked' : '' ?>>
                                                <div>
                                                    <div class="fw-bold text-dark" style="font-size:.85rem;"><?= htmlspecialchars($meta['nom'] ?? $slug) ?></div>
                                                    <?php if (!empty($meta['desc'])): ?>
                                                        <div class="text-muted lh-sm mt-1" style="font-size:.73rem;"><?= htmlspecialchars($meta['desc']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="d-flex justify-content-end gap-2 mt-4 mb-5">
                    <a href="<?= URLROOT ?>/admin/users" class="btn btn-light fw-bold px-4 rounded-pill">Annuler</a>
                    <button type="submit" class="btn btn-primary fw-bold px-5 rounded-pill shadow-sm">
                        <i class="bi bi-floppy me-2"></i>Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-ki').forEach(t => t.classList.add('d-none'));
    document.querySelectorAll('.v-nav-link').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.remove('d-none');
    btn.classList.add('active');
}
document.querySelectorAll('.perm-check').forEach(cb => {
    cb.addEventListener('change', function() {
        this.closest('.perm-card').classList.toggle('checked', this.checked);
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
