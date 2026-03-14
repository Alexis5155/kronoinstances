<?php require_once __DIR__ . '/../../layouts/header.php'; ?>

<?php
$isEdit     = !empty($instance);
$pageTitle  = $isEdit ? htmlspecialchars($instance['nom']) : 'Nouvelle instance';
$modelePath = 'uploads/modeles/modele_instance_' . ($instance['id'] ?? 0) . '.odt';
$hasModele  = $isEdit && file_exists($modelePath);
?>

<style>
.ki-section-icon { width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0; }
.ki-form-label   { font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;margin-bottom:.4rem;display:block; }
.v-nav-link      { display:flex;align-items:center;gap:.6rem;padding:.8rem 1.25rem;font-size:.88rem;font-weight:600;color:#6b7280;text-decoration:none;cursor:pointer;background:none;border:none;border-left:3px solid transparent;transition:all .2s;width:100%;text-align:left; }
.v-nav-link:hover  { background:#f8f9fa;color:#111827; }
.v-nav-link.active { background:#f0fdf4;color:#059669;border-left-color:#059669; }
.stat-card { transition:transform .25s ease,box-shadow .25s ease;border:1px solid rgba(0,0,0,.05) !important; }
/* Recherche destinataires style documents.php */
.search-item { cursor:pointer;transition:background .2s; }
.search-item:hover { background:#f8f9fa; }
.user-pill { animation:fadeInPill .2s ease; }
@keyframes fadeInPill { from{opacity:0;transform:scale(.95)} to{opacity:1;transform:scale(1)} }
/* Erreurs */
.field-error { display:none;font-size:.8rem;color:#dc3545;margin-top:3px; }
.is-invalid ~ .field-error { display:block; }
/* Tables membres */
.fixed-table { table-layout:fixed;width:100%; }
.fixed-table td { white-space:nowrap;overflow:hidden;text-overflow:ellipsis;vertical-align:middle; }
/* Balises ODT */
.tag-badge { display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:4px 10px;font-size:.78rem;font-family:monospace;color:#334155; }
</style>

<div class="container py-4" style="max-width:1080px;">

    <!-- EN-TÊTE -->
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div class="d-flex align-items-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm me-3"
                 style="width:50px;height:50px;font-size:1.2rem;background:#ecfdf5;color:#059669;">
                <i class="bi bi-diagram-3"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-0 text-dark" style="letter-spacing:-0.5px;">
                    <?= $isEdit ? 'Modifier l\'instance' : 'Nouvelle instance' ?>
                </h2>
                <p class="text-muted small mb-0">Instances paritaires</p>
            </div>
        </div>
        <a href="<?= URLROOT ?>/admin/instances" class="btn btn-light fw-bold shadow-sm px-3 rounded-pill border">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
    </div>

    <div class="row g-4">

        <!-- ── Colonne gauche ── -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden stat-card sticky-top" style="top:90px;">
                <div class="p-4 text-center border-bottom">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold mb-3"
                         style="width:64px;height:64px;font-size:1.4rem;background:#ecfdf5;color:#059669;">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                    <div class="fw-bold text-dark mb-1"><?= $pageTitle ?></div>
                    <div class="small text-muted">
                        <?= $isEdit ? count($instance['membres'] ?? []) . ' membre(s)' : 'Nouvelle instance' ?>
                    </div>
                </div>
                <div>
                    <button class="v-nav-link active" type="button" onclick="switchTab('infos',this)">
                        <i class="bi bi-info-circle"></i> Paramètres
                    </button>
                    <button class="v-nav-link" type="button" onclick="switchTab('managers',this)">
                        <i class="bi bi-shield-lock"></i> Gestionnaires
                    </button>
                    <button class="v-nav-link" type="button" onclick="switchTab('membres',this)">
                        <i class="bi bi-people"></i> Membres
                    </button>
                    <button class="v-nav-link" type="button" onclick="switchTab('convocations',this)">
                        <i class="bi bi-file-earmark-word"></i> Modèle convocation
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Colonne droite ── -->
        <div class="col-lg-9">

            <!-- FORMULAIRE PRINCIPAL -->
            <form method="POST" action="<?= URLROOT ?>/admin/instances" id="instanceForm" novalidate>
                <input type="hidden" name="save_instance" value="1">
                <input type="hidden" name="instance_id"  value="<?= $instance['id'] ?? '' ?>">
                <input type="hidden" name="membres_json" id="membres_json" value="[]">

                <!-- ONGLET : PARAMÈTRES -->
                <div class="tab-ki" id="tab-infos">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden stat-card">
                        <div class="card-header bg-light border-0 px-4 py-3 d-flex align-items-center gap-2">
                            <div class="ki-section-icon" style="background:#ecfdf5;color:#059669;"><i class="bi bi-info-circle"></i></div>
                            <span class="fw-bold text-dark">Paramètres généraux</span>
                        </div>
                        <div class="card-body bg-white p-4">
                            <div class="mb-3">
                                <label class="ki-form-label">Nom de l'instance <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-diagram-3 text-muted"></i></span>
                                    <input type="text" id="nom" name="nom" class="form-control bg-light border-start-0"
                                           value="<?= htmlspecialchars($instance['nom'] ?? '') ?>"
                                           placeholder="Ex : Comité Social Territorial">
                                </div>
                                <div class="field-error" id="err-nom">Le nom est obligatoire.</div>
                            </div>
                            <div class="mb-4">
                                <label class="ki-form-label">Description</label>
                                <textarea id="description" name="description" class="form-control bg-light" rows="2"
                                          placeholder="Description facultative..."><?= htmlspecialchars($instance['description'] ?? '') ?></textarea>
                            </div>
                            <div class="border-top pt-4">
                                <label class="ki-form-label mb-3">Composition <span class="text-danger">*</span></label>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="ki-form-label">Nb. Titulaires</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-check text-muted"></i></span>
                                            <input type="number" id="nb_titulaires" name="nb_titulaires"
                                                   class="form-control bg-light border-start-0" min="1"
                                                   value="<?= $instance['nb_titulaires'] ?? 5 ?>">
                                        </div>
                                        <div class="field-error" id="err-titulaires">Min. 1.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="ki-form-label">Nb. Suppléants</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-dash text-muted"></i></span>
                                            <input type="number" id="nb_suppleants" name="nb_suppleants"
                                                   class="form-control bg-light border-start-0" min="0"
                                                   value="<?= $instance['nb_suppleants'] ?? 5 ?>">
                                        </div>
                                        <div class="field-error" id="err-suppleants">Min. 0.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="ki-form-label">Quorum requis</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-check2-circle text-muted"></i></span>
                                            <input type="number" id="quorum" name="quorum"
                                                   class="form-control bg-light border-start-0" min="1"
                                                   value="<?= $instance['quorum_requis'] ?? 3 ?>">
                                        </div>
                                        <div class="field-error" id="err-quorum">Min. 1.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ONGLET : GESTIONNAIRES -->
                <div class="tab-ki d-none" id="tab-managers">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden stat-card">
                        <div class="card-header bg-light border-0 px-4 py-3 d-flex align-items-center gap-2">
                            <div class="ki-section-icon" style="background:#fdf4ff;color:#9333ea;"><i class="bi bi-shield-lock"></i></div>
                            <span class="fw-bold text-dark">Gestionnaires</span>
                        </div>
                        <div class="card-body bg-white p-4">
                            <div class="alert border-0 small rounded-3 mb-4 py-2" style="background:#f5f3ff;color:#6d28d9;">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                Ces agents pourront créer des séances et gérer l'ordre du jour pour cette instance.
                            </div>
                            <label class="ki-form-label mb-2">Agents autorisés</label>

                            <div id="managersSelectedContainer"
                                class="d-flex flex-wrap gap-2 mb-2 p-2 border rounded-3 bg-light"
                                style="min-height:46px;">
                                <span class="text-muted small align-self-center ms-1" id="noManagerText">Aucun gestionnaire sélectionné</span>
                            </div>

                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="managerSearchInput"
                                    class="form-control bg-light border-start-0"
                                    placeholder="Rechercher par nom, prénom ou identifiant..."
                                    autocomplete="off">
                            </div>
                            <!-- Dropdown FIXED (positionné par JS, hors de tout overflow caché) -->
                            <div id="managerSearchResults"
                                style="position:fixed;display:none;max-height:220px;overflow-y:auto;z-index:9999;background:#fff;border:1px solid #dee2e6;border-radius:.5rem;box-shadow:0 6px 20px rgba(0,0,0,.12);">
                            </div>

                            <div id="managersHiddenInputs"></div>
                            <div class="form-text small mt-2">
                                <i class="bi bi-info-circle me-1"></i>Cliquez sur un agent pour l'ajouter, sur la croix pour le retirer.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ONGLET : MEMBRES -->
                <div class="tab-ki d-none" id="tab-membres">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden stat-card">
                        <div class="card-header bg-light border-0 px-4 py-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="ki-section-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-people"></i></div>
                                <span class="fw-bold text-dark">Composition de l'instance</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary fw-bold rounded-pill px-3"
                                    data-bs-toggle="modal" data-bs-target="#memberModal"
                                    onclick="openMemberModal()">
                                <i class="bi bi-person-plus-fill me-1"></i>Ajouter un membre
                            </button>
                        </div>
                        <div class="card-body bg-white p-4">

                            <!-- Collège Administration -->
                            <div class="mb-4">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="ki-section-icon" style="background:#f3f4f6;color:#6b7280;width:24px;height:24px;font-size:.75rem;border-radius:6px;"><i class="bi bi-building"></i></span>
                                    <span class="ki-form-label mb-0">Collège Administration</span>
                                </div>
                                <div class="table-responsive rounded-3 border">
                                    <table class="table table-sm table-hover align-middle fixed-table mb-0">
                                        <thead class="table-light" style="font-size:.7rem;">
                                            <tr>
                                                <th style="width:30%" class="ps-3 py-2 text-uppercase text-muted fw-semibold">Nom Prénom</th>
                                                <th style="width:25%" class="py-2 text-uppercase text-muted fw-semibold">Qualité</th>
                                                <th style="width:15%" class="py-2 text-uppercase text-muted fw-semibold">Mandat</th>
                                                <th style="width:20%" class="py-2 text-uppercase text-muted fw-semibold">Email</th>
                                                <th style="width:80px" class="text-end pe-3 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody-administration"></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Collège Personnel -->
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="ki-section-icon" style="background:#e0f2fe;color:#0369a1;width:24px;height:24px;font-size:.75rem;border-radius:6px;"><i class="bi bi-people"></i></span>
                                    <span class="ki-form-label mb-0">Collège Représentants du Personnel</span>
                                </div>
                                <div class="table-responsive rounded-3 border">
                                    <table class="table table-sm table-hover align-middle fixed-table mb-0">
                                        <thead class="table-light" style="font-size:.7rem;">
                                            <tr>
                                                <th style="width:30%" class="ps-3 py-2 text-uppercase text-muted fw-semibold">Nom Prénom</th>
                                                <th style="width:25%" class="py-2 text-uppercase text-muted fw-semibold">Qualité</th>
                                                <th style="width:15%" class="py-2 text-uppercase text-muted fw-semibold">Mandat</th>
                                                <th style="width:20%" class="py-2 text-uppercase text-muted fw-semibold">Email</th>
                                                <th style="width:80px" class="text-end pe-3 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody-personnel"></tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="d-flex justify-content-end gap-2 mt-4 mb-2" id="form-actions">
                    <a href="<?= URLROOT ?>/admin/instances" class="btn btn-light fw-bold px-4 rounded-pill">Annuler</a>
                    <button type="button" class="btn btn-primary fw-bold px-5 rounded-pill shadow-sm" onclick="submitInstanceForm()">
                        <i class="bi bi-floppy me-2"></i>Enregistrer
                    </button>
                </div>

            </form>

            <!-- ONGLET : MODÈLE CONVOCATION (POST séparé, hors form principale) -->
            <div class="tab-ki d-none" id="tab-convocations">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden stat-card">
                    <div class="card-header bg-light border-0 px-4 py-3 d-flex align-items-center gap-2">
                        <div class="ki-section-icon" style="background:#eff6ff;color:#1d4ed8;"><i class="bi bi-file-earmark-word"></i></div>
                        <span class="fw-bold text-dark">Modèle de convocation</span>
                    </div>
                    <div class="card-body bg-white p-4">

                        <!-- Statut du modèle -->
                        <?php if ($hasModele): ?>
                        <div class="d-flex align-items-center gap-3 rounded-3 p-3 mb-4"
                            style="background:#f0fdf4;border:1px solid #bbf7d0;">
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <span class="fw-bold text-dark small flex-grow-1">Modèle configuré</span>
                            <a href="<?= URLROOT ?>/uploads/modeles/modele_instance_<?= $instance['id'] ?? 0 ?>.odt?v=<?= time() ?>"
                            class="btn btn-sm btn-light border fw-bold rounded-pill px-3" target="_blank">
                                <i class="bi bi-download me-1"></i>Télécharger
                            </a>
                            <a href="<?= URLROOT ?>/admin/instances?delete_modele_id=<?= $instance['id'] ?? 0 ?>"
                            class="btn btn-sm btn-outline-danger border-0 rounded-circle"
                            style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;"
                            onclick="return confirm('Supprimer ce modèle ?')">
                                <i class="bi bi-trash3" style="font-size:.8rem;"></i>
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="d-flex align-items-center gap-3 rounded-3 p-3 mb-4"
                            style="background:#fafafa;border:1px dashed #d1d5db;">
                            <i class="bi bi-file-earmark-plus text-muted fs-5"></i>
                            <span class="text-muted small">Aucun modèle — déposez un fichier <strong>.odt</strong> ci-dessous pour activer la génération automatique des convocations.</span>
                        </div>
                        <?php endif; ?>

                        <!-- Balises — table compacte -->
                        <div class="mb-4">
                            <label class="ki-form-label mb-2">Balises de substitution</label>
                            <div class="rounded-3 border overflow-hidden">
                                <table class="table table-sm mb-0" style="font-size:.82rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3 py-2 text-uppercase text-muted fw-semibold" style="width:140px;font-size:.68rem;letter-spacing:.4px;">Balise</th>
                                            <th class="py-2 text-uppercase text-muted fw-semibold" style="font-size:.68rem;letter-spacing:.4px;">Remplacée par…</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $balises = [
                                            '{{INSTANCE}}' => 'Nom de l\'instance paritaire',
                                            '{{DATE}}'     => 'Date de la séance  (JJ/MM/AAAA)',
                                            '{{HEURE}}'    => 'Heure de début  (HH:MM)',
                                            '{{LIEU}}'     => 'Lieu de tenue de la séance',
                                            '{{ODJ}}'      => 'Points à l\'ordre du jour',
                                            '{{PRENOM}}'   => 'Prénom du destinataire',
                                            '{{NOM}}'      => 'Nom du destinataire',
                                        ];
                                        foreach ($balises as $tag => $desc): ?>
                                        <tr>
                                            <td class="ps-3 py-2"><code class="text-dark fw-bold" style="background:#f1f5f9;border-radius:4px;padding:2px 6px;"><?= $tag ?></code></td>
                                            <td class="py-2 text-muted"><?= $desc ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Zone de dépôt -->
                        <div class="border-top pt-4">
                            <label class="ki-form-label mb-1">
                                <?= $hasModele ? 'Remplacer le modèle' : 'Déposer un modèle de convocation' ?>
                            </label>
                            <p class="text-muted small mb-3">
                                Seul le format <strong>.odt</strong> (LibreOffice Writer) est accepté.
                                <?= $hasModele ? 'Le fichier existant sera remplacé immédiatement.' : '' ?>
                            </p>
                            <?php if ($isEdit): ?>
                            <form action="<?= URLROOT ?>/admin/instances" method="POST" enctype="multipart/form-data"
                                class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="upload_modele" value="1">
                                <input type="hidden" name="instance_id"   value="<?= $instance['id'] ?>">
                                <input type="file" name="modele_odt" class="form-control form-control-sm bg-light" accept=".odt" required>
                                <button type="submit" class="btn btn-sm btn-primary fw-bold px-3 text-nowrap">
                                    <i class="bi bi-upload me-1"></i>Déposer
                                </button>
                            </form>
                            <?php else: ?>
                            <div class="rounded-3 p-3 small" style="background:#fef9c3;color:#854d0e;border:1px solid #fde68a;">
                                <i class="bi bi-lightbulb-fill me-2"></i>
                                Enregistrez d'abord l'instance pour pouvoir déposer un modèle.
                            </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

        </div><!-- /col-lg-9 -->
    </div><!-- /row -->
</div>

<!-- ═══════════════════════════════════════════ -->
<!-- MODAL AJOUT / ÉDITION MEMBRE               -->
<!-- ═══════════════════════════════════════════ -->
<div class="modal fade" id="memberModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 px-4 py-3 rounded-top-4"
                 style="background:#dbeafe;">
                <h5 class="modal-title fw-bold" style="color:#1d4ed8;">
                    <i class="bi bi-person-plus-fill me-2"></i>
                    <span id="memberModalTitle">Nouveau membre</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <input type="hidden" id="m_edit_id" value="">

                <!-- Chaque champ en ligne pleine largeur -->
                <div class="px-4 pt-4 pb-2 d-flex flex-column gap-3">

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="ki-form-label">Nom <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                <input type="text" id="m_nom" class="form-control bg-light border-start-0" placeholder="DUPONT">
                            </div>
                            <div class="field-error" id="err-m-nom">Le nom est obligatoire.</div>
                        </div>
                        <div class="col-6">
                            <label class="ki-form-label">Prénom <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                <input type="text" id="m_prenom" class="form-control bg-light border-start-0" placeholder="Marie">
                            </div>
                            <div class="field-error" id="err-m-prenom">Le prénom est obligatoire.</div>
                        </div>
                    </div>

                    <div>
                        <label class="ki-form-label">Qualité</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-award text-muted"></i></span>
                            <input type="text" id="m_qualite" class="form-control bg-light border-start-0" placeholder="Ex : Président(e), DRH…">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="ki-form-label">Collège</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-building text-muted"></i></span>
                                <select id="m_college" class="form-select bg-light border-start-0">
                                    <option value="administration">Administration</option>
                                    <option value="personnel">Représentants du Personnel</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="ki-form-label">Mandat</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-badge text-muted"></i></span>
                                <select id="m_mandat" class="form-select bg-light border-start-0">
                                    <option value="titulaire">Titulaire</option>
                                    <option value="suppleant">Suppléant</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <label class="ki-form-label">Email <span class="text-muted fw-normal normal-case" style="text-transform:none;letter-spacing:0;">(optionnel — permet de lier un compte)</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                            <input type="email" id="m_email" class="form-control bg-light border-start-0"
                                placeholder="adresse@collectivite.fr"
                                oninput="checkEmailMatch()">
                        </div>
                    </div>

                </div>

                <!-- Résultat de liaison compte -->
                <div id="m_account_alert" class="mx-4 mb-3 mt-1 rounded-3 border p-3 d-none"
                    style="background:#f0fdf4;border-color:#bbf7d0 !important;">
                    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-check-fill text-success fs-5"></i>
                            <div>
                                <div class="fw-bold text-dark small">Compte trouvé : <span id="m_match_name" class="text-success"></span></div>
                                <div class="text-muted" style="font-size:.75rem;">Cette adresse correspond à un compte existant.</div>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="m_link_account" checked>
                            <label class="form-check-label small fw-bold" for="m_link_account">Lier le compte</label>
                        </div>
                    </div>
                    <input type="hidden" id="m_matched_user_id">
                </div>

                <div id="m_no_account_hint" class="mx-4 mb-4 mt-1 d-none">
                    <span class="text-muted small"><i class="bi bi-person-dash me-1"></i>Aucun compte ne correspond — le membre sera créé sans liaison.</span>
                </div>

            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary fw-bold px-4" id="btnSubmitMember" onclick="saveMemberToList()">
                    <i class="bi bi-check-lg me-1"></i><span id="memberModalBtnLabel">Ajouter à la liste</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- FORMULAIRE CACHÉ soumission réelle -->
<form id="realSubmitForm" method="POST" action="<?= URLROOT ?>/admin/instances" style="display:none">
    <input type="hidden" name="save_instance"  value="1">
    <input type="hidden" name="instance_id"    id="fs_instance_id">
    <input type="hidden" name="nom"            id="fs_nom">
    <input type="hidden" name="description"    id="fs_description">
    <input type="hidden" name="nb_titulaires"  id="fs_nb_titulaires">
    <input type="hidden" name="nb_suppleants"  id="fs_nb_suppleants">
    <input type="hidden" name="quorum"         id="fs_quorum">
    <input type="hidden" name="membres_json"   id="fs_membres_json">
    <div id="fs_managers_container"></div>
</form>

<script>
const allUsers = <?= json_encode($all_users) ?>;
let currentMembers = <?= json_encode(array_map(
    fn($m) => array_merge($m, ['id' => 'db_' . $m['id']]),
    $instance['membres'] ?? []
)) ?>;

// ── Navigation verticale ──
function switchTab(name, btn) {
    document.querySelectorAll('.tab-ki').forEach(t => t.classList.add('d-none'));
    document.querySelectorAll('.v-nav-link').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.remove('d-none');
    btn.classList.add('active');
    const actions = document.getElementById('form-actions');
    if (actions) actions.style.display = name === 'convocations' ? 'none' : 'flex';
}

// ── Validation & soumission ──
function setFieldError(fieldId, errorId, condition) {
    const f = document.getElementById(fieldId), e = document.getElementById(errorId);
    if (condition) { f.classList.add('is-invalid'); e.style.display = 'block'; return true; }
    f.classList.remove('is-invalid'); e.style.display = 'none'; return false;
}

function submitInstanceForm() {
    let err = false;
    err |= setFieldError('nom',          'err-nom',        !document.getElementById('nom').value.trim());
    err |= setFieldError('nb_titulaires','err-titulaires', !document.getElementById('nb_titulaires').value || +document.getElementById('nb_titulaires').value < 1);
    err |= setFieldError('nb_suppleants','err-suppleants',  document.getElementById('nb_suppleants').value === '');
    err |= setFieldError('quorum',       'err-quorum',     !document.getElementById('quorum').value || +document.getElementById('quorum').value < 1);
    if (err) { document.querySelector('.v-nav-link').click(); return; }

    document.getElementById('fs_instance_id').value  = document.querySelector('[name="instance_id"]').value;
    document.getElementById('fs_nom').value           = document.getElementById('nom').value.trim();
    document.getElementById('fs_description').value   = document.getElementById('description').value.trim();
    document.getElementById('fs_nb_titulaires').value = document.getElementById('nb_titulaires').value;
    document.getElementById('fs_nb_suppleants').value = document.getElementById('nb_suppleants').value;
    document.getElementById('fs_quorum').value        = document.getElementById('quorum').value;
    document.getElementById('fs_membres_json').value  = JSON.stringify(currentMembers);

    const cont = document.getElementById('fs_managers_container');
    cont.innerHTML = '';
    selectedManagerIds.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'managers[]'; inp.value = id;
        cont.appendChild(inp);
    });
    document.getElementById('realSubmitForm').submit();
}

// ══════════════════════════════════════════
// GESTIONNAIRES
// ══════════════════════════════════════════
let selectedManagerIds = new Set(<?= json_encode(array_map('intval', array_column($instance['managers'] ?? [], 'id'))) ?>);

function getDisplayName(u) {
    const full = [u.prenom ?? '', u.nom ?? ''].filter(Boolean).join(' ');
    return full || u.username;
}

document.addEventListener('DOMContentLoaded', () => {
    renderManagerPills();

    const searchInput   = document.getElementById('managerSearchInput');
    const searchResults = document.getElementById('managerSearchResults');

    searchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        searchResults.innerHTML = '';
        if (q.length < 1) { searchResults.style.display = 'none'; return; }

        const matches = allUsers.filter(u =>
            !selectedManagerIds.has(u.id) &&
            (
                (u.prenom  && u.prenom.toLowerCase().includes(q))  ||
                (u.nom     && u.nom.toLowerCase().includes(q))     ||
                (u.username && u.username.toLowerCase().includes(q)) ||
                (u.email   && u.email.toLowerCase().includes(q))
            )
        );

        if (matches.length) {
            matches.forEach(u => {
                const div = document.createElement('div');
                div.className = 'search-item px-3 py-2 border-bottom small';
                const display = getDisplayName(u);
                div.innerHTML = `<span class="fw-bold text-dark">${display}</span> <span class="text-muted ms-1">@${u.username}</span>`;
                div.onclick = () => {
                    addManagerPill(u.id);
                    searchInput.value = '';
                    searchResults.style.display = 'none';
                };
                searchResults.appendChild(div);
            });
        } else {
            searchResults.innerHTML = '<div class="px-3 py-2 text-muted small">Aucun résultat</div>';
        }

        // Positionner le dropdown sous le champ, en fixed
        const rect = searchInput.getBoundingClientRect();
        searchResults.style.top   = (rect.bottom + window.scrollY + 4) + 'px';
        searchResults.style.left  = rect.left + 'px';
        searchResults.style.width = rect.width + 'px';
        searchResults.style.display = 'block';
    });

    // Repositionner au scroll
    window.addEventListener('scroll', () => {
        if (searchResults.style.display === 'block') {
            const rect = searchInput.getBoundingClientRect();
            searchResults.style.top  = (rect.bottom + window.scrollY + 4) + 'px';
            searchResults.style.left = rect.left + 'px';
        }
    }, true);

    document.addEventListener('click', e => {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target))
            searchResults.style.display = 'none';
    });

    renderMembersTables();
});

function addManagerPill(id) { selectedManagerIds.add(id); renderManagerPills(); }
function removeManagerPill(id) { selectedManagerIds.delete(id); renderManagerPills(); }

function renderManagerPills() {
    const container = document.getElementById('managersSelectedContainer');
    container.innerHTML = '';
    if (selectedManagerIds.size === 0) {
        container.innerHTML = '<span class="text-muted small align-self-center ms-1">Aucun gestionnaire sélectionné</span>';
        return;
    }
    selectedManagerIds.forEach(id => {
        const user = allUsers.find(u => u.id === id); if (!user) return;
        const pill = document.createElement('span');
        pill.className = 'badge bg-dark text-white d-inline-flex align-items-center user-pill px-3 py-2 fw-normal gap-2';
        pill.innerHTML = `${getDisplayName(user)} <i class="bi bi-x-circle-fill" style="cursor:pointer;" onclick="removeManagerPill(${id})"></i>`;
        container.appendChild(pill);
    });
}


// ══════════════════════════════════════════
// MEMBRES — modal Bootstrap
// ══════════════════════════════════════════
let memberModalInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    memberModalInstance = new bootstrap.Modal(document.getElementById('memberModal'));
});

function openMemberModal(memberId = null) {
    // Reset
    ['m_edit_id','m_nom','m_prenom','m_email','m_qualite','m_matched_user_id'].forEach(id => {
        document.getElementById(id).value = '';
    });
    ['m_nom','m_prenom'].forEach(id => document.getElementById(id).classList.remove('is-invalid'));
    document.getElementById('err-m-nom').style.display = 'none';
    document.getElementById('err-m-prenom').style.display = 'none';
    document.getElementById('m_account_alert').classList.add('d-none');
    document.getElementById('m_no_account_hint').classList.add('d-none');
    document.getElementById('m_link_account').checked = true;
    document.getElementById('m_college').value  = 'administration';
    document.getElementById('m_mandat').value   = 'titulaire';

    if (memberId) {
        const m = currentMembers.find(x => x.id === memberId);
        if (!m) return;
        document.getElementById('m_edit_id').value   = m.id;
        document.getElementById('m_nom').value       = m.nom;
        document.getElementById('m_prenom').value    = m.prenom;
        document.getElementById('m_email').value     = m.email || '';
        document.getElementById('m_qualite').value   = m.qualite || '';
        document.getElementById('m_college').value   = m.college;
        document.getElementById('m_mandat').value    = m.type_mandat;
        document.getElementById('memberModalTitle').innerText  = 'Modifier le membre';
        document.getElementById('memberModalBtnLabel').innerText = 'Mettre à jour';
        if (m.user_id) {
            document.getElementById('m_matched_user_id').value = m.user_id;
            document.getElementById('m_match_name').innerText  = m.linkedName || m.nom;
            document.getElementById('m_account_alert').classList.remove('d-none');
        }
        // Réafficher l'indication email si présent
        if (m.email) checkEmailMatch();
    } else {
        document.getElementById('memberModalTitle').innerText   = 'Nouveau membre';
        document.getElementById('memberModalBtnLabel').innerText = 'Ajouter à la liste';
    }
}

function checkEmailMatch() {
    const email = document.getElementById('m_email').value.trim().toLowerCase();
    const alertBox  = document.getElementById('m_account_alert');
    const noAccount = document.getElementById('m_no_account_hint');

    alertBox.classList.add('d-none');
    noAccount.classList.add('d-none');
    document.getElementById('m_matched_user_id').value = '';

    if (email.length < 3) return;

    const match = allUsers.find(u => u.email && u.email.toLowerCase() === email);
    if (match) {
        document.getElementById('m_match_name').innerText       = match.username;
        document.getElementById('m_matched_user_id').value      = match.id;
        document.getElementById('m_link_account').checked       = true;
        alertBox.classList.remove('d-none');
    } else {
        // Montrer le hint "aucun compte" seulement si l'email semble complet
        if (email.includes('@') && email.includes('.')) {
            noAccount.classList.remove('d-none');
        }
    }
}

function saveMemberToList() {
    const nom    = document.getElementById('m_nom').value.trim();
    const prenom = document.getElementById('m_prenom').value.trim();
    let err = false;
    if (!nom)    { document.getElementById('m_nom').classList.add('is-invalid');    document.getElementById('err-m-nom').style.display='block';    err=true; }
    else         { document.getElementById('m_nom').classList.remove('is-invalid'); document.getElementById('err-m-nom').style.display='none'; }
    if (!prenom) { document.getElementById('m_prenom').classList.add('is-invalid'); document.getElementById('err-m-prenom').style.display='block'; err=true; }
    else         { document.getElementById('m_prenom').classList.remove('is-invalid'); document.getElementById('err-m-prenom').style.display='none'; }
    if (err) return;

    const email      = document.getElementById('m_email').value.trim();
    const qualite    = document.getElementById('m_qualite').value.trim();
    const college    = document.getElementById('m_college').value;
    const type_mandat = document.getElementById('m_mandat').value;
    let user_id = null, linkedName = null;

    const accountAlert = document.getElementById('m_account_alert');
    if (!accountAlert.classList.contains('d-none') &&
        document.getElementById('m_link_account').checked &&
        document.getElementById('m_matched_user_id').value) {
        user_id    = document.getElementById('m_matched_user_id').value;
        linkedName = document.getElementById('m_match_name').innerText;
    }

    const editId = document.getElementById('m_edit_id').value;
    if (editId) {
        const idx = currentMembers.findIndex(m => m.id === editId);
        if (idx > -1) currentMembers[idx] = { id: editId, nom, prenom, email, qualite, college, type_mandat, user_id, linkedName };
    } else {
        currentMembers.push({ id: 'temp_' + Date.now(), nom, prenom, email, qualite, college, type_mandat, user_id, linkedName });
    }

    renderMembersTables();
    bootstrap.Modal.getInstance(document.getElementById('memberModal')).hide();
}

function removeMember(tempId) {
    if (!confirm('Retirer ce membre de la liste ?')) return;
    currentMembers = currentMembers.filter(m => m.id !== tempId);
    renderMembersTables();
}

function renderMembersTables() {
    ['administration','personnel'].forEach(college => {
        const tbody    = document.getElementById('tbody-' + college);
        tbody.innerHTML = '';
        const filtered = currentMembers.filter(m => m.college === college);
        if (!filtered.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted small py-4 opacity-50">Aucun membre dans ce collège</td></tr>';
            return;
        }
        filtered.forEach(m => {
            const tr = document.createElement('tr');
            let emailCell = m.email
                ? `<span class="text-truncate d-inline-block" style="max-width:130px" title="${m.email}">${m.email}</span>`
                : '<span class="text-muted opacity-40">—</span>';
            if (m.user_id) {
                emailCell += ` <i class="bi bi-person-check-fill text-success ms-1" title="Lié : ${m.linkedName || m.nom}" data-bs-toggle="tooltip"></i>`;
            }
            const badge = m.type_mandat === 'titulaire'
                ? `<span class="badge bg-dark fw-normal">Titulaire</span>`
                : `<span class="badge bg-secondary bg-opacity-25 text-dark fw-normal">Suppléant</span>`;
            tr.innerHTML = `
                <td class="fw-bold text-truncate ps-3 py-2">${m.nom.toUpperCase()} ${m.prenom}</td>
                <td class="text-truncate text-muted small">${m.qualite || '—'}</td>
                <td>${badge}</td>
                <td class="small">${emailCell}</td>
                <td class="text-end pe-3">
                    <button type="button" class="btn btn-sm text-primary border-0 p-1 me-1"
                            onclick="openMemberModal('${m.id}')"
                            data-bs-toggle="modal" data-bs-target="#memberModal"
                            title="Modifier"><i class="bi bi-pencil-square"></i></button>
                    <button type="button" class="btn btn-sm text-danger border-0 p-1"
                            onclick="removeMember('${m.id}')" title="Retirer"><i class="bi bi-trash3"></i></button>
                </td>`;
            tbody.appendChild(tr);
        });
    });
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el, { trigger: 'hover' }));
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>