<?php
namespace app\controllers;

use app\core\Controller;
use app\models\User;
use app\models\Instance;
use app\models\Parametre;
use app\models\Log;
// On supprime Arrete, Signataire, Service, Role (si non utilisé)

class Admin extends Controller {

    public function __construct() {
        // Redirection si non connecté
        if (!isset($_SESSION['user_id'])) {
            $currentPath = $_GET['url'] ?? ''; 
            $this->redirect('login?return=' . urlencode($currentPath));
            exit;
        }
        
        // Sécurité globale : Seul l'admin (Role ID 1) accède à ce contrôleur
        // Ou via permissions si vous préférez
        if (!User::can('manage_system') && !User::hasPower(100)) {
             $this->redirect('dashboard');
             exit;
        }
    }

    public function index() {
        $userModel = new User();
        $instanceModel = new Instance();
        $logModel = new Log();

        // Statistiques pour l'accueil admin
        $count_users = $userModel->countAll();
        $count_instances = count($instanceModel->getAll());
        
        // Derniers logs
        $latest_logs = $logModel->getFiltered([], 5, 0);

        $this->render('admin/index', [
            'title' => 'Administration',
            'count_users' => $count_users,
            'count_instances' => $count_instances,
            'latest_logs' => $latest_logs
        ]);
    }

    /**
     * Gestion des Utilisateurs
     */
    public function users() {
        $userModel = new User();

        // --- SUPPRESSION ---
        if (isset($_GET['delete_id'])) {
            $targetId = $_GET['delete_id'];
            
            // Protection : on ne se supprime pas soi-même
            if ($targetId == $_SESSION['user_id']) {
                $_SESSION['flash_error'] = "Action impossible sur votre propre compte.";
            } else {
                $userModel->delete($targetId);
                Log::add('DELETE_USER', "Suppression compte ID: " . $targetId);
                $_SESSION['flash_success'] = "Utilisateur supprimé.";
            }
            $this->redirect('admin/users');
        }

        // --- AJOUT / ÉDITION ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // AJOUT
            if (isset($_POST['add_user'])) {
                try {
                    $roleId = $_POST['role_id'];
                    $userModel->create(
                        $_POST['username'], 
                        password_hash($_POST['password'], PASSWORD_DEFAULT), 
                        $_POST['email'], 
                        $roleId
                    );
                    Log::add('CREATE_USER', "Création utilisateur : " . $_POST['username']);
                    $_SESSION['flash_success'] = "Utilisateur créé.";
                } catch (\Exception $e) { 
                    $_SESSION['flash_error'] = "Erreur : L'identifiant existe déjà."; 
                }
            }

            // ÉDITION
            if (isset($_POST['edit_user'])) {
                $targetId = $_POST['user_id'];
                $userModel->updateAdmin($targetId, $_POST['email'], $_POST['role_id']);
                
                if (!empty($_POST['password'])) { 
                    $userModel->updatePassword($targetId, password_hash($_POST['password'], PASSWORD_DEFAULT)); 
                }
                
                Log::add('UPDATE_USER', "Modif utilisateur ID : " . $targetId);
                $_SESSION['flash_success'] = "Utilisateur mis à jour.";
            }
            
            $this->redirect('admin/users');
        }

        // Liste des utilisateurs
        $users = $userModel->getAllWithService(); // Méthode à renommer idéalement en getAllWithRoles() dans User.php
        
        // Liste des rôles (en dur ou via DB si vous avez gardé la table roles)
        // Ici on suppose que la table roles existe encore
        $db = \app\core\Database::getConnection();
        $roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

        $this->render('admin/users', [
            'users' => $users,
            'roles' => $roles
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

        // --- PRÉPARATION DES DONNÉES POUR LA VUE ---
        $instances = $instanceModel->getAll();
        
        // Pour chaque instance, on attache ses relations (Managers et Membres complets)
        foreach ($instances as &$inst) {
            $inst['managers'] = $instanceModel->getManagers($inst['id']);
            $inst['membres'] = $instanceModel->getMembres($inst['id']); 
        }

        $this->render('admin/instances', [
            'instances' => $instances,
            'all_users' => $userModel->getAllWithService() // Sert pour l'autocomplétion JS (Capsules + Lien de compte)
        ]);
    }


    /**
     * Logs Système
     */
    public function logs() {
        $logModel = new Log();
        
        $limit = 50;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $filters = [
            'search' => $_GET['search'] ?? ''
        ];

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
