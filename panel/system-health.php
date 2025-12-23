<?php
/**
 * SYSTEM HEALTH CHECKER
 * File: panel/system-health.php
 * 
 * Admin-only tool to diagnose system issues
 */

require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/core/Auth.php';
require_once __DIR__ . '/../includes/core/Database.php';

// Admin only
if (!Auth::check() || Auth::user()['level'] !== 'admin') {
    http_response_code(403);
    die('Access denied. Administrator privileges required.');
}

// Run health checks
$checks = [];

// 1. Database Connection
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $checks['database'] = [
        'status' => 'ok',
        'message' => 'Database connection successful'
    ];
} catch (Exception $e) {
    $checks['database'] = [
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ];
}

// 2. Required Tables
$requiredTables = ['user', 'tokens', 'candidates', 'jobs', 'job_applications'];
$missingTables = [];
if (isset($conn)) {
    foreach ($requiredTables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows === 0) {
            $missingTables[] = $table;
        }
    }
}

$checks['tables'] = [
    'status' => empty($missingTables) ? 'ok' : 'error',
    'message' => empty($missingTables) ? 'All required tables exist' : 'Missing tables: ' . implode(', ', $missingTables)
];

// 3. Logs Directory
$logsDir = ROOT_PATH . '/logs';
if (!file_exists($logsDir)) {
    $checks['logs'] = [
        'status' => 'warning',
        'message' => 'Logs directory does not exist'
    ];
} elseif (!is_writable($logsDir)) {
    $checks['logs'] = [
        'status' => 'error',
        'message' => 'Logs directory is not writable'
    ];
} else {
    $checks['logs'] = [
        'status' => 'ok',
        'message' => 'Logs directory exists and is writable'
    ];
}

// 4. Session Working
if (session_status() === PHP_SESSION_ACTIVE) {
    $checks['session'] = [
        'status' => 'ok',
        'message' => 'Session is active'
    ];
} else {
    $checks['session'] = [
        'status' => 'warning',
        'message' => 'Session not started'
    ];
}

// 5. File Permissions
$criticalFiles = [
    '/includes/config/config.php',
    '/includes/core/Auth.php',
    '/includes/core/Database.php',
    '/panel/modules/_common.php'
];

$permissionIssues = [];
foreach ($criticalFiles as $file) {
    $fullPath = ROOT_PATH . $file;
    if (!file_exists($fullPath)) {
        $permissionIssues[] = "$file (not found)";
    } elseif (!is_readable($fullPath)) {
        $permissionIssues[] = "$file (not readable)";
    }
}

$checks['permissions'] = [
    'status' => empty($permissionIssues) ? 'ok' : 'error',
    'message' => empty($permissionIssues) ? 'All critical files accessible' : 'Issues: ' . implode(', ', $permissionIssues)
];

// 6. PHP Version
$phpVersion = phpversion();
$checks['php_version'] = [
    'status' => version_compare($phpVersion, '7.4', '>=') ? 'ok' : 'warning',
    'message' => "PHP Version: $phpVersion"
];

// Calculate overall health
$totalChecks = count($checks);
$passedChecks = count(array_filter($checks, function($check) {
    return $check['status'] === 'ok';
}));
$healthScore = round(($passedChecks / $totalChecks) * 100);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health Check - <?php echo COMPANY_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 30px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header h1 { color: #2d3748; font-size: 28px; margin-bottom: 10px; }
        .header p { color: #718096; }
        
        .health-score {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: white;
            background: <?php echo $healthScore >= 80 ? 'linear-gradient(135deg, #48bb78 0%, #38a169 100%)' : ($healthScore >= 50 ? 'linear-gradient(135deg, #ed8936 0%, #dd6b20 100%)' : 'linear-gradient(135deg, #f56565 0%, #e53e3e 100%)'); ?>;
        }
        
        .check-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .check-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 20px;
        }
        .check-icon.ok { background: #c6f6d5; color: #22543d; }
        .check-icon.warning { background: #feebc8; color: #7c2d12; }
        .check-icon.error { background: #fed7d7; color: #742a2a; }
        .check-content { flex: 1; }
        .check-title { font-weight: 600; color: #2d3748; margin-bottom: 5px; }
        .check-message { color: #718096; font-size: 14px; }
        
        .actions {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 5px;
            font-weight: 600;
        }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 System Health Check</h1>
            <p>Database: <?php echo DB_NAME; ?> @ <?php echo DB_HOST; ?></p>
        </div>
        
        <div class="health-score">
            <div class="score-circle"><?php echo $healthScore; ?>%</div>
            <h2>Overall System Health</h2>
            <p><?php echo $passedChecks; ?> of <?php echo $totalChecks; ?> checks passed</p>
        </div>
        
        <?php foreach ($checks as $name => $check): ?>
        <div class="check-item">
            <div class="check-icon <?php echo $check['status']; ?>">
                <?php 
                echo $check['status'] === 'ok' ? '✓' : ($check['status'] === 'warning' ? '⚠' : '✗');
                ?>
            </div>
            <div class="check-content">
                <div class="check-title"><?php echo ucfirst(str_replace('_', ' ', $name)); ?></div>
                <div class="check-message"><?php echo htmlspecialchars($check['message']); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div class="actions">
            <a href="view-logs.php" class="btn">📋 View Logs</a>
            <a href="../schema-installer.php" target="_blank" class="btn">🗄️ Database Schema</a>
            <a href="admin.php" class="btn">🏠 Dashboard</a>
            <a href="?refresh=1" class="btn">🔄 Refresh</a>
        </div>
    </div>
</body>
</html>