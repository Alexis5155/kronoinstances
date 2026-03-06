<div class="row g-4 mb-5">
    <!-- COLONNE GAUCHE : DÉBATS & VOTES -->
    <div class="col-lg-8">
        <h5 class="fw-bold mb-4 text-dark">
            <i class="bi bi-journal-text me-2 text-primary"></i>
            <?= ($seance['statut'] === 'terminee') ? 'Consultation du Procès-Verbal' : 'Édition du Procès-Verbal' ?>
        </h5>

        <div class="accordion shadow-sm" id="accordionPvEdit">
            <div class="d-flex flex-column gap-2">
                <?php foreach ($points as $i => $pt): 
                    $isVote = in_array(strtolower($pt['type_point'] ?? ''), ['vote', 'deliberation']);
                    $isRetire = ($pt['retire'] ?? 0) == 1;
                ?>
                <div class="accordion-item border-0 bg-white rounded <?= $isRetire ? 'opacity-75' : '' ?>">
                    <div class="accordion-header d-flex align-items-center pe-3 border rounded <?= $isRetire ? 'bg-light' : '' ?>">
                        <div class="px-3 py-3 text-muted"><i class="bi bi-dot fs-5"></i></div>
                        
                        <button class="accordion-button collapsed flex-grow-1 border-0 shadow-none bg-transparent px-2 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#col_pv_<?= $pt['id'] ?>">
                            <span class="fw-bold text-dark me-3 <?= $isRetire ? 'text-decoration-line-through text-muted' : '' ?>" style="font-size: 1.05rem;">
                                <?= ($i+1) ?>. <?= htmlspecialchars($pt['titre']) ?>
                            </span>
                            
                            <?php if($isRetire): ?> 
                                <span class="badge bg-warning text-dark ms-auto me-2"><i class="bi bi-slash-circle me-1"></i>Retiré</span> 
                            <?php else: ?>
                                <?php if(!empty($pt['debats'])): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success ms-auto me-2"><i class="bi bi-check2-all me-1"></i>Débats saisis</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border ms-auto me-2"><i class="bi bi-dash me-1"></i>Vide</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </button>
                    </div>
                    
                    <?php if(!$isRetire): ?>
                    <div id="col_pv_<?= $pt['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#accordionPvEdit">
                        <div class="accordion-body bg-light border-top border-light p-4 rounded-bottom">
                            
                            <!-- BLOC VOTES -->
                            <?php if ($isVote): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-0">Résultat des votes</label>
                                    <?php if ($seance['statut'] === 'finalisation'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 fw-bold" data-bs-toggle="modal" data-bs-target="#modalVote_<?= $pt['id'] ?>"><i class="bi bi-pencil me-1"></i>Corriger</button>
                                    <?php endif; ?>
                                </div>
                                <div class="bg-white p-3 rounded border mb-4 shadow-sm">
                                    <?php 
                                    $votesPt = isset($votes[$pt['id']]) ? $votes[$pt['id']] : [];
                                    $hasVotes = false;
                                    foreach(['administration', 'personnel'] as $c) {
                                        if (isset($votesPt[$c]) && array_sum($votesPt[$c]) > 0) $hasVotes = true;
                                    }
                                    if(!$hasVotes): ?>
                                        <div class="text-center py-2"><em class="text-muted small">Aucun vote n'a été enregistré pour ce point.</em></div>
                                    <?php else: ?>
                                        <div class="row g-3">
                                            <?php foreach(['administration', 'personnel'] as $college): 
                                                $v = $votesPt[$college] ?? null;
                                                if($v && array_sum($v) > 0):
                                                    $unanimite = ($v['pour'] > 0 && $v['contre'] == 0 && $v['abstention'] == 0 && $v['refus'] == 0);
                                            ?>
                                                <div class="col-md-6">
                                                    <div class="border rounded p-3 bg-light h-100">
                                                        <div class="fw-bold small text-uppercase mb-2 border-bottom pb-2 text-primary">Collège <?= ucfirst($college) ?></div>
                                                        <?php if($unanimite): ?>
                                                            <div class="d-flex align-items-center text-success fw-bold">
                                                                <i class="bi bi-stars fs-5 me-2"></i>Avis favorable à l'unanimité
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="d-flex justify-content-between small fw-medium text-center">
                                                                <div><div class="text-muted text-uppercase mb-1" style="font-size:0.7rem;">Pour</div><div class="text-success fs-5"><?= $v['pour'] ?></div></div>
                                                                <div><div class="text-muted text-uppercase mb-1" style="font-size:0.7rem;">Contre</div><div class="text-danger fs-5"><?= $v['contre'] ?></div></div>
                                                                <div><div class="text-muted text-uppercase mb-1" style="font-size:0.7rem;">Abst</div><div class="text-warning fs-5"><?= $v['abstention'] ?></div></div>
                                                                <div><div class="text-muted text-uppercase mb-1" style="font-size:0.7rem;">Refus</div><div class="text-secondary fs-5"><?= $v['refus'] ?></div></div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- BLOC DÉBATS -->
                            <label class="form-label small fw-bold text-muted text-uppercase mb-2">Retranscription des débats</label>
                            <?php if ($seance['statut'] === 'finalisation'): ?>
                                <div class="bg-white rounded shadow-sm border mb-2">
                                    <div class="editor-pv" data-point-id="<?= $pt['id'] ?>" style="min-height: 150px; background: #fff; font-size: 0.95rem; border-radius: 0.25rem;">
                                        <?= !empty($pt['debats']) ? $pt['debats'] : '' ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span id="save-msg-pv-<?= $pt['id'] ?>" class="text-success small fw-bold me-3" style="display: none; transition: opacity 0.3s;"><i class="bi bi-check-lg me-1"></i>Enregistré</span>
                                    <button type="button" class="btn btn-sm btn-primary fw-bold px-3 shadow-sm" onclick="saveDebatsFinal(<?= $pt['id'] ?>)"><i class="bi bi-save me-2"></i>Forcer l'enregistrement</button>
                                </div>
                            <?php else: ?>
                                <div class="bg-white p-3 rounded border text-dark shadow-sm" style="min-height: 80px; font-size: 0.95rem;"><?= !empty($pt['debats']) ? $pt['debats'] : '<em class="text-muted">Aucun débat retranscrit.</em>' ?></div>
                            <?php endif; ?>

                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Modal Correction Votes -->
                <?php if ($seance['statut'] === 'finalisation' && $isVote): ?>
                <div class="modal fade" id="modalVote_<?= $pt['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <form action="<?= URLROOT ?>/seances/saveVotesManual/<?= $pt['id'] ?>" method="POST">
                                <div class="modal-header bg-light border-0"><h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Corriger les votes</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body p-4">
                                    <p class="small text-muted mb-4">Ajustez manuellement le décompte des voix pour le point <strong><?= htmlspecialchars($pt['titre']) ?></strong>.</p>
                                    
                                    <?php foreach(['administration' => 'admin', 'personnel' => 'pers'] as $college => $prefix): 
                                        $v = $votesPt[$college] ?? ['pour'=>0,'contre'=>0,'abstention'=>0,'refus'=>0];
                                    ?>
                                        <div class="card bg-light border-0 mb-3">
                                            <div class="card-body p-3">
                                                <h6 class="fw-bold mb-3 text-primary text-uppercase" style="font-size:0.85rem;">Collège <?= ucfirst($college) ?></h6>
                                                <div class="row g-2">
                                                    <div class="col-3"><label class="small text-muted fw-bold">Pour</label><input type="number" min="0" name="<?= $prefix ?>_pour" class="form-control" value="<?= $v['pour'] ?>"></div>
                                                    <div class="col-3"><label class="small text-muted fw-bold">Contre</label><input type="number" min="0" name="<?= $prefix ?>_contre" class="form-control" value="<?= $v['contre'] ?>"></div>
                                                    <div class="col-3"><label class="small text-muted fw-bold">Abst.</label><input type="number" min="0" name="<?= $prefix ?>_abstention" class="form-control" value="<?= $v['abstention'] ?>"></div>
                                                    <div class="col-3"><label class="small text-muted fw-bold">Refus</label><input type="number" min="0" name="<?= $prefix ?>_refus" class="form-control" value="<?= $v['refus'] ?>"></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="modal-footer bg-light border-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn btn-primary fw-bold px-4">Sauvegarder</button></div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if ($seance['statut'] === 'finalisation'): ?>
        <div class="alert alert-info border-0 shadow-sm mt-4 d-flex">
            <i class="bi bi-info-circle-fill fs-4 me-3 text-info"></i>
            <div>
                <strong>Astuce de rédaction :</strong> Les textes saisis ici remplaceront les notes brutes prises pendant le Live. Vous pouvez structurer vos phrases pour le rendu final du procès-verbal.
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- COLONNE DROITE : ACTIONS SUR LE PV -->
    <div class="col-lg-4">
        <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-file-earmark-check me-2 text-primary"></i>Document Officiel</h5>
        
        <div class="card border-0 shadow-sm mb-4 border-top border-4 border-info">
            <div class="card-body p-4 text-center">
                <div class="bg-info bg-opacity-10 d-inline-block p-3 rounded-circle mb-3">
                    <i class="bi bi-file-earmark-word text-info fs-1"></i>
                </div>
                <h6 class="fw-bold text-dark">Générer le PV</h6>
                <p class="small text-muted mb-4">Créez le premier jet du procès-verbal (ODT). Il contiendra l'appel, l'ordre du jour, les débats et les votes.</p>
                <a href="<?= URLROOT ?>/seances/generatePv/<?= $seance['id'] ?>" class="btn btn-info w-100 fw-bold text-dark shadow-sm"><i class="bi bi-download me-2"></i>Télécharger le modèle</a>
            </div>
        </div>

        <div class="card border-0 shadow-sm border-top border-4 <?= !empty($seance['proces_verbal_path']) ? 'border-success' : 'border-secondary' ?>">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-pen me-2 <?= !empty($seance['proces_verbal_path']) ? 'text-success' : 'text-secondary' ?>"></i>PV Définitif Signé</h6>
                
                <?php if (!empty($seance['proces_verbal_path'])): ?>
                    <div class="text-center py-2 mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-2" style="width: 50px; height: 50px;">
                            <i class="bi bi-check-lg fs-2"></i>
                        </div>
                        <div class="fw-bold text-success">Fichier validé</div>
                        <?php if ($seance['statut'] === 'terminee'): ?>
                            <div class="small text-muted mt-1">Envoyé aux membres</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <a href="<?= URLROOT ?>/<?= htmlspecialchars($seance['proces_verbal_path']) ?>" target="_blank" class="btn btn-outline-success flex-grow-1 fw-bold"><i class="bi bi-eye me-2"></i>Consulter le PV</a>
                        <?php if ($seance['statut'] === 'finalisation'): ?>
                        <button type="button" onclick="showConfirmModal('<?= URLROOT ?>/seances/deletePv/<?= $seance['id'] ?>', 'Supprimer le PV signé ?')" class="btn btn-outline-danger" title="Supprimer le fichier"><i class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary p-3 small mb-4 text-center border-0 bg-light">
                        <i class="bi bi-file-earmark-pdf fs-3 d-block mb-2 text-muted"></i>
                        Aucun procès-verbal signé n'a encore été déposé.
                    </div>
                    <?php if ($seance['statut'] === 'finalisation'): ?>
                    <form method="POST" action="<?= URLROOT ?>/seances/uploadPv/<?= $seance['id'] ?>" enctype="multipart/form-data">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Uploader le PDF final</label>
                        <div class="input-group shadow-sm">
                            <input type="file" name="pv_signe" class="form-control bg-light" accept=".pdf" required>
                            <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-upload"></i></button>
                        </div>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($seance['statut'] === 'finalisation'): ?>
<script>
window.quillPvEditors = {};
window.saveDebatsFinal = function(pointId) {
    if (!window.quillPvEditors[pointId]) return;
    fetch('<?= URLROOT ?>/seances/autoSaveDebats/' + pointId, {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ debats: window.quillPvEditors[pointId].root.innerHTML })
    }).then(res => {
        if(res.ok) {
            let msg = document.getElementById('save-msg-pv-' + pointId);
            if(msg) { msg.style.display = 'inline-block'; setTimeout(() => msg.style.display = 'none', 2000); }
        }
    });
};
document.addEventListener("DOMContentLoaded", function() {
    setTimeout(function() {
        if (typeof Quill === 'undefined') return;
        document.querySelectorAll('.editor-pv').forEach(function(el) {
            let id = el.getAttribute('data-point-id');
            if (id) {
                let q = new Quill(el, { 
                    theme: 'snow', 
                    placeholder: 'Retranscrivez les échanges...',
                    modules: { toolbar: [['bold', 'italic', 'underline'], [{'list':'ordered'},{'list':'bullet'}], ['clean']] }
                });
                window.quillPvEditors[id] = q;
                
                // Auto-save sur la rédaction du PV
                let timeout;
                q.on('text-change', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => window.saveDebatsFinal(id), 1500);
                });
            }
        });
    }, 500);
});
</script>
<?php endif; ?>
