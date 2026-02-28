<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KronoInstances</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        
        /* NAVBAR STICKY */
        .navbar { border-bottom: 1px solid rgba(255,255,255,0.05); z-index: 1030; }
        .nav-link { font-size: 0.9rem; font-weight: 500; transition: all 0.2s; }
        .navbar-dark .navbar-nav .nav-link:hover, .nav-link.active-link { color: #0dcaf0 !important; }
        
        /* AVATAR MINIATURE */
        .nav-avatar {
            width: 30px; height: 30px; background: #0dcaf0; color: #000;
            font-size: 0.85rem; font-weight: 700; display: inline-flex;
            align-items: center; justify-content: center; 
            border-radius: 50%; /* Changé en 50% pour faire un cercle parfait */
            transition: transform 0.2s;
        }
        .active-account .nav-avatar { transform: scale(1.1); box-shadow: 0 0 10px rgba(13, 202, 240, 0.4); }

        /* DROPDOWNS */
        .dropdown-menu-admin, .dropdown-notifications {
            border: none !important; box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
            border-radius: 12px !important; padding: 0.75rem;
        }
        .dropdown-notifications { min-width: 320px; max-width: 350px; }
        .dropdown-item { border-radius: 8px; padding: 0.6rem 1rem; font-size: 0.85rem; font-weight: 500; margin-bottom: 2px; }
        .dropdown-header { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; font-weight: 800; padding: 0.5rem 1rem; }

        /* NOTIFICATIONS */
        .bell-container { position: relative; padding: 5px; cursor: pointer; display: flex; align-items: center; transition: 0.2s; }
        .notification-badge {
            position: absolute; top: 2px; right: -2px;
            background-color: #dc3545; color: white;
            font-size: 0.6rem; font-weight: 800;
            min-width: 16px; height: 16px;
            border-radius: 50%; display: flex;
            align-items: center; justify-content: center;
            border: 2px solid #212529;
        }

        /* NOTIF ITEM */
        .h-notif-item { 
            border-bottom: 1px solid #f1f3f5 !important; 
            white-space: normal !important; transition: 0.2s !important; 
            padding: 0.75rem 1rem !important; display: block !important;
            color: #212529 !important; text-decoration: none !important;
        }
        .h-notif-item:hover { background-color: #f8f9fa !important; }
        .h-notif-unread { background-color: rgba(13, 202, 240, 0.05) !important; border-left: 3px solid #0dcaf0 !important; }
        .notif-time { font-size: 0.7rem; color: #adb5bd; }

        @media (min-width: 992px) {
            .dropdown-notifications { position: absolute !important; right: 0 !important; left: auto !important; }
        }

        @media (max-width: 991.98px) {
            .position-static-mobile { position: static !important; }
            .header-right-zone {
                justify-content: center !important; width: 100% !important;
                margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);
            }
            .dropdown-notifications {
                position: absolute !important; left: 10px !important; right: 10px !important;
                width: calc(100% - 20px) !important; max-width: none !important; background: white !important;
                margin-top: 15px !important; border-radius: 12px !important; box-shadow: 0 15px 35px rgba(0,0,0,0.2) !important;
            }
        }

        /* SÉPARATEURS & BOUTONS */
        .header-divider { width: 1px; height: 24px; background-color: rgba(255,255,255,0.15); align-self: center; }
        .btn-logout {
            width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
            border-radius: 50% !important; transition: all 0.2s; border: none; background: transparent;
            color: #dc3545; padding: 0;
        }
        .btn-logout:hover { background-color: rgba(220, 53, 69, 0.1); color: #ff4d5e !important; }
        
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

<?php 
use app\models\User; 
use app\models\Notification;

// --- LOGIQUE NOTIFICATIONS ---
$unreadCount = 0;
$recentNotifs = [];
if (isset($_SESSION['user_id'])) {
    $notifModel = new Notification();
    $unreadCount = $notifModel->count($_SESSION['user_id'], 'unread');
    $recentNotifs = $notifModel->getForUser($_SESSION['user_id'], 5, true);
}

// --- LOGIQUE DE PAGE ACTIVE ---
$current_url  = $_GET['url'] ?? ''; 
$is_dash      = (strpos($current_url, 'dashboard') !== false);
$is_seances   = (strpos($current_url, 'seances') !== false);
$is_instances = (strpos($current_url, 'admin/instances') !== false); // Les instances sont une partie Admin Métier
$is_admin     = (strpos($current_url, 'admin') !== false && !$is_instances); // Menu "Administration logicielle"
$is_notif     = (strpos($current_url, 'notifications') !== false);
$is_compte    = (strpos($current_url, 'compte') !== false);

// Détection de droits pour afficher le menu Admin global
$hasAdminAccess = (
    User::can('view_logs') || 
    User::can('manage_users') || 
    User::can('manage_system')
);

// Préparation du nom à afficher (Prénom NOM ou Username par défaut)
$display_name = $_SESSION['username'] ?? 'Utilisateur';
$initiale = strtoupper(substr($display_name, 0, 1));

if (!empty($_SESSION['prenom']) && !empty($_SESSION['nom'])) {
    $display_name = htmlspecialchars($_SESSION['prenom']) . ' ' . strtoupper(htmlspecialchars($_SESSION['nom']));
    $initiale = strtoupper(substr($_SESSION['prenom'], 0, 1));
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm no-print py-2 sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= URLROOT ?>/dashboard">
            <div class="bg-info bg-opacity-10 rounded p-1 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="bi bi-calendar2-range text-info" style="font-size: 1.2rem;"></i>
            </div>
            <span class="letter-spacing-1">KronoInstances</span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto ms-lg-3 gap-1">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center <?= $is_dash ? 'active-link' : '' ?>" href="<?= URLROOT ?>/dashboard">
                        <i class="bi bi-grid-1x2 me-2"></i> Tableau de bord
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center <?= $is_seances ? 'active-link' : '' ?>" href="<?= URLROOT ?>/seances">
                        <i class="bi bi-card-list me-2"></i> Séances
                    </a>
                </li>

                <?php if (isset($_SESSION['user_id']) && $hasAdminAccess): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center <?= $is_admin ? 'active-link text-info fw-bold' : '' ?>" href="#" id="adminDrop" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-shield-lock-fill me-2"></i> Administration
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-admin mt-lg-2">
                        <li><a class="dropdown-item fw-bold text-info bg-info bg-opacity-10 mb-2" href="<?= URLROOT ?>/admin"><i class="bi bi-speedometer me-2"></i> Vue d'ensemble</a></li>
                        
                        <?php if (User::can('view_logs')): ?>
                            <li><h6 class="dropdown-header mt-2">Traçabilité</h6></li>
                            <li><a class="dropdown-item" href="<?= URLROOT ?>/admin/logs"><i class="bi bi-fingerprint me-2 opacity-50"></i>Journal d'Audit</a></li>
                        <?php endif; ?>
                        
                        <?php if (User::can('manage_users')): ?>
                            <li><hr class="dropdown-divider opacity-10 my-2"></li>
                            <li><h6 class="dropdown-header">Utilisateurs</h6></li>
                            <li><a class="dropdown-item" href="<?= URLROOT ?>/admin/users"><i class="bi bi-people me-2 opacity-50"></i>Comptes & Permissions</a></li>
                        <?php endif; ?>
                        
                        <?php if (User::can('manage_system')): ?>
                            <li><hr class="dropdown-divider opacity-10 my-2"></li>
                            <li><h6 class="dropdown-header">Configuration</h6></li>
                            <li><a class="dropdown-item" href="<?= URLROOT ?>/admin/parametres"><i class="bi bi-sliders2 me-2 opacity-50"></i>Paramètres système</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="d-flex align-items-center gap-3 header-right-zone">
                <div class="dropdown position-static-mobile"> 
                    <div class="bell-container dropdown-toggle <?= $is_notif ? 'text-info' : 'text-white-50' ?>" 
                         data-bs-toggle="dropdown" data-bs-display="static">
                        <i class="bi bi-bell fs-5"></i>
                        <?php if($unreadCount > 0): ?>
                            <span class="notification-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="dropdown-menu dropdown-menu-end dropdown-notifications">
                        <div class="d-flex justify-content-between align-items-center px-2 mb-2">
                            <h6 class="dropdown-header p-0">Notifications non lues</h6>
                            <a href="<?= URLROOT ?>/notifications/markAllRead" class="x-small text-decoration-none text-primary fw-bold">Tout lire</a>
                        </div>
                        
                        <div class="notif-list" style="max-height: 300px; overflow-y: auto;">
                            <?php if(empty($recentNotifs)): ?>
                                <div class="text-center py-3">
                                    <p class="small text-muted mb-0">Aucune nouvelle notification</p>
                                </div>
                            <?php else: foreach($recentNotifs as $notif): ?>
                                <a href="<?= URLROOT ?>/notifications/read/<?= $notif['id'] ?>" 
                                   class="h-notif-item <?= !$notif['is_read'] ? 'h-notif-unread' : '' ?>">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-info-circle-fill text-<?= htmlspecialchars($notif['type'] ?? 'info') ?> me-2 mt-1"></i>
                                        <div>
                                            <div class="small fw-bold mb-0 text-dark"><?= htmlspecialchars($notif['message']) ?></div>
                                            <div class="notif-time opacity-75"><?= date('d/m H:i', strtotime($notif['created_at'])) ?></div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                        <hr class="dropdown-divider opacity-10">
                        <a href="<?= URLROOT ?>/notifications" class="dropdown-item text-center small fw-bold text-primary py-2">Voir tout l'historique</a>
                    </div>
                </div>

                <div class="header-divider d-none d-lg-block"></div>

                <a href="<?= URLROOT ?>/compte" class="nav-link text-white d-flex align-items-center gap-2 py-1 px-2 rounded hover-white <?= $is_compte ? 'text-info active-account active-link' : '' ?>">
                    <div class="nav-avatar">
                        <?= $initiale ?>
                    </div>
                    <span class="small fw-bold"><?= $display_name ?></span>
                </a>

                <div class="header-divider d-none d-lg-block"></div>

                <a href="<?= URLROOT ?>/login/logout" class="btn-logout" title="Se déconnecter">
                    <i class="bi bi-power fs-5"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Gestion des flash messages globaux (s'ils existent) -->
<?php
if (!empty($_SESSION['flash_success'])) {
    echo '<div class="container no-print mb-3"><div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle-fill me-2"></i>' . htmlspecialchars($_SESSION['flash_success']) . '</div></div>';
    unset($_SESSION['flash_success']);
}
if (!empty($_SESSION['flash_error'])) {
    echo '<div class="container no-print mb-3"><div class="alert alert-danger border-0 shadow-sm"><i class="bi bi-exclamation-triangle-fill me-2"></i>' . htmlspecialchars($_SESSION['flash_error']) . '</div></div>';
    unset($_SESSION['flash_error']);
}
?>