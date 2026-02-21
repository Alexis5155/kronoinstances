<?php
namespace app\models;

use app\core\Database;
use PDO;

class PointOdj {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Récupère les points d'une séance triés par ordre
     */
    public function getBySeance($seanceId) {
        $stmt = $this->db->prepare("SELECT * FROM points_odj WHERE seance_id = ? ORDER BY ordre_affichage ASC");
        $stmt->execute([$seanceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($seanceId, $titre, $description, $type, $directionOrigine) {
        // On récupère le dernier ordre pour mettre ce point à la fin
        $stmtOrder = $this->db->prepare("SELECT MAX(ordre_affichage) FROM points_odj WHERE seance_id = ?");
        $stmtOrder->execute([$seanceId]);
        $maxOrder = $stmtOrder->fetchColumn() ?: 0;

        $stmt = $this->db->prepare("INSERT INTO points_odj (seance_id, titre, description, type_point, direction_origine, ordre_affichage, statut) VALUES (?, ?, ?, ?, ?, ?, 'brouillon')");
        return $stmt->execute([$seanceId, $titre, $description, $type, $directionOrigine, $maxOrder + 1]);
    }

    public function updateStatut($id, $statut) {
        $stmt = $this->db->prepare("UPDATE points_odj SET statut = ? WHERE id = ?");
        return $stmt->execute([$statut, $id]);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM points_odj WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
