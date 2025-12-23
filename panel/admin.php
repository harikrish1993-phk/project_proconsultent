<?php
/**
 * ADMIN DASHBOARD
 * Location: panel/admin.php
 */

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Load bootstrap
require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/core/Auth.php';
require_once __DIR__ . '/../includes/core/Database.php';



// Check authentication
if (!Auth::check()) {
    header('Location: ../login.php');
    exit();
}

$user = Auth::user();

// Admin-only access
if ($user['level'] !== 'admin') {
    header('Location: recruiter.php');
    exit();
}

// Database connection
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// Helper function
function safeQuery($conn, $query, $context = '') {
    $result = $conn->query($query);
    if (!$result) {
        error_log("Query Error ($context): " . $conn->error);
        error_log("SQL: " . $query);
        return false;
    }
    return $result;
}

// Get statistics
$stats = [
    'total_candidates' => 0,
    'active_candidates' => 0,
    'total_jobs' => 0,
    'active_jobs' => 0,
    'total_applications' => 0,
    'total_users' => 0,
    'active_users' => 0
];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM candidates", "Total Candidates");
if ($result) $stats['total_candidates'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM candidates WHERE status = 'active'", "Active Candidates");
if ($result) $stats['active_candidates'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM jobs", "Total Jobs");
if ($result) $stats['total_jobs'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM jobs WHERE job_status = 'active'", "Active Jobs");
if ($result) $stats['active_jobs'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM job_applications", "Total Applications");
if ($result) $stats['total_applications'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM user", "Total Users");
if ($result) $stats['total_users'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM user WHERE is_active = 1", "Active Users");
if ($result) $stats['active_users'] = $result->fetch_assoc()['total'];

$conn->close();

// Set page title for header
$pageTitle = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo defined('COMPANY_NAME') ? COMPANY_NAME : 'ProConsultancy'; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: #f5f7fa; 
            color: #2d3748; 
        }
        .page-wrapper { display: flex; min-height: 100vh; }
        .main-content { flex: 1; margin-left: 260px; padding: 30px; }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }
        .page-header h1 { font-size: 28px; margin-bottom: 8px; }
        .page-header p { opacity: 0.95; font-size: 15px; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
        }
        .stat-card.success { border-left-color: #48bb78; }
        .stat-card.info { border-left-color: #4299e1; }
        .stat-card.warning { border-left-color: #ed8936; }
        .stat-label {
            font-size: 13px;
            color: #718096;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .stat-value { font-size: 36px; font-weight: 700; color: #2d3748; }
        .stat-subtitle { font-size: 13px; color: #a0aec0; margin-top: 8px; }
        
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 15px; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php 
        // Include sidebar
        $sidebarPath = __DIR__ . '/includes/sidebar.php';
        if (file_exists($sidebarPath)) {
            include $sidebarPath;
        } else {
            echo '<div style="background:#fee;padding:20px;color:#c00;">Sidebar not found at: ' . $sidebarPath . '</div>';
        }
        ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>👋 Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                <p>Admin Dashboard • <?php echo date('l, F j, Y'); ?></p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Talent Pool</div>
                    <div class="stat-value"><?php echo number_format($stats['total_candidates']); ?></div>
                    <div class="stat-subtitle"><?php echo number_format($stats['active_candidates']); ?> active</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-label">Active Candidates</div>
                    <div class="stat-value"><?php echo number_format($stats['active_candidates']); ?></div>
                    <div class="stat-subtitle">Ready for placement</div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-label">Active Jobs</div>
                    <div class="stat-value"><?php echo number_format($stats['active_jobs']); ?></div>
                    <div class="stat-subtitle"><?php echo number_format($stats['total_jobs']); ?> total</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Applications</div>
                    <div class="stat-value"><?php echo number_format($stats['total_applications']); ?></div>
                    <div class="stat-subtitle">Candidate submissions</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-label">Team Members</div>
                    <div class="stat-value"><?php echo number_format($stats['active_users']); ?></div>
                    <div class="stat-subtitle"><?php echo number_format($stats['total_users']); ?> total</div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>