<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php
// Helper pour définir dynamiquement l'icône selon l'extension du fichier
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'pdf': return ['class' => 'bi-file-earmark-pdf-fill', 'color' => 'text-danger'];
        case 'doc': case 'docx': case 'odt': return ['class' => 'bi-file-earmark-word-fill', 'color' => 'text-primary'];
        case 'xls': case 'xlsx': case 'ods': return ['class' => 'bi-file-earmark-excel-fill', 'color' => 'text-success'];
        case 'ppt': case 'pptx': case 'odp': return ['class' => 'bi-file-earmark-ppt-fill', 'color' => 'text-warning'];
        case 'zip': case 'rar': case '7z': case 'tar': return ['class' => 'bi-file-earmark-zip-fill', 'color' => 'text-secondary'];
        default: return ['class' => 'bi-file-earmark-fill', 'color' => 'text-muted'];
    }
}
?>

<div class="container py-4">
    <!-- EN-TÊTE CONSULTATION -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-building me-2 text-primary"></i><?= htmlspecialchars($seance['instance_nom']) ?></h3>
                    <p class="mb-0 text-muted fs-5">
                        <i class="bi bi-calendar-event me-2"></i><?= date('d/m/Y', strtotime($seance['date_seance'])) ?> à <?= date('H:i', strtotime($seance['heure_debut'])) ?>
                        <?php if (!empty($seance['lieu'])): ?>
                            &nbsp;·&nbsp;<i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($seance['lieu']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <!-- BOUTON D'ACCÈS À L'ÉDITION POUR LES GESTIONNAIRES -->
                <?php if ($hasAdminAccess ?? true): ?>
                <a href="<?= URLROOT ?>/seances/edit/<?= $seance['id'] ?>" class="btn btn-outline-primary shadow-sm">
                    <i class="bi bi-gear-fill me-2"></i>Gérer la séance
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ORDRE DU JOUR ET DOCUMENTS -->
         
    <?php
    // Logique d'affichage selon le cycle de vie
    $showOdj  = in_array($seance['statut'], ['odj_valide', 'dossier_disponible', 'en_cours', 'terminee']);
    $showDocs = in_array($seance['statut'], ['dossier_disponible', 'en_cours', 'terminee']);
    ?>

    <div class="row g-4">
        <div class="col-lg-8">

            <!-- PHASE 0 : BROUILLON -->
            <?php if ($seance['statut'] === 'brouillon'): ?>
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-pencil-square fs-1 text-secondary opacity-50 d-block mb-3"></i>
                        <h5 class="fw-bold text-dark">Séance en cours de programmation</h5>
                        <p class="text-muted small mb-0">Cette séance est à l'état de brouillon. La date, le lieu et l'ordre du jour sont susceptibles d'être modifiés à tout moment.</p>
                    </div>
                </div>

            <!-- PHASE 1 : DATE FIXÉE -->
            <?php elseif ($seance['statut'] === 'date_fixee'): ?>
                <div class="card border-0 shadow-sm text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-calendar-event fs-1 text-info opacity-75 d-block mb-3"></i>
                        <h5 class="fw-bold text-dark">Ordre du jour en cours d'élaboration</h5>
                        <p class="text-muted small mb-0">La date de cette séance est fixée, mais l'ordre du jour officiel n'a pas encore été arrêté.</p>
                    </div>
                </div>

            <!-- PHASES 2 & 3 : ODJ ou DOSSIER DISPONIBLE -->
            <?php else: ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0"><i class="bi bi-list-ol me-2 text-primary"></i>Ordre du Jour</h4>
                    <?php if (!$showDocs): ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Dossiers en préparation</span>
                    <?php endif; ?>
                </div>

                <div class="accordion shadow-sm" id="accordionODJ">
                    <?php foreach ($points as $i => $pt): 
                        $docsPoint = array_filter($documents, fn($d) => $d['point_odj_id'] == $pt['id']);
                    ?>
                    <div class="accordion-item border-0 border-bottom">
                        <h2 class="accordion-header">
                            <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?> fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#col_<?= $pt['id'] ?>">
                                <?= ($i+1) . '. ' . htmlspecialchars($pt['titre']) ?>
                                <?php if($showDocs && count($docsPoint) > 0): ?>
                                    <span class="badge bg-secondary ms-3"><i class="bi bi-paperclip me-1"></i><?= count($docsPoint) ?> PJ</span>
                                <?php endif; ?>
                            </button>
                        </h2>
                        
                        <div id="col_<?= $pt['id'] ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#accordionODJ">
                            <div class="accordion-body bg-light">
                                
                                <!-- PHASE 2 : ON CACHE LE CONTENU, ON N'AFFICHE QUE LES TITRES -->
                                <?php if (!$showDocs): ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-lock text-muted fs-3 opacity-50 d-block mb-2"></i>
                                        <div class="small text-muted fst-italic">L'exposé des motifs et les pièces jointes seront rendus accessibles prochainement.</div>
                                    </div>
                                    
                                <!-- PHASE 3 : ON AFFICHE L'EXPOSÉ DES MOTIFS ET LES PJ -->
                                <?php else: ?>
                                    <div class="rich-text-container bg-white p-3 rounded border shadow-sm mb-3">
                                        <?= $pt['description'] ?: '<em class="text-muted">Aucun exposé des motifs fourni.</em>' ?>
                                    </div>
                                    
                                    <?php if (!empty($docsPoint)): ?>
                                        <div class="d-flex flex-column gap-2">
                                            <?php foreach($docsPoint as $doc): 
                                                $icon = getFileIcon($doc['chemin_fichier']);
                                            ?>
                                                <a href="<?= URLROOT ?>/<?= $doc['chemin_fichier'] ?>" target="_blank" class="btn btn-white border text-start shadow-sm d-flex align-items-center p-3 text-dark hover-primary" style="transition: all 0.2s;">
                                                    <i class="bi <?= $icon['class'] ?> <?= $icon['color'] ?> fs-3 me-3"></i>
                                                    <div>
                                                        <div class="fw-bold small"><?= htmlspecialchars($doc['nom']) ?></div>
                                                        <div class="text-muted" style="font-size:0.7rem;">Cliquer pour consulter</div>
                                                    </div>
                                                    <i class="bi bi-download ms-auto text-muted opacity-50"></i>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- RAPPEL DES MEMBRES CONVOQUÉS -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-3 px-3 pb-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-muted text-uppercase">Convoqués</h6>
                    <span class="badge bg-light text-dark border"><?= count($membres) ?> membres</span>
                </div>
                <div class="card-body px-3 pb-3 pt-0" style="max-height: 500px; overflow-y: auto;">
                    <?php if (empty($membres)): ?>
                        <p class="text-muted small text-center py-3">Aucun membre rattaché.</p>
                    <?php else: ?>
                        <?php
                        // Tri pour regrouper par collège
                        $admins    = array_filter($membres, fn($m) => $m['college'] === 'administration');
                        $personnel = array_filter($membres, fn($m) => $m['college'] === 'personnel');
                        ?>

                        <!-- Collège Administration -->
                        <?php if (!empty($admins)): ?>
                            <div class="small fw-bold text-primary mt-2 mb-2 border-bottom pb-1" style="font-size: 0.75rem;">
                                Collège Administration
                            </div>
                            <ul class="list-unstyled mb-3 small">
                                <?php foreach ($admins as $m): ?>
                                    <li class="d-flex justify-content-between align-items-center py-1">
                                        <span class="text-truncate pe-2">
                                            <i class="bi bi-person text-muted me-1"></i>
                                            <?= htmlspecialchars(strtoupper($m['nom']) . ' ' . $m['prenom']) ?>
                                        </span>
                                        <span class="badge <?= $m['type_mandat'] === 'titulaire' ? 'bg-dark' : 'bg-secondary' ?> bg-opacity-75" style="font-size: 0.65rem;">
                                            <?= $m['type_mandat'] === 'titulaire' ? 'Titulaire' : 'Suppléant' ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <!-- Collège Personnel -->
                        <?php if (!empty($personnel)): ?>
                            <div class="small fw-bold text-success mb-2 border-bottom pb-1" style="font-size: 0.75rem;">
                                Collège Personnel
                            </div>
                            <ul class="list-unstyled mb-0 small">
                                <?php foreach ($personnel as $m): ?>
                                    <li class="d-flex justify-content-between align-items-center py-1">
                                        <span class="text-truncate pe-2">
                                            <i class="bi bi-person text-muted me-1"></i>
                                            <?= htmlspecialchars(strtoupper($m['nom']) . ' ' . $m['prenom']) ?>
                                        </span>
                                        <span class="badge <?= $m['type_mandat'] === 'titulaire' ? 'bg-dark' : 'bg-secondary' ?> bg-opacity-75" style="font-size: 0.65rem;">
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

<style>
/* Léger effet de survol sur les documents */
.hover-primary:hover {
    border-color: #0d6efd !important;
    background-color: #f8fbff !important;
}
.hover-primary:hover .bi-download {
    opacity: 1 !important;
    color: #0d6efd !important;
}

/* Personnalisation de la barre de défilement (scroll) de la carte des membres */
.card-body::-webkit-scrollbar {
    width: 6px;
}
.card-body::-webkit-scrollbar-track {
    background: #f1f1f1; 
    border-radius: 4px;
}
.card-body::-webkit-scrollbar-thumb {
    background: #c1c1c1; 
    border-radius: 4px;
}
.card-body::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8; 
}

.rich-text-container {
    font-size: 0.875em;  /* Équivalent de la classe .small */
    color: #6c757d;      /* Équivalent de la classe .text-muted */
    margin-bottom: 1rem; /* Équivalent de la classe .mb-3 */
}
.rich-text-container p, 
.rich-text-container ul, 
.rich-text-container ol {
    font-size: inherit;
    color: inherit;
    margin-bottom: 0.5rem; /* Espace réduit entre les paragraphes */
}
.rich-text-container p:last-child,
.rich-text-container ul:last-child {
    margin-bottom: 0; /* Pas de marge sous le dernier élément */
}
.rich-text-container a {
    color: #0d6efd; /* Garder les liens bleus si besoin */
}

</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
