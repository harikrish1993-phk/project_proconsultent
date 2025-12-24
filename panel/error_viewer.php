<?php
/**
 * SIMPLE ERROR LOGGER
 * File: panel/error_viewer.php
 * 
 * This page shows PHP errors and helps debug issues
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get error log location
$errorLog = ini_get('error_log');
if (empty($errorLog)) {
    $errorLog = '/var/log/apache2/error.log'; // Default Apache log
}

// Alternative locations to check
$possibleLogs = [
    ini_get('error_log'),
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    '/var/log/php-fpm/error.log',
    '/var/log/php/error.log',
    __DIR__ . '/../../logs/error.log',
    __DIR__ . '/../../error.log'
];

$foundLogs = [];
foreach ($possibleLogs as $log) {
    if ($log && file_exists($log) && is_readable($log)) {
        $foundLogs[] = $log;
    }
}

// Read log file
$logContent = '';
$logFile = $_GET['log'] ?? ($foundLogs[0] ?? '');
$lines = isset($_GET['lines']) ? intval($_GET['lines']) : 100;

if ($logFile && file_exists($logFile) && is_readable($logFile)) {
    try {
        $handle = fopen($logFile, 'r');
        if ($handle) {
            // Read last N lines
            $buffer = [];
            while (($line = fgets($handle)) !== false) {
                $buffer[] = $line;
                if (count($buffer) > $lines) {
                    array_shift($buffer);
                }
            }
            fclose($handle);
            $logContent = implode('', $buffer);
        }
    } catch (Exception $e) {
        $logContent = "Error reading log: " . $e->getMessage();
    }
} else {
    $logContent = "No readable log file found. Checked:\n" . implode("\n", $possibleLogs);
}

// Get PHP info
ob_start();
phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
$phpInfo = ob_get_clean();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Viewer - ProConsultancy</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1a202c;
            color: #e2e8f0;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: #2d3748;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e53e3e;
        }
        .header h1 {
            color: #e53e3e;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .card {
            background: #2d3748;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .card h2 {
            color: #63b3ed;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 1px solid #4a5568;
            padding-bottom: 10px;
        }
        .log-viewer {
            background: #1a202c;
            border: 1px solid #4a5568;
            border-radius: 4px;
            padding: 15px;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.6;
            max-height: 600px;
            overflow-y: auto;
        }
        .log-line {
            margin: 2px 0;
        }
        .log-line.error {
            color: #fc8181;
        }
        .log-line.warning {
            color: #f6ad55;
        }
        .log-line.notice {
            color: #63b3ed;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4299e1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #3182ce;
        }
        .btn-danger {
            background: #e53e3e;
        }
        .btn-danger:hover {
            background: #c53030;
        }
        select {
            padding: 8px 12px;
            background: #4a5568;
            color: white;
            border: 1px solid #718096;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #4a5568;
        }
        table td:first-child {
            width: 200px;
            font-weight: bold;
            color: #63b3ed;
        }
        .info-box {
            background: #4a5568;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .info-box code {
            color: #68d391;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🐛 Error Viewer & Debugger</h1>
            <p>Real-time error log viewer for ProConsultancy</p>
        </div>

        <!-- Log Selector -->
        <div class="card">
            <h2>📁 Select Log File</h2>
            <form method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <select name="log" onchange="this.form.submit()">
                    <option value="">-- Select Log File --</option>
                    <?php foreach ($foundLogs as $log): ?>
                        <option value="<?php echo htmlspecialchars($log); ?>" 
                                <?php echo $log === $logFile ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($log); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="lines" onchange="this.form.submit()">
                    <option value="50" <?php echo $lines === 50 ? 'selected' : ''; ?>>Last 50 lines</option>
                    <option value="100" <?php echo $lines === 100 ? 'selected' : ''; ?>>Last 100 lines</option>
                    <option value="200" <?php echo $lines === 200 ? 'selected' : ''; ?>>Last 200 lines</option>
                    <option value="500" <?php echo $lines === 500 ? 'selected' : ''; ?>>Last 500 lines</option>
                </select>
                
                <button type="submit" class="btn">🔄 Refresh</button>
                <a href="error_viewer.php" class="btn">🔃 Reload Page</a>
            </form>
        </div>

        <!-- Found Logs -->
        <div class="card">
            <h2>📋 Found Log Files</h2>
            <?php if (!empty($foundLogs)): ?>
                <ul style="list-style: none;">
                    <?php foreach ($foundLogs as $log): ?>
                        <li style="padding: 8px; margin: 4px 0; background: #4a5568; border-radius: 4px;">
                            <code><?php echo htmlspecialchars($log); ?></code>
                            <?php if (file_exists($log)): ?>
                                <span style="color: #68d391;"> ✅ (<?php echo number_format(filesize($log)); ?> bytes)</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="info-box" style="color: #fc8181;">
                    ❌ No log files found. Checked locations:
                    <pre style="margin-top: 10px;"><?php echo implode("\n", $possibleLogs); ?></pre>
                </div>
            <?php endif; ?>
        </div>

        <!-- Log Content -->
        <?php if ($logFile): ?>
        <div class="card">
            <h2>📄 Log Content: <?php echo htmlspecialchars($logFile); ?></h2>
            <div class="log-viewer">
                <?php
                if ($logContent) {
                    $lines = explode("\n", $logContent);
                    foreach ($lines as $line) {
                        $class = '';
                        if (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false) {
                            $class = 'error';
                        } elseif (stripos($line, 'warning') !== false) {
                            $class = 'warning';
                        } elseif (stripos($line, 'notice') !== false) {
                            $class = 'notice';
                        }
                        
                        echo '<div class="log-line ' . $class . '">' . htmlspecialchars($line) . '</div>';
                    }
                } else {
                    echo '<div style="color: #a0aec0;">No log content available or file is empty.</div>';
                }
                ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- PHP Configuration -->
        <div class="card">
            <h2>⚙️ PHP Configuration</h2>
            <table>
                <tr>
                    <td>PHP Version</td>
                    <td><code><?php echo PHP_VERSION; ?></code></td>
                </tr>
                <tr>
                    <td>Error Reporting</td>
                    <td><code><?php echo error_reporting(); ?></code></td>
                </tr>
                <tr>
                    <td>Display Errors</td>
                    <td><code><?php echo ini_get('display_errors') ? 'On' : 'Off'; ?></code></td>
                </tr>
                <tr>
                    <td>Log Errors</td>
                    <td><code><?php echo ini_get('log_errors') ? 'On' : 'Off'; ?></code></td>
                </tr>
                <tr>
                    <td>Error Log Location</td>
                    <td><code><?php echo ini_get('error_log') ?: 'Not set'; ?></code></td>
                </tr>
                <tr>
                    <td>Memory Limit</td>
                    <td><code><?php echo ini_get('memory_limit'); ?></code></td>
                </tr>
                <tr>
                    <td>Max Execution Time</td>
                    <td><code><?php echo ini_get('max_execution_time'); ?>s</code></td>
                </tr>
                <tr>
                    <td>Upload Max Filesize</td>
                    <td><code><?php echo ini_get('upload_max_filesize'); ?></code></td>
                </tr>
            </table>
        </div>

        <!-- Test Error Generation -->
        <div class="card">
            <h2>🧪 Test Error Generation</h2>
            <p style="margin-bottom: 15px; color: #a0aec0;">Click to generate test errors and see if logging works:</p>
            <form method="POST">
                <button type="submit" name="test_error" value="notice" class="btn">Generate Notice</button>
                <button type="submit" name="test_error" value="warning" class="btn">Generate Warning</button>
                <button type="submit" name="test_error" value="error" class="btn btn-danger">Generate Error</button>
            </form>
            
            <?php
            if (isset($_POST['test_error'])) {
                $type = $_POST['test_error'];
                error_log("=== TEST ERROR ($type) === Generated at " . date('Y-m-d H:i:s'));
                
                switch ($type) {
                    case 'notice':
                        trigger_error("This is a test NOTICE", E_USER_NOTICE);
                        break;
                    case 'warning':
                        trigger_error("This is a test WARNING", E_USER_WARNING);
                        break;
                    case 'error':
                        trigger_error("This is a test ERROR", E_USER_ERROR);
                        break;
                }
                
                echo '<div class="info-box" style="margin-top: 15px; background: #2d3748; border-left: 4px solid #68d391;">
                        ✅ Test error generated. Refresh this page to see it in the logs above.
                      </div>';
            }
            ?>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h2>🚀 Quick Actions</h2>
            <a href="../modules/candidates/index.php" class="btn">📋 Candidates Module</a>
            <a href="../admin.php" class="btn">🏠 Dashboard</a>
            <a href="javascript:location.reload()" class="btn">🔄 Refresh Logs</a>
        </div>
    </div>
</body>
</html>
