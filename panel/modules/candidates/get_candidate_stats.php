<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';
// Page configuration
$pageTitle = 'Candidates';
$breadcrumbs = [
    'Candidates' => '#'
];
// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// Display breadcrumb
echo renderBreadcrumb($breadcrumbs);

header('Content-Type: application/json');


$db = Database::getInstance();
$conn = $db->getConnection();

$stats = [];

$stmt = $conn->query("SELECT COUNT(*) as total FROM candidates");
$stats['total'] = $stmt->fetch_assoc()['total'];

// By status, lead, etc.

echo json_encode($stats);
?>