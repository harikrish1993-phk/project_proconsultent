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
    // Check if single field update or full form update
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // Single field update (from view page quick edits)
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['contact_id']) || !isset($input['field']) || !isset($input['value'])) {
            throw new Exception('Missing required parameters');
        }
        
        $contactId = intval($input['contact_id']);
        $field = $input['field'];
        $value = $input['value'];
        
        // Whitelist allowed fields for security
        $allowedFields = [
            'status', 'priority', 'next_follow_up', 'assigned_to',
            'current_salary', 'expected_salary', 'notice_period'
        ];
        
        if (!in_array($field, $allowedFields)) {
            throw new Exception('Invalid field');
        }
        
        // Update single field
        $updateQuery = "UPDATE contacts SET $field = ?, updated_at = NOW() WHERE contact_id = ?";
        $stmt = $conn->prepare($updateQuery);
        
        if ($field === 'assigned_to') {
            $assignedTo = $value ? intval($value) : null;
            $stmt->bind_param("ii", $assignedTo, $contactId);
        } else {
            $stmt->bind_param("si", $value, $contactId);
        }
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update contact');
        }
        
        // Log activity
        $activityStmt = $conn->prepare("
            INSERT INTO contact_activity_log (contact_id, activity_type, activity_description, new_value, created_by)
            VALUES (?, 'field_updated', ?, ?, ?)
        ");
        $activityDesc = ucfirst(str_replace('_', ' ', $field)) . " updated";
        $activityStmt->bind_param("issi", $contactId, $activityDesc, $value, $userId);
        $activityStmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Contact updated successfully']);
        
    } else {
        // Full form update (from edit page)
        if (!isset($_POST['contact_id'])) {
            throw new Exception('Contact ID not provided');
        }
        
        $contactId = intval($_POST['contact_id']);
        
        // Verify contact exists
        $checkQuery = "SELECT contact_id FROM contacts WHERE contact_id = ? AND is_archived = 0";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("i", $contactId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception('Contact not found');
        }
        
        // Parse skills
        $skills = isset($_POST['skills']) ? $_POST['skills'] : '[]';
        if (!json_decode($skills)) {
            $skills = json_encode([]);
        }
        
        // Prepare fields
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $phone = trim($_POST['phone']);
        
        if (!$email) {
            throw new Exception('Invalid email address');
        }
        
        // Optional fields
        $alternate_phone = !empty($_POST['alternate_phone']) ? trim($_POST['alternate_phone']) : null;
        $linkedin_url = !empty($_POST['linkedin_url']) ? trim($_POST['linkedin_url']) : null;
        $current_company = !empty($_POST['current_company']) ? trim($_POST['current_company']) : null;
        $current_title = !empty($_POST['current_title']) ? trim($_POST['current_title']) : null;
        $current_location = !empty($_POST['current_location']) ? trim($_POST['current_location']) : null;
        $preferred_locations = !empty($_POST['preferred_locations']) ? trim($_POST['preferred_locations']) : null;
        $experience_years = !empty($_POST['experience_years']) ? floatval($_POST['experience_years']) : null;
        $notice_period = !empty($_POST['notice_period']) ? trim($_POST['notice_period']) : null;
        $current_salary = !empty($_POST['current_salary']) ? floatval($_POST['current_salary']) : null;
        $expected_salary = !empty($_POST['expected_salary']) ? floatval($_POST['expected_salary']) : null;
        $interested_roles = !empty($_POST['interested_roles']) ? trim($_POST['interested_roles']) : null;
        $source_details = !empty($_POST['source_details']) ? trim($_POST['source_details']) : null;
        $assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        $priority = !empty($_POST['priority']) ? trim($_POST['priority']) : 'medium';
        $next_follow_up = !empty($_POST['next_follow_up']) ? $_POST['next_follow_up'] : null;
        $status = trim($_POST['status']);
        $source = trim($_POST['source']);
        
        // Start transaction
        $conn->begin_transaction();
        
        // Update contact
        $updateQuery = "UPDATE contacts SET 
                        first_name = ?, last_name = ?, email = ?, phone = ?, 
                        alternate_phone = ?, linkedin_url = ?,
                        status = ?, source = ?, source_details = ?,
                        assigned_to = ?,
                        skills = ?, interested_roles = ?, experience_years = ?,
                        current_company = ?, current_title = ?, notice_period = ?,
                        current_location = ?, preferred_locations = ?,
                        current_salary = ?, expected_salary = ?,
                        priority = ?, next_follow_up = ?,
                        updated_at = NOW()
                       WHERE contact_id = ?";
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param(
            "sssssssssisdsssssddssi",
            $first_name, $last_name, $email, $phone,
            $alternate_phone, $linkedin_url,
            $status, $source, $source_details,
            $assigned_to,
            $skills, $interested_roles, $experience_years,
            $current_company, $current_title, $notice_period,
            $current_location, $preferred_locations,
            $current_salary, $expected_salary,
            $priority, $next_follow_up,
            $contactId
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update contact: ' . $stmt->error);
        }
        
        // Update tags
        // First, remove existing tags
        $deleteTagsQuery = "DELETE FROM contact_tag_map WHERE contact_id = ?";
        $stmt = $conn->prepare($deleteTagsQuery);
        $stmt->bind_param("i", $contactId);
        $stmt->execute();
        
        // Then, insert new tags if provided
        if (!empty($_POST['tags']) && is_array($_POST['tags'])) {
            $tagStmt = $conn->prepare("INSERT INTO contact_tag_map (contact_id, tag_id) VALUES (?, ?)");
            foreach ($_POST['tags'] as $tagId) {
                $tagId = intval($tagId);
                $tagStmt->bind_param("ii", $contactId, $tagId);
                $tagStmt->execute();
            }
        }
        
        // Log activity
        $activityStmt = $conn->prepare("
            INSERT INTO contact_activity_log (contact_id, activity_type, activity_description, created_by)
            VALUES (?, 'updated', 'Contact information updated', ?)
        ");
        $activityStmt->bind_param("ii", $contactId, $userId);
        $activityStmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'contact_id' => $contactId,
            'message' => 'Contact updated successfully'
        ]);
    }
    
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
