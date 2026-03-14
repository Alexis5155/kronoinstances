<?php include __DIR__ . '/../layouts/header.php'; ?>

<style>
/* Ligne */
.notif-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid #f1f3f5;
    transition: background 0.15s;
}
.notif-row:last-child { border-bottom: none; }
.notif-row:hover { background-color: #f8f9fa; }
.notif-unread { background-color: #eff6ff; }
.notif-unread:hover { background-color: #e0eeff; }

/* Icône */
.notif-icon {
    width: 34px; height: 34px; min-width: 34px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem;
}
.icon-info    { background-color: rgba(13,202,240,0.12); color: #0dcaf0; }
.icon-success { background-color: rgba(25,135,84,0.12);  color: #198754; }
.icon-warning { background-color: rgba(255,193,7,0.12);  color: #e6a800; }
.icon-danger  { background-color: rgba(220,53,69,0.12);  color: #dc3545; }

/* Message */
.notif-message {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.notif-text {
    color: #212529;
    text-decoration: none;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
    min-width: 0;
}
.notif-time {
    font-size: 0.78rem;
    color: #adb5bd;
    white-space: nowrap;
    flex-shrink: 0;
}

/* Actions */
.notif-actions {
    display: flex;
    gap: 0.4rem;
    flex-shrink: 0;
}
</style>


<div class="container py-4">

    <!-- EN-TÊTE -->
    <div class="page-header mb-4">
        <div class="d-flex align-items-center">
            <div class="page-header-avatar bg-dark text-white shadow-sm me-3">
                <i class="bi bi-bell"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Centre de notifications</h2>
                <p class="text-muted small mb-0">
                    Vous avez <span class="fw-bold text-dark"><?= $unreadCount ?></span>
                    notification<?= $unreadCount > 1 ? 's' : '' ?> non lue<?= $unreadCount > 1 ? 's' : '' ?>.
                </p>
            </div>
        </div>
        <a href="<?= URLROOT ?>/notifications/markAllRead" class="btn btn-light border fw-bold rounded-pill px-4">
            <i class="bi bi-check2-all me-2"></i>Tout marquer comme lu
        </a>
    </div>

    <!-- LISTE DES NOTIFICATIONS -->
    <div class="card-section card overflow-hidden mb-4">
        <?php if (empty($notifications)): ?>
            <div class="empty-state py-4">
                <div class="empty-state-icon"><i class="bi bi-bell-slash"></i></div>
                <h6>Aucune notification</h6>
                <p>Votre centre de notifications est vide.</p>
            </div>
        <?php else: foreach ($notifications as $notif):
            $isUnread = !$notif['is_read'];

            $diff = time() - strtotime($notif['created_at']);
            if ($diff < 60)        $timeStr = "À l'instant";
            elseif ($diff < 3600)  $timeStr = "Il y a " . round($diff / 60) . " min";
            elseif ($diff < 86400) $timeStr = "Il y a " . round($diff / 3600) . "h";
            else                   $timeStr = date('d/m/Y', strtotime($notif['created_at']));

            $icon = 'bi-info-circle-fill'; $iconClass = 'icon-info';
            if ($notif['type'] === 'warning')                    { $icon = 'bi-exclamation-triangle-fill'; $iconClass = 'icon-warning'; }
            elseif (in_array($notif['type'], ['danger','alert'])) { $icon = 'bi-x-circle-fill';            $iconClass = 'icon-danger'; }
            elseif ($notif['type'] === 'success')                 { $icon = 'bi-check-circle-fill';        $iconClass = 'icon-success'; }

            $urlActe = !empty($notif['link']) ? URLROOT . "/notifications/read/" . $notif['id'] : null;
        ?>
        <div class="notif-row <?= $isUnread ? 'notif-unread' : '' ?>" id="notif-<?= $notif['id'] ?>">

            <!-- Icône -->
            <div class="notif-icon <?= $iconClass ?>">
                <i class="bi <?= $icon ?>"></i>
            </div>

            <!-- Message -->
            <div class="notif-message">
                <?php if ($urlActe): ?>
                    <a href="<?= $urlActe ?>" class="notif-text <?= $isUnread ? 'fw-bold' : 'opacity-75' ?>">
                        <?= htmlspecialchars($notif['message']) ?>
                    </a>
                <?php else: ?>
                    <span class="notif-text <?= $isUnread ? 'fw-bold' : 'opacity-75' ?>">
                        <?= htmlspecialchars($notif['message']) ?>
                    </span>
                <?php endif; ?>
                <span class="notif-time"><i class="bi bi-clock me-1"></i><?= $timeStr ?></span>
            </div>

            <!-- Actions -->
            <div class="notif-actions">
                <a href="<?= URLROOT ?>/notifications/<?= $isUnread ? 'check' : 'unread' ?>/<?= $notif['id'] ?>"
                class="btn btn-sm btn-light border rounded-pill px-3"
                title="<?= $isUnread ? 'Marquer comme lu' : 'Marquer comme non lu' ?>">
                    <i class="bi bi-<?= $isUnread ? 'envelope-open' : 'envelope' ?>"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-pill px-3"
                        onclick="animateDelete(<?= $notif['id'] ?>)" title="Supprimer">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>

        </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- PAGINATION -->
    <?php if (isset($total_pages) && $total_pages > 1):
        $start  = max(1, $page - 4);
        $end    = min($total_pages, $start + 9);
        if ($end - $start < 9) $start = max(1, $end - 9);
        $isFirst = ($page <= 1);
        $isLast  = ($page >= $total_pages);
    ?>
    <nav class="pb-2">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $isFirst ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $isFirst ? '#' : URLROOT.'/notifications?page=1' ?>">
                    <i class="bi bi-chevron-double-left"></i>
                </a>
            </li>
            <li class="page-item <?= $isFirst ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $isFirst ? '#' : URLROOT.'/notifications?page='.($page-1) ?>">Précédent</a>
            </li>
            <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                <a class="page-link" href="<?= URLROOT ?>/notifications?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $isLast ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $isLast ? '#' : URLROOT.'/notifications?page='.($page+1) ?>">Suivant</a>
            </li>
            <li class="page-item <?= $isLast ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $isLast ? '#' : URLROOT.'/notifications?page='.$total_pages ?>">
                    <i class="bi bi-chevron-double-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<script>
function animateDelete(id) {
    const row = document.getElementById('notif-' + id);
    if (row) {
        row.style.transition = '0.35s ease';
        row.style.transform  = 'translateX(100%)';
        row.style.opacity    = '0';
        setTimeout(() => { window.location.href = "<?= URLROOT ?>/notifications/delete/" + id; }, 380);
    }
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
