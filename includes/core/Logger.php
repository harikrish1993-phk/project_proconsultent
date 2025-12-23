<?php
/**
 * APPLICATION LOGGER
 * File: includes/core/Logger.php
 * 
 * Centralized logging system for application debugging and monitoring
 * 
 * Usage:
 * Logger::error('Database connection failed', ['host' => 'localhost']);
 * Logger::warning('User login attempt failed', ['username' => $username]);
 * Logger::info('New candidate added', ['can_code' => 'CAN/001']);
 * Logger::debug('Processing application', ['job_ref' => 'JOB/001']);
 */

class Logger {
    private static $logDir = null;
    private static $enabled = true;
    private static $maxFileSize = 10485760; // 10MB
    private static $maxFiles = 30; // Keep 30 days of logs
    
    /**
     * Initialize logger
     */
    public static function init($logDir = null) {
        if ($logDir) {
            self::$logDir = $logDir;
        } else {
            self::$logDir = defined('ROOT_PATH') ? ROOT_PATH . '/logs' : __DIR__ . '/../../logs';
        }
        
        // Create log directory if it doesn't exist
        if (!file_exists(self::$logDir)) {
            @mkdir(self::$logDir, 0755, true);
        }
        
        // Clean old log files
        self::cleanOldLogs();
    }
    
    /**
     * Log error message
     */
    public static function error($message, $context = []) {
        self::write('ERROR', $message, $context);
    }
    
    /**
     * Log warning message
     */
    public static function warning($message, $context = []) {
        self::write('WARNING', $message, $context);
    }
    
    /**
     * Log info message
     */
    public static function info($message, $context = []) {
        self::write('INFO', $message, $context);
    }
    
    /**
     * Log debug message
     */
    public static function debug($message, $context = []) {
        self::write('DEBUG', $message, $context);
    }
    
    /**
     * Log database query
     */
    public static function query($sql, $params = [], $error = null) {
        $context = [
            'sql' => $sql,
            'params' => $params
        ];
        
        if ($error) {
            $context['error'] = $error;
            self::error('Database Query Failed', $context);
        } else {
            self::debug('Database Query', $context);
        }
    }
    
    /**
     * Log user activity
     */
    public static function activity($action, $module, $details = []) {
        $userCode = 'guest';
        if (class_exists('Auth') && method_exists('Auth', 'user')) {
            $user = Auth::user();
            if ($user) {
                $userCode = $user['user_code'] ?? 'unknown';
            }
        }
        
        self::info("User Activity: $action in $module", array_merge([
            'user' => $userCode,
            'action' => $action,
            'module' => $module
        ], $details));
    }
    
    /**
     * Write log entry
     */
    private static function write($level, $message, $context = []) {
        if (!self::$enabled) return;
        
        if (!self::$logDir) {
            self::init();
        }
        
        // Get log file path
        $date = date('Y-m-d');
        $logFile = self::$logDir . "/app-{$date}.log";
        
        // Check file size and rotate if needed
        if (file_exists($logFile) && filesize($logFile) > self::$maxFileSize) {
            self::rotateLogFile($logFile);
        }
        
        // Build log entry
        $time = date('H:i:s');
        $logEntry = "[{$time}] [{$level}] {$message}";
        
        // Add context if provided
        if (!empty($context)) {
            $logEntry .= " | " . json_encode($context, JSON_UNESCAPED_SLASHES);
        }
        
        // Add user info if available
        if (class_exists('Auth') && method_exists('Auth', 'user')) {
            $user = Auth::user();
            if ($user) {
                $logEntry .= " | User: " . ($user['user_code'] ?? 'unknown');
            }
        }
        
        // Add IP address
        $logEntry .= " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        // Add request URI
        if (isset($_SERVER['REQUEST_URI'])) {
            $logEntry .= " | URI: " . $_SERVER['REQUEST_URI'];
        }
        
        $logEntry .= "\n";
        
        // Write to file
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also write errors to separate error log
        if ($level === 'ERROR') {
            $errorLog = self::$logDir . "/error-{$date}.log";
            @file_put_contents($errorLog, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Rotate log file when it gets too large
     */
    private static function rotateLogFile($logFile) {
        $timestamp = date('His');
        $rotatedFile = $logFile . ".{$timestamp}";
        @rename($logFile, $rotatedFile);
    }
    
    /**
     * Clean old log files
     */
    private static function cleanOldLogs() {
        if (!self::$logDir || !file_exists(self::$logDir)) return;
        
        $files = glob(self::$logDir . '/app-*.log*');
        if (!$files) return;
        
        // Sort files by modification time
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Delete old files
        $filesToDelete = array_slice($files, self::$maxFiles);
        foreach ($filesToDelete as $file) {
            @unlink($file);
        }
    }
    
    /**
     * Get log file path for today
     */
    public static function getLogFile() {
        if (!self::$logDir) {
            self::init();
        }
        return self::$logDir . '/app-' . date('Y-m-d') . '.log';
    }
    
    /**
     * Get all log files
     */
    public static function getAllLogFiles() {
        if (!self::$logDir) {
            self::init();
        }
        
        $files = glob(self::$logDir . '/app-*.log*');
        if (!$files) return [];
        
        // Sort by modification time (newest first)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        return $files;
    }
    
    /**
     * Read log file
     */
    public static function readLog($file = null, $lines = 100) {
        if (!$file) {
            $file = self::getLogFile();
        }
        
        if (!file_exists($file)) {
            return [];
        }
        
        // Read last N lines
        $handle = fopen($file, 'r');
        if (!$handle) return [];
        
        $buffer = [];
        fseek($handle, -1, SEEK_END);
        
        $lineCount = 0;
        $currentLine = '';
        
        while (ftell($handle) > 0 && $lineCount < $lines) {
            $char = fgetc($handle);
            
            if ($char === "\n") {
                if ($currentLine !== '') {
                    array_unshift($buffer, $currentLine);
                    $currentLine = '';
                    $lineCount++;
                }
            } else {
                $currentLine = $char . $currentLine;
            }
            
            fseek($handle, -2, SEEK_CUR);
        }
        
        if ($currentLine !== '') {
            array_unshift($buffer, $currentLine);
        }
        
        fclose($handle);
        
        return $buffer;
    }
    
    /**
     * Enable logging
     */
    public static function enable() {
        self::$enabled = true;
    }
    
    /**
     * Disable logging
     */
    public static function disable() {
        self::$enabled = false;
    }
}
?>