<?php 
use app\models\User; 
use app\models\Notification;

// --- LOGIQUE DE PAGE ACTIVE ---
$current_url  = $_GET['url'] ?? ''; 
$is_dash      = (strpos($current_url, 'dashboard') !== false);
$is_seances   = (strpos($current_url, 'seances') !== false);
$is_docs      = (strpos($current_url, 'documents') !== false);
$is_instances = (strpos($current_url, 'admin/instances') !== false); 
$is_admin     = (strpos($current_url, 'admin') !== false);
$is_notif     = (strpos($current_url, 'notifications') !== false);
$is_compte    = (strpos($current_url, 'compte') !== false);

// Détermination du nom de la page courante
$pageName = "Accueil";
if ($is_dash) $pageName = "Tableau de bord";
elseif ($is_seances) $pageName = "Séances";
elseif ($is_docs) $pageName = "Mes Documents";
elseif ($is_instances) $pageName = "Gestion des instances";
elseif ($is_admin) $pageName = "Administration";
elseif ($is_notif) $pageName = "Notifications";
elseif ($is_compte) $pageName = "Mon profil";

// --- LOGIQUE NOTIFICATIONS ---
$unreadCount = 0;
$recentNotifs = [];
if (isset($_SESSION['user_id'])) {
    $notifModel = new Notification();
    $unreadCount = $notifModel->count($_SESSION['user_id'], 'unread');
    $recentNotifs = $notifModel->getForUser($_SESSION['user_id'], 5, true);
}

// Préparation du titre de la page
$pageTitle = $pageName . " - KronoInstances";
if ($unreadCount > 0) {
    $pageTitle = "($unreadCount) " . $pageTitle;
}

// Détection de droits
$hasAdminAccess = (
    User::can('view_logs') || 
    User::can('manage_users') || 
    User::can('manage_system') ||
    User::can('manage_instances')
);

// Préparation du nom à afficher
$display_name = $_SESSION['username'] ?? 'Utilisateur';
$initiale = strtoupper(substr($display_name, 0, 1));

if (!empty($_SESSION['prenom']) && !empty($_SESSION['nom'])) {
    $display_name = htmlspecialchars($_SESSION['prenom']) . ' ' . strtoupper(htmlspecialchars($_SESSION['nom']));
    $initiale = strtoupper(substr($_SESSION['prenom'], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= URLROOT ?>/assets/css/components.css">

    <style>
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        
        /* NAVBAR STICKY */
        .navbar { border-bottom: 1px solid rgba(255,255,255,0.05); z-index: 1030; }
        .nav-link { font-size: 0.9rem; font-weight: 500; transition: all 0.2s; padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
        .navbar-dark .navbar-nav .nav-link:hover, .nav-link.active-link { color: #0dcaf0 !important; }
        
        /* AVATAR MINIATURE */
        .nav-avatar {
            width: 30px; height: 30px; background: #0dcaf0; color: #000;
            font-size: 0.85rem; font-weight: 700; display: inline-flex;
            align-items: center; justify-content: center; 
            border-radius: 50%;
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
        .dropdown-header { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; font-weight: 800; padding: 0.5rem 1rem; margin:0; }

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
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* Bouton Tout lire (icône) */
        .btn-mark-all {
            width: 28px; height: 28px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%; background: #f8f9fa; color: #6c757d;
            border: 1px solid #e9ecef;
            transition: all 0.2s; text-decoration: none;
        }
        .btn-mark-all:hover { background: #e6f7ff; color: #0dcaf0; border-color: #0dcaf0; }
        
        /* Animation succès bouton tout lire */
        .mark-success { background: #d1e7dd !important; color: #198754 !important; border-color: #198754 !important; }

        /* Animation quand la cloche sonne */
        @keyframes ring {
            0% { transform: rotate(0); }
            10% { transform: rotate(15deg); }
            20% { transform: rotate(-10deg); }
            30% { transform: rotate(15deg); }
            40% { transform: rotate(-10deg); }
            50% { transform: rotate(0); }
            100% { transform: rotate(0); }
        }
        .bell-ringing i { animation: ring 1s ease 1; color: #0dcaf0 !important; }
        .badge-pop { transform: scale(1.3); }

        /* TOAST DE NOTIFICATION EN DIRECT */
        .live-toast-container { position: fixed; top: 80px; right: 20px; z-index: 1060; }
        .live-toast {
            background: white; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-radius: 8px; width: 300px;
            transform: translateX(120%); transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .live-toast.show { transform: translateX(0); }
        
        .toast-info { border-left: 4px solid #0dcaf0; }
        .toast-success { border-left: 4px solid #198754; }
        .toast-warning { border-left: 4px solid #ffc107; }
        .toast-danger { border-left: 4px solid #dc3545; }

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

        @media (max-width: 1199px) {
            .navbar-brand span { display: none; }
            .nav-link { font-size: 0.85rem; }
            .header-right-zone { gap: 0.5rem !important; }
        }

        @media (max-width: 991.98px) {
            .navbar-brand span { display: inline; }
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

        .header-divider { width: 1px; height: 24px; background-color: rgba(255,255,255,0.15); align-self: center; margin: 0 0.5rem; }
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

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm no-print py-2 sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center me-2" href="<?= URLROOT ?>/dashboard">
            <div class="bg-info bg-opacity-10 rounded p-1 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="bi bi-calendar2-range text-info" style="font-size: 1.2rem;"></i>
            </div>
            <span class="letter-spacing-1">KronoInstances</span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center <?= $is_dash ? 'active-link' : '' ?>" href="<?= URLROOT ?>/dashboard">
                        <i class="bi bi-grid-1x2 me-1"></i> Tableau de bord
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center <?= $is_seances ? 'active-link' : '' ?>" href="<?= URLROOT ?>/seances">
                        <i class="bi bi-calendar-event me-1"></i> Séances
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center <?= $is_docs ? 'active-link' : '' ?>" href="<?= URLROOT ?>/documents">
                        <i class="bi bi-folder2-open me-1"></i> Mes documents
                    </a>
                </li>

                <?php if (isset($_SESSION['user_id']) && $hasAdminAccess): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center <?= $is_admin ? 'active-link text-info fw-bold' : '' ?>" href="#" id="adminDrop" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-gear-fill me-1"></i> Administration
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-admin mt-lg-2">
                        <li><a class="dropdown-item fw-bold text-info bg-info bg-opacity-10 mb-2" href="<?= URLROOT ?>/admin"><i class="bi bi-speedometer me-2"></i> Vue d'ensemble</a></li>
                        
                        <?php if (User::can('manage_instances')): ?>
                            <li><h6 class="dropdown-header mt-2">Gestion Métier</h6></li>
                            <li><a class="dropdown-item <?= $is_instances ? 'active' : '' ?>" href="<?= URLROOT ?>/admin/instances"><i class="bi bi-diagram-3 me-2 opacity-50"></i>Instances & Collèges</a></li>
                        <?php endif; ?>

                        <?php if (User::can('view_logs') || User::can('manage_users') || User::can('manage_system')): ?>
                            <li><hr class="dropdown-divider opacity-10 my-2"></li>
                            <li><h6 class="dropdown-header">Système & Sécurité</h6></li>
                            
                            <?php if (User::can('view_logs')): ?>
                            <li><a class="dropdown-item" href="<?= URLROOT ?>/admin/logs"><i class="bi bi-fingerprint me-2 opacity-50"></i>Journal d'Audit</a></li>
                            <?php endif; ?>
                            
                            <?php if (User::can('manage_users')): ?>
                            <li><a class="dropdown-item" href="<?= URLROOT ?>/admin/users"><i class="bi bi-people me-2 opacity-50"></i>Comptes & Permissions</a></li>
                            <?php endif; ?>
                            
                            <?php if (User::can('manage_system')): ?>
                            <li><a class="dropdown-item" href="<?= URLROOT ?>/admin/parametres"><i class="bi bi-sliders2 me-2 opacity-50"></i>Paramètres système</a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="d-flex align-items-center header-right-zone">
                <div class="dropdown position-static-mobile"> 
                    <div id="bell-icon-container" class="bell-container dropdown-toggle <?= $is_notif ? 'text-info' : 'text-white-50' ?>" 
                         data-bs-toggle="dropdown" data-bs-display="static">
                        <i class="bi bi-bell fs-5"></i>
                        <span id="notif-badge" class="notification-badge <?= $unreadCount == 0 ? 'd-none' : '' ?>">
                            <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                        </span>
                    </div>
                    
                    <div class="dropdown-menu dropdown-menu-end dropdown-notifications" id="notif-dropdown-menu">
                        <div class="d-flex justify-content-between align-items-center px-2 mb-2">
                            <h6 class="dropdown-header">Notifications non lues</h6>
                            <a href="#" onclick="markAllNotificationsAsRead(event)" class="btn-mark-all" id="btn-mark-all-read" title="Marquer tout comme lu">
                                <i class="bi bi-envelope-check" id="icon-mark-all-read"></i>
                            </a>
                        </div>
                        
                        <div id="notif-list-container" class="notif-list" style="max-height: 300px; overflow-y: auto;">
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

                <a href="<?= URLROOT ?>/compte" class="nav-link text-white d-flex align-items-center gap-2 py-1 px-2 rounded hover-white <?= $is_compte ? 'text-info active-account active-link' : '' ?>" style="white-space: nowrap;">
                    <div class="nav-avatar">
                        <?= $initiale ?>
                    </div>
                    <span class="small fw-bold d-none d-sm-inline"><?= $display_name ?></span>
                </a>

                <div class="header-divider d-none d-lg-block"></div>

                <a href="<?= URLROOT ?>/login/logout" class="btn-logout" title="Se déconnecter">
                    <i class="bi bi-power fs-5"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Container pour les alertes Toast en direct -->
<div class="live-toast-container">
    <div id="live-notif-toast" class="live-toast p-3 d-flex align-items-start gap-3 toast-info">
        <i id="live-notif-icon" class="bi bi-info-circle-fill fs-4 text-info mt-1"></i>
        <div class="flex-grow-1">
            <h6 class="fw-bold mb-1 text-dark" id="live-notif-title">Nouvelle notification</h6>
            <p id="live-notif-message" class="small text-muted mb-0 lh-sm">Message ici</p>
        </div>
        <button type="button" class="btn-close mt-1" onclick="closeLiveToast()"></button>
    </div>
</div>

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

<!-- SCRIPT POUR L'ACTUALISATION EN DIRECT ET LE TOUT LIRE AJAX -->
<!-- SCRIPT POUR L'ACTUALISATION EN DIRECT ET LE TOUT LIRE AJAX -->
<script>
let lastNotifCount = <?= $unreadCount ?>;
const basePageTitle = "<?= htmlspecialchars($pageName) ?> - KronoInstances";

// Fonction pour marquer tout comme lu sans recharger la page
function markAllNotificationsAsRead(e) {
    // Si on veut laisser le menu se fermer tout seul, on ne met PAS e.preventDefault()
    // Bootstrap s'occupera de fermer le dropdown.
    
    fetch('<?= URLROOT ?>/notifications/apiMarkAllRead')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                lastNotifCount = 0;
                
                // 1. Cacher le badge rouge et nettoyer le titre
                document.getElementById('notif-badge').classList.add('d-none');
                document.title = basePageTitle;
                
                // 2. Animer la CLOCHE PRINCIPALE du menu
                const bellContainer = document.getElementById('bell-icon-container');
                const bellIcon = bellContainer.querySelector('i');
                
                // On stocke les classes originales pour les remettre après
                const originalBellClasses = bellIcon.className;
                const originalContainerClasses = bellContainer.className;
                
                // On transforme la cloche en coche verte de succès
                bellIcon.className = 'bi bi-check2-all fs-5 text-success fw-bold';
                bellContainer.style.transform = 'scale(1.2)';
                
                // 3. Vider la liste en arrière-plan pour la prochaine ouverture
                document.getElementById('notif-list-container').innerHTML = `
                    <div class="text-center py-3">
                        <p class="small text-muted mb-0"><i class="bi bi-check2-circle text-success me-1"></i>Tout est à jour !</p>
                    </div>
                `;
                
                // 4. Au bout de 2 secondes, remettre la cloche normale
                setTimeout(() => {
                    bellIcon.className = originalBellClasses;
                    bellContainer.style.transform = 'scale(1)';
                }, 2000);
            }
        })
        .catch(error => console.error('Erreur API MarkAllRead:', error));
}


function checkNewNotifications() {
    fetch('<?= URLROOT ?>/notifications/apiCheck')
        .then(response => {
            if (!response.ok) throw new Error('Erreur réseau');
            return response.json();
        })
        .then(data => {
            // Mise à jour de la liste déroulante HTML
            if (data.htmlList) {
                document.getElementById('notif-list-container').innerHTML = data.htmlList;
            }

            if (data.unreadCount > lastNotifCount) {
                lastNotifCount = data.unreadCount;
                
                const badge = document.getElementById('notif-badge');
                badge.innerText = data.unreadCount > 9 ? '9+' : data.unreadCount;
                badge.classList.remove('d-none');
                
                document.title = `(${data.unreadCount}) ${basePageTitle}`;
                
                // Animation de la cloche "qui sonne"
                const bellContainer = document.getElementById('bell-icon-container');
                bellContainer.classList.add('bell-ringing');
                badge.classList.add('badge-pop');
                setTimeout(() => {
                    bellContainer.classList.remove('bell-ringing');
                    badge.classList.remove('badge-pop');
                }, 1000);

                // Afficher le Toast dynamique
                if (data.latestNotif) {
                    const toast = document.getElementById('live-notif-toast');
                    const icon = document.getElementById('live-notif-icon');
                    
                    toast.className = 'live-toast p-3 d-flex align-items-start gap-3';
                    icon.className = 'fs-4 mt-1';
                    
                    let type = data.latestNotif.type || 'info';
                    
                    if (type === 'success') {
                        toast.classList.add('toast-success');
                        icon.classList.add('bi', 'bi-check-circle-fill', 'text-success');
                    } else if (type === 'warning') {
                        toast.classList.add('toast-warning');
                        icon.classList.add('bi', 'bi-exclamation-triangle-fill', 'text-warning');
                    } else if (type === 'danger' || type === 'alert') {
                        toast.classList.add('toast-danger');
                        icon.classList.add('bi', 'bi-x-circle-fill', 'text-danger');
                    } else {
                        toast.classList.add('toast-info');
                        icon.classList.add('bi', 'bi-info-circle-fill', 'text-info');
                    }

                    document.getElementById('live-notif-message').innerText = data.latestNotif.message;
                    toast.classList.add('show');
                    
                    setTimeout(() => {
                        toast.classList.remove('show');
                    }, 5000);
                }
            } else if (data.unreadCount < lastNotifCount) {
                lastNotifCount = data.unreadCount;
                const badge = document.getElementById('notif-badge');
                if (data.unreadCount === 0) {
                    badge.classList.add('d-none');
                    document.title = basePageTitle;
                } else {
                    badge.innerText = data.unreadCount > 9 ? '9+' : data.unreadCount;
                    document.title = `(${data.unreadCount}) ${basePageTitle}`;
                }
            }
        })
        .catch(error => console.error('Erreur API Notifs:', error));
}

function closeLiveToast() {
    document.getElementById('live-notif-toast').classList.remove('show');
}

setInterval(checkNewNotifications, 15000);
</script>