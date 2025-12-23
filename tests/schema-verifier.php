<?php
/**
 * DATABASE SCHEMA VERIFIER
 * Checks for missing tables, columns, and provides fix suggestions
 */

require_once __DIR__ . '/includes/config/config.php';

$VERIFICATION_ENABLED = true; // Set to false to disable

if (!$VERIFICATION_ENABLED) {
    die('Schema verification is disabled. Set $VERIFICATION_ENABLED = true to enable.');
}

// Connect to database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Expected schema
$expectedSchema = [
    'user' => [
        'columns' => ['id', 'user_code', 'name', 'email', 'password', 'level', 'is_active', 'last_login', 'created_at'],
        'required' => true
    ],
    'tokens' => [
        'columns' => ['id', 'user_code', 'token', 'expires_at', 'ip_address', 'user_agent', 'created_at'],
        'required' => true
    ],
    'candidates' => [
        'columns' => ['id', 'can_code', 'candidate_name', 'email', 'contact_number', 'status', 'created_by', 'created_at'],
        'required' => true
    ],
    'jobs' => [
        'columns' => ['id', 'job_ref', 'job_title', 'job_description', 'job_status', 'job_type', 'created_by', 'created_at', 'is_public'],
        'required' => true
    ],
    'password_resets' => [
        'columns' => ['id', 'email', 'token', 'created_at'],
        'required' => false // Optional table
    ]
];

// Verification results
$results = [
    'tables_missing' => [],
    'columns_missing' => [],
    'tables_ok' => [],
    'warnings' => [],
    'errors' => []
];

// Check each table
foreach ($expectedSchema as $tableName => $schema) {
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE '$tableName'");
    
    if ($tableCheck->num_rows === 0) {
        if ($schema['required']) {
            $results['tables_missing'][] = $tableName;
            $results['errors'][] = "CRITICAL: Table '$tableName' is missing!";
        } else {
            $results['warnings'][] = "Optional table '$tableName' not found (this is OK)";
        }
        continue;
    }
    
    // Table exists - check columns
    $columnsResult = $conn->query("DESCRIBE $tableName");
    $existingColumns = [];
    while ($row = $columnsResult->fetch_assoc()) {
        $existingColumns[] = $row['Field'];
    }
    
    // Check for missing columns
    $missingColumns = array_diff($schema['columns'], $existingColumns);
    
    if (empty($missingColumns)) {
        $results['tables_ok'][] = $tableName;
    } else {
        $results['columns_missing'][$tableName] = $missingColumns;
        foreach ($missingColumns as $col) {
            $results['warnings'][] = "Column '$col' missing in table '$tableName'";
        }
    }
}

// Generate fix SQL
function generateFixSQL($results) {
    $sql = [];
    
    // Missing tables
    if (!empty($results['tables_missing'])) {
        foreach ($results['tables_missing'] as $table) {
            switch ($table) {
                case 'tokens':
                    $sql[] = "CREATE TABLE IF NOT EXISTS tokens (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_code VARCHAR(50) NOT NULL,
                        token VARCHAR(64) NOT NULL UNIQUE,
                        expires_at DATETIME NOT NULL,
                        ip_address VARCHAR(45),
                        user_agent TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX (user_code),
                        INDEX (token)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                    break;
                    
                case 'password_resets':
                    $sql[] = "CREATE TABLE IF NOT EXISTS password_resets (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        email VARCHAR(255) NOT NULL,
                        token VARCHAR(255) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX (email),
                        INDEX (token)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                    break;
            }
        }
    }
    
    // Missing columns
    if (!empty($results['columns_missing'])) {
        foreach ($results['columns_missing'] as $table => $columns) {
            foreach ($columns as $column) {
                switch ($table) {
                    case 'tokens':
                        if ($column === 'ip_address') {
                            $sql[] = "ALTER TABLE tokens ADD COLUMN ip_address VARCHAR(45) NULL AFTER expires_at;";
                        } elseif ($column === 'user_agent') {
                            $sql[] = "ALTER TABLE tokens ADD COLUMN user_agent TEXT NULL AFTER ip_address;";
                        }
                        break;
                        
                    case 'user':
                        if ($column === 'is_active') {
                            $sql[] = "ALTER TABLE user ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER level;";
                        } elseif ($column === 'last_login') {
                            $sql[] = "ALTER TABLE user ADD COLUMN last_login DATETIME NULL AFTER is_active;";
                        }
                        break;
                        
                    case 'jobs':
                        if ($column === 'is_public') {
                            $sql[] = "ALTER TABLE jobs ADD COLUMN is_public TINYINT(1) DEFAULT 0 AFTER job_type;";
                        } elseif ($column === 'job_type') {
                            $sql[] = "ALTER TABLE jobs ADD COLUMN job_type VARCHAR(50) DEFAULT 'freelance' AFTER job_status;";
                        }
                        break;
                }
            }
        }
    }
    
    return $sql;
}

$fixSQL = generateFixSQL($results);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Schema Verifier</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #4ec9b0; margin-bottom: 20px; font-size: 28px; }
        .section { background: #2d2d2d; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #4ec9b0; }
        .section h2 { color: #4ec9b0; margin-bottom: 15px; font-size: 20px; }
        .success { color: #4ec9b0; }
        .warning { color: #dcdcaa; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        ul { list-style: none; margin-left: 20px; }
        li { padding: 8px 0; }
        li:before { content: "• "; margin-right: 8px; }
        .sql-block { background: #1e1e1e; padding: 15px; border-radius: 4px; margin-top: 10px; overflow-x: auto; }
        .sql-block code { color: #ce9178; }
        .copy-btn { background: #4ec9b0; color: #1e1e1e; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-top: 10px; font-weight: bold; }
        .copy-btn:hover { background: #3da88a; }
        .status-badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-success { background: #4ec9b0; color: #1e1e1e; }
        .badge-warning { background: #dcdcaa; color: #1e1e1e; }
        .badge-error { background: #f48771; color: #1e1e1e; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #3c3c3c; }
        th { background: #1e1e1e; color: #4ec9b0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Schema Verifier</h1>
        <p style="margin-bottom: 30px; color: #8895a7;">Database: <strong><?php echo DB_NAME; ?></strong> • Host: <?php echo DB_HOST; ?></p>
        
        <!-- Overall Status -->
        <div class="section">
            <h2>📊 Overall Status</h2>
            <?php
            $hasErrors = !empty($results['tables_missing']) || !empty($results['columns_missing']);
            $hasWarnings = !empty($results['warnings']);
            
            if (!$hasErrors && !$hasWarnings) {
                echo '<p class="success">✅ <strong>ALL CHECKS PASSED!</strong> Your database schema is complete and ready.</p>';
            } elseif ($hasErrors) {
                echo '<p class="error">❌ <strong>CRITICAL ISSUES FOUND!</strong> Some required tables or columns are missing.</p>';
            } else {
                echo '<p class="warning">⚠️ <strong>WARNINGS DETECTED!</strong> Some optional components are missing.</p>';
            }
            ?>
            
            <table>
                <tr>
                    <td><strong>Tables OK:</strong></td>
                    <td><span class="badge-success status-badge"><?php echo count($results['tables_ok']); ?></span></td>
                </tr>
                <tr>
                    <td><strong>Missing Tables:</strong></td>
                    <td><span class="<?php echo empty($results['tables_missing']) ? 'badge-success' : 'badge-error'; ?> status-badge"><?php echo count($results['tables_missing']); ?></span></td>
                </tr>
                <tr>
                    <td><strong>Tables with Missing Columns:</strong></td>
                    <td><span class="<?php echo empty($results['columns_missing']) ? 'badge-success' : 'badge-warning'; ?> status-badge"><?php echo count($results['columns_missing']); ?></span></td>
                </tr>
                <tr>
                    <td><strong>Warnings:</strong></td>
                    <td><span class="<?php echo empty($results['warnings']) ? 'badge-success' : 'badge-warning'; ?> status-badge"><?php echo count($results['warnings']); ?></span></td>
                </tr>
            </table>
        </div>
        
        <!-- Tables OK -->
        <?php if (!empty($results['tables_ok'])): ?>
        <div class="section">
            <h2 class="success">✅ Tables Verified</h2>
            <p>These tables exist with all required columns:</p>
            <ul>
                <?php foreach ($results['tables_ok'] as $table): ?>
                <li class="success"><?php echo $table; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Missing Tables -->
        <?php if (!empty($results['tables_missing'])): ?>
        <div class="section">
            <h2 class="error">❌ Missing Tables</h2>
            <p>These critical tables are missing from your database:</p>
            <ul>
                <?php foreach ($results['tables_missing'] as $table): ?>
                <li class="error"><strong><?php echo $table; ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Missing Columns -->
        <?php if (!empty($results['columns_missing'])): ?>
        <div class="section">
            <h2 class="warning">⚠️ Missing Columns</h2>
            <p>These columns are missing from existing tables:</p>
            <?php foreach ($results['columns_missing'] as $table => $columns): ?>
                <p><strong><?php echo $table; ?>:</strong></p>
                <ul>
                    <?php foreach ($columns as $column): ?>
                    <li class="warning"><?php echo $column; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Fix SQL -->
        <?php if (!empty($fixSQL)): ?>
        <div class="section">
            <h2 class="info">🔧 Auto-Generated Fix SQL</h2>
            <p>Execute this SQL to fix all issues:</p>
            <div class="sql-block" id="fixSQL">
                <?php foreach ($fixSQL as $query): ?>
                <code><?php echo htmlspecialchars($query); ?></code><br><br>
                <?php endforeach; ?>
            </div>
            <button class="copy-btn" onclick="copySQL()">📋 Copy SQL</button>
            
            <div style="margin-top: 20px; padding: 15px; background: #3c3c3c; border-radius: 4px;">
                <p class="info"><strong>How to Execute:</strong></p>
                <ol style="margin-left: 20px; margin-top: 10px;">
                    <li style="margin-bottom: 8px;">Click "Copy SQL" button above</li>
                    <li style="margin-bottom: 8px;">Open terminal: <code>mysql -u <?php echo DB_USER; ?> -p <?php echo DB_NAME; ?></code></li>
                    <li style="margin-bottom: 8px;">Paste the SQL</li>
                    <li style="margin-bottom: 8px;">Press Enter</li>
                    <li>Refresh this page to verify fixes</li>
                </ol>
            </div>
        </div>
        <?php else: ?>
        <div class="section">
            <h2 class="success">✅ No Fixes Needed</h2>
            <p>Your database schema is complete!</p>
        </div>
        <?php endif; ?>
        
        <!-- Detailed Table Info -->
        <div class="section">
            <h2>📋 Detailed Schema Information</h2>
            <?php foreach ($expectedSchema as $tableName => $schema): ?>
                <h3 style="color: #569cd6; margin-top: 20px;"><?php echo $tableName; ?></h3>
                <?php
                $tableExists = $conn->query("SHOW TABLES LIKE '$tableName'")->num_rows > 0;
                if ($tableExists) {
                    echo '<p class="success">✓ Table exists</p>';
                    $cols = $conn->query("DESCRIBE $tableName");
                    echo '<table><thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr></thead><tbody>';
                    while ($col = $cols->fetch_assoc()) {
                        $isExpected = in_array($col['Field'], $schema['columns']);
                        $rowClass = $isExpected ? 'success' : 'warning';
                        echo "<tr class='$rowClass'>";
                        echo "<td>{$col['Field']}</td>";
                        echo "<td>{$col['Type']}</td>";
                        echo "<td>{$col['Null']}</td>";
                        echo "<td>{$col['Key']}</td>";
                        echo "</tr>";
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<p class="error">✗ Table does not exist</p>';
                }
                ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        function copySQL() {
            const sql = document.getElementById('fixSQL').textContent;
            const textarea = document.createElement('textarea');
            textarea.value = sql.trim();
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            const btn = event.target;
            btn.textContent = '✓ Copied!';
            btn.style.background = '#3da88a';
            setTimeout(() => {
                btn.textContent = '📋 Copy SQL';
                btn.style.background = '';
            }, 2000);
        }
    </script>
</body>
</html>
