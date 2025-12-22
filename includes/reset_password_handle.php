<?php
/**
 * Reset Password Handler
 * Validates token and updates user password
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($token) || empty($password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Token and password are required'
        ]);
        exit;
    }

    // Validate password strength
    if (strlen($password) < 8) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Password must be at least 8 characters long'
        ]);
        exit;
    }

    if (!preg_match('/[A-Z]/', $password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Password must contain at least one uppercase letter'
        ]);
        exit;
    }

    if (!preg_match('/[a-z]/', $password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Password must contain at least one lowercase letter'
        ]);
        exit;
    }

    if (!preg_match('/[0-9]/', $password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Password must contain at least one number'
        ]);
        exit;
    }

    // Connect to database
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Verify token
    $stmt = $conn->prepare("
        SELECT user_code, email, expires_at, used 
        FROM password_resets 
        WHERE token = ? 
        LIMIT 1
    ");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $reset = $result->fetch_assoc();

    if (!$reset) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or expired reset link'
        ]);
        exit;
    }

    if ($reset['used'] == 1) {
        echo json_encode([
            'status' => 'error',
            'message' => 'This reset link has already been used'
        ]);
        exit;
    }

    if (strtotime($reset['expires_at']) < time()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'This reset link has expired. Please request a new one.'
        ]);
        exit;
    }

    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update user password
        $stmt = $conn->prepare("UPDATE user SET password = ? WHERE user_code = ?");
        $stmt->bind_param('ss', $hashedPassword, $reset['user_code']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update password');
        }

        // Mark token as used
        $usedAt = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE password_resets SET used = 1, used_at = ? WHERE token = ?");
        $stmt->bind_param('ss', $usedAt, $token);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to mark token as used');
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Your password has been reset successfully. You can now login with your new password.'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Reset password error: ' . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while resetting your password. Please try again later.'
    ]);
}
?>
