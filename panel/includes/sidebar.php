<?php
/**
 * PRODUCTION-READY SIDEBAR NAVIGATION
 * File: panel/includes/sidebar.php

 */

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_uri = $_SERVER['REQUEST_URI'];

$user = Auth::user();
$user_level = $user['level'] ?? 'user';
$user_name = $user['name'] ?? 'User';

// Detect active modules
$in_candidates = strpos($current_uri, 'candidates') !== false;
$in_jobs = strpos($current_uri, 'jobs') !== false || strpos($current_uri, 'cv') !== false;
$in_applications = strpos($current_uri, 'applications') !== false;
$in_contacts = strpos($current_uri, 'contacts') !== false;
$in_clients = strpos($current_uri, 'clients') !== false;
$in_users = strpos($current_uri, 'users') !== false;
$in_reports = strpos($current_uri, 'reports') !== false;
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2><?php echo defined('COMPANY_NAME') ? COMPANY_NAME : 'ProConsultancy'; ?></h2>
        <p><?php echo $user_level === 'admin' ? 'Admin Panel' : 'Recruiter Panel'; ?></p>
        <p style="margin-top: 5px; font-size: 12px;"><?php echo htmlspecialchars($user_name); ?></p>
    </div>
    
    <nav>
        <!-- Dashboard -->
        <a href="<?php echo $user_level === 'admin' ? 'admin.php' : 'recruiter.php'; ?>" 
           class="menu-item <?php echo ($current_page == 'admin' || $current_page == 'recruiter') ? 'active' : ''; ?>">
            📊 Dashboard
        </a>
        
        <!-- CANDIDATES MODULE -->
        <div class="menu-section-title">Talent Management</div>
        
        <div class="menu-item <?php echo $in_candidates ? 'active' : ''; ?>" onclick="toggleSubmenu('candidates')">
            👤 Candidates
        </div>
        <div class="submenu <?php echo $in_candidates ? 'open' : ''; ?>" id="submenu-candidates">
            <a href="modules/candidates/create.php" class="menu-item">➕ Add Candidate</a>
            <a href="modules/candidates/list.php" class="menu-item">📋 All Candidates</a>
            <?php if ($user_level === 'recruiter' || $user_level === 'manager'): ?>
            <a href="modules/candidates/assigned.php" class="menu-item">👥 Assigned to Me</a>
            <?php endif; ?>
            <?php if ($user_level === 'admin'): ?>
            <a href="modules/candidates/assigned.php" class="menu-item">👥 Assignment Overview</a>
            <?php endif; ?>
            <a href="modules/candidates/pipeline.php" class="menu-item">📊 Pipeline View</a>
        </div>
        
        <!-- CONTACT REQUESTS  -->
        <div class="menu-item <?php echo $in_contacts ? 'active' : ''; ?>" onclick="toggleSubmenu('contacts')">
            📞 Contact Requests
        </div>
        <div class="submenu <?php echo $in_contacts ? 'open' : ''; ?>" id="submenu-contacts">
            <a href="modules/contacts/list.php" class="menu-item">📋 All Requests</a>
            <a href="modules/contacts/create.php" class="menu-item">➕ Add Contact</a>
            <a href="modules/contacts/convert.php" class="menu-item">🔄 Convert to Candidate</a>
        </div>
        
        <!-- JOBS MODULE -->
        <div class="menu-section-title">Job Management</div>
        
        <div class="menu-item <?php echo $in_jobs ? 'active' : ''; ?>" onclick="toggleSubmenu('jobs')">
            💼 Job Opportunities
        </div>
        <div class="submenu <?php echo $in_jobs ? 'open' : ''; ?>" id="submenu-jobs">
            <a href="modules/jobs/create.php" class="menu-item">➕ Post New Job</a>
            <a href="modules/jobs/list.php" class="menu-item">📋 All Jobs</a>
            <a href="modules/jobs/cv/inbox.php" class="menu-item">📥 CV Inbox</a>
            <?php if ($user_level === 'admin' || $user_level === 'manager'): ?>
            <a href="modules/jobs/approve.php" class="menu-item">⏳ Pending Approval</a>
            <?php endif; ?>
        </div>
        
        <!-- APPLICATIONS MODULE -->
        <div class="menu-item <?php echo $in_applications ? 'active' : ''; ?>" onclick="toggleSubmenu('applications')">
            📄 Applications
        </div>
        <div class="submenu <?php echo $in_applications ? 'open' : ''; ?>" id="submenu-applications">
            <a href="modules/applications/list.php" class="menu-item">📋 All Applications</a>
            <a href="modules/applications/pipeline.php" class="menu-item">📊 Pipeline View</a>
            <?php if ($user_level === 'admin' || $user_level === 'manager'): ?>
            <a href="modules/applications/pending_approval.php" class="menu-item">⏳ Pending Approval</a>
            <?php endif; ?>
        </div>
        
        <!-- CLIENT MANAGEMENT (Admin, Manager, Recruiter) -->
        <?php if ($user_level === 'admin' || $user_level === 'manager' || $user_level === 'recruiter'): ?>
        <div class="menu-section-title">Client Relations</div>
        
        <div class="menu-item <?php echo $in_clients ? 'active' : ''; ?>" onclick="toggleSubmenu('clients')">
            🏢 Clients
        </div>
        <div class="submenu <?php echo $in_clients ? 'open' : ''; ?>" id="submenu-clients">
            <a href="modules/clients/create.php" class="menu-item">➕ Add Client</a>
            <a href="modules/clients/list.php" class="menu-item">📋 All Clients</a>
        </div>
        <?php endif; ?>
        
        <!-- ADMIN ONLY SECTION -->
        <?php if ($user_level === 'admin'): ?>
        <div class="menu-section-title">Administration</div>
        
        <div class="menu-item <?php echo $in_users ? 'active' : ''; ?>" onclick="toggleSubmenu('users')">
            👥 Team Management
        </div>
        <div class="submenu <?php echo $in_users ? 'open' : ''; ?>" id="submenu-users">
            <a href="modules/users/create.php" class="menu-item">➕ Add User</a>
            <a href="modules/users/list.php" class="menu-item">📋 All Users</a>
        </div>
        
        <div class="menu-item <?php echo $in_reports ? 'active' : ''; ?>" onclick="toggleSubmenu('reports')">
            📊 Reports & Analytics
        </div>
        <div class="submenu <?php echo $in_reports ? 'open' : ''; ?>" id="submenu-reports">
            <a href="modules/reports/index.php" class="menu-item">📊 Dashboard</a>
            <a href="modules/reports/daily_report.php" class="menu-item">📅 Daily Report</a>
        </div>
        
        <a href="system-health.php" class="menu-item">
            🏥 System Health
        </a>

        <?php endif; ?>
        
        <!-- Logout -->
        <a href="logout.php" class="logout-btn">
            🚪 Logout
        </a>
    </nav>
</aside>

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
.sidebar-header h2 { font-size: 20px; margin-bottom: 5px; color: white; }
.sidebar-header p { font-size: 13px; color: rgba(255,255,255,0.7); }
.menu-section-title {
    padding: 15px 20px 8px;
    font-size: 11px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.5);
    font-weight: 600;
    letter-spacing: 0.5px;
}
.menu-item {
    padding: 12px 20px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    display: block;
    transition: all 0.3s;
    cursor: pointer;
    font-size: 14px;
}
.menu-item:hover, .menu-item.active {
    background: rgba(255,255,255,0.1);
    color: white;
}
.submenu {
    display: none;
    background: rgba(0,0,0,0.2);
}
.submenu.open { display: block; }
.submenu .menu-item { 
    padding-left: 50px; 
    font-size: 13px;
    padding-top: 10px;
    padding-bottom: 10px;
}
.logout-btn {
    margin: 20px;
    padding: 12px;
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
    font-weight: 600;
}
.logout-btn:hover { background: #c53030; }

/* Scrollbar styling for sidebar */
.sidebar::-webkit-scrollbar { width: 6px; }
.sidebar::-webkit-scrollbar-track { background: rgba(0,0,0,0.2); }
.sidebar::-webkit-scrollbar-thumb { 
    background: rgba(255,255,255,0.3); 
    border-radius: 3px;
}
.sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.5); }

@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
    .sidebar.mobile-open { transform: translateX(0); }
}
</style>

<script>
function toggleSubmenu(id) {
    const submenu = document.getElementById('submenu-' + id);
    if (submenu) {
        submenu.classList.toggle('open');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Auto-open active submenus
    const activeItems = document.querySelectorAll('.menu-item.active');
    activeItems.forEach(function(item) {
        if (item.nextElementSibling && item.nextElementSibling.classList.contains('submenu')) {
            item.nextElementSibling.classList.add('open');
        }
    });
});
</script>