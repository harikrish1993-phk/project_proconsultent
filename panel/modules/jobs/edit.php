<?php
/**
 * Job View Page - Complete Hub
 * File: panel/modules/jobs/view.php
 * Shows: Job details, Applications, CVs received, Activity
 */

// Load common bootstrap
require_once __DIR__ . '/../_common.php';


$db = Database::getInstance();
$conn = $db->getConnection();
$user = Auth::user();
$token = $_GET['ss_id'] ?? '';

// Get job ID
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$job_id) {
    die('Job ID required');
}

// Fetch job details
$stmt = $conn->prepare("
    SELECT j.*, 
           c.client_name,
           c.contact_person,
           c.email as client_email,
           c.phone as client_phone,
           creator.name as created_by_name,
           approver.name as approved_by_name
    FROM jobs j
    LEFT JOIN clients c ON j.client_id = c.client_id
    LEFT JOIN user creator ON j.created_by = creator.user_code
    LEFT JOIN user approver ON j.approved_by = approver.user_code
    WHERE j.job_id = ? AND j.deleted_at IS NULL
");

$stmt->bind_param('i', $job_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    die('Job not found');
}

// Get statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM job_applications WHERE job_id = ? AND deleted_at IS NULL) as total_applications,
        (SELECT COUNT(*) FROM job_applications WHERE job_id = ? AND status = 'screening' AND deleted_at IS NULL) as screening,
        (SELECT COUNT(*) FROM job_applications WHERE job_id = ? AND status = 'interviewing' AND deleted_at IS NULL) as interviewing,
        (SELECT COUNT(*) FROM job_applications WHERE job_id = ? AND status = 'placed' AND deleted_at IS NULL) as placed,
        (SELECT COUNT(*) FROM cv_inbox WHERE job_id = ? AND status != 'rejected') as total_cvs,
        (SELECT COUNT(*) FROM cv_inbox WHERE job_id = ? AND status = 'new') as new_cvs
";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param('iiiiii', $job_id, $job_id, $job_id, $job_id, $job_id, $job_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get applications for this job
$apps_query = "
    SELECT ja.*, 
           c.candidate_name,
           c.email_id,
           c.phone
    FROM job_applications ja
    JOIN candidates c ON ja.can_code = c.can_code
    WHERE ja.job_id = ? AND ja.deleted_at IS NULL
    ORDER BY ja.created_at DESC
    LIMIT 20
";

$stmt = $conn->prepare($apps_query);
$stmt->bind_param('i', $job_id);
$stmt->execute();
$applications = $stmt->get_result();

// Get CVs for this job
$cvs_query = "
    SELECT cv.*,
           u.name as assigned_to_name
    FROM cv_inbox cv
    LEFT JOIN user u ON cv.assigned_to = u.user_code
    WHERE cv.job_id = ?
    ORDER BY cv.submitted_at DESC
    LIMIT 20
";

$stmt = $conn->prepare($cvs_query);
$stmt->bind_param('i', $job_id);
$stmt->execute();
$cvs = $stmt->get_result();

// Get activity log
$activity_query = "
    SELECT al.*, u.name as user_name
    FROM activity_log al
    LEFT JOIN user u ON al.user_code = u.user_code
    WHERE al.entity_type = 'job' AND al.entity_id = ?
    ORDER BY al.created_at DESC
    LIMIT 15
";

$stmt = $conn->prepare($activity_query);
$stmt->bind_param('i', $job_id);
$stmt->execute();
$activity = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($job['job_title']); ?> - Job Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        .status-badge-pending { background-color: #ffab00; }
        .status-badge-active { background-color: #71dd37; }
        .status-badge-closed { background-color: #8592a3; }
    </style>
</head>
<body>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h3 class="mb-1"><?php echo htmlspecialchars($job['job_title']); ?></h3>
                    <div class="d-flex align-items-center gap-3 mt-2">
                        <span class="badge bg-label-secondary"><?php echo htmlspecialchars($job['job_code']); ?></span>
                        <span class="badge status-badge-<?php echo $job['job_status']; ?> text-white">
                            <?php echo ucfirst($job['job_status']); ?>
                        </span>
                        <span class="text-muted">
                            <i class="bx bx-building"></i> <?php echo htmlspecialchars($job['client_name']); ?>
                        </span>
                        <span class="text-muted">
                            <i class="bx bx-calendar"></i> Posted <?php echo date('M d, Y', strtotime($job['posted_date'] ?? $job['created_at'])); ?>
                        </span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($job['job_status'] === 'pending' && $user['level'] === 'admin'): ?>
                        <a href="index.php?action=approve&id=<?php echo $job_id; ?>&ss_id=<?php echo $token; ?>" class="btn btn-success">
                            <i class="bx bx-check-circle"></i> Approve
                        </a>
                    <?php endif; ?>
                    <a href="index.php?action=edit&id=<?php echo $job_id; ?>&ss_id=<?php echo $token; ?>" class="btn btn-primary">
                        <i class="bx bx-edit"></i> Edit
                    </a>
                    <button class="btn btn-danger" onclick="closeJob()">
                        <i class="bx bx-x-circle"></i> Close
                    </button>
                    <a href="index.php?action=list&ss_id=<?php echo $token; ?>" class="btn btn-secondary">
                        <i class="bx bx-arrow-back"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Main Content -->
        <div class="col-lg-8">
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#details-tab">
                        <i class="bx bx-detail"></i> Details
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#applications-tab">
                        <i class="bx bx-user-check"></i> Applications (<?php echo $stats['total_applications']; ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#cvs-tab">
                        <i class="bx bx-file"></i> CVs Received (<?php echo $stats['total_cvs']; ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#activity-tab">
                        <i class="bx bx-time"></i> Activity
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Details Tab -->
                <div class="tab-pane fade show active" id="details-tab">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Job Description</h5>
                        </div>
                        <div class="card-body">
                            <?php echo $job['description']; ?>
                        </div>
                    </div>

                    <?php if ($job['requirements']): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Requirements</h5>
                        </div>
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($job['internal_notes'] && $user['level'] === 'admin'): ?>
                    <div class="card mt-3 bg-light">
                        <div class="card-header bg-transparent">
                            <h6 class="mb-0 text-warning">
                                <i class="bx bx-lock-alt"></i> Internal Notes (Admin Only)
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($job['internal_notes'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1">
                    <label class="form-check-label" for="is_public">
                        <strong>Make this job public</strong>
                        <small class="text-muted d-block">
                            When enabled, this job will appear on the public careers page and accept applications from candidates
                        </small>
                    </label>
                </div>
            </div>
                <!-- Applications Tab -->
                <div class="tab-pane fade" id="applications-tab">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Applications for This Job</h5>
                            <button class="btn btn-sm btn-primary" onclick="showApplyCandidateModal()">
                                <i class="bx bx-plus"></i> Apply Candidate
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($applications) === 0): ?>
                                <div class="text-center py-4">
                                    <i class="bx bx-user-x bx-lg text-muted mb-3" style="font-size: 3rem;"></i>
                                    <h5>No Applications Yet</h5>
                                    <p class="text-muted">Applications will appear here when candidates apply</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Candidate</th>
                                                <th>Status</th>
                                                <th>Applied Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($app = mysqli_fetch_assoc($applications)): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($app['candidate_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($app['email_id']); ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_colors = [
                                                        'applied' => 'secondary',
                                                        'screening' => 'info',
                                                        'interviewing' => 'warning',
                                                        'offered' => 'primary',
                                                        'placed' => 'success',
                                                        'rejected' => 'danger'
                                                    ];
                                                    $color = $status_colors[$app['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-label-<?php echo $color; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                                                <td>
                                                    <a href="../applications/view.php?id=<?php echo $app['application_id']; ?>&ss_id=<?php echo $token; ?>" class="btn btn-sm btn-info">
                                                        <i class="bx bx-show"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="../applications/list.php?job_id=<?php echo $job_id; ?>&ss_id=<?php echo $token; ?>" class="btn btn-link">
                                        View All Applications →
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- CVs Tab -->
                <div class="tab-pane fade" id="cvs-tab">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">CV Submissions</h5>
                            <?php if ($stats['new_cvs'] > 0): ?>
                                <span class="badge bg-primary"><?php echo $stats['new_cvs']; ?> New</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($cvs) === 0): ?>
                                <div class="text-center py-4">
                                    <i class="bx bx-file-blank bx-lg text-muted mb-3" style="font-size: 3rem;"></i>
                                    <h5>No CVs Received</h5>
                                    <p class="text-muted">CV submissions will appear here</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Candidate</th>
                                                <th>Status</th>
                                                <th>Assigned To</th>
                                                <th>Submitted</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($cv = mysqli_fetch_assoc($cvs)): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($cv['candidate_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($cv['email']); ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $cv_status_colors = [
                                                        'new' => 'primary',
                                                        'reviewed' => 'info',
                                                        'shortlisted' => 'success',
                                                        'rejected' => 'danger',
                                                        'converted' => 'secondary'
                                                    ];
                                                    $cv_color = $cv_status_colors[$cv['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-label-<?php echo $cv_color; ?>">
                                                        <?php echo ucfirst($cv['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($cv['assigned_to_name'] ?? 'Unassigned'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($cv['submitted_at'])); ?></td>
                                                <td>
                                                    <a href="cv/view.php?id=<?php echo $cv['id']; ?>&ss_id=<?php echo $token; ?>" class="btn btn-sm btn-info">
                                                        <i class="bx bx-show"></i> View
                                                    </a>
                                                    <?php if ($cv['status'] !== 'converted'): ?>
                                                        <a href="cv/convert.php?id=<?php echo $cv['id']; ?>&ss_id=<?php echo $token; ?>" class="btn btn-sm btn-success">
                                                            <i class="bx bx-transfer"></i> Convert
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="cv/inbox.php?job_id=<?php echo $job_id; ?>&ss_id=<?php echo $token; ?>" class="btn btn-link">
                                        View All CVs →
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Activity Tab -->
                <div class="tab-pane fade" id="activity-tab">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Activity Log</h5>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($activity) === 0): ?>
                                <p class="text-muted">No activity yet</p>
                            <?php else: ?>
                                <div class="timeline">
                                    <?php while ($act = mysqli_fetch_assoc($activity)): ?>
                                    <div class="timeline-item mb-3">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?php echo htmlspecialchars($act['action']); ?></strong>
                                                <p class="mb-0 text-muted small"><?php echo htmlspecialchars($act['description'] ?? ''); ?></p>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted"><?php echo htmlspecialchars($act['user_name'] ?? 'System'); ?></small><br>
                                                <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($act['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Info & Actions -->
        <div class="col-lg-4">
            <!-- Quick Stats -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Quick Stats</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Applications:</span>
                        <strong><?php echo $stats['total_applications']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>In Screening:</span>
                        <strong><?php echo $stats['screening']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Interviewing:</span>
                        <strong><?php echo $stats['interviewing']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Placed:</span>
                        <strong class="text-success"><?php echo $stats['placed']; ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>CVs Received:</span>
                        <strong><?php echo $stats['total_cvs']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>New CVs:</span>
                        <strong class="text-primary"><?php echo $stats['new_cvs']; ?></strong>
                    </div>
                </div>
            </div>

            <!-- Job Info -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Job Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Client:</small>
                        <p class="mb-0">
                            <strong><?php echo htmlspecialchars($job['client_name']); ?></strong>
                        </p>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Type:</small>
                        <p class="mb-0">Freelance</p>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Location:</small>
                        <p class="mb-0">Belgium</p>
                    </div>
                    <?php if ($job['salary_min']): ?>
                    <div class="mb-2">
                        <small class="text-muted">Daily Rate:</small>
                        <p class="mb-0"><strong>€<?php echo number_format($job['salary_min']); ?></strong></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($job['experience_required']): ?>
                    <div class="mb-2">
                        <small class="text-muted">Experience:</small>
                        <p class="mb-0"><?php echo htmlspecialchars($job['experience_required']); ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="mb-2">
                        <small class="text-muted">Priority:</small>
                        <p class="mb-0">
                            <span class="badge bg-label-<?php 
                                echo $job['priority'] === 'urgent' ? 'danger' : 
                                    ($job['priority'] === 'high' ? 'warning' : 'info'); 
                            ?>">
                                <?php echo ucfirst($job['priority']); ?>
                            </span>
                        </p>
                    </div>
                    <?php if ($job['job_source']): ?>
                    <div class="mb-2">
                        <small class="text-muted">Source:</small>
                        <p class="mb-0"><?php echo htmlspecialchars($job['job_source']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="../applications/list.php?job_id=<?php echo $job_id; ?>&ss_id=<?php echo $token; ?>" class="btn btn-primary">
                            <i class="bx bx-list-ul"></i> All Applications
                        </a>
                        <a href="cv/inbox.php?job_id=<?php echo $job_id; ?>&ss_id=<?php echo $token; ?>" class="btn btn-info">
                            <i class="bx bx-inbox"></i> CV Inbox
                        </a>
                        <a href="../clients/?action=view&id=<?php echo $job['client_id']; ?>&ss_id=<?php echo $token; ?>" class="btn btn-secondary">
                            <i class="bx bx-building"></i> View Client
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function closeJob() {
    if (confirm('Are you sure you want to close this job? This will stop accepting new applications.')) {
        $.post('handlers/job_handle.php', {
            job_id: <?php echo $job_id; ?>,
            action: 'close',
            token: '<?php echo Auth::token(); ?>'
        }, function(response) {
            if (response.success) {
                alert('Job closed successfully');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json');
    }
}

function showApplyCandidateModal() {
    alert('Apply Candidate feature - Modal will be implemented');
    // TODO: Implement candidate selection modal
}
</script>

</body>
</html>