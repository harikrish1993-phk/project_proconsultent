<?php
require_once __DIR__ . '/../../_common.php';
header('Content-Type: application/json');

// Verify token
if (!Auth::verifyToken($_GET['token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit();
}

try {
    $conn = Database::getInstance()->getConnection();
    
    // Get jobs not already applied to by this candidate
    $candidate_id = $_GET['candidate_id'] ?? '';
    
    $query = "
        SELECT j.job_code, j.job_title, j.job_reference, j.location, c.client_name
        FROM jobs j
        JOIN clients c ON c.client_code = j.client_code
        WHERE j.status IN ('Active', 'Urgent')
        AND j.job_code NOT IN (
            SELECT job_code FROM candidate_jobs WHERE can_code = ?
        )
        ORDER BY j.created_at DESC
        LIMIT 50
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $candidate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
    
    echo json_encode(['success' => true, 'jobs' => $jobs]);
    
} catch (Exception $e) {
    error_log("Get available jobs error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>