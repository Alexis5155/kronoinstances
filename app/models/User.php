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
        $stmt = $this->db->prepare("
            SELECT * FROM users 
            WHERE username = ? OR email = ?
        ");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $user['permissions'] = $this->getPermissions($user['id']);
            return $user;
        }
        return false;
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function can($permissionSlug) {
        if (session_status() === PHP_SESSION_NONE || !isset($_SESSION['permissions'])) {
            return false;
        }
        if (in_array('manage_system', $_SESSION['permissions'])) {
            return true;
        }
        return in_array($permissionSlug, $_SESSION['permissions']);
    }

    public static function hasPower($requiredPower) {
         return false; // Obsolète
    }

    // ==========================================
    // PERMISSIONS
    // ==========================================

    public function getPermissions($userId) {
        $stmt = $this->db->prepare("SELECT permission_slug FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function syncPermissions($userId, array $slugs) {
        $this->db->prepare("DELETE FROM user_permissions WHERE user_id = ?")->execute([$userId]);
        
        if (!empty($slugs)) {
            $stmt = $this->db->prepare("INSERT INTO user_permissions (user_id, permission_slug) VALUES (?, ?)");
            foreach ($slugs as $slug) {
                $stmt->execute([$userId, trim($slug)]);
            }
        }
    }

    // ==========================================
    // LECTURE & PAGINATION
    // ==========================================

    public function countAll() {
        return $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function getList() {
        return $this->db->query("SELECT id, username, prenom, nom FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        return $this->db->query("SELECT * FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaginated($limit, $offset) {
        $stmt = $this->db->prepare("SELECT * FROM users ORDER BY username ASC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // GESTION (CRUD)
    // ==========================================

    public function create($username, $password, $email, $prenom = null, $nom = null) {
        $stmt = $this->db->prepare("INSERT INTO users (username, password, email, prenom, nom) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $password, $email, $prenom, $nom])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function updateAdmin($id, $email, $prenom = null, $nom = null) {
        $stmt = $this->db->prepare("UPDATE users SET email = ?, prenom = ?, nom = ? WHERE id = ?");
        return $stmt->execute([$email, $prenom, $nom, $id]);
    }

    public function updatePassword($id, $password) {
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$password, $id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ==========================================
    // UTILITAIRES
    // ==========================================

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT id, username, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function setResetToken($id, $token) {
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));
        $stmt = $this->db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        return $stmt->execute([$token, $expires, $id]);
    }

    public function getUserByToken($token) {
        $stmt = $this->db->prepare("
            SELECT id, email 
            FROM users 
            WHERE reset_token = ? 
            AND reset_expires > NOW() 
            LIMIT 1
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

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
}