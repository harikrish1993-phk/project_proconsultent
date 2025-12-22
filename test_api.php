<?php
/**
 * API ENDPOINT TESTER
 * Tests the login API endpoint directly
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Endpoint Tester</title>
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
        .form-group input, .form-group select {
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
        <h1>🌐 API Endpoint Tester</h1>
        
        <div class="test-section">
            <h2>📍 Endpoint Configuration</h2>
            
            <form method="POST" id="apiTestForm">
                <div class="form-group">
                    <label>API Endpoint:</label>
                    <select name="endpoint" id="endpoint">
                        <option value="includes/core/login_handle.php">includes/core/login_handle.php (Correct)</option>
                        <option value="include/login_handle.php">include/login_handle.php (Wrong - Old)</option>
                        <option value="login_handle.php">login_handle.php (Wrong)</option>
                        <option value="core/login_handle.php">core/login_handle.php (Wrong)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Email / User Code:</label>
                    <input type="text" name="user_code" value="admin@proconsultancy.be" required>
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <input type="text" name="password" value="Admin@123" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="remember_me" value="1"> Remember Me
                    </label>
                </div>
                
                <button type="submit" name="test_api" class="btn">🧪 Test API Endpoint</button>
            </form>
        </div>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_api'])) {
            
            echo '<div class="test-section">';
            echo '<h2>🔬 API Test Results</h2>';
            
            $endpoint = $_POST['endpoint'] ?? 'includes/core/login_handle.php';
            $userCode = $_POST['user_code'] ?? '';
            $password = $_POST['password'] ?? '';
            $rememberMe = isset($_POST['remember_me']) ? '1' : '0';
            
            // Check if endpoint file exists
            $fullPath = __DIR__ . '/' . $endpoint;
            
            echo '<h3>Step 1: Check Endpoint File</h3>';
            echo '<div class="info">Endpoint: <code>' . htmlspecialchars($endpoint) . '</code></div>';
            echo '<div class="info">Full path: <code>' . htmlspecialchars($fullPath) . '</code></div>';
            
            if (file_exists($fullPath)) {
                echo '<div class="success">✅ Endpoint file exists</div>';
                
                // Test API call
                echo '<h3>Step 2: Test API Request</h3>';
                
                // Prepare POST data
                $postData = [
                    'type' => 'user_login',
                    'user_code' => $userCode,
                    'password' => $password,
                    'remember_me' => $rememberMe
                ];
                
                echo '<div class="info">Request Data:</div>';
                echo '<pre>' . json_encode($postData, JSON_PRETTY_PRINT) . '</pre>';
                
                // Make internal request
                echo '<h3>Step 3: Send Request</h3>';
                
                try {
                    // Save current working directory
                    $originalDir = getcwd();
                    
                    // Change to endpoint directory
                    chdir(dirname($fullPath));
                    
                    // Capture output
                    ob_start();
                    
                    // Simulate POST request
                    $_POST = $postData;
                    $_SERVER['REQUEST_METHOD'] = 'POST';
                    
                    // Include the endpoint
                    include basename($fullPath);
                    
                    // Get output
                    $response = ob_get_clean();
                    
                    // Restore directory
                    chdir($originalDir);
                    
                    echo '<div class="success">✅ Request sent successfully</div>';
                    
                    // Display response
                    echo '<h3>Step 4: API Response</h3>';
                    
                    echo '<div class="info">Raw Response:</div>';
                    echo '<pre>' . htmlspecialchars($response) . '</pre>';
                    
                    // Try to parse JSON
                    $jsonData = json_decode($response, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        echo '<div class="success">✅ Valid JSON response</div>';
                        
                        echo '<div class="info">Parsed Response:</div>';
                        echo '<pre>' . json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
                        
                        // Check status
                        if (isset($jsonData['status'])) {
                            if ($jsonData['status'] === 'success') {
                                echo '<div class="success">✅ Login API returned SUCCESS!</div>';
                                
                                if (isset($jsonData['redirect'])) {
                                    echo '<div class="info">Redirect URL: <code>' . htmlspecialchars($jsonData['redirect']) . '</code></div>';
                                }
                                
                                if (isset($jsonData['user'])) {
                                    echo '<div class="info">User Data:</div>';
                                    echo '<pre>' . json_encode($jsonData['user'], JSON_PRETTY_PRINT) . '</pre>';
                                }
                                
                                echo '<div class="success">🎉 API is working correctly!</div>';
                                
                            } else {
                                echo '<div class="error">❌ Login API returned ERROR</div>';
                                
                                if (isset($jsonData['message'])) {
                                    echo '<div class="error">Error Message: ' . htmlspecialchars($jsonData['message']) . '</div>';
                                }
                            }
                        }
                        
                        // Show debug info if available
                        if (isset($jsonData['debug'])) {
                            echo '<div class="info">Debug Information:</div>';
                            echo '<pre>' . json_encode($jsonData['debug'], JSON_PRETTY_PRINT) . '</pre>';
                        }
                        
                    } else {
                        echo '<div class="error">❌ Response is NOT valid JSON</div>';
                        echo '<div class="error">JSON Error: ' . json_last_error_msg() . '</div>';
                        
                        // Check if there's any HTML in response
                        if (strpos($response, '<') !== false) {
                            echo '<div class="warning">⚠️ Response contains HTML. There may be errors or warnings before the JSON output.</div>';
                        }
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="error">❌ Exception during API test: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                }
                
            } else {
                echo '<div class="error">❌ Endpoint file NOT found!</div>';
                echo '<div class="warning">Possible locations to check:</div>';
                
                $possiblePaths = [
                    'includes/core/login_handle.php',
                    'includes/core/login_handler.php',
                    'includes/login_handle.php',
                    'core/login_handle.php',
                    'login_handle.php'
                ];
                
                echo '<ul>';
                foreach ($possiblePaths as $path) {
                    $exists = file_exists(__DIR__ . '/' . $path);
                    $status = $exists ? '✅ EXISTS' : '❌ NOT FOUND';
                    echo '<li>' . $status . ' - <code>' . $path . '</code></li>';
                }
                echo '</ul>';
            }
            
            echo '</div>';
        }
        ?>
        
        <!-- Test with JavaScript -->
        <div class="test-section">
            <h2>🔧 JavaScript Test (Browser)</h2>
            
            <p>This simulates exactly what the login page does:</p>
            
            <button onclick="testWithJavaScript()" class="btn">Test with JavaScript</button>
            
            <div id="jsResults" style="margin-top: 20px;"></div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="test_connection.php" style="display: inline-block; padding: 12px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-right: 10px;">
                Database Test
            </a>
            <a href="test_login.php" style="display: inline-block; padding: 12px 30px; background: #ffc107; color: #333; text-decoration: none; border-radius: 5px; font-weight: bold; margin-right: 10px;">
                Login Test
            </a>
            <a href="test_auth.php" style="display: inline-block; padding: 12px 30px; background: #17a2b8; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-right: 10px;">
                Auth Test
            </a>
            <a href="login.php" style="display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Go to Login
            </a>
        </div>
    </div>
    
    <script>
        async function testWithJavaScript() {
            const resultsDiv = document.getElementById('jsResults');
            const endpoint = document.getElementById('endpoint').value;
            
            resultsDiv.innerHTML = '<div class="info">🔄 Testing...</div>';
            
            try {
                const formData = new FormData();
                formData.append('type', 'user_login');
                formData.append('user_code', document.querySelector('input[name="user_code"]').value);
                formData.append('password', document.querySelector('input[name="password"]').value);
                formData.append('remember_me', document.querySelector('input[name="remember_me"]').checked ? '1' : '0');
                
                console.log('Sending request to:', endpoint);
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                const text = await response.text();
                console.log('Response text:', text);
                
                let html = '';
                html += '<div class="info">HTTP Status: <code>' + response.status + '</code></div>';
                html += '<div class="info">Content-Type: <code>' + response.headers.get('content-type') + '</code></div>';
                
                try {
                    const data = JSON.parse(text);
                    
                    html += '<div class="success">✅ Valid JSON response</div>';
                    html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    
                    if (data.status === 'success') {
                        html += '<div class="success">✅ LOGIN SUCCESSFUL!</div>';
                    } else {
                        html += '<div class="error">❌ Login failed: ' + (data.message || 'Unknown error') + '</div>';
                    }
                    
                } catch (e) {
                    html += '<div class="error">❌ Response is not valid JSON</div>';
                    html += '<div class="info">Raw response:</div>';
                    html += '<pre>' + text.substring(0, 500) + (text.length > 500 ? '...' : '') + '</pre>';
                }
                
                resultsDiv.innerHTML = html;
                
            } catch (error) {
                resultsDiv.innerHTML = '<div class="error">❌ Error: ' + error.message + '</div>';
                console.error('Error:', error);
            }
        }
    </script>
</body>
</html>
