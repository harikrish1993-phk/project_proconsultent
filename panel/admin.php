<?php
/**
 * Admin Dashboard - System Control Panel
 * Features: User activity monitoring, system health, advanced analytics
 */

// Load core files
require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/core/Auth.php';
require_once __DIR__ . '/../includes/core/Database.php';

// Check authentication
if (!Auth::check()) {
    header('Location: ../login.php');
    exit();
}

// Get user info
$user = Auth::user();

// Admin-only access
if ($user['level'] !== 'admin') {
    header('Location: dashboard_user.php');
    exit();
}

// Connect to database
$db = Database::getInstance();
$conn = $db->getConnection();

// ============================================================================
// SYSTEM-WIDE STATISTICS
// ============================================================================

$stats = [];

// Candidates stats
$result = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
        SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as this_week
    FROM candidates
");
$stats['candidates'] = $result->fetch_assoc();

// Jobs stats
$result = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN job_status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN job_status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
    FROM jobs
");
$stats['jobs'] = $result->fetch_assoc();

// Users stats
$result = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN active = '1' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN level = 'admin' THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN level = 'user' THEN 1 ELSE 0 END) as users
    FROM user
");
$stats['users'] = $result->fetch_assoc();

// Today's follow-ups
$result = $conn->query("
    SELECT COUNT(*) as count 
    FROM candidates 
    WHERE follow_up_date = CURDATE() AND status = 'active'
");
$stats['followups_today'] = $result->fetch_assoc()['count'];

// ============================================================================
// USER ACTIVITY MONITORING
// ============================================================================

$userActivities = [];
$activityQuery = "
    SELECT 
        u.user_code,
        u.name,
        u.email,
        u.last_login,
        (SELECT COUNT(*) FROM candidates WHERE created_by = u.user_code) as total_candidates,
        (SELECT COUNT(*) FROM candidates WHERE created_by = u.user_code AND DATE(created_at) = CURDATE()) as today_candidates,
        (SELECT COUNT(*) FROM jobs WHERE created_by = u.user_code) as total_jobs,
        (SELECT COUNT(*) FROM jobs WHERE created_by = u.user_code AND DATE(created_at) = CURDATE()) as today_jobs
    FROM user u
    WHERE u.level = 'user' AND u.active = '1'
    ORDER BY u.last_login DESC
    LIMIT 10
";
$result = $conn->query($activityQuery);
while ($row = $result->fetch_assoc()) {
    $userActivities[] = $row;
}

// ============================================================================
// RECENT SYSTEM ACTIVITY
// ============================================================================

$recentCandidates = [];
$candidatesQuery = "
    SELECT 
        c.can_code,
        c.candidate_name,
        c.contact_number,
        c.email,
        c.status,
        c.created_at,
        u.name as created_by_name
    FROM candidates c
    LEFT JOIN user u ON c.created_by = u.user_code
    ORDER BY c.created_at DESC
    LIMIT 10
";
$result = $conn->query($candidatesQuery);
while ($row = $result->fetch_assoc()) {
    $recentCandidates[] = $row;
}

$recentJobs = [];
$jobsQuery = "
    SELECT 
        j.job_ref,
        j.job_title,
        j.company_name,
        j.job_status,
        j.created_at,
        u.name as created_by_name
    FROM jobs j
    LEFT JOIN user u ON j.created_by = u.user_code
    ORDER BY j.created_at DESC
    LIMIT 10
";
$result = $conn->query($jobsQuery);
while ($row = $result->fetch_assoc()) {
    $recentJobs[] = $row;
}

// ============================================================================
// SYSTEM HEALTH METRICS
// ============================================================================

// Database size
$result = $conn->query("
    SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
    FROM information_schema.TABLES
    WHERE table_schema = '" . DB_NAME . "'
");
$dbSize = $result->fetch_assoc()['size_mb'] ?? 0;

// Password reset requests (last 24 hours)
$result = $conn->query("
    SELECT COUNT(*) as count 
    FROM password_resets 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$passwordResets = $result->fetch_assoc()['count'] ?? 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Admin Dashboard - <?php echo COMPANY_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    
    <!-- Icons -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    
    <!-- Page CSS -->
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }
        .dashboard-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
            color: white;
        }
        .dashboard-header p {
            margin: 8px 0 0 0;
            opacity: 0.95;
            font-size: 15px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid #667eea;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.danger { border-left-color: #dc3545; }
        .stat-card.info { border-left-color: #17a2b8; }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card.success .stat-icon { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .stat-card.warning .stat-icon { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); }
        .stat-card.danger .stat-icon { background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%); }
        .stat-card.info .stat-icon { background: linear-gradient(135deg, #17a2b8 0%, #6610f2 100%); }
        
        .stat-label {
            font-size: 13px;
            color: #8898aa;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            line-height: 1;
        }
        .stat-change {
            font-size: 13px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .stat-change.positive { color: #28a745; }
        .stat-change.negative { color: #dc3545; }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        .activity-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        .activity-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .activity-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .activity-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .activity-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            color: #4a5568;
        }
        .activity-table tbody tr:hover {
            background: #f8f9ff;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            margin-right: 10px;
            vertical-align: middle;
        }
        .time-ago {
            font-size: 12px;
            color: #8898aa;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #8898aa;
        }
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
    
    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            
            <!-- Sidebar -->
            <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
            
            <!-- Layout container -->
            <div class="layout-page">
                
                <!-- Navbar -->
                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                            <i class="bx bx-menu bx-sm"></i>
                        </a>
                    </div>
                    
                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                        <div class="navbar-nav align-items-center">
                            <div class="nav-item d-flex align-items-center">
                                <i class="bx bx-search fs-4 lh-0"></i>
                                <input type="text" class="form-control border-0 shadow-none" placeholder="Search..." aria-label="Search..." />
                            </div>
                        </div>
                        
                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <span class="avatar-initial rounded-circle bg-label-primary"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <span class="avatar-initial rounded-circle bg-label-primary"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <span class="fw-semibold d-block"><?php echo htmlspecialchars($user['name']); ?></span>
                                                    <small class="text-muted">Admin</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li><div class="dropdown-divider"></div></li>
                                    <li><a class="dropdown-item" href="profile.php"><i class="bx bx-user me-2"></i> My Profile</a></li>
                                    <li><a class="dropdown-item" href="settings.php"><i class="bx bx-cog me-2"></i> Settings</a></li>
                                    <li><div class="dropdown-divider"></div></li>
                                    <li><a class="dropdown-item" href="logout.php"><i class="bx bx-power-off me-2"></i> Log Out</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                
                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        
                        <!-- Dashboard Header -->
                        <div class="dashboard-header">
                            <h1><i class="bx bx-home-circle"></i> Admin Dashboard</h1>
                            <p>System overview and user activity monitoring • Last updated: <?php echo date('F j, Y g:i A'); ?></p>
                        </div>
                        
                        <!-- Statistics Grid -->
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-icon"><i class="bx bx-user"></i></div>
                                </div>
                                <div class="stat-label">Total Candidates</div>
                                <div class="stat-value"><?php echo number_format($stats['candidates']['total']); ?></div>
                                <div class="stat-change positive">
                                    <i class="bx bx-up-arrow-alt"></i>
                                    <span><?php echo $stats['candidates']['this_week']; ?> this week</span>
                                </div>
                            </div>
                            
                            <div class="stat-card success">
                                <div class="stat-header">
                                    <div class="stat-icon"><i class="bx bx-user-check"></i></div>
                                </div>
                                <div class="stat-label">Active Candidates</div>
                                <div class="stat-value"><?php echo number_format($stats['candidates']['active']); ?></div>
                                <div class="stat-change positive">
                                    <i class="bx bx-up-arrow-alt"></i>
                                    <span><?php echo $stats['candidates']['today']; ?> added today</span>
                                </div>
                            </div>
                            
                            <div class="stat-card info">
                                <div class="stat-header">
                                    <div class="stat-icon"><i class="bx bx-briefcase"></i></div>
                                </div>
                                <div class="stat-label">Active Jobs</div>
                                <div class="stat-value"><?php echo number_format($stats['jobs']['active']); ?></div>
                                <div class="stat-change">
                                    <i class="bx bx-calendar"></i>
                                    <span><?php echo $stats['jobs']['today']; ?> posted today</span>
                                </div>
                            </div>
                            
                            <div class="stat-card warning">
                                <div class="stat-header">
                                    <div class="stat-icon"><i class="bx bx-time"></i></div>
                                </div>
                                <div class="stat-label">Pending Jobs</div>
                                <div class="stat-value"><?php echo number_format($stats['jobs']['pending']); ?></div>
                                <div class="stat-change">
                                    <i class="bx bx-info-circle"></i>
                                    <span>Awaiting approval</span>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-icon"><i class="bx bx-group"></i></div>
                                </div>
                                <div class="stat-label">Total Users</div>
                                <div class="stat-value"><?php echo number_format($stats['users']['total']); ?></div>
                                <div class="stat-change">
                                    <i class="bx bx-user"></i>
                                    <span><?php echo $stats['users']['active']; ?> active</span>
                                </div>
                            </div>
                            
                            <div class="stat-card danger">
                                <div class="stat-header">
                                    <div class="stat-icon"><i class="bx bx-bell"></i></div>
                                </div>
                                <div class="stat-label">Follow-ups Today</div>
                                <div class="stat-value"><?php echo number_format($stats['followups_today']); ?></div>
                                <div class="stat-change">
                                    <i class="bx bx-calendar-check"></i>
                                    <span>Requires attention</span>
                                </div>
                            </div>
                            
                            <div class="stat-card info">
                                <div class="stat-header">
                                    <div class="stat-icon"><i class="bx bx-data"></i></div>
                                </div>
                                <div class="stat-label">Database Size</div>
                                <div class="stat-value"><?php echo $dbSize; ?> MB</div>
                                <div class="stat-change">
                                    <i class="bx bx-server"></i>
                                    <span>System health</span>
                                </div>
                            </div>
                            
                            <div class="stat-card warning">
                                <div class="stat-header">
                                    <div class="stat-icon"><i class="bx bx-lock-open"></i></div>
                                </div>
                                <div class="stat-label">Password Resets</div>
                                <div class="stat-value"><?php echo $passwordResets; ?></div>
                                <div class="stat-change">
                                    <i class="bx bx-time"></i>
                                    <span>Last 24 hours</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Activity Monitoring -->
                        <div class="section-title">
                            <i class="bx bx-line-chart"></i> User Activity Monitoring
                        </div>
                        <div class="activity-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Last Login</th>
                                        <th>Total Candidates</th>
                                        <th>Today's Candidates</th>
                                        <th>Total Jobs</th>
                                        <th>Today's Jobs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($userActivities)): ?>
                                        <tr>
                                            <td colspan="7">
                                                <div class="empty-state">
                                                    <i class="bx bx-user-x"></i>
                                                    <p>No user activity found</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($userActivities as $activity): ?>
                                            <tr>
                                                <td>
                                                    <span class="user-avatar"><?php echo strtoupper(substr($activity['name'], 0, 1)); ?></span>
                                                    <strong><?php echo htmlspecialchars($activity['name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($activity['email']); ?></td>
                                                <td>
                                                    <?php 
                                                    if ($activity['last_login']) {
                                                        $lastLogin = new DateTime($activity['last_login']);
                                                        $now = new DateTime();
                                                        $diff = $now->diff($lastLogin);
                                                        
                                                        if ($diff->days == 0) {
                                                            if ($diff->h == 0) {
                                                                echo $diff->i . ' min ago';
                                                            } else {
                                                                echo $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
                                                            }
                                                        } else {
                                                            echo $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
                                                        }
                                                    } else {
                                                        echo '<span class="text-muted">Never</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><strong><?php echo $activity['total_candidates']; ?></strong></td>
                                                <td>
                                                    <?php if ($activity['today_candidates'] > 0): ?>
                                                        <span class="badge badge-success"><?php echo $activity['today_candidates']; ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong><?php echo $activity['total_jobs']; ?></strong></td>
                                                <td>
                                                    <?php if ($activity['today_jobs'] > 0): ?>
                                                        <span class="badge badge-success"><?php echo $activity['today_jobs']; ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">0</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Recent Activity -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="section-title">
                                    <i class="bx bx-user-plus"></i> Recent Candidates
                                </div>
                                <div class="activity-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Contact</th>
                                                <th>Status</th>
                                                <th>Added By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recentCandidates)): ?>
                                                <tr>
                                                    <td colspan="4">
                                                        <div class="empty-state">
                                                            <i class="bx bx-user-x"></i>
                                                            <p>No recent candidates</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($recentCandidates as $candidate): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($candidate['candidate_name']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($candidate['contact_number']); ?></td>
                                                        <td>
                                                            <?php if ($candidate['status'] == 'active'): ?>
                                                                <span class="badge badge-success">Active</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-warning"><?php echo ucfirst($candidate['status']); ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($candidate['created_by_name']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="section-title">
                                    <i class="bx bx-briefcase-alt"></i> Recent Jobs
                                </div>
                                <div class="activity-table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Job Title</th>
                                                <th>Company</th>
                                                <th>Status</th>
                                                <th>Posted By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recentJobs)): ?>
                                                <tr>
                                                    <td colspan="4">
                                                        <div class="empty-state">
                                                            <i class="bx bx-briefcase-alt-2"></i>
                                                            <p>No recent jobs</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($recentJobs as $job): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($job['job_title']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                                                        <td>
                                                            <?php if ($job['job_status'] == 'active'): ?>
                                                                <span class="badge badge-success">Active</span>
                                                            <?php elseif ($job['job_status'] == 'pending'): ?>
                                                                <span class="badge badge-warning">Pending</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-danger">Closed</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($job['created_by_name']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Footer -->
                    <footer class="content-footer footer bg-footer-theme">
                        <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                            <div class="mb-2 mb-md-0">
                                © <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?>. All rights reserved.
                            </div>
                        </div>
                    </footer>
                    
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    
    <!-- Core JS -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    
    <!-- Main JS -->
    <script src="../assets/js/main.js"></script>
    
    <!-- Auto-refresh dashboard every 5 minutes -->
    <script>
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>
