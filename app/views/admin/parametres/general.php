<?php use app\models\User; ?>

<?php if (User::can('manage_system')): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-building me-2 text-primary"></i> Identité de la collectivité</h5>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=general">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
            <input type="hidden" name="action" value="update_general">
            
            <div class="mb-4">
                <label class="form-label text-uppercase text-muted fw-bold small">Nom affiché sur les documents officiels</label>
                <input type="text" class="form-control form-control-lg fw-bold" name="col_name" value="<?= htmlspecialchars($col_nom ?? '') ?>" placeholder="ex: Ville de Lens" required>
                <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i> Ce nom apparaîtra en haut des convocations et des listes d'émargement PDF.</div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary fw-bold px-5 shadow-sm">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-5 text-center">
        <i class="bi bi-shield-lock text-muted display-1 opacity-25 mb-4 d-block"></i>
        <h4 class="fw-bold">Accès refusé</h4>
        <p class="text-muted mb-0">Vous n'avez pas les droits nécessaires pour modifier l'identité de la collectivité.</p>
    </div>
</div>
<?php endif; ?>
