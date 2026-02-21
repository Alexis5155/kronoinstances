<?php
namespace app\models;

use app\core\Database;
use PDO;

class Presence {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // Récupérer toutes les présences d'une séance (indexées par membre_id pour faciliter l'accès)
    public function getBySeance($seanceId) {
        $stmt = $this->db->prepare("SELECT * FROM presences WHERE seance_id = ?");
        $stmt->execute([$seanceId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach($rows as $r) {
            $result[$r['membre_id']] = $r;
        }
        return $result;
    }

    // Mettre à jour la présence d'un membre (Appel AJAX)
    public function update($seanceId, $membreId, $estPresent, $remplaceParId = null) {
        // On vérifie s'il existe déjà
        $stmt = $this->db->prepare("SELECT membre_id FROM presences WHERE seance_id = ? AND membre_id = ?");
        $stmt->execute([$seanceId, $membreId]);
        $exists = $stmt->fetch();

        if ($exists) {
            $upd = $this->db->prepare("UPDATE presences SET est_present = ?, remplace_par_id = ? WHERE seance_id = ? AND membre_id = ?");
            return $upd->execute([$estPresent ? 1 : 0, $remplaceParId ?: null, $seanceId, $membreId]);
        } else {
            $ins = $this->db->prepare("INSERT INTO presences (seance_id, membre_id, est_present, remplace_par_id) VALUES (?, ?, ?, ?)");
            return $ins->execute([$seanceId, $membreId, $estPresent ? 1 : 0, $remplaceParId ?: null]);
        }
    }
}
