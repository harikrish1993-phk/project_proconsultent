<?php
namespace Core;

class Settings {
    private static $instance = null;
    private $conn;
    private $cache = [];
    
    private function __construct() {
        $db = \Database::getInstance();
        $this->conn = $db->getConnection();
        $this->loadAll();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadAll() {
        $result = $this->conn->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $result->fetch_assoc()) {
            $this->cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    public function get($key, $default = null) {
        return $this->cache[$key] ?? $default;
    }
    
    public function set($key, $value) {
        $stmt = $this->conn->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $stmt->bind_param('sss', $key, $value, $value);
        $stmt->execute();
        $this->cache[$key] = $value;
        return true;
    }
    
    public function getAll() {
        return $this->cache;
    }
}