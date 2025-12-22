<?php
require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$query = $_POST['query'] ?? '';

$db = Database::getInstance();
$conn = $db->getConnection();

$search = "%$query%";
$stmt = $conn->prepare("SELECT * FROM candidates WHERE candidate_name LIKE ? OR email_id LIKE ? LIMIT 20");
$stmt->bind_param("ss", $search, $search);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode($results);
?>