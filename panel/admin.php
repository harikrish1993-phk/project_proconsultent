<?php
/**
 * ADMIN DASHBOARD - FINAL PRODUCTION VERSION
 * Location: panel/admin.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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
    error_log("Admin Dashboard - DB Error: " . $e->getMessage());
    die("Database connection error. Please contact support.");
}

// Safe query helper
function safeQuery($conn, $query, $context = '') {
    $result = $conn->query($query);
    if (!$result) {
        error_log("Query Error ($context): " . $conn->error);
        error_log("SQL: " . $query);
        return false;
    }
    return $result;
}

// Initialize statistics
$stats = [
    'total_candidates' => 0,
    'active_candidates' => 0,
    'total_jobs' => 0,
    'active_jobs' => 0,
    'pending_jobs' => 0,
    'total_applications' => 0,
    'total_users' => 0,
    'active_users' => 0,
    'total_clients' => 0
];

// Get candidate statistics
$result = safeQuery($conn, "SELECT COUNT(*) as total FROM candidates", "Total Candidates");
if ($result) $stats['total_candidates'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM candidates WHERE status = 'active'", "Active Candidates");
if ($result) $stats['active_candidates'] = $result->fetch_assoc()['total'];

// Get job statistics
$result = safeQuery($conn, "SELECT COUNT(*) as total FROM jobs", "Total Jobs");
if ($result) $stats['total_jobs'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM jobs WHERE job_status = 'active'", "Active Jobs");
if ($result) $stats['active_jobs'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM jobs WHERE job_status = 'pending'", "Pending Jobs");
if ($result) $stats['pending_jobs'] = $result->fetch_assoc()['total'];

// Get application statistics
$result = safeQuery($conn, "SELECT COUNT(*) as total FROM job_applications", "Total Applications");
if ($result) $stats['total_applications'] = $result->fetch_assoc()['total'];

// Get user statistics
$result = safeQuery($conn, "SELECT COUNT(*) as total FROM user", "Total Users");
if ($result) $stats['total_users'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM user WHERE is_active = 1", "Active Users");
if ($result) $stats['active_users'] = $result->fetch_assoc()['total'];

// Get client statistics (if table exists)
$result = safeQuery($conn, "SELECT COUNT(*) as total FROM clients", "Total Clients");
if ($result) $stats['total_clients'] = $result->fetch_assoc()['total'];

// Get recent user activity
$recentUsers = [];
$result = safeQuery($conn, "
    SELECT user_code, name, email, level, last_login,
           (SELECT COUNT(*) FROM candidates WHERE created_by = user.user_code) as candidate_count,
           (SELECT COUNT(*) FROM jobs WHERE created_by = user.user_code) as job_count
    FROM user 
    WHERE level != 'admin' AND is_active = 1
    ORDER BY last_login DESC 
    LIMIT 10
", "Recent User Activity");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentUsers[] = $row;
    }
}

// Get recent candidates
$recentCandidates = [];
$result = safeQuery($conn, "
    SELECT can_code, candidate_name, email, contact_number, status, created_at,
           (SELECT name FROM user WHERE user_code = candidates.created_by) as added_by
    FROM candidates 
    ORDER BY created_at DESC 
    LIMIT 8
", "Recent Candidates");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentCandidates[] = $row;
    }
}

// Get recent jobs
$recentJobs = [];
$result = safeQuery($conn, "
    SELECT job_ref, job_title, job_status, job_type, created_at,
           (SELECT name FROM user WHERE user_code = jobs.created_by) as posted_by
    FROM jobs 
    ORDER BY created_at DESC 
    LIMIT 8
", "Recent Jobs");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentJobs[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo defined('COMPANY_NAME') ? COMPANY_NAME : 'ProConsultancy'; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            color: #2d3748;
        }
        
        .page-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }
        .page-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .page-header p {
            opacity: 0.95;
            font-size: 15px;
        }
        
        /* Statistics Grid */
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
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        .stat-card.success { border-left-color: #48bb78; }
        .stat-card.warning { border-left-color: #ed8936; }
        .stat-card.info { border-left-color: #4299e1; }
        .stat-card.danger { border-left-color: #f56565; }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .stat-icon {
            font-size: 32px;
            opacity: 0.8;
        }
        .stat-label {
            font-size: 13px;
            color: #718096;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #2d3748;
            line-height: 1;
        }
        .stat-subtitle {
            font-size: 13px;
            color: #a0aec0;
            margin-top: 8px;
        }
        
        /* Section Titles */
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        .section-title i {
            margin-right: 8px;
        }
        
        /* Tables */
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: #2d3748;
            color: white;
        }
        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        tbody tr:hover {
            background: #f7fafc;
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .badge-success { background: #c6f6d5; color: #22543d; }
        .badge-warning { background: #feebc8; color: #7c2d12; }
        .badge-info { background: #bee3f8; color: #2c5282; }
        .badge-danger { background: #fed7d7; color: #742a2a; }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #a0aec0;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        /* Grid Layout for Recent Activity */
        .activity-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .activity-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1>👋 Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                <p>Administrative Dashboard • <?php echo date('l, F j, Y'); ?></p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">👥</div>
                    </div>
                    <div class="stat-label">Total Talent Pool</div>
                    <div class="stat-value"><?php echo number_format($stats['total_candidates']); ?></div>
                    <div class="stat-subtitle"><?php echo number_format($stats['active_candidates']); ?> active profiles</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-icon">✓</div>
                    </div>
                    <div class="stat-label">Active Candidates</div>
                    <div class="stat-value"><?php echo number_format($stats['active_candidates']); ?></div>
                    <div class="stat-subtitle">Ready for placement</div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-header">
                        <div class="stat-icon">💼</div>
                    </div>
                    <div class="stat-label">Active Opportunities</div>
                    <div class="stat-value"><?php echo number_format($stats['active_jobs']); ?></div>
                    <div class="stat-subtitle"><?php echo number_format($stats['total_jobs']); ?> total positions</div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-icon">⏳</div>
                    </div>
                    <div class="stat-label">Pending Review</div>
                    <div class="stat-value"><?php echo number_format($stats['pending_jobs']); ?></div>
                    <div class="stat-subtitle">Awaiting approval</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">📄</div>
                    </div>
                    <div class="stat-label">Total Applications</div>
                    <div class="stat-value"><?php echo number_format($stats['total_applications']); ?></div>
                    <div class="stat-subtitle">Candidate submissions</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-icon">👨‍💼</div>
                    </div>
                    <div class="stat-label">Team Members</div>
                    <div class="stat-value"><?php echo number_format($stats['active_users']); ?></div>
                    <div class="stat-subtitle"><?php echo number_format($stats['total_users']); ?> total users</div>
                </div>
                
                <?php if ($stats['total_clients'] > 0): ?>
                <div class="stat-card info">
                    <div class="stat-header">
                        <div class="stat-icon">🏢</div>
                    </div>
                    <div class="stat-label">Client Companies</div>
                    <div class="stat-value"><?php echo number_format($stats['total_clients']); ?></div>
                    <div class="stat-subtitle">Active partnerships</div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent User Activity -->
            <?php if (!empty($recentUsers)): ?>
            <div class="section-title">
                <i>📊</i> Team Activity Overview
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Team Member</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Last Active</th>
                            <th>Candidates</th>
                            <th>Jobs Posted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><span class="badge badge-info"><?php echo ucfirst($u['level']); ?></span></td>
                            <td>
                                <?php 
                                if ($u['last_login']) {
                                    $time = strtotime($u['last_login']);
                                    $diff = time() - $time;
                                    if ($diff < 3600) {
                                        echo floor($diff / 60) . ' minutes ago';
                                    } elseif ($diff < 86400) {
                                        echo floor($diff / 3600) . ' hours ago';
                                    } else {
                                        echo date('M j, g:i A', $time);
                                    }
                                } else {
                                    echo '<span style="color: #a0aec0;">Never</span>';
                                }
                                ?>
                            </td>
                            <td><strong><?php echo number_format($u['candidate_count']); ?></strong></td>
                            <td><strong><?php echo number_format($u['job_count']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Recent Activity Grid -->
            <div class="activity-grid">
                <!-- Recent Candidates -->
                <div>
                    <div class="section-title">
                        <i>👤</i> Latest Talent Additions
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Candidate</th>
                                    <th>Status</th>
                                    <th>Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentCandidates)): ?>
                                <tr><td colspan="3"><div class="empty-state"><i>👤</i><p>No candidates yet</p></div></td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentCandidates as $c): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($c['candidate_name']); ?></strong>
                                            <?php if ($c['added_by']): ?>
                                            <br><small style="color: #718096;">by <?php echo htmlspecialchars($c['added_by']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge badge-success"><?php echo ucfirst($c['status']); ?></span></td>
                                        <td><?php echo date('M j', strtotime($c['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Recent Jobs -->
                <div>
                    <div class="section-title">
                        <i>💼</i> Latest Job Postings
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentJobs)): ?>
                                <tr><td colspan="3"><div class="empty-state"><i>💼</i><p>No jobs posted yet</p></div></td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentJobs as $j): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($j['job_title']); ?></strong>
                                            <?php if ($j['posted_by']): ?>
                                            <br><small style="color: #718096;">by <?php echo htmlspecialchars($j['posted_by']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge badge-info"><?php echo ucfirst($j['job_type']); ?></span></td>
                                        <td>
                                            <?php
                                            $statusClass = 'badge-';
                                            switch($j['job_status']) {
                                                case 'active': $statusClass .= 'success'; break;
                                                case 'pending': $statusClass .= 'warning'; break;
                                                default: $statusClass .= 'info';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($j['job_status']); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>