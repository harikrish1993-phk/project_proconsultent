<?php
/**
 * Configuration - Loads from .env file
 */

if (!defined('CONFIG_LOADED')) {
    define('CONFIG_LOADED', true);
}

// Load .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        die('ERROR: .env file not found at: ' . $path);
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

// Load from root directory
$envPath = __DIR__ . '/../../.env';
loadEnv($envPath);

// Helper function
function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Environment
define('ENVIRONMENT', env('APP_ENV', 'development'));
define('DEBUG_MODE', filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN));

// Error Reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Database
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER'));
define('COMPANY_TAGLINE', env('COMPANY_TAGLINE', 'Your Recruitment Partner'));
define('DB_PASS', env('DB_PASS'));
define('DB_NAME', env('DB_NAME', 'proconsultancy_db'));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Validate database credentials
if (!DB_USER || !DB_PASS) {
    if (DEBUG_MODE) {
        die('ERROR: Database credentials not set in .env file');
    } else {
        error_log('CRITICAL: Database credentials missing');
        die('Configuration error. Contact administrator.');
    }
}

// Company Info
define('COMPANY_NAME', env('COMPANY_NAME', 'ProConsultancy'));
define('COMPANY_EMAIL', env('COMPANY_EMAIL', 'info@proconsultancy.be'));
define('COMPANY_PHONE', env('COMPANY_PHONE', '+32 (0)9 XXX XX XX'));

// Application
define('APP_NAME', COMPANY_NAME . ' Recruitment');
define('APP_VERSION', '1.0.0');
define('BASE_URL', env('APP_URL', ''));

// Security
define('SESSION_TIMEOUT', (int)env('SESSION_TIMEOUT', 3600));
define('PASSWORD_MIN_LENGTH', (int)env('PASSWORD_MIN_LENGTH', 8));
define('TOKEN_EXPIRY', 86400 * 30); // 30 days

// Email
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int)env('SMTP_PORT', 587));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', COMPANY_EMAIL));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', COMPANY_NAME));

// Paths
define('UPLOAD_PATH', __DIR__ . '/../../uploads/');
define('ROOT_PATH', __DIR__ . '/../../');

// Roles
define('USER_ROLE_ADMIN', 'admin');
define('USER_ROLE_RECRUITER', 'recruiter');
define('USER_ROLE_USER', 'user');

// Session Configuration
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', ENVIRONMENT === 'production' ? 1 : 0);

// Helper Functions
// function sanitize($input) {
//     if (is_array($input)) {
//         return array_map('sanitize', $input);
//     }
//     return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
// }

// function redirect($url, $statusCode = 302) {
//     header("Location: $url", true, $statusCode);
//     exit();
// }

function dbConnect() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$conn) {
            error_log('Database connection failed: ' . mysqli_connect_error());
            if (DEBUG_MODE) {
                die('Database connection failed: ' . mysqli_connect_error());
            } else {
                die('Database error. Please contact administrator.');
            }
        }
        
        mysqli_set_charset($conn, DB_CHARSET);
    }
    
    return $conn;
}

function showError($message, $details = '') {
    if (DEBUG_MODE) {
        echo "<div style='background:#f8d7da;color:#721c24;padding:15px;margin:10px;border:1px solid #f5c6cb;border-radius:4px;'>";
        echo "<strong>Error:</strong> " . htmlspecialchars($message);
        if ($details) {
            echo "<br><small>" . htmlspecialchars($details) . "</small>";
        }
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da;color:#721c24;padding:15px;margin:10px;border-radius:4px;'>";
        echo "An error occurred. Please contact support.";
        echo "</div>";
    }
}

// Test database connection on config load
$testConn = dbConnect();
if (!$testConn) {
    die('Failed to establish database connection. Check your .env settings.');
}

?>