<?php
/**
 * Authentication Class
 */
class Auth {
    private static $conn = null;
    private static $sessionStarted = false;
    
    private static function initDB() {
        if (self::$conn === null) {
            require_once __DIR__ . '/../config/config.php';
            self::$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (!self::$conn) {
                throw new Exception('Database connection failed: ' . mysqli_connect_error());
            }
            mysqli_set_charset(self::$conn, DB_CHARSET);
        }
        return true;
    }
    
    private static function startSession() {
        if (self::$sessionStarted) return true;
        if (session_status() === PHP_SESSION_NONE) {
            session_name('proconsultancy_session');
            session_start();
            self::$sessionStarted = true;
        }
        return true;
    }
    
    public static function login($identifier, $password, $rememberMe = false) {
        try {
            self::initDB();
            self::startSession();
            
            $stmt = self::$conn->prepare("SELECT * FROM user WHERE user_code = ? OR email = ? LIMIT 1");
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows !== 1) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            $user = $result->fetch_assoc();
            
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Generate token
            $token = bin2hex(random_bytes(32));
            $stmt = self::$conn->prepare("INSERT INTO tokens (user_code, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL " . TOKEN_EXPIRY . " SECOND))");
            $stmt->bind_param("ss", $user['user_code'], $token);
            if (!$stmt->execute()) {
                throw new Exception('Token insertion failed');
            }
            
            $_SESSION['payroll_token'] = $token;
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_code'] = $user['user_code'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_level'] = $user['level'];
            $_SESSION['login_time'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            
            setcookie('payroll_token', $token, time() + TOKEN_EXPIRY, '/', '', false, true);
            
            return ['success' => true, 'message' => 'Login successful'];
        } catch (Exception $e) {
            error_log("Auth login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    public static function check() {
        try {
            self::startSession();
            $token = $_SESSION['payroll_token'] ?? null;
            if (!$token || !isset($_COOKIE['payroll_token']) || $_COOKIE['payroll_token'] !== $token) {
                return false;
            }
            
            self::initDB();
            $stmt = self::$conn->prepare("SELECT u.*, t.expires_at FROM user u JOIN tokens t ON u.user_code = t.user_code WHERE t.token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows !== 1) return false;
            
            $data = $result->fetch_assoc();
            if (strtotime($data['expires_at']) < time()) {
                self::logout();
                return false;
            }
            
            // Set session if not set
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $data['id'];
            $_SESSION['user_code'] = $data['user_code'];
            $_SESSION['user_name'] = $data['name'];
            $_SESSION['user_email'] = $data['email'];
            $_SESSION['user_level'] = $data['level'];
            
            return true;
        } catch (Exception $e) {
            error_log("Auth check error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function user() {
        self::startSession();
        if (!self::check()) return null;
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'user_code' => $_SESSION['user_code'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'level' => $_SESSION['user_level'] ?? null,
        ];
    }
    
    public static function token() {
        self::startSession();
        return $_SESSION['payroll_token'] ?? '';
    }
    
    public static function logout() {
        try {
            self::startSession();
            $token = $_SESSION['payroll_token'] ?? '';
            if ($token && self::initDB()) {
                $stmt = self::$conn->prepare("DELETE FROM tokens WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
            }
            $_SESSION = [];
            if (isset($_COOKIE['payroll_token'])) setcookie('payroll_token', '', time() - 3600, '/');
            session_destroy();
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
        }
    }
}
// Helper functions for quick access
function requireLogin() {
    if (!Auth::check()) {
        header('Location: /index.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    $user = Auth::user();
    if ($user['level'] !== 'admin') {
        header('HTTP/1.0 403 Forbidden');
        die('Access denied. Admin privileges required.');
    }
}

function isAdmin() {
    $user = Auth::user();
    return $user && $user['level'] === 'admin';
}

function getCurrentUser() {
    return Auth::user();
}

// Set constants for backward compatibility
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
    define('CURRENT_USER_ID', $_SESSION['user_id'] ?? null);
    define('CURRENT_USER_CODE', $_SESSION['user_code'] ?? '');
    define('CURRENT_USER_NAME', $_SESSION['user_name'] ?? '');
    define('CURRENT_USER_LEVEL', $_SESSION['user_level'] ?? 'user');
}
?>

