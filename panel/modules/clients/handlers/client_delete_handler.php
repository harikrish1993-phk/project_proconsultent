<?php
/**
 * Client Delete Handler (Soft Delete)
 * File: panel/modules/clients/handlers/client_delete_handler.php
 */

require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';

// Set JSON header
header('Content-Type: application/json');

// Check authentication
if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $user = Auth::user();
    
    // Only admin can delete clients
    if ($user['level'] !== 'admin') {
        throw new Exception('Only administrators can delete clients');
    }
    
    // Verify token
    if (!isset($_POST['token']) || $_POST['token'] !== Auth::token()) {
        throw new Exception('Invalid security token');
    }
    
    // Get client ID
    $client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
    
    if (!$client_id) {
        throw new Exception('Client ID is required');
    }
    
    // Check if client exists
    $stmt = $conn->prepare("SELECT client_name FROM clients WHERE client_id = ?");
    $stmt->bind_param('i', $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $client = $result->fetch_assoc();
    
    if (!$client) {
        throw new Exception('Client not found');
    }
    
    // Check for active jobs
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM jobs 
        WHERE client_id = ? 
        AND job_status = 'active' 
        AND deleted_at IS NULL
    ");
    $stmt->bind_param('i', $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $active_jobs = $result->fetch_assoc()['count'];
    
    if ($active_jobs > 0) {
        throw new Exception("Cannot delete client with {$active_jobs} active job(s). Please close or reassign jobs first.");
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Soft delete client (set status to inactive instead of hard delete)
        // This preserves historical data
        $stmt = $conn->prepare("
            UPDATE clients 
            SET status = 'inactive',
                updated_at = NOW()
            WHERE client_id = ?
        ");
        $stmt->bind_param('i', $client_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete client: ' . $stmt->error);
        }
        
        // Optionally soft delete associated jobs
        $stmt = $conn->prepare("
            UPDATE jobs 
            SET deleted_at = NOW(),
                job_status = 'closed'
            WHERE client_id = ? AND deleted_at IS NULL
        ");
        $stmt->bind_param('i', $client_id);
        $stmt->execute();
        
        // Log activity
        $activity_stmt = $conn->prepare("
            INSERT INTO activity_log (user_code, action, entity_type, entity_id, description)
            VALUES (?, 'delete_client', 'client', ?, ?)
        ");
        
        $description = "Deleted client: {$client['client_name']}";
        $activity_stmt->bind_param('sis', $user['user_code'], $client_id, $description);
        $activity_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Client deleted successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Client delete error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>