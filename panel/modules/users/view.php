<?php
/**
 * User View/Profile Page
 * File: panel/modules/users/view.php
 * Included by index.php
 */

if (!defined('Auth')) {
    die('Direct access not permitted');
}

// Get user ID from index.php
if (!isset($id) || !$id) {
    throw new Exception('User ID is required');
}

// Fetch user details with activity stats
$stmt = $conn->prepare("
    SELECT u.*,
           (SELECT COUNT(*) FROM jobs WHERE created_by = u.user_code AND deleted_at IS NULL) as jobs_created,
           (SELECT COUNT(*) FROM candidates WHERE created_by = u.user_code) as candidates_created,
           (SELECT COUNT(*) FROM job_applications WHERE created_by = u.user_code AND deleted_at IS NULL) as applications_created,
           (SELECT COUNT(*) FROM job_applications ja 
            JOIN jobs j ON ja.job_id = j.job_id 
            WHERE j.created_by = u.user_code AND ja.status = 'placed') as placements_count
    FROM user u
    WHERE u.id = ?
");

$stmt->bind_param('i', $id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

if (!$user_data) {
    throw new Exception('User not found');
}

// Get recent activity
$activity_query = "
    SELECT al.*, j.job_title, c.candidate_name
    FROM activity_log al
    LEFT JOIN jobs j ON al.entity_type = 'job' AND al.entity_id = j.job_id
    LEFT JOIN candidates c ON al.entity_type = 'candidate' AND al.entity_id = 0 -- Simplified
    WHERE al.user_code = ?
    ORDER BY al.created_at DESC
    LIMIT 10
";

$stmt = $conn->prepare($activity_query);
$stmt->bind_param('s', $user_data['user_code']);
$stmt->execute();
$activity_result = $stmt->get_result();
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bx bx-user-circle me-2"></i> <?php echo htmlspecialchars($user_data['name']); ?>
            </h4>
            <p class="text-muted mb-0">User Profile</p>
        </div>
        <div>
            <a href="?action=edit&id=<?php echo $id; ?>&ss_id=<?php echo $token; ?>" class="btn btn-primary me-2">
                <i class="bx bx-edit me-1"></i> Edit User
            </a>
            <a href="?action=list&ss_id=<?php echo $token; ?>" class="btn btn-secondary">
                <i class="bx bx-arrow-back me-1"></i> Back to Users
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: User Info -->
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="avatar avatar-xl bg-label-<?php echo $user_data['level'] === 'admin' ? 'danger' : 'info'; ?> mx-auto mb-3">
                        <i class="bx bx-user bx-lg"></i>
                    </div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($user_data['name']); ?></h4>
                    <p class="text-muted mb-2"><?php echo htmlspecialchars($user_data['email']); ?></p>
                    <div class="mb-3">
                        <?php if ($user_data['level'] === 'admin'): ?>
                            <span class="badge bg-danger">
                                <i class="bx bx-shield me-1"></i> Administrator
                            </span>
                        <?php else: ?>
                            <span class="badge bg-info">
                                <i class="bx bx-user me-1"></i> Recruiter
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($user_data['status'] === 'active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-label-secondary"><?php echo htmlspecialchars($user_data['user_code']); ?></span>
                </div>
            </div>

            <!-- Contact Info Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Contact Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Email</label>
                        <p class="mb-0">
                            <a href="mailto:<?php echo htmlspecialchars($user_data['email']); ?>">
                                <i class="bx bx-envelope me-1"></i>
                                <?php echo htmlspecialchars($user_data['email']); ?>
                            </a>
                        </p>
                    </div>
                    
                    <?php if ($user_data['phone']): ?>
                    <div class="mb-3">
                        <label class="text-muted small">Phone</label>
                        <p class="mb-0">
                            <a href="tel:<?php echo htmlspecialchars($user_data['phone']); ?>">
                                <i class="bx bx-phone me-1"></i>
                                <?php echo htmlspecialchars($user_data['phone']); ?>
                            </a>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="text-muted small">Member Since</label>
                        <p class="mb-0"><?php echo date('F d, Y', strtotime($user_data['created_at'])); ?></p>
                    </div>
                    
                    <?php if ($user_data['updated_at']): ?>
                    <div>
                        <label class="text-muted small">Last Updated</label>
                        <p class="mb-0"><?php echo date('F d, Y', strtotime($user_data['updated_at'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($user_data['notes']): ?>
            <!-- Notes Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Notes</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($user_data['notes'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Stats & Activity -->
        <div class="col-lg-8">
            <!-- Activity Statistics -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="avatar avatar-md bg-label-primary mx-auto mb-2">
                                <i class="bx bx-briefcase bx-sm"></i>
                            </div>
                            <h3 class="mb-0"><?php echo $user_data['jobs_created']; ?></h3>
                            <small class="text-muted">Jobs Created</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="avatar avatar-md bg-label-info mx-auto mb-2">
                                <i class="bx bx-user-check bx-sm"></i>
                            </div>
                            <h3 class="mb-0"><?php echo $user_data['candidates_created']; ?></h3>
                            <small class="text-muted">Candidates Added</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="avatar avatar-md bg-label-warning mx-auto mb-2">
                                <i class="bx bx-file bx-sm"></i>
                            </div>
                            <h3 class="mb-0"><?php echo $user_data['applications_created']; ?></h3>
                            <small class="text-muted">Applications</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="avatar avatar-md bg-label-success mx-auto mb-2">
                                <i class="bx bx-trophy bx-sm"></i>
                            </div>
                            <h3 class="mb-0"><?php echo $user_data['placements_count']; ?></h3>
                            <small class="text-muted">Placements</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($activity_result) === 0): ?>
                        <p class="text-muted text-center py-4">No activity recorded yet</p>
                    <?php else: ?>
                        <ul class="timeline mb-0">
                            <?php while ($activity = mysqli_fetch_assoc($activity_result)): ?>
                            <li class="timeline-item timeline-item-transparent">
                                <span class="timeline-point timeline-point-primary"></span>
                                <div class="timeline-event">
                                    <div class="timeline-header mb-1">
                                        <h6 class="mb-0"><?php echo ucwords(str_replace('_', ' ', $activity['action'])); ?></h6>
                                        <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></small>
                                    </div>
                                    <?php if ($activity['description']): ?>
                                        <p class="mb-0"><?php echo htmlspecialchars($activity['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>