<?php
/**
 * LOGIN DEBUG TOOL
 * This will test the entire login flow step by step
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Debug Tool</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .test-section h2 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 15px;
        }
        .success { color: #28a745; padding: 10px; background: #d4edda; border-radius: 5px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border-radius: 5px; margin: 10px 0; }
        .info { color: #004085; padding: 10px; background: #cce5ff; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; padding: 10px; background: #fff3cd; border-radius: 5px; margin: 10px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        pre {
            background: #272822;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .form-group {
            margin: 15px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Login Debug Tool</h1>
        
        <?php
        // Load config
        $configPath = __DIR__ . '/includes/config/config.php';
        if (file_exists($configPath)) {
            require_once $configPath;
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
            
            echo '<div class="test-section">';
            echo '<h2>🧪 Login Test Results</h2>';
            
            $testEmail = $_POST['email'] ?? '';
            $testPassword = $_POST['password'] ?? '';
            
            echo '<div class="info">Testing login with: <code>' . htmlspecialchars($testEmail) . '</code></div>';
            
            // Step 1: Check database connection
            echo '<h3>Step 1: Database Connection</h3>';
            $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if (!$conn) {
                echo '<div class="error">❌ Database connection failed: ' . mysqli_connect_error() . '</div>';
                echo '</div>';
            } else {
                echo '<div class="success">✅ Database connected</div>';
                
                // Step 2: Find user by email
                echo '<h3>Step 2: Find User</h3>';
                
                $email = mysqli_real_escape_string($conn, $testEmail);
                $query = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
                
                echo '<div class="info">Query: <code>' . htmlspecialchars($query) . '</code></div>';
                
                $result = mysqli_query($conn, $query);
                
                if (!$result) {
                    echo '<div class="error">❌ Query failed: ' . mysqli_error($conn) . '</div>';
                    echo '</div>';
                } else {
                    $user = mysqli_fetch_assoc($result);
                    
                    if (!$user) {
                        echo '<div class="error">❌ User not found with email: ' . htmlspecialchars($testEmail) . '</div>';
                        
                        // List all users
                        echo '<div class="warning">Available users in database:</div>';
                        $allUsers = mysqli_query($conn, "SELECT id, user_code, email, role, status FROM users");
                        
                        if ($allUsers && mysqli_num_rows($allUsers) > 0) {
                            echo '<table border="1" cellpadding="10" style="width:100%; margin:10px 0; border-collapse: collapse;">';
                            echo '<tr><th>ID</th><th>User Code</th><th>Email</th><th>Role</th><th>Status</th></tr>';
                            
                            while ($u = mysqli_fetch_assoc($allUsers)) {
                                echo '<tr>';
                                echo '<td>' . $u['id'] . '</td>';
                                echo '<td><code>' . htmlspecialchars($u['user_code']) . '</code></td>';
                                echo '<td>' . htmlspecialchars($u['email']) . '</td>';
                                echo '<td><strong>' . $u['role'] . '</strong></td>';
                                echo '<td>' . $u['status'] . '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</table>';
                        } else {
                            echo '<div class="error">❌ No users found in database! Import the database schema first.</div>';
                        }
                        
                        echo '</div>';
                    } else {
                        echo '<div class="success">✅ User found!</div>';
                        
                        // Display user info
                        echo '<table border="1" cellpadding="10" style="width:100%; margin:10px 0; border-collapse: collapse;">';
                        echo '<tr><th>Field</th><th>Value</th></tr>';
                        echo '<tr><td>ID</td><td>' . $user['id'] . '</td></tr>';
                        echo '<tr><td>User Code</td><td><code>' . htmlspecialchars($user['user_code']) . '</code></td></tr>';
                        echo '<tr><td>Email</td><td>' . htmlspecialchars($user['email']) . '</td></tr>';
                        echo '<tr><td>Role</td><td><strong>' . $user['role'] . '</strong></td></tr>';
                        echo '<tr><td>Status</td><td>' . $user['status'] . '</td></tr>';
                        echo '<tr><td>Password Hash</td><td><code style="font-size:10px;">' . substr($user['password'], 0, 30) . '...</code></td></tr>';
                        echo '</table>';
                        
                        // Step 3: Check password
                        echo '<h3>Step 3: Verify Password</h3>';
                        
                        $storedHash = $user['password'];
                        echo '<div class="info">Stored hash: <code style="font-size:10px;">' . htmlspecialchars($storedHash) . '</code></div>';
                        echo '<div class="info">Testing password: <code>' . htmlspecialchars($testPassword) . '</code></div>';
                        
                        // Check if password is already hashed
                        if (!str_starts_with($storedHash, '$2y$')) {
                            echo '<div class="warning">⚠️ Password in database is NOT hashed! This is a security issue.</div>';
                            
                            // Try direct comparison
                            if ($storedHash === $testPassword) {
                                echo '<div class="success">✅ Plain text password matches!</div>';
                                echo '<div class="warning">⚠️ You should hash this password immediately!</div>';
                                
                                // Show how to hash
                                $hashedPassword = password_hash($testPassword, PASSWORD_BCRYPT);
                                echo '<div class="info">Run this SQL to fix it:</div>';
                                echo '<pre>UPDATE users SET password = \'' . $hashedPassword . '\' WHERE id = ' . $user['id'] . ';</pre>';
                            } else {
                                echo '<div class="error">❌ Plain text password does NOT match!</div>';
                            }
                        } else {
                            // Verify hashed password
                            $verified = password_verify($testPassword, $storedHash);
                            
                            if ($verified) {
                                echo '<div class="success">✅ Password is CORRECT!</div>';
                                
                                // Step 4: Check account status
                                echo '<h3>Step 4: Check Account Status</h3>';
                                
                                if ($user['status'] !== 'active') {
                                    echo '<div class="error">❌ Account is not active! Status: ' . $user['status'] . '</div>';
                                } else {
                                    echo '<div class="success">✅ Account is active</div>';
                                    
                                    // Step 5: Test session
                                    echo '<h3>Step 5: Session Test</h3>';
                                    
                                    if (session_status() === PHP_SESSION_NONE) {
                                        session_start();
                                        echo '<div class="success">✅ Session started</div>';
                                    } else {
                                        echo '<div class="info">ℹ️ Session already active</div>';
                                    }
                                    
                                    echo '<div class="info">Session ID: <code>' . session_id() . '</code></div>';
                                    
                                    // Step 6: Test Auth class (if exists)
                                    echo '<h3>Step 6: Auth Class Test</h3>';
                                    
                                    $authPath = __DIR__ . '/includes/core/Auth.php';
                                    if (file_exists($authPath)) {
                                        echo '<div class="success">✅ Auth.php found at: <code>' . $authPath . '</code></div>';
                                        
                                        try {
                                            require_once $authPath;
                                            echo '<div class="success">✅ Auth class loaded</div>';
                                            
                                            if (class_exists('Auth')) {
                                                echo '<div class="success">✅ Auth class exists</div>';
                                                
                                                // Try to login
                                                echo '<div class="info">Attempting Auth::login()...</div>';
                                                
                                                try {
                                                    $loginResult = Auth::login($testEmail, $testPassword, false);
                                                    
                                                    echo '<pre>' . print_r($loginResult, true) . '</pre>';
                                                    
                                                    if ($loginResult['success'] ?? false) {
                                                        echo '<div class="success">✅ Auth::login() SUCCESSFUL!</div>';
                                                        echo '<div class="success">🎉 LOGIN SHOULD WORK!</div>';
                                                    } else {
                                                        echo '<div class="error">❌ Auth::login() failed: ' . ($loginResult['message'] ?? 'Unknown error') . '</div>';
                                                    }
                                                } catch (Exception $e) {
                                                    echo '<div class="error">❌ Exception during Auth::login(): ' . $e->getMessage() . '</div>';
                                                    echo '<pre>' . $e->getTraceAsString() . '</pre>';
                                                }
                                            } else {
                                                echo '<div class="error">❌ Auth class not found after loading Auth.php</div>';
                                            }
                                        } catch (Exception $e) {
                                            echo '<div class="error">❌ Error loading Auth class: ' . $e->getMessage() . '</div>';
                                            echo '<pre>' . $e->getTraceAsString() . '</pre>';
                                        }
                                    } else {
                                        echo '<div class="error">❌ Auth.php not found at: ' . $authPath . '</div>';
                                    }
                                    
                                    echo '<div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-radius: 5px;">';
                                    echo '<h3>✅ CONCLUSION</h3>';
                                    echo '<p><strong>User exists, password is correct, and account is active!</strong></p>';
                                    echo '<p>If login still fails, check:</p>';
                                    echo '<ol>';
                                    echo '<li>JavaScript console for errors (F12)</li>';
                                    echo '<li>Network tab to see API response</li>';
                                    echo '<li>Check login_handle.php is accessible</li>';
                                    echo '<li>Check session is being set properly</li>';
                                    echo '</ol>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="error">❌ Password is INCORRECT!</div>';
                                
                                // Test common passwords
                                echo '<div class="warning">Testing common passwords...</div>';
                                $testPasswords = ['Admin@123', 'admin', 'password', '123456', 'admin123'];
                                
                                foreach ($testPasswords as $testPwd) {
                                    if (password_verify($testPwd, $storedHash)) {
                                        echo '<div class="success">✅ Found it! The password is: <code>' . $testPwd . '</code></div>';
                                        break;
                                    }
                                }
                                
                                // Show how to reset password
                                echo '<div class="info">To reset password to "Admin@123", run this SQL:</div>';
                                $newHash = password_hash('Admin@123', PASSWORD_BCRYPT);
                                echo '<pre>UPDATE users SET password = \'' . $newHash . '\' WHERE email = \'' . mysqli_real_escape_string($conn, $testEmail) . '\';</pre>';
                            }
                        }
                        
                        echo '</div>';
                    }
                }
                
                mysqli_close($conn);
            }
        }
        ?>
        
        <!-- Login Test Form -->
        <div class="test-section">
            <h2>🧪 Test Login Credentials</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label>Email / User Code:</label>
                    <input type="text" name="email" value="admin@proconsultancy.be" required>
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <input type="text" name="password" value="Admin@123" required>
                    <small style="color: #666;">Note: Type is "text" so you can see what you're typing</small>
                </div>
                
                <button type="submit" name="test_login" class="btn">🔍 Test Login</button>
            </form>
        </div>
        
        <!-- Quick SQL Fixes -->
        <div class="test-section">
            <h2>🔧 Quick SQL Fixes</h2>
            
            <p>Copy and paste these in your MySQL client to fix common issues:</p>
            
            <h3>1. Reset Admin Password to "Admin@123"</h3>
            <pre><?php
$hash = password_hash('Admin@123', PASSWORD_BCRYPT);
echo "UPDATE users SET password = '$hash' WHERE email = 'admin@proconsultancy.be';";
?></pre>
            
            <h3>2. Create Admin User (if missing)</h3>
            <pre><?php
$hash = password_hash('Admin@123', PASSWORD_BCRYPT);
echo "INSERT INTO users (user_code, username, email, password, first_name, last_name, role, status, email_verified)
VALUES ('ADM001', 'admin', 'admin@proconsultancy.be', '$hash', 'System', 'Administrator', 'admin', 'active', 1);";
?></pre>
            
            <h3>3. Activate All Users</h3>
            <pre>UPDATE users SET status = 'active';</pre>
            
            <h3>4. Check All Users</h3>
            <pre>SELECT id, user_code, email, role, status FROM users;</pre>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="test_connection.php" style="display: inline-block; padding: 12px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-right: 10px;">
                Database Test
            </a>
            <a href="login.php" style="display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Go to Login
            </a>
        </div>
    </div>
</body>
</html>
