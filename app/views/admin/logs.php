<?php include __DIR__ . '/../header.php'; ?>

<main class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3 px-2">
        <div>
            <a href="<?= URLROOT ?>/admin" class="text-decoration-none small text-primary fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Administration
            </a>
            <h2 class="fw-bold mt-2 mb-0">Journal d'Audit 🕵️</h2>
            <p class="text-muted mb-0 small">Traçabilité des actions et historique des modifications.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-dark px-3 py-2 shadow-sm fw-bold">
                <?= number_format($totalLogs, 0, ',', ' ') ?> événements
            </span>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 mx-2">
        <div class="card-body p-3">
            <form method="GET" action="<?= URLROOT ?>/admin/logs" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted fw-bold small mb-1" style="font-size: 0.6rem; letter-spacing: 0.5px;">Recherche</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Mot-clé...">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted fw-bold small mb-1" style="font-size: 0.6rem; letter-spacing: 0.5px;">Agent</label>
                    <select name="f_agent" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <?php foreach($agents as $ag): ?>
                            <option value="<?= $ag['id'] ?>" <?= ($filters['agent'] ?? '') == $ag['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ag['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted fw-bold small mb-1" style="font-size: 0.6rem; letter-spacing: 0.5px;">Action</label>
                    <select name="f_action" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <?php foreach($actions_dispo as $act): ?>
                            <option value="<?= $act ?>" <?= ($filters['action'] ?? '') == $act ? 'selected' : '' ?>><?= $act ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted fw-bold small mb-1" style="font-size: 0.6rem; letter-spacing: 0.5px;">Du</label>
                    <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= $filters['date_debut'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-uppercase text-muted fw-bold small mb-1" style="font-size: 0.6rem; letter-spacing: 0.5px;">Au</label>
                    <input type="date" name="date_fin" class="form-control form-control-sm" value="<?= $filters['date_fin'] ?? '' ?>">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold">Filtrer</button>
                    <a href="<?= URLROOT ?>/admin/logs" class="btn btn-sm btn-outline-secondary" title="Réinitialiser"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 overflow-hidden mx-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="table-layout: fixed; width: 100%; min-width: 900px;">
                <thead class="table-light">
                    <tr class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                        <th style="width: 45px;"></th> 
                        <th style="width: 165px;" class="ps-2">Horodatage</th>
                        <th style="width: 140px;">Agent</th>
                        <th style="width: 180px;">Action</th>
                        <th style="width: auto;">Détails</th> 
                        <th style="width: 130px;" class="text-end pe-3">IP</th>
                        <th style="width: 50px;"></th> 
                    </tr>
                </thead>
                <tbody class="small">
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted italic">Aucun événement trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): 
                            $old = json_decode($log['old_value'] ?? '', true);
                            $new = json_decode($log['new_value'] ?? '', true);
                            $hasData = (!empty($old) || !empty($new));
                            
                            $action = $log['action'];
                            $badgeClass = 'bg-dark';
                            $sensibles = ['EXPORT_COMPLET', 'EXPORT_ANNUEL', 'DELETE_USER', 'ADMIN_UPDATE', 'SUPPRESSION_ACTE'];
                            $critiques = ['RESTAURATION_BDD', 'SYSTEM_UPDATE'];

                            if (in_array($action, $critiques)) $badgeClass = 'bg-danger';
                            elseif (in_array($action, $sensibles)) $badgeClass = 'bg-warning text-dark';

                            $copyText = "[" . date('d/m/Y H:i:s', strtotime($log['created_at'])) . "] " 
                                      . ($log['username'] ?? 'Système') . " | " . $action . " : " . $log['details'];
                        ?>
                        <tr class="log-row <?= $hasData ? 'is-expandable' : '' ?>" data-id="<?= $log['id'] ?>">
                            <td class="text-center">
                                <?php if($hasData): ?>
                                    <i class="bi bi-chevron-right text-muted chevron-icon"></i>
                                <?php endif; ?>
                            </td>
                            <td class="ps-2 text-muted">
                                <?= date('d/m/Y', strtotime($log['created_at'])) ?> 
                                <span class="fw-bold text-dark ms-1"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
                            </td>
                            <td class="text-truncate">
                                <?php if ($log['username']): ?>
                                    <span class="text-primary fw-bold">
                                        <i class="bi bi-person-circle me-1 opacity-50"></i><?= htmlspecialchars($log['username']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted italic">Système</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= $badgeClass ?> fw-bold text-uppercase p-1 px-2" style="font-size: 0.6rem;"><?= $action ?></span></td>
                            <td class="text-truncate" title="<?= htmlspecialchars($log['details']) ?>">
                                <?= htmlspecialchars($log['details']) ?>
                            </td>
                            <td class="text-end text-muted font-monospace pe-3" style="font-size: 0.75rem;"><?= $log['ip_address'] ?></td>
                            <td class="text-center">
                                <button class="btn btn-link btn-sm p-0 text-secondary border-0 btn-copy" 
                                        data-copy="<?= htmlspecialchars($copyText) ?>" 
                                        title="Copier">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </td>
                        </tr>

                        <?php if($hasData): ?>
                        <tr class="details-row" id="row-details-<?= $log['id'] ?>" style="display: none;">
                            <td colspan="7" class="p-0 border-0">
                                <div class="collapse-content" style="height: 0; overflow: hidden; transition: height 0.2s ease-out;">
                                    <div class="p-3 m-3 bg-white border rounded shadow-sm">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold m-0 text-dark"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Comparatif technique</h6>
                                            <span class="badge bg-light text-muted border fw-normal" style="font-size: 0.65rem;">Cible #<?= $log['target_id'] ?> (<?= $log['target_type'] ?>)</span>
                                        </div>
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-light text-uppercase text-muted" style="font-size: 0.6rem;">
                                                <tr>
                                                    <th style="width: 20%;">Champ</th>
                                                    <th style="width: 40%;">Avant</th>
                                                    <th style="width: 40%;">Après</th>
                                                </tr>
                                            </thead>
                                            <tbody style="font-size: 0.75rem;">
                                                <?php 
                                                $keys = array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? [])));
                                                foreach($keys as $key): 
                                                    if(in_array($key, ['id', 'created_at', 'updated_at', 'password', 'power', 'user_id', 'service_id', 'role_id'])) continue;
                                                    $valOld = $old[$key] ?? null;
                                                    $valNew = $new[$key] ?? null;
                                                    $diff = ($valOld !== $valNew);
                                                ?>
                                                <tr class="<?= $diff ? 'table-warning' : '' ?>">
                                                    <td class="fw-bold text-muted ps-2"><?= $key ?></td>
                                                    <td class="text-break"><?= is_array($valOld) ? json_encode($valOld, JSON_UNESCAPED_UNICODE) : ($valOld ?? '-') ?></td>
                                                    <td class="text-break <?= $diff ? 'fw-bold text-success' : '' ?>">
                                                        <?= is_array($valNew) ? json_encode($valNew, JSON_UNESCAPED_UNICODE) : ($valNew ?? '-') ?>
                                                        <?php if($diff): ?> <i class="bi bi-arrow-left ms-1"></i> <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination pagination-sm justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): $query_params['page'] = $i; ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link shadow-none fw-bold" href="<?= URLROOT ?>/admin/logs?<?= http_build_query($query_params) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</main>

<style>
    .italic { font-style: italic; }
    .is-expandable { cursor: pointer; }
    .log-row:hover { background-color: rgba(0,0,0,0.01); }
    
    /* STYLE PAGINATION IDENTIQUE REGISTRE */
    .pagination .page-link { 
        padding: 0.4rem 0.8rem !important; 
        font-size: 0.9rem !important; 
        color: #212529 !important;
        border-color: #dee2e6 !important;
    }
    .pagination .page-item.active .page-link { 
        background-color: #212529 !important; 
        border-color: #212529 !important; 
        color: #fff !important; 
    }

    /* Animation chevron */
    .log-row.open .chevron-icon { transform: rotate(90deg); color: #0d6efd !important; }
    .chevron-icon { transition: transform 0.2s; }
    .btn-copy:hover { color: #0d6efd !important; transform: scale(1.15); transition: 0.1s; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. CLIC LIGNE (EXPANSION)
    document.querySelectorAll('.log-row.is-expandable').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.btn-copy')) return;
            const logId = this.getAttribute('data-id');
            const detailsRow = document.getElementById('row-details-' + logId);
            const content = detailsRow.querySelector('.collapse-content');

            if (this.classList.contains('open')) {
                this.classList.remove('open');
                content.style.height = '0';
                setTimeout(() => { detailsRow.style.display = 'none'; }, 200);
            } else {
                detailsRow.style.display = 'table-row';
                this.classList.add('open');
                setTimeout(() => { content.style.height = content.scrollHeight + 'px'; }, 10);
            }
        });
    });

    // 2. COPIER
    document.querySelectorAll('.btn-copy').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const text = this.getAttribute('data-copy');
            const icon = this.querySelector('i');
            navigator.clipboard.writeText(text).then(() => {
                icon.classList.replace('bi-clipboard', 'bi-check-lg');
                icon.classList.add('text-success');
                setTimeout(() => {
                    icon.classList.replace('bi-check-lg', 'bi-clipboard');
                    icon.classList.remove('text-success');
                }, 1500);
            });
        });
    });
});
</script>

<?php include __DIR__ . '/../footer.php'; ?>