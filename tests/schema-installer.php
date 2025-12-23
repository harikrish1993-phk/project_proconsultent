<?php
/**
 * COMPLETE DATABASE SCHEMA VERIFIER & INSTALLER
 * Includes ALL tables for recruitment system
 */

require_once __DIR__ . '/includes/config/config.php';

$VERIFICATION_ENABLED = true;

if (!$VERIFICATION_ENABLED) {
    die('Schema verification is disabled.');
}

// Database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// COMPLETE EXPECTED SCHEMA
$expectedSchema = [
    // Core Authentication
    'user' => [
        'columns' => ['id', 'user_code', 'name', 'email', 'password', 'level', 'is_active', 'last_login', 'created_at', 'updated_at'],
        'required' => true,
        'description' => 'User accounts (admin, recruiter, user)'
    ],
    'tokens' => [
        'columns' => ['id', 'user_code', 'token', 'expires_at', 'ip_address', 'user_agent', 'created_at'],
        'required' => true,
        'description' => 'Authentication tokens'
    ],
    'password_resets' => [
        'columns' => ['id', 'email', 'token', 'created_at'],
        'required' => false,
        'description' => 'Password reset tokens'
    ],
    
    // Candidates Management
    'candidates' => [
        'columns' => ['id', 'can_code', 'candidate_name', 'email', 'contact_number', 'status', 'skills', 'experience_years', 'current_location', 'preferred_location', 'notice_period', 'expected_salary', 'cv_path', 'notes', 'source', 'follow_up_date', 'created_by', 'created_at', 'updated_at'],
        'required' => true,
        'description' => 'Candidate database'
    ],
    'candidate_assignments' => [
        'columns' => ['id', 'can_code', 'usercode', 'username', 'assigned_at', 'created_at'],
        'required' => false,
        'description' => 'Candidate assignments to recruiters'
    ],
    'cv_inbox' => [
        'columns' => ['id', 'can_code', 'file_path', 'file_name', 'file_size', 'uploaded_by', 'uploaded_at', 'status'],
        'required' => false,
        'description' => 'CV file storage tracking'
    ],
    
    // Jobs Management
    'jobs' => [
        'columns' => ['id', 'job_ref', 'job_title', 'job_description', 'skills_required', 'experience_level', 'job_type', 'job_location', 'salary_min', 'salary_max', 'client_name', 'contact_person', 'contact_email', 'job_status', 'is_public', 'created_by', 'created_at', 'updated_at'],
        'required' => true,
        'description' => 'Job postings'
    ],
    
    // Applications & Matching
    'job_applications' => [
        'columns' => ['id', 'job_ref', 'can_code', 'application_date', 'status', 'notes', 'cv_path', 'source', 'created_by', 'created_at', 'updated_at'],
        'required' => true,
        'description' => 'Candidate job applications'
    ],
    
    // Client Management
    'clients' => [
        'columns' => ['id', 'client_code', 'company_name', 'contact_name', 'email', 'phone', 'address', 'city', 'country', 'industry', 'website', 'notes', 'status', 'created_by', 'created_at', 'updated_at'],
        'required' => false,
        'description' => 'Client companies'
    ],
    
    // Contact Management
    'contacts' => [
        'columns' => ['id', 'name', 'email', 'phone', 'company', 'subject', 'message', 'status', 'created_at'],
        'required' => false,
        'description' => 'Contact form submissions'
    ],
    
    // Activity & Logging
    'activity_log' => [
        'columns' => ['id', 'user_code', 'action', 'module', 'record_id', 'details', 'ip_address', 'created_at'],
        'required' => false,
        'description' => 'System activity tracking'
    ]
];

// SQL Creation Templates
$createTableSQL = [
    'user' => "CREATE TABLE IF NOT EXISTS `user` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_code` VARCHAR(50) NOT NULL UNIQUE,
        `name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `level` ENUM('admin', 'manager', 'recruiter', 'user') DEFAULT 'user',
        `is_active` TINYINT(1) DEFAULT 1,
        `last_login` DATETIME NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (`user_code`),
        INDEX (`email`),
        INDEX (`level`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    'tokens' => "CREATE TABLE IF NOT EXISTS `tokens` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_code` VARCHAR(50) NOT NULL,
        `token` VARCHAR(64) NOT NULL UNIQUE,
        `expires_at` DATETIME NOT NULL,
        `ip_address` VARCHAR(45) NULL,
        `user_agent` TEXT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`user_code`),
        INDEX (`token`),
        INDEX (`expires_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    'password_resets' => "CREATE TABLE IF NOT EXISTS `password_resets` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(255) NOT NULL,
        `token` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`email`),
        INDEX (`token`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    'candidates' => "CREATE TABLE IF NOT EXISTS `candidates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `can_code` VARCHAR(50) NOT NULL UNIQUE,
        `candidate_name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NULL,
        `contact_number` VARCHAR(50) NULL,
        `status` ENUM('active', 'inactive', 'placed', 'blacklist') DEFAULT 'active',
        `skills` TEXT NULL,
        `experience_years` INT NULL,
        `current_location` VARCHAR(255) NULL,
        `preferred_location` VARCHAR(255) NULL,
        `notice_period` VARCHAR(50) NULL,
        `expected_salary` DECIMAL(10,2) NULL,
        `cv_path` VARCHAR(500) NULL,
        `notes` TEXT NULL,
        `source` VARCHAR(100) NULL,
        `follow_up_date` DATE NULL,
        `created_by` VARCHAR(50) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (`can_code`),
        INDEX (`status`),
        INDEX (`created_by`),
        INDEX (`follow_up_date`),
        FULLTEXT (`candidate_name`, `skills`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    'candidate_assignments' => "CREATE TABLE IF NOT EXISTS `candidate_assignments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `can_code` VARCHAR(50) NOT NULL,
        `usercode` VARCHAR(50) NOT NULL,
        `username` VARCHAR(255) NOT NULL,
        `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`can_code`),
        INDEX (`usercode`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    'cv_inbox' => "CREATE TABLE IF NOT EXISTS `cv_inbox` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `can_code` VARCHAR(50) NULL,
        `file_path` VARCHAR(500) NOT NULL,
        `file_name` VARCHAR(255) NOT NULL,
        `file_size` INT NULL,
        `uploaded_by` VARCHAR(50) NULL,
        `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `status` ENUM('pending', 'processed', 'archived') DEFAULT 'pending',
        INDEX (`can_code`),
        INDEX (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    'jobs' => "CREATE TABLE IF NOT EXISTS `jobs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `job_ref` VARCHAR(50) NOT NULL UNIQUE,
        `job_title` VARCHAR(255) NOT NULL,
        `job_description` TEXT NULL,
        `skills_required` TEXT NULL,
        `experience_level` VARCHAR(50) NULL,
        `job_type` ENUM('freelance', 'permanent', 'contract') DEFAULT 'freelance',
        `job_location` VARCHAR(255) DEFAULT 'Belgium',
        `salary_min` DECIMAL(10,2) NULL,
        `salary_max` DECIMAL(10,2) NULL,
        `client_name` VARCHAR(255) NULL,
        `contact_person` VARCHAR(255) NULL,
        `contact_email` VARCHAR(255) NULL,
        `job_status` ENUM('active', 'pending', 'closed', 'filled') DEFAULT 'pending',
        `is_public` TINYINT(1) DEFAULT 0,
        `created_by` VARCHAR(50) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (`job_ref`),
        INDEX (`job_status`),
        INDEX (`is_public`),
        INDEX (`created_by`),
        FULLTEXT (`job_title`, `job_description`, `skills_required`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    'job_applications' => "CREATE TABLE IF NOT EXISTS `job_applications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `job_ref` VARCHAR(50) NOT NULL,
        `can_code` VARCHAR(50) NOT NULL,
        `application_date` DATE NOT NULL,
        `status` ENUM('applied', 'screening', 'interview', 'pending_approval', 'offered', 'rejected', 'withdrawn') DEFAULT 'applied',
        `notes` TEXT NULL,
        `cv_path` VARCHAR(500) NULL,
        `source` VARCHAR(100) NULL,
        `created_by` VARCHAR(50) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (`job_ref`),
        INDEX (`can_code`),
        INDEX (`status`),
        INDEX (`application_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    'clients' => "CREATE TABLE IF NOT EXISTS `clients` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `client_code` VARCHAR(50) NOT NULL UNIQUE,
        `company_name` VARCHAR(255) NOT NULL,
        `contact_name` VARCHAR(255) NULL,
        `email` VARCHAR(255) NULL,
        `phone` VARCHAR(50) NULL,
        `address` TEXT NULL,
        `city` VARCHAR(100) NULL,
        `country` VARCHAR(100) DEFAULT 'Belgium',
        `industry` VARCHAR(100) NULL,
        `website` VARCHAR(255) NULL,
        `notes` TEXT NULL,
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `created_by` VARCHAR(50) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (`client_code`),
        INDEX (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    'contacts' => "CREATE TABLE IF NOT EXISTS `contacts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(50) NULL,
        `company` VARCHAR(255) NULL,
        `subject` VARCHAR(255) NULL,
        `message` TEXT NULL,
        `status` ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`email`),
        INDEX (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    'activity_log' => "CREATE TABLE IF NOT EXISTS `activity_log` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_code` VARCHAR(50) NOT NULL,
        `action` VARCHAR(100) NOT NULL,
        `module` VARCHAR(50) NOT NULL,
        `record_id` VARCHAR(50) NULL,
        `details` TEXT NULL,
        `ip_address` VARCHAR(45) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`user_code`),
        INDEX (`module`),
        INDEX (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
];

// Verification
$results = [
    'tables_missing' => [],
    'columns_missing' => [],
    'tables_ok' => [],
    'warnings' => [],
    'errors' => [],
    'total_tables' => count($expectedSchema),
    'total_ok' => 0
];

foreach ($expectedSchema as $tableName => $schema) {
    $tableCheck = $conn->query("SHOW TABLES LIKE '$tableName'");
    
    if ($tableCheck->num_rows === 0) {
        if ($schema['required']) {
            $results['tables_missing'][] = $tableName;
            $results['errors'][] = "CRITICAL: Required table '$tableName' missing";
        } else {
            $results['warnings'][] = "Optional table '$tableName' not found";
        }
        continue;
    }
    
    // Check columns
    $columnsResult = $conn->query("DESCRIBE $tableName");
    $existingColumns = [];
    while ($row = $columnsResult->fetch_assoc()) {
        $existingColumns[] = $row['Field'];
    }
    
    $missingColumns = array_diff($schema['columns'], $existingColumns);
    
    if (empty($missingColumns)) {
        $results['tables_ok'][] = $tableName;
        $results['total_ok']++;
    } else {
        $results['columns_missing'][$tableName] = $missingColumns;
    }
}

// Generate fix SQL
$fixSQL = [];
foreach ($results['tables_missing'] as $table) {
    if (isset($createTableSQL[$table])) {
        $fixSQL[] = $createTableSQL[$table];
    }
}

foreach ($results['columns_missing'] as $table => $columns) {
    foreach ($columns as $column) {
        // Add column alter statements
        $alterSQL = "-- Add missing column: $table.$column\n";
        $alterSQL .= "-- ALTER TABLE $table ADD COLUMN $column ...;";
        $fixSQL[] = $alterSQL;
    }
}

$healthScore = round(($results['total_ok'] / $results['total_tables']) * 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Schema Installer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { background: white; padding: 30px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header h1 { color: #2d3748; font-size: 32px; margin-bottom: 10px; }
        .header p { color: #718096; font-size: 16px; }
        
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
        
        .section { background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .section h2 { color: #2d3748; margin-bottom: 20px; font-size: 24px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; color: #2d3748; font-weight: 600; }
        tr:hover { background: #f7fafc; }
        
        .badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .badge-success { background: #c6f6d5; color: #22543d; }
        .badge-warning { background: #feebc8; color: #7c2d12; }
        .badge-error { background: #fed7d7; color: #742a2a; }
        
        .sql-box { background: #2d3748; color: #e2e8f0; padding: 20px; border-radius: 8px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.6; }
        
        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .btn-success { background: #48bb78; color: white; }
        .btn-success:hover { background: #38a169; }
        
        .icon { margin-right: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ ProConsultancy Database Schema Installer</h1>
            <p>Database: <strong><?php echo DB_NAME; ?></strong> @ <?php echo DB_HOST; ?></p>
        </div>
        
        <div class="health-score">
            <div class="score-circle"><?php echo $healthScore; ?>%</div>
            <h2>Database Health Score</h2>
            <p><?php echo $results['total_ok']; ?> of <?php echo $results['total_tables']; ?> tables configured correctly</p>
        </div>
        
        <div class="section">
            <h2>📊 Table Status Overview</h2>
            <table>
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Required</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expectedSchema as $table => $info): ?>
                        <?php
                        $isOk = in_array($table, $results['tables_ok']);
                        $isMissing = in_array($table, $results['tables_missing']);
                        $hasMissingCols = isset($results['columns_missing'][$table]);
                        ?>
                        <tr>
                            <td><strong><?php echo $table; ?></strong></td>
                            <td><?php echo $info['description']; ?></td>
                            <td>
                                <?php if ($isOk): ?>
                                    <span class="badge badge-success">✓ OK</span>
                                <?php elseif ($isMissing): ?>
                                    <span class="badge badge-error">✗ Missing</span>
                                <?php elseif ($hasMissingCols): ?>
                                    <span class="badge badge-warning">⚠ Incomplete</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $info['required'] ? '<span class="badge badge-error">Required</span>' : '<span class="badge badge-warning">Optional</span>'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($fixSQL)): ?>
        <div class="section">
            <h2>🔧 Installation SQL</h2>
            <p style="margin-bottom: 15px;">Execute this SQL to create missing tables:</p>
            <div class="sql-box"><?php echo implode("\n\n", array_map('htmlspecialchars', $fixSQL)); ?></div>
            <button class="btn btn-primary" onclick="copySQL()" style="margin-top: 15px;">
                <span class="icon">📋</span> Copy SQL to Clipboard
            </button>
        </div>
        <?php else: ?>
        <div class="section">
            <h2 style="color: #48bb78;">✅ Database Ready!</h2>
            <p>All required tables are present and configured correctly.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function copySQL() {
            const sql = document.querySelector('.sql-box').textContent;
            navigator.clipboard.writeText(sql).then(() => {
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<span class="icon">✓</span> Copied!';
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-success');
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-primary');
                }, 2000);
            });
        }
    </script>
</body>
</html>
