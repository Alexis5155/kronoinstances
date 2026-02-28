<?php
// Vérification des prérequis pour s'assurer que le système peut installer les mises à jour
$check_zip    = class_exists('ZipArchive');
$check_fopen  = ini_get('allow_url_fopen');
$check_write  = is_writable('./');
$system_ready = ($check_zip && $check_fopen && $check_write);
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-cloud-arrow-down me-2 text-primary"></i>Gestion des mises à jour</h5>
        <span class="badge <?= $system_ready ? 'bg-success' : 'bg-danger' ?> text-uppercase fw-bold" style="font-size: 0.65rem;">
            <?= $system_ready ? 'Système Prêt' : 'Anomalie Prérequis' ?>
        </span>
    </div>
    <div class="card-body p-4 pt-0">
        
        <!-- Encart de vérification des prérequis techniques -->
        <div class="p-3 bg-light rounded-3 border mb-4 mt-2">
            <h6 class="fw-bold small text-uppercase text-muted mb-3" style="font-size: 0.6rem;">Vérification des prérequis serveur</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="d-flex align-items-center small <?= $check_zip ? 'text-success' : 'text-danger fw-bold' ?>">
                        <i class="bi <?= $check_zip ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-2"></i> Extension ZipArchive
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center small <?= $check_fopen ? 'text-success' : 'text-danger fw-bold' ?>">
                        <i class="bi <?= $check_fopen ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-2"></i> Directive allow_url_fopen
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center small <?= $check_write ? 'text-success' : 'text-danger fw-bold' ?>">
                        <i class="bi <?= $check_write ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-2"></i> Droits d'écriture (Racine)
                    </div>
                </div>
            </div>
            <?php if (!$system_ready): ?>
                <div class="alert alert-danger mt-3 mb-0 small border-0 py-2">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Un ou plusieurs prérequis manquent. La mise à jour automatique risque d'échouer.
                </div>
            <?php endif; ?>
        </div>

        <!-- Formulaire de choix du canal de diffusion -->
        <div class="card border shadow-none bg-light mb-4">
            <div class="card-body py-2 px-3">
                <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=update" class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
                    <input type="hidden" name="action" value="update_update_settings">
                    
                    <span class="small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Canal de diffusion souhaité :</span>
                    
                    <div class="d-flex gap-2">
                        <select name="update_track" class="form-select form-select-sm fw-bold text-dark" style="width: auto; min-width: 140px; border-radius: 8px;">
                            <option value="main" <?= ($update_track ?? 'main') === 'main' ? 'selected' : '' ?>>🌟 Stable (Recommandé)</option>
                            <option value="beta" <?= ($update_track ?? 'main') === 'beta' ? 'selected' : '' ?>>🧪 Beta (Test)</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-dark px-3 fw-bold shadow-sm" style="border-radius: 8px;">Appliquer</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Affichage des résultats de la vérification GitHub -->
        <?php if (!isset($update_data) || !$update_data): ?>
            <div class="text-center py-5 border rounded-3 bg-white">
                <i class="bi bi-cloud-slash display-4 text-muted mb-2 d-block opacity-50"></i>
                <h5 class="fw-bold mb-1">Serveurs inaccessibles</h5>
                <p class="text-muted small mb-0">Impossible de joindre les serveurs GitHub pour vérifier les mises à jour.</p>
            </div>
        <?php else: ?>
            <?php if ($update_data['has_new']): ?>
                <!-- Nouvelle mise à jour disponible -->
                <div class="alert alert-info border-0 shadow-sm d-flex align-items-center p-4 mb-4" style="background-color: #f0f7ff;">
                    <i class="bi bi-stars fs-1 me-4 text-primary"></i>
                    <div>
                        <h5 class="fw-bold mb-1 text-primary">Mise à jour disponible (v<?= htmlspecialchars($update_data['version']) ?>)</h5>
                        <p class="mb-0 small text-dark">Une nouvelle version de KronoInstances est prête à être installée sur votre serveur.</p>
                    </div>
                </div>
                
                <h6 class="fw-bold small text-uppercase text-muted mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Notes de version (Changelog) :</h6>
                <div class="changelog-box mb-4 p-4 border rounded-3 bg-white shadow-sm" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.85rem; white-space: pre-wrap; line-height: 1.6; color: #333;">
<?= htmlspecialchars($update_data['changelog']) ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="<?= URLROOT ?>/update" class="btn btn-primary btn-lg fw-bold px-5 py-3 shadow <?= !$system_ready ? 'disabled' : '' ?>" style="border-radius: 12px;">
                        <i class="bi bi-magic me-2"></i>Lancer l'installation de la v<?= htmlspecialchars($update_data['version']) ?>
                    </a>
                    <?php if(!$system_ready): ?>
                        <div class="form-text text-danger mt-2 fw-bold"><i class="bi bi-info-circle me-1"></i> Installation bloquée à cause des prérequis.</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Système déjà à jour -->
                <div class="text-center py-5 border rounded-3 mb-4 bg-white shadow-sm">
                    <i class="bi bi-patch-check-fill text-success display-2 opacity-25 mb-3 d-block"></i>
                    <h4 class="fw-bold mb-1">Votre système est à jour</h4>
                    <p class="text-muted small text-uppercase mb-0 fw-bold" style="letter-spacing: 1px;">
                        Version <?= defined('APP_VERSION') ? APP_VERSION : 'Inconnue' ?> • Canal <?= ($update_track ?? 'main') === 'main' ? 'Stable' : 'Beta' ?>
                    </p>
                </div>
                
                <h6 class="fw-bold small text-uppercase text-muted mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Dernières nouveautés installées :</h6>
                <div class="changelog-box border-0 shadow-none border-start border-4 border-success bg-light px-4 py-3 rounded-end" style="font-family: monospace; font-size: 0.85rem; white-space: pre-wrap; color: #555;">
<?= htmlspecialchars($update_data['changelog']) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
    </div>
</div>
