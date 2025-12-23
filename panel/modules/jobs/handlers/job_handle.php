<?php
// modules/jobs/handlers/job_handle.php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';

header('Content-Type: application/json');


$user = Auth::user();
$db = Database::getInstance();
$conn = $db->getConnection();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $conn->begin_transaction();
    
    switch ($action) {
        case 'create':
            $title = trim($_POST['title'] ?? '');
            $description = $_POST['description'] ?? '';
            $requirements = $_POST['requirements'] ?? '';
            $salary_min = floatval($_POST['salary_min'] ?? 0);
            $salary_max = floatval($_POST['salary_max'] ?? 0);
            $job_code = trim($_POST['job_code'] ?? '');
            
            if (empty($title) || empty($description)) {
                throw new Exception('Job title and description are required.');
            }
            
            if ($salary_max < $salary_min) {
                throw new Exception('Maximum salary must be greater than or equal to minimum salary.');
            }
            
            $created_by = $user['user_code'];
            $status = 'pending'; // Consistent lowercase
            
            $stmt = $conn->prepare("
                INSERT INTO jobs (job_code, title, description, requirements, salary_min, salary_max, status, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param('ssssddss', $job_code, $title, $description, $requirements, $salary_min, $salary_max, $status, $created_by);
            
            if (!$stmt->execute()) {
                throw new Exception('Database error: ' . $stmt->error);
            }
            
            $job_id = $stmt->insert_id;
            $stmt->close();
            
            // Log activity (assuming candidates_edit_info like table for jobs - or new job_logs)
            // For simplicity, skip if no table
            
            $response = ['success' => true, 'message' => 'Job created successfully.', 'job_id' => $job_id];
            break;
        
        case 'update':
            $job_id = intval($_POST['job_id'] ?? 0);
            if (!$job_id) throw new Exception('Invalid job ID.');
            
            // Fetch existing for logging changes
            $stmt = $conn->prepare("SELECT * FROM jobs WHERE job_id = ?");
            $stmt->bind_param('i', $job_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            if (!$existing) throw new Exception('Job not found.');
            
            $title = trim($_POST['title'] ?? $existing['title']);
            $description = $_POST['description'] ?? $existing['description'];
            // Similar for other fields
            
            $stmt = $conn->prepare("
                UPDATE jobs SET 
                    title = ?, 
                    description = ?, 
                    requirements = ?, 
                    salary_min = ?, 
                    salary_max = ?, 
                    updated_at = NOW()
                WHERE job_id = ?
            ");
            $stmt->bind_param('sssddi', $title, $description, $requirements, $salary_min, $salary_max, $job_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Database error: ' . $stmt->error);
            }
            
            // Log changes if different
            // e.g., if ($title != $existing['title']) insert log
            
            $response = ['success' => true, 'message' => 'Job updated successfully.'];
            break;
        
        case 'delete':
            $job_id = intval($_POST['job_id'] ?? 0);
            if (!$job_id) throw new Exception('Invalid job ID.');
            
            // Check if can delete (e.g., no submissions)
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM submittedcv WHERE job_id = ?");
            $stmt->bind_param('i', $job_id);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()['count'] > 0) {
                throw new Exception('Cannot delete job with submissions.');
            }
            
            $stmt = $conn->prepare("DELETE FROM jobs WHERE job_id = ?");
            $stmt->bind_param('i', $job_id);
            if (!$stmt->execute()) {
                throw new Exception('Database error: ' . $stmt->error);
            }
            
            $response = ['success' => true, 'message' => 'Job deleted successfully.'];
            break;
        
        case 'change_status':
            $job_id = intval($_POST['job_id'] ?? 0);
            $new_status = $_POST['new_status'] ?? '';
            if (!$job_id || !in_array($new_status, ['pending', 'approved', 'rejected'])) {
                throw new Exception('Invalid job ID or status.');
            }
            
            if ($user['level'] !== 'admin') {
                throw new Exception('Only administrators can change job status.');
            }
            
            $stmt = $conn->prepare("UPDATE jobs SET status = ? WHERE job_id = ?");
            $stmt->bind_param('si', $new_status, $job_id);
            if (!$stmt->execute()) {
                throw new Exception('Database error: ' . $stmt->error);
            }
            
            // Notify creator
            // mail(...)
            
            $response = ['success' => true, 'message' => 'Job status updated to ' . $new_status . '.'];
            break;
        
        case 'assign':
            $job_id = intval($_POST['job_id'] ?? 0);
            $user_code = $_POST['user_code'] ?? '';
            if (!$job_id || empty($user_code)) {
                throw new Exception('Invalid parameters.');
            }
            
            if ($user['level'] !== 'admin') {
                throw new Exception('Only administrators can assign recruiters.');
            }
            
            // Check if already assigned
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_assignments WHERE job_id = ? AND user_code = ?");
            $stmt->bind_param('is', $job_id, $user_code);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()['count'] > 0) {
                throw new Exception('Recruiter already assigned.');
            }
            
            $stmt = $conn->prepare("INSERT INTO job_assignments (job_id, user_code) VALUES (?, ?)");
            $stmt->bind_param('is', $job_id, $user_code);
            if (!$stmt->execute()) {
                throw new Exception('Database error: ' . $stmt->error);
            }
            
            $response = ['success' => true, 'message' => 'Recruiter assigned successfully.'];
            break;
        
        case 'unassign':
            $job_id = intval($_POST['job_id'] ?? 0);
            $user_code = $_POST['user_code'] ?? '';
            if (!$job_id || empty($user_code)) {
                throw new Exception('Invalid parameters.');
            }
            
            if ($user['level'] !== 'admin') {
                throw new Exception('Only administrators can unassign recruiters.');
            }
            
            $stmt = $conn->prepare("DELETE FROM job_assignments WHERE job_id = ? AND user_code = ?");
            $stmt->bind_param('is', $job_id, $user_code);
            if (!$stmt->execute()) {
                throw new Exception('Database error: ' . $stmt->error);
            }
            
            $response = ['success' => true, 'message' => 'Recruiter unassigned successfully.'];
            break;
        
        default:
            throw new Exception('Invalid action.');
    }
    
    $conn->commit();
    echo json_encode($response);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>