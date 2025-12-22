<?php
/**
 * User Save Handler - Create and Update
 * File: panel/modules/users/handlers/user_save_handler.php
 */

require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';

header('Content-Type: application/json');

// Check authentication and admin access
if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (Auth::user()['level'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Only administrators can manage users']);
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $current_user = Auth::user();
    
    // Verify token
    if (!isset($_POST['token']) || $_POST['token'] !== Auth::token()) {
        throw new Exception('Invalid security token');
    }
    
    $action = $_POST['action'] ?? 'create';
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    
    // Common validation
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $level = $_POST['level'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate required fields
    if (empty($name)) {
        throw new Exception('Name is required');
    }
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    if (!in_array($level, ['admin', 'user'])) {
        throw new Exception('Invalid role selected');
    }
    
    if (!in_array($status, ['active', 'inactive'])) {
        $status = 'active';
    }
    
    if ($action === 'create') {
        // Additional validation for create
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($password)) {
            throw new Exception('Password is required');
        }
        
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }
        
        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match');
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Email address already exists');
        }
        
        // Generate unique user code
        $user_code = 'USR-' . date('Ymd-His');
        
        // Check for duplicates
        $stmt = $conn->prepare("SELECT user_code FROM user WHERE user_code = ?");
        $stmt->bind_param('s', $user_code);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $user_code .= '-' . rand(100, 999);
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert user
            $stmt = $conn->prepare("
                INSERT INTO user (
                    user_code,
                    name,
                    email,
                    password,
                    level,
                    status,
                    phone,
                    notes,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param(
                'ssssssss',
                $user_code,
                $name,
                $email,
                $password_hash,
                $level,
                $status,
                $phone,
                $notes
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create user: ' . $stmt->error);
            }
            
            $new_user_id = $conn->insert_id;
            
            // Log activity
            $stmt = $conn->prepare("
                INSERT INTO activity_log (user_code, action, entity_type, entity_id, description)
                VALUES (?, 'create_user', 'user', ?, ?)
            ");
            
            $description = "Created user: {$name} ({$user_code})";
            $stmt->bind_param('sis', $current_user['user_code'], $new_user_id, $description);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'User created successfully',
                'user_id' => $new_user_id,
                'user_code' => $user_code
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } elseif ($action === 'update') {
        if (!$user_id) {
            throw new Exception('User ID is required for update');
        }
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT user_code FROM user WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('User not found');
        }
        
        $existing_user = $result->fetch_assoc();
        
        // Check if email already exists (excluding current user)
        $stmt = $conn->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
        $stmt->bind_param('si', $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Email address already exists');
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Check if password is being updated
            $password = $_POST['password'] ?? '';
            
            if (!empty($password)) {
                // Password is being changed
                if (strlen($password) < 8) {
                    throw new Exception('Password must be at least 8 characters');
                }
                
                $confirm_password = $_POST['confirm_password'] ?? '';
                if ($password !== $confirm_password) {
                    throw new Exception('Passwords do not match');
                }
                
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("
                    UPDATE user 
                    SET name = ?,
                        email = ?,
                        password = ?,
                        level = ?,
                        status = ?,
                        phone = ?,
                        notes = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->bind_param(
                    'sssssssi',
                    $name,
                    $email,
                    $password_hash,
                    $level,
                    $status,
                    $phone,
                    $notes,
                    $user_id
                );
            } else {
                // Password not being changed
                $stmt = $conn->prepare("
                    UPDATE user 
                    SET name = ?,
                        email = ?,
                        level = ?,
                        status = ?,
                        phone = ?,
                        notes = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->bind_param(
                    'ssssssi',
                    $name,
                    $email,
                    $level,
                    $status,
                    $phone,
                    $notes,
                    $user_id
                );
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update user: ' . $stmt->error);
            }
            
            // Log activity
            $stmt = $conn->prepare("
                INSERT INTO activity_log (user_code, action, entity_type, entity_id, description)
                VALUES (?, 'update_user', 'user', ?, ?)
            ");
            
            $description = "Updated user: {$name}";
            $stmt->bind_param('sis', $current_user['user_code'], $user_id, $description);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'User updated successfully',
                'user_id' => $user_id
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log('User save error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>