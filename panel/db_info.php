<?php
/**
 * DEVELOPMENT ONLY - Database Information Dashboard
 * Quick overview of database status
 */

// Security check
$allowed_domains = ['localhost', '.test', '127.0.0.1'];
$current_domain = $_SERVER['HTTP_HOST'];
$is_dev = false;

foreach ($allowed_domains as $domain) {
    if (strpos($current_domain, $domain) !== false) {
        $is_dev = true;
        break;
    }
}

if (!$is_dev) {
    die('❌ This tool only works in development environment!');
}

require_once '../panel/db_conn.php';

// Get database stats
$stats = [
    'candidates' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM candidates"))['count'],
    'jobs' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM jobs"))['count'],
    'users' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM user"))['count'],
    'active_users' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM user WHERE active = '1'"))['count'],
    'submitted_cvs' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM submittedcv"))['count'],
    'candidate_assignments' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM candidate_assignments"))['count'],
];

// Recent activity
$recent_candidates = mysqli_query($conn, "SELECT candidate_name, email_id, created_at FROM candidates ORDER BY created_at DESC LIMIT 5");
$recent_jobs = mysqli_query($conn, "SELECT heading, company_name, created FROM jobs ORDER BY created DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Info</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 32px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 42px;
            font-weight: bold;
            color: #667eea;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 22px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        th {
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            color: #333;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .nav-links {
            margin-bottom: 20px;
        }
        
        .nav-links a {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            margin-right: 10px;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .nav-links a:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Database Information Dashboard</h1>
        
        <div class="nav-links">
            <a href="quick_login.php">Quick Login</a>
            <a href="create_user.php">Create User</a>
            <a href="../panel/dashboard.php">Go to Panel</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Candidates</h3>
                <div class="number"><?php echo number_format($stats['candidates']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Active Jobs</h3>
                <div class="number"><?php echo number_format($stats['jobs']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>System Users</h3>
                <div class="number"><?php echo $stats['active_users']; ?> / <?php echo $stats['users']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>CV Submissions</h3>
                <div class="number"><?php echo number_format($stats['submitted_cvs']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Assignments</h3>
                <div class="number"><?php echo number_format($stats['candidate_assignments']); ?></div>
            </div>
        </div>
        
        <div class="section">
            <h2>Recent Candidates</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Added</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($recent_candidates)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['candidate_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email_id']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>Recent Jobs</h2>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Company</th>
                        <th>Posted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($recent_jobs)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['heading']); ?></td>
                            <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($row['created'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
