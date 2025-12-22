<?php
/**
 * Client Save Handler - Create and Update
 * File: panel/modules/clients/handlers/client_save_handler.php
 */

require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';

// Set JSON header
header('Content-Type: application/json');

// Check authentication
if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $user = Auth::user();
    
    // Verify token
    if (!isset($_POST['token']) || $_POST['token'] !== Auth::token()) {
        throw new Exception('Invalid security token');
    }
    
    // Get action
    $action = $_POST['action'] ?? 'create';
    $client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
    
    // Validate required fields
    $client_name = trim($_POST['client_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($client_name)) {
        throw new Exception('Client name is required');
    }
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Get optional fields
    $company_name = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    // Validate status
    if (!in_array($status, ['active', 'inactive'])) {
        $status = 'active';
    }
    
    if ($action === 'create') {
        // Check for duplicate email
        $stmt = $conn->prepare("SELECT client_id FROM clients WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('A client with this email already exists');
        }
        
        // Insert new client
        $stmt = $conn->prepare("
            INSERT INTO clients (
                client_name, company_name, contact_person, email, phone,
                address, city, country, notes, status, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param(
            'sssssssssss',
            $client_name,
            $company_name,
            $contact_person,
            $email,
            $phone,
            $address,
            $city,
            $country,
            $notes,
            $status,
            $user['user_code']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create client: ' . $stmt->error);
        }
        
        $client_id = $conn->insert_id;
        
        // Log activity
        $activity_stmt = $conn->prepare("
            INSERT INTO activity_log (user_code, action, entity_type, entity_id, description)
            VALUES (?, 'create_client', 'client', ?, ?)
        ");
        
        $description = "Created client: {$client_name}";
        $activity_stmt->bind_param('sis', $user['user_code'], $client_id, $description);
        $activity_stmt->execute();
        
        // Send email notification (optional)
        if (defined('ENABLE_EMAIL_NOTIFICATIONS') && ENABLE_EMAIL_NOTIFICATIONS) {
            try {
                $mailer = new \Core\Mailer();
                $mailer->sendSimpleEmail(
                    $user['email'],
                    $user['name'],
                    'New Client Created',
                    "<p>Client <strong>{$client_name}</strong> has been created successfully.</p>" .
                    "<p><a href='" . BASE_URL . "/panel/modules/clients/?action=view&id={$client_id}'>View Client</a></p>"
                );
            } catch (Exception $e) {
                // Log but don't fail
                error_log('Email notification failed: ' . $e->getMessage());
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Client created successfully',
            'client_id' => $client_id
        ]);
        
    } elseif ($action === 'update') {
        if (!$client_id) {
            throw new Exception('Client ID is required for update');
        }
        
        // Check if client exists
        $stmt = $conn->prepare("SELECT client_id FROM clients WHERE client_id = ?");
        $stmt->bind_param('i', $client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Client not found');
        }
        
        // Check for duplicate email (excluding current client)
        $stmt = $conn->prepare("SELECT client_id FROM clients WHERE email = ? AND client_id != ?");
        $stmt->bind_param('si', $email, $client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('Another client with this email already exists');
        }
        
        // Update client
        $stmt = $conn->prepare("
            UPDATE clients SET
                client_name = ?,
                company_name = ?,
                contact_person = ?,
                email = ?,
                phone = ?,
                address = ?,
                city = ?,
                country = ?,
                notes = ?,
                status = ?,
                updated_at = NOW()
            WHERE client_id = ?
        ");
        
        $stmt->bind_param(
            'ssssssssssi',
            $client_name,
            $company_name,
            $contact_person,
            $email,
            $phone,
            $address,
            $city,
            $country,
            $notes,
            $status,
            $client_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update client: ' . $stmt->error);
        }
        
        // Log activity
        $activity_stmt = $conn->prepare("
            INSERT INTO activity_log (user_code, action, entity_type, entity_id, description)
            VALUES (?, 'update_client', 'client', ?, ?)
        ");
        
        $description = "Updated client: {$client_name}";
        $activity_stmt->bind_param('sis', $user['user_code'], $client_id, $description);
        $activity_stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Client updated successfully',
            'client_id' => $client_id
        ]);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log('Client save error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>