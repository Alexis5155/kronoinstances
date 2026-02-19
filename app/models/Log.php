<?php
namespace app\models;

use app\core\Database;
use \PDO;

class Log {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public static function add($action, $details = '', $target_id = null, $target_type = null, $old_value = null, $new_value = null) {
        $db = Database::getConnection();
        $sql = "INSERT INTO logs (user_id, action, target_id, target_type, old_value, new_value, details, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $target_id,
            $target_type,
            $old_value ? json_encode($old_value) : null,
            $new_value ? json_encode($new_value) : null,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);
    }

    public function getFiltered($filters, $limit, $offset) {
        $sqlData = $this->buildQuery($filters);
        
        $query = "SELECT l.*, u.username 
                  FROM logs l 
                  LEFT JOIN users u ON l.user_id = u.id 
                  WHERE " . $sqlData['where'] . " 
                  ORDER BY l.created_at DESC 
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);
        foreach ($sqlData['params'] as $key => $val) { $stmt->bindValue($key, $val); }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countFiltered($filters) {
        $sqlData = $this->buildQuery($filters);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM logs l WHERE " . $sqlData['where']);
        foreach ($sqlData['params'] as $key => $val) { $stmt->bindValue($key, $val); }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getUniqueActions() {
        return $this->db->query("SELECT DISTINCT action FROM logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);
    }

    private function buildQuery($filters) {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(l.details LIKE :search OR l.action LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }
        if (!empty($filters['agent'])) { $where[] = "l.user_id = :agent"; $params[':agent'] = $filters['agent']; }
        if (!empty($filters['action'])) { $where[] = "l.action = :action"; $params[':action'] = $filters['action']; }
        
        if (!empty($filters['date_debut'])) { $where[] = "l.created_at >= :debut"; $params[':debut'] = $filters['date_debut'] . " 00:00:00"; }
        if (!empty($filters['date_fin'])) { $where[] = "l.created_at <= :fin"; $params[':fin'] = $filters['date_fin'] . " 23:59:59"; }

        return ['where' => implode(" AND ", $where), 'params' => $params];
    }
}