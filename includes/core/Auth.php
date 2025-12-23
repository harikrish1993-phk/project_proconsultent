<?php
/**
 * STANDALONE Authentication Class
 * Works with BOTH plain text and bcrypt passwords
 * No external dependencies (Session.php, ActivityLogger.php)
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
    
    /**
     * Check if password is bcrypt hash
     */
    private static function isBcryptHash($password) {
        return (strlen($password) === 60 && substr($password, 0, 4) === '$2y$');
    }
    
    /**
     * Login with support for both plain text and bcrypt
     */
    public static function login($identifier, $password, $rememberMe = false) {
        try {
            self::initDB();
            self::startSession();
            
            error_log("=== LOGIN ATTEMPT ===");
            error_log("Identifier: $identifier");
            error_log("Password length: " . strlen($password));
            
            // Find user by user_code OR email
            $stmt = self::$conn->prepare(
                "SELECT * FROM user 
                WHERE (user_code = ? OR email = ?) 
                AND is_active = 1 
                LIMIT 1"
            );
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows !== 1) {
                error_log("LOGIN FAILED: User not found - $identifier");
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            $user = $result->fetch_assoc();
            error_log("User found: " . $user['user_code']);
            error_log("DB password: " . substr($user['password'], 0, 20) . "... (length: " . strlen($user['password']) . ")");
            
            // Check password - support BOTH plain text and bcrypt
            $passwordValid = false;
            $needsUpgrade = false;
            
            if (self::isBcryptHash($user['password'])) {
                // Password is bcrypt hash
                error_log("Password type: BCRYPT");
                $passwordValid = password_verify($password, $user['password']);
                error_log("Bcrypt verify result: " . ($passwordValid ? 'TRUE' : 'FALSE'));
            } else {
                // Password is plain text
                error_log("Password type: PLAIN TEXT");
                error_log("Comparing: '$password' === '{$user['password']}'");
                $passwordValid = ($password === $user['password']);
                error_log("Plain text compare result: " . ($passwordValid ? 'TRUE' : 'FALSE'));
                $needsUpgrade = true;
            }
            
            if (!$passwordValid) {
                error_log("LOGIN FAILED: Invalid password for user: {$user['user_code']}");
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            error_log("Password verified successfully!");
            
            // SECURITY UPGRADE: If password was plain text, upgrade to bcrypt
            if ($needsUpgrade) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = self::$conn->prepare("UPDATE user SET password = ? WHERE user_code = ?");
                $updateStmt->bind_param('ss', $newHash, $user['user_code']);
                if ($updateStmt->execute()) {
                    error_log("Password upgraded to bcrypt for: {$user['user_code']}");
                } else {
                    error_log("Failed to upgrade password: " . $updateStmt->error);
                }
            }
            
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            error_log("Generated token: " . substr($token, 0, 20) . "...");
            
            // Store token in database
            $tokenExpiry = defined('TOKEN_EXPIRY') ? TOKEN_EXPIRY : 86400; // 24 hours default
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            $stmt = self::$conn->prepare(
                "INSERT INTO tokens (user_code, token, expires_at, ip_address, user_agent) 
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), ?, ?)"
            );
            $stmt->bind_param('ssiss', $user['user_code'], $token, $tokenExpiry, $ipAddress, $userAgent);
            
            if (!$stmt->execute()) {
                error_log("Token insertion failed: " . $stmt->error);
                throw new Exception('Token creation failed: ' . $stmt->error);
            }
            
            error_log("Token inserted into database");
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            error_log("Session regenerated");
            
            // Set session variables
            $_SESSION['payroll_token'] = $token;
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_code'] = $user['user_code'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_level'] = $user['level'];
            $_SESSION['login_time'] = time();
            $_SESSION['ip_address'] = $ipAddress;
            
            error_log("Session variables set");
            
            // Set cookie for remember me
            if ($rememberMe) {
                $cookieSet = setcookie('payroll_token', $token, time() + $tokenExpiry, '/', '', false, true);
                error_log("Remember me cookie set: " . ($cookieSet ? 'YES' : 'NO'));
            }
            
            // Update last login
            $updateStmt = self::$conn->prepare("UPDATE user SET last_login = NOW() WHERE user_code = ?");
            $updateStmt->bind_param('s', $user['user_code']);
            $updateStmt->execute();
            
            error_log("=== LOGIN SUCCESSFUL ===");
            error_log("User: {$user['user_code']} ({$user['name']})");
            error_log("Level: {$user['level']}");
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'user_code' => $user['user_code'],
                    'name' => $user['name'],
                    'level' => $user['level']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("=== LOGIN EXCEPTION ===");
            error_log("Error: " . $e->getMessage());
            error_log("File: " . $e->getFile());
            error_log("Line: " . $e->getLine());
            error_log("Trace: " . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public static function check() {
        try {
            self::startSession();
            
            // Get token from session or cookie
            $token = $_SESSION['payroll_token'] ?? null;
            if (!$token && isset($_COOKIE['payroll_token'])) {
                $token = $_COOKIE['payroll_token'];
            }
            
            if (!$token) {
                return false;
            }
            
            self::initDB();
            
            // Verify token in database
            $stmt = self::$conn->prepare(
                "SELECT u.*, t.expires_at 
                FROM user u 
                JOIN tokens t ON u.user_code = t.user_code 
                WHERE t.token = ? AND u.is_active = 1"
            );
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows !== 1) {
                return false;
            }
            
            $data = $result->fetch_assoc();
            
            // Check if token expired
            if (strtotime($data['expires_at']) < time()) {
                self::logout();
                return false;
            }
            
            // Refresh session variables
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
    
    /**
     * Get current user data
     */
    public static function user() {
        self::startSession();
        if (!self::check()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'user_code' => $_SESSION['user_code'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'level' => $_SESSION['user_level'] ?? null,
        ];
    }
    
    /**
     * Get current token
     */
    public static function token() {
        self::startSession();
        return $_SESSION['payroll_token'] ?? '';
    }
    
    /**
     * Logout and clean up
     */
    public static function logout() {
        try {
            self::startSession();
            
            $token = $_SESSION['payroll_token'] ?? '';
            
            if ($token && self::initDB()) {
                // Delete token from database
                $stmt = self::$conn->prepare("DELETE FROM tokens WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
            }
            
            // Clear session
            $_SESSION = [];
            
            // Clear cookie
            if (isset($_COOKIE['payroll_token'])) {
                setcookie('payroll_token', '', time() - 3600, '/', '', false, true);
            }
            
            // Destroy session
            session_destroy();
            self::$sessionStarted = false;
            
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
        }
    }
    
    /**
     * Change user password
     */
    public static function changePassword($userCode, $oldPassword, $newPassword) {
        try {
            self::initDB();
            
            // Get current user
            $stmt = self::$conn->prepare("SELECT password FROM user WHERE user_code = ?");
            $stmt->bind_param('s', $userCode);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows !== 1) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            $user = $result->fetch_assoc();
            
            // Verify old password (support both plain text and bcrypt)
            $passwordValid = false;
            if (self::isBcryptHash($user['password'])) {
                $passwordValid = password_verify($oldPassword, $user['password']);
            } else {
                $passwordValid = ($oldPassword === $user['password']);
            }
            
            if (!$passwordValid) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Hash new password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = self::$conn->prepare("UPDATE user SET password = ? WHERE user_code = ?");
            $stmt->bind_param('ss', $newHash, $userCode);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Password changed successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update password'];
            }
            
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }
    
    /**
     * Reset password (for forgot password flow)
     */
    public static function resetPassword($email, $newPassword) {
        try {
            self::initDB();
            
            // Find user by email
            $stmt = self::$conn->prepare("SELECT user_code FROM user WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows !== 1) {
                return ['success' => false, 'message' => 'Email not found'];
            }
            
            $user = $result->fetch_assoc();
            
            // Hash new password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = self::$conn->prepare("UPDATE user SET password = ? WHERE user_code = ?");
            $stmt->bind_param('ss', $newHash, $user['user_code']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Password reset successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to reset password'];
            }
            
        } catch (Exception $e) {
            error_log("Reset password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }
}

// Helper functions
function requireLogin() {
    if (!Auth::check()) {
        header('Location: /login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    $user = Auth::user();
    if ($user['level'] !== 'admin') {
        http_response_code(403);
        die('Access denied. Admin privileges required.');
    }
}

function requireLevel($requiredLevel) {
    requireLogin();
    $user = Auth::user();
    $levels = ['user', 'recruiter', 'manager', 'admin'];
    
    $userLevelIndex = array_search($user['level'], $levels);
    $requiredLevelIndex = array_search($requiredLevel, $levels);
    
    if ($userLevelIndex < $requiredLevelIndex) {
        http_response_code(403);
        die('Access denied. Insufficient privileges.');
    }
}

function isAdmin() {
    $user = Auth::user();
    return $user && $user['level'] === 'admin';
}

function getCurrentUser() {
    return Auth::user();
}
?>