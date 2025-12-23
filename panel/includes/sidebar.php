<?php
/**
 * UNIFIED SIDEBAR NAVIGATION
 * Works for both admin and recruiter roles
 * File location: panel/includes/sidebar.php
 */

// Get current page
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_uri = $_SERVER['REQUEST_URI'];

// Get user info
$user = Auth::user();
$user_level = $user['level'] ?? 'user';
$user_name = $user['name'] ?? 'User';

// Check if in specific module
$in_candidates = strpos($current_uri, 'can_') !== false || strpos($current_uri, 'candidates') !== false;
$in_jobs = strpos($current_uri, 'job') !== false || strpos($current_uri, 'jobs') !== false;
$in_users = strpos($current_uri, 'users') !== false || strpos($current_uri, 'user_') !== false;
$in_clients = strpos($current_uri, 'clients') !== false;
$in_contacts = strpos($current_uri, 'contacts') !== false;
$in_applications = strpos($current_uri, 'applications') !== false;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .sidebar {
            width: 260px;
            background: #2d3748;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            left: 0;
            top: 0;
            z-index: 1000;
        }
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        .sidebar-header p {
            font-size: 13px;
            color: rgba(255,255,255,0.7);
        }
        .menu-section-title {
            padding: 10px 20px;
            font-size: 11px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.5);
            font-weight: 600;
            margin-top: 15px;
        }
        .menu-item {
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: block;
            transition: all 0.3s;
            cursor: pointer;
        }
        .menu-item:hover,
        .menu-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .menu-item i {
            margin-right: 10px;
            width: 20px;
            display: inline-block;
        }
        .submenu {
            display: none;
            background: rgba(0,0,0,0.2);
        }
        .submenu.open {
            display: block;
        }
        .submenu .menu-item {
            padding-left: 50px;
            font-size: 14px;
        }
        .logout-btn {
            margin: 20px;
            padding: 10px;
            background: #e53e3e;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: calc(100% - 40px);
            font-size: 14px;
            text-align: center;
            text-decoration: none;
            display: block;
        }
        .logout-btn:hover {
            background: #c53030;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2><?php echo defined('COMPANY_NAME') ? COMPANY_NAME : 'ProConsultancy'; ?></h2>
            <p><?php echo $user_level === 'admin' ? 'Admin Panel' : 'Recruiter Panel'; ?></p>
            <p style="margin-top: 5px; font-size: 12px;"><?php echo htmlspecialchars($user_name); ?></p>
        </div>
        
        <nav>
            <!-- Dashboard -->
            <a href="<?php echo $user_level === 'admin' ? 'admin.php' : 'recruiter.php'; ?>" class="menu-item <?php echo ($current_page == 'admin' || $current_page == 'recruiter' || $current_page == 'dashboard') ? 'active' : ''; ?>">
                <i>📊</i> Dashboard
            </a>
            
            <!-- RECRUITMENT SECTION -->
            <div class="menu-section-title">Recruitment</div>
            
            <!-- Candidates -->
            <div class="menu-item <?php echo $in_candidates ? 'active' : ''; ?>" onclick="toggleSubmenu('candidates')">
                <i>👤</i> Candidates
            </div>
            <div class="submenu <?php echo $in_candidates ? 'open' : ''; ?>" id="submenu-candidates">
                <a href="can_add.php" class="menu-item">➕ Add Candidate</a>
                <a href="can_list.php" class="menu-item">📋 List Candidates</a>
                <a href="can_assigned.php" class="menu-item">👥 Assigned Candidates</a>
                <a href="can_full_view.php" class="menu-item">🔍 Full View</a>
                <a href="can_daily_rep.php" class="menu-item">📊 Daily Report</a>
            </div>
            
            <!-- Jobs -->
            <div class="menu-item <?php echo $in_jobs ? 'active' : ''; ?>" onclick="toggleSubmenu('jobs')">
                <i>💼</i> Jobs
            </div>
            <div class="submenu <?php echo $in_jobs ? 'open' : ''; ?>" id="submenu-jobs">
                <a href="add_job.php" class="menu-item">➕ Post Job</a>
                <a href="list_jobs.php" class="menu-item">📋 List Jobs</a>
                <a href="job_status.php" class="menu-item">⏳ Pending Approval</a>
            </div>
            
            <!-- Applications -->
            <a href="modules/applications/index.php" class="menu-item <?php echo $in_applications ? 'active' : ''; ?>">
                <i>📄</i> Applications
            </a>
            
            <!-- CLIENT MANAGEMENT -->
            <?php if ($user_level === 'admin' || $user_level === 'manager' || $user_level === 'recruiter'): ?>
            <div class="menu-section-title">Client Management</div>
            
            <div class="menu-item <?php echo $in_clients ? 'active' : ''; ?>" onclick="toggleSubmenu('clients')">
                <i>🏢</i> Clients
            </div>
            <div class="submenu <?php echo $in_clients ? 'open' : ''; ?>" id="submenu-clients">
                <a href="modules/clients/create.php" class="menu-item">➕ Add Client</a>
                <a href="modules/clients/list.php" class="menu-item">📋 List Clients</a>
            </div>
            
            <a href="modules/contacts/index.php" class="menu-item <?php echo $in_contacts ? 'active' : ''; ?>">
                <i>📞</i> Contacts
            </a>
            <?php endif; ?>
            
            <!-- COMMUNICATION -->
            <div class="menu-section-title">Communication</div>
            
            <div class="menu-item" onclick="toggleSubmenu('contact-mgmt')">
                <i>📞</i> Contact Manager
            </div>
            <div class="submenu" id="submenu-contact-mgmt">
                <a href="call_candidate.php" class="menu-item">📞 Call Candidates</a>
                <a href="manage_number.php" class="menu-item">🔢 Manage Numbers</a>
            </div>
            
            <a href="collection.php" class="menu-item">
                <i>📁</i> CV Inbox
            </a>
            
            <a href="contact.php" class="menu-item">
                <i>✉️</i> Queries
            </a>
            
            <!-- ADMIN SECTION -->
            <?php if ($user_level === 'admin'): ?>
            <div class="menu-section-title">Administration</div>
            
            <div class="menu-item <?php echo $in_users ? 'active' : ''; ?>" onclick="toggleSubmenu('users')">
                <i>👥</i> User Management
            </div>
            <div class="submenu <?php echo $in_users ? 'open' : ''; ?>" id="submenu-users">
                <a href="modules/users/index.php?action=list" class="menu-item">📋 All Users</a>
                <a href="modules/users/index.php?action=create" class="menu-item">➕ Add User</a>
                <a href="user_login.php" class="menu-item">🕐 Login History</a>
                <a href="assign_user.php" class="menu-item">🔐 Role Assignment</a>
            </div>
            
            <a href="modules/reports/index.php" class="menu-item">
                <i>📊</i> Reports & Analytics
            </a>
            
            <a href="schema-installer.php" class="menu-item" target="_blank">
                <i>🗄️</i> Database Status
            </a>
            <?php endif; ?>
            
            <!-- Logout -->
            <a href="logout.php" class="logout-btn">
                🚪 Logout
            </a>
        </nav>
    </aside>
    
    <script>
        function toggleSubmenu(id) {
            const submenu = document.getElementById('submenu-' + id);
            if (submenu) {
                submenu.classList.toggle('open');
            }
        }
        
        // Auto-open active submenus on page load
        document.addEventListener('DOMContentLoaded', function() {
            const activeItem = document.querySelector('.menu-item.active');
            if (activeItem && activeItem.nextElementSibling) {
                const nextElement = activeItem.nextElementSibling;
                if (nextElement.classList.contains('submenu')) {
                    nextElement.classList.add('open');
                }
            }
        });
    </script>
</body>
</html>
