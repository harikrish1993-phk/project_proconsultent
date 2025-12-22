<?php
/**
 * Activity Logging Class
 * Tracks user actions for audit trail
 */
class ActivityLogger {
    private static $conn = null;
    
    /**
     * Initialize database connection
     */
    private static function initDB() {
        if (self::$conn === null) {
            require_once __DIR__ . '/../config/config.php';
            self::$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (!self::$conn) {
                error_log('ActivityLogger DB connection failed: ' . mysqli_connect_error());
                return false;
            }
            mysqli_set_charset(self::$conn, DB_CHARSET);
        }
        return true;
    }
    
    /**
     * Log an activity
     * 
     * @param string $action Action performed (e.g., 'login', 'create_candidate')
     * @param string $entityType Entity affected (e.g., 'candidate', 'job')
     * @param string $entityId ID of affected entity
     * @param array $details Additional details (will be JSON encoded)
     * @param string $userCode User who performed action (optional, defaults to current user)
     */
    public static function log($action, $entityType = null, $entityId = null, $details = [], $userCode = null) {
        try {
            if (!self::initDB()) {
                return false;
            }
            
            // Get user code from session if not provided
            if ($userCode === null) {
                Session::start();
                $userCode = $_SESSION['user_code'] ?? null;
            }
            
            // Get client info
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Convert details to JSON
            $detailsJson = !empty($details) ? json_encode($details) : null;
            
            // Insert log
            $stmt = self::$conn->prepare(
                "INSERT INTO activity_log 
                (user_code, action, entity_type, entity_id, details, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            
            $stmt->bind_param(
                'sssssss',
                $userCode,
                $action,
                $entityType,
                $entityId,
                $detailsJson,
                $ipAddress,
                $userAgent
            );
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
            
        } catch (Exception $e) {
            error_log("ActivityLogger error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's activity history
     */
    public static function getUserActivity($userCode, $limit = 50) {
        try {
            if (!self::initDB()) {
                return [];
            }
            
            $stmt = self::$conn->prepare(
                "SELECT * FROM activity_log 
                WHERE user_code = ? 
                ORDER BY created_at DESC 
                LIMIT ?"
            );
            
            $stmt->bind_param('si', $userCode, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $activities = [];
            while ($row = $result->fetch_assoc()) {
                if ($row['details']) {
                    $row['details'] = json_decode($row['details'], true);
                }
                $activities[] = $row;
            }
            
            $stmt->close();
            return $activities;
            
        } catch (Exception $e) {
            error_log("Get activity error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get entity activity history
     */
    public static function getEntityActivity($entityType, $entityId, $limit = 50) {
        try {
            if (!self::initDB()) {
                return [];
            }
            
            $stmt = self::$conn->prepare(
                "SELECT a.*, u.name as user_name 
                FROM activity_log a 
                LEFT JOIN user u ON a.user_code = u.user_code 
                WHERE a.entity_type = ? AND a.entity_id = ? 
                ORDER BY a.created_at DESC 
                LIMIT ?"
            );
            
            $stmt->bind_param('ssi', $entityType, $entityId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $activities = [];
            while ($row = $result->fetch_assoc()) {
                if ($row['details']) {
                    $row['details'] = json_decode($row['details'], true);
                }
                $activities[] = $row;
            }
            
            $stmt->close();
            return $activities;
            
        } catch (Exception $e) {
            error_log("Get entity activity error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search activity logs
     */
    public static function search($filters = [], $limit = 100) {
        try {
            if (!self::initDB()) {
                return [];
            }
            
            $where = [];
            $params = [];
            $types = '';
            
            if (!empty($filters['user_code'])) {
                $where[] = "user_code = ?";
                $params[] = $filters['user_code'];
                $types .= 's';
            }
            
            if (!empty($filters['action'])) {
                $where[] = "action = ?";
                $params[] = $filters['action'];
                $types .= 's';
            }
            
            if (!empty($filters['entity_type'])) {
                $where[] = "entity_type = ?";
                $params[] = $filters['entity_type'];
                $types .= 's';
            }
            
            if (!empty($filters['from_date'])) {
                $where[] = "created_at >= ?";
                $params[] = $filters['from_date'];
                $types .= 's';
            }
            
            if (!empty($filters['to_date'])) {
                $where[] = "created_at <= ?";
                $params[] = $filters['to_date'];
                $types .= 's';
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $sql = "SELECT a.*, u.name as user_name 
                    FROM activity_log a 
                    LEFT JOIN user u ON a.user_code = u.user_code 
                    $whereClause 
                    ORDER BY a.created_at DESC 
                    LIMIT ?";
            
            $stmt = self::$conn->prepare($sql);
            
            $params[] = $limit;
            $types .= 'i';
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $activities = [];
            while ($row = $result->fetch_assoc()) {
                if ($row['details']) {
                    $row['details'] = json_decode($row['details'], true);
                }
                $activities[] = $row;
            }
            
            $stmt->close();
            return $activities;
            
        } catch (Exception $e) {
            error_log("Activity search error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean up old logs (for maintenance)
     */
    public static function cleanup($daysToKeep = 90) {
        try {
            if (!self::initDB()) {
                return false;
            }
            
            $stmt = self::$conn->prepare(
                "DELETE FROM activity_log 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
            );
            
            $stmt->bind_param('i', $daysToKeep);
            $result = $stmt->execute();
            $affected = self::$conn->affected_rows;
            $stmt->close();
            
            error_log("Activity log cleanup: Deleted $affected old records");
            return $affected;
            
        } catch (Exception $e) {
            error_log("Activity cleanup error: " . $e->getMessage());
            return false;
        }
    }
}

// Helper function for quick logging
function logActivity($action, $entityType = null, $entityId = null, $details = []) {
    return ActivityLogger::log($action, $entityType, $entityId, $details);
}
?>