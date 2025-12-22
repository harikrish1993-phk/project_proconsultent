<?php
/**
 * Logout Handler
 * Destroys session and redirects to login
 */

// Load authentication class
require_once __DIR__ . '/../includes/core/Auth.php';

// Logout user
Auth::logout();

// Redirect to login page
header('Location: /login.php?logged_out=1');
exit;
?>
