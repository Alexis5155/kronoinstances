<?php include __DIR__ . '/../layouts/header.php'; ?>

<style>
    /* Structure de la ligne */
    .notif-item { 
        display: flex;
        align-items: center;
        padding: 0 !important;
        border: none !important;
        border-bottom: 1px solid #f1f3f5 !important;
        transition: all 0.2s ease-in-out;
        min-width: 0;
        background: transparent;
    }
    
    .notif-item:last-child {
        border-bottom: none !important;
    }

    /* Différenciation des notifications non lues */
    .notif-unread { 
        background-color: #f8f9fa; 
    }
    .notif-unread-marker {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #0dcaf0;
        margin-right: 12px;
        box-shadow: 0 0 8px rgba(13, 202, 240, 0.4);
    }
    .notif-read-marker {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: transparent;
        margin-right: 12px;
    }
    
    /* Colonne 91% : Zone vers l'acte */
    .notif-main-link {
        flex: 1;
        display: flex;
        align-items: center;
        padding: 1rem 1.25rem;
        text-decoration: none !important;
        color: inherit;
        min-width: 0;
        cursor: pointer;
    }
    
    .notif-main-link[href="#"] { 
        cursor: default; 
    }

    .notif-main-link:hover { 
        background-color: rgba(13, 202, 240, 0.05); 
        transform: translateX(4px);
    }

    /* Icône selon le type */
    .icon-box {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    
    /* Couleurs des icônes */
    .icon-info { background-color: rgba(13, 202, 240, 0.1); color: #0dcaf0; }
    .icon-success { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
    .icon-warning { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }
    .icon-danger { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }

    /* Texte Responsive (Truncate) */
    .notif-body {
        flex-grow: 1;
        min-width: 0;
    }
    
    .notif-title {
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis; 
        font-size: 0.95rem;
        letter-spacing: -0.2px;
    }

    /* Actions isolées */
    .notif-actions {
        padding-right: 1.25rem;
        display: flex;
        gap: 0.5rem;
    }

    .btn-notif-action { 
        width: 36px; 
        height: 36px; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: 8px; 
        border: none; 
        background: transparent; 
        color: #adb5bd;
        transition: all 0.2s;
    }
    .btn-notif-action:hover { background-color: #f8f9fa; color: #212529; }
    .btn-delete:hover { color: #dc3545 !important; background-color: rgba(220, 53, 69, 0.1); }

    .slide-out { transform: translateX(100%); opacity: 0; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }

    /* Pagination harmonisée */
    .pagination .page-link { padding: 0.5rem 1rem !important; font-size: 0.9rem !important; color: #212529 !important; font-weight: 600 !important; border: none; border-radius: 8px; margin: 0 2px; }
    .pagination .page-link:hover { background-color: #f8f9fa; color: #0dcaf0 !important; }
    .pagination .page-item.active .page-link { background-color: #0dcaf0 !important; color: #fff !important; box-shadow: 0 4px 10px rgba(13,202,240,0.3); }
    .pagination .page-item.disabled .page-link { color: #ced4da !important; cursor: not-allowed; background: transparent; }
</style>

<div class="container py-4">
    <!-- En-tête de page -->
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div class="d-flex align-items-center">
            <div class="avatar-circle rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold shadow-sm me-3" style="width: 50px; height: 50px; font-size: 1.5rem;">
                <i class="bi bi-bell"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Centre de notifications</h2>
                <p class="text-muted small mb-0">
                    Vous avez <span class="fw-bold text-dark"><?= $unreadCount ?></span> notification<?= $unreadCount > 1 ? 's' : '' ?> non lue<?= $unreadCount > 1 ? 's' : '' ?>.
                </p>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            <a href="<?= URLROOT ?>/notifications/markAllRead" class="btn btn-light border fw-bold shadow-sm px-4 hover-primary" style="transition: all 0.2s;">
                <i class="bi bi-check2-all me-2"></i> Tout marquer comme lu
            </a>
        </div>
    </div>

    <!-- Liste des notifications -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-5">
        <div class="list-group list-group-flush" id="notif-container">
            <?php if(empty($notifications)): ?>
                <div class="text-center py-5 text-muted bg-white">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-bell-slash fs-2 opacity-50"></i>
                    </div>
                    <h6 class="fw-bold text-dark">Aucune notification</h6>
                    <p class="small mb-0">Votre centre de notifications est vide.</p>
                </div>
            <?php else: foreach($notifications as $notif): 
                $isUnread = !$notif['is_read'];
                
                // Formatage de l'heure
                $diff = time() - strtotime($notif['created_at']);
                if ($diff < 60) $timeStr = "À l'instant";
                elseif ($diff < 3600) $timeStr = "Il y a " . round($diff / 60) . " min";
                elseif ($diff < 86400) $timeStr = "Il y a " . round($diff / 3600) . "h";
                else $timeStr = date('d/m/Y', strtotime($notif['created_at']));

                // Icônes et couleurs dynamiques
                $icon = 'bi-info-circle-fill'; 
                $iconClass = 'icon-info';
                
                if($notif['type'] == 'warning') { 
                    $icon = 'bi-exclamation-triangle-fill'; 
                    $iconClass = 'icon-warning'; 
                }
                elseif($notif['type'] == 'danger' || $notif['type'] == 'alert') { 
                    $icon = 'bi-x-circle-fill'; 
                    $iconClass = 'icon-danger'; 
                }
                elseif($notif['type'] == 'success') { 
                    $icon = 'bi-check-circle-fill'; 
                    $iconClass = 'icon-success'; 
                }

                $urlActe = !empty($notif['link']) ? URLROOT . "/notifications/read/" . $notif['id'] : "#";
            ?>
                <div class="list-group-item notif-item <?= $isUnread ? 'notif-unread' : 'bg-white' ?>" id="notif-<?= $notif['id'] ?>">
                    
                    <a href="<?= $urlActe ?>" class="notif-main-link">
                        <div class="<?= $isUnread ? 'notif-unread-marker' : 'notif-read-marker' ?>"></div>
                        
                        <div class="icon-box <?= $iconClass ?> me-3 shadow-sm">
                            <i class="bi <?= $icon ?>"></i>
                        </div>
                        
                        <div class="notif-body">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <span class="fw-bold text-dark notif-title pe-2 <?= $isUnread ? '' : 'opacity-75' ?>">
                                    <?= htmlspecialchars($notif['message']) ?>
                                </span>
                                <span class="badge bg-light text-muted border fw-normal" style="font-size: 0.75rem;">
                                    <i class="bi bi-clock me-1"></i><?= $timeStr ?>
                                </span>
                            </div>
                        </div>
                    </a>

                    <div class="notif-actions">
                        <a href="<?= URLROOT ?>/notifications/<?= $isUnread ? 'check' : 'unread' ?>/<?= $notif['id'] ?>" 
                           class="btn-notif-action shadow-sm border bg-white" title="<?= $isUnread ? 'Marquer comme lu' : 'Marquer comme non lu' ?>">
                            <i class="bi bi-<?= $isUnread ? 'envelope-open' : 'envelope' ?>"></i>
                        </a>
                        <button type="button" class="btn-notif-action btn-delete shadow-sm border bg-white" 
                                onclick="animateDelete(<?= $notif['id'] ?>)" title="Supprimer">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>

                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Pagination -->
    <?php if (isset($total_pages) && $total_pages > 1): ?>
    <nav class="mt-4 pb-4">
        <ul class="pagination justify-content-center">
            <?php 
                $start = max(1, $page - 4); $end = min($total_pages, $start + 9);
                if ($end - $start < 9) $start = max(1, $end - 9);
                $isFirst = ($page <= 1); $isLast = ($page >= $total_pages);
            ?>
            <li class="page-item <?= $isFirst ? 'disabled' : '' ?>"><a class="page-link shadow-none" href="<?= $isFirst ? '#' : URLROOT.'/notifications?page=1' ?>"><i class="bi bi-chevron-double-left"></i></a></li>
            <li class="page-item <?= $isFirst ? 'disabled' : '' ?>"><a class="page-link px-3 shadow-none" href="<?= $isFirst ? '#' : URLROOT.'/notifications?page='.($page - 1) ?>">Précédent</a></li>
            <?php for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $page == $i ? 'active' : '' ?>"><a class="page-link shadow-none" href="<?= URLROOT ?>/notifications?page=<?= $i ?>"><?= $i ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?= $isLast ? 'disabled' : '' ?>"><a class="page-link px-3 shadow-none" href="<?= $isLast ? '#' : URLROOT.'/notifications?page='.($page + 1) ?>">Suivant</a></li>
            <li class="page-item <?= $isLast ? 'disabled' : '' ?>"><a class="page-link shadow-none" href="<?= $isLast ? '#' : URLROOT.'/notifications?page='.$total_pages ?>"><i class="bi bi-chevron-double-right"></i></a></li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script>
function animateDelete(id) {
    const row = document.getElementById('notif-' + id);
    if (row) {
        row.classList.add('slide-out');
        setTimeout(() => { window.location.href = "<?= URLROOT ?>/notifications/delete/" + id; }, 400);
    }
}
</script>

<style>
    .hover-primary:hover { background-color: #212529 !important; color: white !important; }
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
