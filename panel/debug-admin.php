<?php
/**
 * ADMIN PAGE ERROR DEBUGGER
 * Run this instead of admin.php to see what's breaking
 */

// ENABLE ALL ERROR REPORTING
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

echo "<pre style='background: #000; color: #0f0; padding: 20px; font-family: monospace;'>";
echo "=== ADMIN PAGE DEBUGGER ===\n\n";

// STEP 1: Check file existence
echo "STEP 1: Checking file paths...\n";

$files_to_check = [
    '../includes/config/config.php',
    '../includes/core/Auth.php',
    '../includes/core/Database.php'
];

foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        echo "✓ EXISTS: $file\n";
    } else {
        echo "✗ MISSING: $file\n";
        echo "  Full path: $full_path\n";
    }
}

echo "\n";

// STEP 2: Load config
echo "STEP 2: Loading config.php...\n";
try {
    require_once __DIR__ . '/../includes/config/config.php';
    echo "✓ Config loaded successfully\n";
    echo "  DB_HOST: " . DB_HOST . "\n";
    echo "  DB_NAME: " . DB_NAME . "\n";
} catch (Exception $e) {
    echo "✗ Config failed: " . $e->getMessage() . "\n";
    die();
}

echo "\n";

// STEP 3: Load Auth
echo "STEP 3: Loading Auth.php...\n";
try {
    require_once __DIR__ . '/../includes/core/Auth.php';
    echo "✓ Auth loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Auth failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    echo "  Trace:\n" . $e->getTraceAsString() . "\n";
    die();
}

echo "\n";

// STEP 4: Check authentication
echo "STEP 4: Checking authentication...\n";
try {
    $isAuthenticated = Auth::check();
    echo "  Auth::check(): " . ($isAuthenticated ? "TRUE" : "FALSE") . "\n";
    
    if ($isAuthenticated) {
        $user = Auth::user();
        echo "  User found: " . ($user ? "YES" : "NO") . "\n";
        if ($user) {
            echo "  User code: " . $user['user_code'] . "\n";
            echo "  User name: " . $user['name'] . "\n";
            echo "  User level: " . $user['level'] . "\n";
        }
    } else {
        echo "  ⚠ Not authenticated - would redirect to login\n";
    }
} catch (Exception $e) {
    echo "✗ Auth check failed: " . $e->getMessage() . "\n";
    die();
}

echo "\n";

// STEP 5: Load Database
echo "STEP 5: Loading Database.php...\n";
try {
    require_once __DIR__ . '/../includes/core/Database.php';
    echo "✓ Database class loaded\n";
    
    $db = Database::getInstance();
    echo "✓ Database instance created\n";
    
    $conn = $db->getConnection();
    echo "✓ Database connection obtained\n";
    echo "  Connection: " . ($conn ? "ACTIVE" : "NULL") . "\n";
    
} catch (Exception $e) {
    echo "✗ Database failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    die();
}

echo "\n";

// STEP 6: Test basic query
echo "STEP 6: Testing database query...\n";
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM candidates");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✓ Query successful\n";
        echo "  Candidates count: " . $row['count'] . "\n";
    } else {
        echo "✗ Query failed: " . $conn->error . "\n";
    }
} catch (Exception $e) {
    echo "✗ Query exception: " . $e->getMessage() . "\n";
}

echo "\n";

// STEP 7: Test admin statistics queries
echo "STEP 7: Testing admin statistics queries...\n";

$queries = [
    'Candidates stats' => "SELECT COUNT(*) as total FROM candidates",
    'Jobs stats' => "SELECT COUNT(*) as total FROM jobs",
    'Users stats' => "SELECT COUNT(*) as total FROM user",
    'Today followups' => "SELECT COUNT(*) as count FROM candidates WHERE follow_up_date = CURDATE()"
];

foreach ($queries as $name => $sql) {
    try {
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $count = reset($row); // Get first column value
            echo "✓ $name: $count\n";
        } else {
            echo "✗ $name failed: " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        echo "✗ $name exception: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// STEP 8: Check for problematic columns
echo "STEP 8: Checking table structures...\n";

$tables = ['candidates', 'jobs', 'user', 'tokens'];

foreach ($tables as $table) {
    try {
        $result = $conn->query("DESCRIBE $table");
        if ($result) {
            echo "✓ Table '$table' exists\n";
            echo "  Columns: ";
            $columns = [];
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            echo implode(', ', $columns) . "\n";
        } else {
            echo "✗ Table '$table' error: " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Table '$table' exception: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// STEP 9: Check admin.php for syntax errors
echo "STEP 9: Checking admin.php syntax...\n";
$admin_file = __DIR__ . '/admin.php';
if (file_exists($admin_file)) {
    echo "✓ admin.php exists\n";
    
    // Try to parse the file
    $output = [];
    $return_var = 0;
    exec("php -l " . escapeshellarg($admin_file) . " 2>&1", $output, $return_var);
    
    if ($return_var === 0) {
        echo "✓ admin.php syntax is valid\n";
    } else {
        echo "✗ admin.php has syntax errors:\n";
        echo "  " . implode("\n  ", $output) . "\n";
    }
} else {
    echo "✗ admin.php file not found\n";
}

echo "\n";

// STEP 10: Check password_resets table (admin.php queries this)
echo "STEP 10: Checking password_resets table...\n";
try {
    $result = $conn->query("SHOW TABLES LIKE 'password_resets'");
    if ($result && $result->num_rows > 0) {
        echo "✓ password_resets table exists\n";
        
        $result = $conn->query("SELECT COUNT(*) as count FROM password_resets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "  Recent resets: " . $row['count'] . "\n";
        }
    } else {
        echo "⚠ password_resets table does NOT exist\n";
        echo "  This will cause admin.php to fail at line ~160\n";
        echo "  Solution: Create the table or comment out that query\n";
    }
} catch (Exception $e) {
    echo "✗ password_resets check failed: " . $e->getMessage() . "\n";
}

echo "\n";

// STEP 11: Memory and execution limits
echo "STEP 11: PHP configuration...\n";
echo "  Memory limit: " . ini_get('memory_limit') . "\n";
echo "  Max execution time: " . ini_get('max_execution_time') . " seconds\n";
echo "  Error reporting: " . error_reporting() . "\n";
echo "  Display errors: " . ini_get('display_errors') . "\n";

echo "\n";

// FINAL SUMMARY
echo "=== SUMMARY ===\n\n";
echo "If all steps above show ✓, then admin.php should work.\n";
echo "If you see ✗ or ⚠, that's your problem.\n\n";

echo "Common issues:\n";
echo "1. Missing password_resets table → Comment out line ~160-166 in admin.php\n";
echo "2. Database query errors → Check table structures match queries\n";
echo "3. Auth issues → Clear session and re-login\n";
echo "4. File permissions → Check if files are readable\n\n";

echo "Next steps:\n";
echo "1. Fix any issues shown above\n";
echo "2. Try accessing admin.php again\n";
echo "3. If still 500 error, check:\n";
echo "   - tail -f " . ini_get('error_log') . "\n";
echo "   - tail -f ~/.config/valet/Log/php-fpm.log\n";
echo "   - tail -f ~/.config/valet/Log/nginx-error.log\n";

echo "\n</pre>";

// Close connection
if (isset($conn)) {
    $conn->close();
}
?>
