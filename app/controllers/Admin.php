<?php
namespace app\controllers;

use app\core\Controller;
use app\models\User;
use app\models\Instance;
use app\models\Log;
use app\config\Permissions;

class Admin extends Controller
{
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

    public function index()
    {
        // Ici, on autorise toute personne ayant accès au panel admin
        $userModel = new User();
        $instanceModel = new Instance();
        $logModel = new Log();

        $count_users = $userModel->countAll();
        $count_instances = count($instanceModel->getAll());
        $latest_logs = $logModel->getFiltered([], 5, 0);

        $this->render('admin/index', [
            'title' => 'Administration',
            'count_users' => $count_users,
            'count_instances' => $count_instances,
            'latest_logs' => $latest_logs
        ]);
    }

    /**
     * Liste des utilisateurs
     */
    public function users() {
        $this->requirePerm('manage_users');
        $userModel = new User();

        // --- SUPPRESSION ---
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

    /**
     * API AJAX : Vérifier si l'email correspond à des membres d'instance
     */
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

    /**
     * Page de création et modification d'utilisateur
     */
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

            // 1. Vérification de l'unicité de l'email
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

            // 1. Vérification de l'unicité de l'email (en excluant l'utilisateur actuel)
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



    /**
     * Gestion des Instances
     */
    public function instances() {
        // Sécurité : Vérifiez ici les droits nécessaires si besoin (ex: User::can('manage_instances'))

        $instanceModel = new Instance();
        $userModel = new User();

        // --- SUPPRESSION ---
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

        // --- SAUVEGARDE UNIQUE (Création ou Édition + Managers + Membres) ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_instance'])) {
            $id = $_POST['instance_id'] ?? null;
            $nom = trim($_POST['nom']);
            $desc = trim($_POST['description'] ?? '');
            $nbTit = (int)($_POST['nb_titulaires'] ?? 0);
            $nbSup = (int)($_POST['nb_suppleants'] ?? 0);
            $quorum = (int)($_POST['quorum'] ?? 0);

            // Récupération des tableaux
            $managers = $_POST['managers'] ?? []; // Tableau d'IDs issus des capsules
            
            // Décodage du JSON contenant le tableau détaillé des membres
            $membresJson = $_POST['membres_json'] ?? '[]';
            $membresArray = json_decode($membresJson, true);
            if (!is_array($membresArray)) {
                $membresArray = [];
            }

            if (!empty($nom)) {
                if (empty($id)) {
                    // CRÉATION
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
                    // MISE À JOUR
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

        // --- UPLOAD DU MODÈLE DE CONVOCATION (.ODT) ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_modele'])) {
            $instanceId = (int)$_POST['instance_id'];
            if (isset($_FILES['modele_odt']) && $_FILES['modele_odt']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['modele_odt']['name'], PATHINFO_EXTENSION));
                if ($ext !== 'odt') {
                    setToast("Le modèle doit obligatoirement être un fichier au format .odt", "danger");
                } else {
                    $uploadDir = 'uploads/modeles/';
                    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
                    
                    // On force ce nom précis pour s'affranchir de la base de données
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
        
        // --- SUPPRESSION DU MODÈLE DE CONVOCATION ---
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

        // --- PRÉPARATION DES DONNÉES POUR LA VUE ---
        $instances = $instanceModel->getAll();
        
        foreach ($instances as &$inst) {
            $inst['managers'] = $instanceModel->getManagers($inst['id']);
            $inst['membres'] = $instanceModel->getMembres($inst['id']);
        }

        $this->render('admin/instances', [
            'instances' => $instances,
            'all_users' => $userModel->getList() // ou autre méthode compatible
        ]);
    }


    /**
     * Logs Système
     */

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
