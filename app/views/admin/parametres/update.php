<?php
$check_zip    = class_exists('ZipArchive');
$check_fopen  = ini_get('allow_url_fopen');
$check_write  = is_writable('./');
$system_ready = ($check_zip && $check_fopen && $check_write);
?>

<!-- Prérequis serveur -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white py-3 border-0 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-2 d-inline-flex">
                <i class="bi bi-cloud-arrow-down"></i>
            </div>
            Mises à jour
        </h6>
        <span class="badge px-2 py-1 fw-medium border <?= $system_ready ? 'bg-success bg-opacity-10 text-success border-success' : 'bg-danger bg-opacity-10 text-danger border-danger' ?> border-opacity-25" style="font-size:0.7rem;">
            <i class="bi <?= $system_ready ? 'bi-check2-circle' : 'bi-x-circle' ?> me-1"></i>
            <?= $system_ready ? 'Système prêt' : 'Anomalie prérequis' ?>
        </span>
    </div>
    <div class="card-body p-4">

        <!-- Prérequis -->
        <div class="bg-light rounded-3 border p-3 mb-4">
            <div class="small fw-bold text-muted text-uppercase mb-3" style="font-size:0.65rem;letter-spacing:0.5px;">Vérification des prérequis serveur</div>
            <div class="row g-2">
                <?php foreach ([
                    ['check' => $check_zip,   'label' => 'Extension ZipArchive'],
                    ['check' => $check_fopen, 'label' => 'Directive allow_url_fopen'],
                    ['check' => $check_write, 'label' => 'Droits d\'écriture (Racine)'],
                ] as $req): ?>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3 <?= $req['check'] ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10' ?>">
                            <i class="bi <?= $req['check'] ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?>"></i>
                            <span class="small fw-medium <?= $req['check'] ? 'text-success' : 'text-danger' ?>"><?= $req['label'] ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$system_ready): ?>
                <div class="alert alert-danger border-0 rounded-3 mt-3 mb-0 small py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    Un ou plusieurs prérequis manquent. La mise à jour automatique risque d'échouer.
                </div>
            <?php endif; ?>
        </div>

        <!-- Canal de diffusion -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 bg-light rounded-3 border p-3 mb-4">
            <div>
                <div class="small fw-bold text-muted text-uppercase mb-1" style="font-size:0.65rem;letter-spacing:0.5px;">Canal de diffusion</div>
                <div class="small text-muted">Choisissez entre la branche stable (recommandée) ou beta.</div>
            </div>
            <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=update" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
                <input type="hidden" name="action" value="update_update_settings">
                <select name="update_track" class="form-select form-select-sm fw-bold bg-white" style="width:auto;min-width:160px;">
                    <option value="main"  <?= ($update_track ?? 'main') === 'main'  ? 'selected' : '' ?>>🌟 Stable (Recommandé)</option>
                    <option value="beta"  <?= ($update_track ?? 'main') === 'beta'  ? 'selected' : '' ?>>🧪 Beta (Test)</option>
                </select>
                <button type="submit" class="btn btn-sm btn-dark fw-bold px-3 rounded-pill shadow-sm">Appliquer</button>
            </form>
        </div>

        <!-- Résultat de la vérification GitHub -->
        <?php if (!isset($update_data) || !$update_data): ?>
            <div class="text-center py-5 bg-light rounded-4 border">
                <i class="bi bi-cloud-slash display-4 text-muted d-block mb-3 opacity-25"></i>
                <h6 class="fw-bold text-dark mb-1">Serveurs inaccessibles</h6>
                <p class="text-muted small mb-0">Impossible de joindre GitHub pour vérifier les mises à jour.</p>
            </div>

        <?php elseif ($update_data['has_new']): ?>
            <!-- Nouvelle version disponible -->
            <div class="card border-0 rounded-4 overflow-hidden mb-4" style="background: linear-gradient(135deg, #e8f4ff 0%, #f0f7ff 100%); border: 1px solid #b6d4fe !important;">
                <div class="card-body p-4 d-flex align-items-center gap-4">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:60px;height:60px;">
                        <i class="bi bi-stars fs-2"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-primary mb-1">Mise à jour disponible — v<?= htmlspecialchars($update_data['version']) ?></h5>
                        <p class="mb-0 small text-dark">Une nouvelle version de KronoInstances est prête à être installée.</p>
                    </div>
                </div>
            </div>

            <div class="small fw-bold text-muted text-uppercase mb-2" style="font-size:0.65rem;letter-spacing:0.5px;">Notes de version (Changelog)</div>
            <div class="bg-light border rounded-4 p-4 mb-4" style="max-height:300px;overflow-y:auto;font-family:monospace;font-size:0.85rem;white-space:pre-wrap;line-height:1.6;color:#333;">
<?= htmlspecialchars($update_data['changelog']) ?>
            </div>

            <div class="text-center">
                <a href="<?= URLROOT ?>/update"
                   class="btn btn-primary btn-lg fw-bold px-5 shadow rounded-pill <?= !$system_ready ? 'disabled' : '' ?>">
                    <i class="bi bi-magic me-2"></i>Installer la v<?= htmlspecialchars($update_data['version']) ?>
                </a>
                <?php if (!$system_ready): ?>
                    <div class="form-text text-danger mt-2 fw-bold small">
                        <i class="bi bi-info-circle me-1"></i>Installation bloquée à cause des prérequis manquants.
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- Système à jour -->
            <div class="text-center py-5 bg-light rounded-4 border mb-4">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;">
                    <i class="bi bi-patch-check-fill" style="font-size:2rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Votre système est à jour</h5>
                <p class="text-muted small mb-0 fw-bold text-uppercase" style="letter-spacing:1px;">
                    Version <?= defined('APP_VERSION') ? APP_VERSION : 'Inconnue' ?> &nbsp;·&nbsp; Canal <?= ($update_track ?? 'main') === 'main' ? 'Stable' : 'Beta' ?>
                </p>
            </div>

            <div class="small fw-bold text-muted text-uppercase mb-2" style="font-size:0.65rem;letter-spacing:0.5px;">Dernières nouveautés installées</div>
            <div class="border-start border-4 border-success bg-light px-4 py-3 rounded-end" style="font-family:monospace;font-size:0.85rem;white-space:pre-wrap;color:#555;">
<?= htmlspecialchars($update_data['changelog']) ?>
            </div>
        <?php endif; ?>

    </div>
</div>
