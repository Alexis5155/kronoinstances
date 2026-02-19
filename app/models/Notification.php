<?php
namespace app\models;
use app\core\Database;
use \PDO;

class Notification {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public static function add($userId, $type, $message, $link = null) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $type, $message, $link]);
    }

    public function getForUser($userId, $limit = 5, $onlyUnread = false, $offset = 0) {
        $sql = "SELECT * FROM notifications WHERE user_id = :uid";
        if ($onlyUnread) $sql .= " AND is_read = 0";
        
        $sql .= " ORDER BY created_at DESC LIMIT :offset, :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function count($userId, $status = 'all') {
        $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = ?";
        $params = [$userId];

        if ($status === 'unread') {
            $sql .= " AND is_read = 0";
        } elseif ($status === 'read') {
            $sql .= " AND is_read = 1";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function markAsRead($id, $userId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public function markAllRead($userId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    public function markAsUnread($id, $userId) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 0 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public function delete($id, $userId) {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}