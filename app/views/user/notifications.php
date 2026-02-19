<?php include 'app/views/header.php'; ?>

<style>
    /* Structure de la ligne */
    .notif-item { 
        display: flex;
        align-items: center;
        padding: 0 !important;
        border-left: 3px solid transparent;
        border-bottom: 1px solid #edf2f7; /* Séparateur discret restauré */
        transition: background 0.2s;
        min-width: 0;
    }
    .notif-unread { background-color: #f0faff; border-left-color: #0dcaf0; }
    
    /* Colonne 91% : Zone vers l'acte */
    .notif-main-link {
        flex: 0 0 91%;
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        text-decoration: none !important;
        color: inherit;
        min-width: 0;
        cursor: pointer; /* Par défaut pour les liens */
    }
    
    /* CURSEUR : Uniquement par défaut si pas de lien réel */
    .notif-main-link[href="#"] { 
        cursor: default; 
    }

    .notif-main-link:hover { background-color: rgba(0,0,0,0.02); }
    .notif-unread.notif-main-link:hover { background-color: #e6f7ff; }

    /* Colonne 9% : Actions isolées */
    .notif-actions {
        flex: 0 0 9%;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.3rem;
        min-width: 80px;
    }

    /* Texte Responsive (Truncate) */
    .notif-body {
        flex-grow: 1;
        min-width: 0;
    }
    .notif-title {
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis; /* Coupe proprement avec "..." */
        font-size: 0.9rem;
    }

    .notif-icon-box { width: 30px; flex-shrink: 0; text-align: center; }
    .btn-notif-action { 
        width: 32px; height: 32px; display: inline-flex; 
        align-items: center; justify-content: center; 
        border-radius: 50%; border: none; background: transparent; color: #adb5bd;
    }
    .btn-notif-action:hover { background-color: rgba(0,0,0,0.05); color: #212529; }
    .btn-delete:hover { color: #dc3545 !important; background-color: rgba(220, 53, 69, 0.1); }

    .slide-out { transform: translateX(-100%); opacity: 0; transition: 0.4s; }

    /* Pagination harmonisée */
    .pagination .page-link { padding: 0.4rem 0.8rem !important; font-size: 0.9rem !important; color: #212529 !important; font-weight: 700 !important; border-color: #dee2e6 !important; }
    .pagination .page-item.active .page-link { background-color: #212529 !important; border-color: #212529 !important; color: #fff !important; }
    .pagination .page-item.disabled .page-link { color: #ced4da !important; cursor: not-allowed; }
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
        <div>
            <h2 class="fw-bold mb-0">Notifications 🔔</h2>
            <p class="text-muted small mb-0">Historique de vos alertes</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= URLROOT ?>/notifications/markAllRead" class="btn btn-dark btn-sm fw-bold px-3 shadow-sm">Tout marquer comme lu</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mx-2 overflow-hidden">
        <div class="list-group list-group-flush" id="notif-container">
            <?php if(empty($notifications)): ?>
                <div class="text-center py-5 text-muted italic">Aucune notification.</div>
            <?php else: foreach($notifications as $notif): 
                $isUnread = !$notif['is_read'];
                
                $diff = time() - strtotime($notif['created_at']);
                if ($diff < 60) $timeStr = "1m";
                elseif ($diff < 3600) $timeStr = round($diff / 60) . "m";
                elseif ($diff < 86400) $timeStr = round($diff / 3600) . "h";
                else $timeStr = date('d/m', strtotime($notif['created_at']));

                $icon = 'bi-info-circle'; $color = 'text-primary';
                if($notif['type'] == 'warning') { $icon = 'bi-exclamation-triangle'; $color = 'text-warning'; }
                elseif($notif['type'] == 'danger') { $icon = 'bi-x-circle'; $color = 'text-danger'; }
                elseif($notif['type'] == 'success') { $icon = 'bi-check-circle'; $color = 'text-success'; }

                $urlActe = !empty($notif['link']) ? URLROOT . "/notifications/read/" . $notif['id'] : "#";
            ?>
                <div class="list-group-item notif-item <?= $isUnread ? 'notif-unread' : '' ?>" id="notif-<?= $notif['id'] ?>">
                    
                    <a href="<?= $urlActe ?>" class="notif-main-link">
                        <div class="notif-icon-box me-2">
                            <i class="bi <?= $icon ?> <?= $color ?> fs-5"></i>
                        </div>
                        <div class="notif-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fw-bold text-dark notif-title pe-2"><?= htmlspecialchars($notif['message']) ?></span>
                                <span class="x-small text-muted opacity-75 fw-normal"><?= $timeStr ?></span>
                            </div>
                        </div>
                    </a>

                    <div class="notif-actions">
                        <a href="<?= URLROOT ?>/notifications/<?= $isUnread ? 'check' : 'unread' ?>/<?= $notif['id'] ?>" 
                           class="btn-notif-action" title="Statut">
                            <i class="bi bi-<?= $isUnread ? 'envelope-open' : 'envelope' ?>"></i>
                        </a>
                        <button type="button" class="btn-notif-action btn-delete" 
                                onclick="animateDelete(<?= $notif['id'] ?>)" title="Supprimer">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>

                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <?php if (isset($total_pages) && $total_pages > 1): ?>
    <nav class="mt-4">
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

<?php include 'app/views/footer.php'; ?>