<?php
namespace app\models;

use app\core\Database;
use PDO;

class Membre {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Liste tous les membres d'une instance
     */
    public function getByInstance($instanceId) {
        $stmt = $this->db->prepare("SELECT * FROM membres WHERE instance_id = ? ORDER BY college ASC, nom ASC");
        $stmt->execute([$instanceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($instanceId, $nom, $prenom, $email, $qualite, $college, $typeMandat) {
        $stmt = $this->db->prepare("INSERT INTO membres (instance_id, nom, prenom, email, qualite, college, type_mandat) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$instanceId, $nom, $prenom, $email, $qualite, $college, $typeMandat]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM membres WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
