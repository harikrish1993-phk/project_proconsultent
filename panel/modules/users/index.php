<?php
/**
 * Users Module - Router
 * File: panel/modules/users/index.php
 * Admin only - manages system users
 */

require_once __DIR__ . '/../../includes/config/config.php';
require_once __DIR__ . '/../../includes/core/Auth.php';
require_once __DIR__ . '/../../includes/core/Database.php';

// Check authentication and admin access
if (!Auth::check()) {
    header('Location: ../../login.php');
    exit();
}

if (Auth::user()['level'] !== 'admin') {
    header('Location: ../../dashboard.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();
$token = $_GET['ss_id'] ?? '';

// Get action and ID
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Define Auth constant for included files
define('Auth', true);

// Include header
require_once __DIR__ . '/../../includes/header.php';

try {
    switch ($action) {
        case 'list':
            include 'list.php';
            break;
            
        case 'create':
            include 'create.php';
            break;
            
        case 'view':
            if ($id) {
                include 'view.php';
            } else {
                throw new Exception('User ID is required');
            }
            break;
            
        case 'edit':
            if ($id) {
                include 'edit.php';
            } else {
                throw new Exception('User ID is required');
            }
            break;
            
        default:
            include 'list.php';
            break;
    }
} catch (Exception $e) {
    echo '<div class="container-xxl flex-grow-1 container-p-y">';
    echo '<div class="alert alert-danger"><i class="bx bx-error me-2"></i>' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<a href="?action=list&ss_id=' . $token . '" class="btn btn-secondary">Back to Users</a>';
    echo '</div>';
}

// Include footer
require_once __DIR__ . '/../../includes/footer.php';
?>