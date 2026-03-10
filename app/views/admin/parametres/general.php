<?php use app\models\User; ?>

<?php if (User::can('manage_system')): ?>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 border-0 border-bottom">
        <h6 class="mb-0 fw-bold d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-2 d-inline-flex">
                <i class="bi bi-building"></i>
            </div>
            Identité de la collectivité
        </h6>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=general">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
            <input type="hidden" name="action" value="update_general">

            <div class="mb-4">
                <label class="form-label small fw-bold text-muted text-uppercase mb-2" style="letter-spacing:0.5px;">
                    Nom affiché sur les documents officiels
                </label>
                <input type="text" class="form-control bg-light fw-bold" name="col_name"
                       value="<?= htmlspecialchars($col_nom ?? '') ?>"
                       placeholder="ex : Ville de Lens" required>
                <div class="form-text mt-2 small">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    Ce nom apparaîtra en haut des convocations et des listes d'émargement PDF.
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

<?php else: ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-5 text-center">
        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;">
            <i class="bi bi-shield-lock" style="font-size:2rem;"></i>
        </div>
        <h5 class="fw-bold text-dark">Accès refusé</h5>
        <p class="text-muted mb-0">Vous n'avez pas les droits nécessaires pour modifier l'identité de la collectivité.</p>
    </div>
</div>

<?php endif; ?>
