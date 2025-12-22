<?php
// modules/candidates/index.php
require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';
require_once __DIR__ . '/../../../includes/core/Settings.php';
require_once __DIR__ . '/../../../includes/header.php';

if (!Auth::check()) {
    header('Location: ../../../login.php');
    exit();
}

$user = Auth::user();
$action = $_GET['action'] ?? 'dashboard';
$id = $_GET['id'] ?? null;

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    switch ($action) {
        case 'dashboard':
            include 'dashboard.php';
            break;
        case 'list':
            include 'list.php';
            break;
        case 'view':
            if (!$id) throw new Exception('Invalid ID');
            include 'view.php';
            break;
        case 'full-view':
            if (!$id) throw new Exception('Invalid ID');
            include 'full-view.php';
            break;
        case 'create':
            include 'create.php';
            break;
        case 'edit':
            if (!$id) throw new Exception('Invalid ID');
            include 'edit.php';
            break;
        case 'pipeline':
            include 'pipeline.php';
            break;
        case 'assigned':
            include 'assigned.php';
            break;
        case 'daily-report':
            include 'daily-report.php';
            break;
        // case 'cv-collection':
        //     include 'cv-collection.php';
        //     break;
        case 'interviews':
            include 'interviews.php'; 
            break;
        default:
            include 'dashboard.php';
            break;
    }
} catch (Exception $e) {
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">' . $e->getMessage() . '</div></div>';
}

include __DIR__ . '/../../../includes/footer.php';
?>