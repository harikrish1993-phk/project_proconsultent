<?php
/**
 * USER CREDENTIALS VIEWER
 * Shows all user details needed for login testing
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load config
require_once __DIR__ . '/includes/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Credentials Viewer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid;
        }
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .alert-info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .alert-danger {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-number {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .user-grid {
            display: grid;
            gap: 25px;
            margin-top: 30px;
        }
        .user-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
            position: relative;
        }
        .user-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        .user-card.admin {
            border-left: 5px solid #dc3545;
        }
        .user-card.recruiter {
            border-left: 5px solid #28a745;
        }
        .user-card.manager {
            border-left: 5px solid #ffc107;
        }
        .user-card.employee {
            border-left: 5px solid #17a2b8;
        }
        .role-badge {
            position: absolute;
            top: 25px;
            right: 25px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .role-badge.admin { background: #dc3545; color: white; }
        .role-badge.recruiter { background: #28a745; color: white; }
        .role-badge.manager { background: #ffc107; color: #333; }
        .role-badge.employee { background: #17a2b8; color: white; }
        .user-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-right: 120px;
        }
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            margin-right: 20px;
            flex-shrink: 0;
        }
        .user-info h3 {
            color: #333;
            font-size: 22px;
            margin-bottom: 5px;
        }
        .user-info .email {
            color: #666;
            font-size: 14px;
        }
        .credentials {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .credential-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .credential-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        .credential-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            font-family: 'Courier New', monospace;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.2s;
            margin-left: 10px;
        }
        .copy-btn:hover {
            background: #5568d3;
            transform: scale(1.05);
        }
        .copy-btn:active {
            transform: scale(0.95);
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .quick-login {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #dee2e6;
        }
        .login-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            width: 100%;
            transition: all 0.3s;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .login-btn:active {
            transform: translateY(0);
        }
        .search-box {
            margin-bottom: 30px;
        }
        .search-box input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #dee2e6;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }
        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            display: none;
        }
        table.active {
            display: table;
        }
        th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .view-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .toggle-btn {
            padding: 10px 20px;
            border: 2px solid #dee2e6;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            flex: 1;
            transition: all 0.3s;
        }
        .toggle-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>👥 User Credentials Viewer</h1>
        <p class="subtitle">All available users with their login credentials (User Code + Password)</p>
        
        <?php
        // Connect to database
        $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$conn) {
            echo '<div class="alert alert-danger">';
            echo '<strong>❌ Database Connection Failed!</strong><br>';
            echo 'Error: ' . htmlspecialchars(mysqli_connect_error());
            echo '</div>';
            exit;
        }
        
        // Get all users
        $query = "SELECT * FROM users ORDER BY 
                  CASE 
                      WHEN role = 'admin' THEN 1
                      WHEN role = 'manager' THEN 2
                      WHEN role = 'recruiter' THEN 3
                      ELSE 4
                  END, created_at DESC";
        
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            echo '<div class="alert alert-danger">';
            echo '<strong>❌ Query Failed!</strong><br>';
            echo 'Error: ' . htmlspecialchars(mysqli_error($conn));
            echo '</div>';
            exit;
        }
        
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        
        mysqli_close($conn);
        
        if (empty($users)) {
            echo '<div class="alert alert-warning">';
            echo '<strong>⚠️ No Users Found!</strong><br>';
            echo 'Run FINAL_DATABASE_SETUP.sql to create default users.';
            echo '</div>';
            exit;
        }
        
        // Calculate stats
        $totalUsers = count($users);
        $activeUsers = count(array_filter($users, fn($u) => $u['status'] === 'active'));
        $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
        $recruiterCount = count(array_filter($users, fn($u) => $u['role'] === 'recruiter'));
        ?>
        
        <div class="alert alert-success">
            <strong>✅ Found <?php echo $totalUsers; ?> user(s) in database</strong><br>
            You can login with any of these using their <strong>User Code</strong> (not email) and password.
        </div>
        
        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $activeUsers; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $adminCount; ?></div>
                <div class="stat-label">Administrators</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $recruiterCount; ?></div>
                <div class="stat-label">Recruiters</div>
            </div>
        </div>
        
        <!-- Search -->
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 Search by name, email, or user code..." onkeyup="filterUsers()">
        </div>
        
        <!-- View Toggle -->
        <div class="view-toggle">
            <button class="toggle-btn active" onclick="toggleView('cards')">📱 Card View</button>
            <button class="toggle-btn" onclick="toggleView('table')">📊 Table View</button>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <button class="filter-btn active" data-filter="all" onclick="filterByRole('all')">All Users</button>
            <button class="filter-btn" data-filter="admin" onclick="filterByRole('admin')">🔴 Admins</button>
            <button class="filter-btn" data-filter="recruiter" onclick="filterByRole('recruiter')">🟢 Recruiters</button>
            <button class="filter-btn" data-filter="manager" onclick="filterByRole('manager')">🟡 Managers</button>
            <button class="filter-btn" data-filter="active" onclick="filterByStatus('active')">✅ Active Only</button>
        </div>
        
        <!-- Card View -->
        <div class="user-grid" id="cardView">
            <?php foreach ($users as $user): ?>
                <div class="user-card <?php echo htmlspecialchars($user['role']); ?>" 
                     data-role="<?php echo htmlspecialchars($user['role']); ?>"
                     data-status="<?php echo htmlspecialchars($user['status']); ?>"
                     data-search="<?php echo htmlspecialchars(strtolower($user['first_name'] . ' ' . $user['last_name'] . ' ' . $user['email'] . ' ' . $user['user_code'])); ?>">
                    
                    <span class="role-badge <?php echo htmlspecialchars($user['role']); ?>">
                        <?php echo htmlspecialchars(strtoupper($user['role'])); ?>
                    </span>
                    
                    <div class="user-header">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <h3>
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                <span class="status-badge status-<?php echo htmlspecialchars($user['status']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                                </span>
                            </h3>
                            <div class="email"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                    </div>
                    
                    <div class="credentials">
                        <div class="credential-item">
                            <div class="credential-label">User Code (Login)</div>
                            <div class="credential-value">
                                <code><?php echo htmlspecialchars($user['user_code']); ?></code>
                                <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($user['user_code']); ?>', this)">
                                    📋 Copy
                                </button>
                            </div>
                        </div>
                        
                        <div class="credential-item">
                            <div class="credential-label">Default Password</div>
                            <div class="credential-value">
                                <code>
                                    <?php 
                                    // Show default passwords based on role
                                    if ($user['role'] === 'admin') {
                                        echo 'Admin@123';
                                    } elseif ($user['role'] === 'recruiter') {
                                        echo 'Recruiter@123';
                                    } else {
                                        echo 'Password@123';
                                    }
                                    ?>
                                </code>
                                <button class="copy-btn" onclick="copyToClipboard('<?php 
                                    if ($user['role'] === 'admin') {
                                        echo 'Admin@123';
                                    } elseif ($user['role'] === 'recruiter') {
                                        echo 'Recruiter@123';
                                    } else {
                                        echo 'Password@123';
                                    }
                                ?>', this)">
                                    📋 Copy
                                </button>
                            </div>
                        </div>
                        
                        <div class="credential-item">
                            <div class="credential-label">Email (Alternative)</div>
                            <div class="credential-value">
                                <code><?php echo htmlspecialchars($user['email']); ?></code>
                                <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($user['email']); ?>', this)">
                                    📋 Copy
                                </button>
                            </div>
                        </div>
                        
                        <div class="credential-item">
                            <div class="credential-label">User ID</div>
                            <div class="credential-value">
                                <code><?php echo htmlspecialchars($user['id']); ?></code>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($user['status'] === 'active'): ?>
                    <div class="quick-login">
                        <form method="POST" action="login.php" style="margin: 0;">
                            <input type="hidden" name="user_code" value="<?php echo htmlspecialchars($user['user_code']); ?>">
                            <input type="hidden" name="password" value="<?php 
                                if ($user['role'] === 'admin') {
                                    echo 'Admin@123';
                                } elseif ($user['role'] === 'recruiter') {
                                    echo 'Recruiter@123';
                                } else {
                                    echo 'Password@123';
                                }
                            ?>">
                            <button type="submit" class="login-btn">
                                🔐 Quick Login as <?php echo htmlspecialchars($user['first_name']); ?>
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="quick-login">
                        <button class="login-btn" style="opacity: 0.5; cursor: not-allowed;" disabled>
                            ⚠️ Account Inactive
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Table View -->
        <table id="tableView">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User Code</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Password</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr data-role="<?php echo htmlspecialchars($user['role']); ?>"
                    data-status="<?php echo htmlspecialchars($user['status']); ?>"
                    data-search="<?php echo htmlspecialchars(strtolower($user['first_name'] . ' ' . $user['last_name'] . ' ' . $user['email'] . ' ' . $user['user_code'])); ?>">
                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                    <td><code><?php echo htmlspecialchars($user['user_code']); ?></code></td>
                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <span class="role-badge <?php echo htmlspecialchars($user['role']); ?>">
                            <?php echo htmlspecialchars(strtoupper($user['role'])); ?>
                        </span>
                    </td>
                    <td>
                        <code>
                            <?php 
                            if ($user['role'] === 'admin') {
                                echo 'Admin@123';
                            } elseif ($user['role'] === 'recruiter') {
                                echo 'Recruiter@123';
                            } else {
                                echo 'Password@123';
                            }
                            ?>
                        </code>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo htmlspecialchars($user['status']); ?>">
                            <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user['status'] === 'active'): ?>
                        <button class="copy-btn" onclick="copyCredentials('<?php echo htmlspecialchars($user['user_code']); ?>', '<?php 
                            if ($user['role'] === 'admin') {
                                echo 'Admin@123';
                            } elseif ($user['role'] === 'recruiter') {
                                echo 'Recruiter@123';
                            } else {
                                echo 'Password@123';
                            }
                        ?>')">Copy Both</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="alert alert-info" style="margin-top: 30px;">
            <strong>💡 How to Login:</strong><br>
            1. Go to: <a href="login.php" target="_blank"><strong>login.php</strong></a><br>
            2. Enter <strong>User Code</strong> (e.g., ADM001) - NOT the email<br>
            3. Enter the password shown above<br>
            4. Click Login<br><br>
            <strong>Or use the "Quick Login" button on each card!</strong>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #dee2e6;">
            <a href="test_connection.php" style="display: inline-block; padding: 12px 25px; background: #28a745; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin-right: 10px;">
                🗄️ Database Test
            </a>
            <a href="test_login.php" style="display: inline-block; padding: 12px 25px; background: #ffc107; color: #333; text-decoration: none; border-radius: 8px; font-weight: bold; margin-right: 10px;">
                🔐 Login Test
            </a>
            <a href="login.php" style="display: inline-block; padding: 12px 25px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;">
                🚀 Go to Login Page
            </a>
        </div>
    </div>
    
    <script>
        let currentView = 'cards';
        let currentFilter = 'all';
        
        function toggleView(view) {
            currentView = view;
            const cardView = document.getElementById('cardView');
            const tableView = document.getElementById('tableView');
            const toggleBtns = document.querySelectorAll('.toggle-btn');
            
            toggleBtns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            if (view === 'cards') {
                cardView.style.display = 'grid';
                tableView.classList.remove('active');
            } else {
                cardView.style.display = 'none';
                tableView.classList.add('active');
            }
        }
        
        function filterByRole(role) {
            currentFilter = role;
            const filterBtns = document.querySelectorAll('.filter-btn');
            filterBtns.forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.filter === role) {
                    btn.classList.add('active');
                }
            });
            
            const cards = document.querySelectorAll('.user-card');
            const rows = document.querySelectorAll('#tableView tbody tr');
            
            cards.forEach(card => {
                if (role === 'all' || card.dataset.role === role) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
            
            rows.forEach(row => {
                if (role === 'all' || row.dataset.role === role) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function filterByStatus(status) {
            const filterBtns = document.querySelectorAll('.filter-btn');
            filterBtns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            const cards = document.querySelectorAll('.user-card');
            const rows = document.querySelectorAll('#tableView tbody tr');
            
            cards.forEach(card => {
                if (card.dataset.status === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
            
            rows.forEach(row => {
                if (row.dataset.status === status) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function filterUsers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.user-card');
            const rows = document.querySelectorAll('#tableView tbody tr');
            
            cards.forEach(card => {
                const searchData = card.dataset.search;
                if (searchData.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
            
            rows.forEach(row => {
                const searchData = row.dataset.search;
                if (searchData.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(() => {
                const originalText = button.textContent;
                button.textContent = '✅ Copied!';
                button.style.background = '#28a745';
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '#667eea';
                }, 2000);
            });
        }
        
        function copyCredentials(userCode, password) {
            const text = `User Code: ${userCode}\nPassword: ${password}`;
            navigator.clipboard.writeText(text).then(() => {
                alert('Credentials copied to clipboard!\n\n' + text);
            });
        }
    </script>
</body>
</html>