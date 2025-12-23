<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';

$user = Auth::user();
$db = Database::getInstance();
$conn = $db->getConnection();

$action = $_GET['action'] ?? 'dashboard';
$id = intval($_GET['id'] ?? 0);

try {
    switch ($action) {
        case 'dashboard':
            include 'dashboard.php';
            break;
        case 'list':
            include 'list.php';
            break;
        case 'create':
            include 'create.php';
            break;
        case 'view':
            if ($id) include 'view.php';
            else throw new Exception('Invalid ID');
            break;
        case 'edit':
            if ($id) include 'edit.php';
            else throw new Exception('Invalid ID');
            break;
        case 'approve':
            include 'approve.php';
            break;
        case 'status':
            include 'status.php';
            break;
        case 'cv-collection':
            include 'cv/cv-collection.php';
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