<?php
namespace app\models;
use app\core\Database;
use app\config\Permissions;

class Role {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll() {
        return $this->db->query("SELECT * FROM roles ORDER BY power DESC")->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAllPermissions() {
        $list = [];
        foreach (Permissions::getAll() as $slug => $data) {
            $list[] = [
                'id' => $slug,
                'slug' => $slug,
                'nom' => $data['nom'],
                'description' => $data['desc'],
                'category' => $data['cat']
            ];
        }
        return $list;
    }

    public function getPermissionIds($roleId) {
        $stmt = $this->db->prepare("SELECT permission_slug FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN); // Retourne un tableau de slugs ['create_acte', 'view_logs'...]
    }

    public function getPermissionsByRole($roleId) {
    $stmt = $this->db->prepare("SELECT permission_slug FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$roleId]);
    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function syncPermissions($roleId, $slugs) {
        $this->db->beginTransaction();
        try {
            // 1. On vide tout pour ce rôle
            $del = $this->db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $del->execute([$roleId]);

            // 2. On réinsère les slugs cochés
            if (!empty($slugs)) {
                $ins = $this->db->prepare("INSERT INTO role_permissions (role_id, permission_slug) VALUES (?, ?)");
                foreach ($slugs as $slug) {
                    if (array_key_exists($slug, Permissions::getAll())) {
                        $ins->execute([$roleId, $slug]);
                    }
                }
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function create($nom, $desc, $power) {
        $stmt = $this->db->prepare("INSERT INTO roles (nom, description, power) VALUES (?, ?, ?)");
        return $stmt->execute([$nom, $desc, $power]);
    }

    public function delete($id) {
        $role = $this->getById($id);
        if ($role && $role['is_immutable'] == 0) {
            $stmt = $this->db->prepare("DELETE FROM roles WHERE id = ?");
            return $stmt->execute([$id]);
        }
        return false;
    }
}