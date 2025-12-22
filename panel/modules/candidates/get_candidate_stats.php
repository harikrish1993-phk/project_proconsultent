<?php
require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

$stats = [];

$stmt = $conn->query("SELECT COUNT(*) as total FROM candidates");
$stats['total'] = $stmt->fetch_assoc()['total'];

// By status, lead, etc.

echo json_encode($stats);
?>