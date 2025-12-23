<?php
/**
 * LOGIN DIAGNOSTIC PAGE
 * This page helps diagnose login issues by showing:
 * 1. Database connection status
 * 2. User table structure
 * 3. Existing users and their details
 * 4. Password verification test
 */

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProConsultancy - Login Diagnostic</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .status {
            padding: 10px 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-weight: bold;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .code {
            background: #2d3748;
            color: #48bb78;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 10px 0;
            font-size: 14px;
        }
        .info {
            background: #e7f3ff;
            padding: 10px;
            border-left: 4px solid #667eea;
            margin: 10px 0;
        }
        .test-form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .test-form input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .test-form button {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        .test-form button:hover {
            background: #5568d3;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-active {
            background: #48bb78;
            color: white;
        }
        .badge-inactive {
            background: #fc8181;
            color: white;
        }
        .badge-admin {
            background: #667eea;
            color: white;
        }
        .badge-recruiter {
            background: #38b2ac;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 ProConsultancy Login Diagnostic</h1>

        <?php
        // ==========================================
        // SECTION 1: DATABASE CONNECTION TEST
        // ==========================================
        echo '<div class="section">';
        echo '<h2>1. Database Connection Test</h2>';
        
        try {
            // Try to load config
            if (file_exists(__DIR__ . '/includes/config/config.php')) {
                require_once __DIR__ . '/includes/config/config.php';
                echo '<div class="success">✓ Config file loaded successfully</div>';
            } else {
                throw new Exception('Config file not found at: ' . __DIR__ . '/includes/config/config.php');
            }
            
            // Test database connection
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception('Connection failed: ' . $conn->connect_error);
            }
            
            echo '<div class="success">✓ Database connection successful!</div>';
            
            echo '<div class="info">';
            echo '<strong>Connection Details:</strong><br>';
            echo '• Host: ' . DB_HOST . '<br>';
            echo '• Database: ' . DB_NAME . '<br>';
            echo '• User: ' . DB_USER . '<br>';
            echo '• Server Version: ' . $conn->server_info . '<br>';
            echo '• Character Set: ' . $conn->character_set_name() . '<br>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">✗ Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<div class="info">';
            echo '<strong>Troubleshooting Steps:</strong><br>';
            echo '1. Check if MySQL is running: <code>brew services list</code><br>';
            echo '2. Verify database exists: <code>mysql -u root -p -e "SHOW DATABASES;"</code><br>';
            echo '3. Check credentials in config/config.php<br>';
            echo '4. Verify user permissions<br>';
            echo '</div>';
            exit;
        }
        echo '</div>';

        // ==========================================
        // SECTION 2: USER TABLE STRUCTURE
        // ==========================================
        echo '<div class="section">';
        echo '<h2>2. User Table Structure</h2>';
        
        try {
            // Check if user table exists
            $result = $conn->query("SHOW TABLES LIKE 'user'");
            
            if ($result->num_rows === 0) {
                echo '<div class="error">✗ Table "user" does not exist!</div>';
                echo '<div class="warning">You need to import the database schema first.</div>';
                echo '<div class="code">mysql -u root -p proconsultancy_db < CORRECTED_DATABASE_SCHEMA.sql</div>';
            } else {
                echo '<div class="success">✓ Table "user" exists</div>';
                
                // Get table structure
                $structure = $conn->query("DESCRIBE user");
                
                echo '<table>';
                echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
                
                $hasUserCode = false;
                $hasName = false;
                $hasEmail = false;
                $hasPassword = false;
                $hasLevel = false;
                $hasActive = false;
                $hasIsActive = false;
                
                while ($col = $structure->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td><strong>' . htmlspecialchars($col['Field']) . '</strong></td>';
                    echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                    echo '<td>' . htmlspecialchars($col['Extra']) . '</td>';
                    echo '</tr>';
                    
                    // Check for required columns
                    if ($col['Field'] === 'user_code') $hasUserCode = true;
                    if ($col['Field'] === 'name') $hasName = true;
                    if ($col['Field'] === 'email') $hasEmail = true;
                    if ($col['Field'] === 'password') $hasPassword = true;
                    if ($col['Field'] === 'level') $hasLevel = true;
                    if ($col['Field'] === 'active') $hasActive = true;
                    if ($col['Field'] === 'is_active') $hasIsActive = true;
                }
                
                echo '</table>';
                
                // Verify required columns
                echo '<div class="info">';
                echo '<strong>Column Verification:</strong><br>';
                echo ($hasUserCode ? '✓' : '✗') . ' user_code (for login)<br>';
                echo ($hasName ? '✓' : '✗') . ' name (for display)<br>';
                echo ($hasEmail ? '✓' : '✗') . ' email (for login)<br>';
                echo ($hasPassword ? '✓' : '✗') . ' password (for authentication)<br>';
                echo ($hasLevel ? '✓' : '✗') . ' level (for role)<br>';
                echo ($hasActive ? '✓' : '✗') . ' active (varchar status)<br>';
                echo ($hasIsActive ? '✓' : '✗') . ' is_active (tinyint status)<br>';
                echo '</div>';
                
                if (!$hasUserCode || !$hasName || !$hasEmail || !$hasPassword || !$hasLevel || !$hasIsActive) {
                    echo '<div class="error">✗ Missing required columns! You need to re-import the corrected schema.</div>';
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="error">✗ Error checking table: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        echo '</div>';

        // ==========================================
        // SECTION 3: EXISTING USERS
        // ==========================================
        echo '<div class="section">';
        echo '<h2>3. Existing Users in Database</h2>';
        
        try {
            $users = $conn->query("SELECT user_code, name, email, level, active, is_active, last_login, created_at FROM user ORDER BY created_at DESC");
            
            if ($users->num_rows === 0) {
                echo '<div class="warning">⚠ No users found in database!</div>';
                echo '<div class="info">';
                echo '<strong>Default users should be:</strong><br>';
                echo '• Admin: admin@proconsultancy.be / Admin@123<br>';
                echo '• Recruiter: recruiter@proconsultancy.be / Recruiter@123<br>';
                echo '</div>';
                echo '<div class="code">';
                echo "-- Run this SQL to create default admin:<br>";
                echo "INSERT INTO user (user_code, name, email, password, level, active, is_active) VALUES<br>";
                echo "('ADM001', 'System Administrator', 'admin@proconsultancy.be', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '1', 1);";
                echo '</div>';
            } else {
                echo '<div class="success">✓ Found ' . $users->num_rows . ' user(s)</div>';
                
                echo '<table>';
                echo '<tr>';
                echo '<th>User Code</th>';
                echo '<th>Name</th>';
                echo '<th>Email</th>';
                echo '<th>Level</th>';
                echo '<th>Active (varchar)</th>';
                echo '<th>Is Active (tinyint)</th>';
                echo '<th>Last Login</th>';
                echo '</tr>';
                
                while ($user = $users->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td><strong>' . htmlspecialchars($user['user_code']) . '</strong></td>';
                    echo '<td>' . htmlspecialchars($user['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                    echo '<td><span class="badge badge-' . htmlspecialchars($user['level']) . '">' . 
                         strtoupper(htmlspecialchars($user['level'])) . '</span></td>';
                    echo '<td>' . htmlspecialchars($user['active'] ?? 'NULL') . '</td>';
                    echo '<td><span class="badge badge-' . ($user['is_active'] ? 'active' : 'inactive') . '">' . 
                         ($user['is_active'] ? 'ACTIVE' : 'INACTIVE') . '</span></td>';
                    echo '<td>' . htmlspecialchars($user['last_login'] ?? 'Never') . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">✗ Error fetching users: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        echo '</div>';

        // ==========================================
        // SECTION 4: PASSWORD TEST
        // ==========================================
        echo '<div class="section">';
        echo '<h2>4. Test Login Credentials</h2>';
        
        echo '<div class="test-form">';
        echo '<form method="POST" action="">';
        echo '<label><strong>User Code or Email:</strong></label>';
        echo '<input type="text" name="test_identifier" placeholder="e.g., ADM001 or admin@proconsultancy.be" required>';
        
        echo '<label><strong>Password:</strong></label>';
        echo '<input type="password" name="test_password" placeholder="e.g., Admin@123" required>';
        
        echo '<button type="submit" name="test_login">Test These Credentials</button>';
        echo '</form>';
        echo '</div>';
        
        // Process test login
        if (isset($_POST['test_login'])) {
            $testIdentifier = $_POST['test_identifier'];
            $testPassword = $_POST['test_password'];
            
            echo '<h3>Test Results:</h3>';
            
            try {
                // Find user
                $stmt = $conn->prepare("SELECT * FROM user WHERE (user_code = ? OR email = ?) AND is_active = 1 LIMIT 1");
                $stmt->bind_param('ss', $testIdentifier, $testIdentifier);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    echo '<div class="error">✗ User not found with identifier: ' . htmlspecialchars($testIdentifier) . '</div>';
                    echo '<div class="info">Checked both user_code and email columns with is_active = 1</div>';
                } else {
                    $user = $result->fetch_assoc();
                    
                    echo '<div class="success">✓ User found!</div>';
                    echo '<div class="info">';
                    echo '<strong>User Details:</strong><br>';
                    echo '• User Code: ' . htmlspecialchars($user['user_code']) . '<br>';
                    echo '• Name: ' . htmlspecialchars($user['name']) . '<br>';
                    echo '• Email: ' . htmlspecialchars($user['email']) . '<br>';
                    echo '• Level: ' . htmlspecialchars($user['level']) . '<br>';
                    echo '• Active (varchar): ' . htmlspecialchars($user['active'] ?? 'NULL') . '<br>';
                    echo '• Is Active (tinyint): ' . ($user['is_active'] ? 'Yes' : 'No') . '<br>';
                    echo '</div>';
                    
                    // Test password
                    if (password_verify($testPassword, $user['password'])) {
                        echo '<div class="success" style="font-size: 16px; margin-top: 15px;">';
                        echo '✓✓✓ PASSWORD VERIFIED! LOGIN SHOULD WORK! ✓✓✓';
                        echo '</div>';
                        
                        echo '<div class="info">';
                        echo '<strong>Login will redirect to:</strong><br>';
                        if ($user['level'] === 'admin') {
                            echo '→ panel/admin.php (Admin Dashboard)';
                        } else {
                            echo '→ panel/recruiter.php (Recruiter Dashboard)';
                        }
                        echo '</div>';
                        
                    } else {
                        echo '<div class="error">✗ PASSWORD INCORRECT!</div>';
                        
                        echo '<div class="info">';
                        echo '<strong>Password Hash in Database:</strong><br>';
                        echo '<code>' . htmlspecialchars(substr($user['password'], 0, 60)) . '...</code><br><br>';
                        
                        echo '<strong>Test Password Entered:</strong><br>';
                        echo '<code>' . htmlspecialchars($testPassword) . '</code><br><br>';
                        
                        echo '<strong>Possible Issues:</strong><br>';
                        echo '1. Password is case-sensitive<br>';
                        echo '2. Check for extra spaces<br>';
                        echo '3. Default password should be: <strong>Admin@123</strong> (capital A)<br>';
                        echo '</div>';
                        
                        // Show how to reset password
                        echo '<div class="code">';
                        echo "-- SQL to reset password to 'Admin@123':<br>";
                        echo "UPDATE user SET password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' <br>";
                        echo "WHERE user_code = '" . htmlspecialchars($user['user_code']) . "';";
                        echo '</div>';
                    }
                }
                
            } catch (Exception $e) {
                echo '<div class="error">✗ Test Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
        
        echo '</div>';

        // ==========================================
        // SECTION 5: AUTH.PHP VALIDATION CHECK
        // ==========================================
        echo '<div class="section">';
        echo '<h2>5. Auth.php Login Query Check</h2>';
        
        if (file_exists(__DIR__ . '/includes/core/Auth.php')) {
            echo '<div class="success">✓ Auth.php file found</div>';
            
            $authContent = file_get_contents(__DIR__ . '/includes/core/Auth.php');
            
            // Check what Auth.php is looking for
            echo '<div class="info">';
            echo '<strong>Auth.php Login Query Analysis:</strong><br><br>';
            
            if (strpos($authContent, 'FROM user') !== false) {
                echo '✓ Queries table: <code>user</code> (correct)<br>';
            } else if (strpos($authContent, 'FROM users') !== false) {
                echo '✗ Queries table: <code>users</code> (WRONG - should be "user")<br>';
            }
            
            if (strpos($authContent, 'user_code = ?') !== false || strpos($authContent, 'user_code = \'') !== false) {
                echo '✓ Uses column: <code>user_code</code> (correct)<br>';
            }
            
            if (strpos($authContent, 'email = ?') !== false || strpos($authContent, 'email = \'') !== false) {
                echo '✓ Uses column: <code>email</code> (correct)<br>';
            }
            
            if (strpos($authContent, 'is_active = 1') !== false) {
                echo '✓ Checks: <code>is_active = 1</code> (correct)<br>';
            } else if (strpos($authContent, 'active = \'1\'') !== false) {
                echo '✓ Checks: <code>active = \'1\'</code> (varchar check)<br>';
            }
            
            if (strpos($authContent, 'password_verify') !== false) {
                echo '✓ Uses: <code>password_verify()</code> for bcrypt (correct)<br>';
            }
            
            echo '</div>';
            
            // Extract the actual SQL query from Auth.php
            if (preg_match('/SELECT.*?FROM user.*?WHERE.*?LIMIT/s', $authContent, $matches)) {
                echo '<div class="code">';
                echo '<strong>Actual SQL Query from Auth.php:</strong><br>';
                echo htmlspecialchars($matches[0]);
                echo '</div>';
            }
            
        } else {
            echo '<div class="error">✗ Auth.php not found at: includes/core/Auth.php</div>';
        }
        
        echo '</div>';

        // ==========================================
        // SECTION 6: RECOMMENDATIONS
        // ==========================================
        echo '<div class="section">';
        echo '<h2>6. Quick Fix Recommendations</h2>';
        
        echo '<div class="info">';
        echo '<strong>Based on diagnostic results above, here are your next steps:</strong><br><br>';
        
        // Recommendation 1
        echo '<strong>STEP 1:</strong> If Database.php duplicate error still shows:<br>';
        echo '<code>bash FIX_DATABASE_DUPLICATE.sh</code><br>';
        echo '<code>valet restart</code> (or restart your web server)<br><br>';
        
        // Recommendation 2
        echo '<strong>STEP 2:</strong> If "user" table doesn\'t exist or missing columns:<br>';
        echo '<code>mysql -u root -p proconsultancy_db < CORRECTED_DATABASE_SCHEMA.sql</code><br><br>';
        
        // Recommendation 3
        echo '<strong>STEP 3:</strong> If no users exist or password fails:<br>';
        echo '<code>mysql -u proconsultancy_user -p proconsultancy_db</code><br>';
        echo 'Then run the SQL shown in Section 3 or 4 above<br><br>';
        
        // Recommendation 4
        echo '<strong>STEP 4:</strong> Test login:<br>';
        echo '• Go to: <code>http://localhost/proconsultancy/login.php</code><br>';
        echo '• Enter: <code>admin@proconsultancy.be</code><br>';
        echo '• Password: <code>Admin@123</code> (capital A)<br><br>';
        
        // Recommendation 5
        echo '<strong>STEP 5:</strong> If still issues, check browser console (F12) for JavaScript errors<br>';
        echo 'And check: <code>tail -f includes/core/login_errors.log</code><br>';
        
        echo '</div>';
        
        echo '</div>';

        // Close connection
        if (isset($conn)) {
            $conn->close();
        }
        ?>

        <div class="section" style="background: #667eea; color: white; text-align: center;">
            <p style="margin: 0; font-size: 14px;">
                <strong>ProConsultancy Login Diagnostic v1.0</strong> | 
                Generated: <?php echo date('Y-m-d H:i:s'); ?> |
                Refresh this page after making changes
            </p>
        </div>
    </div>
</body>
</html>
