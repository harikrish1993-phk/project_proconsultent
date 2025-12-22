<?php
// Get current page
$current_page = basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);

// User level
$user_level = Auth::user()['level'] ?? 'user';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Proconsultancy</h2>
        <p><?php echo ($user_level === 'admin') ? 'Admin Panel' : 'Recruiter Panel'; ?></p>
    </div>
    <nav class="sidebar-menu">
        <ul>
            <li class="<?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>"><a href="dashboard?ss_id=<?php echo Auth::token(); ?>"><i class="bx bx-home-circle"></i> Dashboard</a></li>
            <li class="menu-header small text-uppercase"><span class="menu-header-text">Recruitment</span></li>
            <?php if ($user_level === 'admin'): ?>
            <li class="<?php echo strpos($current_page, 'candidates') !== false ? 'active' : ''; ?>"><a href="modules/candidates/index.php?action=list"><i class="bx bx-user-pin"></i> Candidate Management</a></li>
            <li class="<?php echo strpos($current_page, 'jobs') !== false ? 'active' : ''; ?>"><a href="modules/jobs/index.php?action=list"><i class="bx bx-briefcase"></i> Job Management</a></li>
            <li class="<?php echo ($current_page == 'approvals') ? 'active' : ''; ?>"><a href="modules/jobs/approve.php"><i class="bx bx-check-shield"></i> Approvals</a></li>
            <li class="menu-header small text-uppercase"><span class="menu-header-text">Administration</span></li>
            <li class="<?php echo strpos($current_page, 'users') !== false ? 'active' : ''; ?>"><a href="modules/users/index.php"><i class="bx bx-group"></i> User Management</a></li>
            <li class="<?php echo ($current_page == 'settings_admin') ? 'active' : ''; ?>"><a href="settings_admin.php"><i class="bx bx-cog"></i> Settings</a></li>
            <li class="<?php echo strpos($current_page, 'reports') !== false ? 'active' : ''; ?>"><a href="modules/reports/index.php"><i class="bx bx-bar-chart-alt-2"></i> Reports</a></li>
            <?php else: ?>
            <li class="<?php echo ($current_page == 'candidates' && $_GET['action'] == 'list') ? 'active' : ''; ?>"><a href="modules/candidates/index.php?action=list"><i class="bx bx-user-pin"></i> Candidate List</a></li>
            <li class="<?php echo ($current_page == 'candidates' && $_GET['action'] == 'create') ? 'active' : ''; ?>"><a href="modules/candidates/index.php?action=create"><i class="bx bx-user-plus"></i> Add Candidate</a></li>
            <li class="menu-item">
                <a class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-building"></i>
                    <div>Clients</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="modules/clients/create.php" class="menu-link">
                            <div>Add Client</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="modules/clients/list.php" class="menu-link">
                            <div>List Clients</div>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="<?php echo ($current_page == 'pipeline') ? 'active' : ''; ?>"><a href="modules/candidates/index.php?action=pipeline"><i class="bx bx-git-branch"></i> Pipeline View</a></li>
            <li class="<?php echo ($current_page == 'jobs' && $_GET['action'] == 'list') ? 'active' : ''; ?>"><a href="modules/jobs/index.php?action=list"><i class="bx bx-briefcase"></i> Job Management</a></li>
            <li class="<?php echo ($current_page == 'jobs' && $_GET['action'] == 'create') ? 'active' : ''; ?>"><a href="modules/jobs/index.php?action=create"><i class="bx bx-plus-circle"></i> Post New Job</a></li>
            <li class="menu-header small text-uppercase"><span class="menu-header-text">Reporting & Tools</span></li>
            <li class="menu-item"> <a href="/modules/contacts/index.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-user-plus"></i>
                    <div>Contacts</div>
                </a>
            </li>
            <li class="menu-item <?php echo $currentModule === 'applications' ? 'active' : ''; ?>">
            <a href="<?php echo BASE_URL; ?>/panel/modules/applications/" class="menu-link">
                <i class="menu-icon bx bx-briefcase"></i>
                <div>Applications</div>
                <?php
                // Get pending count
                $pendingCount = mysqli_fetch_assoc(mysqli_query(dbConnect(), 
                    "SELECT COUNT(*) as count FROM job_applications WHERE status = 'pending_approval' AND deleted_at IS NULL"
                ))['count'];
                ?>
                <?php if ($pendingCount > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
            <li class="<?php echo ($current_page == 'daily-report') ? 'active' : ''; ?>"><a href="modules/candidates/index.php?action=daily_report"><i class="bx bx-bar-chart-alt-2"></i> Daily Report</a></li>
            <li class="<?php echo ($current_page == 'settings_user') ? 'active' : ''; ?>"><a href="settings_user.php"><i class="bx bx-cog"></i> My Settings</a></li>
            <?php endif; ?>
            <li><a href="logout.php"><i class="bx bx-power-off"></i> Logout</a></li>
        </ul>
    </nav>
</aside>

<div class="content-wrapper">
    <!-- Page content starts here -->