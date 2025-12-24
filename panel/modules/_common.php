<?php
/**
 * COMMON BOOTSTRAP - NON-BLOCKING VERSION
 * Location: panel/modules/_common.php
 */

if (defined('MODULE_BOOTSTRAP_LOADED')) {
    error_log('Bootstrap already loaded, returning');
    return;
}
define('MODULE_BOOTSTRAP_LOADED', true);

// Add logging at bootstrap start
error_log('Bootstrap started: ' . __FILE__);
error_log('Script filename: ' . $_SERVER['SCRIPT_FILENAME']);
error_log('Current directory: ' . __DIR__);

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Define ROOT_PATH
if (!defined('ROOT_PATH')) {
    $rootPath = dirname(dirname(dirname(__FILE__)));
    define('ROOT_PATH', $rootPath);
    error_log('ROOT_PATH defined as: ' . $rootPath);
} else {
    error_log('ROOT_PATH already defined: ' . ROOT_PATH);
}

// Initialize error tracking
$bootstrap_errors = [];
error_log('Bootstrap errors array initialized');

// Load core files with error handling
try {
    $configPath = ROOT_PATH . '/includes/config/config.php';
    error_log('Looking for config at: ' . $configPath);
    
    if (file_exists($configPath)) {
        error_log('Config file found, including...');
        require_once $configPath;
        error_log('Config file loaded');
    } else {
        $bootstrap_errors[] = 'Config file not found: ' . $configPath;
        error_log('ERROR: Config file not found');
    }
    
    $authPath = ROOT_PATH . '/includes/core/Auth.php';
    error_log('Looking for Auth at: ' . $authPath);
    
    // if (file_exists($authPath)) {
    //     error_log('Auth file found, including...');
    //     require_once $authPath;
    //     error_log('Auth file loaded');
    // } else {
    //     $bootstrap_errors[] = 'Auth file not found';
    //     error_log('ERROR: Auth file not found');
    // }
    
    $dbPath = ROOT_PATH . '/includes/core/Database.php';
    error_log('Looking for Database at: ' . $dbPath);
    
    if (file_exists($dbPath)) {
        error_log('Database file found, including...');
        require_once $dbPath;
        error_log('Database file loaded');
    } else {
        $bootstrap_errors[] = 'Database file not found';
        error_log('ERROR: Database file not found');
    }
} catch (Exception $e) {
    $bootstrap_errors[] = 'Core file loading error: ' . $e->getMessage();
    error_log('EXCEPTION in core loading: ' . $e->getMessage());
}

// Log core loading status
error_log('Core files loaded, Auth class exists: ' . (class_exists('Auth') ? 'YES' : 'NO'));
error_log('Core files loaded, Database class exists: ' . (class_exists('Database') ? 'YES' : 'NO'));

// Load Logger (optional - non-blocking)
$loggerPath = ROOT_PATH . '/includes/core/Logger.php';
error_log('Looking for Logger at: ' . $loggerPath);

if (file_exists($loggerPath)) {
    try {
        error_log('Logger file found, including...');
        require_once $loggerPath;
        if (class_exists('Logger')) {
            Logger::init();
            error_log('Logger initialized');
        } else {
            error_log('Logger class not found after require');
        }
    } catch (Exception $e) {
        error_log('Logger initialization failed: ' . $e->getMessage());
    }
} else {
    error_log('Logger file not found (optional)');
}

// Check authentication (non-blocking)
$user = null;
$current_user_code = '';
$current_user_name = 'Guest';
$current_user_email = '';
$current_user_level = 'user';

error_log('Starting authentication check...');

try {
    if (class_exists('Auth')) {
        error_log('Auth class exists, calling Auth::check()');
        
        if (!Auth::check()) {
            error_log('Auth::check() returned false, redirecting to login');
            header('Location: ' . ROOT_PATH . '/login.php');
            exit();
        }
        
        error_log('Auth::check() passed, getting user...');
        $user = Auth::user();
        error_log('User data retrieved: ' . ($user ? 'YES' : 'NO'));
        
        if (!$user) {
            error_log('No user data, logging out...');
            Auth::logout();
            header('Location: ' . ROOT_PATH . '/login.php');
            exit();
        }
        
        // Make user info accessible
        $current_user_code = $user['user_code'] ?? '';
        $current_user_name = $user['name'] ?? 'Unknown';
        $current_user_email = $user['email'] ?? '';
        $current_user_level = $user['level'] ?? 'user';
        
        error_log('User authenticated: ' . $current_user_name . ' (' . $current_user_code . ')');
        error_log('User level: ' . $current_user_level);
        error_log('User email: ' . $current_user_email);
        
    } else {
        $bootstrap_errors[] = 'Auth class not available';
        error_log('ERROR: Auth class not available');
    }
} catch (Exception $e) {
    $bootstrap_errors[] = 'Authentication error: ' . $e->getMessage();
    error_log('EXCEPTION in authentication: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
}

error_log('Authentication process completed');

/**
 * Get Database Connection (non-blocking)
 */
function getDB() {
    static $connection = null;
    
    error_log('getDB() called');
    
    if ($connection !== null) {
        error_log('Returning cached connection');
        return $connection;
    }
    
    try {
        if (class_exists('Database')) {
            error_log('Database class exists, getting instance...');
            $db = Database::getInstance();
            $connection = $db->getConnection();
            
            if ($connection) {
                error_log('Database connection established');
            } else {
                error_log('WARNING: Database connection is null');
            }
            
            return $connection;
        } else {
            error_log("ERROR: Database class not available in getDB()");
            return null;
        }
    } catch (Exception $e) {
        error_log("EXCEPTION in getDB(): " . $e->getMessage());
        return null;
    }
}

/**
 * Require Admin Access
 */
function requireAdmin() {
    global $user;
    error_log('requireAdmin() called');
    
    if (!$user || ($user['level'] ?? '') !== 'admin') {
        error_log('Admin access denied for user: ' . ($user['level'] ?? 'no user'));
        http_response_code(403);
        die('
            <div style="font-family: Arial; padding: 50px; text-align: center; background: #fee; border: 2px solid #f00; border-radius: 8px; margin: 20px;">
                <h1 style="color: #e53e3e;">⛔ Access Denied</h1>
                <p>Administrator privileges required.</p>
                <a href="../../admin.php" style="color: #667eea; text-decoration: none; font-weight: bold;">← Return to Dashboard</a>
            </div>
        ');
    }
    
    error_log('Admin access granted');
}

/**
 * Require Minimum Level
 */
function requireLevel($minLevel) {
    global $user;
    error_log('requireLevel() called with: ' . $minLevel);
    
    if (!$user) {
        error_log('Access denied: Not authenticated');
        http_response_code(403);
        die('Access denied: Not authenticated');
    }
    
    $levels = ['user' => 1, 'recruiter' => 2, 'manager' => 3, 'admin' => 4];
    $userLevel = $levels[$user['level'] ?? 'user'] ?? 0;
    $requiredLevel = $levels[$minLevel] ?? 999;
    
    error_log('User level: ' . ($user['level'] ?? 'unknown') . ' (value: ' . $userLevel . ')');
    error_log('Required level: ' . $minLevel . ' (value: ' . $requiredLevel . ')');
    
    if ($userLevel < $requiredLevel) {
        error_log('Access denied: Insufficient level');
        http_response_code(403);
        die('
            <div style="font-family: Arial; padding: 50px; text-align: center;">
                <h1 style="color: #e53e3e;">Access Denied</h1>
                <p>Required level: <strong>' . ucfirst($minLevel) . '</strong></p>
                <p>Your level: <strong>' . ucfirst($user['level'] ?? 'unknown') . '</strong></p>
            </div>
        ');
    }
    
    error_log('Level requirement satisfied');
}

/**
 * Log Activity (non-blocking) - FIXED VERSION
 */
function logActivity($action, $module, $details = [], $recordId = null) {
    global $user;
    
    error_log('logActivity called: ' . $action . ' in ' . $module);
    
    try {
        // Log to Logger if available
        if (class_exists('Logger')) {
            error_log('Logger available, logging activity...');
            Logger::activity($action, $module, [
                'record_id' => $recordId,
                'details' => $details
            ]);
            error_log('Logger activity logged');
        } else {
            error_log('Logger not available');
        }
    } catch (Exception $e) {
        error_log("Logger activity failed: " . $e->getMessage());
    }
    
    try {
        $conn = getDB();
        if (!$conn) {
            error_log('DB not available for activity logging - skipping');
            return; // Non-blocking
        }
        
        error_log('Checking for activity_log table...');
        $tableExists = $conn->query("SHOW TABLES LIKE 'activity_log'");
        if (!$tableExists || $tableExists->num_rows === 0) {
            error_log('activity_log table missing - skipping');
            return; // Non-blocking
        }
        
        $userCode = $user['user_code'] ?? 'UNKNOWN';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $detailsJson = json_encode($details); // FIXED: Encode array to string
        
        error_log('Preparing activity log statement for user: ' . $userCode);
        
        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_code, action, module, record_id, details, ip_address, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt) {
            $stmt->bind_param('ssssss', $userCode, $action, $module, $recordId, $detailsJson, $ipAddress); // FIXED: Bind encoded string
            $stmt->execute();
            $stmt->close();
            error_log('Activity logged successfully');
        } else {
            error_log('Statement prepare failed for activity log: ' . $conn->error);
        }
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }
}

/**
 * Safe Query (non-blocking)
 */
function safeQuery($conn, $query, $context = '') {
    error_log('safeQuery called for context: ' . $context);
    
    if (!$conn) {
        error_log("safeQuery called with null connection ($context)");
        return false;
    }
    
    try {
        error_log('Executing query: ' . substr($query, 0, 100) . '...');
        $result = $conn->query($query);
        if (!$result) {
            $errorMsg = "Query Error";
            if ($context) $errorMsg .= " ($context)";
            $errorMsg .= ": " . $conn->error;
            
            error_log($errorMsg);
            error_log("Full SQL: " . $query);
            
            if (class_exists('Logger')) {
                Logger::query($query, [], $conn->error);
            }
            
            return false;
        }
        
        error_log('Query executed successfully');
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
    error_log('generateCode called with prefix: ' . $prefix);
    $code = strtoupper($prefix) . '/' . str_pad(mt_rand(1, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    error_log('Generated code: ' . $code);
    return $code;
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
    $result = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    error_log('isValidEmail(' . $email . '): ' . ($result ? 'YES' : 'NO'));
    return $result;
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
    error_log('Redirecting to: ' . $url);
    header("Location: $url");
    exit();
}

/**
 * JSON Response
 */
function jsonResponse($data) {
    error_log('Sending JSON response');
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Show Bootstrap Errors (if any)
 */
function showBootstrapErrors() {
    global $bootstrap_errors;
    
    error_log('showBootstrapErrors called, count: ' . count($bootstrap_errors));
    
    if (!empty($bootstrap_errors)) {
        echo '<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px; border-radius: 6px;">';
        echo '<h3 style="color: #856404; margin-bottom: 10px;">⚠️ Bootstrap Warnings</h3>';
        echo '<ul style="color: #856404;">';
        foreach ($bootstrap_errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}

// Set timezone
date_default_timezone_set('Europe/Brussels');
error_log('Timezone set to Europe/Brussels');

// Log bootstrap completion
if (class_exists('Logger')) {
    try {
        error_log('Attempting Logger debug...');
        Logger::debug('Module bootstrap loaded', [
            'user' => $user['user_code'] ?? 'unknown',
            'module' => basename(dirname($_SERVER['SCRIPT_FILENAME'])),
            'errors' => count($bootstrap_errors)
        ]);
        error_log('Logger debug completed');
    } catch (Exception $e) {
        error_log('Logger debug failed: ' . $e->getMessage());
    }
} else {
    error_log('Logger not available for bootstrap completion log');
}

// Display bootstrap errors at top of page (non-blocking)
error_log('Checking for bootstrap errors to display...');
if (!empty($bootstrap_errors) && ini_get('display_errors')) {
    error_log('Displaying bootstrap errors');
    showBootstrapErrors();
}

// Final bootstrap log
error_log('Bootstrap completed successfully. Total errors: ' . count($bootstrap_errors));
error_log('Current user: ' . $current_user_name . ' (' . $current_user_code . ')');
error_log('===============================================');
?>