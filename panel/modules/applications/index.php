<?php
/**
 * Applications Module Router
 * Central routing for all application-related pages
 */

require_once '../../includes/core/Auth.php';
require_once '../../includes/config/config.php';
requireLogin();

$page = $_GET['page'] ?? 'dashboard';

$allowedPages = [
    'dashboard', 'list', 'view', 'screening', 'pending_approval',
    'approved', 'submitted', 'interviewing', 'offered', 'placed',
    'rejected', 'pipeline', 'bulk_actions'
];

if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}

require $page . '.php';
?>