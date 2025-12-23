<?php
/**
 * RECRUITER DASHBOARD
 * Location: panel/recruiter.php
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
$user_code = $user['user_code'];
$user_name = $user['name'];

// Database connection
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    error_log("Recruiter Dashboard - DB Error: " . $e->getMessage());
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

// Get personal statistics
$stats = [
    'my_candidates' => 0,
    'active_candidates' => 0,
    'my_jobs' => 0,
    'active_jobs' => 0,
    'my_applications' => 0,
    'pending_followups' => 0
];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM candidates WHERE created_by = '$user_code'", "My Candidates");
if ($result) $stats['my_candidates'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM candidates WHERE created_by = '$user_code' AND status = 'active'", "My Active Candidates");
if ($result) $stats['active_candidates'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM jobs WHERE created_by = '$user_code'", "My Jobs");
if ($result) $stats['my_jobs'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM jobs WHERE created_by = '$user_code' AND job_status = 'active'", "My Active Jobs");
if ($result) $stats['active_jobs'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM job_applications WHERE created_by = '$user_code'", "My Applications");
if ($result) $stats['my_applications'] = $result->fetch_assoc()['total'];

$result = safeQuery($conn, "SELECT COUNT(*) as total FROM candidates WHERE created_by = '$user_code' AND follow_up_date = CURDATE()", "Follow-ups Today");
if ($result) $stats['pending_followups'] = $result->fetch_assoc()['total'];

// Get my recent candidates
$myCandidates = [];
$result = safeQuery($conn, "
    SELECT can_code, candidate_name, email, contact_number, status, skills, created_at
    FROM candidates 
    WHERE created_by = '$user_code'
    ORDER BY created_at DESC 
    LIMIT 10
", "My Recent Candidates");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $myCandidates[] = $row;
    }
}

// Get my recent jobs
$myJobs = [];
$result = safeQuery($conn, "
    SELECT job_ref, job_title, job_type, job_status, created_at
    FROM jobs 
    WHERE created_by = '$user_code'
    ORDER BY created_at DESC 
    LIMIT 10
", "My Recent Jobs");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $myJobs[] = $row;
    }
}

// Get today's follow-ups
$todayFollowups = [];
$result = safeQuery($conn, "
    SELECT can_code, candidate_name, contact_number, email, follow_up_date
    FROM candidates 
    WHERE created_by = '$user_code' AND follow_up_date = CURDATE()
    ORDER BY follow_up_date ASC
    LIMIT 5
", "Today's Follow-ups");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $todayFollowups[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - <?php echo defined('COMPANY_NAME') ? COMPANY_NAME : 'ProConsultancy'; ?></title>
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
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .quick-action-btn {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #2d3748;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .quick-action-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        .quick-action-btn .icon {
            font-size: 32px;
        }
        .quick-action-btn .label {
            font-weight: 600;
            font-size: 14px;
        }
        
        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-4px);
        }
        .stat-card.success { border-left-color: #48bb78; }
        .stat-card.warning { border-left-color: #ed8936; }
        .stat-card.info { border-left-color: #4299e1; }
        
        .stat-label {
            font-size: 13px;
            color: #718096;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #2d3748;
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
        
        /* Alert Box */
        .alert {
            background: #fef5e7;
            border-left: 4px solid #f39c12;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-title {
            font-weight: 600;
            color: #d68910;
            margin-bottom: 8px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #a0aec0;
        }
        
        /* Activity Grid */
        .activity-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
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
                <h1>👋 Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p>Your Personal Dashboard • <?php echo date('l, F j, Y'); ?></p>
            </div>
            
            <!-- Follow-up Alert -->
            <?php if ($stats['pending_followups'] > 0): ?>
            <div class="alert">
                <div class="alert-title">⏰ You have <?php echo $stats['pending_followups']; ?> follow-up<?php echo $stats['pending_followups'] > 1 ? 's' : ''; ?> scheduled for today</div>
                <p>Stay on track with your candidate engagement plan</p>
            </div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="can_add.php" class="quick-action-btn">
                    <div class="icon">➕</div>
                    <div class="label">Add Candidate</div>
                </a>
                <a href="add_job.php" class="quick-action-btn">
                    <div class="icon">💼</div>
                    <div class="label">Post Position</div>
                </a>
                <a href="can_list.php" class="quick-action-btn">
                    <div class="icon">📋</div>
                    <div class="label">View My Talent</div>
                </a>
                <a href="list_jobs.php" class="quick-action-btn">
                    <div class="icon">🔍</div>
                    <div class="label">My Openings</div>
                </a>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">My Talent Pool</div>
                    <div class="stat-value"><?php echo number_format($stats['my_candidates']); ?></div>
                    <div class="stat-subtitle">Total candidates managed</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-label">Active Profiles</div>
                    <div class="stat-value"><?php echo number_format($stats['active_candidates']); ?></div>
                    <div class="stat-subtitle">Ready for placement</div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-label">My Job Postings</div>
                    <div class="stat-value"><?php echo number_format($stats['my_jobs']); ?></div>
                    <div class="stat-subtitle"><?php echo number_format($stats['active_jobs']); ?> currently active</div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-label">Follow-ups Today</div>
                    <div class="stat-value"><?php echo number_format($stats['pending_followups']); ?></div>
                    <div class="stat-subtitle">Scheduled engagements</div>
                </div>
                
                <?php if ($stats['my_applications'] > 0): ?>
                <div class="stat-card">
                    <div class="stat-label">Applications Managed</div>
                    <div class="stat-value"><?php echo number_format($stats['my_applications']); ?></div>
                    <div class="stat-subtitle">Candidate submissions</div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Today's Follow-ups -->
            <?php if (!empty($todayFollowups)): ?>
            <div class="section-title">
                <i>⏰</i> Today's Follow-ups
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todayFollowups as $f): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($f['candidate_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($f['contact_number'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($f['email'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="tel:<?php echo htmlspecialchars($f['contact_number']); ?>" style="color: #667eea; text-decoration: none; font-weight: 600;">
                                    📞 Call Now
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Recent Activity Grid -->
            <div class="activity-grid">
                <!-- My Recent Candidates -->
                <div>
                    <div class="section-title">
                        <i>👤</i> My Recent Candidates
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($myCandidates)): ?>
                                <tr><td colspan="3"><div class="empty-state">No candidates yet. Start building your talent pool!</div></td></tr>
                                <?php else: ?>
                                    <?php foreach ($myCandidates as $c): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($c['candidate_name']); ?></strong>
                                            <?php if ($c['skills']): ?>
                                            <br><small style="color: #718096;"><?php echo htmlspecialchars(substr($c['skills'], 0, 50)); ?><?php echo strlen($c['skills']) > 50 ? '...' : ''; ?></small>
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
                
                <!-- My Recent Jobs -->
                <div>
                    <div class="section-title">
                        <i>💼</i> My Recent Postings
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
                                <?php if (empty($myJobs)): ?>
                                <tr><td colspan="3"><div class="empty-state">No job postings yet. Create your first opening!</div></td></tr>
                                <?php else: ?>
                                    <?php foreach ($myJobs as $j): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($j['job_title']); ?></strong></td>
                                        <td><span class="badge badge-info"><?php echo ucfirst($j['job_type']); ?></span></td>
                                        <td>
                                            <?php
                                            $statusClass = $j['job_status'] == 'active' ? 'badge-success' : 'badge-warning';
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