<?php
/**
 * Job Save Handler - Create and Update
 * File: panel/modules/jobs/handlers/job_save_handler.php
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
    
    $action = $_POST['action'] ?? 'create';
    $job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
    
    // Validate required fields
    $client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
    $job_title = trim($_POST['job_title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (!$client_id) {
        throw new Exception('Please select a client');
    }
    
    if (empty($job_title)) {
        throw new Exception('Job title is required');
    }
    
    if (empty($description)) {
        throw new Exception('Job description is required');
    }
    
    // Get optional fields
    $job_code = trim($_POST['job_code'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $daily_rate = isset($_POST['daily_rate']) && $_POST['daily_rate'] !== '' ? (float)$_POST['daily_rate'] : null;
    $experience_required = trim($_POST['experience_required'] ?? '');
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $closing_date = !empty($_POST['closing_date']) ? $_POST['closing_date'] : null;
    $priority = $_POST['priority'] ?? 'medium';
    $job_source = trim($_POST['job_source'] ?? '');
    $internal_notes = trim($_POST['internal_notes'] ?? '');
    
    // Fixed values
    $location = 'Belgium';
    $employment_type = 'freelance';
    
    // Validate priority
    if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
        $priority = 'medium';
    }
    
    // Auto-approve if user is admin and requested
    $auto_approve = isset($_POST['auto_approve']) && $_POST['auto_approve'] === '1' && $user['level'] === 'admin';
    $job_status = $auto_approve ? 'active' : 'pending';
    
    if ($action === 'create') {
        // Generate job code if not provided
        if (empty($job_code)) {
            $job_code = 'JOB-' . date('Ymd-His');
        }
        
        // Check for duplicate job code
        $stmt = $conn->prepare("SELECT job_id FROM jobs WHERE job_code = ?");
        $stmt->bind_param('s', $job_code);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $job_code = 'JOB-' . date('Ymd-His') . '-' . rand(100, 999);
        }
        
        // Insert new job
        $stmt = $conn->prepare("
            INSERT INTO jobs (
                client_id,
                job_code,
                job_title,
                description,
                requirements,
                salary_min,
                location,
                employment_type,
                experience_required,
                start_date,
                closing_date,
                priority,
                job_source,
                internal_notes,
                job_status,
                created_by,
                created_at,
                posted_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), CURDATE())
        ");
        
        $stmt->bind_param(
            'issssdsssssssss',
            $client_id,
            $job_code,
            $job_title,
            $description,
            $requirements,
            $daily_rate,
            $location,
            $employment_type,
            $experience_required,
            $start_date,
            $closing_date,
            $priority,
            $job_source,
            $internal_notes,
            $job_status,
            $user['user_code']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create job: ' . $stmt->error);
        }
        
        $job_id = $conn->insert_id;
        $is_public = isset($_POST['is_public']) ? 1 : 0;

        $stmt = $conn->prepare("
            INSERT INTO jobs (..., is_public) 
            VALUES (..., ?)
        ");
        // If auto-approved, set approval fields
        if ($auto_approve) {
            $stmt = $conn->prepare("
                UPDATE jobs 
                SET approved_by = ?,
                    approved_at = NOW()
                WHERE job_id = ?
            ");
            $stmt->bind_param('si', $user['user_code'], $job_id);
            $stmt->execute();
        }
        
        // Log activity
        $activity_stmt = $conn->prepare("
            INSERT INTO activity_log (user_code, action, entity_type, entity_id, description)
            VALUES (?, 'create_job', 'job', ?, ?)
        ");
        
        $description_log = "Created job: {$job_title}" . ($auto_approve ? " (auto-approved)" : "");
        $activity_stmt->bind_param('sis', $user['user_code'], $job_id, $description_log);
        $activity_stmt->execute();
        
        // Send email notification (optional)
        if (defined('ENABLE_EMAIL_NOTIFICATIONS') && ENABLE_EMAIL_NOTIFICATIONS) {
            try {
                $mailer = new \Core\Mailer();
                
                // Get client name
                $client_stmt = $conn->prepare("SELECT client_name FROM clients WHERE client_id = ?");
                $client_stmt->bind_param('i', $client_id);
                $client_stmt->execute();
                $client = $client_stmt->get_result()->fetch_assoc();
                
                // Notify admin if job needs approval
                if (!$auto_approve && defined('ADMIN_EMAIL')) {
                    $mailer->sendSimpleEmail(
                        ADMIN_EMAIL,
                        'Admin',
                        'New Job Pending Approval',
                        "<h3>New Job Requires Approval</h3>
                        <p><strong>Job:</strong> {$job_title}</p>
                        <p><strong>Client:</strong> {$client['client_name']}</p>
                        <p><strong>Created by:</strong> {$user['name']}</p>
                        <p><a href='" . BASE_URL . "/panel/modules/jobs/?action=approve&id={$job_id}'>Approve Job</a></p>"
                    );
                }
            } catch (Exception $e) {
                error_log('Email notification failed: ' . $e->getMessage());
            }
        }
        
        $message = $auto_approve ? 
            'Job created and approved successfully!' : 
            'Job created successfully! Waiting for admin approval.';
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'job_id' => $job_id
        ]);
        
    } elseif ($action === 'update') {
        if (!$job_id) {
            throw new Exception('Job ID is required for update');
        }
        $is_public = isset($_POST['is_public']) ? 1 : 0;

        $stmt = $conn->prepare("
            INSERT INTO jobs (..., is_public) 
            VALUES (..., ?)
        ");
        // Check if job exists
        $stmt = $conn->prepare("SELECT job_id, job_status FROM jobs WHERE job_id = ?");
        $stmt->bind_param('i', $job_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Job not found');
        }
        
        $existing_job = $result->fetch_assoc();
        
        // Don't allow editing approved jobs unless admin
        if ($existing_job['job_status'] === 'active' && $user['level'] !== 'admin') {
            throw new Exception('Only admins can edit approved jobs');
        }
        
        // Update job
        $stmt = $conn->prepare("
            UPDATE jobs SET
                client_id = ?,
                job_title = ?,
                description = ?,
                requirements = ?,
                salary_min = ?,
                location = ?,
                employment_type = ?,
                experience_required = ?,
                start_date = ?,
                closing_date = ?,
                priority = ?,
                job_source = ?,
                internal_notes = ?,
                updated_at = NOW()
            WHERE job_id = ?
        ");
        
        $stmt->bind_param(
            'isssdssssssssi',
            $client_id,
            $job_title,
            $description,
            $requirements,
            $daily_rate,
            $location,
            $employment_type,
            $experience_required,
            $start_date,
            $closing_date,
            $priority,
            $job_source,
            $internal_notes,
            $job_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update job: ' . $stmt->error);
        }
        
        // Log activity
        $activity_stmt = $conn->prepare("
            INSERT INTO activity_log (user_code, action, entity_type, entity_id, description)
            VALUES (?, 'update_job', 'job', ?, ?)
        ");
        
        $description_log = "Updated job: {$job_title}";
        $activity_stmt->bind_param('sis', $user['user_code'], $job_id, $description_log);
        $activity_stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Job updated successfully',
            'job_id' => $job_id
        ]);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log('Job save error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>