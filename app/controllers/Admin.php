<?php
namespace app\controllers;

use app\core\Controller;
use app\models\User;
use app\models\Instance;
use app\models\Log;
use app\models\Parametre;
use app\config\Permissions;

class Admin extends Controller
{
    // ==========================================
    // 1. CONSTRUCTEUR ET SÉCURITÉ
    // ==========================================

    public function __construct()
    {
        // 1) Auth obligatoire
        if (!isset($_SESSION['user_id'])) {
            $currentPath = $_GET['url'] ?? '';
            $this->redirect('login?return=' . urlencode($currentPath));
            exit;
        }

        // 2) Accès admin si au moins une permission "admin"
        $this->requireAnyPerm(['manage_system', 'manage_users', 'manage_instances', 'view_logs']);

        // 3) CSRF token simple (à injecter dans les formulaires POST)
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
    }

    private function requirePerm(string $perm): void
    {
        if (!User::can($perm)) {
            $this->redirect('dashboard');
            exit;
        }
    }

    private function requireAnyPerm(array $perms): void
    {
        foreach ($perms as $p) {
            if (User::can($p)) return;
        }
        $this->redirect('dashboard');
        exit;
    }

    private function checkCsrf(): void
    {
        $token = $_POST['csrf'] ?? '';
        if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
            http_response_code(403);
            exit('CSRF invalid');
        }
    }

    // ==========================================
    // 2. ACCUEIL ADMINISTRATION
    // ==========================================

    public function index() {
        $userModel = new User();
        $instanceModel = new Instance();
        $logModel = new Log();
        
        // --- Vérification des mises à jour (Via API Github) ---
        $has_update = false;
        $new_v_name = '';

        if (User::can('manage_system')) {
            $opts = ['http' => ['method' => 'GET', 'header' => ['User-Agent: KronoInstances-App'], 'timeout' => 2]];
            $context = stream_context_create($opts);
            
            $url = "https://api.github.com/repos/Alexis5155/kronoinstances/releases/latest";
            
            $res = @file_get_contents($url, false, $context);
            if ($res) {
                $release = json_decode($res, true);
                if (isset($release['tag_name'])) {
                    $new_version = $release['tag_name'];
                    if (defined('APP_VERSION') && version_compare($new_version, APP_VERSION, '>')) {
                        $has_update = true;
                        $new_v_name = $new_version;
                    }
                }
            }
        }

        // --- Statistiques ---
        $count_users = $userModel->countAll();
        $all_instances = $instanceModel->getAll();
        $count_instances = count($all_instances);
        
        $count_membres = 0;
        foreach ($all_instances as $inst) {
            $count_membres += count($instanceModel->getMembres($inst['id']));
        }

        $this->render('admin/index', [
            'title' => 'Administration',
            'count_users' => $count_users,
            'count_instances' => $count_instances,
            'count_membres' => $count_membres,
            'has_update' => $has_update,
            'new_v_name' => $new_v_name
        ]);
    }

    // ==========================================
    // 3. PARAMÈTRES (Section découpée)
    // ==========================================

    public function parametres() {
        // Redirection si aucun droit d'accès
        if (!User::can('manage_system')) {
            setToast("Accès non autorisé.", "danger");
            $this->redirect('admin');
            exit;
        }

        $paramModel = new Parametre();
        $section = $_GET['section'] ?? 'general';

        $allowed_sections = ['general', 'smtp', 'system', 'update'];
        if (!in_array($section, $allowed_sections)) {
            $section = 'general';
        }

        // --- TRAITEMENT POST ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $action = $_POST['action'] ?? '';

            // 1. Général (Base de données)
            if ($action === 'update_general') {
                $colName = trim($_POST['col_name'] ?? '');
                $paramModel->set('collectivite_nom', $colName);
                Log::add('UPDATE_PARAM', "Mise à jour du nom de la collectivité : " . $colName);
                setToast("Nom de la collectivité mis à jour.");
                $this->redirect('admin/parametres?section=general');
            }

            // 2. Base de données (Réécriture config.php)
            if ($action === 'update_system') {
                $db_host = trim($_POST['db_host'] ?? '');
                $db_name = trim($_POST['db_name'] ?? '');
                $db_user = trim($_POST['db_user'] ?? '');
                $db_pass = trim($_POST['db_pass'] ?? ''); // Le mot de passe peut être vide localement
                
                // Vérification du mot de passe de l'utilisateur connecté pour cette action critique
                $confirm_password = $_POST['confirm_password'] ?? '';
                $currentUser = (new User())->getById($_SESSION['user_id']);
                
                if (!password_verify($confirm_password, $currentUser['password'])) {
                    setToast("Mot de passe administrateur incorrect.", "danger");
                    $this->redirect('admin/parametres?section=system');
                    exit;
                }

                $updates = [
                    'DB_HOST' => $db_host,
                    'DB_NAME' => $db_name,
                    'DB_USER' => $db_user,
                    'DB_PASS' => $db_pass
                ];

                if ($this->updateConfigFile($updates)) {
                    Log::add('UPDATE_PARAM', "Mise à jour des identifiants de base de données");
                    setToast("Configuration base de données enregistrée avec succès. Déconnexion requise si les identifiants ont changé.");
                } else {
                    setToast("Erreur d'écriture dans app/config/config.php. Vérifiez les droits.", "danger");
                }
                $this->redirect('admin/parametres?section=system');
            }

            // 3. SMTP (Réécriture config.php)
            if ($action === 'update_smtp') {
                $updates = [
                    'MAIL_HOST' => trim($_POST['smtp_host'] ?? ''),
                    'MAIL_PORT' => trim($_POST['smtp_port'] ?? '587'),
                    'MAIL_USER' => trim($_POST['smtp_user'] ?? ''),
                    'MAIL_FROM' => trim($_POST['smtp_from'] ?? '')
                ];

                // On ne modifie le mot de passe que s'il a été rempli
                if (!empty($_POST['smtp_pass'])) {
                    $updates['MAIL_PASS'] = trim($_POST['smtp_pass']);
                }

                if ($this->updateConfigFile($updates)) {
                    Log::add('UPDATE_PARAM', "Mise à jour de la configuration SMTP");
                    setToast("Configuration email enregistrée avec succès.");
                } else {
                    setToast("Erreur d'écriture dans app/config/config.php. Vérifiez les droits.", "danger");
                }
                $this->redirect('admin/parametres?section=smtp');
            }

            // 4. Test d'envoi Email
            if ($action === 'test_email') {
                $testEmail = trim($_POST['test_email_address'] ?? '');
                
                if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                    $subject = "Test de configuration SMTP - KronoInstances";
                    $body = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                            <h2 style='color: #0d6efd;'>Test réussi ! ✅</h2>
                            <p>Victoire !</p>
                            <p>Si vous lisez ce message, cela signifie que votre configuration SMTP sur <b>KronoInstances</b> fonctionne parfaitement.</p>
                        </div>
                    ";

                    // Utilisation de la classe Mailer
                    $success = \app\core\Mailer::send($testEmail, $subject, $body);

                    if ($success) {
                        setToast("Un email de test a été envoyé avec succès à $testEmail.", "success");
                        Log::add('SYSTEM_TEST', "Email de test envoyé à $testEmail");
                    } else {
                        setToast("Échec de l'envoi. Vérifiez votre configuration SMTP et les journaux de votre serveur.", "danger");
                    }
                } else {
                    setToast("Adresse email de test invalide.", "warning");
                }
                $this->redirect('admin/parametres?section=smtp');
            }

            // 5. Update system...
            if ($action === 'update_update_settings') {
                $track = trim($_POST['update_track'] ?? 'main');
                $paramModel->set('update_track', $track);
                setToast("Canal de mise à jour défini sur : " . ucfirst($track));
                $this->redirect('admin/parametres?section=update');
            }
        }

        // --- PRÉPARATION DES DONNÉES POUR LES VUES ---
        $data = [
            'title' => 'Paramètres Système',
            'section' => $section,
            'csrf' => $_SESSION['csrf'] ?? ''
        ];

        if ($section === 'general') {
            $data['col_nom'] = $paramModel->get('collectivite_nom') ?: 'KronoInstances';
        }
        elseif ($section === 'update') {
            $data['update_track'] = $paramModel->get('update_track') ?: 'main';
            $data['update_data'] = null;

            $opts = ['http' => ['method' => 'GET', 'header' => ['User-Agent: KronoInstances-App'], 'timeout' => 3]];
            $context = stream_context_create($opts);
            
            $url = ($data['update_track'] === 'beta') 
                ? "https://api.github.com/repos/Alexis5155/kronoinstances/releases" 
                : "https://api.github.com/repos/Alexis5155/kronoinstances/releases/latest";

            $res = @file_get_contents($url, false, $context);
            if ($res) {
                $releaseList = json_decode($res, true);
                $release = ($data['update_track'] === 'beta') ? ($releaseList[0] ?? null) : $releaseList;

                if ($release && isset($release['tag_name'])) {
                    $new_version = $release['tag_name'];
                    $current_version = defined('APP_VERSION') ? APP_VERSION : '0.0.0';
                    $data['update_data'] = [
                        'version' => $new_version,
                        'has_new' => version_compare($new_version, $current_version, '>'),
                        'changelog' => $release['body'] ?? 'Aucune note de version.'
                    ];
                }
            }
        }

        $this->render('admin/parametres/_wrapper', $data);
    }

    /**
     * Méthode pour mettre à jour le fichier config.php
     */
    private function updateConfigFile(array $updates): bool {
        $configFile = __DIR__ . '/../config/config.php';
        
        if (!file_exists($configFile) || !is_writable($configFile)) {
            return false;
        }

        $content = file_get_contents($configFile);

        foreach ($updates as $key => $value) {
            // Échapper les apostrophes et antislashs pour ne pas casser le PHP
            $safeValue = addslashes($value);
            
            // Cherche : define('MA_CONSTANTE', 'ancienne_valeur');
            // Remplace par : define('MA_CONSTANTE', 'nouvelle_valeur');
            $pattern = "/define\(\s*['\"]" . preg_quote($key, '/') . "['\"]\s*,\s*['\"].*?['\"]\s*\);/s";
            $replacement = "define('" . $key . "', '" . $safeValue . "');";
            
            // Si la constante existe, on la remplace
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
            } else {
                // Si la constante n'existe pas (ex: ajout lors d'une maj), on l'ajoute à la fin avant la balise PHP fermante
                // Si la balise fermante n'existe pas, on l'ajoute à la toute fin
                $newDefine = "\ndefine('" . $key . "', '" . $safeValue . "');\n";
                if (strpos($content, '?>') !== false) {
                    $content = str_replace('?>', $newDefine . '?>', $content);
                } else {
                    $content .= $newDefine;
                }
            }
        }

        return file_put_contents($configFile, $content) !== false;
    }



    // ==========================================
    // 4. GESTION DES UTILISATEURS
    // ==========================================

    public function users() {
        $this->requirePerm('manage_users');
        $userModel = new User();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
            $this->checkCsrf();
            $targetId = (int)($_POST['user_id'] ?? 0);

            if ($targetId === (int)$_SESSION['user_id']) {
                setToast("Action impossible sur votre propre compte.", "danger");
            } else {
                $userModel->delete($targetId);
                Log::add('DELETE_USER', "Suppression compte ID: " . $targetId);
                setToast("Utilisateur supprimé avec succès.");
            }
            $this->redirect('admin/users');
        }

        $limit = 20;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $totalUsers = $userModel->countAll();
        
        $users = $userModel->getPaginated($limit, ($page - 1) * $limit); 
        foreach ($users as &$u) {
            $u['permissions'] = $userModel->getPermissions((int)$u['id']);
        }

        $this->render('admin/users', [
            'users' => $users,
            'csrf' => $_SESSION['csrf'] ?? '',
            'page' => $page,
            'totalPages' => ceil($totalUsers / $limit),
            'totalUsers' => $totalUsers,
            'limit' => $limit
        ]);
    }

    public function checkEmailMembers() {
        header('Content-Type: application/json');
        $this->requirePerm('manage_users');
        
        $email = $_GET['email'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([]);
            exit;
        }

        $instanceModel = new Instance();
        $orphans = $instanceModel->getOrphanMembresByEmail($email);
        echo json_encode($orphans);
        exit;
    }

    public function userAdd() {
        $this->requirePerm('manage_users');
        $userModel = new User();
        $catalog = \app\config\Permissions::LIST;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $username = trim($_POST['username'] ?? '');
            $prenom   = trim($_POST['prenom'] ?? '');
            $nom      = trim($_POST['nom'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = (string)($_POST['password'] ?? '');

            if ($userModel->findByEmail($email)) {
                setToast("L'adresse mail {$email} est déjà utilisée par un autre compte.", "danger");
                $this->redirect('admin/userAdd');
                exit;
            }

            $slugs = $_POST['permissions'] ?? [];
            if (!is_array($slugs)) $slugs = [];
            $slugs = array_values(array_intersect($slugs, array_keys($catalog)));

            try {
                $newId = $userModel->create(
                    $username,
                    password_hash($password, PASSWORD_DEFAULT),
                    $email,
                    $prenom,
                    $nom
                );
                $userModel->syncPermissions((int)$newId, $slugs);

                if (!empty($_POST['link_membres'])) {
                    $instanceModel = new Instance();
                    foreach ($_POST['link_membres'] as $membreId) {
                        $instanceModel->linkUserToMembre((int)$membreId, $newId);
                    }
                }

                Log::add('CREATE_USER', "Création utilisateur : " . $username);
                setToast("L'utilisateur a été créé avec succès.");
                $this->redirect('admin/users');
                exit;
            } catch (\Exception $e) {
                setToast("Erreur : L'identifiant de connexion existe déjà.", "danger");
            }
        }

        $this->render('admin/user_create', [
            'catalog' => $catalog,
            'csrf' => $_SESSION['csrf'] ?? ''
        ]);
    }

    public function userEdit($id = null) {
        $this->requirePerm('manage_users');
        if (!$id) {
            $this->redirect('admin/users');
            exit;
        }

        $userModel = new User();
        $instanceModel = new Instance();
        $catalog = \app\config\Permissions::LIST;

        $user = $userModel->getById($id);
        if (!$user) {
            setToast("Utilisateur introuvable.", "danger");
            $this->redirect('admin/users');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $email    = trim($_POST['email'] ?? '');
            $prenom   = trim($_POST['prenom'] ?? '');
            $nom      = trim($_POST['nom'] ?? '');

            $existing = $userModel->findByEmail($email);
            if ($existing && $existing['id'] != $id) {
                setToast("L'adresse mail {$email} est déjà utilisée par un autre compte.", "danger");
                $this->redirect('admin/userEdit/' . $id);
                exit;
            }

            $slugs = $_POST['permissions'] ?? [];
            if (!is_array($slugs)) $slugs = [];
            $slugs = array_values(array_intersect($slugs, array_keys($catalog)));

            $userModel->updateAdmin($id, $email, $prenom, $nom);

            if (!empty($_POST['password'])) {
                $userModel->updatePassword($id, password_hash($_POST['password'], PASSWORD_DEFAULT));
            }

            $userModel->syncPermissions($id, $slugs);

            if (!empty($_POST['link_membres'])) {
                foreach ($_POST['link_membres'] as $membreId) {
                    $instanceModel->linkUserToMembre((int)$membreId, $id);
                }
            }

            Log::add('UPDATE_USER', "Modif utilisateur ID : " . $id);
            setToast("Utilisateur mis à jour avec succès.");

            if ((int)$id === (int)$_SESSION['user_id']) {
                $_SESSION['permissions'] = $userModel->getPermissions($id);
            }
            $this->redirect('admin/users');
            exit;
        }

        $user['permissions'] = $userModel->getPermissions($id);
        $allInstances = $instanceModel->getAll();
        $user['instances_manager'] = [];
        $user['instances_membre'] = [];
        
        foreach ($allInstances as $inst) {
            $managers = $instanceModel->getManagers($inst['id']);
            if (in_array($id, $managers)) $user['instances_manager'][] = $inst;
            
            $membres = $instanceModel->getMembres($inst['id']);
            foreach ($membres as $m) {
                if ($m['user_id'] == $id) {
                    $user['instances_membre'][] = $inst;
                    break;
                }
            }
        }
        $orphanMembres = $instanceModel->getOrphanMembresByEmail($user['email']);

        $this->render('admin/user_edit', [
            'u' => $user,
            'catalog' => $catalog,
            'orphanMembres' => $orphanMembres,
            'csrf' => $_SESSION['csrf'] ?? ''
        ]);
    }

    // ==========================================
    // 5. GESTION DES INSTANCES
    // ==========================================

    public function instances() {
        $instanceModel = new Instance();
        $userModel = new User();

        if (isset($_GET['delete_id'])) {
            $targetId = (int)$_GET['delete_id'];
            $inst = $instanceModel->getById($targetId);
            if ($inst) {
                $instanceModel->delete($targetId);
                Log::add('DELETE_INSTANCE', "Suppression de l'instance : " . $inst['nom']);
                setToast("Instance supprimée avec succès.");
            }
            $this->redirect('admin/instances');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_instance'])) {
            $id = $_POST['instance_id'] ?? null;
            $nom = trim($_POST['nom']);
            $desc = trim($_POST['description'] ?? '');
            $nbTit = (int)($_POST['nb_titulaires'] ?? 0);
            $nbSup = (int)($_POST['nb_suppleants'] ?? 0);
            $quorum = (int)($_POST['quorum'] ?? 0);

            $managers = $_POST['managers'] ?? [];
            
            $membresJson = $_POST['membres_json'] ?? '[]';
            $membresArray = json_decode($membresJson, true);
            if (!is_array($membresArray)) {
                $membresArray = [];
            }

            if (!empty($nom)) {
                if (empty($id)) {
                    $newId = $instanceModel->create($nom, $desc, $nbTit, $nbSup, $quorum);
                    if ($newId) {
                        $instanceModel->setManagers($newId, $managers);
                        $instanceModel->setMembres($newId, $membresArray);
                        Log::add('CREATE_INSTANCE', "Création de l'instance : " . $nom);
                        setToast("Instance créée avec succès !");
                    } else {
                        setToast("Erreur lors de la création de l'instance.", "danger");
                    }
                } else {
                    $instanceModel->update($id, $nom, $desc, $nbTit, $nbSup, $quorum);
                    $instanceModel->setManagers($id, $managers);
                    $instanceModel->setMembres($id, $membresArray);
                    Log::add('UPDATE_INSTANCE', "Modification de l'instance : " . $nom);
                    setToast("Instance mise à jour.");
                }
            } else {
                setToast("Le nom de l'instance est obligatoire.", "warning");
            }
            $this->redirect('admin/instances');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_modele'])) {
            $instanceId = (int)$_POST['instance_id'];
            if (isset($_FILES['modele_odt']) && $_FILES['modele_odt']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['modele_odt']['name'], PATHINFO_EXTENSION));
                if ($ext !== 'odt') {
                    setToast("Le modèle doit obligatoirement être un fichier au format .odt", "danger");
                } else {
                    $uploadDir = 'uploads/modeles/';
                    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
                    
                    $destPath = $uploadDir . 'modele_instance_' . $instanceId . '.odt';
                    if (move_uploaded_file($_FILES['modele_odt']['tmp_name'], $destPath)) {
                        Log::add('UPDATE_INSTANCE', "Upload du modèle de convocation pour l'instance ID: " . $instanceId);
                        setToast("Le modèle de convocation a été enregistré avec succès.");
                    } else {
                        setToast("Erreur système lors de l'enregistrement du fichier.", "danger");
                    }
                }
            }
            $this->redirect('admin/instances');
        }
        
        if (isset($_GET['delete_modele_id'])) {
            $targetId = (int)$_GET['delete_modele_id'];
            $path = 'uploads/modeles/modele_instance_' . $targetId . '.odt';
            if (file_exists($path)) {
                unlink($path);
                Log::add('UPDATE_INSTANCE', "Suppression du modèle de convocation pour l'instance ID: " . $targetId);
                setToast("Modèle de convocation supprimé.");
            }
            $this->redirect('admin/instances');
        }

        $instances = $instanceModel->getAll();
        foreach ($instances as &$inst) {
            $inst['managers'] = $instanceModel->getManagers($inst['id']);
            $inst['membres'] = $instanceModel->getMembres($inst['id']);
        }

        $this->render('admin/instances', [
            'instances' => $instances,
            'all_users' => $userModel->getList() 
        ]);
    }

    // ==========================================
    // 6. LOGS SYSTÈME
    // ==========================================

    public function logs()
    {
        $this->requirePerm('view_logs');

        $logModel = new Log();
        $limit = 50;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $filters = ['search' => $_GET['search'] ?? ''];

        $totalLogs = $logModel->countFiltered($filters);

        $this->render('admin/logs', [
            'logs'       => $logModel->getFiltered($filters, $limit, ($page - 1) * $limit),
            'totalLogs'  => $totalLogs,
            'totalPages' => ceil($totalLogs / $limit),
            'page'       => $page,
            'filters'    => $filters
        ]);
    }
}
