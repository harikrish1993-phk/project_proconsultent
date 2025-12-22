<?php
/**
 * Database Class - Singleton pattern for database connections
 */
class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Private constructor - Singleton pattern
     */
    private function __construct() {
        require_once __DIR__ . '/../config/config.php';
        
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            error_log("DB connect error: " . $this->connection->connect_error);
            throw new Exception('Database connection failed: ' . $this->connection->connect_error);
        }
        
        $this->connection->set_charset(DB_CHARSET);
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     * @throws Exception if connection is lost
     */
    public function getConnection() {
        if (!$this->connection || $this->connection->ping() === false) {
            throw new Exception('DB connection lost');
        }
        return $this->connection;
    }
    
    /**
     * Prepare statement
     */
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    /**
     * Execute query
     */
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    /**
     * Escape string
     */
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    /**
     * Get last insert ID
     */
    public function getLastInsertId() {
        return $this->connection->insert_id;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->connection->begin_transaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->connection->rollback();
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>