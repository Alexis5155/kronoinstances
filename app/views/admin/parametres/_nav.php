<?php
use app\models\User;
$section = $section ?? 'general';
?>
<div class="col-lg-3">
    <div class="card border-0 shadow-sm sticky-top" style="top: 20px; border-radius: 15px;">
        <div class="card-body p-3">
            <nav class="nav flex-column nav-pills custom-v-pills">
                
                <?php if(User::can('manage_system')): ?>
                <div class="nav-section-title">Général</div>
                
                <a class="nav-link <?= $section === 'general' ? 'active' : '' ?>" href="<?= URLROOT ?>/admin/parametres?section=general">
                    <i class="bi bi-building"></i> Identité
                </a>

                <a class="nav-link <?= $section === 'smtp' ? 'active' : '' ?>" href="<?= URLROOT ?>/admin/parametres?section=smtp">
                    <i class="bi bi-envelope-at"></i> E-mail
                </a>
                
                <div class="nav-section-title mt-4">Technique & Serveur</div>
                
                <a class="nav-link <?= $section === 'system' ? 'active' : '' ?>" href="<?= URLROOT ?>/admin/parametres?section=system">
                    <i class="bi bi-database-gear"></i> Base de données
                </a>
                
                <div class="nav-section-title mt-4">Déploiement</div>
                
                <a class="nav-link <?= $section === 'update' ? 'active' : '' ?>" href="<?= URLROOT ?>/admin/parametres?section=update">
                    <i class="bi bi-cloud-arrow-down"></i> Mise à jour
                </a>
                <?php endif; ?>

            </nav>
        </div>
    </div>
</div>

<style>
/* Titres des sections dans le menu */
.nav-section-title {
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    color: #adb5bd;
    letter-spacing: 1px;
    margin-bottom: 8px;
    padding-left: 12px;
}

/* Style de base des boutons (Agrandis, arrondis) */
.custom-v-pills .nav-link { 
    transition: all 0.2s ease;
    border-radius: 12px; /* Coins bien arrondis */
    padding: 12px 18px; /* Boutons plus grands */
    margin-bottom: 6px;
    color: #495057; /* Texte gris foncé par défaut */
    font-weight: 500;
    border: 1px solid transparent; /* Bordure invisible par défaut pour garder la taille */
    display: flex;
    align-items: center;
}

.custom-v-pills .nav-link i {
    margin-right: 12px;
    font-size: 1.1rem;
    opacity: 0.7; /* Icône légèrement grisée par défaut */
}

/* Survol des boutons inactifs */
.custom-v-pills .nav-link:hover:not(.active) { 
    background-color: #f8f9fa; 
    color: #212529;
}

/* Style spécifique du bouton ACTIF (Inspiré de ta photo) */
.custom-v-pills .nav-link.active {
    background-color: #f0f4ff !important; /* Bleu très pâle */
    color: #0d6efd !important; /* Texte bleu vif */
    font-weight: 600; /* Texte légèrement plus gras */
    border: 1px solid #b6d4fe !important; /* Bordure fine bleu clair */
}

.custom-v-pills .nav-link.active i {
    opacity: 1; /* L'icône prend la couleur bleu vif complète */
}
</style>
