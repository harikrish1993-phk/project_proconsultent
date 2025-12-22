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
    // Validate required fields
    if (!isset($_POST['contact_id']) || !isset($_FILES['document'])) {
        throw new Exception('Missing required fields');
    }
    
    $contactId = intval($_POST['contact_id']);
    $documentType = isset($_POST['document_type']) ? trim($_POST['document_type']) : 'other';
    
    // Verify contact exists
    $checkQuery = "SELECT contact_id FROM contacts WHERE contact_id = ? AND is_archived = 0";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $contactId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Contact not found');
    }
    
    // Validate file
    $file = $_FILES['document'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error');
    }
    
    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File size exceeds 5MB limit');
    }
    
    // Check file type
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Invalid file type. Allowed: PDF, DOC, DOCX, TXT');
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../../../uploads/contacts/' . $contactId . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = $file['name'];
    $uniqueFileName = time() . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . $uniqueFileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to save file');
    }
    
    // Insert document record
    $insertQuery = "INSERT INTO contact_documents (contact_id, document_type, file_name, file_path, file_type, file_size, uploaded_by, uploaded_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("issssii", $contactId, $documentType, $fileName, $filePath, $mimeType, $file['size'], $userId);
    
    if (!$stmt->execute()) {
        // Delete uploaded file if database insert fails
        unlink($filePath);
        throw new Exception('Failed to save document record: ' . $stmt->error);
    }
    
    $docId = $conn->insert_id;
    
    // Log activity
    $activityStmt = $conn->prepare("
        INSERT INTO contact_activity_log (contact_id, activity_type, activity_description, created_by)
        VALUES (?, 'document_uploaded', ?, ?)
    ");
    $activityDesc = ucfirst($documentType) . " document uploaded: " . $fileName;
    $activityStmt->bind_param("isi", $contactId, $activityDesc, $userId);
    $activityStmt->execute();
    
    echo json_encode([
        'success' => true,
        'doc_id' => $docId,
        'file_name' => $fileName,
        'message' => 'Document uploaded successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
