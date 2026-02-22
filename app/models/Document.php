<?php
namespace app\models;

use app\core\Database;
use PDO;

class Document {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getBySeance($seanceId) {
        $stmt = $this->db->prepare("SELECT * FROM documents WHERE seance_id = ? ORDER BY type_doc ASC, nom ASC");
        $stmt->execute([$seanceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($seanceId, $pointId, $nom, $cheminFichier, $typeDoc = 'annexe') {
        $stmt = $this->db->prepare("INSERT INTO documents (seance_id, point_odj_id, nom, chemin_fichier, type_doc) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$seanceId, $pointId ?: null, $nom, $cheminFichier, $typeDoc]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM documents WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
