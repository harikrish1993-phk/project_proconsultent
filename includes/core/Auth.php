<?php
/**
 * Enhanced Authentication Class with Security Features
 */
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/ActivityLogger.php';

class Auth {
    private static $conn = null;
    
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
    
    /**
     * Login with enhanced security
     */
    public static function login($identifier, $password, $rememberMe = false) {
        try {
            self::initDB();
            Session::start();
            
            // Check if account is locked
            $lockCheck = self::checkAccountLock($identifier);
            if ($lockCheck['locked']) {
                return [
                    'success' => false,
                    'message' => 'Account temporarily locked due to multiple failed attempts. Try again in ' . 
                                ceil($lockCheck['remaining'] / 60) . ' minutes.'
                ];
            }
            
            // Find user
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
                self::recordFailedAttempt($identifier);
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            $user = $result->fetch_assoc();
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                self::recordFailedAttempt($user['user_code']);
                ActivityLogger::log('failed_login', 'user', $user['user_code'], [
                    'reason' => 'invalid_password'
                ]);
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Check if password needs rehashing (if algorithm updated)
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = self::$conn->prepare("UPDATE user SET password = ? WHERE user_code = ?");
                $updateStmt->bind_param('ss', $newHash, $user['user_code']);
                $updateStmt->execute();
            }
            
            // Clear failed attempts
            self::clearFailedAttempts($user['user_code']);
            
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $stmt = self::$conn->prepare(
                "INSERT INTO tokens 
                (user_code, token, expires_at, ip_address, user_agent) 
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), ?, ?)"
            );
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt->bind_param(
                "ssiss",
                $user['user_code'],
                $token,
                TOKEN_EXPIRY,
                $ipAddress,
                $userAgent
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Token creation failed');
            }
            
            // Regenerate session ID (prevent session fixation)
            Session::regenerate();
            
            // Set session variables
            Session::set('payroll_token', $token);
            Session::set('authenticated', true);
            Session::set('user_id', $user['id']);
            Session::set('user_code', $user['user_code']);
            Session::set('user_name', $user['name']);
            Session::set('user_email', $user['email']);
            Session::set('user_level', $user['level']);
            Session::set('login_time', time());
            
            // Set cookie if remember me
            if ($rememberMe) {
                setcookie(
                    'payroll_token',
                    $token,
                    time() + TOKEN_EXPIRY,
                    '/',
                    '',
                    ENVIRONMENT === 'production',
                    true
                );
            }
            
            // Update last login
            $updateLoginStmt = self::$conn->prepare(
                "UPDATE user SET last_login = NOW() WHERE user_code = ?"
            );
            $updateLoginStmt->bind_param('s', $user['user_code']);
            $updateLoginStmt->execute();
            
            // Log activity
            ActivityLogger::log('login', 'user', $user['user_code'], [
                'method' => 'password',
                'remember_me' => $rememberMe
            ]);
            
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
            error_log("Auth login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Check account lock status
     */
    private static function checkAccountLock($identifier) {
        try {
            self::initDB();
            
            $stmt = self::$conn->prepare(
                "SELECT failed_login_attempts, locked_until 
                FROM user 
                WHERE user_code = ? OR email = ? 
                LIMIT 1"
            );
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['locked' => false];
            }
            
            $data = $result->fetch_assoc();
            
            if ($data['locked_until'] && strtotime($data['locked_until']) > time()) {
                return [
                    'locked' => true,
                    'remaining' => strtotime($data['locked_until']) - time()
                ];
            }
            
            // If lock expired, clear it
            if ($data['locked_until'] && strtotime($data['locked_until']) <= time()) {
                self::clearFailedAttempts($identifier);
            }
            
            return ['locked' => false];
            
        } catch (Exception $e) {
            error_log("Check account lock error: " . $e->getMessage());
            return ['locked' => false];
        }
    }
    
    /**
     * Record failed login attempt
     */
    private static function recordFailedAttempt($identifier) {
        try {
            self::initDB();
            
            $stmt = self::$conn->prepare(
                "UPDATE user 
                SET failed_login_attempts = failed_login_attempts + 1 
                WHERE user_code = ? OR email = ?"
            );
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            
            // Check if should lock account
            $stmt = self::$conn->prepare(
                "SELECT failed_login_attempts, user_code 
                FROM user 
                WHERE user_code = ? OR email = ?"
            );
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $data = $result->fetch_assoc();
                
                if ($data['failed_login_attempts'] >= LOGIN_MAX_ATTEMPTS) {
                    // Lock account
                    $lockStmt = self::$conn->prepare(
                        "UPDATE user 
                        SET locked_until = DATE_ADD(NOW(), INTERVAL ? SECOND) 
                        WHERE user_code = ?"
                    );
                    $lockStmt->bind_param('is', LOGIN_LOCKOUT_TIME, $data['user_code']);
                    $lockStmt->execute();
                    
                    ActivityLogger::log('account_locked', 'user', $data['user_code'], [
                        'reason' => 'max_failed_attempts'
                    ]);
                }
            }
            
        } catch (Exception $e) {
            error_log("Record failed attempt error: " . $e->getMessage());
        }
    }
    
    /**
     * Clear failed login attempts
     */
    private static function clearFailedAttempts($identifier) {
        try {
            self::initDB();
            
            $stmt = self::$conn->prepare(
                "UPDATE user 
                SET failed_login_attempts = 0, locked_until = NULL 
                WHERE user_code = ? OR email = ?"
            );
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Clear failed attempts error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public static function check() {
        try {
            Session::start();
            
            $token = Session::get('payroll_token');
            if (!$token) {
                $token = $_COOKIE['payroll_token'] ?? null;
            }
            
            if (!$token) {
                return false;
            }
            
            self::initDB();
            
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
            
            // Check token expiration
            if (strtotime($data['expires_at']) < time()) {
                self::logout();
                return false;
            }
            
            // Refresh session variables
            Session::set('authenticated', true);
            Session::set('user_id', $data['id']);
            Session::set('user_code', $data['user_code']);
            Session::set('user_name', $data['name']);
            Session::set('user_email', $data['email']);
            Session::set('user_level', $data['level']);
            
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
        Session::start();
        if (!self::check()) {
            return null;
        }
        
        return [
            'id' => Session::get('user_id'),
            'user_code' => Session::get('user_code'),
            'name' => Session::get('user_name'),
            'email' => Session::get('user_email'),
            'level' => Session::get('user_level'),
        ];
    }
    
    /**
     * Get current token
     */
    public static function token() {
        Session::start();
        return Session::get('payroll_token', '');
    }
    
    /**
     * Logout and clean up
     */
    public static function logout() {
        try {
            Session::start();
            
            $token = Session::get('payroll_token', '');
            $userCode = Session::get('user_code');
            
            if ($token && self::initDB()) {
                // Delete token from database
                $stmt = self::$conn->prepare("DELETE FROM tokens WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
            }
            
            // Log activity
            if ($userCode) {
                ActivityLogger::log('logout', 'user', $userCode);
            }
            
            // Clear cookie
            if (isset($_COOKIE['payroll_token'])) {
                setcookie('payroll_token', '', time() - 3600, '/', '', ENVIRONMENT === 'production', true);
            }
            
            // Destroy session
            Session::destroy();
            
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
        }
    }
}

// Helper functions
function requireLogin() {
    if (!Auth::check()) {
        Session::flash('error', 'Please login to continue');
        redirect('/login.php');
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