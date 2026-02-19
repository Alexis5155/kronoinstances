<?php
namespace app\models;

use app\core\Database;
use \PDO;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function authenticate($identifier, $password) {
        // Modification de la requête pour inclure l'email
        $stmt = $this->db->prepare("
            SELECT u.*, r.nom as role_name, r.power as role_power 
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id 
            WHERE u.username = ? OR u.email = ?
        ");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Si c'est l'admin (role_id 1), on lui donne tout
            if ($user['role_id'] == 1) {
                // Vérifie bien que cette classe existe, sinon cela fera une erreur fatale
                if (class_exists('\app\config\Permissions')) {
                    $user['permissions'] = array_keys(\app\config\Permissions::getAll());
                } else {
                    $user['permissions'] = []; // Sécurité si la classe n'est pas chargée
                }
                // On s'assure que le power de l'admin est au max (ex: 100) si non défini
                $user['role_power'] = $user['role_power'] ?? 100;
            } else {
                $roleModel = new Role();
                $user['permissions'] = $roleModel->getPermissionIds($user['role_id']);
            }
            
            return $user;
        }

        return false;
    }

    /**
     * Trouve un utilisateur par son email pour la récupération
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT id, username, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Enregistre un jeton de réinitialisation
     */
    public function setResetToken($id, $token) {
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));
        $stmt = $this->db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        return $stmt->execute([$token, $expires, $id]);
    }

    /**
     * Vérifie si un jeton de réinitialisation est valide et retourne l'utilisateur
     */
    public function getUserByToken($token) {
        // On cherche l'utilisateur dont le token correspond et n'est pas expiré
        $stmt = $this->db->prepare("
            SELECT id, email 
            FROM users 
            WHERE reset_token = ? 
            AND reset_expires > NOW() 
            LIMIT 1
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Met à jour le mot de passe et invalide le jeton
     */
    public function resetPassword($id, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // On met à jour le mot de passe et on vide les champs de récupération
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password = ?, 
                reset_token = NULL, 
                reset_expires = NULL 
            WHERE id = ?
        ");
        return $stmt->execute([$hashedPassword, $id]);
    }

    /**
     * Vérification de permission (Helper statique)
     * @param string $permissionSlug Le code de la permission (ex: 'create_acte')
     * @return bool
     */
    public static function can($permissionSlug) {
        // Si la session n'est pas démarrée ou pas de permissions, refus
        if (session_status() === PHP_SESSION_NONE || !isset($_SESSION['permissions'])) {
            return false;
        }
        
        return in_array($permissionSlug, $_SESSION['permissions']);
    }

    /**
     * Vérifie si l'utilisateur connecté a un pouvoir supérieur ou égal à une valeur
     * Utile pour empêcher un user de modifier un admin
     */
    public static function hasPower($requiredPower) {
         if (session_status() === PHP_SESSION_NONE || !isset($_SESSION['user_power'])) {
            return false;
        }
        return $_SESSION['user_power'] >= $requiredPower;
    }

    // Utilisé pour les statistiques de l'accueil admin
    public function countAll() {
        return $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    // Utilisé pour remplir les listes déroulantes (Filtres)
    public function getList() {
        return $this->db->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll();
    }

    // Récupération de la liste complète pour le tableau Admin
    public function getAllWithService() {
        return $this->db->query("
            SELECT u.*, s.nom AS service_nom, r.nom AS role_nom, r.power AS role_power
            FROM users u 
            LEFT JOIN services s ON u.service_id = s.id 
            LEFT JOIN roles r ON u.role_id = r.id
            ORDER BY u.username ASC
        ")->fetchAll();
    }

    // --- MÉTHODES DE GESTION (CRUD) ---

    // Création avec role_id
    public function create($username, $password, $email, $roleId, $service_id) {
        $stmt = $this->db->prepare("INSERT INTO users (username, password, email, role_id, service_id) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$username, $password, $email, $roleId, $service_id]);
    }

    // Mise à jour admin avec role_id
    public function updateAdmin($id, $email, $roleId, $service_id) {
        $stmt = $this->db->prepare("UPDATE users SET email = ?, role_id = ?, service_id = ? WHERE id = ?");
        return $stmt->execute([$email, $roleId, $service_id, $id]);
    }

    public function updatePassword($id, $password) {
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$password, $id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Récupération info pour édition ou suppression
    public function getInfoWithService($id) {
        $stmt = $this->db->prepare("
            SELECT u.*, s.nom as service_nom, r.id as role_id, r.nom as role_name, r.power as role_power
            FROM users u 
            LEFT JOIN services s ON u.service_id = s.id 
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getById($id) {
        $sql = "SELECT a.*, u.username 
                FROM arretes a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.id = ?";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

}