<?php
/**
 * Session Management Class
 * Handles secure session operations
 */
class Session {
    private static $started = false;
    
    /**
     * Start session with security settings
     */
    public static function start() {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        
        // Configure session settings
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', ENVIRONMENT === 'production' ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_name('proconsultancy_session');
        session_start();
        self::$started = true;
        
        // Session hijacking protection
        if (!self::validate()) {
            self::destroy();
            return false;
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created_at'])) {
            $_SESSION['created_at'] = time();
        } elseif (time() - $_SESSION['created_at'] > 300) {
            session_regenerate_id(true);
            $_SESSION['created_at'] = time();
        }
        
        return true;
    }
    
    /**
     * Validate session against hijacking
     */
    private static function validate() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        
        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $userAgent;
            $_SESSION['ip_address'] = $ipAddress;
            return true;
        }
        
        // Check for session hijacking
        if ($_SESSION['user_agent'] !== $userAgent) {
            error_log("Session hijack attempt detected: User agent mismatch");
            return false;
        }
        
        // Optional: IP check (can cause issues with mobile networks)
        // if ($_SESSION['ip_address'] !== $ipAddress) {
        //     error_log("Session hijack attempt: IP mismatch");
        //     return false;
        // }
        
        return true;
    }
    
    /**
     * Set session value
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session value
     */
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session key exists
     */
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session key
     */
    public static function remove($key) {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Flash message (one-time session data)
     */
    public static function flash($key, $value = null) {
        self::start();
        
        if ($value === null) {
            // Get and remove flash message
            $message = $_SESSION['flash'][$key] ?? null;
            if (isset($_SESSION['flash'][$key])) {
                unset($_SESSION['flash'][$key]);
            }
            return $message;
        } else {
            // Set flash message
            $_SESSION['flash'][$key] = $value;
        }
    }
    
    /**
     * Regenerate session ID (anti-session fixation)
     */
    public static function regenerate() {
        self::start();
        session_regenerate_id(true);
        $_SESSION['created_at'] = time();
    }
    
    /**
     * Destroy session completely
     */
    public static function destroy() {
        self::start();
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
        self::$started = false;
    }
    
    /**
     * Get session ID
     */
    public static function id() {
        self::start();
        return session_id();
    }
}
?>