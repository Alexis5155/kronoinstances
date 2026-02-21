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
        // Modification : On retire 'r.power' qui n'existe plus dans la table roles
        $stmt = $this->db->prepare("
            SELECT u.*, r.nom as role_name 
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id 
            WHERE u.username = ? OR u.email = ?
        ");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            
            // Gestion des permissions simplifiée pour KronoInstances
            // On définit les permissions en dur selon le Rôle ID
            // 1 = Admin, 2 = RH, 3 = Membre
            
            $permissions = [];

            if ($user['role_id'] == 1) {
                // ADMIN : Tout pouvoir
                $permissions = ['admin_access', 'manage_users', 'manage_instances', 'view_logs'];
            } elseif ($user['role_id'] == 2) {
                // RH : Gestion des séances
                $permissions = ['create_seance', 'manage_odj', 'manage_convocations', 'view_instances'];
            } elseif ($user['role_id'] == 3) {
                // MEMBRE : Consultation seulement
                $permissions = ['view_instances', 'download_documents'];
            }

            $user['permissions'] = $permissions;
            
            // On simule le power pour la compatibilité avec le reste du code si besoin
            // Admin = 100, RH = 50, Membre = 10
            $user['role_power'] = ($user['role_id'] == 1) ? 100 : (($user['role_id'] == 2) ? 50 : 10);
            
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
     */
    public static function can($permissionSlug) {
        if (session_status() === PHP_SESSION_NONE || !isset($_SESSION['permissions'])) {
            return false;
        }
        return in_array($permissionSlug, $_SESSION['permissions']);
    }

    /**
     * Vérifie le niveau de pouvoir
     */
    public static function hasPower($requiredPower) {
         if (session_status() === PHP_SESSION_NONE || !isset($_SESSION['user_power'])) {
            return false;
        }
        return $_SESSION['user_power'] >= $requiredPower;
    }

    public function countAll() {
        return $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function getList() {
        return $this->db->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll();
    }

    // Récupération de la liste complète pour le tableau Admin
    public function getAllWithService() {
        // Correction : Suppression de r.power
        return $this->db->query("
            SELECT u.*, r.nom AS role_nom
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id
            ORDER BY u.username ASC
        ")->fetchAll();
    }

    // --- MÉTHODES DE GESTION (CRUD) ---

    // Création
    // Note : j'ai retiré service_id car nous l'avons supprimé ou rendu optionnel selon votre choix
    // Si vous voulez le garder, remettez-le, mais vérifiez qu'il existe dans la BDD
    public function create($username, $password, $email, $roleId) {
        $stmt = $this->db->prepare("INSERT INTO users (username, password, email, role_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$username, $password, $email, $roleId]);
    }

    // Mise à jour admin
    public function updateAdmin($id, $email, $roleId) {
        $stmt = $this->db->prepare("UPDATE users SET email = ?, role_id = ? WHERE id = ?");
        return $stmt->execute([$email, $roleId, $id]);
    }

    public function updatePassword($id, $password) {
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$password, $id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Récupération info pour édition
    public function getInfoWithService($id) {
        // Correction : Suppression de service_id et r.power
        $stmt = $this->db->prepare("
            SELECT u.*, r.id as role_id, r.nom as role_name
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
