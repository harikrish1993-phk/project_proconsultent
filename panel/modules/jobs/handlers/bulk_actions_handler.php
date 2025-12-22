<?php
// modules/jobs/handlers/bulk_actions_handler.php
require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

$user = Auth::user();
$db = Database::getInstance();
$conn = $db->getConnection();

$action = $_POST['action'] ?? '';
$ids = $_POST['ids'] ?? []; // Array of job_ids

if (!is_array($ids) || empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No jobs selected.']);
    exit();
}

try {
    $conn->begin_transaction();
    
    switch ($action) {
        case 'delete':
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("DELETE FROM jobs WHERE job_id IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
            if (!$stmt->execute()) throw new Exception($stmt->error);
            
            // Clean assignments, etc.
            $stmt = $conn->prepare("DELETE FROM job_assignments WHERE job_id IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
            $stmt->execute();
            
            $response = ['success' => true, 'message' => count($ids) . ' jobs deleted.'];
            break;
        
        case 'change_status':
            $new_status = $_POST['new_status'] ?? '';
            if ($user['level'] !== 'admin' || !in_array($new_status, ['pending', 'approved', 'rejected'])) {
                throw new Exception('Invalid operation.');
            }
            
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("UPDATE jobs SET status = ? WHERE job_id IN ($placeholders)");
            $params = array_merge([$new_status], $ids);
            $types = 's' . str_repeat('i', count($ids));
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) throw new Exception($stmt->error);
            
            $response = ['success' => true, 'message' => count($ids) . ' jobs status updated to ' . $new_status . '.'];
            break;
        
        case 'assign':
            $user_code = $_POST['user_code'] ?? '';
            if (empty($user_code)) throw new Exception('No recruiter selected.');
            
            foreach ($ids as $job_id) {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_assignments WHERE job_id = ? AND user_code = ?");
                $stmt->bind_param('is', $job_id, $user_code);
                $stmt->execute();
                if ($stmt->get_result()->fetch_assoc()['count'] == 0) {
                    $stmt = $conn->prepare("INSERT INTO job_assignments (job_id, user_code) VALUES (?, ?)");
                    $stmt->bind_param('is', $job_id, $user_code);
                    $stmt->execute();
                }
            }
            
            $response = ['success' => true, 'message' => count($ids) . ' jobs assigned.'];
            break;
        
        default:
            throw new Exception('Invalid action.');
    }
    
    $conn->commit();
    echo json_encode($response);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>