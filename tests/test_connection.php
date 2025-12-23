<?php
/**
 * DATABASE CONNECTION TESTER
 * Place this in your project root and access via browser
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 900px;
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
        .success {
            color: #28a745;
            padding: 10px;
            background: #d4edda;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #dc3545;
            padding: 10px;
            background: #f8d7da;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #004085;
            padding: 10px;
            background: #cce5ff;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            color: #856404;
            padding: 10px;
            background: #fff3cd;
            border-radius: 5px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        .copy-btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Connection Tester</h1>
        
        <?php
        // Test 1: Check if .env file exists
        echo '<div class="test-section">';
        echo '<h2>Test 1: Environment File</h2>';
        
        $envPath = __DIR__ . '/.env';
        if (file_exists($envPath)) {
            echo '<div class="success">✅ .env file found at: ' . $envPath . '</div>';
            
            // Read and display .env contents (masked)
            $envContents = file_get_contents($envPath);
            $lines = explode("\n", $envContents);
            
            echo '<table>';
            echo '<tr><th>Key</th><th>Value</th></tr>';
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) continue;
                
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Mask sensitive values
                    if (stripos($key, 'PASS') !== false || stripos($key, 'KEY') !== false) {
                        $value = str_repeat('*', strlen($value));
                    }
                    
                    echo '<tr><td><code>' . htmlspecialchars($key) . '</code></td><td>' . htmlspecialchars($value) . '</td></tr>';
                }
            }
            echo '</table>';
            
        } else {
            echo '<div class="error">❌ .env file NOT found! Expected at: ' . $envPath . '</div>';
            echo '<div class="warning">Create a .env file in your project root with database credentials.</div>';
        }
        echo '</div>';
        
        // Test 2: Load config
        echo '<div class="test-section">';
        echo '<h2>Test 2: Configuration Loading</h2>';
        
        $configPath = __DIR__ . '/includes/config/config.php';
        if (file_exists($configPath)) {
            echo '<div class="success">✅ Config file found</div>';
            
            try {
                require_once $configPath;
                echo '<div class="success">✅ Config loaded successfully</div>';
                
                // Display constants
                echo '<table>';
                echo '<tr><th>Constant</th><th>Value</th></tr>';
                
                $constants = [
                    'DB_HOST' => DB_HOST ?? 'NOT SET',
                    'DB_USER' => DB_USER ?? 'NOT SET',
                    'DB_PASS' => DB_PASS ? str_repeat('*', strlen(DB_PASS)) : 'NOT SET',
                    'DB_NAME' => DB_NAME ?? 'NOT SET',
                    'COMPANY_NAME' => defined('COMPANY_NAME') ? COMPANY_NAME : 'NOT SET',
                    'COMPANY_TAGLINE' => defined('COMPANY_TAGLINE') ? COMPANY_TAGLINE : 'NOT SET',
                    'DEBUG_MODE' => defined('DEBUG_MODE') ? (DEBUG_MODE ? 'TRUE' : 'FALSE') : 'NOT SET',
                ];
                
                foreach ($constants as $name => $value) {
                    echo '<tr><td><code>' . $name . '</code></td><td>' . htmlspecialchars($value) . '</td></tr>';
                }
                echo '</table>';
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Error loading config: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } else {
            echo '<div class="error">❌ Config file NOT found at: ' . $configPath . '</div>';
        }
        echo '</div>';
        
        // Test 3: Database Connection
        echo '<div class="test-section">';
        echo '<h2>Test 3: Database Connection</h2>';
        
        if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
            
            // Test connection
            $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn) {
                echo '<div class="success">✅ Database connection successful!</div>';
                
                // Get MySQL version
                $version = mysqli_get_server_info($conn);
                echo '<div class="info">MySQL Version: ' . htmlspecialchars($version) . '</div>';
                
                // List tables
                $result = mysqli_query($conn, "SHOW TABLES");
                if ($result) {
                    $tables = [];
                    while ($row = mysqli_fetch_array($result)) {
                        $tables[] = $row[0];
                    }
                    
                    echo '<div class="success">✅ Found ' . count($tables) . ' tables</div>';
                    
                    if (count($tables) > 0) {
                        echo '<table>';
                        echo '<tr><th>#</th><th>Table Name</th><th>Row Count</th></tr>';
                        
                        foreach ($tables as $index => $table) {
                            $countResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM `$table`");
                            $countRow = mysqli_fetch_assoc($countResult);
                            $count = $countRow['count'];
                            
                            echo '<tr>';
                            echo '<td>' . ($index + 1) . '</td>';
                            echo '<td><code>' . htmlspecialchars($table) . '</code></td>';
                            echo '<td>' . $count . '</td>';
                            echo '</tr>';
                        }
                        
                        echo '</table>';
                    } else {
                        echo '<div class="warning">⚠️ No tables found! You need to import the database schema.</div>';
                    }
                }
                
                mysqli_close($conn);
                
            } else {
                echo '<div class="error">❌ Database connection FAILED!</div>';
                echo '<div class="error">Error: ' . htmlspecialchars(mysqli_connect_error()) . '</div>';
                echo '<div class="warning">Please check your database credentials in .env file</div>';
            }
            
        } else {
            echo '<div class="error">❌ Database constants not defined!</div>';
        }
        echo '</div>';
        
        // Test 4: Check Users Table
        if (isset($conn) && $conn) {
            echo '<div class="test-section">';
            echo '<h2>Test 4: Users Table</h2>';
            
            $result = @mysqli_query($conn, "SELECT * FROM users LIMIT 5");
            
            if ($result) {
                $userCount = mysqli_num_rows($result);
                
                if ($userCount > 0) {
                    echo '<div class="success">✅ Found ' . $userCount . ' user(s)</div>';
                    
                    echo '<table>';
                    echo '<tr><th>ID</th><th>User Code</th><th>Email</th><th>Role</th><th>Status</th></tr>';
                    
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                        echo '<td><code>' . htmlspecialchars($row['user_code']) . '</code></td>';
                        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                        echo '<td><strong>' . htmlspecialchars($row['role']) . '</strong></td>';
                        echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                    
                    echo '<div class="info">💡 You can login with any of these emails using their passwords</div>';
                    
                } else {
                    echo '<div class="error">❌ No users found in database!</div>';
                    echo '<div class="warning">You need to create at least one user to login.</div>';
                }
                
            } else {
                echo '<div class="error">❌ Users table not found or query failed!</div>';
                echo '<div class="error">Error: ' . htmlspecialchars(mysqli_error($conn)) . '</div>';
            }
            
            echo '</div>';
        }
        
        // Test 5: PHP Info
        echo '<div class="test-section">';
        echo '<h2>Test 5: PHP Environment</h2>';
        
        echo '<table>';
        echo '<tr><th>Setting</th><th>Value</th></tr>';
        echo '<tr><td>PHP Version</td><td>' . phpversion() . '</td></tr>';
        echo '<tr><td>Display Errors</td><td>' . ini_get('display_errors') . '</td></tr>';
        echo '<tr><td>Error Reporting</td><td>' . error_reporting() . '</td></tr>';
        echo '<tr><td>Max Upload Size</td><td>' . ini_get('upload_max_filesize') . '</td></tr>';
        echo '<tr><td>Post Max Size</td><td>' . ini_get('post_max_size') . '</td></tr>';
        echo '<tr><td>Session Path</td><td>' . session_save_path() . '</td></tr>';
        echo '</table>';
        
        echo '</div>';
        
        // Test 6: File Permissions
        echo '<div class="test-section">';
        echo '<h2>Test 6: File Permissions</h2>';
        
        $dirsToCheck = ['uploads', 'logs', 'uploads/candidates', 'uploads/documents'];
        
        echo '<table>';
        echo '<tr><th>Directory</th><th>Exists</th><th>Writable</th><th>Permissions</th></tr>';
        
        foreach ($dirsToCheck as $dir) {
            $fullPath = __DIR__ . '/' . $dir;
            $exists = is_dir($fullPath);
            $writable = $exists ? is_writable($fullPath) : false;
            $perms = $exists ? substr(sprintf('%o', fileperms($fullPath)), -4) : 'N/A';
            
            echo '<tr>';
            echo '<td><code>' . $dir . '</code></td>';
            echo '<td>' . ($exists ? '✅' : '❌') . '</td>';
            echo '<td>' . ($writable ? '✅' : '❌') . '</td>';
            echo '<td>' . $perms . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        echo '</div>';
        ?>
        
        <div class="test-section">
            <h2>📋 Next Steps</h2>
            
            <?php if (!isset($conn) || !$conn): ?>
                <div class="error">
                    <strong>Cannot connect to database!</strong>
                    <ol>
                        <li>Verify MySQL is running</li>
                        <li>Check .env file has correct credentials</li>
                        <li>Run: <code>mysql -u root -p</code> and create database</li>
                        <li>Import schema: <code>mysql -u [user] -p [database] < FINAL_DATABASE_SETUP.sql</code></li>
                    </ol>
                </div>
            <?php elseif (empty($tables)): ?>
                <div class="warning">
                    <strong>Database connected but no tables found!</strong>
                    <ol>
                        <li>Import the database schema</li>
                        <li>Run: <code>mysql -u <?php echo DB_USER; ?> -p <?php echo DB_NAME; ?> < FINAL_DATABASE_SETUP.sql</code></li>
                        <li>Refresh this page</li>
                    </ol>
                </div>
            <?php elseif ($userCount == 0): ?>
                <div class="warning">
                    <strong>No users in database!</strong>
                    <ol>
                        <li>Run the FINAL_DATABASE_SETUP.sql again (it includes default users)</li>
                        <li>Or manually create an admin user</li>
                    </ol>
                </div>
            <?php else: ?>
                <div class="success">
                    <strong>✅ Everything looks good!</strong>
                    <p>You should be able to login now. Try these credentials:</p>
                    <ul>
                        <li><strong>Admin:</strong> admin@proconsultancy.be / Admin@123</li>
                        <li><strong>Recruiter:</strong> recruiter@proconsultancy.be / Recruiter@123</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <a href="login.php" style="display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Go to Login Page
            </a>
        </div>
    </div>
</body>
</html>
