<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'pdf': return ['class' => 'bi-filetype-pdf', 'color' => 'text-danger'];
        case 'doc': case 'docx': case 'odt': return ['class' => 'bi-filetype-doc', 'color' => 'text-primary'];
        case 'xls': case 'xlsx': case 'ods': return ['class' => 'bi-filetype-xls', 'color' => 'text-success'];
        case 'ppt': case 'pptx': case 'odp': return ['class' => 'bi-filetype-ppt', 'color' => 'text-warning'];
        case 'zip': case 'rar': case '7z': case 'tar': return ['class' => 'bi-file-zip', 'color' => 'text-secondary'];
        case 'jpg': case 'jpeg': case 'png': return ['class' => 'bi-file-image', 'color' => 'text-info'];
        default: return ['class' => 'bi-file-earmark', 'color' => 'text-secondary'];
    }
}

$statut = $seance['statut'];
$showDate       = $statut !== 'brouillon';
$showOdj        = in_array($statut, ['odj_valide', 'dossier_disponible', 'en_cours', 'pv', 'terminee']);
$showDocs       = in_array($statut, ['dossier_disponible', 'en_cours', 'pv', 'terminee']);
$isLive         = $statut === 'en_cours';
$isPv           = $statut === 'finalisation';
$isTerminee     = $statut === 'terminee';
$showConvoc     = isset($seance['convocations_envoyees']) && $seance['convocations_envoyees'] == 1;
?>

<style>
.group-hover:hover { background-color: #f8f9fa !important; }
.z-index-2 { z-index: 2; position: relative; }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f8f9fa; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #ced4da; }
.rich-text-container { font-size: 0.95rem; color: #212529; }
.rich-text-container p, .rich-text-container ul, .rich-text-container ol { margin-bottom: 0.8rem; }
.rich-text-container p:last-child, .rich-text-container ul:last-child { margin-bottom: 0; }
.rich-text-container a { color: #0d6efd; }
.point-item { transition: all 0.2s ease; border: 1px solid rgba(0,0,0,0.05) !important; }
.point-item:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.05) !important; }
/* Fix espace entre badge type et flèche de l'accordéon */
.accordion-button::after { margin-left: 1rem; flex-shrink: 0; }
</style>

<div class="container py-4">

    <!-- ===== EN-TÊTE ===== -->
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="row align-items-center g-4">
                <div class="col-md-8">
                    <h3 class="fw-bold mb-2 d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-3 d-inline-flex">
                            <i class="bi bi-building"></i>
                        </div>
                        <?= htmlspecialchars($seance['instance_nom']) ?>
                    </h3>
                    <?php if ($showDate): ?>
                    <div class="d-flex flex-wrap gap-4 text-muted mt-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-event fs-5 me-2 text-primary opacity-75"></i>
                            <span class="fw-medium text-dark"><?= date('d/m/Y', strtotime($seance['date_seance'])) ?></span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-clock fs-5 me-2 text-primary opacity-75"></i>
                            <span class="fw-medium text-dark"><?= date('H:i', strtotime($seance['heure_debut'])) ?></span>
                        </div>
                        <?php if (!empty($seance['lieu'])): ?>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-geo-alt fs-5 me-2 text-primary opacity-75"></i>
                                <span class="fw-medium text-dark"><?= htmlspecialchars($seance['lieu']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($hasAdminAccess ?? false): ?>
                <div class="col-md-4 text-md-end">
                    <a href="<?= URLROOT ?>/seances/edit/<?= $seance['id'] ?>" class="btn btn-primary fw-bold shadow-sm rounded-pill px-4">
                        <i class="bi bi-gear-fill me-2"></i>Gérer la séance
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- ===== COLONNE GAUCHE : ORDRE DU JOUR ===== -->
        <div class="col-lg-8">

            <!-- ===== BANNIÈRE LIVE ===== -->
            <?php if ($isLive): ?>
                <div class="alert alert-danger border-danger shadow-sm mb-4 rounded-4 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <span class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                            <i class="bi bi-broadcast"></i>
                        </span>
                        <div>
                            <div class="fw-bold text-danger">La séance est en cours</div>
                            <div class="small text-muted">Cette séance se déroule actuellement.</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ===== BANNIÈRE PV EN COURS ===== -->
            <?php if ($isPv): ?>
                <div class="alert alert-secondary border-0 shadow-sm mb-4 rounded-4 d-flex align-items-center gap-3">
                    <span class="bg-secondary bg-opacity-25 text-secondary rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;">
                        <i class="bi bi-hourglass-split"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark">La séance est terminée</div>
                        <div class="small text-muted">Le compte-rendu et les résultats des votes sont en cours de préparation. Ils seront disponibles ici dès leur validation.</div>
                    </div>
                </div>
            <?php endif; ?>


            <!-- PHASE 0 : BROUILLON -->
            <?php if ($statut === 'brouillon'): ?>
                <div class="card border-0 shadow-sm rounded-4 text-center p-5 bg-white">
                    <div class="card-body">
                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;">
                            <i class="bi bi-pencil-square" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Séance en cours de programmation</h5>
                        <p class="text-muted mb-0 mx-auto" style="max-width:400px;">La date, le lieu et l'ordre du jour n'ont pas encore été arrêtés.</p>
                    </div>
                </div>

            <!-- PHASE 1 : DATE FIXÉE -->
            <?php elseif ($statut === 'date_fixee'): ?>
                <div class="card border-0 shadow-sm rounded-4 text-center p-5 bg-white">
                    <div class="card-body">
                        <div class="bg-info bg-opacity-10 text-info rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;">
                            <i class="bi bi-list-task" style="font-size: 2rem;"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Ordre du jour en cours d'élaboration</h5>
                        <p class="text-muted mb-0 mx-auto" style="max-width:400px;">La date de cette séance est fixée, mais l'ordre du jour officiel n'a pas encore été arrêté.</p>
                    </div>
                </div>

            <!-- PHASES ODJ / DOSSIER / LIVE / PV / TERMINÉE -->
            <?php else: ?>

                <!-- En-tête de section ODJ -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0 text-dark d-flex align-items-center" style="letter-spacing:-0.5px;">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm me-3" style="width:45px;height:45px;font-size:1.2rem;">
                            <i class="bi bi-list-ol"></i>
                        </div>
                        Ordre du jour
                    </h4>
                    <?php if (!$showDocs && !$isPv): ?>
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2 rounded-pill">
                            <i class="bi bi-hourglass-split me-1"></i>Dossiers en préparation
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Liste des points -->
                <div class="accordion d-flex flex-column gap-3" id="accordionODJ">
                    <?php foreach ($points as $i => $pt):
                        $docsPoint = array_filter($documents, fn($d) => $d['point_odj_id'] == $pt['id']);
                        $isRetire  = ($pt['retire'] ?? 0) == 1;
                        $typeConfig = [
                            'information' => ['cls' => 'bg-info bg-opacity-10 text-info border-info', 'icon' => 'bi-info-circle'],
                            'deliberation' => ['cls' => 'bg-primary bg-opacity-10 text-primary border-primary', 'icon' => 'bi-chat-left-text'],
                            'vote'         => ['cls' => 'bg-danger bg-opacity-10 text-danger border-danger', 'icon' => 'bi-box-arrow-in-right'],
                            'divers'       => ['cls' => 'bg-dark bg-opacity-10 text-dark border-dark', 'icon' => 'bi-three-dots'],
                        ];
                        $type = strtolower($pt['type_point'] ?? 'information');
                        $cfg  = $typeConfig[$type] ?? $typeConfig['information'];
                    ?>
                    <div class="accordion-item rounded-4 shadow-sm border-0 point-item <?= $isRetire ? 'opacity-75 bg-light' : 'bg-white' ?>">

                        <h2 class="accordion-header m-0" id="heading_<?= $pt['id'] ?>">
                            <button class="accordion-button collapsed shadow-none bg-transparent px-4 py-4 border-0 text-dark w-100" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#col_<?= $pt['id'] ?>"
                                    aria-expanded="false" aria-controls="col_<?= $pt['id'] ?>">

                                <!-- Numéro -->
                                <span class="bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0 me-3 <?= $isRetire ? 'opacity-50' : '' ?>" style="width:28px;height:28px;font-size:0.78rem;">
                                    <?= $i + 1 ?>
                                </span>

                                <!-- Titre -->
                                <span class="fw-bold flex-grow-1 text-truncate <?= $isRetire ? 'text-decoration-line-through text-muted' : '' ?>" style="font-size:1rem;">
                                    <?= htmlspecialchars($pt['titre']) ?>
                                </span>

                                <!-- Badges (me-2 pour espacer de la flèche) -->
                                <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-3 me-2">
                                    <?php if ($showDocs && count($docsPoint) > 0): ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-paperclip me-1"></i><?= count($docsPoint) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($isRetire): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">
                                            <i class="bi bi-slash-circle me-1"></i>Retiré
                                        </span>
                                    <?php else: ?>
                                        <span class="badge <?= $cfg['cls'] ?> border border-opacity-25 px-2 py-1">
                                            <i class="bi <?= $cfg['icon'] ?> me-1"></i><?= ucfirst($type) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                            </button>
                        </h2>

                        <!-- Corps déroulant -->
                        <div id="col_<?= $pt['id'] ?>" class="accordion-collapse collapse" aria-labelledby="heading_<?= $pt['id'] ?>" data-bs-parent="#accordionODJ">
                            <div class="accordion-body border-top p-4 bg-white rounded-bottom-4">

                                <?php if (!$showDocs): ?>
                                    <!-- Contenu verrouillé -->
                                    <div class="text-center py-4 bg-light rounded-3">
                                        <i class="bi bi-lock text-muted fs-3 opacity-50 d-block mb-2"></i>
                                        <div class="small text-muted fw-medium">L'exposé des motifs et les pièces jointes seront accessibles prochainement.</div>
                                    </div>

                                <?php else: ?>
                                    <!-- Exposé des motifs -->
                                    <div class="mb-4">
                                        <label class="form-label small fw-bold text-muted text-uppercase d-flex align-items-center gap-2 mb-3" style="letter-spacing:0.5px;">
                                            <i class="bi bi-text-paragraph text-primary"></i> Exposé des motifs
                                        </label>
                                        <div class="bg-light px-4 py-3 rounded-3 text-dark rich-text-container border border-light">
                                            <?= !empty($pt['description']) ? $pt['description'] : '<em class="text-muted">Aucun exposé des motifs rédigé pour ce point.</em>' ?>
                                        </div>
                                    </div>

                                    <!-- Votes (seulement en "terminée") -->
                                    <?php if ($isTerminee): ?>
                                        <div class="mt-4 pt-3 border-top mb-4">
                                            <label class="form-label small fw-bold text-muted text-uppercase d-flex align-items-center gap-2 mb-3" style="letter-spacing:0.5px;">
                                                <i class="bi bi-check2-square text-success"></i> Résultat du vote
                                            </label>
                                            <!-- PLACEHOLDER — À implémenter -->
                                            <div class="text-center p-4 bg-light rounded-4 text-muted small border border-dashed">
                                                <i class="bi bi-bar-chart fs-3 d-block mb-1 opacity-25"></i>
                                                Les résultats des votes seront affichés ici.
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Documents annexes -->
                                    <div class="mt-4 pt-3 border-top">
                                        <label class="form-label small fw-bold text-muted text-uppercase d-flex align-items-center gap-2 mb-3" style="letter-spacing:0.5px;">
                                            <i class="bi bi-paperclip text-info"></i> Documents annexés
                                        </label>
                                        <?php if (!empty($docsPoint)): ?>
                                            <div class="row g-3">
                                                <?php foreach($docsPoint as $doc):
                                                    $icon = getFileIcon($doc['chemin_fichier']);
                                                ?>
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center bg-light bg-opacity-50 p-2 rounded-3 border border-light shadow-sm position-relative group-hover h-100">
                                                            <div class="bg-white p-2 rounded-3 shadow-sm me-3">
                                                                <i class="bi <?= $icon['class'] ?> <?= $icon['color'] ?> fs-4"></i>
                                                            </div>
                                                            <div class="flex-grow-1 text-truncate me-2">
                                                                <a href="<?= URLROOT ?>/<?= $doc['chemin_fichier'] ?>" target="_blank" class="text-decoration-none fw-bold text-dark stretched-link text-truncate d-block" style="font-size:0.85rem;" title="<?= htmlspecialchars($doc['nom']) ?>">
                                                                    <?= htmlspecialchars($doc['nom']) ?>
                                                                </a>
                                                                <div class="small text-muted text-uppercase fw-medium" style="font-size:0.65rem;letter-spacing:0.5px;">
                                                                    <?= strtoupper(pathinfo($doc['chemin_fichier'], PATHINFO_EXTENSION)) ?>
                                                                </div>
                                                            </div>
                                                            <i class="bi bi-download text-primary opacity-50 pe-2"></i>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center p-4 bg-light rounded-4 text-muted small border border-dashed">
                                                <i class="bi bi-inbox fs-3 d-block mb-1 opacity-25"></i>
                                                Aucun document rattaché.
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- PLACEHOLDER PV (terminée) -->
                <?php if ($isTerminee): ?>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mt-4">
                        <div class="card-header bg-white py-3 border-0 border-bottom">
                            <h6 class="mb-0 fw-bold d-flex align-items-center">
                                <div class="bg-dark bg-opacity-10 text-dark p-2 rounded-3 me-2 d-inline-flex">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                                Procès-verbal
                            </h6>
                        </div>
                        <div class="card-body text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-text fs-1 d-block mb-3 opacity-25"></i>
                            <div class="small fw-medium">Le procès-verbal sera disponible ici une fois publié.</div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>

        <!-- ===== COLONNE DROITE : CONVOCATION + MEMBRES ===== -->
        <div class="col-lg-4">

            <!-- ENCART CONVOCATION (uniquement si envoyée) -->
            <?php if ($showConvoc): ?>
                <?php
                $convocationDoc = null;
                foreach ($documents ?? [] as $d) {
                    if ($d['type_doc'] === 'convocation') { $convocationDoc = $d; break; }
                }
                ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="card-header bg-white py-3 border-0 border-bottom">
                        <h6 class="mb-0 fw-bold d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 text-success p-2 rounded-3 me-2 d-inline-flex">
                                <i class="bi bi-envelope-check"></i>
                            </div>
                            Convocation officielle
                        </h6>
                    </div>
                    <div class="card-body p-4 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-3" style="width:60px;height:60px;">
                            <i class="bi bi-check2-all fs-2"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Envoyée aux membres</h6>
                        <p class="small text-muted mb-3">La convocation a été transmise à tous les membres convoqués.</p>
                        <?php if ($convocationDoc): ?>
                            <a href="<?= URLROOT ?>/<?= htmlspecialchars($convocationDoc['chemin_fichier']) ?>" target="_blank" class="btn btn-outline-success fw-bold rounded-pill w-100">
                                <i class="bi bi-download me-2"></i>Télécharger le PDF
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- MEMBRES CONVOQUÉS -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-0 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold d-flex align-items-center">
                        <div class="bg-secondary bg-opacity-10 text-secondary p-2 rounded-3 me-2 d-inline-flex">
                            <i class="bi bi-people"></i>
                        </div>
                        Membres
                    </h6>
                    <span class="badge bg-light text-dark border rounded-pill px-2 py-1"><?= count($membres) ?></span>
                </div>
                <div class="card-body p-0 custom-scrollbar" style="max-height:500px;overflow-y:auto;">
                    <?php if (empty($membres)): ?>
                        <p class="text-muted small text-center py-4 m-0">Aucun membre rattaché.</p>
                    <?php else: ?>
                        <?php
                        $admins    = array_filter($membres, fn($m) => $m['college'] === 'administration');
                        $personnel = array_filter($membres, fn($m) => $m['college'] === 'personnel');
                        ?>
                        <?php if (!empty($admins)): ?>
                            <div class="bg-light px-4 py-2 border-bottom">
                                <span class="small fw-bold text-muted text-uppercase" style="letter-spacing:0.5px;font-size:0.7rem;">Collège Administration</span>
                            </div>
                            <ul class="list-group list-group-flush mb-0">
                                <?php foreach ($admins as $m): ?>
                                    <li class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center border-bottom">
                                        <div class="d-flex align-items-center text-truncate pe-2">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:32px;height:32px;">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                            <span class="fw-medium text-dark text-truncate" style="font-size:0.9rem;">
                                                <?= htmlspecialchars(strtoupper($m['nom']) . ' ' . $m['prenom']) ?>
                                            </span>
                                        </div>
                                        <span class="badge <?= $m['type_mandat'] === 'titulaire' ? 'bg-dark' : 'bg-secondary' ?> bg-opacity-75 rounded-pill fw-normal flex-shrink-0" style="font-size:0.65rem;">
                                            <?= $m['type_mandat'] === 'titulaire' ? 'Titulaire' : 'Suppléant' ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!empty($personnel)): ?>
                            <div class="bg-light px-4 py-2 border-bottom border-top">
                                <span class="small fw-bold text-muted text-uppercase" style="letter-spacing:0.5px;font-size:0.7rem;">Collège Personnel</span>
                            </div>
                            <ul class="list-group list-group-flush mb-0">
                                <?php foreach ($personnel as $m): ?>
                                    <li class="list-group-item px-4 py-3 d-flex justify-content-between align-items-center border-bottom">
                                        <div class="d-flex align-items-center text-truncate pe-2">
                                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:32px;height:32px;">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                            <span class="fw-medium text-dark text-truncate" style="font-size:0.9rem;">
                                                <?= htmlspecialchars(strtoupper($m['nom']) . ' ' . $m['prenom']) ?>
                                            </span>
                                        </div>
                                        <span class="badge <?= $m['type_mandat'] === 'titulaire' ? 'bg-dark' : 'bg-secondary' ?> bg-opacity-75 rounded-pill fw-normal flex-shrink-0" style="font-size:0.65rem;">
                                            <?= $m['type_mandat'] === 'titulaire' ? 'Titulaire' : 'Suppléant' ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>