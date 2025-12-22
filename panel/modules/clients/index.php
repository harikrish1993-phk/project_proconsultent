<?php
/**
 * Clients Module - Router
 * File: panel/modules/clients/index.php
 */

require_once __DIR__ . '/../../includes/config/config.php';
require_once __DIR__ . '/../../includes/core/Auth.php';
require_once __DIR__ . '/../../includes/core/Database.php';

// Check authentication
if (!Auth::check()) {
    header('Location: ../../login.php');
    exit();
}

$user = Auth::user();
$db = Database::getInstance();
$conn = $db->getConnection();

// Get action and ID
$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// Include header
include __DIR__ . '/../../includes/header.php';

try {
    switch ($action) {
        case 'list':
            include 'list.php';
            break;
        
        case 'create':
            include 'create.php';
            break;
        
        case 'view':
            if (!$id) throw new Exception('Invalid client ID');
            include 'view.php';
            break;
        
        case 'edit':
            if (!$id) throw new Exception('Invalid client ID');
            include 'edit.php';
            break;
        
        case 'dashboard':
            include 'dashboard.php';
            break;
        
        default:
            include 'list.php';
            break;
    }
} catch (Exception $e) {
    echo '<div class="container-xxl flex-grow-1 container-p-y">';
    echo '<div class="alert alert-danger"><i class="bx bx-error-circle me-2"></i>' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<a href="?action=list" class="btn btn-primary">Back to Clients</a>';
    echo '</div>';
}

// Include footer
include __DIR__ . '/../../includes/footer.php';
?>