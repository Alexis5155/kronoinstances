<?php
namespace app\models;

use app\core\Database;
use PDO;

class Seance {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Récupère les prochaines séances à venir
     * @param int $limit le nombre de séances à récupérer
     * @return array les prochaines séances avec le nom de l'instance associée
     */
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

    /**
     * Récupère les séances avec filtres optionnels
     * @param string $periode 'futur' ou 'passe' pour filtrer par date
     * @param string $instanceId pour filtrer par instance
     * @param string $dateDebut pour filtrer les séances à partir de cette date
     * @param string $dateFin pour filtrer les séances jusqu'à cette date
     * @return array les séances filtrées avec le nombre de points à l'ordre du jour
     */
    public function getFiltered(string $periode = '', string $instanceId = '', string $dateDebut = '', string $dateFin = ''): array {
        $sql = "SELECT s.*, i.nom as instance_nom,
                       (SELECT COUNT(*) FROM points_odj p WHERE p.seance_id = s.id) as nb_points
                FROM seances s
                JOIN instances i ON s.instance_id = i.id
                WHERE 1=1";

        $params = [];

        if ($periode === 'futur') {
            $sql .= " AND s.date_seance >= CURDATE()";
        } elseif ($periode === 'passe') {
            $sql .= " AND s.date_seance < CURDATE()";
        }

        if (!empty($instanceId)) {
            $sql .= " AND s.instance_id = :instance_id";
            $params[':instance_id'] = $instanceId;
        }

        if (!empty($dateDebut)) {
            $sql .= " AND s.date_seance >= :date_debut";
            $params[':date_debut'] = $dateDebut;
        }

        if (!empty($dateFin)) {
            $sql .= " AND s.date_seance <= :date_fin";
            $params[':date_fin'] = $dateFin;
        }

        $sql .= " ORDER BY s.date_seance DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
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
        $stmt = $this->db->prepare("INSERT INTO seances (instance_id, date_seance, heure_debut, lieu, statut) VALUES (?, ?, ?, ?, 'brouillon')");
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

    public function updatePvPath($id, $path) {
        $stmt = $this->db->prepare("UPDATE seances SET proces_verbal_path = ? WHERE id = ?");
        return $stmt->execute([$path, $id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM seances WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getMembresAvecEmail($instanceId) {
        $stmt = $this->db->prepare("
            SELECT id, nom, prenom, email, college, type_mandat
            FROM membres
            WHERE instance_id = ?
              AND type_mandat = 'titulaire'
              AND email IS NOT NULL AND email != ''
        ");
        $stmt->execute([$instanceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
