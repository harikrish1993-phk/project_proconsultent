<?php
/**
 * User Dashboard - Productivity Focus
 * Features: Personal KPIs, Today's Tasks, Quick Actions, Recent Activity
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
$user_code = $user['user_code'];

// User-only access (or redirect admin to admin dashboard)
if ($user['level'] === 'admin') {
    header('Location: dashboard_admin_new.php');
    exit();
}

// Connect to database
$db = Database::getInstance();
$conn = $db->getConnection();

// ============================================================================
// USER-SPECIFIC DATA FETCHING
// ============================================================================

$stats = [];

// Personal KPIs
$result = $conn->query("
    SELECT 
        COUNT(CASE WHEN created_by = '$user_code' THEN 1 END) as my_candidates,
        COUNT(CASE WHEN assigned_to = '$user_code' THEN 1 END) as assigned_candidates,
        COUNT(CASE WHEN created_by = '$user_code' AND DATE(created_at) = CURDATE() THEN 1 END) as candidates_today,
        COUNT(CASE WHEN created_by = '$user_code' AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as candidates_week
    FROM candidates
");
$stats['candidates'] = $result->fetch_assoc();

$result = $conn->query("
    SELECT 
        COUNT(CASE WHEN created_by = '$user_code' THEN 1 END) as posted_jobs,
        COUNT(CASE WHEN created_by = '$user_code' AND DATE(created_at) = CURDATE() THEN 1 END) as jobs_today
    FROM jobs
");
$stats['jobs'] = $result->fetch_assoc();

// Today's Follow-ups (Tasks)
$followups = [];
$followupQuery = "
    SELECT 
        can_code,
        candidate_name,
        contact_number,
        follow_up_date,
        assigned_to
    FROM candidates
    WHERE follow_up_date = CURDATE() AND status = 'active' AND assigned_to = '$user_code'
    ORDER BY candidate_name ASC
";
$result = $conn->query($followupQuery);
while ($row = $result->fetch_assoc()) {
    $followups[] = $row;
}

// Overdue Follow-ups
$overdueCount = 0;
$overdueQuery = "
    SELECT COUNT(*) as count
    FROM candidates
    WHERE follow_up_date < CURDATE() AND status = 'active' AND assigned_to = '$user_code'
";
$result = $conn->query($overdueQuery);
$overdueCount = $result->fetch_assoc()['count'] ?? 0;

// Recent Activity (Last 5)
$recentActivity = [];
$recentQuery = "
    (SELECT 'candidate' as type, can_code as id, candidate_name as title, created_at as time FROM candidates WHERE created_by = '$user_code')
    UNION ALL
    (SELECT 'job' as type, job_ref as id, job_title as title, created_at as time FROM jobs WHERE created_by = '$user_code')
    ORDER BY time DESC
    LIMIT 5
";
$result = $conn->query($recentQuery);
while ($row = $result->fetch_assoc()) {
    $recentActivity[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>User Dashboard - <?php echo COMPANY_NAME; ?></title>
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
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(52, 152, 219, 0.3);
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
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid #3498db;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
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
        .task-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .task-list-item:last-child {
            border-bottom: none;
        }
        .task-title {
            font-weight: 600;
            color: #4a5568;
        }
        .task-actions a {
            margin-left: 8px;
        }
        .quick-action-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        .quick-action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .quick-action-btn:hover {
            transform: translateY(-2px);
            color: white;
        }
        .quick-action-btn i {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .bg-primary-gradient { background: linear-gradient(45deg, #3498db, #2980b9); }
        .bg-success-gradient { background: linear-gradient(45deg, #2ecc71, #27ae60); }
        .bg-warning-gradient { background: linear-gradient(45deg, #f1c40f, #f39c12); }
        .bg-danger-gradient { background: linear-gradient(45deg, #e74c3c, #c0392b); }
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
            <?php include __DIR__ . '/../includes/sidebar_user.php'; ?>
            
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
                                                    <small class="text-muted"><?php echo ucfirst($user['level']); ?></small>
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
                            <h1><i class="bx bx-rocket"></i> My Productivity Dashboard</h1>
                            <p>Welcome back, <?php echo htmlspecialchars($user['name']); ?>. Focus on your daily tasks and key performance indicators.</p>
                        </div>
                        
                        <!-- Attention Alert -->
                        <?php if ($overdueCount > 0): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bx bx-error-alt me-2"></i>
                            <div class="flex-grow-1">
                                <strong>Attention!</strong> You have **<?php echo $overdueCount; ?>** overdue follow-ups. Please prioritize these tasks.
                            </div>
                            <a href="candidates.php?filter=overdue" class="btn btn-sm btn-danger">View Overdue</a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <!-- Left Column: KPIs and Tasks -->
                            <div class="col-lg-8 col-md-12">
                                
                                <!-- Personal KPIs -->
                                <h5 class="pb-1 mb-4 text-muted">Personal Key Performance Indicators</h5>
                                <div class="row g-4 mb-4">
                                    <div class="col-lg-3 col-md-6 col-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <i class="bx bx-user-plus text-primary fs-3"></i>
                                                    </div>
                                                </div>
                                                <span class="d-block fw-semibold mb-1">Candidates Added</span>
                                                <h3 class="card-title mb-2"><?php echo number_format($stats['candidates']['my_candidates']); ?></h3>
                                                <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +<?php echo $stats['candidates']['candidates_today']; ?> Today</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <i class="bx bx-user-check text-success fs-3"></i>
                                                    </div>
                                                </div>
                                                <span class="d-block fw-semibold mb-1">Assigned Candidates</span>
                                                <h3 class="card-title mb-2"><?php echo number_format($stats['candidates']['assigned_candidates']); ?></h3>
                                                <small class="text-muted">Total in pipeline</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <i class="bx bx-briefcase text-info fs-3"></i>
                                                    </div>
                                                </div>
                                                <span class="d-block fw-semibold mb-1">Jobs Posted</span>
                                                <h3 class="card-title mb-2"><?php echo number_format($stats['jobs']['posted_jobs']); ?></h3>
                                                <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +<?php echo $stats['jobs']['jobs_today']; ?> Today</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="card-title d-flex align-items-start justify-content-between">
                                                    <div class="avatar flex-shrink-0">
                                                        <i class="bx bx-calendar-check text-warning fs-3"></i>
                                                    </div>
                                                </div>
                                                <span class="d-block fw-semibold mb-1">Follow-ups Today</span>
                                                <h3 class="card-title mb-2"><?php echo number_format(count($followups)); ?></h3>
                                                <small class="text-danger fw-semibold"><i class="bx bx-down-arrow-alt"></i> <?php echo $overdueCount; ?> Overdue</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Today's Tasks (Follow-ups) -->
                                <div class="card mb-4">
                                    <h5 class="card-header"><i class="bx bx-list-check me-2"></i> Today's Priority Tasks</h5>
                                    <div class="card-body">
                                        <?php if (empty($followups)): ?>
                                            <div class="text-center py-5 text-muted">
                                                <i class="bx bx-check-circle fs-1 mb-3 text-success"></i>
                                                <p class="mb-0">All clear! No follow-ups scheduled for today.</p>
                                            </div>
                                        <?php else: ?>
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach ($followups as $task): ?>
                                                <li class="task-list-item">
                                                    <div>
                                                        <div class="task-title"><?php echo htmlspecialchars($task['candidate_name']); ?></div>
                                                        <small class="text-muted">Candidate ID: <?php echo htmlspecialchars($task['can_code']); ?></small>
                                                    </div>
                                                    <div class="task-actions">
                                                        <a href="tel:<?php echo htmlspecialchars($task['contact_number']); ?>" class="btn btn-sm btn-icon btn-success" title="Call"><i class="bx bx-phone"></i></a>
                                                        <a href="candidates.php?action=view&id=<?php echo htmlspecialchars($task['can_code']); ?>" class="btn btn-sm btn-icon btn-primary" title="View Profile"><i class="bx bx-show"></i></a>
                                                        <a href="candidates.php?action=complete_followup&id=<?php echo htmlspecialchars($task['can_code']); ?>" class="btn btn-sm btn-icon btn-outline-secondary" title="Complete"><i class="bx bx-check"></i></a>
                                                    </div>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Recent Activity -->
                                <div class="card">
                                    <h5 class="card-header"><i class="bx bx-history me-2"></i> My Recent Activity</h5>
                                    <div class="card-body">
                                        <?php if (empty($recentActivity)): ?>
                                            <div class="text-center py-5 text-muted">
                                                <i class="bx bx-time-five fs-1 mb-3"></i>
                                                <p class="mb-0">No recent activity to display.</p>
                                            </div>
                                        <?php else: ?>
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach ($recentActivity as $activity): ?>
                                                <li class="d-flex mb-4 pb-1">
                                                    <div class="avatar flex-shrink-0 me-3">
                                                        <span class="avatar-initial rounded bg-label-<?php echo $activity['type'] === 'candidate' ? 'primary' : 'info'; ?>"><i class="bx bx-<?php echo $activity['type'] === 'candidate' ? 'user' : 'briefcase'; ?>"></i></span>
                                                    </div>
                                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                        <div class="me-2">
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                                            <small class="text-muted"><?php echo ucfirst($activity['type']); ?> Added</small>
                                                        </div>
                                                        <div class="user-progress">
                                                            <small class="fw-semibold"><?php echo date('M d, h:i A', strtotime($activity['time'])); ?></small>
                                                        </div>
                                                    </div>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                            </div>
                            
                            <!-- Right Column: Quick Actions and Pipeline -->
                            <div class="col-lg-4 col-md-12">
                                
                                <!-- Quick Actions -->
                                <div class="quick-action-card">
                                    <h5 class="card-header p-0 mb-3"><i class="bx bx-bolt-circle me-2"></i> Quick Actions</h5>
                                    <div class="quick-action-grid">
                                        <a href="candidates.php?action=add" class="quick-action-btn bg-primary-gradient">
                                            <i class="bx bx-user-plus"></i>
                                            <span>Add Candidate</span>
                                        </a>
                                        <a href="jobs.php?action=post" class="quick-action-btn bg-success-gradient">
                                            <i class="bx bx-briefcase"></i>
                                            <span>Post Job</span>
                                        </a>
                                        <a href="reports.php?type=daily" class="quick-action-btn bg-warning-gradient">
                                            <i class="bx bx-bar-chart-alt-2"></i>
                                            <span>Daily Report</span>
                                        </a>
                                        <a href="candidates.php?filter=assigned" class="quick-action-btn bg-danger-gradient">
                                            <i class="bx bx-user-pin"></i>
                                            <span>My Pipeline</span>
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Candidate Pipeline Overview (Placeholder for future Kanban) -->
                                <div class="card">
                                    <h5 class="card-header"><i class="bx bx-git-branch me-2"></i> Candidate Pipeline Overview</h5>
                                    <div class="card-body">
                                        <p class="text-muted">Visual representation of your assigned candidates across different stages (e.g., Sourced, Interview, Offer, Hired).</p>
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-primary">Sourced</small>
                                            <small class="fw-semibold">15</small>
                                        </div>
                                        <div class="progress mb-3" style="height: 8px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: 30%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-info">Interview</small>
                                            <small class="fw-semibold">8</small>
                                        </div>
                                        <div class="progress mb-3" style="height: 8px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-success">Offer</small>
                                            <small class="fw-semibold">3</small>
                                        </div>
                                        <div class="progress mb-3" style="height: 8px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 15%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-danger">Rejected</small>
                                            <small class="fw-semibold">4</small>
                                        </div>
                                        <div class="progress mb-3" style="height: 8px;">
                                            <div class="progress-bar bg-danger" role="progressbar" style="width: 5%" aria-valuenow="5" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        
                                        <a href="candidates.php?view=pipeline" class="btn btn-sm btn-outline-secondary w-100 mt-3">Manage Pipeline</a>
                                    </div>
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
