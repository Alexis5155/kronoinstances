<?php
namespace app\controllers;

use app\core\Controller;
use app\models\User;
use app\models\Arrete;
use app\models\Parametre;
use app\models\Role;
use app\models\Service;
use app\models\Signataire;
use app\models\Log;

class Admin extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            $currentPath = $_GET['url'] ?? ''; 
            $this->redirect('login?return=' . urlencode($currentPath));
            exit;
        }
    }

    public function index() {
        // Accès au Dashboard Admin :
        // On autorise l'accès si l'utilisateur a AU MOINS UNE permission d'administration.
        $hasAccess = (
            User::can('view_logs') || 
            User::can('manage_users') || 
            User::can('manage_system') || 
            User::can('manage_roles') ||
            User::can('view_all_actes') ||
            User::can('export_registre') ||
            User::can('manage_services') ||
            User::can('manage_signataires')
        );

        if (!$hasAccess) {
            $this->redirect('dashboard');
        }

        $userModel = new User();
        $arreteModel = new Arrete();
        $settingModel = new Parametre();

        $annee_actuelle = date("Y");
        
        $count_users = $userModel->countAll();
        $count_arretes_total = $arreteModel->countFiltered(['isAdmin' => true]);
        $count_arretes_annee = $arreteModel->countFiltered([
            'isAdmin' => true, 
            'annee'   => '2026'
        ]);

        // Vérification de la mise à jour GitHub (UNIQUEMENT si permission système)
        $has_update  = false;
        $new_v_name  = null;
        
        if (User::can('manage_system')) {
            $update_track = $settingModel->get('update_track') ?: "main";
            $data = $this->checkUpdatesGitHub($update_track);
            if ($data && $data['has_new']) {
                $has_update = true;
                $new_v_name = $data['version'];
            }
        }

        $this->render('admin/index', [
            'count_users' => $count_users,
            'count_total' => $count_arretes_total,
            'count_year'  => $count_arretes_annee,
            'annee'       => $annee_actuelle,
            'has_update'  => $has_update,
            'new_v_name'  => $new_v_name
        ]);
    }

    public function update() {
        if (!User::can('manage_system')) {
            $this->redirect('admin');
        }
        $this->render('admin/update');
    }

    public function registre() {
        if (!User::can('view_all_actes')) {
            setToast("Accès refusé au registre global.", "danger");
            $this->redirect('dashboard');
        }

        $arreteModel = new Arrete();
        $userModel = new User();
        $serviceModel = new Service();

        // Gestion suppression
        if (isset($_GET['delete_id'])) {
            if (!User::can('delete_acte')) {
                setToast("Vous n'avez pas la permission de supprimer des actes.", "danger");
            } elseif (!isset($_GET['token']) || !checkCsrf($_GET['token'])) { 
                die("Action non autorisée (CSRF)."); 
            } else {
                $acte = $arreteModel->getById($_GET['delete_id']);
                if ($acte) {
                    // Suppression du fichier physique
                    if ($acte['fichier_path'] && file_exists("uploads/" . $acte['fichier_path'])) { 
                        unlink("uploads/" . $acte['fichier_path']); 
                    }
                    
                    // Suppression BDD
                    \app\core\Database::getConnection()->prepare("DELETE FROM arretes WHERE id = ?")->execute([$_GET['delete_id']]);
                    
                    // Log Audit Trail
                    Log::add('DELETE_ACTE', "Suppression définitive de l'acte n° " . $acte['num_complet'], $acte['id'], 'arrete', $acte, null);
                    
                    setToast("L'acte <strong>{$acte['num_complet']}</strong> a été supprimé.");
                }
            }
            $this->redirect('admin/registre');
        }

        $limit = 15;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $filters = [
            'scope'   => 'global',
            'search'  => $_GET['search'] ?? '',
            'statut'  => $_GET['f_statut'] ?? '',
            'agent'   => $_GET['f_agent'] ?? '',
            'annee'   => $_GET['f_annee'] ?? '',
            'service' => $_GET['f_service'] ?? ''
        ];

        $this->render('admin/registre', [
            'all_arretes' => $arreteModel->getFiltered($filters, $limit, ($page - 1) * $limit),
            'total_items' => $arreteModel->countFiltered($filters),
            'total_pages' => ceil($arreteModel->countFiltered($filters) / $limit),
            'page'         => $page,
            'agents'      => $userModel->getList(),
            'services'    => $serviceModel->getAll(),
            'annees'      => $arreteModel->getDistinctYears(),
            'filters'     => $filters,
            'query_params'=> $_GET
        ]);
    }

    public function users() {
        if (!User::can('manage_users')) {
            setToast("Accès refusé.", "danger");
            $this->redirect('dashboard');
        }

        $userModel = new User();
        $serviceModel = new Service();
        $roleModel = new Role();

        // --- SUPPRESSION ---
        if (isset($_GET['delete_id'])) {
            $targetUser = $userModel->getInfoWithService($_GET['delete_id']);
            if ($targetUser) {
                $targetRole = $roleModel->getById($targetUser['role_id']);
                
                // Protection Hiérarchique Stricte
                if ($_GET['delete_id'] == $_SESSION['user_id']) {
                    setToast("Action impossible sur votre propre compte.", "danger");
                } elseif ($targetRole['power'] >= $_SESSION['user_power']) {
                    // On ne peut supprimer ni un supérieur ni un égal
                    setToast("Vous ne pouvez pas supprimer un utilisateur de rang supérieur ou égal au vôtre.", "danger");
                } else {
                    $userModel->delete($_GET['delete_id']);
                    
                    // Log Audit Trail
                    Log::add('DELETE_USER', "Suppression compte ID: " . $targetUser['id'] . " (" . $targetUser['username'] . ")", $targetUser['id'], 'user', $targetUser, null);
                    
                    setToast("Utilisateur supprimé avec succès.");
                }
            }
            $this->redirect('admin/users');
        }

        // --- AJOUT / ÉDITION ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // AJOUT
            if (isset($_POST['add_user'])) {
                $targetRoleId = $_POST['role_id'];
                $targetRoleData = $roleModel->getById($targetRoleId);
                
                // On ne peut créer que des sous-fifres ou des égaux (<=), pas des supérieurs
                if ($targetRoleData['power'] > $_SESSION['user_power']) {
                     setToast("Erreur : Vous ne pouvez pas attribuer un rôle supérieur au vôtre.", "danger");
                } else {
                    try {
                        $userModel->create($_POST['username'], password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['email'], $targetRoleId, $_POST['service_id'] ?: null);
                        
                        $newData = [
                            'username' => $_POST['username'],
                            'email' => $_POST['email'],
                            'role_id' => $targetRoleId,
                            'service_id' => $_POST['service_id'] ?: null
                        ];
                        Log::add('CREATE_USER', "Création de l'utilisateur : " . $_POST['username'], null, 'user', null, $newData);
                        
                        setToast("Utilisateur créé avec succès !");
                    } catch (\Exception $e) { 
                        setToast("Erreur : L'identifiant existe déjà.", "danger"); 
                    }
                }
            }

            // ÉDITION
            if (isset($_POST['edit_user'])) {
                $targetUserId = $_POST['user_id'];
                $targetUser = $userModel->getInfoWithService($targetUserId);
                $currentRoleTarget = $roleModel->getById($targetUser['role_id']); 
                
                // 1. BLOCAGE ABSOLU SI CIBLE SUPÉRIEURE OU ÉGALE (sauf soi-même)
                if ($targetUserId != $_SESSION['user_id'] && $currentRoleTarget['power'] > $_SESSION['user_power']) {
                    setToast("Action interdite : Cet utilisateur a un rang supérieur au vôtre.", "danger");
                } else {
                    // 2. Gestion du changement de rôle
                    $newRoleId = $targetUser['role_id']; // Par défaut, on garde l'ancien
                    
                    if (isset($_POST['role_id']) && $_POST['role_id'] != $targetUser['role_id']) {
                        // Si c'est moi-même, interdit de changer mon propre rôle
                        if ($targetUserId == $_SESSION['user_id']) {
                            setToast("Vous ne pouvez pas modifier votre propre rôle.", "warning");
                        } else {
                            // Si c'est un subalterne, on vérifie qu'on ne le promeut pas trop haut
                            $requestedRole = $roleModel->getById($_POST['role_id']);
                            if ($requestedRole['power'] > $_SESSION['user_power']) {
                                setToast("Vous ne pouvez pas promouvoir quelqu'un au dessus de votre rang.", "danger");
                            } else {
                                $newRoleId = $_POST['role_id'];
                            }
                        }
                    }

                    // 3. Application des modifications
                    $userModel->updateAdmin($targetUserId, $_POST['email'], $newRoleId, $_POST['service_id'] ?: null);
                    
                    $newData = [
                        'email' => $_POST['email'],
                        'role_id' => $newRoleId,
                        'service_id' => $_POST['service_id'] ?: null
                    ];

                    Log::add('UPDATE_USER', "Modification du compte utilisateur : " . $targetUser['username'], $targetUserId, 'user', $targetUser, $newData);
                    
                    if (!empty($_POST['password'])) { 
                        $userModel->updatePassword($targetUserId, password_hash($_POST['password'], PASSWORD_DEFAULT)); 
                        Log::add('UPDATE_PASSWORD', "Changement de mot de passe forcé pour : " . $targetUser['username'], $targetUserId, 'user');
                    }
                    
                    setToast("Utilisateur mis à jour.");
                }
            }
            $this->redirect('admin/users');
        }

        $this->render('admin/users', [
            'users' => $userModel->getAllWithService(), // getAllWithService inclut role_power via jointure
            'roles' => $roleModel->getAll(),
            'services' => $serviceModel->getAll()
        ]);
    }

    public function roles() {
        if (!User::can('manage_roles')) {
            $this->redirect('dashboard');
        }

        $roleModel = new Role();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Mise à jour des permissions
            if (isset($_POST['update_permissions'])) {
                $rId = $_POST['role_id'];
                $roleCible = $roleModel->getById($rId);
                
                if ($roleCible['power'] > $_SESSION['user_power']) {
                    setToast("Interdit de modifier un rôle supérieur au vôtre.", "danger");
                } else {
                    $oldPerms = $roleModel->getPermissionsByRole($rId);
                    $perms = $_POST['permissions'] ?? [];
                    $roleModel->syncPermissions($rId, $perms);

                    Log::add('UPDATE_ROLE_PERMS', "Mise à jour des permissions pour le rôle : " . $roleCible['nom'], $rId, 'role', $oldPerms, $perms);
                    
                    setToast("Permissions mises à jour pour le rôle " . htmlspecialchars($roleCible['nom']));
                }
            }
            
            // Création de rôle
            if (isset($_POST['create_role'])) {
                $power = (int)$_POST['power'];
                if ($power >= $_SESSION['user_power']) {
                     setToast("Vous ne pouvez pas créer un rôle plus puissant que le vôtre.", "danger");
                } else {
                    $roleModel->create($_POST['nom'], $_POST['description'], $power);
                    Log::add('CREATE_ROLE', "Création du rôle : " . $_POST['nom'], null, 'role', null, ['nom' => $_POST['nom'], 'power' => $power]);
                    setToast("Rôle créé avec succès.");
                }
            }
            
            // Suppression de rôle
            if (isset($_POST['delete_role'])) {
                $roleCible = $roleModel->getById($_POST['role_id']);
                if ($roleModel->delete($_POST['role_id'])) {
                    Log::add('DELETE_ROLE', "Suppression du rôle : " . $roleCible['nom'], $_POST['role_id'], 'role', $roleCible, null);
                    setToast("Rôle supprimé.");
                } else {
                    setToast("Impossible de supprimer ce rôle (peut-être immuable ou utilisé).", "danger");
                }
            }

            $this->redirect('admin/roles');
        }

        $this->render('admin/roles', [
            'roles' => $roleModel->getAll(),
            'permissions' => $roleModel->getAllPermissions(),
            'roleModel' => $roleModel
        ]);
    }

    public function logs() {
        if (!User::can('view_logs')) {
            $this->redirect('dashboard');
        }

        $logModel = new Log();
        $userModel = new User();

        $limit = 25;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $filters = [
            'search'     => $_GET['search'] ?? '',
            'agent'      => $_GET['f_agent'] ?? '',
            'action'     => $_GET['f_action'] ?? '',
            'date_debut' => $_GET['date_debut'] ?? '',
            'date_fin'   => $_GET['date_fin'] ?? ''
        ];

        $totalLogs = $logModel->countFiltered($filters);

        $this->render('admin/logs', [
            'logs'            => $logModel->getFiltered($filters, $limit, ($page - 1) * $limit),
            'totalLogs'       => $totalLogs,
            'totalPages'      => ceil($totalLogs / $limit),
            'page'            => $page,
            'agents'          => $userModel->getList(),
            'actions_dispo'   => $logModel->getUniqueActions(),
            'filters'         => $filters,
            'query_params'    => $_GET
        ]);
    }

    public function export() {
        if (!User::can('export_registre')) {
            $this->redirect('dashboard');
        }
        
        $arreteModel = new Arrete();

        if (isset($_GET['action'])) {
            $annee_export = isset($_GET['year']) ? intval($_GET['year']) : null;
            $data = $arreteModel->getForExport($annee_export);
            $filename = $annee_export ? "registre_$annee_export.csv" : "save_kronoactes_" . date('d-m-Y') . ".csv";

            $logMsg = $annee_export ? "Extraction du registre pour l'année : $annee_export" : "Extraction complète du registre.";
            
            // Audit Trail Export
            Log::add($annee_export ? 'EXPORT_ANNUEL' : 'EXPORT_COMPLET', $logMsg);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Numéro', 'Année', 'Type', 'Titre', 'Date de prise', 'Signataire', 'Service', 'Statut', 'Commentaire', 'Créé par'], ";");

            foreach ($data as $row) {
                fputcsv($output, [
                    $row['num_complet'], $row['annee'], $row['type_acte'], $row['titre'],
                    $row['date_prise'], $row['signataire'], $row['service_nom'] ?? 'Aucun',
                    $row['statut'], $row['commentaire'], $row['username']
                ], ";");
            }
            fclose($output);
            exit();
        }

        $this->render('admin/export', ['annees_dispo' => $arreteModel->getDistinctYears()]);
    }

    public function restaurer() {
        if (!User::can('manage_system')) {
            $this->redirect('dashboard');
        }
        
        $db = \app\core\Database::getConnection();

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_restore'])) {
            $pwd = $_POST['admin_password'];
            
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user && password_verify($pwd, $user['password'])) {
                // A. BACKUP AUTO
                if (!is_dir('backups')) { mkdir('backups', 0777, true); }
                $backup_file = "backups/save_auto_" . date('Y-m-d_H-i-s') . ".csv";
                $out = fopen($backup_file, 'w');
                fwrite($out, "\xEF\xBB\xBF");
                $current_data = (new Arrete())->getForExport();
                fputcsv($out, ['Numéro', 'Année', 'Type', 'Titre', 'Date', 'Signataire', 'Service', 'Statut', 'Commentaire', 'Agent'], ";");
                foreach ($current_data as $row) { fputcsv($out, array_values($row), ";"); }
                fclose($out);

                // B. RESTAURATION
                $file = $_FILES['csv_restore']['tmp_name'];
                if (($handle = fopen($file, "r")) !== FALSE) {
                    $bom = fread($handle, 3);
                    if ($bom != "\xEF\xBB\xBF") { rewind($handle); }
                    fgetcsv($handle, 0, ";");

                    try {
                        $db->beginTransaction();
                        $db->exec("DELETE FROM arretes");
                        $stmt_ins = $db->prepare("INSERT INTO arretes (num_index, num_complet, annee, type_acte, titre, date_prise, signataire, service_id, statut, commentaire, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                        while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                            if(count($data) >= 10) {
                                $num_index = intval(explode('_', $data[0])[2] ?? 0);
                                
                                $stmt_u = $db->prepare("SELECT id FROM users WHERE username = ?");
                                $stmt_u->execute([$data[9]]);
                                $u_id = $stmt_u->fetchColumn() ?: $_SESSION['user_id'];

                                $s_id = null;
                                if (!empty($data[6]) && $data[6] !== 'Aucun') {
                                    $stmt_s = $db->prepare("SELECT id FROM services WHERE nom = ?");
                                    $stmt_s->execute([$data[6]]);
                                    $s_id = $stmt_s->fetchColumn() ?: null;
                                }

                                $stmt_ins->execute([$num_index, $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $s_id, $data[7], $data[8], $u_id]);
                            }
                        }
                        $db->commit();
                        
                        // Audit Trail Restauration
                        Log::add('RESTAURATION_BDD', "Restauration complète effectuée depuis fichier CSV.");
                        
                        setToast("Restauration terminée avec succès.");
                        $this->redirect('admin/export');
                    } catch (\Exception $e) {
                        $db->rollBack();
                        setToast("Erreur technique : " . $e->getMessage(), "danger");
                    }
                    fclose($handle);
                }
            } else {
                setToast("Mot de passe incorrect.", "danger");
            }
        }
        $this->render('admin/restaurer');
    }

    public function parametres() {
        $paramModel = new Parametre();
        $serviceModel = new Service();
        $signataireModel = new Signataire();
        $db = \app\core\Database::getConnection();

        $section = $_GET['section'] ?? 'general';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'backup_db' && User::can('manage_system')) {
                Log::add('BACKUP_SQL', "Génération d'une sauvegarde SQL manuelle.");
                $this->generateSqlBackup($db);
            }
            if (($action === 'update_general' || $action === 'update_update_settings') && User::can('manage_system')) {
                if ($action === 'update_general') {
                    $oldVal = $paramModel->get('collectivite_nom');
                    $paramModel->set('collectivite_nom', $_POST['col_name']);
                    Log::add('UPDATE_SETTING', "Modification nom collectivité", null, 'setting', ['collectivite_nom' => $oldVal], ['collectivite_nom' => $_POST['col_name']]);
                }
                if ($action === 'update_update_settings') {
                    $oldVal = $paramModel->get('update_track');
                    $paramModel->set('update_track', $_POST['update_track']);
                    Log::add('UPDATE_SETTING', "Modification canal mise à jour", null, 'setting', ['update_track' => $oldVal], ['update_track' => $_POST['update_track']]);
                }
                setToast("Paramètres mis à jour.");
            }
            if (in_array($action, ['add_signataire', 'delete_signataire']) && User::can('manage_signataires')) {
                 if ($action === 'add_signataire') {
                     $signataireModel->create($_POST['sig_nom'], $_POST['sig_prenom'], $_POST['sig_qualite']);
                     Log::add('CREATE_SIGNATAIRE', "Ajout signataire : " . $_POST['sig_nom'], null, 'signataire');
                 }
                 if ($action === 'delete_signataire') {
                     $signataireModel->delete($_POST['target_id']);
                     Log::add('DELETE_SIGNATAIRE', "Suppression signataire ID : " . $_POST['target_id'], $_POST['target_id'], 'signataire');
                 }
                 setToast("Signataire mis à jour.");
            }
            if (in_array($action, ['add_service', 'delete_service']) && User::can('manage_services')) {
                 if ($action === 'add_service') {
                     $serviceModel->create($_POST['service_nom']);
                     Log::add('CREATE_SERVICE', "Ajout service : " . $_POST['service_nom'], null, 'service');
                 }
                 if ($action === 'delete_service') {
                     $serviceModel->delete($_POST['target_id']);
                     Log::add('DELETE_SERVICE', "Suppression service ID : " . $_POST['target_id'], $_POST['target_id'], 'service');
                 }
                 setToast("Service mis à jour.");
            }

             if ($action === 'update_system' && User::can('manage_system')) {
                $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                if (password_verify($_POST['confirm_password'], $stmt->fetchColumn())) {
                    $content = "<?php\ndefine('DB_HOST', '" . addslashes($_POST['db_host']) . "');\ndefine('DB_NAME', '" . addslashes($_POST['db_name']) . "');\ndefine('DB_USER', '" . addslashes($_POST['db_user']) . "');\ndefine('DB_PASS', '" . addslashes($_POST['db_pass']) . "');\n\ntry {\n    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8\", DB_USER, DB_PASS);\n    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);\n} catch (PDOException \$e) {\n    die(\"Erreur de connexion : \" . \$e->getMessage());\n}\n?>";
                    file_put_contents('app/config/config.php', $content); // Modifié pour pointer vers app/config/
                    Log::add('UPDATE_CONFIG', "Modification manuelle du fichier config.php");
                    setToast("Système mis à jour.");
                } else { setToast("Mot de passe incorrect.", "danger"); }
            }
            if (($action === 'clean_bdd' || $action === 'delete_file' || $action === 'rattach_file') && User::can('manage_system')) {
                if ($action === 'clean_bdd') {
                    $db->prepare("UPDATE arretes SET fichier_path = NULL WHERE id = ?")->execute([$_POST['target_id']]);
                    Log::add('INTEGRITY_CLEAN', "Nettoyage lien fichier BDD pour acte " . $_POST['target_id'], $_POST['target_id'], 'arrete');
                    setToast("Lien nettoyé.");
                }
                if ($action === 'delete_file') {
                    if (file_exists('uploads/' . basename($_POST['target_file']))) {
                        unlink('uploads/' . basename($_POST['target_file']));
                        Log::add('INTEGRITY_DELETE_FILE', "Suppression physique fichier orphelin : " . $_POST['target_file']);
                        setToast("Fichier supprimé.");
                    }
                }
                if ($action === 'rattach_file') {
                    $db->prepare("UPDATE arretes SET fichier_path = ? WHERE id = ?")->execute([$_POST['target_file'], $_POST['target_id']]);
                    Log::add('INTEGRITY_RATTACH', "Rattachement manuel fichier " . $_POST['target_file'] . " à l'acte " . $_POST['target_id'], $_POST['target_id'], 'arrete');
                    setToast("Fichier rattaché !");
                }
            }

            $this->redirect('admin/parametres?section=' . $section);
        }

        $this->render('admin/parametres', [
            'section'       => $section,
            'col_nom'       => $paramModel->get('collectivite_nom') ?: "Ma Collectivité",
            'update_track'  => $paramModel->get('update_track') ?: "main",
            'signataires'   => ($section === 'signataires') ? $signataireModel->getAll() : [],
            'services'      => ($section === 'services') ? $serviceModel->getAll() : [],
            'diagnostic'    => ($section === 'integrity' && User::can('manage_system')) ? diagnostiquerFichiers($db, true) : null,
            'update_data'   => ($section === 'update' && User::can('manage_system')) ? $this->checkUpdatesGitHub($paramModel->get('update_track')) : null
        ]);
    }

    private function generateSqlBackup($db) {
        $tables = [];
        $res = $db->query("SHOW TABLES");
        while ($row = $res->fetch(\PDO::FETCH_NUM)) { $tables[] = $row[0]; }
        $output = "-- KronoActes Backup\n\n";
        foreach ($tables as $t) {
            $create = $db->query("SHOW CREATE TABLE `$t`")->fetch(\PDO::FETCH_ASSOC);
            $output .= $create['Create Table'] . ";\n\n";
            $rows = $db->query("SELECT * FROM `$t`")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $vals = array_map(fn($v) => is_null($v) ? 'NULL' : $db->quote($v), array_values($r));
                $output .= "INSERT INTO `$t` VALUES (" . implode(", ", $vals) . ");\n";
            }
        }
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d') . '.sql"');
        echo $output; exit();
    }

    private function checkUpdatesGitHub($track) {
        $url = ($track === 'beta') ? "https://api.github.com/repos/Alexis5155/kronoactes/releases" : "https://api.github.com/repos/Alexis5155/kronoactes/releases/latest";
        $opts = ['http' => ['method' => 'GET', 'header' => ['User-Agent: KronoActes-App']]];
        $response = @file_get_contents($url, false, stream_context_create($opts));
        if ($response) {
            $data = json_decode($response, true);
            $rel = ($track === 'beta') ? ($data[0] ?? null) : $data;
            return $rel ? ['version' => $rel['tag_name'], 'changelog' => $rel['body'], 'has_new' => version_compare($rel['tag_name'], APP_VERSION, '>')] : null;
        }
        return null;
    }
}