<?php
namespace app\models;

use app\core\Database;

class Parametre {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function get($key) {
        $stmt = $this->db->prepare("SELECT s_value FROM settings WHERE s_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetchColumn();
    }

    public function set($key, $value) {
    $stmt = $this->db->prepare("INSERT INTO settings (s_key, s_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE s_value = ?");
    return $stmt->execute([$key, $value, $value]);
    }
}