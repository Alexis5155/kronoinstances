<?php
/**
 * KRONOINSTANCES - Installateur SPA
 */

$configPath = '../app/config/config.php';

// Bloquer si déjà installé
if (file_exists($configPath) && !isset($_POST['action'])) {
    die("Le système est déjà installé. Supprimez le dossier 'install' par sécurité.");
}

// --- LOGIQUE AJAX / POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // ACTION A : Vérifier si la base est vide
    if ($_POST['action'] === 'check_db') {
        try {
            $pdo = new PDO("mysql:host=".$_POST['host'], $_POST['dbuser'], $_POST['dbpass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `".$_POST['dbname']."` CHARACTER SET utf8mb4");
            $pdo->exec("USE `".$_POST['dbname']."`");
            $stmt = $pdo->query("SHOW TABLES");
            echo json_encode(['status' => 'success', 'is_empty' => ($stmt->rowCount() === 0)]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ACTION B : Installation finale
    if ($_POST['action'] === 'install_final') {
        try {
            $pdo = new PDO("mysql:host=".$_POST['host'], $_POST['dbuser'], $_POST['dbpass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $dbname = $_POST['dbname'];
            
            if (isset($_POST['force']) && $_POST['force'] == '1') {
                $pdo->exec("DROP DATABASE `$dbname` ");
                $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4");
            }
            $pdo->exec("USE `$dbname` ");

            // SQL
            $sql = file_get_contents('setup.sql');
            $pdo->exec($sql);

            // Admin
            $admin_pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role_id) VALUES (?, ?, ?, 1)");
            $stmt->execute([$_POST['admin_user'], $admin_pass, $_POST['admin_email']]);

            // Nom Commune
            $stmt = $pdo->prepare("UPDATE settings SET s_value = ? WHERE s_key = 'collectivite_nom'");
            $stmt->execute([$_POST['col_nom']]);

            // Config PHP avec SMTP
            $config_content = "<?php\n// Généré le " . date('d/m/Y H:i:s') . "\n"
                . "define('DB_HOST', " . var_export($_POST['host'], true) . ");\n"
                . "define('DB_NAME', " . var_export($_POST['dbname'], true) . ");\n"
                . "define('DB_USER', " . var_export($_POST['dbuser'], true) . ");\n"
                . "define('DB_PASS', " . var_export($_POST['dbpass'] ?? '', true) . ");\n\n"
                . "// Config SMTP\n"
                . "define('MAIL_HOST', " . var_export($_POST['mail_host'] ?? '', true) . ");\n"
                . "define('MAIL_PORT', " . var_export($_POST['mail_port'] ?? '', true) . ");\n"
                . "define('MAIL_USER', " . var_export($_POST['mail_user'] ?? '', true) . ");\n"
                . "define('MAIL_PASS', " . var_export($_POST['mail_pass'] ?? '', true) . ");\n"
                . "define('MAIL_FROM', " . var_export($_POST['mail_from'] ?? '', true) . ");\n";
            file_put_contents($configPath, $config_content);

            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// 1. PRÉREQUIS
$prereqs = [
    'php' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'pdo' => extension_loaded('pdo_mysql'),
    'gd' => extension_loaded('gd'),
    'config_writable' => is_writable('../app/config/'),
    'uploads_writable' => is_writable('../uploads/'),
    'backups_writable' => is_writable('../backups/')
];
$all_ok = !in_array(false, $prereqs);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Installation - KronoInstances</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; color: #444; overflow-x: hidden; }
        .card { border-radius: 15px; border: none; transition: all 0.3s ease; }
        .badge-step { width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; }
        .step-panel { display: none; }
        .step-panel.active { display: block; animation: fadeIn 0.4s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .stepper-item { flex: 1; text-align: center; position: relative; }
        .stepper-item:not(:last-child):after { content: ''; position: absolute; width: 100%; height: 2px; background: #dee2e6; top: 15px; left: 50%; z-index: -1; }
        .stepper-item.active .badge-step { background-color: #0d6efd !important; color: white; }
        .stepper-item.active { font-weight: bold; color: #0d6efd; }
        .onboarding-card:hover { border-color: #0d6efd !important; transform: translateY(-3px); transition: 0.2s; }
        .x-small { font-size: 0.75rem; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            
            <header class="text-center mb-5">
                <h1 class="fw-bold">🔧 KronoInstances</h1>
                <p class="text-muted">Assistant d'installation et de configuration</p>
            </header>

            <div class="d-flex mb-5" id="stepper">
                <div class="stepper-item active" data-step="1"><span class="badge bg-secondary badge-step mb-2">1</span><br><small>Base de données</small></div>
                <div class="stepper-item" data-step="2"><span class="badge bg-secondary badge-step mb-2">2</span><br><small>Compte admin</small></div>
                <div class="stepper-item" data-step="3"><span class="badge bg-secondary badge-step mb-2">3</span><br><small>Personnalisation</small></div>
                <div class="stepper-item" data-step="4"><span class="badge bg-secondary badge-step mb-2">4</span><br><small>Gestion Email</small></div>
                <div class="stepper-item" data-step="5"><span class="badge bg-secondary badge-step mb-2">5</span><br><small>Finalisation</small></div>
            </div>

            <div class="row g-4">
                <div class="col-md-8">
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-5">
                            <div id="alert-container"></div>

                            <form id="masterForm">
                                <!-- ÉTAPE 1 : BDD -->
                                <div class="step-panel active" id="step1">
                                    <h5 class="fw-bold mb-4">Base de données MySQL</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label small fw-bold text-muted">Hôte</label><input type="text" name="host" id="host" class="form-control" placeholder="ex: localhost" required></div>
                                        <div class="col-md-6"><label class="form-label small fw-bold text-muted">Nom de la base</label><input type="text" name="dbname" id="dbname" class="form-control" placeholder="ex: kronoinstances" required></div>
                                        <div class="col-md-6"><label class="form-label small fw-bold text-muted">Utilisateur</label><input type="text" name="dbuser" id="dbuser" class="form-control" placeholder="ex: root" required></div>
                                        <div class="col-md-6"><label class="form-label small fw-bold text-muted">Mot de passe</label><input type="text" name="dbpass" id="dbpass" class="form-control" placeholder="Laisser vide si aucun"></div>
                                    </div>
                                    <div class="mt-4 d-flex justify-content-end">
                                        <button type="button" onclick="validateStep1()" class="btn btn-primary px-4 py-2" <?= !$all_ok ? 'disabled' : '' ?>>Suivant <i class="bi bi-arrow-right ms-1"></i></button>
                                    </div>
                                </div>

                                <!-- ÉTAPE 2 : ADMIN -->
                                <div class="step-panel" id="step2">
                                    <h5 class="fw-bold mb-4">Création du compte administrateur</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label small fw-bold text-muted">Identifiant</label><input type="text" name="admin_user" id="admin_user" class="form-control" placeholder="ex: admin" required></div>
                                        <div class="col-md-6"><label class="form-label small fw-bold text-muted">Email</label><input type="email" name="admin_email" id="admin_email" class="form-control" placeholder="ex: admin@mairie.fr" required></div>
                                        <div class="col-md-12"><label class="form-label small fw-bold text-muted">Mot de passe</label><input type="password" name="admin_pass" id="admin_pass" class="form-control" required></div>
                                    </div>
                                    <div class="mt-4 d-flex justify-content-between">
                                        <button type="button" onclick="goToStep(1)" class="btn btn-outline-secondary px-4">Retour</button>
                                        <button type="button" onclick="validateAdmin()" class="btn btn-primary px-4">Suivant</button>
                                    </div>
                                </div>

                                <!-- ÉTAPE 3 : IDENTITÉ -->
                                <div class="step-panel" id="step3">
                                    <h5 class="fw-bold mb-4">Identité de l'instance</h5>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-muted">Nom de la collectivité / organisme</label>
                                        <input type="text" name="col_nom" id="col_nom" class="form-control form-control-lg" placeholder="ex: Commune de Lille" required>
                                    </div>
                                    <div class="mt-4 d-flex justify-content-between">
                                        <button type="button" onclick="goToStep(2)" class="btn btn-outline-secondary px-4">Retour</button>
                                        <button type="button" onclick="validateIdentity()" class="btn btn-primary px-4">Suivant</button>
                                    </div>
                                </div>

                                <!-- ÉTAPE 4 : SMTP (NOUVEAU) -->
                                <div class="step-panel" id="step4">
                                    <h5 class="fw-bold mb-4">Configuration Email (SMTP)</h5>
                                    <p class="text-muted small mb-3">Nécessaire pour l'envoi des convocations et notifications.</p>
                                    <div class="row g-3">
                                        <div class="col-md-8"><label class="form-label small fw-bold text-muted">Hôte SMTP</label><input type="text" name="mail_host" id="mail_host" class="form-control smtp-field" placeholder="smtp.gmail.com" required></div>
                                        <div class="col-md-4"><label class="form-label small fw-bold text-muted">Port</label><input type="number" name="mail_port" id="mail_port" class="form-control smtp-field" value="587" required></div>
                                        <div class="col-md-6"><label class="form-label small fw-bold text-muted">Utilisateur</label><input type="text" name="mail_user" id="mail_user" class="form-control smtp-field" placeholder="ex: noreply@mairie.fr" required></div>
                                        <div class="col-md-6"><label class="form-label small fw-bold text-muted">Mot de passe</label><input type="password" name="mail_pass" id="mail_pass" class="form-control smtp-field" required></div>
                                        <div class="col-md-12"><label class="form-label small fw-bold text-muted">Email expéditeur (FROM)</label><input type="email" name="mail_from" id="mail_from" class="form-control smtp-field" placeholder="ex: noreply@mairie.fr" required></div>
                                    </div>
                                    <div class="mt-4 d-flex justify-content-between align-items-center">
                                        <button type="button" onclick="goToStep(3)" class="btn btn-outline-secondary px-4">Retour</button>
                                        <div class="d-flex gap-2">
                                            <button type="button" onclick="skipSMTP()" class="btn btn-link text-muted text-decoration-none">Passer cette étape</button>
                                            <button type="button" onclick="validateSMTP()" class="btn btn-primary px-4">Suivant</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- ÉTAPE 5 : FINALISATION -->
                                <div class="step-panel" id="step5">
                                    <h5 class="fw-bold mb-4">Récapitulatif</h5>
                                    <div class="table-responsive mb-4">
                                        <table class="table table-sm table-bordered">
                                            <tbody class="small" id="recapTable"></tbody>
                                        </table>
                                    </div>
                                    <div class="alert alert-info py-2 small"><i class="bi bi-info-circle me-2"></i>L'installation créera la base de données et le fichier de configuration.</div>
                                    <div class="mt-4 d-flex justify-content-between">
                                        <button type="button" onclick="goToStep(4)" class="btn btn-outline-secondary px-4">Retour</button>
                                        <button type="button" id="finalBtn" onclick="runInstallation()" class="btn btn-success px-5 fw-bold">Confirmer l'installation</button>
                                    </div>
                                </div>

                                <div class="step-panel" id="stepSuccess">
                                    <div class="text-center">
                                        <div class="mb-4 text-success"><i class="bi bi-check-circle-fill display-1"></i></div>
                                        <h3 class="fw-bold">Installation terminée !</h3>
                                        <div class="alert alert-danger py-2 small mt-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>Supprimez le dossier <b>/install</b> de votre serveur.</div>
                                        <h6 class="fw-bold mt-5 mb-3 text-start border-bottom pb-2">Premiers pas :</h6>
                                        <div class="row text-start g-3">
                                            <div class="col-md-6"><a href="../login?return=admin/instances" target="_blank" class="text-decoration-none h-100 d-block"><div class="p-3 border rounded shadow-sm bg-white h-100 onboarding-card"><h6 class="fw-bold mb-1 small text-primary"><i class="bi bi-diagram-3 me-2"></i> Instances</h6><p class="x-small text-muted mb-0">Configurez vos CAP, CST et leurs membres.</p></div></a></div>
                                            <div class="col-md-6"><a href="../login?return=admin/users" target="_blank" class="text-decoration-none h-100 d-block"><div class="p-3 border rounded shadow-sm bg-white h-100 onboarding-card"><h6 class="fw-bold mb-1 small text-primary"><i class="bi bi-people me-2"></i> Utilisateurs</h6><p class="x-small text-muted mb-0">Créez les comptes RH et Élus.</p></div></a></div>
                                            <div class="col-md-6"><a href="../login?return=admin/logs" target="_blank" class="text-decoration-none h-100 d-block"><div class="p-3 border rounded shadow-sm bg-white h-100 onboarding-card"><h6 class="fw-bold mb-1 small text-primary"><i class="bi bi-journal-text me-2"></i> Audit Trail</h6><p class="x-small text-muted mb-0">Vérifiez le journal de traçabilité.</p></div></a></div>
                                        </div>
                                        <a href="../login" class="btn btn-dark w-100 py-3 fw-bold mt-5">Accéder au Tableau de bord</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-4 small text-uppercase text-muted">Vérification Système</h6>
                            <div class="small">
                                <div class="d-flex justify-content-between mb-3 pb-2 border-bottom"><span>PHP 8.0+</span><i class="bi <?= $prereqs['php'] ? 'bi-check-lg text-success' : 'bi-x-lg text-danger' ?>"></i></div>
                                <div class="d-flex justify-content-between mb-3 pb-2 border-bottom"><span>PDO MySQL</span><i class="bi <?= $prereqs['pdo'] ? 'bi-check-lg text-success' : 'bi-x-lg text-danger' ?>"></i></div>
                                <div class="d-flex justify-content-between mb-3 pb-2 border-bottom"><span>GD (Captcha)</span><i class="bi <?= $prereqs['gd'] ? 'bi-check-lg text-success' : 'bi-x-lg text-danger' ?>"></i></div>
                                <div class="d-flex justify-content-between mb-3 pb-2 border-bottom"><span>Droit /app/config/</span><i class="bi <?= $prereqs['config_writable'] ? 'bi-check-lg text-success' : 'bi-x-lg text-danger' ?>"></i></div>
                                <div class="d-flex justify-content-between mb-3 pb-2 border-bottom"><span>Droit /uploads/</span><i class="bi <?= $prereqs['uploads_writable'] ? 'bi-check-lg text-success' : 'bi-x-lg text-danger' ?>"></i></div>
                                <div class="d-flex justify-content-between"><span>Droit /backups/</span><i class="bi <?= $prereqs['backups_writable'] ? 'bi-check-lg text-success' : 'bi-x-lg text-danger' ?>"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="card shadow-sm bg-dark text-white p-2">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-info"></i>Concept</h6>
                            <p class="x-small text-white-50 lh-base mb-0">KronoInstances automatise la gestion des instances paritaires (CAP, CST), de la convocation au procès-verbal.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-body p-5 text-center">
        <div class="text-warning mb-4"><i class="bi bi-exclamation-triangle display-4"></i></div>
        <h5 class="fw-bold">Base de données non vierge</h5>
        <p class="text-muted small">Des tables existent déjà dans cette base. Forcer l'installation supprimera définitivement toutes les données existantes.</p>
        <div class="d-grid gap-2 mt-4"><button type="button" onclick="confirmForce()" class="btn btn-warning fw-bold">Écraser et continuer</button><button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button></div>
    </div></div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let forceInstall = 0;

    function goToStep(n) {
        document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('step' + n).classList.add('active');
        document.querySelectorAll('.stepper-item').forEach(i => {
            const step = parseInt(i.getAttribute('data-step'));
            i.classList.remove('active');
            if(step <= n) i.classList.add('active');
        });
    }

    function validateStep1() {
        const host = document.getElementById('host');
        const dbname = document.getElementById('dbname');
        const dbuser = document.getElementById('dbuser');
        if (!host.reportValidity() || !dbname.reportValidity() || !dbuser.reportValidity()) return;
        const formData = new FormData(document.getElementById('masterForm'));
        formData.append('action', 'check_db');
        fetch('index.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                if(!data.is_empty) {
                    const myModal = new bootstrap.Modal(document.getElementById('confirmModal'));
                    myModal.show();
                } else {
                    goToStep(2);
                }
            } else {
                alert("Erreur de connexion : " + data.message);
            }
        });
    }

    function confirmForce() {
        forceInstall = 1;
        bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
        goToStep(2);
    }

    function validateAdmin() {
        const user = document.getElementById('admin_user');
        const email = document.getElementById('admin_email');
        const pass = document.getElementById('admin_pass');
        if (user.reportValidity() && email.reportValidity() && pass.reportValidity()) goToStep(3);
    }

    function validateIdentity() {
        if (document.getElementById('col_nom').reportValidity()) goToStep(4);
    }

    function skipSMTP() {
        document.querySelectorAll('.smtp-field').forEach(f => f.value = '');
        prepareStep5();
    }

    function validateSMTP() {
        let allOk = true;
        document.querySelectorAll('.smtp-field').forEach(f => {
            if(!f.reportValidity()) allOk = false;
        });
        if(allOk) prepareStep5();
    }

    function prepareStep5() {
        const table = document.getElementById('recapTable');
        const smtpInfo = document.getElementById('mail_host').value 
            ? `${document.getElementById('mail_host').value}:${document.getElementById('mail_port').value}` 
            : '<span class="text-danger">Non configuré</span>';

        table.innerHTML = `
            <tr><th class="bg-light" style="width:40%">Serveur BDD</th><td>${document.getElementById('host').value}</td></tr>
            <tr><th class="bg-light">Base de données</th><td>${document.getElementById('dbname').value}</td></tr>
            <tr><th class="bg-light">Administrateur</th><td>${document.getElementById('admin_user').value} (${document.getElementById('admin_email').value})</td></tr>
            <tr><th class="bg-light">Collectivité</th><td>${document.getElementById('col_nom').value}</td></tr>
            <tr><th class="bg-light">Configuration SMTP</th><td>${smtpInfo}</td></tr>
        `;
        goToStep(5);
    }

    function runInstallation() {
        const btn = document.getElementById('finalBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Installation...';
        const formData = new FormData(document.getElementById('masterForm'));
        formData.append('action', 'install_final');
        formData.append('force', forceInstall);
        fetch('index.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('stepper').style.display = 'none';
                document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
                document.getElementById('stepSuccess').classList.add('active');
            } else {
                alert("Erreur fatale : " + data.message);
                btn.disabled = false;
                btn.innerText = 'Confirmer l\'installation';
            }
        });
    }
</script>
</body>
</html>
