<?php
namespace app\models;

use app\core\Database;
use PDO;

class PointOdj {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getBySeance($seanceId) {
        $stmt = $this->db->prepare("SELECT * FROM points_odj WHERE seance_id = ? ORDER BY ordre_affichage ASC");
        $stmt->execute([$seanceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($seanceId, $titre, $description, $type, $directionOrigine) {
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

    // NOUVEAU : Sauvegarde des débats via AJAX
    public function updateDebats($id, $debats) {
        $stmt = $this->db->prepare("UPDATE points_odj SET debats = ? WHERE id = ?");
        return $stmt->execute([$debats, $id]);
    }

    // NOUVEAU : Sauvegarder les votes globaux par collège
    public function saveVotes($pointId, $college, $pour, $contre, $abstention, $refus) {
        // On supprime d'abord les anciens votes de ce collège pour ce point
        $this->db->prepare("DELETE FROM votes WHERE point_odj_id = ? AND college = ?")->execute([$pointId, $college]);
        
        $stmt = $this->db->prepare("INSERT INTO votes (point_odj_id, college, nb_pour, nb_contre, nb_abstention, nb_refus_vote) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$pointId, $college, (int)$pour, (int)$contre, (int)$abstention, (int)$refus]);
    }

    // NOUVEAU : Récupérer les votes d'un point
    public function getVotes($pointId) {
        $stmt = $this->db->prepare("SELECT * FROM votes WHERE point_odj_id = ?");
        $stmt->execute([$pointId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM points_odj WHERE id = ?");
        return $stmt->execute([$id]);
    }

        // Mettre à jour l'ordre de tri (Drag & Drop)
    public function updateOrdre($id, $ordre) {
        $stmt = $this->db->prepare("UPDATE points_odj SET ordre_affichage = ? WHERE id = ?");
        return $stmt->execute([$ordre, $id]);
    }

    // Mettre à jour la description riche (Exposé des motifs)
    public function updateDescription($id, $description) {
        $stmt = $this->db->prepare("UPDATE points_odj SET description = ? WHERE id = ?");
        return $stmt->execute([$description, $id]);
    }
}
