<?php
/**
 * LIVE LOGIN DEBUGGER
 * Test login process step-by-step with detailed output
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/config/config.php';

// Test credentials
$testUser = $_POST['test_user'] ?? 'pro/901';
$testPass = $_POST['test_pass'] ?? 'admin123';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Login Debugger</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #4ec9b0; margin-bottom: 20px; }
        .test-form { background: #2d2d2d; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .test-form input { padding: 10px; margin-right: 10px; background: #3c3c3c; border: 1px solid #555; color: #d4d4d4; border-radius: 4px; }
        .test-form button { padding: 10px 20px; background: #4ec9b0; color: #1e1e1e; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .step { background: #2d2d2d; padding: 15px; margin-bottom: 15px; border-left: 4px solid #4ec9b0; border-radius: 4px; }
        .step-title { color: #4ec9b0; font-weight: bold; margin-bottom: 10px; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .info { color: #569cd6; }
        pre { background: #1e1e1e; padding: 10px; border-radius: 4px; overflow-x: auto; margin-top: 10px; }
        .code { background: #3c3c3c; padding: 2px 6px; border-radius: 3px; color: #ce9178; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #555; }
        th { color: #4ec9b0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Live Login Debugger</h1>

        <div class="test-form">
            <form method="POST">
                <input type="text" name="test_user" placeholder="User Code or Email" value="<?php echo htmlspecialchars($testUser); ?>">
                <input type="text" name="test_pass" placeholder="Password" value="<?php echo htmlspecialchars($testPass); ?>">
                <button type="submit">🧪 Test Login Process</button>
            </form>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>

        <!-- STEP 1: Database Connection -->
        <div class="step">
            <div class="step-title">STEP 1: Database Connection</div>
            <?php
            try {
                $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                if ($conn->connect_error) {
                    throw new Exception($conn->connect_error);
                }
                mysqli_set_charset($conn, DB_CHARSET);
                echo '<div class="success">✓ Connected to database: ' . DB_NAME . '</div>';
            } catch (Exception $e) {
                echo '<div class="error">✗ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
                die();
            }
            ?>
        </div>

        <!-- STEP 2: Find User -->
        <div class="step">
            <div class="step-title">STEP 2: Find User</div>
            <?php
            echo '<div class="info">Searching for: <code>' . htmlspecialchars($testUser) . '</code></div>';
            
            $stmt = $conn->prepare("SELECT * FROM user WHERE (user_code = ? OR email = ?) AND is_active = 1 LIMIT 1");
            $stmt->bind_param('ss', $testUser, $testUser);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo '<div class="error">✗ User not found</div>';
                echo '<div class="warning">SQL: SELECT * FROM user WHERE (user_code = \'' . htmlspecialchars($testUser) . '\' OR email = \'' . htmlspecialchars($testUser) . '\') AND is_active = 1</div>';
                die();
            }
            
            $user = $result->fetch_assoc();
            echo '<div class="success">✓ User found</div>';
            
            echo '<table>';
            echo '<tr><th>Field</th><th>Value</th></tr>';
            foreach ($user as $key => $value) {
                if ($key === 'password') {
                    $displayValue = substr($value, 0, 30) . '... (' . strlen($value) . ' chars)';
                } else {
                    $displayValue = htmlspecialchars($value);
                }
                echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . $displayValue . '</td></tr>';
            }
            echo '</table>';
            ?>
        </div>

        <!-- STEP 3: Password Analysis -->
        <div class="step">
            <div class="step-title">STEP 3: Password Analysis</div>
            <?php
            $dbPassword = $user['password'];
            $isBcrypt = (strlen($dbPassword) === 60 && substr($dbPassword, 0, 4) === '$2y$');
            
            echo '<div class="info">Password in DB: <code>' . htmlspecialchars(substr($dbPassword, 0, 30)) . '...</code></div>';
            echo '<div class="info">Password length: ' . strlen($dbPassword) . ' chars</div>';
            echo '<div class="info">Password type: ' . ($isBcrypt ? '<span class="success">Bcrypt Hash</span>' : '<span class="warning">Plain Text</span>') . '</div>';
            echo '<div class="info">Password entered: <code>' . htmlspecialchars($testPass) . '</code></div>';
            ?>
        </div>

        <!-- STEP 4: Password Verification -->
        <div class="step">
            <div class="step-title">STEP 4: Password Verification</div>
            <?php
            $passwordValid = false;
            
            if ($isBcrypt) {
                echo '<div class="info">Using bcrypt verification: password_verify()</div>';
                $passwordValid = password_verify($testPass, $dbPassword);
                echo '<div class="info">Result: ' . ($passwordValid ? '<span class="success">MATCH ✓</span>' : '<span class="error">NO MATCH ✗</span>') . '</div>';
            } else {
                echo '<div class="info">Using plain text comparison</div>';
                $passwordValid = ($testPass === $dbPassword);
                echo '<div class="info">Comparing: "' . htmlspecialchars($testPass) . '" === "' . htmlspecialchars($dbPassword) . '"</div>';
                echo '<div class="info">Result: ' . ($passwordValid ? '<span class="success">MATCH ✓</span>' : '<span class="error">NO MATCH ✗</span>') . '</div>';
            }
            
            if (!$passwordValid) {
                echo '<div class="error">✗ PASSWORD VERIFICATION FAILED</div>';
                echo '<div class="warning">This is why login fails!</div>';
            } else {
                echo '<div class="success">✓ PASSWORD VERIFIED</div>';
            }
            ?>
        </div>

        <!-- STEP 5: Auth.php Test -->
        <div class="step">
            <div class="step-title">STEP 5: Test Auth::login()</div>
            <?php
            echo '<div class="info">Loading Auth.php...</div>';
            
            try {
                require_once __DIR__ . '/includes/core/Auth.php';
                echo '<div class="success">✓ Auth.php loaded</div>';
                
                echo '<div class="info">Calling Auth::login("' . htmlspecialchars($testUser) . '", "' . htmlspecialchars($testPass) . '", false)...</div>';
                
                $result = Auth::login($testUser, $testPass, false);
                
                echo '<div class="info">Auth::login() returned:</div>';
                echo '<pre>' . htmlspecialchars(print_r($result, true)) . '</pre>';
                
                if ($result['success']) {
                    echo '<div class="success">✓✓✓ AUTH::LOGIN() SUCCESSFUL ✓✓✓</div>';
                    echo '<div class="success">Login should work in the actual login page!</div>';
                } else {
                    echo '<div class="error">✗✗✗ AUTH::LOGIN() FAILED ✗✗✗</div>';
                    echo '<div class="error">Message: ' . htmlspecialchars($result['message']) . '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">✗ Exception in Auth::login()</div>';
                echo '<div class="error">Message: ' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '<div class="error">File: ' . htmlspecialchars($e->getFile()) . '</div>';
                echo '<div class="error">Line: ' . $e->getLine() . '</div>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            }
            ?>
        </div>

        <!-- STEP 6: Session Check -->
        <div class="step">
            <div class="step-title">STEP 6: Session Status</div>
            <?php
            if (session_status() === PHP_SESSION_ACTIVE) {
                echo '<div class="success">✓ Session is active</div>';
                echo '<div class="info">Session variables:</div>';
                echo '<pre>' . htmlspecialchars(print_r($_SESSION, true)) . '</pre>';
            } else {
                echo '<div class="warning">⚠ No active session</div>';
            }
            ?>
        </div>

        <!-- STEP 7: Token Check -->
        <div class="step">
            <div class="step-title">STEP 7: Token Status</div>
            <?php
            if (isset($_SESSION['payroll_token'])) {
                $token = $_SESSION['payroll_token'];
                echo '<div class="success">✓ Token created in session</div>';
                echo '<div class="info">Token: <code>' . htmlspecialchars(substr($token, 0, 20)) . '...</code></div>';
                
                // Check if token exists in database
                $stmt = $conn->prepare("SELECT * FROM tokens WHERE token = ?");
                $stmt->bind_param('s', $token);
                $stmt->execute();
                $tokenResult = $stmt->get_result();
                
                if ($tokenResult->num_rows > 0) {
                    $tokenData = $tokenResult->fetch_assoc();
                    echo '<div class="success">✓ Token found in database</div>';
                    echo '<table>';
                    foreach ($tokenData as $key => $value) {
                        echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="error">✗ Token NOT found in database</div>';
                }
            } else {
                echo '<div class="warning">⚠ No token in session</div>';
            }
            ?>
        </div>

        <!-- STEP 8: Error Log Check -->
        <div class="step">
            <div class="step-title">STEP 8: Recent Error Logs</div>
            <?php
            $logFile = __DIR__ . '/includes/core/login_errors.log';
            if (file_exists($logFile)) {
                $logs = file_get_contents($logFile);
                $logLines = explode("\n", $logs);
                $recentLogs = array_slice($logLines, -20);
                
                echo '<div class="info">Last 20 lines from login_errors.log:</div>';
                echo '<pre>' . htmlspecialchars(implode("\n", $recentLogs)) . '</pre>';
            } else {
                echo '<div class="warning">⚠ No error log file found</div>';
            }
            
            // Also check PHP error log
            if (ini_get('error_log')) {
                echo '<div class="info">PHP error_log location: ' . ini_get('error_log') . '</div>';
            }
            ?>
        </div>

        <!-- STEP 9: Recommendations -->
        <div class="step">
            <div class="step-title">STEP 9: Diagnosis & Recommendations</div>
            <?php
            echo '<div class="info">Based on the test results:</div>';
            
            if (isset($passwordValid) && !$passwordValid) {
                echo '<div class="error">❌ PASSWORD MISMATCH</div>';
                echo '<div class="warning">Fix: Password in database doesn\'t match entered password</div>';
                echo '<div class="info">Database has: "' . htmlspecialchars($dbPassword) . '"</div>';
                echo '<div class="info">You entered: "' . htmlspecialchars($testPass) . '"</div>';
            } elseif (isset($result) && !$result['success']) {
                echo '<div class="error">❌ AUTH::LOGIN() FAILED</div>';
                echo '<div class="warning">Issue: ' . htmlspecialchars($result['message'] ?? 'Unknown') . '</div>';
            } elseif (isset($result) && $result['success']) {
                echo '<div class="success">✅ EVERYTHING WORKS!</div>';
                echo '<div class="info">Login should work perfectly at login.php</div>';
                echo '<div class="info">Try: <a href="login.php" style="color: #4ec9b0;">login.php</a></div>';
            }
            ?>
        </div>

        <?php endif; ?>
    </div>
</body>
</html>
