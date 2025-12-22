<?php
// Get the current page name from the URL
$current_page = basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);
?>

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="dashboard?ss_id=<?php echo $token;?>" class="app-brand-link">
            <span class="demo">
                <img src="<?php echo LOGO_PATH; ?>" style="width: 100%;" />
            </span>
        </a>
        <a class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1" style="padding-top:4rem !important;">
        <!-- Dashboard -->
        <li class="menu-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
            <a href="dashboard?ss_id=<?php echo $token;?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
            </a>
        </li>            

        <!-- User Section -->
        <li class="menu-item <?php echo in_array($current_page, ['assign_user']) ? 'active open' : ''; ?>">
            <a class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div data-i18n="Account Settings">User</div>
            </a>
            <ul class="menu-sub">                
                <li class="menu-item <?php echo ($current_page == 'assign_user') ? 'active' : ''; ?>">
                    <a href="assign_user?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Notifications">Assigned Role</div>
                    </a>
                </li>
            </ul> 
        </li>
        <li class="menu-item <?php echo in_array($current_page, ['can_add', 'can_assigned', 'can_list', 'can_full_view', 'can_hr_comment', 'can_daily_rep', 'can_edit']) ? 'active open' : ''; ?>">
            <a class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div data-i18n="Authentications">Candidate Portal</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'can_add' || $current_page == 'can_edit') ? 'active' : ''; ?>">
                    <a href="can_add?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Basic">Add Candidate</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'can_assigned') ? 'active' : ''; ?>">
                    <a href="can_assigned?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Basic">Assigned Candidate</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'can_list') ? 'active' : ''; ?>">
                    <a href="can_list?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Basic">List Candidate</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'can_full_view') ? 'active' : ''; ?>">
                    <a href="can_full_view?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Basic">Full view</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'can_hr_comment') ? 'active' : ''; ?>">
                    <a href="can_hr_comment?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Basic">Payroll Comment</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'can_daily_rep') ? 'active' : ''; ?>">
                    <a href="can_daily_rep?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Basic">Daily Report</div>
                    </a>
                </li>
            </ul>
        </li>
        <li class="menu-item <?php echo in_array($current_page, ['call_candidate', 'manage_number']) ? 'active open' : ''; ?>">
            <a class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-phone"></i>
                <div data-i18n="Authentications">Contact Manager</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'call_candidate') ? 'active' : ''; ?>">
                    <a href="call_candidate?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Basic">Contact Candidates</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'manage_number') ? 'active' : ''; ?>">
                    <a href="manage_number?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Basic">Fix number</div>
                    </a>
                </li>
                
            </ul>
        </li>
        <li class="menu-item <?php echo in_array($current_page, ['add_job', 'job_status', 'list_jobs', 'approve_jobs', 'view_jobs']) ? 'active open' : ''; ?>">
            <a class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-detail"></i>
                <div data-i18n="Authentications">Job Posts</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'add_job') ? 'active' : ''; ?>">
                    <a href="add_job?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Basic">Add Job</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'job_status' || $current_page == 'approve_jobs') ? 'active' : ''; ?>">
                    <a href="job_status?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Basic">Waiting Posts</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'list_jobs' || $current_page == 'view_jobs') ? 'active' : ''; ?>">
                    <a href="list_jobs?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Basic">List Jobs</div>
                    </a>
                </li>
            </ul>
        </li>

        <li class="menu-item <?php echo ($current_page == 'user_login') ? 'active open' : ''; ?>">
            <a class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-time"></i>
                <div data-i18n="Authentications">User Login</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'user_login') ? 'active' : ''; ?>">
                    <a href="user_login?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Basic">List User</div>
                    </a>
                </li>
            </ul>
        </li>

        <li class="menu-item <?php echo in_array($current_page, ['collection', 'contact']) ? 'active open' : ''; ?>">
            <a class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-file"></i>
                <div data-i18n="Authentications">Cv Data</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo ($current_page == 'collection') ? 'active' : ''; ?>">
                    <a href="collection?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Basic">submitted CV</div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($current_page == 'contact') ? 'active' : ''; ?>">
                    <a href="contact?ss_id=<?php echo $token;?>" class="menu-link">
                        <div data-i18n="Basic">Queries</div>
                    </a>
                </li>
            </ul>
        </li>
        <?php if (Auth::user()['level'] === 'admin'): ?>
        <!-- USER MANAGEMENT -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-group"></i>
                <div data-i18n="User Management">User Management</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="modules/users/?action=list&ss_id=<?php echo $token; ?>" class="menu-link">
                        <div data-i18n="All Users">All Users</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="modules/users/?action=create&ss_id=<?php echo $token; ?>" class="menu-link">
                        <div data-i18n="Add User">Add User</div>
                    </a>
                </li>
            </ul>
        </li>
        <?php endif; ?>
        <!-- Logout -->
        <li class="menu-item">
            <a href="logout" class="menu-link">
                <span class="badge bg-label-danger me-1"><i class="bx bx-power-off"></i></span>
                <div data-i18n="Basic">Logout</div>
            </a>
        </li>
    </ul>
    <div id="alertContainer" class="mb-4"></div>
</aside>
