<?php
/**
 * Settings Core Class
 * Manages dynamic system settings stored in the database.
 */

namespace Core;

class Settings {
    private static $instance = null;
    private $settings = [];
    private $db;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->loadSettings();
    }

    public static function getInstance(): Settings {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadSettings() {
        $conn = $this->db->getConnection();
        $result = $conn->query("SELECT setting_key, setting_value FROM settings");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }

    public function get(string $key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    public function set(string $key, $value): bool {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        
        if (!$stmt) {
            error_log("Settings::set prepare failed: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param('sss', $key, $value, $value);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            $this->settings[$key] = $value; // Update local cache
        }
        
        return $success;
    }
    
    // Helper to get all settings for the Admin UI
    public function getAll(): array {
        return $this->settings;
    }
}
