<?php
/**
 * Candidate Bulk Operations Handler
 * Handle bulk actions: delete, export, assign, status change
 */

require_once __DIR__ . '/../../../../includes/config/config.php';
require_once __DIR__ . '/../../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../../includes/core/Database.php';

header('Content-Type: application/json');

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
        
        case 'bulk_delete':
            // Soft delete multiple candidates
            $can_codes = $_POST['can_codes'] ?? [];
            
            if (empty($can_codes) || !is_array($can_codes)) {
                throw new Exception('No candidates selected');
            }
            
            // Admin only
            if ($user['level'] !== 'admin') {
                throw new Exception('Admin access required');
            }
            
            $conn->begin_transaction();
            
            $deleted_count = 0;
            foreach ($can_codes as $can_code) {
                $stmt = $conn->prepare("
                    UPDATE candidates 
                    SET is_archived = 1, archived_by = ?, archived_at = NOW()
                    WHERE can_code = ?
                ");
                $stmt->bind_param("ss", $user['user_code'], $can_code);
                $stmt->execute();
                $deleted_count += $stmt->affected_rows;
                $stmt->close();
                
                // Log activity
                $stmt = $conn->prepare("
                    INSERT INTO candidate_activity_log 
                    (can_code, activity_type, activity_description, created_by)
                    VALUES (?, 'Archived', 'Bulk archive operation', ?)
                ");
                $stmt->bind_param("ss", $can_code, $user['user_code']);
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => "Archived $deleted_count candidates",
                'deleted_count' => $deleted_count
            ]);
            break;
            
        case 'bulk_restore':
            // Restore archived candidates
            $can_codes = $_POST['can_codes'] ?? [];
            
            if (empty($can_codes) || !is_array($can_codes)) {
                throw new Exception('No candidates selected');
            }
            
            if ($user['level'] !== 'admin') {
                throw new Exception('Admin access required');
            }
            
            $conn->begin_transaction();
            
            $restored_count = 0;
            foreach ($can_codes as $can_code) {
                $stmt = $conn->prepare("
                    UPDATE candidates 
                    SET is_archived = 0, archived_by = NULL, archived_at = NULL
                    WHERE can_code = ?
                ");
                $stmt->bind_param("s", $can_code);
                $stmt->execute();
                $restored_count += $stmt->affected_rows;
                $stmt->close();
                
                // Log activity
                $stmt = $conn->prepare("
                    INSERT INTO candidate_activity_log 
                    (can_code, activity_type, activity_description, created_by)
                    VALUES (?, 'Restored', 'Bulk restore operation', ?)
                ");
                $stmt->bind_param("ss", $can_code, $user['user_code']);
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => "Restored $restored_count candidates",
                'restored_count' => $restored_count
            ]);
            break;
            
        case 'bulk_tag':
            // Add tag to multiple candidates
            $can_codes = $_POST['can_codes'] ?? [];
            $tag_name = trim($_POST['tag_name'] ?? '');
            
            if (empty($can_codes) || empty($tag_name)) {
                throw new Exception('Candidates and tag name required');
            }
            
            $conn->begin_transaction();
            
            // Get or create tag
            $stmt = $conn->prepare("
                INSERT INTO candidate_tags (tag_name) 
                VALUES (?)
                ON DUPLICATE KEY UPDATE tag_id = LAST_INSERT_ID(tag_id)
            ");
            $stmt->bind_param("s", $tag_name);
            $stmt->execute();
            $tag_id = $conn->insert_id;
            $stmt->close();
            
            // Add tag to candidates
            $tagged_count = 0;
            foreach ($can_codes as $can_code) {
                $stmt = $conn->prepare("
                    INSERT IGNORE INTO candidate_tag_map (can_code, tag_id)
                    VALUES (?, ?)
                ");
                $stmt->bind_param("si", $can_code, $tag_id);
                $stmt->execute();
                $tagged_count += $stmt->affected_rows;
                $stmt->close();
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => "Tagged $tagged_count candidates with '$tag_name'",
                'tagged_count' => $tagged_count
            ]);
            break;
            
        case 'bulk_export':
            // Export selected candidates
            $can_codes = $_POST['can_codes'] ?? [];
            
            if (empty($can_codes)) {
                throw new Exception('No candidates selected');
            }
            
            // Get candidate data
            $placeholders = str_repeat('?,', count($can_codes) - 1) . '?';
            $query = "
                SELECT 
                    c.can_code,
                    c.candidate_name,
                    c.email_id,
                    c.contact_details,
                    c.current_position,
                    c.experience,
                    c.current_location,
                    c.candidate_status,
                    c.skill_set,
                    wa.status as work_auth
                FROM candidates c
                LEFT JOIN work_authorization wa ON c.work_auth_status = wa.id
                WHERE c.can_code IN ($placeholders)
            ";
            
            $stmt = $conn->prepare($query);
            $types = str_repeat('s', count($can_codes));
            $stmt->bind_param($types, ...$can_codes);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $candidates = [];
            while ($row = $result->fetch_assoc()) {
                $candidates[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'candidates' => $candidates,
                'count' => count($candidates)
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
