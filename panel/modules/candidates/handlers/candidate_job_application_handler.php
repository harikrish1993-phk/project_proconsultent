<?php
require_once __DIR__ . '/../_common.php';
header('Content-Type: application/json');


$db = Database::getInstance();
$conn = $db->getConnection();

$action = $_POST['action'] ?? '';

try {
    if ($action === 'apply') {
        $can_code = $_POST['can_code'];
        $job_id = $_POST['job_id'];
        $status = 'applied';
        
        $stmt = $conn->prepare("INSERT INTO candidate_job_applications (can_code, job_id, status, applied_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $can_code, $job_id, $status);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    } // Add get, update, etc. as needed
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
