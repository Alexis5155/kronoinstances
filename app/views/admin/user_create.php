<?php include __DIR__ . '/../layouts/header.php'; ?>

<style>
.stat-card { z-index:1; transition: transform .25s ease, box-shadow .25s ease; border: 1px solid rgba(0,0,0,0.05) !important; }
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,.08) !important; }
.ki-section-icon { width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:.9rem; flex-shrink:0; }
.ki-form-label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#6b7280; margin-bottom:.4rem; display:block; }
.perm-card { border:1px solid rgba(0,0,0,.08); border-radius:11px; padding:.85rem 1rem; cursor:pointer; transition:all .2s; }
.perm-card:hover  { border-color:#0d6efd; background:#f0f4ff; }
.perm-card.checked{ border-color:#0d6efd; background:#e8f0fe; }
</style>

<div class="container py-4" style="max-width:960px;">

    <!-- EN-TÊTE -->
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div class="d-flex align-items-center">
            <div class="avatar-circle rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm me-3"
                 style="width:50px;height:50px;font-size:1.5rem;">
                <i class="bi bi-person-plus"></i>
            </div>
            <div>
                <nav aria-label="breadcrumb" class="mb-1">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="<?= URLROOT ?>/admin" class="text-decoration-none text-primary fw-bold">Administration</a></li>
                        <li class="breadcrumb-item"><a href="<?= URLROOT ?>/admin/users" class="text-decoration-none text-primary fw-bold">Utilisateurs</a></li>
                        <li class="breadcrumb-item active">Nouvel agent</li>
                    </ol>
                </nav>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing:-0.5px;">Créer un nouveau profil</h2>
                <p class="text-muted small mb-0">Créez un accès pour un nouvel agent et définissez ses permissions initiales.</p>
            </div>
        </div>
        <a href="<?= URLROOT ?>/admin/users" class="btn btn-light fw-bold shadow-sm px-3 rounded-pill border d-none d-sm-inline-block">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
    </div>

    <form method="POST" action="<?= URLROOT ?>/admin/userAdd">
        <input type="hidden" name="csrf"     value="<?= htmlspecialchars($csrf ?? '') ?>">
        <input type="hidden" name="add_user" value="1">

        <div class="row g-4">

            <!-- ── Colonne gauche : aperçu sticky ── -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center stat-card sticky-top" style="top:90px;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 fw-bold mx-auto"
                         id="preview_avatar"
                         style="width:72px;height:72px;font-size:1.6rem;background:#eef2ff;color:#4f46e5;">?</div>
                    <div class="fw-bold text-dark mb-1" id="preview_name">Nouvel Utilisateur</div>
                    <div class="text-muted small mb-3" id="preview_username">@identifiant</div>
                    <hr class="opacity-25">
                    <ul class="list-unstyled text-start small text-muted mb-0 mt-3">
                        <li class="mb-2"><i class="bi bi-info-circle text-primary me-2"></i>L'identifiant est généré automatiquement mais reste modifiable.</li>
                        <li class="mb-2"><i class="bi bi-envelope-at text-primary me-2"></i>L'e-mail relie l'agent aux instances existantes.</li>
                        <li><i class="bi bi-key text-primary me-2"></i>Le mot de passe provisoire devra être transmis manuellement.</li>
                    </ul>
                </div>
            </div>

            <!-- ── Colonne droite ── -->
            <div class="col-lg-8 d-flex flex-column gap-4">

                <!-- Bloc 1 : Identité & Connexion -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden stat-card">
                    <div class="card-header bg-light border-0 px-4 py-3 d-flex align-items-center gap-2">
                        <div class="ki-section-icon" style="background:#eef2ff;color:#4f46e5;"><i class="bi bi-person-vcard"></i></div>
                        <span class="fw-bold text-dark">Identité &amp; Connexion</span>
                    </div>
                    <div class="card-body bg-white p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="ki-form-label">Prénom <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                    <input type="text" name="prenom" id="add_prenom" class="form-control bg-light border-start-0" required placeholder="Jean">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="ki-form-label">Nom <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                    <input type="text" name="nom" id="add_nom" class="form-control bg-light border-start-0" required placeholder="Dupont">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="ki-form-label">Adresse e-mail <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                    <input type="email" name="email" id="add_email" class="form-control bg-light border-start-0" required placeholder="jean.dupont@collectivite.fr">
                                </div>
                                <div class="text-muted mt-1" style="font-size:.75rem;">Utile pour la récupération de mot de passe et l'envoi des convocations.</div>
                                <div id="email_suggestions_container" class="mt-2"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="ki-form-label">Identifiant de connexion <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-at text-muted"></i></span>
                                    <input type="text" name="username" id="add_username" class="form-control bg-light border-start-0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="ki-form-label">Mot de passe <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                    <input type="password" name="password" class="form-control bg-light border-start-0" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bloc 2 : Options du compte -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden stat-card">
                    <div class="card-header bg-light border-0 px-4 py-3 d-flex align-items-center gap-2">
                        <div class="ki-section-icon" style="background:#ecfdf5;color:#059669;"><i class="bi bi-toggles"></i></div>
                        <span class="fw-bold text-dark">Options du compte</span>
                    </div>
                    <div class="card-body bg-white p-4 d-flex flex-column gap-3">

                        <!-- Vérification e-mail -->
                        <div class="d-flex align-items-center justify-content-between p-3 border rounded-3">
                            <div>
                                <div class="fw-bold text-dark" style="font-size:.9rem;">
                                    <i class="bi bi-envelope-check me-2 text-primary"></i>Demander la vérification de l'e-mail
                                </div>
                                <div class="text-muted mt-1" style="font-size:.75rem;">
                                    Si activé, un e-mail de confirmation est envoyé et le compte reste en attente jusqu'à validation.
                                </div>
                            </div>
                            <div class="form-check form-switch ms-3 mb-0" style="flex-shrink:0;">
                                <input class="form-check-input" type="checkbox" name="require_email_verify" id="tog_email_verify"
                                       role="switch" style="width:2.5rem;height:1.3rem;cursor:pointer;">
                            </div>
                        </div>

                        <!-- Statut -->
                        <input type="hidden" name="status" id="hidden_status" value="active">
                        <div class="d-flex align-items-center justify-content-between p-3 border rounded-3 bg-light">
                            <div>
                                <div class="fw-bold text-dark" style="font-size:.9rem;">
                                    <i class="bi bi-check-circle me-2 text-success"></i>Compte actif immédiatement
                                </div>
                                <div class="text-muted mt-1" style="font-size:.75rem;">
                                    Créé par un administrateur, le compte est automatiquement approuvé.
                                </div>
                            </div>
                            <span class="badge rounded-pill px-3 py-2" style="background:#d1fae5;color:#065f46;font-size:.75rem;">
                                <i class="bi bi-shield-check me-1"></i>Approuvé
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Bloc 3 : Permissions -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden stat-card">
                    <div class="card-header bg-light border-0 px-4 py-3 d-flex align-items-center gap-2">
                        <div class="ki-section-icon" style="background:#fdf4ff;color:#9333ea;"><i class="bi bi-shield-lock"></i></div>
                        <span class="fw-bold text-dark">Permissions système</span>
                        <span class="badge bg-light text-muted border ms-auto" style="font-size:.7rem;">Optionnel</span>
                    </div>
                    <div class="card-body bg-white p-4">
                        <div class="alert alert-secondary border-0 small rounded-3 mb-4">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            Un agent membre d'une instance n'a besoin d'aucune permission pour être convoqué.
                        </div>
                        <?php
                        $groupedCatalog = [];
                        foreach ($catalog as $slug => $meta) {
                            $groupedCatalog[$meta['cat'] ?? 'Autres'][$slug] = $meta;
                        }
                        foreach ($groupedCatalog as $cat => $items): ?>
                            <div class="text-uppercase fw-bold small text-muted mb-3 mt-4" style="letter-spacing:.6px;font-size:.7rem;">
                                <?= htmlspecialchars($cat) ?>
                            </div>
                            <div class="row g-2 mb-2">
                                <?php foreach ($items as $slug => $meta): ?>
                                <div class="col-md-6 d-flex align-items-start">
                                    <label class="perm-card d-block w-100" for="add_perm_<?= htmlspecialchars($slug) ?>">
                                        <div class="d-flex align-items-start gap-2">
                                            <input class="form-check-input perm-check flex-shrink-0" style="margin-top:3px;" type="checkbox"
                                                   name="permissions[]"
                                                   id="add_perm_<?= htmlspecialchars($slug) ?>"
                                                   value="<?= htmlspecialchars($slug) ?>">
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

                <!-- Actions -->
                <div class="d-flex justify-content-end gap-2 mb-5">
                    <a href="<?= URLROOT ?>/admin/users" class="btn btn-light fw-bold px-4 rounded-pill">Annuler</a>
                    <button type="submit" class="btn btn-primary fw-bold px-5 rounded-pill shadow-sm">
                        <i class="bi bi-person-plus me-2"></i>Créer le profil
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const prenomInput = document.getElementById('add_prenom');
    const nomInput    = document.getElementById('add_nom');
    const userInput   = document.getElementById('add_username');
    const previewName = document.getElementById('preview_name');
    const previewUser = document.getElementById('preview_username');
    const previewAv   = document.getElementById('preview_avatar');

    function slugify(s) {
        return s.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]/g,'');
    }
    let userEdited = false;
    userInput.addEventListener('input', () => userEdited = true);

    function updatePreview() {
        const p = prenomInput.value.trim(), n = nomInput.value.trim();
        previewName.textContent = (p || n) ? `${p} ${n}`.trim() : 'Nouvel Utilisateur';
        let init = '?';
        if (p && n) init = p[0].toUpperCase() + n[0].toUpperCase();
        else if (p) init = p[0].toUpperCase();
        else if (n) init = n[0].toUpperCase();
        previewAv.textContent = init;
        if (!userEdited) {
            const sp = slugify(p), sn = slugify(n);
            const gen = (sp && sn) ? `${sp}.${sn}` : sp + sn;
            userInput.value = gen;
            previewUser.textContent = gen ? '@' + gen : '@identifiant';
        }
    }
    prenomInput.addEventListener('input', updatePreview);
    nomInput.addEventListener('input',   updatePreview);
    userInput.addEventListener('input',  () => {
        previewUser.textContent = userInput.value ? '@' + userInput.value : '@identifiant';
    });

    // Perm cards
    document.querySelectorAll('.perm-check').forEach(cb => {
        cb.addEventListener('change', function() {
            this.closest('.perm-card').classList.toggle('checked', this.checked);
        });
    });

    // Toggle vérification e-mail → met à jour hidden_status
    document.getElementById('tog_email_verify').addEventListener('change', function() {
        document.getElementById('hidden_status').value = this.checked ? 'pending_email' : 'active';
    });

    // AJAX email membres
    const emailInput       = document.getElementById('add_email');
    const suggestContainer = document.getElementById('email_suggestions_container');
    let emailTimeout = null;

    emailInput.addEventListener('input', function() {
        clearTimeout(emailTimeout);
        const email = this.value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { suggestContainer.innerHTML = ''; return; }
        emailTimeout = setTimeout(() => {
            fetch('<?= URLROOT ?>/admin/checkEmailMembers?email=' + encodeURIComponent(email))
                .then(r => r.json())
                .then(data => {
                    if (!data.length) { suggestContainer.innerHTML = ''; return; }
                    let html = `<div class="card border-0 shadow-sm rounded-3 p-3 mt-2" style="border-color:#bfdbfe !important;">
                        <div class="fw-bold small text-primary mb-2"><i class="bi bi-link-45deg me-1"></i>Membres existants détectés</div>
                        <p class="text-muted small mb-3">L'adresse <strong>${email}</strong> est associée à des membres. Cochez ceux à relier :</p>`;
                    data.forEach(m => {
                        html += `<div class="form-check bg-white rounded border p-2 mb-2 ps-5">
                            <input class="form-check-input" type="checkbox" name="link_membres[]" value="${m.id}" id="lk${m.id}" checked style="margin-left:-1.8rem;">
                            <label class="form-check-label small w-100" for="lk${m.id}">
                                <strong>${m.instance_nom}</strong><br>
                                <span class="text-muted">${m.prenom} ${m.nom}</span>
                            </label>
                        </div>`;
                    });
                    html += '</div>';
                    suggestContainer.innerHTML = html;
                })
                .catch(() => suggestContainer.innerHTML = '');
        }, 400);
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
