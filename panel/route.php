<?php
/**
 * MAIN DASHBOARD ROUTER (panel/route.php)
 * Central routing point that directs users to appropriate dashboard
 */

// Load core files
require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/core/Auth.php';

// Check authentication
if (!Auth::check()) {
    header('Location: ../login.php');
    exit();
}

// Get user info
$user = Auth::user();

if (!$user) {
    // If Auth::check() passed but user() returns null, something is wrong
    // Clear session and redirect to login
    session_destroy();
    header('Location: ../login.php');
    exit();
}

$user_code = $user['user_code'];
$user_level = $user['level'];
$token = Auth::token();

// Route based on user level
if ($user_level === 'admin') {
    // Redirect to admin dashboard
    header('Location: admin.php');
    exit();
} else {
    // Redirect to recruiter dashboard
    header('Location: recruiter.php');
    exit();
}
?>