<?php
/**
 * Candidate Document Handler
 * Upload, download, delete documents
 */

require_once __DIR__ . '/../_common.php';
header('Content-Type: application/json');



$user = Auth::user();
$db = Database::getInstance();
$conn = $db->getConnection();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Upload directory
$upload_dir = __DIR__ . '/../../../../user/uploads/candidates/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

try {
    switch ($action) {
        case 'upload':
            $can_code = $_POST['can_code'] ?? '';
            $document_type = $_POST['document_type'] ?? 'Other';
            
            if (empty($can_code)) {
                throw new Exception('Candidate code required');
            }
            
            if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No file uploaded or upload error');
            }
            
            $file = $_FILES['document'];
            
            // Validate file type
            $allowed_types = ['application/pdf', 'application/msword', 
                              'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                              'image/jpeg', 'image/png'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                throw new Exception('Invalid file type. Only PDF, Word, JPG, PNG allowed');
            }
            
            // Validate file size (5MB max)
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception('File too large. Maximum 5MB');
            }
            
            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $can_code . '_' . time() . '_' . uniqid() . '.' . $ext;
            $filepath = $upload_dir . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Failed to save file');
            }
            
            $conn->begin_transaction();
            
            // Get current version
            $stmt = $conn->prepare("
                SELECT MAX(version) as max_version 
                FROM candidate_documents 
                WHERE can_code = ? AND document_type = ?
            ");
            $stmt->bind_param("ss", $can_code, $document_type);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $version = ($result['max_version'] ?? 0) + 1;
            $stmt->close();
            
            // Mark previous versions as not latest
            $stmt = $conn->prepare("
                UPDATE candidate_documents 
                SET is_latest = 0 
                WHERE can_code = ? AND document_type = ?
            ");
            $stmt->bind_param("ss", $can_code, $document_type);
            $stmt->execute();
            $stmt->close();
            
            // Insert new document
            $stmt = $conn->prepare("
                INSERT INTO candidate_documents 
                (can_code, document_type, document_name, file_path, file_type, file_size, version, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $rel_path = 'uploads/candidates/' . $filename;
            $stmt->bind_param("sssssiss", 
                $can_code, 
                $document_type, 
                $file['name'], 
                $rel_path, 
                $mime_type, 
                $file['size'], 
                $version, 
                $user['user_code']
            );
            $stmt->execute();
            $doc_id = $conn->insert_id;
            $stmt->close();
            
            // Log activity
            $stmt = $conn->prepare("
                INSERT INTO candidate_activity_log 
                (can_code, activity_type, activity_description, created_by)
                VALUES (?, 'Document Uploaded', ?, ?)
            ");
            $desc = "Uploaded $document_type: {$file['name']}";
            $stmt->bind_param("sss", $can_code, $desc, $user['user_code']);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Document uploaded',
                'doc_id' => $doc_id,
                'filename' => $filename
            ]);
            break;
            
        case 'get':
            $can_code = $_GET['can_code'] ?? '';
            
            if (empty($can_code)) {
                throw new Exception('Candidate code required');
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    d.doc_id,
                    d.document_type,
                    d.document_name,
                    d.file_path,
                    d.file_type,
                    d.file_size,
                    d.version,
                    d.is_latest,
                    d.uploaded_by,
                    u.name as uploaded_by_name,
                    d.uploaded_at
                FROM candidate_documents d
                LEFT JOIN user u ON d.uploaded_by = u.user_code
                WHERE d.can_code = ?
                ORDER BY d.document_type, d.version DESC
            ");
            $stmt->bind_param("s", $can_code);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $documents = [];
            while ($row = $result->fetch_assoc()) {
                $documents[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'documents' => $documents
            ]);
            break;
            
        case 'delete':
            $doc_id = (int)($_POST['doc_id'] ?? 0);
            
            if ($doc_id <= 0) {
                throw new Exception('Invalid document ID');
            }
            
            // Get document info
            $stmt = $conn->prepare("
                SELECT file_path, can_code, document_name 
                FROM candidate_documents 
                WHERE doc_id = ?
            ");
            $stmt->bind_param("i", $doc_id);
            $stmt->execute();
            $doc = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$doc) {
                throw new Exception('Document not found');
            }
            
            $conn->begin_transaction();
            
            // Delete from database
            $stmt = $conn->prepare("DELETE FROM candidate_documents WHERE doc_id = ?");
            $stmt->bind_param("i", $doc_id);
            $stmt->execute();
            $stmt->close();
            
            // Delete physical file
            $full_path = __DIR__ . '/../../../../user/' . $doc['file_path'];
            if (file_exists($full_path)) {
                unlink($full_path);
            }
            
            // Log activity
            $stmt = $conn->prepare("
                INSERT INTO candidate_activity_log 
                (can_code, activity_type, activity_description, created_by)
                VALUES (?, 'Document Deleted', ?, ?)
            ");
            $desc = "Deleted document: {$doc['document_name']}";
            $stmt->bind_param("sss", $doc['can_code'], $desc, $user['user_code']);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Document deleted'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    if (isset($conn) && $conn->in_transaction) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($conn)) $conn->close();
?>
