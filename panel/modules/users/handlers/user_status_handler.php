<?php
/**
 * User Status Handler - Activate/Deactivate
 * File: panel/modules/users/handlers/user_status_handler.php
 */

// Load common bootstrap
require_once __DIR__ . '/../_common.php';

header('Content-Type: application/json');


if (Auth::user()['level'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Only administrators can change user status']);
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
    $new_status = $_POST['status'] ?? '';
    
    if (!$user_id) {
        throw new Exception('User ID is required');
    }
    
    if (!in_array($new_status, ['active', 'inactive'])) {
        throw new Exception('Invalid status');
    }
    
    // Get user details
    $stmt = $conn->prepare("SELECT user_code, name FROM user WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('User not found');
    }
    
    $user = $result->fetch_assoc();
    
    // Prevent self-deactivation
    if ($user['user_code'] === $current_user['user_code'] && $new_status === 'inactive') {
        throw new Exception('You cannot deactivate your own account');
    }
    
    // Update status
    $stmt = $conn->prepare("UPDATE user SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $new_status, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update user status');
    }
    
    // Log activity
    $stmt = $conn->prepare("
        INSERT INTO activity_log (user_code, action, entity_type, entity_id, description)
        VALUES (?, 'change_user_status', 'user', ?, ?)
    ");
    
    $description = "Changed user status to {$new_status}: {$user['name']}";
    $stmt->bind_param('sis', $current_user['user_code'], $user_id, $description);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'User status updated successfully'
    ]);
    
} catch (Exception $e) {
    error_log('User status error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>