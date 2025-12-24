<?php
/**
 * MINIMAL WORKING CANDIDATES INDEX
 * File: panel/modules/candidates/index.php
 * 
 * This is a MINIMAL test page to validate:
 * 1. Authentication works
 * 2. Database connection works
 * 3. Basic page rendering works
 * 4. Module structure is correct
 */

// Load common bootstrap
require_once __DIR__ . '/../_common.php';

// Page configuration
$pageTitle = 'Candidates Module';

// Test database connection
$dbStatus = 'Unknown';
$candidateCount = 0;
$errorMessage = '';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $dbStatus = '✅ Connected';
    
    // Get candidate count
    $result = $conn->query("SELECT COUNT(*) as total FROM candidates");
    if ($result) {
        $row = $result->fetch_assoc();
        $candidateCount = $row['total'];
    }
} catch (Exception $e) {
    $dbStatus = '❌ Error: ' . $e->getMessage();
    $errorMessage = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ProConsultancy</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .status-item {
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .status-item.success { border-left-color: #48bb78; background: #f0fff4; }
        .status-item.error { border-left-color: #e53e3e; background: #fff5f5; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 5px;
            font-weight: 500;
        }
        .btn:hover { background: #5568d3; }
        .btn-success { background: #48bb78; }
        .btn-success:hover { background: #38a169; }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .info-box {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        .info-box h3 { color: #667eea; font-size: 32px; margin-bottom: 10px; }
        .info-box p { color: #718096; font-size: 14px; }
        pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 Candidates Module - Test Page</h1>
            <p>Minimal validation page to test core functionality</p>
        </div>

        <!-- System Status -->
        <div class="card">
            <h2>System Status Check</h2>
            
            <div class="status-item success">
                <strong>✅ Authentication:</strong> You are logged in as 
                <strong><?php echo htmlspecialchars($current_user_name); ?></strong>
                (<?php echo htmlspecialchars($current_user_level); ?>)
            </div>
            
            <div class="status-item <?php echo strpos($dbStatus, '✅') !== false ? 'success' : 'error'; ?>">
                <strong>Database Connection:</strong> <?php echo $dbStatus; ?>
            </div>
            
            <?php if ($errorMessage): ?>
            <div class="status-item error">
                <strong>Error Details:</strong>
                <pre><?php echo htmlspecialchars($errorMessage); ?></pre>
            </div>
            <?php endif; ?>
            
            <div class="status-item success">
                <strong>✅ Module Loading:</strong> _common.php loaded successfully
            </div>
            
            <div class="status-item success">
                <strong>✅ Configuration:</strong> Constants loaded (ROOT_PATH: <?php echo ROOT_PATH; ?>)
            </div>
        </div>

        <!-- Statistics -->
        <div class="card">
            <h2>Module Statistics</h2>
            <div class="info-grid">
                <div class="info-box">
                    <h3><?php echo $candidateCount; ?></h3>
                    <p>Total Candidates</p>
                </div>
                <div class="info-box">
                    <h3><?php echo $current_user_level; ?></h3>
                    <p>Your Access Level</p>
                </div>
                <div class="info-box">
                    <h3><?php echo date('H:i:s'); ?></h3>
                    <p>Current Time</p>
                </div>
            </div>
        </div>

        <!-- Session Info -->
        <div class="card">
            <h2>Session Information</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 10px; font-weight: bold;">User Code:</td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($current_user_code); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 10px; font-weight: bold;">Name:</td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($current_user_name); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 10px; font-weight: bold;">Email:</td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($current_user_email); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 10px; font-weight: bold;">Level:</td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($current_user_level); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px; font-weight: bold;">Token:</td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars(substr(Auth::token(), 0, 20)) . '...'; ?></td>
                </tr>
            </table>
        </div>

        <!-- Available Pages -->
        <div class="card">
            <h2>Module Pages</h2>
            <p style="margin-bottom: 20px;">If this page loaded successfully, you can now test the other pages:</p>
            
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <a href="list.php" class="btn">📋 List Candidates</a>
                <a href="create.php" class="btn btn-success">➕ Create Candidate</a>
                <a href="dashboard.php" class="btn">📊 Dashboard</a>
                <a href="../../admin.php" class="btn">🏠 Back to Admin</a>
                <a href="../../logout.php" class="btn" style="background: #e53e3e;">🚪 Logout</a>
            </div>
        </div>

        <!-- Test Queries -->
        <div class="card">
            <h2>Database Test Queries</h2>
            <?php if ($dbStatus === '✅ Connected'): ?>
            <div style="margin: 15px 0;">
                <strong>Candidates Table:</strong>
                <?php
                try {
                    $result = $conn->query("SELECT candidate_code, first_name, last_name, email, status FROM candidates LIMIT 5");
                    if ($result && $result->num_rows > 0) {
                        echo "<table style='width: 100%; margin-top: 10px; border-collapse: collapse;'>";
                        echo "<tr style='background: #f8f9fa; border-bottom: 2px solid #e2e8f0;'>";
                        echo "<th style='padding: 10px; text-align: left;'>Code</th>";
                        echo "<th style='padding: 10px; text-align: left;'>Name</th>";
                        echo "<th style='padding: 10px; text-align: left;'>Email</th>";
                        echo "<th style='padding: 10px; text-align: left;'>Status</th>";
                        echo "</tr>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr style='border-bottom: 1px solid #e2e8f0;'>";
                            echo "<td style='padding: 10px;'>" . htmlspecialchars($row['candidate_code']) . "</td>";
                            echo "<td style='padding: 10px;'>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
                            echo "<td style='padding: 10px;'>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td style='padding: 10px;'><span style='background: #48bb78; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;'>" . htmlspecialchars($row['status']) . "</span></td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<p style='color: #718096; padding: 10px; background: #f8f9fa; border-radius: 4px;'>No candidates found in database. Click 'Create Candidate' to add one.</p>";
                    }
                } catch (Exception $e) {
                    echo "<div style='color: #e53e3e; padding: 10px; background: #fff5f5; border-radius: 4px;'>";
                    echo "Query Error: " . htmlspecialchars($e->getMessage());
                    echo "</div>";
                }
                ?>
            </div>
            <?php else: ?>
            <p style="color: #e53e3e;">Cannot run queries - database not connected</p>
            <?php endif; ?>
        </div>

        <!-- Debug Info -->
        <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
        <div class="card">
            <h2>Debug Information</h2>
            <pre><?php
            echo "PHP Version: " . PHP_VERSION . "\n";
            echo "ROOT_PATH: " . ROOT_PATH . "\n";
            echo "File: " . __FILE__ . "\n";
            echo "User Variables Available:\n";
            echo "  - \$current_user_code: " . $current_user_code . "\n";
            echo "  - \$current_user_name: " . $current_user_name . "\n";
            echo "  - \$current_user_email: " . $current_user_email . "\n";
            echo "  - \$current_user_level: " . $current_user_level . "\n";
            ?></pre>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>