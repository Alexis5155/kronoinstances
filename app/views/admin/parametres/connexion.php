<?php use app\models\User; ?>

<?php if (!User::can('manage_system')): ?>
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-5 text-center">
        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;">
            <i class="bi bi-shield-lock" style="font-size:2rem;"></i>
        </div>
        <h5 class="fw-bold text-dark">Accès refusé</h5>
        <p class="text-muted mb-0">Vous n'avez pas les droits nécessaires pour modifier ces paramètres.</p>
    </div>
</div>
<?php return; endif; ?>


<!-- ===== INSCRIPTION ===== -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white py-3 border-0 border-bottom">
        <h6 class="mb-0 fw-bold d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-2 d-inline-flex">
                <i class="bi bi-person-plus"></i>
            </div>
            Création de comptes
        </h6>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=connexion">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
            <input type="hidden" name="action" value="update_connexion">

            <!-- Autoriser l'inscription -->
            <div class="d-flex justify-content-between align-items-start p-3 rounded-3 border bg-light mb-3">
                <div class="me-4">
                    <div class="fw-bold text-dark mb-1">Autoriser l'inscription publique</div>
                    <div class="small text-muted">Si activé, un lien « Créer un compte » sera affiché sur la page de connexion.</div>
                </div>
                <div class="form-check form-switch mt-1 flex-shrink-0">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="allow_register" name="allow_register" value="1"
                           <?= !empty($settings['allow_register']) ? 'checked' : '' ?>
                           onchange="toggleApprovalOption()">
                </div>
            </div>

            <!-- Approbation requise -->
            <div class="d-flex justify-content-between align-items-start p-3 rounded-3 border bg-light mb-4" id="approval_row"
                 style="<?= empty($settings['allow_register']) ? 'opacity:0.4; pointer-events:none;' : '' ?>">
                <div class="me-4">
                    <div class="fw-bold text-dark mb-1">Soumettre à approbation administrateur</div>
                    <div class="small text-muted">
                        Si activé, les nouveaux comptes seront bloqués jusqu'à validation manuelle d'un administrateur.<br>
                        Un encart <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 fw-normal" style="font-size:0.75rem;">En attente de validation</span> sera affiché sur la page de l'utilisateur.
                    </div>
                </div>
                <div class="form-check form-switch mt-1 flex-shrink-0">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="require_approval" name="require_approval" value="1"
                           <?= !empty($settings['require_approval']) ? 'checked' : '' ?>>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary fw-bold px-5 shadow-sm rounded-pill">
                    <i class="bi bi-save me-2"></i>Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ===== KRONOCONNECT ===== -->
<?php
$kronoConnectEnabled = !empty($settings['kronoconnect_enabled']);
$kronoConnectUrl     = $settings['kronoconnect_url'] ?? '';
?>

<?php if ($kronoConnectEnabled): ?>

    <!-- État : KronoConnect ACTIVÉ -->
    <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #0d1117 0%, #161b22 50%, #0d1117 100%);">
        <div class="card-body p-0">

            <!-- Bandeau supérieur -->
            <div class="d-flex align-items-center justify-content-between px-4 pt-4 pb-3 border-bottom border-white border-opacity-10">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                        <i class="bi bi-link-45deg text-white fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-white" style="font-size:1rem;letter-spacing:-0.3px;">KronoConnect</div>
                        <div class="text-white opacity-50" style="font-size:0.75rem;">Identité unifiée · Compte unique</div>
                    </div>
                </div>
                <span class="badge px-3 py-2 fw-bold rounded-pill" style="background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3);font-size:0.7rem;">
                    <span class="d-inline-block rounded-circle me-1" style="width:7px;height:7px;background:#4ade80;"></span>
                    Connecté
                </span>
            </div>

            <!-- Corps -->
            <div class="px-4 py-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="rounded-3 p-3 text-center" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                            <i class="bi bi-person-check text-white opacity-50 d-block mb-1 fs-5"></i>
                            <div class="text-white fw-bold fs-5">—</div>
                            <div class="text-white opacity-40" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;">Comptes synchronisés</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="rounded-3 p-3 text-center" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                            <i class="bi bi-app-indicator text-white opacity-50 d-block mb-1 fs-5"></i>
                            <div class="text-white fw-bold fs-5">—</div>
                            <div class="text-white opacity-40" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;">Applications liées</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="rounded-3 p-3 text-center" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                            <i class="bi bi-clock-history text-white opacity-50 d-block mb-1 fs-5"></i>
                            <div class="text-white fw-bold fs-5">—</div>
                            <div class="text-white opacity-40" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;">Dernière synchro</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 rounded-3 px-3 py-2 mb-4" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                    <i class="bi bi-hdd-network text-white opacity-40 flex-shrink-0"></i>
                    <span class="text-white opacity-50 small">Serveur :</span>
                    <span class="text-white fw-medium small ms-1"><?= htmlspecialchars($kronoConnectUrl) ?></span>
                    <span class="badge ms-auto px-2 py-1 fw-medium" style="background:rgba(34,197,94,0.15);color:#4ade80;font-size:0.65rem;">En ligne</span>
                </div>

                <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=connexion" class="d-flex gap-2 justify-content-end">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
                    <input type="hidden" name="action" value="update_kronoconnect">
                    <input type="hidden" name="kronoconnect_enabled" value="0">
                    <button type="submit" class="btn btn-sm fw-bold px-4 rounded-pill" style="background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.25);">
                        <i class="bi bi-plug me-2"></i>Déconnecter KronoConnect
                    </button>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>

    <!-- État : KronoConnect DÉSACTIVÉ -->
    <div class="card border-0 rounded-4 overflow-hidden kc-card">
        <!-- Fond animé -->
        <div class="kc-bg-animated"></div>

        <div class="card-body p-4 position-relative" style="z-index:1;">
            <!-- En-tête -->
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="kc-logo-box rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:52px;height:52px;">
                    <i class="bi bi-link-45deg text-white" style="font-size:1.6rem;"></i>
                </div>
                <div>
                    <div class="fw-bold mb-0 kc-title" style="font-size:1.1rem;letter-spacing:-0.3px;">KronoConnect</div>
                    <div class="kc-subtitle small">Authentification unifiée · Compte unique pour toutes les applis</div>
                </div>
                <span class="badge ms-auto px-3 py-2 fw-bold rounded-pill kc-badge-off" style="font-size:0.7rem;">
                    Non activé
                </span>
            </div>

            <!-- Features -->
            <div class="row g-3 mb-4">
                <?php foreach ([
                    ['icon' => 'bi-person-badge',  'title' => 'Compte unique',        'desc' => 'Un seul identifiant pour toutes les applis Krono'],
                    ['icon' => 'bi-shield-check',  'title' => 'SSO centralisé',       'desc' => 'Connexion automatique entre les applications'],
                    ['icon' => 'bi-arrow-repeat',  'title' => 'Synchro en temps réel','desc' => 'Droits et profils synchronisés instantanément'],
                ] as $feat): ?>
                    <div class="col-md-4">
                        <div class="kc-feature-card h-100 p-3 rounded-3">
                            <i class="bi <?= $feat['icon'] ?> kc-feature-icon d-block mb-2" style="font-size:1.3rem;"></i>
                            <div class="fw-bold kc-feature-title mb-1" style="font-size:0.85rem;"><?= $feat['title'] ?></div>
                            <div class="kc-feature-desc" style="font-size:0.75rem;"><?= $feat['desc'] ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Formulaire d'activation -->
            <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=connexion">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
                <input type="hidden" name="action" value="update_kronoconnect">
                <input type="hidden" name="kronoconnect_enabled" value="1">

                <div class="kc-form-wrapper rounded-3 p-3">
                    <label class="form-label small fw-bold kc-label text-uppercase mb-2" style="letter-spacing:0.5px;">
                        URL du serveur KronoConnect
                    </label>
                    <div class="d-flex gap-2 flex-wrap">
                        <input type="url" class="form-control kc-input flex-grow-1" name="kronoconnect_url"
                            value="<?= htmlspecialchars($kronoConnectUrl) ?>"
                            placeholder="https://connect.macollectivite.fr">
                        <button type="submit" class="btn kc-btn-activate fw-bold px-4 rounded-pill shadow text-nowrap">
                            <i class="bi bi-plug-fill me-2"></i>Activer KronoConnect
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

<style>
/* --- Conteneur principal --- */
.kc-card {
    position: relative;
    border: 1px solid rgba(139, 92, 246, 0.25) !important;
    isolation: isolate;
}

/* --- Dégradé animé en arrière-plan --- */
.kc-bg-animated {
    position: absolute;
    inset: 0;
    z-index: 0;
    background: linear-gradient(
        270deg,
        #f5f3ff,
        #eef2ff,
        #faf5ff,
        #ede9fe,
        #e0e7ff,
        #f5f3ff
    );
    background-size: 400% 400%;
    animation: kc-gradient-shift 8s ease infinite;
}

@keyframes kc-gradient-shift {
    0%   { background-position: 0%   50%; }
    50%  { background-position: 100% 50%; }
    100% { background-position: 0%   50%; }
}

/* --- Logo box --- */
.kc-logo-box {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
}

/* --- Textes --- */
.kc-title    { color: #3730a3; }
.kc-subtitle { color: #6366f1; opacity: 0.8; }
.kc-label    { color: #4338ca; }

/* --- Badge "Non activé" --- */
.kc-badge-off {
    background: rgba(99, 102, 241, 0.1);
    color: #6366f1;
    border: 1px solid rgba(99, 102, 241, 0.25);
}

/* --- Cartes de features --- */
.kc-feature-card {
    background: rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(139, 92, 246, 0.15);
    backdrop-filter: blur(4px);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.kc-feature-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.12);
}
.kc-feature-icon { color: #7c3aed; }
.kc-feature-title { color: #1e1b4b; }
.kc-feature-desc  { color: #6366f1; opacity: 0.75; }

/* --- Zone formulaire --- */
.kc-form-wrapper {
    background: rgba(255, 255, 255, 0.55);
    border: 1px solid rgba(139, 92, 246, 0.2);
    backdrop-filter: blur(4px);
}
.kc-input {
    background: rgba(255, 255, 255, 0.9) !important;
    border-color: rgba(139, 92, 246, 0.3) !important;
    color: #1e1b4b;
}
.kc-input:focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.2) !important;
}
.kc-input::placeholder { color: #a5b4fc; }

/* --- Bouton d'activation --- */
.kc-btn-activate {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    background-size: 200% 200%;
    border: none;
    color: white;
    animation: kc-btn-shimmer 3s ease infinite;
}
.kc-btn-activate:hover {
    color: white;
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45) !important;
    transform: translateY(-1px);
    transition: all 0.2s ease;
}
@keyframes kc-btn-shimmer {
    0%   { background-position: 0%   50%; }
    50%  { background-position: 100% 50%; }
    100% { background-position: 0%   50%; }
}
</style>


<script>
function toggleApprovalOption() {
    const enabled = document.getElementById('allow_register').checked;
    const row     = document.getElementById('approval_row');
    row.style.opacity        = enabled ? '1'    : '0.4';
    row.style.pointerEvents  = enabled ? 'auto' : 'none';
    if (!enabled) {
        document.getElementById('require_approval').checked = false;
    }
}
</script>
