<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';
header('Content-Type: application/json');


$db = Database::getInstance();
$conn = $db->getConnection();
$userId = Auth::userId();

try {
    // Validate required fields
    if (!isset($_POST['contact_id'])) {
        throw new Exception('Contact ID not provided');
    }
    
    $contactId = intval($_POST['contact_id']);
    
    // Verify contact exists and is not already converted
    $checkQuery = "SELECT c.*, 
                         CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                  FROM contacts c
                  LEFT JOIN users u ON c.created_by = u.user_id
                  WHERE c.contact_id = ? AND c.is_archived = 0 AND c.status != 'converted'";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $contactId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Contact not found or already converted');
    }
    
    $contact = $result->fetch_assoc();
    
    // Validate required candidate fields
    $required = ['first_name', 'last_name', 'email', 'phone'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Sanitize inputs
    $first_name = trim($_POST['first_name']);
    $middle_name = !empty($_POST['middle_name']) ? trim($_POST['middle_name']) : null;
    $last_name = trim($_POST['last_name']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone']);
    
    if (!$email) {
        throw new Exception('Invalid email address');
    }
    
    // Parse skills
    $skills = isset($_POST['skills']) ? $_POST['skills'] : '[]';
    if (!json_decode($skills)) {
        $skills = json_encode([]);
    }
    
    // Prepare optional fields
    $linkedin_url = !empty($_POST['linkedin_url']) ? trim($_POST['linkedin_url']) : null;
    $location = !empty($_POST['location']) ? trim($_POST['location']) : null;
    $current_company = !empty($_POST['current_company']) ? trim($_POST['current_company']) : null;
    $current_title = !empty($_POST['current_title']) ? trim($_POST['current_title']) : null;
    $experience_years = !empty($_POST['experience_years']) ? floatval($_POST['experience_years']) : null;
    $notice_period = !empty($_POST['notice_period']) ? trim($_POST['notice_period']) : null;
    $current_salary = !empty($_POST['current_salary']) ? floatval($_POST['current_salary']) : null;
    $expected_salary = !empty($_POST['expected_salary']) ? floatval($_POST['expected_salary']) : null;
    $summary = !empty($_POST['summary']) ? trim($_POST['summary']) : null;
    $visa_status = !empty($_POST['visa_status']) ? trim($_POST['visa_status']) : null;
    $work_type = !empty($_POST['work_type']) ? trim($_POST['work_type']) : null;
    $work_authorization = !empty($_POST['work_authorization']) ? trim($_POST['work_authorization']) : null;
    $willing_to_relocate = isset($_POST['willing_to_relocate']) ? intval($_POST['willing_to_relocate']) : 0;
    $preferred_locations = !empty($_POST['preferred_locations']) ? trim($_POST['preferred_locations']) : null;
    $candidate_status = !empty($_POST['candidate_status']) ? trim($_POST['candidate_status']) : 'Active';
    $availability_date = !empty($_POST['availability_date']) ? $_POST['availability_date'] : null;
    $source = 'Converted from Contact';
    $conversion_note = !empty($_POST['conversion_note']) ? trim($_POST['conversion_note']) : null;
    
    // Start transaction
    $conn->begin_transaction();
    
    // Insert candidate
    $insertCandidateQuery = "INSERT INTO candidates (
                                first_name, middle_name, last_name, email, phone,
                                linkedin_url, location,
                                current_company, current_title, experience_years, notice_period,
                                current_salary, expected_salary,
                                skills, summary,
                                visa_status, work_type, work_authorization,
                                willing_to_relocate, preferred_locations,
                                status, availability_date, source,
                                created_by, created_at
                            ) VALUES (
                                ?, ?, ?, ?, ?,
                                ?, ?,
                                ?, ?, ?, ?,
                                ?, ?,
                                ?, ?,
                                ?, ?, ?,
                                ?, ?,
                                ?, ?, ?,
                                ?, NOW()
                            )";
    
    $stmt = $conn->prepare($insertCandidateQuery);
    $stmt->bind_param(
        "sssssssssdsddsssssisssi",
        $first_name, $middle_name, $last_name, $email, $phone,
        $linkedin_url, $location,
        $current_company, $current_title, $experience_years, $notice_period,
        $current_salary, $expected_salary,
        $skills, $summary,
        $visa_status, $work_type, $work_authorization,
        $willing_to_relocate, $preferred_locations,
        $candidate_status, $availability_date, $source,
        $userId
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create candidate: ' . $stmt->error);
    }
    
    $candidateId = $conn->insert_id;
    
    // Update contact - mark as converted
    $updateContactQuery = "UPDATE contacts 
                          SET status = 'converted',
                              converted_to_candidate = ?,
                              converted_date = NOW(),
                              conversion_reason = ?,
                              updated_at = NOW()
                          WHERE contact_id = ?";
    
    $stmt = $conn->prepare($updateContactQuery);
    $stmt->bind_param("isi", $candidateId, $conversion_note, $contactId);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update contact status');
    }
    
    // Log activity in contact
    $activityStmt = $conn->prepare("
        INSERT INTO contact_activity_log (contact_id, activity_type, activity_description, new_value, created_by)
        VALUES (?, 'converted', 'Converted to candidate', ?, ?)
    ");
    $activityStmt->bind_param("isi", $contactId, $candidateId, $userId);
    $activityStmt->execute();
    
    // Add conversion note to contact notes if provided
    if ($conversion_note) {
        $noteStmt = $conn->prepare("
            INSERT INTO contact_notes (contact_id, note_type, note_text, is_important, created_by)
            VALUES (?, 'general', ?, 1, ?)
        ");
        $noteText = "Conversion Note: " . $conversion_note;
        $noteStmt->bind_param("isi", $contactId, $noteText, $userId);
        $noteStmt->execute();
    }
    
    // Copy contact documents to candidate documents (if table exists)
    // This is optional - depends on your candidates table structure
    $copyDocsQuery = "INSERT INTO candidate_documents (candidate_id, document_type, file_name, file_path, file_type, file_size, uploaded_by, uploaded_at)
                     SELECT ?, document_type, file_name, file_path, file_type, file_size, uploaded_by, uploaded_at
                     FROM contact_documents
                     WHERE contact_id = ?";
    
    // Check if candidate_documents table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'candidate_documents'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $stmt = $conn->prepare($copyDocsQuery);
        $stmt->bind_param("ii", $candidateId, $contactId);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'candidate_id' => $candidateId,
        'contact_id' => $contactId,
        'message' => 'Contact successfully converted to candidate'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if ($conn) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
