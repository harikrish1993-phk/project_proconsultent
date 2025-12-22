<?php
require_once __DIR__ . '/../core/Auth.php';

// Check if user is authenticated
if (!Auth::check()) {
    if (!Auth::checkRememberMe()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// User is authenticated
$current_user = Auth::user();

define('CURRENT_USER_ID', $current_user['id']);
define('CURRENT_USER_CODE', $current_user['user_code']);
define('CURRENT_USER_NAME', $current_user['name']);
define('CURRENT_USER_EMAIL', $current_user['email']);
define('CURRENT_USER_LEVEL', $current_user['level']);

function isAdmin() {
    return CURRENT_USER_LEVEL === 'admin';
}

function isUser() {
    return CURRENT_USER_LEVEL === 'user';
}
?>