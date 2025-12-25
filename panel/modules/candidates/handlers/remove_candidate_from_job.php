<?php
require_once __DIR__ . '/../../_common.php';
header('Content-Type: application/json');

// Verify token
if (!Auth::verifyToken($_POST['token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit();
}

try {
    $conn = Database::getInstance()->getConnection();
    
    $candidate_job_id = $_POST['candidate_job_id'] ?? '';
    
    if (!$candidate_job_id) {
        throw new Exception('Missing candidate job ID');
    }
    
    $stmt = $conn->prepare("DELETE FROM candidate_jobs WHERE id = ?");
    $stmt->bind_param("i", $candidate_job_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to remove candidate from job: ' . $stmt->error);
    }
    
    echo json_encode(['success' => true, 'message' => 'Candidate removed from job successfully']);
    
} catch (Exception $e) {
    error_log("Remove candidate from job error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>