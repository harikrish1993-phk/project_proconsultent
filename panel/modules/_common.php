<?php
/**
 * COMMON BOOTSTRAP - FIXED VERSION
 * Location: panel/modules/_common.php
 * 
 * This file initializes the application environment for all module pages.
 * It MUST be loaded first before any other code runs.
 */

// Prevent multiple loading
if (defined('MODULE_BOOTSTRAP_LOADED')) {
    return;
}
define('MODULE_BOOTSTRAP_LOADED', true);

// Start output buffering to prevent header issues
ob_start();

// Enable comprehensive error reporting during initialization
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Create logs directory if it doesn't exist
$logsDir = __DIR__ . '/../../logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0755, true);
}

// Define ROOT_PATH
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
}

// Initialize error tracking
$bootstrap_errors = [];
$bootstrap_warnings = [];

/**
 * STEP 1: Load Core Configuration
 */
try {
    $configPath = ROOT_PATH . '/includes/config/config.php';
    
    if (!file_exists($configPath)) {
        throw new Exception('Configuration file not found: ' . $configPath);
    }
    
    require_once $configPath;
    
    if (!defined('DB_HOST')) {
        throw new Exception('Configuration not properly loaded');
    }
    
} catch (Exception $e) {
    $bootstrap_errors[] = 'Configuration Error: ' . $e->getMessage();
    die('<!DOCTYPE html><html><head><title>Configuration Error</title></head><body style="font-family: Arial; padding: 50px;"><div style="background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px;"><h1 style="color: #856404;">⚠️ Configuration Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p><p><strong>Line:</strong> ' . $e->getLine() . '</p></div></body></html>');
}

/**
 * STEP 2: Load Authentication System
 */
try {
    $authPath = ROOT_PATH . '/includes/core/Auth.php';
    
    if (!file_exists($authPath)) {
        throw new Exception('Auth file not found: ' . $authPath);
    }
    
    require_once $authPath;
    
    if (!class_exists('Auth')) {
        throw new Exception('Auth class not loaded properly');
    }
    
} catch (Exception $e) {
    $bootstrap_errors[] = 'Authentication System Error: ' . $e->getMessage();
    die('<!DOCTYPE html><html><head><title>System Error</title></head><body style="font-family: Arial; padding: 50px;"><div style="background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px;"><h1 style="color: #856404;">⚠️ Authentication System Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></div></body></html>');
}

/**
 * STEP 3: Load Database System
 */
try {
    $dbPath = ROOT_PATH . '/includes/core/Database.php';
    
    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found: ' . $dbPath);
    }
    
    require_once $dbPath;
    
    if (!class_exists('Database')) {
        throw new Exception('Database class not loaded properly');
    }
    
} catch (Exception $e) {
    $bootstrap_errors[] = 'Database System Error: ' . $e->getMessage();
    die('<!DOCTYPE html><html><head><title>System Error</title></head><body style="font-family: Arial; padding: 50px;"><div style="background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px;"><h1 style="color: #856404;">⚠️ Database System Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></div></body></html>');
}

/**
 * STEP 4: Load Logger (Optional - Non-blocking)
 */
$loggerPath = ROOT_PATH . '/includes/core/Logger.php';
if (file_exists($loggerPath)) {
    try {
        require_once $loggerPath;
        if (class_exists('Logger')) {
            Logger::init();
        }
    } catch (Exception $e) {
        $bootstrap_warnings[] = 'Logger initialization failed: ' . $e->getMessage();
        error_log('Logger initialization failed: ' . $e->getMessage());
    }
} else {
    $bootstrap_warnings[] = 'Logger not found (non-critical)';
}

/**
 * STEP 5: Check Authentication
 */
$user = null;
$current_user_code = '';
$current_user_name = 'Guest';
$current_user_email = '';
$current_user_level = 'user';
$current_user_id = null;

try {
    if (!Auth::check()) {
        // Not authenticated - redirect to login
        $loginPath = ROOT_PATH . '/login.php';
        $loginUrl = str_replace($_SERVER['DOCUMENT_ROOT'], '', $loginPath);
        
        // Clean up output buffer before redirect
        ob_end_clean();
        header('Location: ' . $loginUrl);
        exit();
    }
    
    $user = Auth::user();
    
    if (!$user) {
        // Auth check passed but no user data - logout and redirect
        Auth::logout();
        ob_end_clean();
        header('Location: ' . $loginUrl);
        exit();
    }
    
    // Set user variables
    $current_user_id = $user['id'] ?? null;
    $current_user_code = $user['user_code'] ?? '';
    $current_user_name = $user['name'] ?? 'Unknown';
    $current_user_email = $user['email'] ?? '';
    $current_user_level = $user['level'] ?? 'user';
    
} catch (Exception $e) {
    $bootstrap_errors[] = 'Authentication Error: ' . $e->getMessage();
    error_log('Authentication error in bootstrap: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Critical error - show error page
    ob_end_clean();
    die('<!DOCTYPE html><html><head><title>Authentication Error</title></head><body style="font-family: Arial; padding: 50px;"><div style="background: #fee; border: 2px solid #f00; padding: 20px; border-radius: 8px;"><h1 style="color: #721c24;">🔒 Authentication Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p><a href="' . ROOT_PATH . '/login.php" style="color: #667eea; text-decoration: none; font-weight: bold;">← Back to Login</a></p></div></body></html>');
}

/**
 * HELPER FUNCTIONS
 */

/**
 * Get Database Connection
 */
function getDB() {
    static $connection = null;
    
    if ($connection !== null) {
        return $connection;
    }
    
    try {
        if (class_exists('Database')) {
            $db = Database::getInstance();
            $connection = $db->getConnection();
            return $connection;
        }
        
        error_log('ERROR: Database class not available in getDB()');
        return null;
    } catch (Exception $e) {
        error_log('EXCEPTION in getDB(): ' . $e->getMessage());
        return null;
    }
}

/**
 * Require Admin Access
 */
function requireAdmin() {
    global $user;
    
    if (!$user || ($user['level'] ?? '') !== 'admin') {
        http_response_code(403);
        ob_end_clean();
        die('<!DOCTYPE html><html><head><title>Access Denied</title></head><body style="font-family: Arial; padding: 50px; text-align: center;"><div style="background: #fee; border: 2px solid #f00; padding: 40px; border-radius: 12px; display: inline-block; margin: 50px auto;"><h1 style="color: #e53e3e; font-size: 48px; margin-bottom: 20px;">⛔</h1><h2 style="color: #721c24; margin-bottom: 10px;">Access Denied</h2><p style="color: #721c24; margin-bottom: 20px;">Administrator privileges required to access this page.</p><a href="' . ROOT_PATH . '/panel/route.php" style="display: inline-block; background: #667eea; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: bold;">← Return to Dashboard</a></div></body></html>');
    }
}

/**
 * Require Minimum Level
 */
function requireLevel($minLevel) {
    global $user;
    
    if (!$user) {
        http_response_code(403);
        ob_end_clean();
        die('<!DOCTYPE html><html><head><title>Access Denied</title></head><body style="font-family: Arial; padding: 50px; text-align: center;"><div style="background: #fee; border: 2px solid #f00; padding: 40px; border-radius: 12px; display: inline-block;"><h1 style="color: #e53e3e;">Access Denied</h1><p>Authentication required</p></div></body></html>');
    }
    
    $levels = ['user' => 1, 'recruiter' => 2, 'manager' => 3, 'admin' => 4];
    $userLevel = $levels[$user['level'] ?? 'user'] ?? 0;
    $requiredLevel = $levels[$minLevel] ?? 999;
    
    if ($userLevel < $requiredLevel) {
        http_response_code(403);
        ob_end_clean();
        die('<!DOCTYPE html><html><head><title>Access Denied</title></head><body style="font-family: Arial; padding: 50px; text-align: center;"><div style="background: #fee; border: 2px solid #f00; padding: 40px; border-radius: 12px; display: inline-block;"><h1 style="color: #e53e3e;">Access Denied</h1><p>Required level: <strong>' . ucfirst($minLevel) . '</strong></p><p>Your level: <strong>' . ucfirst($user['level'] ?? 'unknown') . '</strong></p><a href="' . ROOT_PATH . '/panel/route.php" style="display: inline-block; background: #667eea; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; margin-top: 20px;">← Return to Dashboard</a></div></body></html>');
    }
}

/**
 * Log Activity
 */
function logActivity($action, $module, $details = [], $recordId = null) {
    global $user;
    
    // Try Logger first
    try {
        if (class_exists('Logger')) {
            Logger::activity($action, $module, [
                'record_id' => $recordId,
                'details' => $details
            ]);
        }
    } catch (Exception $e) {
        error_log('Logger activity failed: ' . $e->getMessage());
    }
    
    // Try database logging
    try {
        $conn = getDB();
        if (!$conn) {
            return;
        }
        
        $tableCheck = $conn->query("SHOW TABLES LIKE 'activity_log'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            return;
        }
        
        $userCode = $user['user_code'] ?? 'SYSTEM';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $detailsJson = json_encode($details);
        
        $stmt = $conn->prepare(
            "INSERT INTO activity_log (user_code, action, module, record_id, details, ip_address, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        
        if ($stmt) {
            $stmt->bind_param('ssssss', $userCode, $action, $module, $recordId, $detailsJson, $ipAddress);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log('Activity logging error: ' . $e->getMessage());
    }
}

/**
 * Safe Query Execution
 */
function safeQuery($conn, $query, $context = '') {
    if (!$conn) {
        error_log("safeQuery: null connection ($context)");
        return false;
    }
    
    try {
        $result = $conn->query($query);
        
        if (!$result) {
            error_log("Query Error ($context): " . $conn->error);
            error_log("SQL: " . $query);
            
            if (class_exists('Logger')) {
                Logger::query($query, [], $conn->error);
            }
            
            return false;
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Query exception ($context): " . $e->getMessage());
        return false;
    }
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
    if (!$timestamp) return 'Invalid date';
    
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
    ob_end_clean();
    header("Location: $url");
    exit();
}

/**
 * JSON Response
 */
function jsonResponse($data) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Show Bootstrap Errors
 */
function showBootstrapErrors() {
    global $bootstrap_errors, $bootstrap_warnings;
    
    if (!empty($bootstrap_errors)) {
        echo '<div style="background: #fee; border-left: 4px solid #f00; padding: 15px; margin: 20px; border-radius: 6px;">';
        echo '<h3 style="color: #721c24; margin-bottom: 10px;">🔴 Bootstrap Errors</h3>';
        echo '<ul style="color: #721c24; margin: 0;">';
        foreach ($bootstrap_errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }
    
    if (!empty($bootstrap_warnings) && DEBUG_MODE) {
        echo '<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px; border-radius: 6px;">';
        echo '<h3 style="color: #856404; margin-bottom: 10px;">⚠️ Bootstrap Warnings</h3>';
        echo '<ul style="color: #856404; margin: 0;">';
        foreach ($bootstrap_warnings as $warning) {
            echo '<li>' . htmlspecialchars($warning) . '</li>';
        }
        echo '</ul></div>';
    }
}

// Set timezone
date_default_timezone_set('Europe/Brussels');

// Log bootstrap completion
if (class_exists('Logger')) {
    try {
        Logger::debug('Module bootstrap loaded', [
            'user' => $current_user_code,
            'module' => basename(dirname($_SERVER['SCRIPT_FILENAME'])),
            'errors' => count($bootstrap_errors),
            'warnings' => count($bootstrap_warnings)
        ]);
    } catch (Exception $e) {
        error_log('Logger debug failed: ' . $e->getMessage());
    }
}

// Display errors if in debug mode
if (DEBUG_MODE && (!empty($bootstrap_errors) || !empty($bootstrap_warnings))) {
    // These will be output when the buffer is flushed
    ob_start();
    showBootstrapErrors();
    $errorHtml = ob_get_clean();
    // Store for later output
    define('BOOTSTRAP_ERROR_HTML', $errorHtml);
}

// End output buffering - content is now ready to be sent
ob_end_flush();

// Bootstrap complete
error_log('=== BOOTSTRAP COMPLETE ===');
error_log('User: ' . $current_user_name . ' (' . $current_user_code . ')');
error_log('Level: ' . $current_user_level);
error_log('Module: ' . basename(dirname($_SERVER['SCRIPT_FILENAME'])));
error_log('==========================');
?>