<?php
/**
 * Forgot Password Handler
 * Generates a secure token and sends password reset email
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Mailer.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Email address is required'
        ]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email address'
        ]);
        exit;
    }

    // Connect to database
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if user exists
    $stmt = $conn->prepare("SELECT user_code, name, email FROM user WHERE email = ? AND active = '1'");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        // For security, don't reveal if email exists or not
        echo json_encode([
            'status' => 'success',
            'message' => 'If your email is registered, you will receive a password reset link shortly.'
        ]);
        exit;
    }

    // Generate secure token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Store token in database
    $stmt = $conn->prepare("
        INSERT INTO password_resets (user_code, email, token, expires_at, ip_address) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('sssss', $user['user_code'], $email, $token, $expiresAt, $ipAddress);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to generate reset token');
    }

    // Generate reset link
    $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
                 . "://" . $_SERVER['HTTP_HOST'] 
                 . dirname($_SERVER['PHP_SELF']) 
                 . "/reset-password.php?token=" . $token;

    // Send email
    $mailer = new Mailer();
    $subject = 'Password Reset Request - ' . COMPANY_NAME;
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>" . COMPANY_NAME . "</h1>
                <p>Password Reset Request</p>
            </div>
            <div class='content'>
                <p>Dear " . htmlspecialchars($user['name']) . ",</p>
                
                <p>We received a request to reset your password. Click the button below to create a new password:</p>
                
                <div style='text-align: center;'>
                    <a href='" . $resetLink . "' class='button'>Reset Password</a>
                </div>
                
                <p>Or copy and paste this link into your browser:</p>
                <p style='word-break: break-all; background: white; padding: 10px; border-radius: 5px;'>" . $resetLink . "</p>
                
                <div class='warning'>
                    <strong>⚠️ Important:</strong>
                    <ul>
                        <li>This link will expire in 1 hour</li>
                        <li>If you didn't request this, please ignore this email</li>
                        <li>Your password won't change until you create a new one</li>
                    </ul>
                </div>
                
                <p>For security reasons, this link can only be used once.</p>
                
                <p>Best regards,<br>
                <strong>" . COMPANY_NAME . " Team</strong></p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . COMPANY_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $emailSent = $mailer->send($email, $user['name'], $subject, $message);

    if ($emailSent) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Password reset link has been sent to your email address. Please check your inbox.'
        ]);
    } else {
        throw new Exception('Failed to send email');
    }

} catch (Exception $e) {
    error_log('Forgot password error: ' . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while processing your request. Please try again later.'
    ]);
}
?>
