<?php
require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

$cv_id = intval($_POST['cv_id'] ?? 0);
$note = $_POST['note'] ?? '';

if ($cv_id && $note) {
    $created_by = Auth::user()['user_code'];
    
    $stmt = $conn->prepare("INSERT INTO cv_notes (cv_id, note, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $cv_id, $note, $created_by);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
}
?>