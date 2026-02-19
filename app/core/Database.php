<?php
namespace app\core;
use \PDO;

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            self::$instance = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        }
        return self::$instance;
    }
}