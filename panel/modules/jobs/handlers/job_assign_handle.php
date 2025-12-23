<?php
// modules/jobs/handlers/job_assign_handler.php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';
header('Content-Type: application/json');

$db = Database::getInstance();
$conn = $db->getConnection();

$action = $_POST['action'] ?? '';
$job_id = intval($_POST['job_id'] ?? 0);
$user_code = $_POST['user_code'] ?? '';

try {
    if ($action === 'assign') {
        if (!$job_id || empty($user_code)) throw new Exception('Invalid parameters.');
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_assignments WHERE job_id = ? AND user_code = ?");
        $stmt->bind_param('is', $job_id, $user_code);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()['count'] > 0) throw new Exception('Already assigned.');
        
        $stmt = $conn->prepare("INSERT INTO job_assignments (job_id, user_code) VALUES (?, ?)");
        $stmt->bind_param('is', $job_id, $user_code);
        if (!$stmt->execute()) throw new Exception($stmt->error);
        
        echo json_encode(['success' => true, 'message' => 'Assigned successfully.']);
    } elseif ($action === 'unassign') {
        if (!$job_id || empty($user_code)) throw new Exception('Invalid parameters.');
        
        $stmt = $conn->prepare("DELETE FROM job_assignments WHERE job_id = ? AND user_code = ?");
        $stmt->bind_param('is', $job_id, $user_code);
        if (!$stmt->execute()) throw new Exception($stmt->error);
        
        echo json_encode(['success' => true, 'message' => 'Unassigned successfully.']);
    } else {
        throw new Exception('Invalid action.');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>