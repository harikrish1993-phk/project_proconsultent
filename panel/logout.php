<?php
/**
 * LOGOUT HANDLER (panel/logout.php)
 * 
 * FIXED VERSION - Simplified, no heavy bootstrap
 * Terminates user session and redirects to login
 * 
 * Changes from original:
 * - Removed _common.php dependency (no need for full bootstrap to logout)
 * - Direct Auth::logout() call
 * - Minimal resource usage
 */

// Load only essential components - NO full bootstrap needed
require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/core/Auth.php';

// Optional: Log logout activity before destroying session
if (class_exists('Logger')) {
    require_once __DIR__ . '/../includes/core/Logger.php';
    Logger::init();
    
    $user = Auth::user();
    if ($user) {
        Logger::info('User logout', [
            'user_code' => $user['user_code'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
}

// Perform logout - this handles session cleanup, token deletion, cookie clearing
Auth::logout();

// Redirect to login page
header('Location: ../login.php');
exit();
?>