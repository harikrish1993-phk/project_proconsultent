<?php
require_once __DIR__ . '/../../_common.php';
header('Content-Type: application/json');


$user = Auth::user();
if (!$user || $user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

try {
    $conn = Database::getInstance()->getConnection();
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'assign':
            $can_code = $_POST['can_code'] ?? '';
            $user_code = $_POST['user_code'] ?? '';
            $user_name = $_POST['user_name'] ?? '';
            
            if (!$can_code || !$user_code || !$user_name) {
                throw new Exception('Missing required parameters');
            }
            
            // Check if assignment already exists
            $stmt = $conn->prepare("
                SELECT id FROM candidate_assignments 
                WHERE can_code = ? AND usercode = ?
            ");
            $stmt->bind_param("ss", $can_code, $user_code);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('This candidate is already assigned to this recruiter');
            }
            
            // Create assignment
            $stmt = $conn->prepare("
                INSERT INTO candidate_assignments (can_code, usercode, username, assigned_at, assigned_by)
                VALUES (?, ?, ?, NOW(), ?)
            ");
            $stmt->bind_param("ssss", $can_code, $user_code, $user_name, $user['user_code']);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create assignment: ' . $stmt->error);
            }
            
            echo json_encode(['success' => true, 'message' => 'Candidate assigned successfully']);
            break;
            
        case 'remove':
            $can_code = $_POST['can_code'] ?? '';
            $user_code = $_POST['user_code'] ?? '';
            
            if (!$can_code || !$user_code) {
                throw new Exception('Missing required parameters');
            }
            
            $stmt = $conn->prepare("
                DELETE FROM candidate_assignments 
                WHERE can_code = ? AND usercode = ?
            ");
            $stmt->bind_param("ss", $can_code, $user_code);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to remove assignment: ' . $stmt->error);
            }
            
            echo json_encode(['success' => true, 'message' => 'Assignment removed successfully']);
            break;
            
        case 'bulk_assign':
            $can_codes = $_POST['can_codes'] ?? [];
            $user_code = $_POST['user_code'] ?? '';
            
            if (empty($can_codes) || !$user_code) {
                throw new Exception('Missing required parameters');
            }
            
            // Get user name
            $stmt = $conn->prepare("SELECT full_name FROM users WHERE user_code = ?");
            $stmt->bind_param("s", $user_code);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();
            
            if (!$userData) {
                throw new Exception('Recruiter not found');
            }
            
            $user_name = $userData['full_name'];
            
            // Process each assignment
            $conn->begin_transaction();
            
            foreach ($can_codes as $can_code) {
                // Skip if already assigned
                $stmt = $conn->prepare("
                    SELECT id FROM candidate_assignments 
                    WHERE can_code = ? AND usercode = ?
                ");
                $stmt->bind_param("ss", $can_code, $user_code);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    continue;
                }
                
                $stmt = $conn->prepare("
                    INSERT INTO candidate_assignments (can_code, usercode, username, assigned_at, assigned_by)
                    VALUES (?, ?, ?, NOW(), ?)
                ");
                $stmt->bind_param("ssss", $can_code, $user_code, $user_name, $user['user_code']);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to assign candidate ' . $can_code . ': ' . $stmt->error);
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => count($can_codes) . ' candidates assigned successfully']);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {

        $conn->rollback();

    error_log("Assignment handler error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>