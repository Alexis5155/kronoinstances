<?php include __DIR__ . '/../layouts/header.php'; ?>
<?php
// Utilitaires d'affichage
$displayName = trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''));
if (empty($displayName)) $displayName = $u['username'];

$initials = "U";
if (!empty($u['prenom']) && !empty($u['nom'])) {
    $initials = mb_strtoupper(mb_substr($u['prenom'], 0, 1) . mb_substr($u['nom'], 0, 1));
} elseif (!empty($u['username'])) {
    $initials = mb_strtoupper(mb_substr($u['username'], 0, 2));
}
?>

<div class="container py-4">
    <!-- En-tête -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-2">
                <li class="breadcrumb-item"><a href="<?= URLROOT ?>/admin" class="text-decoration-none">Administration</a></li>
                <li class="breadcrumb-item"><a href="<?= URLROOT ?>/admin/users" class="text-decoration-none">Utilisateurs</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dossier : <?= htmlspecialchars($displayName) ?></li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Modifier le profil</h2>
    </div>

    <form method="POST" action="<?= URLROOT ?>/admin/userEdit/<?= $u['id'] ?>">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">

        <div class="row g-4">
            <!-- Colonne Gauche : Navigation Profile -->
            <div class="col-lg-3">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center shadow-sm mb-3" 
                             style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($displayName) ?></h5>
                        <p class="text-muted small mb-3">@<?= htmlspecialchars($u['username']) ?></p>
                        
                        <?php if (in_array('manage_system', $u['permissions'])): ?>
                            <span class="badge bg-danger mb-3 px-3 py-2"><i class="bi bi-shield-lock-fill me-1"></i> Administrateur</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Navigation (Pills verticaux) -->
                    <div class="card-footer bg-white border-top p-0">
                        <div class="nav flex-column nav-pills custom-v-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <button class="nav-link active text-start px-4 py-3 border-0 rounded-0" id="v-pills-infos-tab" data-bs-toggle="pill" data-bs-target="#v-pills-infos" type="button" role="tab">
                                <i class="bi bi-person-vcard me-2"></i> Informations
                            </button>
                            <button class="nav-link text-start px-4 py-3 border-0 rounded-0" id="v-pills-instances-tab" data-bs-toggle="pill" data-bs-target="#v-pills-instances" type="button" role="tab">
                                <i class="bi bi-diagram-3 me-2"></i> Instances
                            </button>
                            <button class="nav-link text-start px-4 py-3 border-0 rounded-0" id="v-pills-perms-tab" data-bs-toggle="pill" data-bs-target="#v-pills-perms" type="button" role="tab">
                                <i class="bi bi-key me-2"></i> Permissions
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne Droite : Contenu -->
            <div class="col-lg-9">
                <div class="tab-content" id="v-pills-tabContent">
                    
                    <!-- ONGLET : INFOS -->
                    <div class="tab-pane fade show active" id="v-pills-infos" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="fw-bold mb-0 text-dark">Informations d'identité et de connexion</h6>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Prénom</label>
                                        <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($u['prenom'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Nom</label>
                                        <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($u['nom'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Identifiant (Lecture seule)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-box-arrow-in-right"></i></span>
                                            <input type="text" class="form-control bg-light text-muted" value="<?= htmlspecialchars($u['username']) ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Adresse mail</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12 mt-5">
                                        <h6 class="fw-bold mb-3 border-bottom pb-2">Sécurité du compte</h6>
                                        <label class="form-label fw-bold small text-muted">Réinitialiser le mot de passe</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                            <input type="password" name="password" class="form-control" placeholder="Laissez vide pour conserver le mot de passe actuel">
                                        </div>
                                        <div class="form-text">Si vous le modifiez, le nouveau mot de passe prendra effet immédiatement.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ONGLET : INSTANCES -->
                    <div class="tab-pane fade" id="v-pills-instances" role="tabpanel">
                        
                        <!-- LIAISON INTELLIGENTE -->
                        <?php if (!empty($orphanMembres)): ?>
                            <div class="alert border-primary bg-primary bg-opacity-10 shadow-sm mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                        <i class="bi bi-magic"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-primary mb-0">Associer des profils "membres" existants</h6>
                                        <div class="small text-dark opacity-75">Ces membres partagent l'adresse <strong><?= htmlspecialchars($u['email']) ?></strong>.</div>
                                    </div>
                                </div>
                                <div class="bg-white rounded p-3 border border-primary-subtle">
                                    <?php foreach($orphanMembres as $om): ?>
                                        <div class="form-check p-2 rounded hover-light mb-1 ps-5">
                                            <input class="form-check-input" type="checkbox" name="link_membres[]" value="<?= $om['id'] ?>" id="link_<?= $om['id'] ?>" style="margin-left:-2rem;">
                                            <label class="form-check-label w-100" for="link_<?= $om['id'] ?>">
                                                <strong class="text-dark"><?= htmlspecialchars($om['instance_nom']) ?></strong> 
                                                <span class="text-muted small mx-2">—</span> 
                                                <span class="text-muted"><?= htmlspecialchars($om['prenom'].' '.$om['nom']) ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="small text-muted mt-2 ms-1"><i class="bi bi-info-circle me-1"></i> Cochez puis enregistrez la page pour valider l'association.</div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="fw-bold mb-0 text-dark">Rôle dans les instances</h6>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold small text-uppercase text-warning mb-3"><i class="bi bi-star-fill me-2"></i>Gestionnaire (Modification)</h6>
                                        <?php if(empty($u['instances_manager'])): ?>
                                            <div class="p-3 bg-light rounded text-muted small text-center">L'agent ne gère aucune instance.</div>
                                        <?php else: ?>
                                            <ul class="list-group list-group-flush border rounded">
                                                <?php foreach($u['instances_manager'] as $inst): ?>
                                                    <li class="list-group-item bg-light border-0 border-bottom last-border-0">
                                                        <i class="bi bi-building me-2 text-muted"></i> <span class="fw-bold"><?= htmlspecialchars($inst['nom']) ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6">
                                        <h6 class="fw-bold small text-uppercase text-primary mb-3"><i class="bi bi-people-fill me-2"></i>Membre convoqué (Lecture)</h6>
                                        <?php if(empty($u['instances_membre'])): ?>
                                            <div class="p-3 bg-light rounded text-muted small text-center">L'agent n'est membre d'aucune instance.</div>
                                        <?php else: ?>
                                            <ul class="list-group list-group-flush border rounded">
                                                <?php foreach($u['instances_membre'] as $inst): ?>
                                                    <li class="list-group-item bg-light border-0 border-bottom last-border-0">
                                                        <i class="bi bi-building me-2 text-muted"></i> <span class="fw-bold"><?= htmlspecialchars($inst['nom']) ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="alert alert-light border small text-muted mt-4 mb-0">
                                    <i class="bi bi-info-circle me-1"></i> Pour ajouter/retirer cet utilisateur d'une instance, veuillez vous rendre dans l'onglet <strong>Instances</strong> de la navigation principale.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ONGLET : PERMISSIONS -->
                    <div class="tab-pane fade" id="v-pills-perms" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="fw-bold mb-0 text-dark">Permissions d'administration</h6>
                            </div>
                            <div class="card-body p-4">
                                <?php 
                                $groupedCatalog = [];
                                foreach ($catalog as $slug => $meta) $groupedCatalog[$meta['cat'] ?? 'Autres'][$slug] = $meta;
                                foreach ($groupedCatalog as $cat => $items): ?>
                                    <div class="fw-bold text-uppercase small text-muted mb-2 mt-4 first-mt-0" style="letter-spacing: 0.5px;"><?= htmlspecialchars($cat) ?></div>
                                    <div class="row g-3">
                                        <?php foreach ($items as $slug => $meta): ?>
                                            <div class="col-md-6">
                                                <div class="form-check form-switch bg-light border border-light-subtle rounded p-3 ps-5 h-100 <?= in_array($slug, $u['permissions'], true) ? 'border-primary-subtle bg-primary bg-opacity-10' : '' ?>">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" 
                                                           id="perm_<?= htmlspecialchars($slug) ?>" value="<?= htmlspecialchars($slug) ?>"
                                                           <?= in_array($slug, $u['permissions'], true) ? 'checked' : '' ?> style="margin-left:-2.5rem; margin-top:0.3rem;">
                                                    <label class="form-check-label d-block w-100" for="perm_<?= htmlspecialchars($slug) ?>">
                                                        <div class="fw-bold text-dark mb-1" style="font-size:0.9rem;"><?= htmlspecialchars($meta['nom'] ?? $slug) ?></div>
                                                        <?php if (!empty($meta['desc'])): ?>
                                                            <div class="text-muted lh-sm" style="font-size:0.75rem;"><?= htmlspecialchars($meta['desc']) ?></div>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Boutons globaux en bas -->
                <div class="d-flex justify-content-end gap-2 mb-5">
                    <a href="<?= URLROOT ?>/admin/users" class="btn btn-light fw-bold px-4">Annuler</a>
                    <button type="submit" class="btn btn-primary fw-bold px-5 shadow-sm">Enregistrer les modifications</button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- CSS local pour peaufiner les "Vertical Pills" -->
<style>
.custom-v-pills .nav-link {
    color: #6c757d;
    transition: all 0.2s;
    border-left: 3px solid transparent !important;
}
.custom-v-pills .nav-link:hover {
    background-color: #f8f9fa;
    color: #212529;
}
.custom-v-pills .nav-link.active {
    background-color: #f8f9fa;
    color: #0d6efd !important;
    border-left: 3px solid #0d6efd !important;
    font-weight: 700;
}
.hover-light:hover { background-color: rgba(255,255,255,0.5) !important; }
.first-mt-0:first-child { margin-top: 0 !important; }
.last-border-0:last-child { border-bottom: 0 !important; }
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
