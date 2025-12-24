<?php
/**
 * BULLETPROOF CANDIDATES INDEX
 * This page will ALWAYS load, even if:
 * - Database tables are missing
 * - Configuration is broken
 * - Auth fails
 * - Any other error occurs
 */

// Enable all error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Capture all errors
$errors = [];
$warnings = [];
$success = [];

// Step 1: Try to load common bootstrap
$bootstrapLoaded = false;
try {
    if (file_exists(__DIR__ . '/../_common.php')) {
        require_once __DIR__ . '/../_common.php';
        $bootstrapLoaded = true;
        $success[] = "✅ Bootstrap loaded successfully";
    } else {
        $errors[] = "❌ _common.php not found at: " . __DIR__ . '/../_common.php';
    }
} catch (Exception $e) {
    $errors[] = "❌ Bootstrap error: " . $e->getMessage();
} catch (Error $e) {
    $errors[] = "❌ Bootstrap fatal error: " . $e->getMessage();
}

// Step 2: Check if we're authenticated
$isAuthenticated = false;
$userData = [
    'user_code' => 'UNKNOWN',
    'name' => 'Unknown User',
    'email' => 'unknown@example.com',
    'level' => 'unknown'
];

if ($bootstrapLoaded) {
    try {
        if (class_exists('Auth') && method_exists('Auth', 'check')) {
            $isAuthenticated = Auth::check();
            if ($isAuthenticated) {
                $success[] = "✅ User is authenticated";
                $user = Auth::user();
                if ($user) {
                    $userData = [
                        'user_code' => $user['user_code'] ?? 'N/A',
                        'name' => $user['name'] ?? 'N/A',
                        'email' => $user['email'] ?? 'N/A',
                        'level' => $user['level'] ?? 'N/A'
                    ];
                }
            } else {
                $warnings[] = "⚠️ User not authenticated";
            }
        } else {
            $warnings[] = "⚠️ Auth class not available";
        }
    } catch (Exception $e) {
        $warnings[] = "⚠️ Auth check error: " . $e->getMessage();
    }
} else {
    $warnings[] = "⚠️ Cannot check authentication - bootstrap not loaded";
}

// Step 3: Try database connection
$dbConnected = false;
$dbError = '';
$conn = null;

try {
    if (class_exists('Database')) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        if ($conn && !$conn->connect_error) {
            $dbConnected = true;
            $success[] = "✅ Database connected";
        } else {
            $dbError = $conn->connect_error ?? 'Connection failed';
            $errors[] = "❌ Database connection error: " . $dbError;
        }
    } else {
        $errors[] = "❌ Database class not found";
    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
    $errors[] = "❌ Database connection exception: " . $dbError;
}

// Step 4: Check if tables exist
$tables = [
    'user' => false,
    'candidates' => false,
    'jobs' => false,
    'job_applications' => false,
    'companies' => false,
    'tokens' => false,
    'work_auth' => false,
    'activity_log' => false
];

if ($dbConnected && $conn) {
    foreach ($tables as $tableName => $exists) {
        try {
            $result = $conn->query("SHOW TABLES LIKE '$tableName'");
            if ($result && $result->num_rows > 0) {
                $tables[$tableName] = true;
                $success[] = "✅ Table '$tableName' exists";
            } else {
                $warnings[] = "⚠️ Table '$tableName' does not exist";
            }
        } catch (Exception $e) {
            $warnings[] = "⚠️ Cannot check table '$tableName': " . $e->getMessage();
        }
    }
}

// Step 5: Try to get candidate count (safely)
$candidateCount = 0;
$candidateSamples = [];

if ($dbConnected && $tables['candidates']) {
    try {
        $result = $conn->query("SELECT COUNT(*) as total FROM candidates");
        if ($result) {
            $row = $result->fetch_assoc();
            $candidateCount = $row['total'];
            $success[] = "✅ Retrieved candidate count: $candidateCount";
        }
        
        // Get sample candidates
        $result = $conn->query("SELECT candidate_code, first_name, last_name, email, status FROM candidates LIMIT 5");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $candidateSamples[] = $row;
            }
        }
    } catch (Exception $e) {
        $warnings[] = "⚠️ Cannot retrieve candidates: " . $e->getMessage();
    }
}

// Step 6: Get PHP and system info
$systemInfo = [
    'PHP Version' => PHP_VERSION,
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'Script Filename' => __FILE__,
    'Current Directory' => __DIR__,
    'ROOT_PATH' => defined('ROOT_PATH') ? ROOT_PATH : 'Not defined',
    'DB_HOST' => defined('DB_HOST') ? DB_HOST : 'Not defined',
    'DB_NAME' => defined('DB_NAME') ? DB_NAME : 'Not defined',
    'COMPANY_NAME' => defined('COMPANY_NAME') ? COMPANY_NAME : 'Not defined'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates Module - Diagnostic Page</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .header h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .header p {
            color: #718096;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .card h2 {
            color: #2d3748;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .status-list {
            list-style: none;
        }
        .status-list li {
            padding: 12px 15px;
            margin: 8px 0;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .status-list li.success {
            background: #f0fff4;
            border-left: 4px solid #48bb78;
            color: #22543d;
        }
        .status-list li.error {
            background: #fff5f5;
            border-left: 4px solid #e53e3e;
            color: #742a2a;
        }
        .status-list li.warning {
            background: #fffaf0;
            border-left: 4px solid #ed8936;
            color: #7c2d12;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .info-box {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #e2e8f0;
        }
        .info-box.success { border-color: #48bb78; }
        .info-box.error { border-color: #e53e3e; }
        .info-box h3 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        .info-box.success h3 { color: #48bb78; }
        .info-box.error h3 { color: #e53e3e; }
        .info-box p {
            color: #718096;
            font-size: 14px;
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
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-success { background: #48bb78; }
        .btn-success:hover { background: #38a169; }
        .btn-danger { background: #e53e3e; }
        .btn-danger:hover { background: #c53030; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        table th {
            background: #f7fafc;
            font-weight: 600;
            color: #2d3748;
        }
        table tr:hover {
            background: #f7fafc;
        }
        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #48bb78; color: white; }
        .badge-error { background: #e53e3e; color: white; }
        .badge-warning { background: #ed8936; color: white; }
        .badge-info { background: #4299e1; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 ProConsultancy - System Diagnostic Page</h1>
            <p>This page will ALWAYS load - even if there are errors</p>
            <p style="margin-top: 10px; font-size: 12px; color: #a0aec0;">
                Page loaded at: <?php echo date('Y-m-d H:i:s'); ?>
            </p>
        </div>

        <!-- Status Overview -->
        <div class="card">
            <h2>📊 System Status Overview</h2>
            <div class="info-grid">
                <div class="info-box <?php echo $bootstrapLoaded ? 'success' : 'error'; ?>">
                    <h3><?php echo $bootstrapLoaded ? '✅' : '❌'; ?></h3>
                    <p>Bootstrap</p>
                </div>
                <div class="info-box <?php echo $isAuthenticated ? 'success' : 'error'; ?>">
                    <h3><?php echo $isAuthenticated ? '✅' : '❌'; ?></h3>
                    <p>Authentication</p>
                </div>
                <div class="info-box <?php echo $dbConnected ? 'success' : 'error'; ?>">
                    <h3><?php echo $dbConnected ? '✅' : '❌'; ?></h3>
                    <p>Database</p>
                </div>
                <div class="info-box success">
                    <h3><?php echo $candidateCount; ?></h3>
                    <p>Candidates</p>
                </div>
            </div>
        </div>

        <!-- Success Messages -->
        <?php if (!empty($success)): ?>
        <div class="card">
            <h2>✅ Success Messages</h2>
            <ul class="status-list">
                <?php foreach ($success as $msg): ?>
                <li class="success"><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Warnings -->
        <?php if (!empty($warnings)): ?>
        <div class="card">
            <h2>⚠️ Warnings</h2>
            <ul class="status-list">
                <?php foreach ($warnings as $msg): ?>
                <li class="warning"><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Errors -->
        <?php if (!empty($errors)): ?>
        <div class="card">
            <h2>❌ Errors</h2>
            <ul class="status-list">
                <?php foreach ($errors as $msg): ?>
                <li class="error"><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- User Information -->
        <div class="card">
            <h2>👤 User Information</h2>
            <table>
                <tr>
                    <th>Property</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>User Code</td>
                    <td><code><?php echo htmlspecialchars($userData['user_code']); ?></code></td>
                </tr>
                <tr>
                    <td>Name</td>
                    <td><?php echo htmlspecialchars($userData['name']); ?></td>
                </tr>
                <tr>
                    <td>Email</td>
                    <td><?php echo htmlspecialchars($userData['email']); ?></td>
                </tr>
                <tr>
                    <td>Level</td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars($userData['level']); ?></span></td>
                </tr>
            </table>
        </div>

        <!-- Database Tables Status -->
        <div class="card">
            <h2>🗄️ Database Tables Status</h2>
            <table>
                <tr>
                    <th>Table Name</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($tables as $tableName => $exists): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($tableName); ?></code></td>
                    <td>
                        <?php if ($exists): ?>
                            <span class="badge badge-success">✅ Exists</span>
                        <?php else: ?>
                            <span class="badge badge-error">❌ Missing</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Sample Candidates -->
        <?php if (!empty($candidateSamples)): ?>
        <div class="card">
            <h2>📋 Sample Candidates (First 5)</h2>
            <table>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($candidateSamples as $candidate): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($candidate['candidate_code']); ?></code></td>
                    <td><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($candidate['email']); ?></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars($candidate['status']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <!-- System Information -->
        <div class="card">
            <h2>⚙️ System Information</h2>
            <table>
                <?php foreach ($systemInfo as $key => $value): ?>
                <tr>
                    <td style="width: 30%; font-weight: 600;"><?php echo htmlspecialchars($key); ?></td>
                    <td><code><?php echo htmlspecialchars($value); ?></code></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Actions -->
        <div class="card">
            <h2>🚀 Available Actions</h2>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <?php if ($dbConnected && $tables['candidates']): ?>
                    <a href="create.php" class="btn btn-success">➕ Create Candidate</a>
                    <a href="list.php" class="btn">📋 View List</a>
                <?php endif; ?>
                <a href="index.php" class="btn">🔄 Reload Page</a>
                <a href="../../admin.php" class="btn">🏠 Dashboard</a>
                <?php if ($isAuthenticated): ?>
                    <a href="../../logout.php" class="btn btn-danger">🚪 Logout</a>
                <?php else: ?>
                    <a href="../../../login.php" class="btn">🔐 Login</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- SQL to Create Missing Tables -->
        <?php 
        $missingTables = array_filter($tables, function($exists) { return !$exists; });
        if (!empty($missingTables)): 
        ?>
        <div class="card">
            <h2>🔧 SQL to Create Missing Tables</h2>
            <p style="margin-bottom: 15px; color: #718096;">Copy and run this SQL to create missing tables:</p>
            <div class="code-block">
<?php if (!$tables['candidates']): ?>
CREATE TABLE candidates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  candidate_code VARCHAR(50) UNIQUE NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50),
  status ENUM('new', 'active', 'screening', 'interviewing', 'hired', 'rejected') DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
<?php endif; ?>

<?php if (!$tables['work_auth']): ?>
CREATE TABLE work_auth (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  candidate_id INT UNSIGNED NOT NULL,
  auth_status VARCHAR(100) NOT NULL,
  country VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
<?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Debug Information -->
        <div class="card">
            <h2>🐛 Debug Information</h2>
            <div class="code-block">
Error Reporting: <?php echo error_reporting(); ?>

Display Errors: <?php echo ini_get('display_errors'); ?>

Log Errors: <?php echo ini_get('log_errors'); ?>

Error Log: <?php echo ini_get('error_log') ?: 'Not set'; ?>

Memory Limit: <?php echo ini_get('memory_limit'); ?>

Max Execution Time: <?php echo ini_get('max_execution_time'); ?>s

Upload Max Filesize: <?php echo ini_get('upload_max_filesize'); ?>

Session Status: <?php echo session_status(); ?> (1=disabled, 2=active)
            </div>
        </div>
    </div>
</body>
</html>