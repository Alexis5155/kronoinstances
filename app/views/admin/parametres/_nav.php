<?php
use app\models\User;
$section = $section ?? 'general';
?>

<div class="col-lg-3">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden sticky-top" style="top: 20px;">
        <div class="card-body p-3">

            <nav class="nav flex-column gap-1">

                <?php if(User::can('manage_system')): ?>

                <!-- Général -->
                <div class="px-3 pt-2 pb-1">
                    <span class="text-muted fw-bold text-uppercase" style="font-size:0.65rem; letter-spacing:1px;">Général</span>
                </div>

                <a class="nav-link d-flex align-items-center rounded-3 px-3 py-2 fw-medium <?= $section === 'general' ? 'bg-primary bg-opacity-10 text-primary' : 'text-dark' ?>"
                   href="<?= URLROOT ?>/admin/parametres?section=general"
                   style="transition: all 0.2s ease; <?= $section === 'general' ? 'border: 1px solid rgba(13,110,253,0.2);' : 'border: 1px solid transparent;' ?>">
                    <div class="me-3 d-flex align-items-center justify-content-center rounded-3 <?= $section === 'general' ? 'bg-primary bg-opacity-10 text-primary' : 'bg-light text-muted' ?>" style="width:32px;height:32px;">
                        <i class="bi bi-building"></i>
                    </div>
                    Identité
                </a>

                <!-- Technique -->
                <div class="px-3 pt-3 pb-1">
                    <span class="text-muted fw-bold text-uppercase" style="font-size:0.65rem; letter-spacing:1px;">Système</span>
                </div>

                <a class="nav-link d-flex align-items-center rounded-3 px-3 py-2 fw-medium <?= $section === 'smtp' ? 'bg-primary bg-opacity-10 text-primary' : 'text-dark' ?>"
                   href="<?= URLROOT ?>/admin/parametres?section=smtp"
                   style="transition: all 0.2s ease; <?= $section === 'smtp' ? 'border: 1px solid rgba(13,110,253,0.2);' : 'border: 1px solid transparent;' ?>">
                    <div class="me-3 d-flex align-items-center justify-content-center rounded-3 <?= $section === 'smtp' ? 'bg-primary bg-opacity-10 text-primary' : 'bg-light text-muted' ?>" style="width:32px;height:32px;">
                        <i class="bi bi-envelope-at"></i>
                    </div>
                    E-mail (SMTP)
                </a>

                <!-- Après le lien "E-mail (SMTP)" -->
                <a class="nav-link d-flex align-items-center rounded-3 px-3 py-2 fw-medium <?= $section === 'connexion' ? 'bg-primary bg-opacity-10 text-primary' : 'text-dark' ?>"
                href="<?= URLROOT ?>/admin/parametres?section=connexion"
                style="transition: all 0.2s ease; <?= $section === 'connexion' ? 'border: 1px solid rgba(13,110,253,0.2);' : 'border: 1px solid transparent;' ?>">
                    <div class="me-3 d-flex align-items-center justify-content-center rounded-3 <?= $section === 'connexion' ? 'bg-primary bg-opacity-10 text-primary' : 'bg-light text-muted' ?>" style="width:32px;height:32px;">
                        <i class="bi bi-door-open"></i>
                    </div>
                    Connexion
                </a>

                <a class="nav-link d-flex align-items-center rounded-3 px-3 py-2 fw-medium <?= $section === 'system' ? 'bg-primary bg-opacity-10 text-primary' : 'text-dark' ?>"
                   href="<?= URLROOT ?>/admin/parametres?section=system"
                   style="transition: all 0.2s ease; <?= $section === 'system' ? 'border: 1px solid rgba(13,110,253,0.2);' : 'border: 1px solid transparent;' ?>">
                    <div class="me-3 d-flex align-items-center justify-content-center rounded-3 <?= $section === 'system' ? 'bg-primary bg-opacity-10 text-primary' : 'bg-light text-muted' ?>" style="width:32px;height:32px;">
                        <i class="bi bi-database-gear"></i>
                    </div>
                    Base de données
                </a>

                <!-- Déploiement -->
                <div class="px-3 pt-3 pb-1">
                    <span class="text-muted fw-bold text-uppercase" style="font-size:0.65rem; letter-spacing:1px;">Déploiement</span>
                </div>

                <a class="nav-link d-flex align-items-center rounded-3 px-3 py-2 fw-medium <?= $section === 'update' ? 'bg-primary bg-opacity-10 text-primary' : 'text-dark' ?>"
                   href="<?= URLROOT ?>/admin/parametres?section=update"
                   style="transition: all 0.2s ease; <?= $section === 'update' ? 'border: 1px solid rgba(13,110,253,0.2);' : 'border: 1px solid transparent;' ?>">
                    <div class="me-3 d-flex align-items-center justify-content-center rounded-3 <?= $section === 'update' ? 'bg-primary bg-opacity-10 text-primary' : 'bg-light text-muted' ?>" style="width:32px;height:32px;">
                        <i class="bi bi-cloud-arrow-down"></i>
                    </div>
                    Mise à jour
                </a>

                <?php endif; ?>

            </nav>
        </div>
    </div>
</div>

<style>
.nav-link:hover:not(.bg-primary) {
    background-color: #f8f9fa !important;
}
</style>
