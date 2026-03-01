<!-- ORDRE DU JOUR (ÉDITION) -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-list-ol me-2 text-primary"></i>Édition de l'Ordre du Jour</h4>
    <?php if ($isOdjEditable): ?>
        <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addPointModal">
            <i class="bi bi-plus-lg me-1"></i>Nouveau point
        </button>
    <?php endif; ?>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <?php if (empty($points)): ?>
            <div class="card border-0 shadow-sm text-center py-5 text-muted border-dashed">
                <i class="bi bi-journal-x fs-1 opacity-25 d-block mb-3"></i>
                L'ordre du jour est vide.<br>
                <small>Ajoutez des points pour structurer la séance.</small>
            </div>
        <?php else: ?>
            <div class="accordion shadow-sm" id="accordionODJEdit">
                <?php foreach ($points as $i => $pt): 
                    $docsPoint = array_filter($documents, fn($d) => $d['point_odj_id'] == $pt['id'] && (!isset($d['type_doc']) || $d['type_doc'] !== 'convocation'));
                    $tcfg = $typeCfg[$pt['type_point']] ?? ['label' => $pt['type_point'], 'class' => 'bg-secondary'];
                ?>
                <div class="accordion-item border-0 border-bottom bg-white" data-id="<?= $pt['id'] ?>">
                    <div class="accordion-header d-flex align-items-center pe-3">
                        <?php if ($seance['statut'] !== 'terminee'): ?>
                            <div class="px-3 py-3 text-muted drag-handle" style="cursor: grab;" title="Glisser pour réorganiser">
                                <i class="bi bi-grip-vertical fs-5"></i>
                            </div>
                        <?php else: ?>
                            <div class="px-3 py-3 text-muted"><i class="bi bi-dot fs-5"></i></div>
                        <?php endif; ?>
                        
                        <button class="accordion-button collapsed flex-grow-1 border-0 shadow-none bg-transparent px-2" type="button" data-bs-toggle="collapse" data-bs-target="#col_edit_<?= $pt['id'] ?>" style="width: auto;">
                            <span class="fw-bold text-dark text-truncate" style="max-width: 75%;"><?= htmlspecialchars($pt['titre']) ?></span>
                            <span class="badge <?= $tcfg['class'] ?> ms-3 small fw-normal"><?= $tcfg['label'] ?></span>
                            <?php if(count($docsPoint) > 0): ?>
                                <span class="badge bg-light text-dark border ms-2"><i class="bi bi-paperclip me-1"></i><?= count($docsPoint) ?></span>
                            <?php endif; ?>
                        </button>
                        
                        <?php if ($isOdjEditable): ?>
                        <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/deletePoint/<?= $pt['id'] ?>', 'Supprimer ce point et ses documents associés ?')" class="btn btn-sm btn-outline-danger border-0 ms-2" title="Supprimer le point">
                            <i class="bi bi-trash fs-6"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div id="col_edit_<?= $pt['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#accordionODJEdit">
                        <div class="accordion-body bg-light border-top border-light">
                            <!-- EXPOSÉ DES MOTIFS -->
                            <div class="mb-4 bg-white p-3 rounded shadow-sm border">
                                <h6 class="fw-bold text-secondary mb-2" style="font-size: 0.85rem; text-transform: uppercase;">Exposé des motifs</h6>
                                <?php if ($isDossierEditable): ?>
                                    <div id="editor-<?= $pt['id'] ?>" style="height: 150px; background: #fff;"><?= $pt['description'] ?></div>
                                    <div class="d-flex justify-content-end align-items-center mt-2">
                                        <span id="save-msg-<?= $pt['id'] ?>" class="text-success small fw-bold me-3 d-none"><i class="bi bi-check-lg me-1"></i>Sauvegardé</span>
                                        <button class="btn btn-sm btn-primary fw-bold" onclick="saveDescription(<?= $pt['id'] ?>)"><i class="bi bi-save me-1"></i>Enregistrer le texte</button>
                                    </div>
                                <?php else: ?>
                                    <div class="rich-text-container">
                                        <?= !empty(trim(strip_tags($pt['description']))) ? $pt['description'] : '<em class="text-muted">Aucun exposé des motifs fourni.</em>' ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- PIÈCES JOINTES -->
                            <div class="d-flex justify-content-between align-items-center mb-2 mt-4">
                                <h6 class="fw-bold mb-0 text-secondary" style="font-size: 0.85rem; text-transform: uppercase;">Pièces jointes</h6>
                                <?php if ($isDossierEditable): ?>
                                <button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.75rem;" onclick="openDocModal(<?= $pt['id'] ?>)">
                                    <i class="bi bi-upload me-1"></i>Ajouter un document
                                </button>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($docsPoint)): ?>
                                <div class="list-group list-group-flush border rounded overflow-hidden shadow-sm">
                                    <?php foreach($docsPoint as $doc): $icon = getFileIcon($doc['chemin_fichier']); ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center p-3 bg-white border-bottom border-light">
                                            <a href="<?= URLROOT ?>/<?= $doc['chemin_fichier'] ?>" target="_blank" class="text-decoration-none text-dark d-flex align-items-center flex-grow-1">
                                                <div class="bg-light p-2 rounded d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                    <i class="bi <?= $icon['class'] ?> <?= $icon['color'] ?> fs-5"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold small mb-0"><?= htmlspecialchars($doc['nom']) ?></div>
                                                    <div class="text-muted" style="font-size:0.65rem;">Ajouté le <?= date('d/m/Y', strtotime($doc['uploaded_at'] ?? 'now')) ?></div>
                                                </div>
                                            </a>
                                            <?php if ($isDossierEditable): ?>
                                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/deleteDoc/<?= $doc['id'] ?>', 'Retirer ce document ?')" class="btn btn-sm btn-light text-danger border rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                                <i class="bi bi-x-lg"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-white border text-center small text-muted py-3 mb-0 shadow-sm">Aucun document rattaché à ce point.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <!-- ENCART CONVOCATION OFFICIELLE -->
        <div class="card border-0 shadow-sm mb-4 border-top border-4 border-warning">
            <div class="card-header bg-white border-0 pt-3 px-3 pb-0">
                <h6 class="fw-bold mb-0 text-dark text-uppercase"><i class="bi bi-file-earmark-check text-warning me-2"></i>Convocation</h6>
            </div>
            <div class="card-body px-3 pb-3">
                <?php if ($isOdjEditable): ?>
                    <?php if (!$hasConvocSignee): ?>
                        <div class="mb-3 border-bottom pb-3 mt-2">
                            <p class="small text-muted mb-2">Générez la base de votre convocation pour mise au parapheur.</p>
                            <a href="<?= URLROOT ?>/seances/generateConvocation/<?= $seance['id'] ?>" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-file-earmark-word me-1"></i> Générer via le modèle
                            </a>
                        </div>
                        <div>
                            <p class="small text-muted mb-2">Une fois signée, téléversez la version finale (PDF).</p>
                            <form action="<?= URLROOT ?>/seances/uploadDoc/<?= $seance['id'] ?>" method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                                <input type="hidden" name="type_doc" value="convocation">
                                <input type="hidden" name="nom" value="Convocation officielle signée">
                                <input type="file" name="fichier" class="form-control form-control-sm bg-light" accept=".pdf" required>
                                <button type="submit" class="btn btn-sm btn-primary fw-bold"><i class="bi bi-upload"></i></button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success bg-success bg-opacity-10 border-0 d-flex align-items-center mt-3 mb-0">
                            <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                            <div>
                                <div class="fw-bold text-dark small">Convocation prête</div>
                                <a href="<?= URLROOT ?>/<?= $convocationDoc['chemin_fichier'] ?>" target="_blank" class="small text-decoration-none text-success fw-bold">Voir le PDF</a>
                            </div>
                            <a href="#" onclick="showConfirmModal('<?= URLROOT ?>/seances/deleteDoc/<?= $convocationDoc['id'] ?>', 'Retirer cette convocation ?')" class="btn btn-sm text-danger ms-auto px-1" title="Supprimer">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($hasConvocSignee): ?>
                        <div class="alert alert-secondary bg-light border border-secondary border-opacity-25 d-flex align-items-center mt-2 mb-0">
                            <i class="bi bi-lock-fill text-muted fs-4 me-3"></i>
                            <div>
                                <div class="fw-bold text-dark small">Zone figée</div>
                                <a href="<?= URLROOT ?>/<?= $convocationDoc['chemin_fichier'] ?>" target="_blank" class="small text-decoration-none text-primary fw-bold">Consulter le PDF publié</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning border-0 small mb-0 mt-2">
                            <i class="bi bi-exclamation-triangle me-1"></i> Aucune convocation n'a été déposée.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- RAPPEL DES MEMBRES -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 px-3 pb-2 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-muted text-uppercase">Convoqués</h6>
                <span class="badge bg-light text-dark border"><?= count($membres) ?> membres</span>
            </div>
            <div class="card-body px-3 pb-3 pt-0" style="max-height: 250px; overflow-y: auto;">
                <?php if (empty($membres)): ?>
                    <p class="text-muted small text-center py-3">Aucun membre rattaché.</p>
                <?php else: ?>
                    <?php
                    $admins    = array_filter($membres, fn($m) => $m['college'] === 'administration');
                    $personnel = array_filter($membres, fn($m) => $m['college'] === 'personnel');
                    ?>
                    <?php if (!empty($admins)): ?>
                        <div class="small fw-bold text-primary mt-2 mb-2 border-bottom pb-1" style="font-size: 0.75rem;">Collège Administration</div>
                        <ul class="list-unstyled mb-3 small">
                            <?php foreach ($admins as $m): ?>
                                <li class="d-flex justify-content-between align-items-center py-1">
                                    <span class="text-truncate pe-2"><i class="bi bi-person text-muted me-1"></i><?= htmlspecialchars(strtoupper($m['nom']) . ' ' . $m['prenom']) ?></span>
                                    <span class="badge <?= $m['type_mandat'] === 'titulaire' ? 'bg-dark' : 'bg-secondary' ?> bg-opacity-75" style="font-size: 0.65rem;"><?= $m['type_mandat'] === 'titulaire' ? 'T' : 'S' ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($personnel)): ?>
                        <div class="small fw-bold text-success mb-2 border-bottom pb-1" style="font-size: 0.75rem;">Collège Personnel</div>
                        <ul class="list-unstyled mb-0 small">
                            <?php foreach ($personnel as $m): ?>
                                <li class="d-flex justify-content-between align-items-center py-1">
                                    <span class="text-truncate pe-2"><i class="bi bi-person text-muted me-1"></i><?= htmlspecialchars(strtoupper($m['nom']) . ' ' . $m['prenom']) ?></span>
                                    <span class="badge <?= $m['type_mandat'] === 'titulaire' ? 'bg-dark' : 'bg-secondary' ?> bg-opacity-75" style="font-size: 0.65rem;"><?= $m['type_mandat'] === 'titulaire' ? 'T' : 'S' ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODALE D'AJOUT DE DOCUMENT -->
<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= URLROOT ?>/seances/uploadDoc/<?= $seance['id'] ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="point_odj_id" id="upload_point_id" value="">
                <input type="hidden" name="type_doc" value="annexe">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cloud-arrow-up text-primary me-2"></i>Ajouter un document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Fichier <span class="text-danger">*</span></label>
                        <input type="file" name="fichier" class="form-control form-control-lg bg-light" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-muted text-uppercase">Titre d'affichage (optionnel)</label>
                        <input type="text" name="nom" class="form-control" placeholder="Laisser vide pour utiliser le nom du fichier">
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Téléverser</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODALE DE CRÉATION DE POINT -->
<div class="modal fade" id="addPointModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= URLROOT ?>/seances/addPoint/<?= $seance['id'] ?>" method="POST">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-journal-plus me-2 text-primary"></i>Créer un point</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Titre du point <span class="text-danger">*</span></label>
                        <input type="text" name="titre" class="form-control" placeholder="Ex : Budget primitif..." required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Nature du point</label>
                            <select name="type_point" class="form-select">
                                <option value="information">Information simple</option>
                                <option value="deliberation">Délibération</option>
                                <option value="vote">Soumis au vote</option>
                                <option value="divers">Questions diverse</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Direction d'origine</label>
                            <input type="text" name="direction_origine" class="form-control" placeholder="Ex: RH, Finances...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// SCRIPTS SPÉCIFIQUES À LA PRÉPARATION (Quill, Sortable, Modales)
function openDocModal(pointId) {
    document.getElementById('upload_point_id').value = pointId;
    new bootstrap.Modal(document.getElementById('uploadDocModal')).show();
}

const quillEditors = {};
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('[id^="editor-"]').forEach(function(el) {
        let pointId = el.id.split('-')[1];
        quillEditors[pointId] = new Quill('#editor-' + pointId, {
            theme: 'snow',
            placeholder: 'Rédigez l\'exposé des motifs ici...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'color': [] }],
                    ['clean']
                ]
            }
        });
    });

    const accordionODJ = document.getElementById('accordionODJEdit');
    if (accordionODJ) {
        new Sortable(accordionODJ, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function (evt) {
                const newOrder = [];
                accordionODJ.querySelectorAll('.accordion-item').forEach(function(item) {
                    newOrder.push(item.getAttribute('data-id'));
                });
                fetch('<?= URLROOT ?>/seances/updateOrder', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order: newOrder })
                });
            }
        });
    }
});

function saveDescription(pointId) {
    const htmlContent = document.querySelector('#editor-' + pointId + ' .ql-editor').innerHTML;
    fetch('<?= URLROOT ?>/seances/updateDescription/' + pointId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ description: htmlContent })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const msg = document.getElementById('save-msg-' + pointId);
            msg.classList.remove('d-none');
            setTimeout(() => { msg.classList.add('d-none'); }, 3000);
        }
    });
}
</script>
