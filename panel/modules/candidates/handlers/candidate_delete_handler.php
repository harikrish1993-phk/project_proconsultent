<?php
require_once __DIR__ . '/../_common.php';
try {

$conn = Database::getInstance()->getConnection();
$can_code = $_POST['can_code'];

$stmt = $conn->prepare("
    UPDATE candidates SET is_archived = 1 WHERE can_code = ?
");
$stmt->bind_param("s", $can_code);
$stmt->execute();

echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>