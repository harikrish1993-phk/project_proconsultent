<?php
/**
 * Candidate Note Handler
 * Simple note management
 */

require_once __DIR__ . '/../_common.php';
header('Content-Type: application/json');

$user = Auth::user();
$db = Database::getInstance();
$conn = $db->getConnection();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        case 'add':
            $can_code = $_POST['can_code'] ?? '';
            $note_text = trim($_POST['note_text'] ?? '');
            $note_type = $_POST['note_type'] ?? 'General';
            $is_important = (int)($_POST['is_important'] ?? 0);
            
            if (empty($can_code) || empty($note_text)) {
                throw new Exception('Candidate code and note text required');
            }
            
            $stmt = $conn->prepare("
                INSERT INTO candidate_notes 
                (can_code, note_type, note_text, is_important, created_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssis", $can_code, $note_type, $note_text, $is_important, $user['user_code']);
            $stmt->execute();
            $note_id = $conn->insert_id;
            $stmt->close();
            
            // Log activity
            $stmt = $conn->prepare("
                INSERT INTO candidate_activity_log 
                (can_code, activity_type, activity_description, created_by)
                VALUES (?, 'Note Added', ?, ?)
            ");
            $desc = "Note added: " . substr($note_text, 0, 50) . (strlen($note_text) > 50 ? '...' : '');
            $stmt->bind_param("sss", $can_code, $desc, $user['user_code']);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Note added successfully',
                'note_id' => $note_id
            ]);
            break;
            
        case 'get_notes':
            $can_code = $_GET['can_code'] ?? '';
            
            if (empty($can_code)) {
                throw new Exception('Candidate code required');
            }
            
            $stmt = $conn->prepare("
                SELECT cn.*, u.name as author_name
                FROM candidate_notes cn
                LEFT JOIN user u ON cn.created_by = u.user_code
                WHERE cn.can_code = ?
                ORDER BY cn.created_at DESC
            ");
            $stmt->bind_param("s", $can_code);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $notes = [];
            while ($row = $result->fetch_assoc()) {
                $notes[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'notes' => $notes
            ]);
            break;
            
        case 'delete':
            $note_id = (int)($_POST['note_id'] ?? 0);
            
            if ($note_id <= 0) {
                throw new Exception('Invalid note ID');
            }
            
            // Check ownership (only creator or admin can delete)
            $stmt = $conn->prepare("
                SELECT created_by FROM candidate_notes WHERE note_id = ?
            ");
            $stmt->bind_param("i", $note_id);
            $stmt->execute();
            $note = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$note) {
                throw new Exception('Note not found');
            }
            
            if ($note['created_by'] !== $user['user_code'] && $user['level'] !== 'admin') {
                throw new Exception('Permission denied');
            }
            
            $stmt = $conn->prepare("DELETE FROM candidate_notes WHERE note_id = ?");
            $stmt->bind_param("i", $note_id);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Note deleted'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
