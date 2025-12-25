<?php
/**
 * Candidate Save Handler - Create and Update
 */
require_once __DIR__ . '/../../_common.php';

$conn = Database::getInstance()->getConnection();
$user = Auth::user();

// Verify token
if (!Auth::verifyToken($_POST['token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit();
}

function validateCandidate($data) {
    $errors = [];
    
    // Required fields validation
    $requiredFields = [
        'candidate_name' => 'Candidate name is required',
        'email_id' => 'Email address is required',
        'current_location' => 'Current location is required',
        'work_auth_status' => 'Work authorization status is required',
        'candidate_status' => 'Candidate status is required',
        'lead_type' => 'Lead type is required',
        'lead_type_role' => 'Lead role type is required',
        'source' => 'Source is required',
        'skill_set' => 'At least one skill is required'
    ];
    
    foreach ($requiredFields as $field => $message) {
        if ($field === 'skill_set') {
            if (empty($_POST['skill_set']) || !is_array($_POST['skill_set']) || count($_POST['skill_set']) === 0) {
                $errors[] = $message;
            }
        } else if (empty(trim($data[$field] ?? ''))) {
            $errors[] = $message;
        }
    }
    
    // Email validation
    if (!empty($data['email_id']) && !filter_var($data['email_id'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Phone validation
    if (!empty($data['contact_details']) && !preg_match('/^[0-9+\-\s()]{7,20}$/', $data['contact_details'])) {
        $errors[] = 'Phone number appears invalid';
    }
    
    return $errors;
}

function uploadFile($file, $can_code, $prefix) {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;

    $allowedMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png'
    ];
    $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    if ($file['size'] > $maxFileSize) {
        throw new Exception('File size exceeds 5MB limit');
    }

    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Invalid file type: ' . $fileExtension);
    }

    // Generate unique filename
    $fileName = $prefix . '_' . $can_code . '_' . uniqid() . '.' . $fileExtension;
    $uploadDir = '../uploads/candidates/';
    $filePath = $uploadDir . $fileName;
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return 'uploads/candidates/' . $fileName;
    }
    
    throw new Exception('Failed to move uploaded file');
}

function logEditHistory($conn, $can_code, $field, $oldValue, $newValue, $editedBy) {
    if ($oldValue === $newValue) return;
    
    $stmt = $conn->prepare("
        INSERT INTO candidates_edit_info 
        (can_code, edited_field, old_value, new_value, edited_by, edited_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssssss", $can_code, $field, $oldValue, $newValue, $editedBy, $editedBy);
    $stmt->execute();
}

try {
    $conn->begin_transaction();
    
    // Validate input
    $errors = validateCandidate($_POST);
    if ($errors) {
        throw new Exception(implode(', ', $errors));
    }
    
    // Prepare data
    $can_code = $_POST['can_code'];
    $candidate_rating = $_POST['candidate_rating'] ?? 3;
    $skill_set = !empty($_POST['skill_set']) ? implode(',', $_POST['skill_set']) : null;
    $languages = !empty($_POST['languages']) ? implode(',', $_POST['languages']) : null;
    $certifications = !empty($_POST['certifications']) ? implode(',', $_POST['certifications']) : null;
    $updated_by = $_POST['updated_by'] ?? $user['user_code'];
    
    // Get current candidate data for history logging
    $currentData = [];
    if ($_POST['action'] === 'update') {
        $stmt = $conn->prepare("SELECT * FROM candidates WHERE can_code = ?");
        $stmt->bind_param("s", $can_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentData = $result->fetch_assoc();
    }
    
    // Prepare and execute update
    $stmt = $conn->prepare("
        UPDATE candidates
        SET candidate_name = ?, email_id = ?, contact_details = ?, alternate_contact_details = ?,
            linkedin = ?, alternate_email_id = ?, current_position = ?, current_employer = ?,
            experience = ?, professional_summary = ?, skill_set = ?, current_location = ?,
            preferred_location = ?, work_auth_status = ?, willing_to_relocate = ?, notice_period = ?,
            can_join = ?, current_working_status = ?, compensation_type = ?, current_salary = ?,
            expected_salary = ?, current_daily_rate = ?, expected_daily_rate = ?, candidate_status = ?,
            lead_type = ?, lead_type_role = ?, source = ?, candidate_rating = ?, role_addressed = ?,
            follow_up = ?, follow_up_date = ?, face_to_face = ?, extra_details = ?, assigned_to = ?,
            languages = ?, certifications = ?, availability = ?, updated_by = ?, updated_at = NOW()
        WHERE can_code = ?
    ");
    
    $null = null; // For binding null values
    $stmt->bind_param(
        "sssssssssdsssssisssdssdssssssssssssssssss",
        $_POST['candidate_name'],
        $_POST['email_id'],
        $_POST['contact_details'] ?? $null,
        $_POST['alternate_contact_details'] ?? $null,
        $_POST['linkedin'] ?? $null,
        $_POST['alternate_email_id'] ?? $null,
        $_POST['current_position'] ?? $null,
        $_POST['current_employer'] ?? $null,
        $_POST['experience'] ?? $null,
        $_POST['professional_summary'] ?? $null,
        $skill_set,
        $_POST['current_location'],
        $_POST['preferred_location'] ?? $null,
        $_POST['work_auth_status'],
        $_POST['willing_to_relocate'] ?? 0,
        $_POST['notice_period'] ?? $null,
        $_POST['can_join'] ?? $null,
        $_POST['current_working_status'] ?? $null,
        $_POST['compensation_type'] ?? 'salary',
        $_POST['current_salary'] ?? $null,
        $_POST['expected_salary'] ?? $null,
        $_POST['current_daily_rate'] ?? $null,
        $_POST['expected_daily_rate'] ?? $null,
        $_POST['candidate_status'],
        $_POST['lead_type'],
        $_POST['lead_type_role'],
        $_POST['source'],
        $candidate_rating,
        $_POST['role_addressed'] ?? $null,
        $_POST['follow_up'] ?? 'Not Done',
        $_POST['follow_up_date'] ?? $null,
        $_POST['face_to_face'] ?? $null,
        $_POST['extra_details'] ?? $null,
        $_POST['assigned_to'] ?? $null,
        $languages,
        $certifications,
        $_POST['availability'] ?? 'immediate',
        $updated_by,
        $can_code
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }
    
    // Log changes to edit history
    $fieldsToTrack = [
        'candidate_name', 'email_id', 'current_position', 'current_employer', 
        'experience', 'skill_set', 'current_location', 'candidate_status',
        'lead_type', 'expected_salary', 'expected_daily_rate'
    ];
    
    foreach ($fieldsToTrack as $field) {
        $oldValue = $currentData[$field] ?? '';
        $newValue = $_POST[$field] ?? '';
        
        if (is_array($newValue)) {
            $newValue = implode(',', $newValue);
        }
        
        if ($oldValue != $newValue) {
            logEditHistory($conn, $can_code, $field, $oldValue, $newValue, $updated_by);
        }
    }
    
    // Handle file uploads
    $fileMappings = [
        'candidate_cv' => 'candidate_cv',
        'consultancy_cv' => 'consultancy_cv',
        'consent' => 'consent'
    ];
    
    foreach ($fileMappings as $field => $column) {
        if (!empty($_FILES[$field]['name'])) {
            try {
                $filePath = uploadFile($_FILES[$field], $can_code, $field);
                if ($filePath) {
                    // Log file change
                    $oldValue = $currentData[$column] ?? '';
                    logEditHistory($conn, $can_code, ucfirst(str_replace('_', ' ', $column)), $oldValue, $filePath, $updated_by);
                    
                    $updateStmt = $conn->prepare("UPDATE candidates SET $column = ? WHERE can_code = ?");
                    $updateStmt->bind_param("ss", $filePath, $can_code);
                    $updateStmt->execute();
                }
            } catch (Exception $e) {
                error_log("File upload error for $field: " . $e->getMessage());
                // Don't fail the whole transaction for file errors
            }
        }
    }
    
    // Handle additional documents
    if (!empty($_FILES['additional_docs']['name'][0])) {
        foreach ($_FILES['additional_docs']['name'] as $key => $name) {
            if ($_FILES['additional_docs']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $name,
                    'type' => $_FILES['additional_docs']['type'][$key],
                    'tmp_name' => $_FILES['additional_docs']['tmp_name'][$key],
                    'error' => $_FILES['additional_docs']['error'][$key],
                    'size' => $_FILES['additional_docs']['size'][$key]
                ];
                
                try {
                    $filePath = uploadFile($file, $can_code, 'additional');
                    if ($filePath) {
                        $docStmt = $conn->prepare("
                            INSERT INTO candidate_documents (candidate_code, file_path, uploaded_by) 
                            VALUES (?, ?, ?)
                        ");
                        $docStmt->bind_param("sss", $can_code, $filePath, $updated_by);
                        $docStmt->execute();
                    }
                } catch (Exception $e) {
                    error_log("Additional document upload error: " . $e->getMessage());
                }
            }
        }
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'can_code' => $can_code]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Candidate update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>