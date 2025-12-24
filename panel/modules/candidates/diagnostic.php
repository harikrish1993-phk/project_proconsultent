<?php
/**
 * DIAGNOSTIC TEST PAGE
 * File: panel/modules/candidates/diagnostic.php
 * 
 * This page tests each component of _common.php step by step
 * Use this to identify exactly where the problem is
 */

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>System Diagnostic</title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            padding: 20px; 
            background: #1a1a1a; 
            color: #0f0;
        }
        .test { 
            padding: 10px; 
            margin: 10px 0; 
            border-left: 4px solid #333;
            background: #2a2a2a;
        }
        .pass { border-left-color: #0f0; color: #0f0; }
        .fail { border-left-color: #f00; color: #f00; }
        .info { border-left-color: #0af; color: #0af; }
        pre { 
            background: #000; 
            padding: 10px; 
            overflow-x: auto;
            color: #fff;
        }
        h1 { color: #0af; }
        h2 { color: #0f0; margin-top: 30px; }
    </style>
</head>
<body>
<h1>🔍 ProConsultancy System Diagnostic</h1>
<p>Testing each component step by step...</p>
<hr style='border-color: #333;'>
";

$results = [];
$failedAt = '';

// =============================================================================
// TEST 1: PHP Version
// =============================================================================
echo "<h2>TEST 1: PHP Environment</h2>";
echo "<div class='test pass'>✅ PHP Version: " . PHP_VERSION . "</div>";
$results[] = "✅ PHP Version OK";

// =============================================================================
// TEST 2: File Paths
// =============================================================================
echo "<h2>TEST 2: File Structure</h2>";

$currentFile = __FILE__;
echo "<div class='test info'>Current file: $currentFile</div>";

$rootPath = dirname(dirname(dirname(__FILE__)));
echo "<div class='test info'>Calculated ROOT_PATH: $rootPath</div>";

if (file_exists($rootPath)) {
    echo "<div class='test pass'>✅ ROOT_PATH exists</div>";
    $results[] = "✅ ROOT_PATH exists";
} else {
    echo "<div class='test fail'>❌ ROOT_PATH does not exist!</div>";
    $failedAt = 'ROOT_PATH';
}

// =============================================================================
// TEST 3: Configuration File
// =============================================================================
echo "<h2>TEST 3: Configuration File</h2>";

$configPath = $rootPath . '/includes/config/config.php';
echo "<div class='test info'>Looking for: $configPath</div>";

if (file_exists($configPath)) {
    echo "<div class='test pass'>✅ config.php found</div>";
    
    try {
        require_once $configPath;
        echo "<div class='test pass'>✅ config.php loaded successfully</div>";
        
        // Check constants
        $constants = ['DB_HOST', 'DB_USER', 'DB_NAME', 'COMPANY_NAME'];
        echo "<div class='test info'>Checking constants...</div>";
        
        foreach ($constants as $const) {
            if (defined($const)) {
                $value = constant($const);
                // Mask password
                if ($const === 'DB_PASS') {
                    $value = '***hidden***';
                }
                echo "<div class='test pass'>  ✅ $const = " . htmlspecialchars($value) . "</div>";
            } else {
                echo "<div class='test fail'>  ❌ $const not defined</div>";
            }
        }
        
        $results[] = "✅ Configuration loaded";
    } catch (Exception $e) {
        echo "<div class='test fail'>❌ Failed to load config.php: " . htmlspecialchars($e->getMessage()) . "</div>";
        $failedAt = 'config.php load';
    }
} else {
    echo "<div class='test fail'>❌ config.php NOT FOUND</div>";
    echo "<div class='test info'>Check that .env file exists and config.php can read it</div>";
    $failedAt = 'config.php missing';
}

// =============================================================================
// TEST 4: Auth Class
// =============================================================================
echo "<h2>TEST 4: Auth Class</h2>";

$authPath = $rootPath . '/includes/core/Auth.php';
echo "<div class='test info'>Looking for: $authPath</div>";

if (file_exists($authPath)) {
    echo "<div class='test pass'>✅ Auth.php found</div>";
    
    try {
        require_once $authPath;
        echo "<div class='test pass'>✅ Auth.php loaded</div>";
        
        if (class_exists('Auth')) {
            echo "<div class='test pass'>✅ Auth class exists</div>";
            
            // Test Auth::check()
            try {
                $isLoggedIn = Auth::check();
                if ($isLoggedIn) {
                    echo "<div class='test pass'>✅ User is authenticated</div>";
                    
                    // Get user
                    $user = Auth::user();
                    if ($user) {
                        echo "<div class='test pass'>✅ User data retrieved</div>";
                        echo "<div class='test info'>User data:</div>";
                        echo "<pre>" . print_r($user, true) . "</pre>";
                        $results[] = "✅ Authentication working";
                    } else {
                        echo "<div class='test fail'>❌ Auth::user() returned null</div>";
                        $failedAt = 'Auth::user()';
                    }
                } else {
                    echo "<div class='test fail'>❌ User NOT authenticated</div>";
                    echo "<div class='test info'>You need to login first: <a href='/login.php' style='color: #0af;'>Login</a></div>";
                    $failedAt = 'Not logged in';
                }
            } catch (Exception $e) {
                echo "<div class='test fail'>❌ Auth::check() failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                $failedAt = 'Auth::check()';
            }
        } else {
            echo "<div class='test fail'>❌ Auth class does not exist after loading</div>";
            $failedAt = 'Auth class';
        }
    } catch (Exception $e) {
        echo "<div class='test fail'>❌ Failed to load Auth.php: " . htmlspecialchars($e->getMessage()) . "</div>";
        $failedAt = 'Auth.php load';
    }
} else {
    echo "<div class='test fail'>❌ Auth.php NOT FOUND</div>";
    $failedAt = 'Auth.php missing';
}

// =============================================================================
// TEST 5: Database Class
// =============================================================================
echo "<h2>TEST 5: Database Class</h2>";

$dbPath = $rootPath . '/includes/core/Database.php';
echo "<div class='test info'>Looking for: $dbPath</div>";

if (file_exists($dbPath)) {
    echo "<div class='test pass'>✅ Database.php found</div>";
    
    try {
        require_once $dbPath;
        echo "<div class='test pass'>✅ Database.php loaded</div>";
        
        if (class_exists('Database')) {
            echo "<div class='test pass'>✅ Database class exists</div>";
            
            // Test connection
            try {
                $db = Database::getInstance();
                echo "<div class='test pass'>✅ Database instance created</div>";
                
                $conn = $db->getConnection();
                if ($conn) {
                    echo "<div class='test pass'>✅ Database connection established</div>";
                    
                    // Test query
                    $result = $conn->query("SELECT DATABASE() as db_name");
                    if ($result) {
                        $row = $result->fetch_assoc();
                        echo "<div class='test pass'>✅ Connected to database: " . htmlspecialchars($row['db_name']) . "</div>";
                        
                        // Check tables
                        $tables = $conn->query("SHOW TABLES");
                        $tableCount = $tables->num_rows;
                        echo "<div class='test pass'>✅ Database has $tableCount tables</div>";
                        
                        $results[] = "✅ Database connected";
                    } else {
                        echo "<div class='test fail'>❌ Test query failed</div>";
                        $failedAt = 'Database query';
                    }
                } else {
                    echo "<div class='test fail'>❌ getConnection() returned null</div>";
                    $failedAt = 'Database getConnection';
                }
            } catch (Exception $e) {
                echo "<div class='test fail'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                $failedAt = 'Database connection';
            }
        } else {
            echo "<div class='test fail'>❌ Database class does not exist</div>";
            $failedAt = 'Database class';
        }
    } catch (Exception $e) {
        echo "<div class='test fail'>❌ Failed to load Database.php: " . htmlspecialchars($e->getMessage()) . "</div>";
        $failedAt = 'Database.php load';
    }
} else {
    echo "<div class='test fail'>❌ Database.php NOT FOUND</div>";
    $failedAt = 'Database.php missing';
}

// =============================================================================
// TEST 6: Session
// =============================================================================
echo "<h2>TEST 6: Session</h2>";

if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<div class='test pass'>✅ Session is active</div>";
    echo "<div class='test info'>Session ID: " . session_id() . "</div>";
    
    if (!empty($_SESSION)) {
        echo "<div class='test info'>Session data:</div>";
        echo "<pre>";
        foreach ($_SESSION as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                echo htmlspecialchars("$key = $value") . "\n";
            } else {
                echo htmlspecialchars("$key = ") . gettype($value) . "\n";
            }
        }
        echo "</pre>";
    } else {
        echo "<div class='test info'>Session is empty (may need to login)</div>";
    }
} else {
    echo "<div class='test fail'>❌ Session is not active</div>";
}

// =============================================================================
// TEST 7: Now try loading _common.php
// =============================================================================
echo "<h2>TEST 7: Loading _common.php</h2>";

if (empty($failedAt)) {
    echo "<div class='test info'>Attempting to load _common.php...</div>";
    
    try {
        $commonPath = __DIR__ . '/../_common.php';
        echo "<div class='test info'>Path: $commonPath</div>";
        
        if (file_exists($commonPath)) {
            require_once $commonPath;
            echo "<div class='test pass'>✅ _common.php loaded successfully!</div>";
            
            // Check variables
            if (isset($current_user_code)) {
                echo "<div class='test pass'>✅ \$current_user_code = " . htmlspecialchars($current_user_code) . "</div>";
            }
            if (isset($current_user_name)) {
                echo "<div class='test pass'>✅ \$current_user_name = " . htmlspecialchars($current_user_name) . "</div>";
            }
            if (isset($current_user_level)) {
                echo "<div class='test pass'>✅ \$current_user_level = " . htmlspecialchars($current_user_level) . "</div>";
            }
            
            $results[] = "✅ _common.php loaded";
            
        } else {
            echo "<div class='test fail'>❌ _common.php not found at: $commonPath</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='test fail'>❌ _common.php load failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<div class='test info'>Stack trace:</div>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
} else {
    echo "<div class='test fail'>⚠️ Skipping _common.php test because previous test failed at: $failedAt</div>";
}

// =============================================================================
// SUMMARY
// =============================================================================
echo "<hr style='border-color: #333; margin-top: 30px;'>";
echo "<h2>📊 Summary</h2>";

foreach ($results as $result) {
    echo "<div class='test pass'>$result</div>";
}

if ($failedAt) {
    echo "<div class='test fail'><strong>⚠️ FAILED AT: $failedAt</strong></div>";
    echo "<div class='test info'><strong>Next Steps:</strong></div>";
    
    if ($failedAt === 'config.php missing' || $failedAt === 'config.php load') {
        echo "<div class='test info'>
            1. Check that .env file exists in project root<br>
            2. Verify .env has correct database credentials<br>
            3. Check that includes/config/config.php exists and can read .env
        </div>";
    } elseif ($failedAt === 'Not logged in') {
        echo "<div class='test info'>
            1. <a href='/login.php' style='color: #0af;'>Login here</a><br>
            2. Then return to this diagnostic page
        </div>";
    } elseif ($failedAt === 'Database connection') {
        echo "<div class='test info'>
            1. Check database credentials in .env<br>
            2. Verify MySQL/MariaDB is running<br>
            3. Check database exists: mysql -u root -p -e 'SHOW DATABASES;'<br>
            4. Deploy schema if needed
        </div>";
    }
} else {
    echo "<div class='test pass'><strong>✅ ALL TESTS PASSED!</strong></div>";
    echo "<div class='test info'>
        The system is working correctly. You can now:<br>
        • <a href='index.php' style='color: #0af;'>Go to Candidates Index</a><br>
        • <a href='create.php' style='color: #0af;'>Create a Candidate</a><br>
        • <a href='list.php' style='color: #0af;'>View Candidate List</a>
    </div>";
}

echo "
<hr style='border-color: #333; margin-top: 30px;'>
<div class='test info'>
    <strong>Diagnostic Complete</strong><br>
    Generated: " . date('Y-m-d H:i:s') . "<br>
    File: " . __FILE__ . "
</div>
</body>
</html>";
?>
