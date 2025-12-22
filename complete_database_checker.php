<?php
/**
 * COMPLETE DATABASE CHECKER
 * Shows exact database structure and sample data
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Structure Check</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #0f172a; color: #e2e8f0; }
        .box { background: #1e293b; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #334155; }
        h2 { color: #60a5fa; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #475569; }
        th { background: #334155; font-weight: bold; }
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        pre { background: #0f172a; padding: 15px; border-radius: 6px; overflow-x: auto; border: 1px solid #475569; }
        .sql-box { background: #1e40af; color: white; padding: 15px; border-radius: 6px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔍 Complete Database Structure Check</h1>

<?php

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("<div class='box'><span class='error'>✗ Connection failed: " . $conn->connect_error . "</span></div>");
    }
    
    echo "<div class='box'><span class='success'>✓ Connected to database: " . DB_NAME . "</span></div>";
    
    // ========================================
    // 1. USER TABLE STRUCTURE
    // ========================================
    echo "<div class='box'>";
    echo "<h2>1. USER Table Structure</h2>";
    
    $result = $conn->query("DESCRIBE user");
    
    if ($result) {
        echo "<table>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
            echo "<tr>";
            echo "<td><strong>" . $row['Field'] . "</strong></td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Total columns:</strong> " . count($columns) . "</p>";
        echo "<p><strong>Column names:</strong> " . implode(', ', $columns) . "</p>";
    }
    
    echo "</div>";
    
    // ========================================
    // 2. CHECK REQUIRED COLUMNS
    // ========================================
    echo "<div class='box'>";
    echo "<h2>2. Required Columns Check</h2>";
    
    $required = ['user_code', 'user_email', 'user_password', 'user_level', 'user_name'];
    $missing = [];
    
    echo "<table>";
    echo "<tr><th>Column</th><th>Status</th></tr>";
    
    foreach ($required as $col) {
        $exists = in_array($col, $columns);
        echo "<tr>";
        echo "<td><strong>$col</strong></td>";
        echo "<td>" . ($exists ? "<span class='success'>✓ EXISTS</span>" : "<span class='error'>✗ MISSING</span>") . "</td>";
        echo "</tr>";
        
        if (!$exists) {
            $missing[] = $col;
        }
    }
    
    echo "</table>";
    
    if (empty($missing)) {
        echo "<p class='success'><strong>✓ All required columns exist!</strong></p>";
    } else {
        echo "<p class='error'><strong>✗ Missing columns: " . implode(', ', $missing) . "</strong></p>";
    }
    
    echo "</div>";
    
    // ========================================
    // 3. SAMPLE DATA
    // ========================================
    echo "<div class='box'>";
    echo "<h2>3. Sample User Data (First 5 Users)</h2>";
    
    // Build SELECT query with available columns
    $selectCols = [];
    foreach (['id', 'user_code', 'user_email', 'user_password', 'user_level', 'user_name'] as $col) {
        if (in_array($col, $columns)) {
            $selectCols[] = $col;
        }
    }
    
    if (!empty($selectCols)) {
        $sql = "SELECT " . implode(', ', $selectCols) . " FROM user LIMIT 5";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            echo "<table>";
            echo "<tr>";
            foreach ($selectCols as $col) {
                echo "<th>$col</th>";
            }
            echo "</tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                foreach ($selectCols as $col) {
                    $value = $row[$col] ?? '';
                    
                    // Mask password
                    if ($col === 'user_password') {
                        if (strlen($value) > 50) {
                            $value = "[HASHED - bcrypt]";
                        } else {
                            $value = "[PLAIN TEXT: " . substr($value, 0, 3) . "***]";
                        }
                    }
                    
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p class='warning'>No users found in database</p>";
        }
    }
    
    echo "</div>";
    
    // ========================================
    // 4. PASSWORD CHECK
    // ========================================
    echo "<div class='box'>";
    echo "<h2>4. Password Format Check</h2>";
    
    if (in_array('user_password', $columns)) {
        $result = $conn->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN user_password LIKE '\$2y\$%' THEN 1 ELSE 0 END) as hashed,
            SUM(CASE WHEN user_password NOT LIKE '\$2y\$%' THEN 1 ELSE 0 END) as plain
            FROM user WHERE user_password IS NOT NULL");
        
        if ($row = $result->fetch_assoc()) {
            echo "<table>";
            echo "<tr><th>Type</th><th>Count</th></tr>";
            echo "<tr><td>Total users with password</td><td>" . $row['total'] . "</td></tr>";
            echo "<tr><td>Hashed (bcrypt)</td><td>" . $row['hashed'] . "</td></tr>";
            echo "<tr><td>Plain text</td><td>" . $row['plain'] . "</td></tr>";
            echo "</table>";
            
            if ($row['plain'] > 0) {
                echo "<p class='warning'>⚠ Warning: " . $row['plain'] . " users have plain text passwords</p>";
            }
        }
    } else {
        echo "<p class='error'>✗ user_password column does not exist</p>";
    }
    
    echo "</div>";
    
    // ========================================
    // 5. USER_LOGIN TABLE CHECK
    // ========================================
    echo "<div class='box'>";
    echo "<h2>5. user_login Table (Login History)</h2>";
    
    $result = $conn->query("SHOW TABLES LIKE 'user_login'");
    
    if ($result && $result->num_rows > 0) {
        echo "<p class='success'>✓ user_login table exists</p>";
        
        // Show structure
        $result = $conn->query("DESCRIBE user_login");
        echo "<table>";
        echo "<tr><th>Column</th><th>Type</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row['Field'] . "</td><td>" . $row['Type'] . "</td></tr>";
        }
        echo "</table>";
        
        // Show recent attempts
        $result = $conn->query("SELECT COUNT(*) as count FROM user_login");
        $row = $result->fetch_assoc();
        echo "<p>Total login attempts logged: " . $row['count'] . "</p>";
        
    } else {
        echo "<p class='error'>✗ user_login table does not exist</p>";
        echo "<p class='warning'>This table is needed for rate limiting. Create it with:</p>";
        echo "<div class='sql-box'>";
        echo "CREATE TABLE user_login (\n";
        echo "  id INT AUTO_INCREMENT PRIMARY KEY,\n";
        echo "  user_code VARCHAR(100),\n";
        echo "  ip_address VARCHAR(45),\n";
        echo "  user_login_status TINYINT DEFAULT 0,\n";
        echo "  user_login_time DATETIME,\n";
        echo "  created DATETIME DEFAULT CURRENT_TIMESTAMP\n";
        echo ");";
        echo "</div>";
    }
    
    echo "</div>";
    
    // ========================================
    // 6. SQL FIX GENERATOR
    // ========================================
    if (!empty($missing)) {
        echo "<div class='box'>";
        echo "<h2>6. SQL Fix for Missing Columns</h2>";
        
        echo "<p>Copy and run this SQL to add missing columns:</p>";
        
        echo "<div class='sql-box'>";
        echo "USE " . DB_NAME . ";\n\n";
        
        foreach ($missing as $col) {
            echo "ALTER TABLE user ADD COLUMN $col ";
            
            switch ($col) {
                case 'user_email':
                    echo "VARCHAR(255)";
                    break;
                case 'user_password':
                    echo "VARCHAR(255)";
                    break;
                case 'user_level':
                    echo "VARCHAR(50) DEFAULT 'user'";
                    break;
                case 'user_name':
                    echo "VARCHAR(255)";
                    break;
                default:
                    echo "VARCHAR(255)";
            }
            
            echo ";\n";
        }
        
        echo "\n-- Copy user_code to user_email if email is missing\n";
        echo "UPDATE user SET user_email = user_code WHERE user_email IS NULL;\n";
        
        echo "\n-- Set default level\n";
        echo "UPDATE user SET user_level = 'admin' WHERE user_level IS NULL;\n";
        
        echo "\n-- Verify\n";
        echo "SELECT user_code, user_email, user_level FROM user LIMIT 5;\n";
        
        echo "</div>";
        
        echo "</div>";
    }
    
    // ========================================
    // 7. TEST LOGIN CREDENTIALS
    // ========================================
    echo "<div class='box'>";
    echo "<h2>7. Test Login Credentials</h2>";
    
    echo "<p>Use these credentials to test login:</p>";
    
    if (in_array('user_code', $columns) && in_array('user_password', $columns)) {
        $sql = "SELECT user_code, user_password FROM user LIMIT 3";
        $result = $conn->query($sql);
        
        echo "<table>";
        echo "<tr><th>User Code (use this to login)</th><th>Password Info</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($row['user_code']) . "</strong></td>";
            
            $pass = $row['user_password'];
            if (strpos($pass, '$2y$') === 0) {
                echo "<td>[Hashed - you need original password]</td>";
            } else {
                echo "<td>[Plain text: " . htmlspecialchars($pass) . "]</td>";
            }
            
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<p><strong>How to test:</strong></p>";
        echo "<ol>";
        echo "<li>Go to: <a href='login.php'>login.php</a></li>";
        echo "<li>Enter user_code as shown above</li>";
        echo "<li>Enter the plain text password (if shown) or try common passwords like: admin123, test123, password</li>";
        echo "<li>Check browser console (F12) for errors</li>";
        echo "</ol>";
    }
    
    echo "</div>";
    
    // ========================================
    // 8. SUMMARY
    // ========================================
    echo "<div class='box'>";
    echo "<h2>8. Summary & Next Steps</h2>";
    
    if (empty($missing)) {
        echo "<p class='success'><strong>✓ Database structure is correct!</strong></p>";
        echo "<p>If login still doesn't work:</p>";
        echo "<ol>";
        echo "<li>Check that passwords are correct (try creating a new test user with known password)</li>";
        echo "<li>Check browser console for JavaScript errors</li>";
        echo "<li>Check network tab to see API response</li>";
        echo "<li>Check PHP error log for server errors</li>";
        echo "</ol>";
    } else {
        echo "<p class='error'><strong>✗ Missing columns detected!</strong></p>";
        echo "<p><strong>Run the SQL fix above to add missing columns.</strong></p>";
        echo "<p>Command:</p>";
        echo "<pre>mysql -u " . DB_USER . " -p " . DB_NAME . " < fix.sql</pre>";
    }
    
    echo "</div>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div class='box'><span class='error'>✗ Error: " . $e->getMessage() . "</span></div>";
}

?>

</body>
</html>
