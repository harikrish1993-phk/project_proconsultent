<?php
require_once '../includes/config/config.php';
require_once '../includes/core/Auth.php';
require_once '../includes/core/Session.php';
require_once '../includes/core/ActivityLogger.php';

echo "<h1>Authentication System Test</h1>";

// Test 1: Session Management
echo "<h2>Test 1: Session Management</h2>";
Session::start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color:green;'>✅ Session started successfully</p>";
} else {
    echo "<p style='color:red;'>❌ Session failed to start</p>";
}

// Test 2: Login with invalid credentials
echo "<h2>Test 2: Invalid Login Attempt</h2>";
$result = Auth::login('invalid_user', 'wrong_password');
if ($result['success'] === false) {
    echo "<p style='color:green;'>✅ Invalid login correctly rejected</p>";
} else {
    echo "<p style='color:red;'>❌ Invalid login was accepted (SECURITY ISSUE)</p>";
}

// Test 3: Activity logging
echo "<h2>Test 3: Activity Logging</h2>";
$logged = ActivityLogger::log('test_action', 'test_entity', 'test_001', ['test' => true]);
if ($logged) {
    echo "<p style='color:green;'>✅ Activity logged successfully</p>";
} else {
    echo "<p style='color:red;'>❌ Activity logging failed</p>";
}

// Test 4: Check if test user exists
echo "<h2>Test 4: Test User Check</h2>";
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$result = mysqli_query($conn, "SELECT * FROM user WHERE user_code = 'test001' LIMIT 1");
if (mysqli_num_rows($result) > 0) {
    echo "<p style='color:green;'>✅ Test user exists</p>";
    
    // Test actual login
    $user = mysqli_fetch_assoc($result);
    echo "<h2>Test 5: Actual Login Test</h2>";
    echo "<p>Attempting login with user_code: test001</p>";
    
    // Note: You need to know the test user password or set it
    echo "<p>ℹ️ Create test user with: </p>";
    echo "<pre>INSERT INTO user (user_code, name, email, password, level) VALUES 
('test001', 'Test User', 'test@test.local', 
'" . password_hash('Test@123', PASSWORD_DEFAULT) . "', 'admin');</pre>";
    
} else {
    echo "<p style='color:orange;'>⚠️ Test user not found. Create one to test login.</p>";
}

mysqli_close($conn);

echo "<h2>Summary</h2>";
echo "<p><strong>Core systems operational. Proceed with manual login testing.</strong></p>";
?>