<?php
/**
 * CV Handler - Bulk Operations & Status Changes
 * File: panel/modules/jobs/cv/handlers/cv_handler.php
 * 
 * Handles:
 * - Bulk assign CVs to recruiters
 * - Change CV status
 * - Reject CVs with reasons
 * - Delete CVs
 */

// Load common bootstrap
require_once __DIR__ . '/../_common.php';

header('Content-Type: application/json');


try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $user = Auth::user();
    
    // Verify token
    if (!isset($_POST['token']) || $_POST['token'] !== Auth::token()) {
        throw new Exception('Invalid security token');
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'bulk_assign':
            handleBulkAssign($conn, $user);
            break;
            
        case 'change_status':
            handleStatusChange($conn, $user);
            break;
            
        case 'reject':
            handleReject($conn, $user);
            break;
            
        case 'delete':
            handleDelete($conn, $user);
            break;
            
        case 'bulk_status':
            handleBulkStatus($conn, $user);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log('CV handler error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Bulk Assign CVs to Recruiter
 */
function handleBulkAssign($conn, $user) {
    $ids = $_POST['ids'] ?? '';
    $assigned_to = trim($_POST['assigned_to'] ?? '');
    
    if (empty($ids) || empty($assigned_to)) {
        throw new Exception('CV IDs and user code required');
    }
    
    // Convert comma-separated IDs to array
    $cv_ids = array_filter(array_map('intval', explode(',', $ids)));
    
    if (empty($cv_ids)) {
        throw new Exception('No valid CV IDs provided');
    }
    
    // Verify assigned user exists
    $stmt = $conn->prepare("SELECT user_code, name FROM user WHERE user_code = ? AND status = 'active'");
    $stmt->bind_param('s', $assigned_to);
    $stmt->execute();
    $assigned_user = $stmt->get_result()->fetch_assoc();
    
    if (!$assigned_user) {
        throw new Exception('Assigned user not found or inactive');
    }
    
    // Update CVs
    $placeholders = str_repeat('?,', count($cv_ids) - 1) . '?';
    $query = "UPDATE cv_inbox SET assigned_to = ?, status = 'reviewed' WHERE id IN ($placeholders)";
    
    $stmt = $conn->prepare($query);
    $types = 's' . str_repeat('i', count($cv_ids));
    $params = array_merge([$assigned_to], $cv_ids);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to assign CVs');
    }
    
    $count = $stmt->affected_rows;
    
    // Send email notification
    if (defined('ENABLE_EMAIL_NOTIFICATIONS') && ENABLE_EMAIL_NOTIFICATIONS) {
        try {
            $mailer = new \Core\Mailer();
            $mailer->sendSimpleEmail(
                $assigned_user['email'] ?? '',
                $assigned_user['name'],
                'New CVs Assigned to You',
                "<h3>CVs Assigned</h3>
                <p>You have been assigned $count new CV(s) for review.</p>
                <p><a href='" . BASE_URL . "/panel/modules/jobs/cv/inbox.php?assigned=" . $assigned_to . "'>View CVs</a></p>"
            );
        } catch (Exception $e) {
            error_log('Email notification failed: ' . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "$count CV(s) assigned to {$assigned_user['name']} successfully"
    ]);
}

/**
 * Change Single CV Status
 */
function handleStatusChange($conn, $user) {
    $cv_id = (int)($_POST['cv_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if (!$cv_id) {
        throw new Exception('CV ID required');
    }
    
    $valid_statuses = ['new', 'reviewed', 'shortlisted', 'rejected', 'converted'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status');
    }
    
    // Get CV details
    $stmt = $conn->prepare("SELECT * FROM cv_inbox WHERE id = ?");
    $stmt->bind_param('i', $cv_id);
    $stmt->execute();
    $cv = $stmt->get_result()->fetch_assoc();
    
    if (!$cv) {
        throw new Exception('CV not found');
    }
    
    // Cannot change status if already converted
    if ($cv['status'] === 'converted') {
        throw new Exception('Cannot change status of converted CV');
    }
    
    // Update status
    $stmt = $conn->prepare("UPDATE cv_inbox SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $cv_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update status');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);
}

/**
 * Reject CV with Reason
 */
function handleReject($conn, $user) {
    $cv_id = (int)($_POST['cv_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    if (!$cv_id) {
        throw new Exception('CV ID required');
    }
    
    if (empty($reason)) {
        throw new Exception('Rejection reason is required');
    }
    
    // Update CV
    $stmt = $conn->prepare("
        UPDATE cv_inbox 
        SET status = 'rejected',
            rejected_reason = ?,
            rejected_by = ?,
            rejected_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->bind_param('ssi', $reason, $user['user_code'], $cv_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to reject CV');
    }
    
    // Optional: Send rejection email to candidate
    if (defined('ENABLE_EMAIL_NOTIFICATIONS') && ENABLE_EMAIL_NOTIFICATIONS) {
        $stmt = $conn->prepare("SELECT candidate_name, email FROM cv_inbox WHERE id = ?");
        $stmt->bind_param('i', $cv_id);
        $stmt->execute();
        $cv = $stmt->get_result()->fetch_assoc();
        
        try {
            $mailer = new \Core\Mailer();
            $mailer->sendSimpleEmail(
                $cv['email'],
                $cv['candidate_name'],
                'Application Update',
                "<h3>Thank you for your application</h3>
                <p>Dear {$cv['candidate_name']},</p>
                <p>Thank you for your interest in our position. After careful review, we have decided to move forward with other candidates at this time.</p>
                <p>We wish you the best in your job search.</p>"
            );
        } catch (Exception $e) {
            error_log('Rejection email failed: ' . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'CV rejected successfully'
    ]);
}

/**
 * Bulk Status Change
 */
function handleBulkStatus($conn, $user) {
    $ids = $_POST['ids'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if (empty($ids)) {
        throw new Exception('CV IDs required');
    }
    
    $valid_statuses = ['new', 'reviewed', 'shortlisted', 'rejected'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status');
    }
    
    $cv_ids = array_filter(array_map('intval', explode(',', $ids)));
    
    if (empty($cv_ids)) {
        throw new Exception('No valid CV IDs provided');
    }
    
    $placeholders = str_repeat('?,', count($cv_ids) - 1) . '?';
    $query = "UPDATE cv_inbox SET status = ? WHERE id IN ($placeholders) AND status != 'converted'";
    
    $stmt = $conn->prepare($query);
    $types = 's' . str_repeat('i', count($cv_ids));
    $params = array_merge([$status], $cv_ids);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update statuses');
    }
    
    $count = $stmt->affected_rows;
    
    echo json_encode([
        'success' => true,
        'message' => "$count CV(s) status updated to $status"
    ]);
}

/**
 * Delete CV (Soft Delete)
 */
function handleDelete($conn, $user) {
    $cv_id = (int)($_POST['cv_id'] ?? 0);
    
    if (!$cv_id) {
        throw new Exception('CV ID required');
    }
    
    // Soft delete
    $stmt = $conn->prepare("UPDATE cv_inbox SET status = 'deleted', deleted_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $cv_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete CV');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'CV deleted successfully'
    ]);
}
?>