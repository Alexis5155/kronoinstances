<?php
namespace app\controllers;

use app\core\Controller;
use app\models\User;
use app\models\Instance;
use app\models\Log;
use app\models\Parametre;
use app\config\Permissions;
use app\core\Database;

class Admin extends Controller
{
    // --- Sécurité / initialisation ---

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            $currentPath = $_GET['url'] ?? '';
            $this->redirect('login?return=' . urlencode($currentPath));
            exit;
        }
        $this->requireAnyPerm(['manage_system', 'manage_users', 'manage_instances', 'view_logs']);
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
    }

    private function requirePerm(string $perm): void
    {
        if (!User::can($perm)) { $this->redirect('dashboard'); exit; }
    }

    private function requireAnyPerm(array $perms): void
    {
        foreach ($perms as $p) { if (User::can($p)) return; }
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

    /**
     * Récupère la dernière release GitHub (main ou beta).
     * Mutualisé entre index() et parametres().
     */
    private function fetchLatestRelease(string $track = 'main'): ?array
    {
        $opts = ['http' => ['method' => 'GET', 'header' => ['User-Agent: KronoInstances-App'], 'timeout' => 3]];
        $url  = ($track === 'beta')
            ? 'https://api.github.com/repos/Alexis5155/kronoinstances/releases'
            : 'https://api.github.com/repos/Alexis5155/kronoinstances/releases/latest';

        $res = @file_get_contents($url, false, stream_context_create($opts));
        if (!$res) return null;

        $data    = json_decode($res, true);
        $release = ($track === 'beta') ? ($data[0] ?? null) : $data;
        return ($release && isset($release['tag_name'])) ? $release : null;
    }

    /**
     * Filtre un tableau de slugs de permissions contre le catalogue autorisé.
     */
    private function sanitizeSlugs(array $raw): array
    {
        return array_values(array_intersect($raw, array_keys(Permissions::LIST)));
    }

    /**
     * Passe un compte en 'active', avec validation d'email optionnelle.
     */
    private function activateUser(int $id, bool $verifyEmail = false): void
    {
        $cols = $verifyEmail ? "status = 'active', email_verified_at = NOW()" : "status = 'active'";
        Database::getConnection()->prepare("UPDATE users SET $cols WHERE id = :id")->execute(['id' => $id]);
    }


    // --- Accueil ---

    public function index()
    {
        $userModel     = new User();
        $instanceModel = new Instance();

        $has_update = false;
        $new_v_name = '';

        if (User::can('manage_system')) {
            $release = $this->fetchLatestRelease();
            if ($release && defined('APP_VERSION') && version_compare($release['tag_name'], APP_VERSION, '>')) {
                $has_update = true;
                $new_v_name = $release['tag_name'];
            }
        }

        $count_pending = 0;
        if (User::can('manage_users')) {
            $stmt = Database::getConnection()->query("SELECT COUNT(*) FROM users WHERE status = 'pending_approval'");
            $count_pending = (int)$stmt->fetchColumn();
        }

        $all_instances = $instanceModel->getAll();
        $count_membres = 0;
        foreach ($all_instances as $inst) {
            $count_membres += count($instanceModel->getMembres($inst['id']));
        }

        $this->render('admin/index', [
            'title'           => 'Administration',
            'count_users'     => $userModel->countAll(),
            'count_instances' => count($all_instances),
            'count_membres'   => $count_membres,
            'has_update'      => $has_update,
            'new_v_name'      => $new_v_name,
            'count_pending'   => $count_pending,
        ]);
    }


    // --- Paramètres système ---

    public function parametres()
    {
        if (!User::can('manage_system')) {
            setToast("Accès non autorisé.", "danger");
            $this->redirect('admin');
            exit;
        }

        $paramModel = new Parametre();
        $allowed    = ['general', 'smtp', 'connexion', 'system', 'update'];
        $section    = in_array($_GET['section'] ?? '', $allowed) ? $_GET['section'] : 'general';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $action = $_POST['action'] ?? '';

            if ($action === 'update_general') {
                $colName = trim($_POST['col_name'] ?? '');
                $paramModel->set('collectivite_nom', $colName);
                Log::add('UPDATE_PARAM', "Mise à jour du nom de la collectivité : " . $colName);
                setToast("Nom de la collectivité mis à jour.");
                $this->redirect('admin/parametres?section=general');
            }

            if ($action === 'update_system') {
                $confirm = $_POST['confirm_password'] ?? '';
                $current = (new User())->getById($_SESSION['user_id']);
                if (!password_verify($confirm, $current['password'])) {
                    setToast("Mot de passe administrateur incorrect.", "danger");
                    $this->redirect('admin/parametres?section=system');
                    exit;
                }
                $updates = [
                    'DB_HOST' => trim($_POST['db_host'] ?? ''),
                    'DB_NAME' => trim($_POST['db_name'] ?? ''),
                    'DB_USER' => trim($_POST['db_user'] ?? ''),
                    'DB_PASS' => trim($_POST['db_pass'] ?? ''), // peut être vide en local
                ];
                if ($this->updateConfigFile($updates)) {
                    Log::add('UPDATE_PARAM', "Mise à jour des identifiants de base de données");
                    setToast("Configuration base de données enregistrée avec succès. Déconnexion requise si les identifiants ont changé.");
                } else {
                    setToast("Erreur d'écriture dans app/config/config.php. Vérifiez les droits.", "danger");
                }
                $this->redirect('admin/parametres?section=system');
            }

            if ($action === 'update_smtp') {
                $updates = [
                    'MAIL_HOST' => trim($_POST['smtp_host'] ?? ''),
                    'MAIL_PORT' => trim($_POST['smtp_port'] ?? '587'),
                    'MAIL_USER' => trim($_POST['smtp_user'] ?? ''),
                    'MAIL_FROM' => trim($_POST['smtp_from'] ?? ''),
                ];
                // mot de passe SMTP seulement si renseigné
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

            if ($action === 'test_email') {
                $testEmail = trim($_POST['test_email_address'] ?? '');
                if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                    $subject = "Test de configuration SMTP - KronoInstances";
                    $body    = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                            <h2 style='color: #0d6efd;'>Test réussi ! ✅</h2>
                            <p>Victoire !</p>
                            <p>Si vous lisez ce message, cela signifie que votre configuration SMTP sur <b>KronoInstances</b> fonctionne parfaitement.</p>
                        </div>
                    ";
                    if (\app\core\Mailer::send($testEmail, $subject, $body)) {
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

            if ($action === 'update_update_settings') {
                $track = trim($_POST['update_track'] ?? 'main');
                $paramModel->set('update_track', $track);
                setToast("Canal de mise à jour défini sur : " . ucfirst($track));
                $this->redirect('admin/parametres?section=update');
            }
        }

        $data = [
            'title'   => 'Paramètres Système',
            'section' => $section,
            'csrf'    => $_SESSION['csrf'] ?? '',
        ];

        if ($section === 'general') {
            $data['col_nom'] = $paramModel->get('collectivite_nom') ?: 'KronoInstances';
        } elseif ($section === 'update') {
            $track               = $paramModel->get('update_track') ?: 'main';
            $data['update_track'] = $track;
            $data['update_data']  = null;
            $release = $this->fetchLatestRelease($track);
            if ($release) {
                $data['update_data'] = [
                    'version'   => $release['tag_name'],
                    'has_new'   => version_compare($release['tag_name'], defined('APP_VERSION') ? APP_VERSION : '0.0.0', '>'),
                    'changelog' => $release['body'] ?? 'Aucune note de version.',
                ];
            }
        }

        $this->render('admin/parametres/_wrapper', $data);
    }

    private function updateConfigFile(array $updates): bool
    {
        $configFile = __DIR__ . '/../config/config.php';
        if (!file_exists($configFile) || !is_writable($configFile)) return false;

        $content = file_get_contents($configFile);
        foreach ($updates as $key => $value) {
            $safeValue   = addslashes($value);
            $pattern     = "/define\(\s*['\"]" . preg_quote($key, '/') . "['\"]\s*,\s*['\"].*?['\"]\s*\);/s";
            $replacement = "define('$key', '$safeValue');";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
            } else {
                // constante absente : ajout avant '>?' ou en fin de fichier
                $newDefine = "\ndefine('$key', '$safeValue');\n";
                $content   = strpos($content, '?>') !== false
                    ? str_replace('?>', $newDefine . '?>', $content)
                    : $content . $newDefine;
            }
        }
        return file_put_contents($configFile, $content) !== false;
    }

    // --- Utilisateurs ---

    public function users()
    {
        $this->requirePerm('manage_users');
        $userModel = new User();
        $db        = Database::getConnection();

        $count_pending = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'pending_approval'")->fetchColumn();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $targetId = (int)($_POST['user_id'] ?? 0);

            if (isset($_POST['approve_user']) && $targetId > 0) {
                $this->activateUser($targetId);
                Log::add('APPROVE_USER', "Approbation du compte utilisateur ID: " . $targetId);
                setToast("Le compte a été approuvé avec succès.");
                $_SESSION['open_pending_modal'] = true;
                $this->redirect('admin/users');
            }

            if (isset($_POST['delete_user'])) {
                if ($targetId === (int)$_SESSION['user_id']) {
                    setToast("Action impossible sur votre propre compte.", "danger");
                } else {
                    $userModel->delete($targetId);
                    Log::add('DELETE_USER', "Suppression compte ID: " . $targetId);
                    setToast("Utilisateur supprimé avec succès.");
                    if (isset($_POST['from_pending'])) {
                        $_SESSION['open_pending_modal'] = true;
                    }
                }
                $this->redirect('admin/users');
            }
        }

        $limit      = 20;
        $page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $totalUsers = $userModel->countAll();
        $users      = $userModel->getPaginated($limit, ($page - 1) * $limit);
        foreach ($users as &$u) {
            $u['permissions'] = $userModel->getPermissions((int)$u['id']);
        }

        $pending_limit = 10;
        $pending_page  = isset($_GET['pending_page']) ? max(1, (int)$_GET['pending_page']) : 1;
        $stmtList = $db->prepare("
            SELECT id, username, email, prenom, nom, created_at
            FROM users WHERE status = 'pending_approval'
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmtList->bindValue(':limit',  $pending_limit, \PDO::PARAM_INT);
        $stmtList->bindValue(':offset', ($pending_page - 1) * $pending_limit, \PDO::PARAM_INT);
        $stmtList->execute();

        $open_pending_modal = (bool)($_SESSION['open_pending_modal'] ?? false);
        unset($_SESSION['open_pending_modal']);

        $this->render('admin/users', [
            'users'              => $users,
            'csrf'               => $_SESSION['csrf'] ?? '',
            'page'               => $page,
            'totalPages'         => ceil($totalUsers / $limit),
            'totalUsers'         => $totalUsers,
            'limit'              => $limit,
            'count_pending'      => $count_pending,
            'pending_users'      => $stmtList->fetchAll(\PDO::FETCH_ASSOC),
            'pending_total'      => $count_pending,
            'pending_page'       => $pending_page,
            'pending_pages'      => ceil($count_pending / $pending_limit),
            'open_pending_modal' => $open_pending_modal,
        ]);
    }

    public function checkEmailMembers()
    {
        header('Content-Type: application/json');
        $this->requirePerm('manage_users');
        $email = $_GET['email'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode([]); exit; }
        echo json_encode((new Instance())->getOrphanMembresByEmail($email));
        exit;
    }

    public function userAdd()
    {
        $this->requirePerm('manage_users');
        $userModel = new User();
        $catalog   = Permissions::LIST;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();
            $username = trim($_POST['username'] ?? '');
            $prenom   = trim($_POST['prenom']   ?? '');
            $nom      = trim($_POST['nom']      ?? '');
            $email    = trim($_POST['email']    ?? '');
            $password = (string)($_POST['password'] ?? '');

            $status = $_POST['status'] ?? 'active';
            if (!in_array($status, ['active', 'pending_email', 'pending_approval', 'inactive'])) {
                $status = 'active';
            }

            if ($userModel->findByEmail($email)) {
                setToast("L'adresse mail {$email} est déjà utilisée par un autre compte.", "danger");
                $this->redirect('admin/userAdd');
                exit;
            }

            $slugs = $this->sanitizeSlugs($_POST['permissions'] ?? []);

            try {
                $newId = $userModel->create(
                    $username,
                    password_hash($password, PASSWORD_DEFAULT),
                    $email, $prenom, $nom,
                    $status // Passage du statut
                );
                $userModel->syncPermissions((int)$newId, $slugs);

                if (!empty($_POST['link_membres'])) {
                    $instanceModel = new Instance();
                    foreach ($_POST['link_membres'] as $membreId) {
                        $instanceModel->linkUserToMembre((int)$membreId, $newId);
                    }
                }

                Log::add('CREATE_USER', "Création utilisateur : " . $username . " (statut: $status)");
                setToast("L'utilisateur a été créé avec succès.");
                $this->redirect('admin/users');
                exit;
            } catch (\Exception $e) {
                setToast("Erreur : L'identifiant de connexion existe déjà.", "danger");
            }
        }

        $this->render('admin/user_create', [
            'catalog' => $catalog,
            'csrf'    => $_SESSION['csrf'] ?? '',
        ]);
    }

    public function userEdit($id = null)
    {
        $this->requirePerm('manage_users');
        if (!$id) { $this->redirect('admin/users'); exit; }

        $userModel     = new User();
        $instanceModel = new Instance();
        $catalog       = Permissions::LIST;

        $user = $userModel->getById($id);
        if (!$user) {
            setToast("Utilisateur introuvable.", "danger");
            $this->redirect('admin/users');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->checkCsrf();

            if (isset($_POST['approve_user'])) {
                $this->activateUser((int)$id);
                Log::add('APPROVE_USER', "Approbation du compte utilisateur ID: " . $id);
                setToast("Le compte a été approuvé avec succès.");
                $this->redirect('admin/userEdit/' . $id);
                exit;
            }

            if (isset($_POST['force_verify_email'])) {
                $this->activateUser((int)$id, true);
                Log::add('VERIFY_EMAIL', "Validation forcée de l'e-mail pour l'utilisateur ID: " . $id);
                setToast("L'adresse e-mail a été validée de force.");
                $this->redirect('admin/userEdit/' . $id);
                exit;
            }

            // Mise à jour générale (formulaire principal)
            $email  = trim($_POST['email']  ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $nom    = trim($_POST['nom']    ?? '');

            $existing = $userModel->findByEmail($email);
            if ($existing && $existing['id'] != $id) {
                setToast("L'adresse mail {$email} est déjà utilisée par un autre compte.", "danger");
                $this->redirect('admin/userEdit/' . $id);
                exit;
            }

            $slugs = $this->sanitizeSlugs($_POST['permissions'] ?? []);
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

        $user['permissions']       = $userModel->getPermissions($id);
        $user['instances_manager'] = [];
        $user['instances_membre']  = [];

        foreach ($instanceModel->getAll() as $inst) {
            if (in_array($id, $instanceModel->getManagers($inst['id']))) {
                $user['instances_manager'][] = $inst;
            }
            foreach ($instanceModel->getMembres($inst['id']) as $m) {
                if ($m['user_id'] == $id) { $user['instances_membre'][] = $inst; break; }
            }
        }

        $this->render('admin/user_edit', [
            'u'             => $user,
            'catalog'       => $catalog,
            'orphanMembres' => $instanceModel->getOrphanMembresByEmail($user['email']),
            'csrf'          => $_SESSION['csrf'] ?? '',
        ]);
    }


    // --- Instances ---

    public function instances()
    {
        $instanceModel = new Instance();
        $userModel     = new User();

        if (isset($_GET['delete_id'])) {
            $targetId = (int)$_GET['delete_id'];
            $inst     = $instanceModel->getById($targetId);
            if ($inst) {
                $instanceModel->delete($targetId);
                Log::add('DELETE_INSTANCE', "Suppression de l'instance : " . $inst['nom']);
                setToast("Instance supprimée avec succès.");
            }
            $this->redirect('admin/instances');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_instance'])) {
            $id           = $_POST['instance_id'] ?? null;
            $nom          = trim($_POST['nom']);
            $desc         = trim($_POST['description'] ?? '');
            $nbTit        = (int)($_POST['nb_titulaires'] ?? 0);
            $nbSup        = (int)($_POST['nb_suppleants'] ?? 0);
            $quorum       = (int)($_POST['quorum'] ?? 0);
            $managers     = $_POST['managers'] ?? [];
            $membresArray = json_decode($_POST['membres_json'] ?? '[]', true);
            if (!is_array($membresArray)) $membresArray = [];

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
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
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
            $path     = 'uploads/modeles/modele_instance_' . $targetId . '.odt';
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
            $inst['membres']  = $instanceModel->getMembres($inst['id']);
        }

        $this->render('admin/instances', [
            'instances' => $instances,
            'all_users' => $userModel->getList(),
        ]);
    }


    // --- Logs ---

    public function logs()
    {
        $this->requirePerm('view_logs');
        $logModel  = new Log();
        $limit     = 50;
        $page      = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $filters   = ['search' => $_GET['search'] ?? ''];
        $totalLogs = $logModel->countFiltered($filters);

        $this->render('admin/logs', [
            'logs'       => $logModel->getFiltered($filters, $limit, ($page - 1) * $limit),
            'totalLogs'  => $totalLogs,
            'totalPages' => ceil($totalLogs / $limit),
            'page'       => $page,
            'filters'    => $filters,
        ]);
    }
}
