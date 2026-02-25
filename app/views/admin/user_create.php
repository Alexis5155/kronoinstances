<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-4">
    <!-- En-tête de page -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-2">
                <li class="breadcrumb-item"><a href="<?= URLROOT ?>/admin" class="text-decoration-none">Administration</a></li>
                <li class="breadcrumb-item"><a href="<?= URLROOT ?>/admin/users" class="text-decoration-none">Utilisateurs</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nouvel agent</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-1">Créer un nouveau profil</h2>
        <p class="text-muted">Créez un accès pour un nouvel agent, définissez ses identifiants et attribuez-lui ses permissions système initiales.</p>
    </div>

    <form method="POST" action="<?= URLROOT ?>/admin/userAdd">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
        <input type="hidden" name="add_user" value="1">

        <div class="row g-4">
            <!-- Colonne de gauche : Aperçu et Explications -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 100px; z-index: 1;">
                    <div class="card-body text-center p-4">
                        <!-- Aperçu Avatar dynamique -->
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center shadow-sm mb-3" 
                             style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;" id="preview_avatar">
                            ?
                        </div>
                        <h5 class="fw-bold mb-1" id="preview_name">Nouvel Utilisateur</h5>
                        <p class="text-muted small mb-0" id="preview_username">@identifiant</p>
                        <hr class="my-4 opacity-10">
                        <div class="text-start small text-muted">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><i class="bi bi-info-circle text-primary me-2"></i> Le nom d'utilisateur est généré automatiquement mais reste modifiable.</li>
                                <li class="mb-2"><i class="bi bi-envelope-at text-primary me-2"></i> L'adresse mail permettra de relier automatiquement cet agent aux instances existantes.</li>
                                <li><i class="bi bi-key text-primary me-2"></i> Le mot de passe devra être transmis manuellement à l'agent.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne de droite : Formulaire -->
            <div class="col-lg-8">
                <!-- Bloc 1 : Identité & Connexion -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-person-vcard me-2"></i>Identité & Connexion</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Prénom <span class="text-danger">*</span></label>
                                <input type="text" name="prenom" id="add_prenom" class="form-control" required placeholder="Ex: Jean">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Nom <span class="text-danger">*</span></label>
                                <input type="text" name="nom" id="add_nom" class="form-control" required placeholder="Ex: Dupont">
                            </div>
                            
                            <div class="col-12 mt-4">
                                <label class="form-label fw-bold small text-muted">Adresse mail <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" id="add_email" class="form-control" required placeholder="jean.dupont@collectivite.fr">
                                </div>
                                <div class="form-text">Utile pour la récupération de mot de passe et l'envoi des convocations.</div>
                            </div>

                            <!-- Zone AJAX qui apparaîtra si des membres orphelins sont trouvés -->
                            <div class="col-12" id="email_suggestions_container"></div>

                            <div class="col-md-6 mt-4">
                                <label class="form-label fw-bold small text-muted">Identifiant de connexion <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-box-arrow-in-right"></i></span>
                                    <input type="text" name="username" id="add_username" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6 mt-4">
                                <label class="form-label fw-bold small text-muted">Mot de passe provisoire <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bloc 2 : Permissions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-shield-lock me-2"></i>Permissions système</h6>
                            <span class="badge bg-light text-dark border">Optionnel</span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <p class="small text-muted mb-4">Attribuez les droits d'administration logicielle ou de gestion globale. <strong>Un agent membre d'une instance n'a besoin d'aucune de ces permissions pour être convoqué.</strong></p>
                        
                        <?php 
                        $groupedCatalog = [];
                        foreach ($catalog as $slug => $meta) {
                            $groupedCatalog[$meta['cat'] ?? 'Autres'][$slug] = $meta;
                        }
                        foreach ($groupedCatalog as $cat => $items): ?>
                            <div class="fw-bold text-uppercase small text-muted mb-2 mt-3" style="letter-spacing: 0.5px;"><?= htmlspecialchars($cat) ?></div>
                            <div class="row g-3 mb-3">
                                <?php foreach ($items as $slug => $meta): ?>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch bg-light border border-light-subtle rounded p-3 ps-5 h-100">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" 
                                                   id="add_perm_<?= htmlspecialchars($slug) ?>" 
                                                   value="<?= htmlspecialchars($slug) ?>" style="margin-left:-2.5rem; margin-top:0.3rem;">
                                            <label class="form-check-label d-block w-100" for="add_perm_<?= htmlspecialchars($slug) ?>">
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

                <!-- Boutons d'action -->
                <div class="d-flex justify-content-end gap-2 mb-5">
                    <a href="<?= URLROOT ?>/admin/users" class="btn btn-light fw-bold px-4">Annuler</a>
                    <button type="submit" class="btn btn-primary fw-bold px-5 shadow-sm">Créer le profil</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Remplissage du nom d'utilisateur et aperçu
    const prenomInput = document.getElementById('add_prenom');
    const nomInput = document.getElementById('add_nom');
    const userInput = document.getElementById('add_username');
    const previewName = document.getElementById('preview_name');
    const previewUsername = document.getElementById('preview_username');
    const previewAvatar = document.getElementById('preview_avatar');

    function updatePreview() {
        const p = prenomInput.value.trim();
        const n = nomInput.value.trim();
        
        if (p || n) previewName.textContent = `${p} ${n}`;
        else previewName.textContent = "Nouvel Utilisateur";

        let init = "?";
        if (p && n) init = p.charAt(0).toUpperCase() + n.charAt(0).toUpperCase();
        else if (p) init = p.charAt(0).toUpperCase();
        else if (n) init = n.charAt(0).toUpperCase();
        previewAvatar.textContent = init;

        const pNorm = p.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/\s+/g, '-');
        const nNorm = n.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/\s+/g, '-');
        
        if (pNorm || nNorm) {
            const generatedUser = (pNorm && nNorm) ? `${pNorm}.${nNorm}` : pNorm + nNorm;
            userInput.value = generatedUser;
            previewUsername.textContent = '@' + generatedUser;
        } else {
            userInput.value = "";
            previewUsername.textContent = '@identifiant';
        }
    }

    prenomInput.addEventListener('input', updatePreview);
    nomInput.addEventListener('input', updatePreview);
    userInput.addEventListener('input', function() {
        previewUsername.textContent = this.value ? '@' + this.value : '@identifiant';
    });

    // 2. Détection Email EN DIRECT (AJAX avec debounce)
    const emailInput = document.getElementById('add_email');
    const suggestContainer = document.getElementById('email_suggestions_container');
    let emailTimeout = null; // Timer pour le debounce

    emailInput.addEventListener('input', function() {
        clearTimeout(emailTimeout); // Annule la recherche précédente si l'utilisateur tape encore
        
        const email = this.value.trim();
        
        // Vérification regex simple pour voir si ça ressemble à un email avant de chercher
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email || !emailRegex.test(email)) {
            suggestContainer.innerHTML = ''; 
            return;
        }

        // Attend 400ms après la dernière frappe avant de lancer la requête AJAX
        emailTimeout = setTimeout(() => {
            fetch('<?= URLROOT ?>/admin/checkEmailMembers?email=' + encodeURIComponent(email))
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        let html = `
                        <div class="alert alert-info border border-info shadow-sm mt-3 mb-0">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-link-45deg fs-4 text-info me-2"></i>
                                <h6 class="fw-bold mb-0">Comptes "membres" détectés en direct !</h6>
                            </div>
                            <p class="small mb-3">L'adresse <strong>${email}</strong> est déjà associée à des membres dans vos instances. Cochez ceux que vous souhaitez relier à ce nouvel agent :</p>`;
                        data.forEach(m => {
                            html += `
                            <div class="form-check bg-white p-2 rounded border border-info-subtle mb-2 ps-5">
                                <input class="form-check-input" type="checkbox" name="link_membres[]" value="${m.id}" id="link_${m.id}" checked style="margin-left:-2rem;">
                                <label class="form-check-label small w-100" for="link_${m.id}">
                                    <strong class="text-dark">${m.instance_nom}</strong> <br>
                                    <span class="text-muted">${m.prenom} ${m.nom}</span>
                                </label>
                            </div>`;
                        });
                        html += `</div>`;
                        suggestContainer.innerHTML = html;
                    } else {
                        suggestContainer.innerHTML = '';
                    }
                })
                .catch(() => suggestContainer.innerHTML = '');
        }, 400); // 400 millisecondes de délai
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
