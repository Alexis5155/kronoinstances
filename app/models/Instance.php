<?php
namespace app\models;

use app\core\Database;
use PDO;

class Instance {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll() {
        return $this->db->query("SELECT * FROM instances ORDER BY nom ASC")->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM instances WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($nom, $description, $nb_titulaires, $nb_suppleants, $quorum_requis) {
        $stmt = $this->db->prepare("INSERT INTO instances (nom, description, nb_titulaires, nb_suppleants, quorum_requis) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$nom, $description, $nb_titulaires, $nb_suppleants, $quorum_requis])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function update($id, $nom, $description, $nb_titulaires, $nb_suppleants, $quorum_requis) {
        $stmt = $this->db->prepare("UPDATE instances SET nom = ?, description = ?, nb_titulaires = ?, nb_suppleants = ?, quorum_requis = ? WHERE id = ?");
        return $stmt->execute([$nom, $description, $nb_titulaires, $nb_suppleants, $quorum_requis, $id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM instances WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ==========================================
    // MANAGERS (Liaison User -> Instance)
    // ==========================================

    public function getManagers($instanceId) {
        $stmt = $this->db->prepare("SELECT user_id FROM instance_managers WHERE instance_id = ?");
        $stmt->execute([$instanceId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function setManagers($instanceId, array $userIds) {
        $this->db->prepare("DELETE FROM instance_managers WHERE instance_id = ?")->execute([$instanceId]);
        if (!empty($userIds)) {
            $ins = $this->db->prepare("INSERT INTO instance_managers (instance_id, user_id) VALUES (?, ?)");
            foreach ($userIds as $uid) {
                if ($uid > 0) $ins->execute([$instanceId, $uid]);
            }
        }
    }

    // ==========================================
    // MEMBRES (Titulaires, Suppléants, Externes)
    // ==========================================

    public function getMembres($instanceId) {
        $stmt = $this->db->prepare("
            SELECT id, user_id, nom, prenom, email, qualite, college, type_mandat 
            FROM membres 
            WHERE instance_id = ? 
            ORDER BY college ASC, type_mandat ASC, nom ASC
        ");
        $stmt->execute([$instanceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setMembres($instanceId, array $membres) {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM membres WHERE instance_id = ?")->execute([$instanceId]);
            
            if (!empty($membres)) {
                $ins = $this->db->prepare("INSERT INTO membres (instance_id, user_id, nom, prenom, email, qualite, college, type_mandat) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                foreach ($membres as $m) {
                    $uid = !empty($m['user_id']) ? (int)$m['user_id'] : null;
                    $ins->execute([
                        $instanceId,
                        $uid,
                        trim($m['nom']),
                        trim($m['prenom']),
                        trim($m['email'] ?? ''),
                        trim($m['qualite'] ?? ''),
                        trim($m['college']),
                        trim($m['type_mandat'])
                    ]);
                }
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getOrphanMembresByEmail($email) {
        $stmt = $this->db->prepare("
            SELECT m.id, m.nom, m.prenom, m.email, i.nom as instance_nom 
            FROM membres m
            JOIN instances i ON m.instance_id = i.id
            WHERE m.email = ? AND (m.user_id IS NULL OR m.user_id = 0)
        ");
        $stmt->execute([trim($email)]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function linkUserToMembre($membreId, $userId) {
        $stmt = $this->db->prepare("UPDATE membres SET user_id = ? WHERE id = ?");
        return $stmt->execute([$userId, $membreId]);
    }

}
