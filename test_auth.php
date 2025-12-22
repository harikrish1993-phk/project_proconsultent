<?php
/**
 * AUTH CLASS DEBUGGER
 * Helps debug the Auth class and see exactly what's happening
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load config
require_once __DIR__ . '/includes/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth Class Debugger</title>
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
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Auth Class Debugger</h1>
        
        <?php
        // Check if Auth class file exists
        echo '<div class="test-section">';
        echo '<h2>Test 1: Auth Class File</h2>';
        
        $authPaths = [
            __DIR__ . '/includes/core/Auth.php',
            __DIR__ . '/includes/Auth.php',
            __DIR__ . '/core/Auth.php',
        ];
        
        $authPath = null;
        foreach ($authPaths as $path) {
            if (file_exists($path)) {
                $authPath = $path;
                break;
            }
        }
        
        if ($authPath) {
            echo '<div class="success">✅ Auth.php found at: <code>' . $authPath . '</code></div>';
            
            // Display file contents
            echo '<h3>File Contents:</h3>';
            $contents = file_get_contents($authPath);
            echo '<pre>' . htmlspecialchars($contents) . '</pre>';
            
            // Try to load it
            echo '<h3>Loading Auth Class...</h3>';
            try {
                require_once $authPath;
                echo '<div class="success">✅ Auth.php loaded successfully</div>';
                
                if (class_exists('Auth')) {
                    echo '<div class="success">✅ Auth class exists</div>';
                    
                    // Check methods
                    echo '<h3>Available Methods:</h3>';
                    $methods = get_class_methods('Auth');
                    echo '<ul>';
                    foreach ($methods as $method) {
                        echo '<li><code>' . $method . '()</code></li>';
                    }
                    echo '</ul>';
                    
                    // Check properties
                    echo '<h3>Class Properties:</h3>';
                    $reflection = new ReflectionClass('Auth');
                    $properties = $reflection->getProperties();
                    
                    if (count($properties) > 0) {
                        echo '<ul>';
                        foreach ($properties as $property) {
                            echo '<li><code>' . $property->getName() . '</code> - ' . ($property->isStatic() ? 'static' : 'instance') . '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<div class="info">No properties defined</div>';
                    }
                    
                } else {
                    echo '<div class="error">❌ Auth class NOT found after loading file!</div>';
                    echo '<div class="warning">The file exists but does not define the Auth class.</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Error loading Auth.php: ' . $e->getMessage() . '</div>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
            }
            
        } else {
            echo '<div class="error">❌ Auth.php NOT found!</div>';
            echo '<div class="warning">Searched in:</div>';
            echo '<ul>';
            foreach ($authPaths as $path) {
                echo '<li><code>' . $path . '</code></li>';
            }
            echo '</ul>';
        }
        
        echo '</div>';
        
        // Check Database class
        echo '<div class="test-section">';
        echo '<h2>Test 2: Database Class</h2>';
        
        $dbPaths = [
            __DIR__ . '/includes/core/Database.php',
            __DIR__ . '/includes/Database.php',
            __DIR__ . '/core/Database.php',
        ];
        
        $dbPath = null;
        foreach ($dbPaths as $path) {
            if (file_exists($path)) {
                $dbPath = $path;
                break;
            }
        }
        
        if ($dbPath) {
            echo '<div class="success">✅ Database.php found at: <code>' . $dbPath . '</code></div>';
            
            try {
                require_once $dbPath;
                echo '<div class="success">✅ Database.php loaded</div>';
                
                if (class_exists('Database')) {
                    echo '<div class="success">✅ Database class exists</div>';
                    
                    // Test connection
                    try {
                        $db = Database::getInstance();
                        echo '<div class="success">✅ Database::getInstance() works</div>';
                        
                        $conn = $db->getConnection();
                        if ($conn) {
                            echo '<div class="success">✅ Database connection successful</div>';
                        } else {
                            echo '<div class="error">❌ Database connection failed</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="error">❌ Database getInstance error: ' . $e->getMessage() . '</div>';
                    }
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ Error loading Database.php: ' . $e->getMessage() . '</div>';
            }
        } else {
            echo '<div class="warning">⚠️ Database.php not found (may use direct mysqli)</div>';
        }
        
        echo '</div>';
        
        // Check Session class
        echo '<div class="test-section">';
        echo '<h2>Test 3: Session Class</h2>';
        
        $sessionPaths = [
            __DIR__ . '/includes/core/Session.php',
            __DIR__ . '/includes/Session.php',
            __DIR__ . '/core/Session.php',
        ];
        
        $sessionPath = null;
        foreach ($sessionPaths as $path) {
            if (file_exists($path)) {
                $sessionPath = $path;
                break;
            }
        }
        
        if ($sessionPath) {
            echo '<div class="success">✅ Session.php found at: <code>' . $sessionPath . '</code></div>';
            
            try {
                require_once $sessionPath;
                echo '<div class="success">✅ Session.php loaded</div>';
                
                if (class_exists('Session')) {
                    echo '<div class="success">✅ Session class exists</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ Error loading Session.php: ' . $e->getMessage() . '</div>';
            }
        } else {
            echo '<div class="warning">⚠️ Session.php not found (may use native PHP sessions)</div>';
        }
        
        echo '</div>';
        
        // Check login_handle.php
        echo '<div class="test-section">';
        echo '<h2>Test 4: Login Handler</h2>';
        
        $loginHandlerPath = __DIR__ . '/includes/core/login_handle.php';
        
        if (file_exists($loginHandlerPath)) {
            echo '<div class="success">✅ login_handle.php found</div>';
            
            echo '<h3>File Contents (first 50 lines):</h3>';
            $lines = file($loginHandlerPath);
            $preview = array_slice($lines, 0, 50);
            echo '<pre>' . htmlspecialchars(implode('', $preview)) . '</pre>';
            
            if (count($lines) > 50) {
                echo '<div class="info">... (file has ' . count($lines) . ' lines total)</div>';
            }
            
            // Check for common issues
            $content = file_get_contents($loginHandlerPath);
            
            echo '<h3>Checking for Common Issues:</h3>';
            
            if (strpos($content, 'session_start()') !== false) {
                echo '<div class="success">✅ session_start() found</div>';
            } else {
                echo '<div class="warning">⚠️ session_start() not found - sessions may not work</div>';
            }
            
            if (strpos($content, 'Auth::login') !== false) {
                echo '<div class="success">✅ Auth::login() call found</div>';
            } else {
                echo '<div class="warning">⚠️ Auth::login() call not found</div>';
            }
            
            if (strpos($content, 'json_encode') !== false) {
                echo '<div class="success">✅ JSON response found</div>';
            } else {
                echo '<div class="warning">⚠️ JSON response not found</div>';
            }
            
            if (strpos($content, 'Content-Type: application/json') !== false) {
                echo '<div class="success">✅ JSON header set</div>';
            } else {
                echo '<div class="warning">⚠️ JSON header not set</div>';
            }
            
        } else {
            echo '<div class="error">❌ login_handle.php not found at: <code>' . $loginHandlerPath . '</code></div>';
        }
        
        echo '</div>';
        
        // Check login.php API path
        echo '<div class="test-section">';
        echo '<h2>Test 5: Login Page Configuration</h2>';
        
        $loginPagePath = __DIR__ . '/login.php';
        
        if (file_exists($loginPagePath)) {
            echo '<div class="success">✅ login.php found</div>';
            
            $loginContent = file_get_contents($loginPagePath);
            
            // Find API_URL definition
            if (preg_match('/const\s+API_URL\s*=\s*[\'"]([^\'"]+)[\'"]/', $loginContent, $matches)) {
                $apiUrl = $matches[1];
                echo '<div class="info">Found API_URL: <code>' . htmlspecialchars($apiUrl) . '</code></div>';
                
                // Check if it's correct
                $correctPaths = ['includes/core/login_handle.php', 'includes/core/login_handler.php'];
                if (in_array($apiUrl, $correctPaths)) {
                    echo '<div class="success">✅ API path looks correct</div>';
                } else {
                    echo '<div class="warning">⚠️ API path may be wrong. Should be: <code>includes/core/login_handle.php</code></div>';
                }
                
                // Check if file exists
                $fullPath = __DIR__ . '/' . $apiUrl;
                if (file_exists($fullPath)) {
                    echo '<div class="success">✅ Login handler file exists at: <code>' . $fullPath . '</code></div>';
                } else {
                    echo '<div class="error">❌ Login handler file NOT found at: <code>' . $fullPath . '</code></div>';
                }
            } else {
                echo '<div class="error">❌ Could not find API_URL definition in login.php</div>';
            }
            
        } else {
            echo '<div class="error">❌ login.php not found</div>';
        }
        
        echo '</div>';
        
        // Test actual login flow
        if (class_exists('Auth')) {
            echo '<div class="test-section">';
            echo '<h2>Test 6: Actual Login Test</h2>';
            
            echo '<div class="info">Testing login with: admin@proconsultancy.be / Admin@123</div>';
            
            try {
                $result = Auth::login('admin@proconsultancy.be', 'Admin@123', false);
                
                echo '<h3>Login Result:</h3>';
                echo '<pre>' . print_r($result, true) . '</pre>';
                
                if ($result['success'] ?? false) {
                    echo '<div class="success">✅ LOGIN SUCCESSFUL!</div>';
                    echo '<div class="success">🎉 Your Auth class is working correctly!</div>';
                } else {
                    echo '<div class="error">❌ Login failed: ' . ($result['message'] ?? 'Unknown error') . '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Exception during login: ' . $e->getMessage() . '</div>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
            }
            
            echo '</div>';
        }
        ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="test_connection.php" style="display: inline-block; padding: 12px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-right: 10px;">
                Database Test
            </a>
            <a href="test_login.php" style="display: inline-block; padding: 12px 30px; background: #ffc107; color: #333; text-decoration: none; border-radius: 5px; font-weight: bold; margin-right: 10px;">
                Login Test
            </a>
            <a href="login.php" style="display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Go to Login
            </a>
        </div>
    </div>
</body>
</html>
