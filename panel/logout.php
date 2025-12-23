<?php
/**
 * LOGOUT HANDLER (panel/logout.php)
 * Terminates user session and redirects to login
 */

// Load common bootstrap
require_once __DIR__ . '/modules/_common.php';

// Perform logout
Auth::logout();

// Redirect to login page
header('Location: ../login.php');
exit();
?>