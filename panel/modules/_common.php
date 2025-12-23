<?php
/**
 * COMMON BOOTSTRAP
 * Location: panel/modules/_common.php
 */

if (defined('MODULE_BOOTSTRAP_LOADED')) {
    return;
}
define('MODULE_BOOTSTRAP_LOADED', true);

// Define ROOT_PATH
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
}

// Load core files
require_once ROOT_PATH . '/includes/config/config.php';
require_once ROOT_PATH . '/includes/core/Auth.php';
require_once ROOT_PATH . '/includes/core/Database.php';

// Load Logger if available
if (file_exists(ROOT_PATH . '/includes/core/Logger.php')) {
    require_once ROOT_PATH . '/includes/core/Logger.php';
    Logger::init();
}

// Load debug config if available
if (file_exists(ROOT_PATH . '/includes/config/debug.php')) {
    require_once ROOT_PATH . '/includes/config/debug.php';
}

// Check authentication
if (!Auth::check()) {
    header('Location: ' . ROOT_PATH . '/login.php');
    exit();
}

// Get user
$user = Auth::user();

if (!$user) {
    Auth::logout();
    header('Location: ' . ROOT_PATH . '/login.php');
    exit();
}

// Make user info accessible with CORRECT field names from database
$current_user_code = $user['user_code'] ?? '';
$current_user_name = $user['name'] ?? '';  // FIXED: was 'username'
$current_user_email = $user['email'] ?? '';
$current_user_level = $user['level'] ?? 'user';  // FIXED: was 'role'

/**
 * Get Database Connection
 */
function getDB() {
    try {
        $db = Database::getInstance();
        return $db->getConnection();
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        if (class_exists('Logger')) {
            Logger::error('Database Connection Failed', ['error' => $e->getMessage()]);
        }
        die("System error: Unable to connect to database.");
    }
}

/**
 * Require Admin Access
 */
function requireAdmin() {
    global $user;
    if (($user['level'] ?? '') !== 'admin') {
        http_response_code(403);
        if (class_exists('Logger')) {
            Logger::warning('Unauthorized admin access attempt', ['user' => $user['user_code'] ?? 'unknown']);
        }
        die('
            <div style="font-family: Arial; padding: 50px; text-align: center;">
                <h1 style="color: #e53e3e;">Access Denied</h1>
                <p>Administrator privileges required.</p>
                <a href="../admin.php" style="color: #667eea;">Return to Dashboard</a>
            </div>
        ');
    }
}

/**
 * Require Minimum Level
 */
function requireLevel($minLevel) {
    global $user;
    $levels = ['user' => 1, 'recruiter' => 2, 'manager' => 3, 'admin' => 4];
    
    $userLevel = $levels[$user['level'] ?? 'user'] ?? 0;
    $requiredLevel = $levels[$minLevel] ?? 999;
    
    if ($userLevel < $requiredLevel) {
        http_response_code(403);
        die('
            <div style="font-family: Arial; padding: 50px; text-align: center;">
                <h1 style="color: #e53e3e;">Access Denied</h1>
                <p>Required level: <strong>' . ucfirst($minLevel) . '</strong></p>
                <a href="../' . ($user['level'] == 'admin' ? 'admin' : 'recruiter') . '.php">Return</a>
            </div>
        ');
    }
}

/**
 * Log Activity
 */
function logActivity($action, $module, $recordId = null, $details = null) {
    global $user;
    
    // Log to Logger if available
    if (class_exists('Logger')) {
        Logger::activity($action, $module, [
            'record_id' => $recordId,
            'details' => $details
        ]);
    }
    
    try {
        $conn = getDB();
        
        $tableExists = $conn->query("SHOW TABLES LIKE 'activity_log'");
        if ($tableExists->num_rows === 0) {
            return;
        }
        
        $userCode = $user['user_code'] ?? 'UNKNOWN';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_code, action, module, record_id, details, ip_address, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param('ssssss', $userCode, $action, $module, $recordId, $details, $ipAddress);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }
}

/**
 * Safe Query
 */
function safeQuery($conn, $query, $context = '') {
    $result = $conn->query($query);
    if (!$result) {
        $errorMsg = "Query Error";
        if ($context) $errorMsg .= " ($context)";
        $errorMsg .= ": " . $conn->error;
        
        error_log($errorMsg);
        error_log("SQL: " . $query);
        
        if (class_exists('Logger')) {
            Logger::query($query, [], $conn->error);
        }
        
        return false;
    }
    return $result;
}

/**
 * Generate Unique Code
 */
function generateCode($prefix, $length = 6) {
    return strtoupper($prefix) . '/' . str_pad(mt_rand(1, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

/**
 * Sanitize Input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate Email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Format Date
 */
function formatDate($date, $format = 'M j, Y') {
    if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

/**
 * Time Ago
 */
function timeAgo($datetime) {
    if (empty($datetime)) return 'Never';
    
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return date('M j, Y', $timestamp);
}

/**
 * Redirect
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * JSON Response
 */
function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Set timezone
date_default_timezone_set('Europe/Brussels');

// Configure error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Log bootstrap completion
if (class_exists('Logger')) {
    Logger::debug('Module bootstrap loaded', [
        'user' => $user['user_code'] ?? 'unknown',
        'module' => basename(dirname($_SERVER['SCRIPT_FILENAME']))
    ]);
}
?>