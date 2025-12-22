<?php
/**
 * User Delete Handler
 * File: panel/modules/users/handlers/user_delete_handler.php
 */

require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';

header('Content-Type: application/json');

// Check authentication and admin access
if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (Auth::user()['level'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Only administrators can delete users']);
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $current_user = Auth::user();
    
    // Verify token
    if (!isset($_POST['token']) || $_POST['token'] !== Auth::token()) {
        throw new Exception('Invalid security token');
    }
    
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    
    if (!$user_id) {
        throw new Exception('User ID is required');
    }
    
    // Get user details
    $stmt = $conn->prepare("SELECT user_code, name, email FROM user WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('User not found');
    }
    
    $user = $result->fetch_assoc();
    
    // Prevent self-deletion
    if ($user['user_code'] === $current_user['user_code']) {
        throw new Exception('You cannot delete your own account');
    }
    
    // Check for related data
    $checks = [
        'jobs' => "SELECT COUNT(*) as count FROM jobs WHERE created_by = ?",
        'candidates' => "SELECT COUNT(*) as count FROM candidates WHERE created_by = ?",
        'applications' => "SELECT COUNT(*) as count FROM job_applications WHERE created_by = ?"
    ];
    
    $has_data = [];
    foreach ($checks as $entity => $query) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $user['user_code']);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        if ($count > 0) {
            $has_data[] = "$count $entity";
        }
    }
    
    if (!empty($has_data)) {
        throw new Exception('Cannot delete user. They have created: ' . implode(', ', $has_data) . '. Please reassign or delete this data first.');
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete user
        $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete user: ' . $stmt->error);
        }
        
        // Log activity
        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_code, action, entity_type, entity_id, description)
            VALUES (?, 'delete_user', 'user', ?, ?)
        ");
        
        $description = "Deleted user: {$user['name']} ({$user['email']})";
        $stmt->bind_param('sis', $current_user['user_code'], $user_id, $description);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('User delete error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>