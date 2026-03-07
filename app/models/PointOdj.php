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

    /**
     * Met à jour le titre et le type d'un point
     */
    public function updateMeta(int $id, string $titre, string $typePoint): bool
    {
        $allowed = ['information', 'deliberation', 'vote', 'divers'];
        if (!in_array($typePoint, $allowed)) return false;

        $stmt = $this->db->prepare(
            'UPDATE points_odj SET titre = :titre, type_point = :type WHERE id = :id'
        );
        return $stmt->execute([':titre' => trim($titre), ':type' => $typePoint, ':id' => $id]);
    }

    /**
     * Met à jour la note interne d'un point
     */
    public function updateNoteInterne(int $id, string $note): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE points_odj SET note_interne = :note WHERE id = :id'
        );
        return $stmt->execute([':note' => $note, ':id' => $id]);
    }

    /**
     * Pose un verrou sur un point (ou rafraîchit le verrou existant du même user)
     */
    public function lockPoint(int $pointId, int $userId, string $userName): void
    {
        // Nettoyage des verrous expirés (> 60s) au passage
        $this->db->prepare(
            'DELETE FROM point_locks WHERE locked_at < DATE_SUB(NOW(), INTERVAL 60 SECOND)'
        )->execute();

        $stmt = $this->db->prepare('
            INSERT INTO point_locks (point_odj_id, user_id, user_name, locked_at)
            VALUES (:pid, :uid, :uname, NOW())
            ON DUPLICATE KEY UPDATE
                user_id   = :uid,
                user_name = :uname,
                locked_at = NOW()
        ');
        $stmt->execute([':pid' => $pointId, ':uid' => $userId, ':uname' => $userName]);
    }

    /**
     * Libère le verrou d'un point pour un utilisateur donné
     */
    public function unlockPoint(int $pointId, int $userId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM point_locks WHERE point_odj_id = :pid AND user_id = :uid'
        );
        $stmt->execute([':pid' => $pointId, ':uid' => $userId]);
    }

    /**
     * Retourne tous les verrous actifs pour une séance
     * Résultat : [ point_odj_id => ['user_id' => X, 'user_name' => '...'] ]
     */
    public function getActiveLocks(int $seanceId): array
    {
        // Nettoyage des verrous expirés au passage
        $this->db->prepare(
            'DELETE FROM point_locks WHERE locked_at < DATE_SUB(NOW(), INTERVAL 60 SECOND)'
        )->execute();

        $stmt = $this->db->prepare('
            SELECT pl.point_odj_id, pl.user_id, pl.user_name
            FROM point_locks pl
            INNER JOIN points_odj po ON po.id = pl.point_odj_id
            WHERE po.seance_id = :seance_id
        ');
        $stmt->execute([':seance_id' => $seanceId]);

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[$row['point_odj_id']] = [
                'user_id'   => $row['user_id'],
                'user_name' => $row['user_name'],
            ];
        }
        return $result;
    }
}
