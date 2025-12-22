<?php
require_once '../../../includes/auth.php';
require_once '../../../includes/db.php';

header('Content-Type: application/json');

// Check authentication
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$userId = Auth::userId();

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['doc_id'])) {
        throw new Exception('Document ID not provided');
    }
    
    $docId = intval($input['doc_id']);
    
    // Fetch document details
    $fetchQuery = "SELECT cd.*, c.contact_id 
                   FROM contact_documents cd
                   JOIN contacts c ON cd.contact_id = c.contact_id
                   WHERE cd.doc_id = ? AND c.is_archived = 0";
    $stmt = $conn->prepare($fetchQuery);
    $stmt->bind_param("i", $docId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Document not found');
    }
    
    $document = $result->fetch_assoc();
    $contactId = $document['contact_id'];
    $filePath = $document['file_path'];
    
    // Delete file from filesystem
    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            throw new Exception('Failed to delete file from server');
        }
    }
    
    // Delete document record from database
    $deleteQuery = "DELETE FROM contact_documents WHERE doc_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $docId);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete document record');
    }
    
    // Log activity
    $activityStmt = $conn->prepare("
        INSERT INTO contact_activity_log (contact_id, activity_type, activity_description, created_by)
        VALUES (?, 'document_deleted', ?, ?)
    ");
    $activityDesc = "Document deleted: " . $document['file_name'];
    $activityStmt->bind_param("isi", $contactId, $activityDesc, $userId);
    $activityStmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Document deleted successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
