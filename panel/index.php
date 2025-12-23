<?php
/**
 * PANEL INDEX (panel/index.php)
 * Main entry point - redirects to route.php
 */

require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/core/Auth.php';

// Check authentication
if (!Auth::check()) {
    header('Location: ../login.php');
    exit();
}

// Redirect to router
header('Location: route.php');
exit();
?>