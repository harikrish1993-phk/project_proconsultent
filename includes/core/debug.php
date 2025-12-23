<?php
/**
 * DEBUG CONFIGURATION
 * File: includes/config/debug.php
 * 
 * Controls application debug mode and error display
 * 
 * Set DEBUG_MODE to true during development
 * Set DEBUG_MODE to false in production
 */

// Debug mode - SET TO FALSE IN PRODUCTION
define('DEBUG_MODE', false); // Change to true for development

// Configure error reporting based on debug mode
if (DEBUG_MODE) {
    // Development mode - show all errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/php-errors.log');
    
    // Show detailed errors
    define('SHOW_ERRORS', true);
    define('LOG_QUERIES', true);
    
} else {
    // Production mode - hide errors from users but log them
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/php-errors.log');
    
    // Don't show detailed errors
    define('SHOW_ERRORS', false);
    define('LOG_QUERIES', false);
}

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Log error
    $error = sprintf(
        "PHP Error [%d]: %s in %s on line %d",
        $errno,
        $errstr,
        $errfile,
        $errline
    );
    
    error_log($error);
    
    // Log to application logger if available
    if (class_exists('Logger')) {
        Logger::error('PHP Error', [
            'errno' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]);
    }
    
    // Display error in debug mode
    if (DEBUG_MODE) {
        echo '<div style="background:#fee;border:2px solid #f00;padding:15px;margin:10px;border-radius:8px;font-family:monospace;">';
        echo '<strong style="color:#c00;">Error [' . $errno . ']:</strong> ' . htmlspecialchars($errstr) . '<br>';
        echo '<strong>File:</strong> ' . htmlspecialchars($errfile) . '<br>';
        echo '<strong>Line:</strong> ' . $errline . '<br>';
        echo '</div>';
    }
    
    // Don't execute PHP internal error handler
    return true;
});

// Custom exception handler
set_exception_handler(function($exception) {
    // Log exception
    $message = sprintf(
        "Uncaught Exception: %s in %s on line %d",
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    );
    
    error_log($message);
    error_log("Stack trace: " . $exception->getTraceAsString());
    
    // Log to application logger if available
    if (class_exists('Logger')) {
        Logger::error('Uncaught Exception', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
    
    // Display exception in debug mode
    if (DEBUG_MODE) {
        echo '<div style="background:#fee;border:2px solid #f00;padding:20px;margin:20px;border-radius:8px;">';
        echo '<h2 style="color:#c00;margin-top:0;">Exception Occurred</h2>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($exception->getFile()) . '</p>';
        echo '<p><strong>Line:</strong> ' . $exception->getLine() . '</p>';
        echo '<details style="margin-top:15px;">';
        echo '<summary style="cursor:pointer;font-weight:bold;">Stack Trace</summary>';
        echo '<pre style="background:#f5f5f5;padding:10px;overflow-x:auto;margin-top:10px;">' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
        echo '</details>';
        echo '</div>';
    } else {
        // Show user-friendly error page
        http_response_code(500);
        if (file_exists(ROOT_PATH . '/panel/includes/error-page.php')) {
            include ROOT_PATH . '/panel/includes/error-page.php';
        } else {
            echo '<div style="padding:50px;text-align:center;font-family:Arial;">';
            echo '<h1>Something went wrong</h1>';
            echo '<p>We\'re sorry, but something unexpected happened. Our team has been notified.</p>';
            echo '<a href="' . (defined('ROOT_PATH') ? ROOT_PATH : '/') . '/panel/admin.php">Return to Dashboard</a>';
            echo '</div>';
        }
    }
    
    exit(1);
});

// Shutdown handler for fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $message = sprintf(
            "Fatal Error: %s in %s on line %d",
            $error['message'],
            $error['file'],
            $error['line']
        );
        
        error_log($message);
        
        // Log to application logger if available
        if (class_exists('Logger')) {
            Logger::error('Fatal Error', $error);
        }
        
        if (DEBUG_MODE) {
            echo '<div style="background:#fee;border:2px solid #f00;padding:20px;margin:20px;border-radius:8px;">';
            echo '<h2 style="color:#c00;margin-top:0;">Fatal Error</h2>';
            echo '<p><strong>Message:</strong> ' . htmlspecialchars($error['message']) . '</p>';
            echo '<p><strong>File:</strong> ' . htmlspecialchars($error['file']) . '</p>';
            echo '<p><strong>Line:</strong> ' . $error['line'] . '</p>';
            echo '</div>';
        }
    }
});

// Helper function to check if in debug mode
function isDebugMode() {
    return defined('DEBUG_MODE') && DEBUG_MODE === true;
}

// Helper function to dump variables in debug mode
function dd($var, $label = '') {
    if (!DEBUG_MODE) return;
    
    echo '<div style="background:#f0f0f0;border:2px solid #666;padding:15px;margin:10px;border-radius:8px;">';
    if ($label) {
        echo '<strong>' . htmlspecialchars($label) . ':</strong><br>';
    }
    echo '<pre>' . htmlspecialchars(print_r($var, true)) . '</pre>';
    echo '</div>';
}
?>