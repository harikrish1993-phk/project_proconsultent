<?php
/**
 * LOGIN HANDLER
 */

// Enable ALL error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Auth.php';

// Set JSON header
header('Content-Type: application/json');

// Error log array
$debugLog = [];
$debugLog[] = "=== LOGIN HANDLER DEBUG ===";
$debugLog[] = "Time: " . date('Y-m-d H:i:s');

try {
    $debugLog[] = "Request method: " . $_SERVER['REQUEST_METHOD'];
    
    // Check if POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get POST data
    $debugLog[] = "POST data received";
    $debugLog[] = "POST keys: " . implode(', ', array_keys($_POST));
    
    $type = $_POST['type'] ?? '';
    $userCode = $_POST['user_code'] ?? '';
    $password = $_POST['password'] ?? '';
    $rememberMe = ($_POST['remember_me'] ?? '0') === '1';
    
    $debugLog[] = "Type: $type";
    $debugLog[] = "User code: $userCode";
    $debugLog[] = "Password length: " . strlen($password);
    $debugLog[] = "Remember me: " . ($rememberMe ? 'Yes' : 'No');
    
    if ($type !== 'user_login') {
        throw new Exception('Invalid request type: ' . $type);
    }
    
    // Validate input
    if (empty($userCode)) {
        $debugLog[] = "Error: User code is empty";
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'User code is required',
            'debug' => $debugLog
        ]);
        exit;
    }
    
    if (empty($password)) {
        $debugLog[] = "Error: Password is empty";
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'Password is required',
            'debug' => $debugLog
        ]);
        exit;
    }
    
    $debugLog[] = "Calling Auth::login()...";
    
    // Attempt login
    $result = Auth::login($userCode, $password, $rememberMe);
    
    $debugLog[] = "Auth::login() returned";
    $debugLog[] = "Result: " . json_encode($result);
    
    if ($result['success']) {
        $debugLog[] = "Login successful!";
        
        // Determine redirect based on user level
        $userLevel = $result['user']['level'] ?? 'user';
        $redirectUrl = match($userLevel) {
            'admin' => 'panel/admin.php',
            default => 'panel/recruiter.php'
        };
        
        $debugLog[] = "User level: $userLevel";
        $debugLog[] = "Redirect URL: $redirectUrl";
        
        // Clear output buffer
        ob_clean();
        
        // Return success
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'redirect' => $redirectUrl,
            'user' => $result['user'] ?? null,
            'debug' => $debugLog
        ]);
    } else {
        $debugLog[] = "Login failed: " . ($result['message'] ?? 'Unknown error');
        
        // Clear output buffer
        ob_clean();
        
        // Return error
        echo json_encode([
            'status' => 'error',
            'message' => $result['message'],
            'debug' => $debugLog
        ]);
    }
    
} catch (Exception $e) {
    $debugLog[] = "EXCEPTION: " . $e->getMessage();
    $debugLog[] = "File: " . $e->getFile();
    $debugLog[] = "Line: " . $e->getLine();
    $debugLog[] = "Trace: " . $e->getTraceAsString();
    
    // Clear output buffer
    ob_clean();
    
    // Log error
    error_log('Login error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Return detailed error for debugging
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred during login: ' . $e->getMessage(),
        'error_details' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ],
        'debug' => $debugLog
    ]);
}

// End output buffering
ob_end_flush();
?>