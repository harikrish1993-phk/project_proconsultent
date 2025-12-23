<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';
header('Content-Type: application/json');

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