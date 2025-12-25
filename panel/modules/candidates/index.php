<?php
require_once __DIR__ . '/../_common.php';
$pageTitle = 'Candidates';
$breadcrumbs = [
    'Candidates' => '#'
];


$action = $_GET['action'] ?? 'list';

// Set page title
$pageTitle = 'Candidates';

// Include header
require_once ROOT_PATH . '/panel/includes/header.php';

// Route to appropriate page
switch ($action) {
    case 'create':
    case 'add':
        if (file_exists(__DIR__ . '/create.php')) {
            include __DIR__ . '/create.php';
        } else {
            echo renderAlert('Create page not yet implemented', 'warning');
        }
        break;
        
    case 'list':
    default:
        if (file_exists(__DIR__ . '/list.php')) {
            include __DIR__ . '/list.php';
        } else {
            echo renderAlert('List page not yet implemented', 'warning');
        }
        break;
}

// Include footer
require_once ROOT_PATH . '/panel/includes/footer.php';
?>