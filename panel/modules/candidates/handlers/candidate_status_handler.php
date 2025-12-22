<?php
/**
 * Candidate Status Handler
 * Handles status changes with automatic activity logging
 */

require_once __DIR__ . '/../../../../includes/config/config.php';
require_once __DIR__ . '/../../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../../includes/core/Database.php';

header('Content-Type: application/json');

// Check authentication
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user = Auth::user();
$db = Database::getInstance();
$conn = $db->getConnection();

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        
        case 'change_status':
            // Change candidate status
            $can_code = $_POST['can_code'] ?? '';
            $new_status = $_POST['status'] ?? '';
            $reason = $_POST['reason'] ?? '';
            
            if (empty($can_code) || empty($new_status)) {
                throw new Exception('Candidate code and status required');
            }
            
            // Validate status exists
            $stmt = $conn->prepare("
                SELECT status_value FROM candidate_statuses 
                WHERE status_value = ? AND is_active = 1
            ");
            $stmt->bind_param("s", $new_status);
            $stmt->execute();
            $valid_status = $stmt->get_result()->num_rows > 0;
            $stmt->close();
            
            if (!$valid_status) {
                throw new Exception('Invalid status value');
            }
            
            $conn->begin_transaction();
            
            // Get current status
            $stmt = $conn->prepare("
                SELECT candidate_status, candidate_name 
                FROM candidates WHERE can_code = ?
            ");
            $stmt->bind_param("s", $can_code);
            $stmt->execute();
            $result = $stmt->get_result();
            $candidate = $result->fetch_assoc();
            $stmt->close();
            
            if (!$candidate) {
                throw new Exception('Candidate not found');
            }
            
            $old_status = $candidate['candidate_status'];
            
            // Update status
            $stmt = $conn->prepare("
                UPDATE candidates 
                SET candidate_status = ?, updated_at = NOW()
                WHERE can_code = ?
            ");
            $stmt->bind_param("ss", $new_status, $can_code);
            $stmt->execute();
            $stmt->close();
            
            // Log activity (trigger will also log, but this is more detailed)
            $description = "Status changed from {$old_status} to {$new_status}";
            if ($reason) {
                $description .= ". Reason: {$reason}";
            }
            
            $stmt = $conn->prepare("
                INSERT INTO candidate_activity_log 
                (can_code, activity_type, activity_description, old_value, new_value, created_by)
                VALUES (?, 'Status Changed', ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssss", $can_code, $description, $old_status, $new_status, $user['user_code']);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Status updated successfully',
                'old_status' => $old_status,
                'new_status' => $new_status
            ]);
            break;
            
        case 'bulk_status':
            // Bulk status change
            $can_codes = $_POST['can_codes'] ?? [];
            $new_status = $_POST['status'] ?? '';
            $reason = $_POST['reason'] ?? '';
            
            if (empty($can_codes) || !is_array($can_codes)) {
                throw new Exception('No candidates selected');
            }
            
            if (empty($new_status)) {
                throw new Exception('Status is required');
            }
            
            // Validate status
            $stmt = $conn->prepare("
                SELECT status_value FROM candidate_statuses 
                WHERE status_value = ? AND is_active = 1
            ");
            $stmt->bind_param("s", $new_status);
            $stmt->execute();
            $valid = $stmt->get_result()->num_rows > 0;
            $stmt->close();
            
            if (!$valid) {
                throw new Exception('Invalid status');
            }
            
            $conn->begin_transaction();
            
            $updated_count = 0;
            foreach ($can_codes as $can_code) {
                // Get current status
                $stmt = $conn->prepare("SELECT candidate_status FROM candidates WHERE can_code = ?");
                $stmt->bind_param("s", $can_code);
                $stmt->execute();
                $result = $stmt->get_result();
                $candidate = $result->fetch_assoc();
                $stmt->close();
                
                if (!$candidate) continue;
                
                $old_status = $candidate['candidate_status'];
                
                // Update
                $stmt = $conn->prepare("
                    UPDATE candidates 
                    SET candidate_status = ?, updated_at = NOW()
                    WHERE can_code = ?
                ");
                $stmt->bind_param("ss", $new_status, $can_code);
                $stmt->execute();
                $stmt->close();
                
                // Log
                $description = "Bulk status change from {$old_status} to {$new_status}";
                if ($reason) {
                    $description .= ". Reason: {$reason}";
                }
                
                $stmt = $conn->prepare("
                    INSERT INTO candidate_activity_log 
                    (can_code, activity_type, activity_description, old_value, new_value, created_by)
                    VALUES (?, 'Bulk Status Change', ?, ?, ?, ?)
                ");
                $stmt->bind_param("sssss", $can_code, $description, $old_status, $new_status, $user['user_code']);
                $stmt->execute();
                $stmt->close();
                
                $updated_count++;
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => "Updated status for $updated_count candidates",
                'updated_count' => $updated_count
            ]);
            break;
            
        case 'get_statuses':
            // Get all available statuses
            $stmt = $conn->prepare("
                SELECT status_value, status_label, status_color, status_order
                FROM candidate_statuses
                WHERE is_active = 1
                ORDER BY status_order ASC
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $statuses = [];
            while ($row = $result->fetch_assoc()) {
                $statuses[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'statuses' => $statuses
            ]);
            break;
            
        case 'add_status':
            // Add new status (admin only)
            if ($user['level'] !== 'admin') {
                throw new Exception('Admin access required');
            }
            
            $value = $_POST['status_value'] ?? '';
            $label = $_POST['status_label'] ?? '';
            $color = $_POST['status_color'] ?? 'secondary';
            $order = (int)($_POST['status_order'] ?? 999);
            
            if (empty($value) || empty($label)) {
                throw new Exception('Status value and label required');
            }
            
            $stmt = $conn->prepare("
                INSERT INTO candidate_statuses 
                (status_value, status_label, status_color, status_order)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("sssi", $value, $label, $color, $order);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Status added successfully'
            ]);
            break;
            
        case 'toggle_status':
            // Activate/deactivate status (admin only)
            if ($user['level'] !== 'admin') {
                throw new Exception('Admin access required');
            }
            
            $status_id = (int)($_POST['status_id'] ?? 0);
            $is_active = (int)($_POST['is_active'] ?? 1);
            
            $stmt = $conn->prepare("
                UPDATE candidate_statuses 
                SET is_active = ?
                WHERE status_id = ?
            ");
            $stmt->bind_param("ii", $is_active, $status_id);
            $stmt->execute();
            $stmt->close();
            
            $action_text = $is_active ? 'activated' : 'deactivated';
            
            echo json_encode([
                'success' => true,
                'message' => "Status $action_text successfully"
            ]);
            break;
            
        case 'get_status_stats':
            // Get candidate count by status
            $stmt = $conn->query("
                SELECT 
                    cs.status_value,
                    cs.status_label,
                    cs.status_color,
                    COUNT(c.can_code) as count
                FROM candidate_statuses cs
                LEFT JOIN candidates c ON cs.status_value = c.candidate_status AND c.is_archived = 0
                WHERE cs.is_active = 1
                GROUP BY cs.status_value, cs.status_label, cs.status_color
                ORDER BY cs.status_order ASC
            ");
            
            $stats = [];
            while ($row = $stmt->fetch_assoc()) {
                $stats[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
