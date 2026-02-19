<?php include __DIR__ . '/../header.php'; ?>
<?php use app\models\User; ?>

<style>
    /* 1. Ajustement Navigation Latérale (Fond blanc pour trancher avec le gris du site) */
    .sidebar-box { 
        background: #ffffff; /* Passé en blanc pur */
        border-radius: 15px; 
        padding: 15px; 
        border: 1px solid #dee2e6; /* Bordure plus marquée pour la profondeur */
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    
    .nav-settings .nav-link { 
        color: #495057; font-weight: 600; border-radius: 10px; 
        margin-bottom: 4px; padding: 12px 16px; transition: all 0.2s;
        font-size: 0.9rem; border: 1px solid transparent;
    }
    .nav-settings .nav-link:hover { background-color: #f8f9fa; color: #0d6efd; }
    .nav-settings .nav-link.active { 
        background-color: #e7f1ff !important; /* Bleu très léger pour l'onglet actif */
        color: #0d6efd !important; 
        border-color: #0d6efd !important; 
    }
    
    .sidebar-header { 
        font-size: 0.65rem; text-transform: uppercase; font-weight: 800; 
        color: #adb5bd; letter-spacing: 1.2px; margin-bottom: 10px; margin-top: 20px; padding-left: 16px;
    }

    /* 2. Nouveau Style Changelog (Fond blanc/transparent, style console, respect Markdown) */
    .changelog-box { 
        background: #ffffff; /* Fond blanc comme demandé */
        color: #212529; 
        border-radius: 12px; 
        padding: 20px; 
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; /* Police console */
        font-size: 0.85rem; 
        line-height: 1.6;
        max-height: 400px; 
        overflow-y: auto; 
        border: 1px solid #dee2e6;
        white-space: pre-wrap; /* Crucial pour reproduire la mise en page Markdown (retours à la ligne) */
    }

    .table-settings thead th {
        font-size: 0.65rem; font-weight: 800; text-transform: uppercase;
        color: #adb5bd; letter-spacing: 1px; border-top: none; padding: 12px 16px;
    }
</style>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3 px-2">
        <div>
            <a href="<?= URLROOT ?>/admin" class="text-decoration-none small text-primary fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Administration
            </a>
            <h2 class="fw-bold mt-2 mb-0">Configuration Système ⚙️</h2>
            <p class="text-muted mb-0 small">Gérez les préférences de la collectivité et la maintenance.</p>
        </div>
        <span class="badge bg-white text-dark border px-3 py-2 fw-bold shadow-sm" style="font-size: 0.8rem;">
            Version <?= APP_VERSION ?>
        </span>
    </div>

    <div class="row g-4 px-2">
        <div class="col-lg-3">
            <div class="sidebar-box sticky-top" style="top: 20px;">
                <nav class="nav flex-column nav-settings">
                    
                    <?php if(User::can('manage_system')): ?>
                    <div class="sidebar-header mt-0">Général</div>
                    <a class="nav-link <?= $section === 'general' ? 'active' : '' ?>" href="<?= URLROOT ?>/admin/parametres?section=general">
                        <i class="bi bi-building me-2"></i> Identité
                    </a>
                    <?php endif; ?>

                    <?php if(User::can('manage_services')): ?>
                    <a class="nav-link <?= $section === 'services' ? 'active' : '' ?>" href="<?= URLROOT ?>/admin/parametres?section=services">
                        <i class="bi bi-diagram-3 me-2"></i> Services
                    </a>
                    <?php endif; ?>
                    
                    <?php if(User::can('manage_signataires')): ?>
                    <a class="nav-link <?= $section === 'signataires' ? 'active' : '' ?>" href="<?= URLROOT ?>/admin/parametres?section=signataires">
                        <i class="bi bi-pen me-2"></i> Signataires
                    </a>
                    <?php endif; ?>
                    
                    <?php if(User::can('manage_system')): ?>
                    <div class="sidebar-header">Maintenance</div>
                    <a class="nav-link <?= $section === 'system' ? 'active' : '' ?>" href="<?= URLROOT ?>/admin/parametres?section=system">
                        <i class="bi bi-database-gear me-2"></i> Base de données
                    </a>
                    <a class="nav-link <?= $section === 'integrity' ? 'active' : '' ?>" href="<?= URLROOT ?>/admin/parametres?section=integrity">
                        <i class="bi bi-shield-check me-2"></i> Intégrité fichiers
                    </a>
                    
                    <div class="sidebar-header">Mises à jour</div>
                    <a class="nav-link <?= $section === 'update' ? 'active' : '' ?>" href="<?= URLROOT ?>/admin/parametres?section=update">
                        <i class="bi bi-cloud-arrow-down me-2"></i> Déploiement
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>

        <div class="col-lg-9">
            
            <?php if ($section === 'general' && User::can('manage_system')): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-0"><h5 class="fw-bold mb-0 text-dark">Informations de la collectivité</h5></div>
                    <div class="card-body">
                        <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=general">
                            <input type="hidden" name="action" value="update_general">
                            <div class="mb-4">
                                <label class="form-label text-uppercase text-muted fw-bold small" style="font-size: 0.6rem;">Nom affiché sur les documents officiels</label>
                                <input type="text" class="form-control form-control-lg fw-bold" name="col_name" value="<?= htmlspecialchars($col_nom) ?>" placeholder="ex: Mairie de Houdain" required>
                                <div class="form-text italic x-small">Ce nom sera utilisé pour l'entête des registres d'arrêtés.</div>
                            </div>
                            <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm">Enregistrer</button>
                        </form>
                    </div>
                </div>

            <?php elseif ($section === 'services' && User::can('manage_services')): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0"><h5 class="fw-bold mb-0 text-primary">Nouveau service</h5></div>
                    <div class="card-body">
                        <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=services" class="row g-3">
                            <input type="hidden" name="action" value="add_service">
                            <div class="col-md-9">
                                <input type="text" name="service_nom" class="form-control" placeholder="Nom du service (ex: Services Techniques)" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-dark fw-bold w-100 shadow-sm">Ajouter</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card border-0 shadow-sm overflow-hidden">
                    <table class="table table-hover align-middle mb-0 table-settings">
                        <thead class="table-light"><tr><th class="px-4">Service</th><th class="text-end px-4">Action</th></tr></thead>
                        <tbody class="small">
                            <?php foreach ($services as $srv): ?>
                            <tr>
                                <td class="px-4 fw-bold text-dark"><?= htmlspecialchars($srv['nom']) ?></td>
                                <td class="text-end px-4">
                                    <button type="button" class="btn btn-sm text-danger border-0" data-bs-toggle="modal" data-bs-target="#delSrv<?= $srv['id'] ?>"><i class="bi bi-trash"></i></button>
                                    <div class="modal fade" id="delSrv<?= $srv['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow-lg text-start">
                                                <div class="modal-header border-0 pb-0 ps-4"><h5 class="modal-title fw-bold text-danger">Supprimer le service</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                <div class="modal-body p-4"><p>Voulez-vous supprimer le service <strong><?= htmlspecialchars($srv['nom']) ?></strong> ?<br><small class="text-muted">Attention, les agents rattachés n'auront plus de service.</small></p></div>
                                                <div class="modal-footer border-0 bg-light p-3">
                                                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                                                    <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=services">
                                                        <input type="hidden" name="action" value="delete_service">
                                                        <input type="hidden" name="target_id" value="<?= $srv['id'] ?>">
                                                        <button type="submit" class="btn btn-danger fw-bold px-4 shadow-sm">Confirmer</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($section === 'signataires' && User::can('manage_signataires')): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0"><h5 class="fw-bold mb-0 text-primary">Nouveau signataire</h5></div>
                    <div class="card-body">
                        <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=signataires" class="row g-3">
                            <input type="hidden" name="action" value="add_signataire">
                            <div class="col-md-4">
                                <label class="text-uppercase text-muted fw-bold small mb-1" style="font-size: 0.6rem;">Nom</label>
                                <input type="text" name="sig_nom" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="text-uppercase text-muted fw-bold small mb-1" style="font-size: 0.6rem;">Prénom</label>
                                <input type="text" name="sig_prenom" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="text-uppercase text-muted fw-bold small mb-1" style="font-size: 0.6rem;">Qualité / Fonction</label>
                                <input type="text" name="sig_qualite" class="form-control" placeholder="ex: Maire" required>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm mt-2">Ajouter à la liste</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card border-0 shadow-sm overflow-hidden">
                    <table class="table table-hover align-middle mb-0 table-settings">
                        <thead class="table-light"><tr><th class="px-4">Identité</th><th>Qualité</th><th class="text-end px-4">Action</th></tr></thead>
                        <tbody class="small">
                            <?php foreach ($signataires as $s): ?>
                            <tr>
                                <td class="px-4 fw-bold text-dark"><?= htmlspecialchars($s['prenom']) ?> <?= htmlspecialchars($s['nom']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($s['qualite']) ?></td>
                                <td class="text-end px-4">
                                    <button type="button" class="btn btn-sm text-danger border-0" data-bs-toggle="modal" data-bs-target="#delSig<?= $s['id'] ?>"><i class="bi bi-trash"></i></button>
                                    <div class="modal fade" id="delSig<?= $s['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow-lg text-start">
                                                <div class="modal-header border-0 pb-0 ps-4"><h5 class="modal-title fw-bold text-danger">Supprimer le signataire</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                <div class="modal-body p-4"><p>Retirer <strong><?= htmlspecialchars($s['prenom']) ?> <?= htmlspecialchars($s['nom']) ?></strong> de la liste ?</p></div>
                                                <div class="modal-footer border-0 bg-light p-3">
                                                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                                                    <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=signataires">
                                                        <input type="hidden" name="action" value="delete_signataire">
                                                        <input type="hidden" name="target_id" value="<?= $s['id'] ?>">
                                                        <button type="submit" class="btn btn-danger fw-bold px-4 shadow-sm">Confirmer</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($section === 'system' && User::can('manage_system')): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0"><h5 class="fw-bold mb-0 text-danger"><i class="bi bi-shield-lock me-2"></i>Configuration Base de données</h5></div>
                    <div class="card-body pt-0">
                        <div class="alert alert-warning border-0 small mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Cette section modifie directement le fichier <code>app/config/config.php</code>.
                        </div>
                        <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=system">
                            <input type="hidden" name="action" value="update_system">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="small fw-bold text-muted text-uppercase" style="font-size: 0.6rem;">Hôte</label><input type="text" class="form-control" name="db_host" value="<?= DB_HOST ?>" required></div>
                                <div class="col-md-6"><label class="small fw-bold text-muted text-uppercase" style="font-size: 0.6rem;">Base</label><input type="text" class="form-control" name="db_name" value="<?= DB_NAME ?>" required></div>
                                <div class="col-md-6"><label class="small fw-bold text-muted text-uppercase" style="font-size: 0.6rem;">Utilisateur</label><input type="text" class="form-control" name="db_user" value="<?= DB_USER ?>" required></div>
                                <div class="col-md-6"><label class="small fw-bold text-muted text-uppercase" style="font-size: 0.6rem;">Mot de passe</label><input type="password" class="form-control" name="db_pass" value="<?= DB_PASS ?>"></div>
                            </div>
                            <button type="button" class="btn btn-danger fw-bold mt-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#confirmSystemModal">Appliquer les changements</button>
                            
                            <div class="modal fade" id="confirmSystemModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg">
                                    <div class="modal-header bg-danger text-white border-0"><h5 class="modal-title fw-bold">Action critique</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body p-4 text-center">
                                        <p>Confirmez votre <strong>mot de passe administrateur</strong> pour valider la réécriture du fichier système :</p>
                                        <input type="password" name="confirm_password" class="form-control form-control-lg text-center fw-bold" placeholder="••••••••" required>
                                    </div>
                                    <div class="modal-footer border-0 bg-light px-4"><button type="submit" class="btn btn-danger fw-bold w-100 py-2 shadow-sm">Confirmer la réécriture</button></div>
                                </div></div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-0"><h5 class="fw-bold mb-0 text-dark">Sauvegarde</h5></div>
                    <div class="card-body pt-0">
                        <p class="small text-muted mb-3">Télécharger un export complet de la structure et des données au format SQL.</p>
                        <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=system">
                            <input type="hidden" name="action" value="backup_db">
                            <button type="submit" class="btn btn-dark fw-bold shadow-sm"><i class="bi bi-database-down me-2"></i>Exporter la base (.sql)</button>
                        </form>
                    </div>
                </div>

            <?php elseif ($section === 'integrity' && User::can('manage_system')): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-danger">Fichiers manquants</h5>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= count($diagnostic['bdd_manquants']) ?> anomalie(s)</span>
                    </div>
                    <div class="card-body pt-0">
                        <?php if (empty($diagnostic['bdd_manquants'])): ?>
                            <div class="p-4 bg-light rounded-3 text-success small fw-bold text-center border">✅ Tous les actes ont leur fichier PDF sur le serveur.</div>
                        <?php else: ?>
                            <div class="table-responsive"><table class="table align-middle table-settings mb-0">
                                <thead class="table-light"><tr><th>Référence Acte</th><th>Chemin attendu</th><th class="text-end">Action</th></tr></thead>
                                <tbody class="small">
                                    <?php foreach ($diagnostic['bdd_manquants'] as $m): ?>
                                    <tr>
                                        <td><span class="fw-bold text-dark"><?= $m['num_complet'] ?></span></td>
                                        <td class="text-muted font-monospace" style="font-size: 0.75rem;"><?= $m['fichier_path'] ?></td>
                                        <td class="text-end">
                                            <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=integrity">
                                                <input type="hidden" name="action" value="clean_bdd">
                                                <input type="hidden" name="target_id" value="<?= $m['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 fw-bold">Délier</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-warning">Fichiers orphelins</h5>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?= count($diagnostic['fichiers_fantomes']) ?> fichier(s)</span>
                    </div>
                    <div class="card-body pt-0">
                        <?php if (empty($diagnostic['fichiers_fantomes'])): ?>
                            <div class="p-4 bg-light rounded-3 text-success small fw-bold text-center border">✅ Aucun fichier inutile détecté sur le serveur.</div>
                        <?php else: ?>
                            <div class="table-responsive"><table class="table align-middle table-settings mb-0">
                                <thead class="table-light"><tr><th>Nom du fichier</th><th>Analyse / Match</th><th class="text-end">Actions</th></tr></thead>
                                <tbody class="small">
                                    <?php foreach ($diagnostic['fichiers_fantomes'] as $f): $fId = md5($f['nom']); ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= $f['nom'] ?></td>
                                        <td>
                                            <?php if($f['match']): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold">
                                                    <i class="bi bi-link-45deg"></i> Correspond à : <?= $f['match']['num_complet'] ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted italic x-small">Inconnu</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <?php if($f['match']): ?>
                                                    <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=integrity">
                                                        <input type="hidden" name="action" value="rattach_file">
                                                        <input type="hidden" name="target_file" value="<?= $f['nom'] ?>">
                                                        <input type="hidden" name="target_id" value="<?= $f['match']['id'] ?>">
                                                        <button class="btn btn-sm btn-success fw-bold px-3">Rattacher</button>
                                                    </form>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm text-danger border-0" data-bs-toggle="modal" data-bs-target="#delFile<?= $fId ?>"><i class="bi bi-trash"></i></button>
                                            </div>
                                            <div class="modal fade" id="delFile<?= $fId ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content border-0 shadow-lg text-start">
                                                        <div class="modal-header border-0 pb-0 ps-4"><h5 class="modal-title fw-bold text-danger">Suppression physique</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                        <div class="modal-body p-4"><p>Supprimer définitivement <code><?= $f['nom'] ?></code> du serveur ? Cette action est irréversible.</p></div>
                                                        <div class="modal-footer border-0 bg-light p-3">
                                                            <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Annuler</button>
                                                            <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=integrity">
                                                                <input type="hidden" name="action" value="delete_file">
                                                                <input type="hidden" name="target_file" value="<?= $f['nom'] ?>">
                                                                <button type="submit" class="btn btn-danger fw-bold px-4 shadow-sm">Supprimer</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table></div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($section === 'update' && User::can('manage_system')): ?>
                <?php
                    $check_zip    = class_exists('ZipArchive');
                    $check_fopen  = ini_get('allow_url_fopen');
                    $check_write  = is_writable('./');
                    $system_ready = ($check_zip && $check_fopen && $check_write);
                ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-dark">Gestion des mises à jour</h5>
                        <span class="badge <?= $system_ready ? 'bg-success' : 'bg-danger' ?> text-uppercase fw-bold" style="font-size: 0.65rem;">
                            <?= $system_ready ? 'Système Prêt' : 'Anomalie Prérequis' ?>
                        </span>
                    </div>
                    <div class="card-body pt-0">
                        <div class="p-3 bg-light rounded-3 border mb-4">
                            <h6 class="fw-bold small text-uppercase text-muted mb-3" style="font-size: 0.6rem;">Vérification des prérequis</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center small <?= $check_zip ? 'text-success' : 'text-danger fw-bold' ?>">
                                        <i class="bi <?= $check_zip ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-2"></i> ZipArchive
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center small <?= $check_fopen ? 'text-success' : 'text-danger fw-bold' ?>">
                                        <i class="bi <?= $check_fopen ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-2"></i> allow_url_fopen
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center small <?= $check_write ? 'text-success' : 'text-danger fw-bold' ?>">
                                        <i class="bi <?= $check_write ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-2"></i> Écriture Racine
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border shadow-none bg-light mb-4">
                            <div class="card-body py-2 px-3">
                                <form method="POST" action="<?= URLROOT ?>/admin/parametres?section=update" class="d-flex align-items-center justify-content-between">
                                    <input type="hidden" name="action" value="update_update_settings">
                                    <span class="small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Canal de diffusion :</span>
                                    <div class="d-flex gap-2">
                                        <select name="update_track" class="form-select form-select-sm" style="width: auto; min-width: 120px;">
                                            <option value="main" <?= $update_track === 'main' ? 'selected' : '' ?>>Stable</option>
                                            <option value="beta" <?= $update_track === 'beta' ? 'selected' : '' ?>>Beta</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-dark px-3 fw-bold shadow-sm">Appliquer</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <?php if (!$update_data): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-cloud-slash display-4 text-muted mb-2 d-block"></i>
                                <p class="text-muted small">Connexion aux serveurs GitHub impossible.</p>
                            </div>
                        <?php else: ?>
                            <?php if ($update_data['has_new']): ?>
                                <div class="alert alert-info border-0 shadow-sm d-flex align-items-center p-4 mb-4">
                                    <i class="bi bi-stars fs-3 me-3 text-primary"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">Version <?= $update_data['version'] ?> disponible !</h6>
                                        <p class="mb-0 small">Mettez à jour pour bénéficier des dernières fonctionnalités.</p>
                                    </div>
                                </div>
                                <h6 class="fw-bold small text-uppercase text-muted mb-2" style="font-size: 0.6rem;">Nouveautés :</h6>
                                <div class="changelog-box mb-4"><?= htmlspecialchars($update_data['changelog']) ?></div>
                                <div class="text-center">
                                    <a href="<?= URLROOT ?>/admin/update" class="btn btn-primary btn-lg fw-bold px-5 py-3 shadow <?= !$system_ready ? 'disabled' : '' ?>">
                                        <i class="bi bi-magic me-2"></i>Lancer l'installation
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 border rounded-3 mb-4 bg-white">
                                    <i class="bi bi-patch-check-fill text-success display-2 opacity-25 mb-3 d-block"></i>
                                    <h4 class="fw-bold mb-1">Système à jour</h4>
                                    <p class="text-muted small text-uppercase mb-0">v<?= APP_VERSION ?> • Canal <?= ucfirst($update_track) ?></p>
                                </div>
                                <h6 class="fw-bold small text-uppercase text-muted mb-2" style="font-size: 0.6rem;">Dernier Changelog :</h6>
                                <div class="changelog-box border-0 shadow-none border-start border-4 bg-light px-4 py-3"><?= htmlspecialchars($update_data['changelog']) ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            
            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5 text-center">
                        <i class="bi bi-shield-lock text-muted display-1 opacity-25 mb-4 d-block"></i>
                        <h4 class="fw-bold">Accès réservée</h4>
                        <p class="text-muted mb-0">Vous ne possédez pas les habilitations nécessaires pour cette action.</p>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>