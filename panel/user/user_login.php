<?php
/**
 * USER LOGIN HANDLER
 * Redirects users to appropriate dashboard based on their role
 */

session_start();

// Get session token
$session_token = $_GET['ss_id'] ?? $_SESSION['payroll_token'] ?? '';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_level'])) {
    // Not logged in, redirect to login page
    header('Location: ../login.php');
    exit;
}

// Get user information
$user_level = $_SESSION['user_level'];
$user_name = $_SESSION['user_name'] ?? 'User';
$user_code = $_SESSION['user_code'] ?? '';

// Update last login time in database
try {
    require_once __DIR__ . '/../config/database.php';
    $conn = getDBConnection();
    
    $update_query = "UPDATE user SET last_login = NOW() WHERE user_code = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, 's', $user_code);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    $conn->close();
} catch (Exception $e) {
    error_log('Error updating last login: ' . $e->getMessage());
}

// Redirect based on user level
switch ($user_level) {
    case 'admin':
        // Admin users go to admin dashboard
        header('Location: dashboard.php');
        break;
        
    case 'user':
    case 'recruiter':
    case 'hr':
        // Regular users/recruiters go to user dashboard
        header('Location: user/dashboard.php');
        break;
        
    case 'viewer':
        // Viewers have read-only access
        header('Location: user/dashboard.php?readonly=1');
        break;
        
    default:
        // Unknown role, log out for security
        session_destroy();
        header('Location: ../login.php?error=invalid_role');
        break;
}

exit;
?>
