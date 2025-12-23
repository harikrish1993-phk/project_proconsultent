<?php
/**
 * ============================================================================
 * COMMON BOOTSTRAP FILE FOR ALL MODULES
 * ============================================================================
 * 
 * Purpose: This file loads all necessary components for module functionality
 * Location: panel/modules/_common.php
 * 
 * Include this at the TOP of EVERY module file like this:
 * require_once __DIR__ . '/../_common.php';
 * 
 * What this file does:
 * - Defines project root path
 * - Loads configuration (database credentials, app settings)
 * - Loads authentication system
 * - Loads database connection handler
 * - Checks user is logged in
 * - Provides helper functions for common tasks
 * 
 * After including this file, you can use:
 * - $user (current logged-in user information)
 * - getDB() (database connection)
 * - Auth::user() (user details)
 * - All helper functions defined below
 * ============================================================================
 */

// Prevent multiple inclusions
if (defined('MODULE_BOOTSTRAP_LOADED')) {
    return;
}
define('MODULE_BOOTSTRAP_LOADED', true);

// ============================================================================
// 1. DEFINE PROJECT ROOT PATH
// ============================================================================
// Go up 3 levels from modules: modules -> panel -> root
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
}

// ============================================================================
// 2. LOAD CORE SYSTEM FILES
// ============================================================================
require_once ROOT_PATH . '/includes/config/config.php';
require_once ROOT_PATH . '/includes/core/Auth.php';
require_once ROOT_PATH . '/includes/core/Database.php';
// Load Logger
if (file_exists(ROOT_PATH . '/includes/core/Logger.php')) {
    require_once ROOT_PATH . '/includes/core/Logger.php';
    Logger::init();
}

// Load debug configuration
if (file_exists(ROOT_PATH . '/includes/config/debug.php')) {
    require_once ROOT_PATH . '/includes/config/debug.php';
}
// Optional: Load additional components if they exist
if (file_exists(ROOT_PATH . '/includes/core/ActivityLogger.php')) {
    require_once ROOT_PATH . '/includes/core/ActivityLogger.php';
}
if (file_exists(ROOT_PATH . '/includes/core/Mailer.php')) {
    require_once ROOT_PATH . '/includes/core/Mailer.php';
}

// ============================================================================
// 3. AUTHENTICATION & SESSION MANAGEMENT
// ============================================================================

// Check if user is logged in
if (!Auth::check()) {
    // User not authenticated - redirect to login
    header('Location: ' . ROOT_PATH . '/login.php');
    exit();
}

// Get current user information
$user = Auth::user();

if (!$user) {
    // Session exists but user data is missing - force re-login
    Auth::logout();
    header('Location: ' . ROOT_PATH . '/login.php');
    exit();
}

// Make user information easily accessible
$current_user_code = $user['user_code'];
$current_user_name = $user['name'];
$current_user_email = $user['email'];
$current_user_level = $user['level'];

// ============================================================================
// 4. HELPER FUNCTIONS
// ============================================================================

/**
 * Get Database Connection
 * Returns active database connection instance
 * 
 * @return mysqli Database connection
 */
function getDB() {
    try {
        $db = Database::getInstance();
        return $db->getConnection();
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        die("System error: Unable to connect to database. Please contact support.");
    }
}

/**
 * Require Admin Access
 * Blocks non-admin users from accessing the page
 * Use at top of admin-only modules
 */
function requireAdmin() {
    global $user;
    if ($user['level'] !== 'admin') {
        http_response_code(403);
        die('
            <div style="font-family: Arial; padding: 50px; text-align: center;">
                <h1 style="color: #e53e3e;">Access Denied</h1>
                <p>You need administrator privileges to access this page.</p>
                <a href="../admin.php" style="color: #667eea;">Return to Dashboard</a>
            </div>
        ');
    }
}

/**
 * Require Minimum User Level
 * Blocks users below specified level
 * 
 * @param string $minLevel Minimum required level (user, recruiter, manager, admin)
 */
function requireLevel($minLevel) {
    global $user;
    $levels = ['user' => 1, 'recruiter' => 2, 'manager' => 3, 'admin' => 4];
    
    $userLevel = $levels[$user['level']] ?? 0;
    $requiredLevel = $levels[$minLevel] ?? 999;
    
    if ($userLevel < $requiredLevel) {
        http_response_code(403);
        die('
            <div style="font-family: Arial; padding: 50px; text-align: center;">
                <h1 style="color: #e53e3e;">Access Denied</h1>
                <p>You do not have sufficient privileges to access this page.</p>
                <p>Required level: <strong>' . ucfirst($minLevel) . '</strong></p>
                <a href="../' . ($user['level'] == 'admin' ? 'admin' : 'recruiter') . '.php" style="color: #667eea;">Return to Dashboard</a>
            </div>
        ');
    }
}

/**
 * Log User Activity
 * Records user actions for audit trail
 * 
 * @param string $action Action performed (e.g., "created", "updated", "deleted")
 * @param string $module Module name (e.g., "candidates", "jobs", "users")
 * @param string $recordId ID of affected record
 * @param string $details Additional details (optional)
 */
function logActivity($action, $module, $recordId = null, $details = null) {
    global $user;
    
    try {
        $conn = getDB();
        
        // Check if activity_log table exists
        $tableExists = $conn->query("SHOW TABLES LIKE 'activity_log'");
        if ($tableExists->num_rows === 0) {
            return; // Table doesn't exist yet, skip logging
        }
        
        $userCode = $user['user_code'];
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
        // Don't halt execution if logging fails
    }
}

/**
 * Safe Query Execution with Error Logging
 * Executes query and logs any errors
 * 
 * @param mysqli $conn Database connection
 * @param string $query SQL query to execute
 * @param string $context Description for error log (e.g., "Get Candidates")
 * @return mysqli_result|false Query result or false on error
 */
function safeQuery($conn, $query, $context = '') {
    $result = $conn->query($query);
    if (!$result) {
        $errorMsg = "Query Error";
        if ($context) {
            $errorMsg .= " ($context)";
        }
        $errorMsg .= ": " . $conn->error;
        
        error_log($errorMsg);
        error_log("SQL: " . $query);
        
        return false;
    }
    return $result;
}

/**
 * Generate Unique Code
 * Creates unique identifier for records (e.g., CAN/001234, JOB/005678)
 * 
 * @param string $prefix Code prefix (e.g., "CAN", "JOB", "CLI")
 * @param int $length Number of digits (default: 6)
 * @return string Generated code
 */
function generateCode($prefix, $length = 6) {
    return strtoupper($prefix) . '/' . str_pad(mt_rand(1, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

/**
 * Sanitize User Input
 * Cleans input to prevent XSS attacks
 * 
 * @param mixed $input Input to sanitize (string or array)
 * @return mixed Sanitized input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate Email Address
 * Checks if email format is valid
 * 
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Format Date for Display
 * Converts database date to readable format
 * 
 * @param string $date Date from database
 * @param string $format Display format (default: "M j, Y")
 * @return string Formatted date
 */
function formatDate($date, $format = 'M j, Y') {
    if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

/**
 * Get Time Ago
 * Converts timestamp to human-readable "time ago" format
 * 
 * @param string $datetime Database datetime
 * @return string Human-readable time (e.g., "2 hours ago")
 */
function timeAgo($datetime) {
    if (empty($datetime)) {
        return 'Never';
    }
    
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

/**
 * Redirect Helper
 * Performs safe redirect
 * 
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * JSON Response
 * Outputs JSON response and exits
 * 
 * @param array $data Data to return as JSON
 */
function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// ============================================================================
// 5. CONFIGURATION
// ============================================================================

// Set timezone (adjust as needed)
date_default_timezone_set('Europe/Brussels');

// Error reporting configuration
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to users
ini_set('log_errors', 1);      // Log errors to file

// Set maximum execution time for complex operations
set_time_limit(300); // 5 minutes

// ============================================================================
// 6. READY TO USE
// ============================================================================

/**
 * At this point, your module has access to:
 * 
 * VARIABLES:
 * - $user                  (array) Current user information
 * - $current_user_code     (string) User's unique code
 * - $current_user_name     (string) User's full name
 * - $current_user_email    (string) User's email
 * - $current_user_level    (string) User's role (admin, recruiter, user)
 * 
 * FUNCTIONS:
 * - getDB()                Get database connection
 * - requireAdmin()         Require admin access
 * - requireLevel($level)   Require minimum user level
 * - logActivity()          Log user actions
 * - safeQuery()            Execute query with error handling
 * - generateCode()         Generate unique codes
 * - sanitize()             Clean user input
 * - isValidEmail()         Validate email format
 * - formatDate()           Format dates for display
 * - timeAgo()              Convert to "time ago" format
 * - redirect()             Redirect to another page
 * - jsonResponse()         Return JSON response
 * 
 * CONSTANTS:
 * - ROOT_PATH              Project root directory path
 * - DB_NAME, DB_HOST, etc. Database configuration (from config.php)
 * - COMPANY_NAME, etc.     Application settings (from config.php)
 * 
 * You can now write your module code below!
 */
?>