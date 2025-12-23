<?php
/**
 * CV to Candidate Conversion Handler
 * File: panel/modules/jobs/cv/handlers/convert_handler.php
 * 
 * This creates:
 * 1. Candidate record
 * 2. Application record
 * 3. Updates CV status to 'converted'
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
    
    // Get form data
    $cv_id = (int)$_POST['cv_id'];
    $job_id = (int)$_POST['job_id'];
    $can_code = trim($_POST['can_code']);
    $candidate_name = trim($_POST['candidate_name']);
    $email_id = trim($_POST['email_id']);
    $phone = trim($_POST['phone'] ?? '');
    $experience_years = isset($_POST['experience_years']) && $_POST['experience_years'] !== '' ? (int)$_POST['experience_years'] : null;
    $current_location = trim($_POST['current_location'] ?? '');
    $expected_salary = isset($_POST['expected_salary']) && $_POST['expected_salary'] !== '' ? (float)$_POST['expected_salary'] : null;
    $availability = trim($_POST['availability'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $application_status = $_POST['application_status'] ?? 'screening';
    $send_email = isset($_POST['send_welcome_email']) && $_POST['send_welcome_email'] === '1';
    
    // Validate required fields
    if (!$cv_id || !$job_id) {
        throw new Exception('CV ID and Job ID are required');
    }
    
    if (empty($can_code) || empty($candidate_name) || empty($email_id)) {
        throw new Exception('Candidate code, name, and email are required');
    }
    
    // Validate email
    if (!filter_var($email_id, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Get CV details
    $stmt = $conn->prepare("SELECT * FROM cv_inbox WHERE id = ?");
    $stmt->bind_param('i', $cv_id);
    $stmt->execute();
    $cv = $stmt->get_result()->fetch_assoc();
    
    if (!$cv) {
        throw new Exception('CV not found');
    }
    
    // Check if already converted
    if ($cv['status'] === 'converted') {
        throw new Exception('This CV has already been converted');
    }
    
    // Check if candidate code already exists
    $stmt = $conn->prepare("SELECT can_code FROM candidates WHERE can_code = ?");
    $stmt->bind_param('s', $can_code);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        // Generate new unique code
        $can_code = 'CAN-' . date('Ymd-His') . '-' . rand(100, 999);
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // STEP 1: Create Candidate
        $stmt = $conn->prepare("
            INSERT INTO candidates (
                can_code,
                candidate_name,
                email_id,
                phone,
                contact_number,
                experience_years,
                current_location,
                expected_salary,
                availability,
                skills,
                cv_source,
                status,
                created_by,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'website', 'active', ?, NOW())
        ");
        
        $stmt->bind_param(
            'sssssisdss',
            $can_code,
            $candidate_name,
            $email_id,
            $phone,
            $phone,
            $experience_years,
            $current_location,
            $expected_salary,
            $availability,
            $skills,
            $user['user_code']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create candidate: ' . $stmt->error);
        }
        
        // STEP 2: Copy CV file to candidate documents (if path exists)
        if ($cv['cv_path']) {
            // Store original CV path in candidate_documents or a similar table
            // For now, we'll just reference it
            $stmt = $conn->prepare("
                INSERT INTO candidate_documents (can_code, document_type, file_path, uploaded_by, uploaded_at)
                VALUES (?, 'cv', ?, ?, NOW())
            ");
            
            $doc_type = 'cv';
            $stmt->bind_param('sss', $can_code, $cv['cv_path'], $user['user_code']);
            $stmt->execute(); // Don't fail if this table doesn't exist
        }
        
        // STEP 3: Create Application
        $stmt = $conn->prepare("
            INSERT INTO job_applications (
                can_code,
                job_id,
                cv_inbox_id,
                status,
                application_source,
                created_by,
                created_at
            ) VALUES (?, ?, ?, ?, 'cv_inbox', ?, NOW())
        ");
        
        $stmt->bind_param('siiss', $can_code, $job_id, $cv_id, $application_status, $user['user_code']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create application: ' . $stmt->error);
        }
        
        $application_id = $conn->insert_id;
        
        // STEP 4: Add initial note if provided
        if (!empty($notes)) {
            $stmt = $conn->prepare("
                INSERT INTO candidate_notes (can_code, note_type, note, created_by, created_at)
                VALUES (?, 'screening', ?, ?, NOW())
            ");
            
            $note_type = 'screening';
            $stmt->bind_param('sss', $can_code, $notes, $user['user_code']);
            $stmt->execute(); // Don't fail if table doesn't exist
        }
        
        // STEP 5: Update CV status
        $stmt = $conn->prepare("
            UPDATE cv_inbox 
            SET status = 'converted',
                converted_to_candidate = ?,
                converted_by = ?,
                converted_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param('ssi', $can_code, $user['user_code'], $cv_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update CV status: ' . $stmt->error);
        }
        
        // STEP 6: Log activity
        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_code, action, entity_type, entity_id, description)
            VALUES (?, 'convert_cv', 'candidate', ?, ?)
        ");
        
        $description = "Converted CV to candidate: {$candidate_name} (Application #$application_id)";
        $activity_stmt = $conn->prepare("
            INSERT INTO activity_log (user_code, action, entity_type, entity_id, description)
            VALUES (?, 'convert_cv', 'candidate', 0, ?)
        ");
        $activity_stmt->bind_param('ss', $user['user_code'], $description);
        $activity_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // STEP 7: Send welcome email (optional)
        if ($send_email && defined('ENABLE_EMAIL_NOTIFICATIONS') && ENABLE_EMAIL_NOTIFICATIONS) {
            try {
                $mailer = new \Core\Mailer();
                
                $mailer->sendSimpleEmail(
                    $email_id,
                    $candidate_name,
                    'Welcome - Your Application Received',
                    "<h3>Thank you for your application!</h3>
                    <p>Dear {$candidate_name},</p>
                    <p>We have received your application and are currently reviewing it. 
                    We will be in touch if your profile matches our requirements.</p>
                    <p>Best regards,<br>Recruitment Team</p>"
                );
            } catch (Exception $e) {
                error_log('Welcome email failed: ' . $e->getMessage());
                // Don't fail the conversion if email fails
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Candidate created and application submitted successfully!',
            'can_code' => $can_code,
            'application_id' => $application_id
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('CV conversion error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>