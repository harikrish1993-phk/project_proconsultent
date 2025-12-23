<?php
/**
 * Users List Page
 * File: panel/modules/users/list.php
 * Included by index.php
 */

// Load common bootstrap
require_once __DIR__ . '/../_common.php';
// Page configuration

$pageTitle = 'Team List';
$breadcrumbs = [
    'Team' => '#'
];
// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// Display breadcrumb
echo renderBreadcrumb($breadcrumbs);

// Fetch users with statistics
$users_query = "
    SELECT u.*,
           (SELECT COUNT(*) FROM jobs WHERE created_by = u.user_code) as jobs_count,
           (SELECT COUNT(*) FROM candidates WHERE created_by = u.user_code) as candidates_count,
           (SELECT COUNT(*) FROM job_applications WHERE created_by = u.user_code) as applications_count
    FROM user u
    ORDER BY u.created_at DESC
";

$users_result = mysqli_query($conn, $users_query);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN level = 'admin' THEN 1 ELSE 0 END) as admin_count,
        SUM(CASE WHEN level = 'user' THEN 1 ELSE 0 END) as recruiter_count,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count
    FROM user
";

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bx bx-group me-2"></i> User Management
            </h4>
            <p class="text-muted mb-0">Manage system users and permissions</p>
        </div>
        <a href="?action=create&ss_id=<?php echo $token; ?>" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> Add New User
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar avatar-md bg-label-primary mx-auto mb-2">
                        <i class="bx bx-group bx-sm"></i>
                    </div>
                    <h4 class="mb-0"><?php echo $stats['total_users']; ?></h4>
                    <small class="text-muted">Total Users</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar avatar-md bg-label-danger mx-auto mb-2">
                        <i class="bx bx-shield bx-sm"></i>
                    </div>
                    <h4 class="mb-0"><?php echo $stats['admin_count']; ?></h4>
                    <small class="text-muted">Admins</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar avatar-md bg-label-info mx-auto mb-2">
                        <i class="bx bx-user bx-sm"></i>
                    </div>
                    <h4 class="mb-0"><?php echo $stats['recruiter_count']; ?></h4>
                    <small class="text-muted">Recruiters</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar avatar-md bg-label-success mx-auto mb-2">
                        <i class="bx bx-check-circle bx-sm"></i>
                    </div>
                    <h4 class="mb-0"><?php echo $stats['active_count']; ?></h4>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar avatar-md bg-label-secondary mx-auto mb-2">
                        <i class="bx bx-x-circle bx-sm"></i>
                    </div>
                    <h4 class="mb-0"><?php echo $stats['inactive_count']; ?></h4>
                    <small class="text-muted">Inactive</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Users</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="usersTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>User Code</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Activity</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-3 bg-label-<?php echo $user['level'] === 'admin' ? 'danger' : 'info'; ?>">
                                        <i class="bx bx-user"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-label-secondary"><?php echo htmlspecialchars($user['user_code']); ?></span>
                            </td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($user['level'] === 'admin'): ?>
                                    <span class="badge bg-danger">
                                        <i class="bx bx-shield me-1"></i> Admin
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-info">
                                        <i class="bx bx-user me-1"></i> Recruiter
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge bg-label-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-label-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo $user['jobs_count']; ?> Jobs<br>
                                    <?php echo $user['candidates_count']; ?> Candidates<br>
                                    <?php echo $user['applications_count']; ?> Applications
                                </small>
                            </td>
                            <td>
                                <small><?php echo date('M d, Y', strtotime($user['created_at'])); ?></small>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-label-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="?action=view&id=<?php echo $user['id']; ?>&ss_id=<?php echo $token; ?>">
                                            <i class="bx bx-show me-2"></i> View
                                        </a>
                                        <a class="dropdown-item" href="?action=edit&id=<?php echo $user['id']; ?>&ss_id=<?php echo $token; ?>">
                                            <i class="bx bx-edit me-2"></i> Edit
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <a class="dropdown-item text-warning" href="#" onclick="toggleStatus(<?php echo $user['id']; ?>, 'inactive')">
                                                <i class="bx bx-x-circle me-2"></i> Deactivate
                                            </a>
                                        <?php else: ?>
                                            <a class="dropdown-item text-success" href="#" onclick="toggleStatus(<?php echo $user['id']; ?>, 'active')">
                                                <i class="bx bx-check-circle me-2"></i> Activate
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($user['user_code'] !== Auth::user()['user_code']): ?>
                                            <a class="dropdown-item text-danger" href="#" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                                <i class="bx bx-trash me-2"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        pageLength: 25,
        order: [[6, 'desc']], // Sort by created date
        columnDefs: [
            { orderable: false, targets: [5, 7] }
        ]
    });
});

function toggleStatus(userId, newStatus) {
    if (confirm('Change user status to ' + newStatus + '?')) {
        $.post('handlers/user_status_handler.php', {
            user_id: userId,
            status: newStatus,
            token: '<?php echo Auth::token(); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json');
    }
}

function deleteUser(userId, userName) {
    if (!confirm('Are you sure you want to delete user "' + userName + '"? This action cannot be undone.')) {
        return;
    }
    
    if (!confirm('Final confirmation: Delete user permanently?')) {
        return;
    }
    
    $.post('handlers/user_delete_handler.php', {
        user_id: userId,
        token: '<?php echo Auth::token(); ?>'
    }, function(response) {
        if (response.success) {
            alert('User deleted successfully');
            location.reload();
        } else {
            alert('Error: ' + response.message);
        }
    }, 'json');
}
</script>

<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">