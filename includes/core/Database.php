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
        
        // error_log('Attempting DB connection: Host=' . DB_HOST); // ADDED: Logging
        
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            error_log("DB connect error: " . $this->connection->connect_error); // Already there
            throw new Exception('Database connection failed: ' . $this->connection->connect_error);
        }
        
        $this->connection->set_charset(DB_CHARSET);
        error_log('DB connection successful');
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
/**
 * Get database connection
 */
public function getConnection() {
    return $this->connection;
}

/**
 * Prepare statement with error handling
 */
public function prepare($sql) {
    if (!$this->connection) {
        throw new Exception('No database connection');
    }
    
    $stmt = $this->connection->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $this->connection->error);
    }
    return $stmt;
}

/**
 * Execute query with error handling
 */
public function query($sql) {
    if (!$this->connection) {
        throw new Exception('No database connection');
    }
    
    $result = $this->connection->query($sql);
    if ($result === false) {
        throw new Exception('Query failed: ' . $this->connection->error);
    }
    return $result;
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