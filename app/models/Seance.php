<?php
namespace app\models;

use app\core\Database;
use PDO;

class Seance {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getProchaines($limit = 5) {
        $sql = "SELECT s.*, i.nom as instance_nom 
                FROM seances s
                JOIN instances i ON s.instance_id = i.id
                WHERE s.date_seance >= CURDATE()
                ORDER BY s.date_seance ASC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByInstance($instanceId) {
        $sql = "SELECT s.*, 
                       (SELECT COUNT(*) FROM points_odj p WHERE p.seance_id = s.id) as nb_points
                FROM seances s
                WHERE s.instance_id = ?
                ORDER BY s.date_seance DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$instanceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $sql = "SELECT s.*, i.nom as instance_nom, i.quorum_requis
                FROM seances s
                JOIN instances i ON s.instance_id = i.id
                WHERE s.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($instanceId, $date, $heure, $lieu) {
        $stmt = $this->db->prepare("INSERT INTO seances (instance_id, date_seance, heure_debut, lieu, statut) VALUES (?, ?, ?, ?, 'planifiee')");
        if ($stmt->execute([$instanceId, $date, $heure, $lieu])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function update($id, $date, $heure, $lieu) {
        $stmt = $this->db->prepare("UPDATE seances SET date_seance = ?, heure_debut = ?, lieu = ? WHERE id = ?");
        return $stmt->execute([$date, $heure, $lieu, $id]);
    }

    public function updateStatut($id, $statut) {
        $stmt = $this->db->prepare("UPDATE seances SET statut = ? WHERE id = ?");
        return $stmt->execute([$statut, $id]);
    }

    public function updateQuorum($id, $attained) {
        $stmt = $this->db->prepare("UPDATE seances SET quorum_atteint = ? WHERE id = ?");
        return $stmt->execute([$attained ? 1 : 0, $id]);
    }

    public function delete($id) {
        // Les points ODJ seront supprimés en cascade si la contrainte FK est définie
        $stmt = $this->db->prepare("DELETE FROM seances WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
