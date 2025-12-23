<?php
/**
 * Client View/Details Page
 * File: panel/modules/clients/view.php
 * Included by index.php
 */

// Load common bootstrap
require_once __DIR__ . '/../_common.php';
// Page configuration
$pageTitle = 'View Client';
$breadcrumbs = [
    'Client' => '#'
];
// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// Display breadcrumb
echo renderBreadcrumb($breadcrumbs);

// Get client ID from index.php
if (!isset($id) || !$id) {
    throw new Exception('Client ID is required');
}

// Fetch client details
$stmt = $conn->prepare("
    SELECT c.*, 
           u.name as created_by_name,
           (SELECT COUNT(*) FROM jobs WHERE client_id = c.client_id AND deleted_at IS NULL) as total_jobs,
           (SELECT COUNT(*) FROM jobs WHERE client_id = c.client_id AND job_status = 'active' AND deleted_at IS NULL) as active_jobs,
           (SELECT COUNT(*) FROM job_applications ja 
            JOIN jobs j ON ja.job_id = j.job_id 
            WHERE j.client_id = c.client_id AND ja.deleted_at IS NULL) as total_applications,
           (SELECT COUNT(*) FROM job_applications ja 
            JOIN jobs j ON ja.job_id = j.job_id 
            WHERE j.client_id = c.client_id AND ja.status = 'placed') as total_placements
    FROM clients c
    LEFT JOIN user u ON c.created_by = u.user_code
    WHERE c.client_id = ?
");

$stmt->bind_param('i', $id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();

if (!$client) {
    throw new Exception('Client not found');
}

// Fetch client's jobs
$jobs_query = "
    SELECT j.*, 
           u.name as created_by_name,
           (SELECT COUNT(*) FROM job_applications WHERE job_id = j.job_id AND deleted_at IS NULL) as application_count,
           (SELECT COUNT(*) FROM cv_inbox WHERE job_id = j.job_id AND status != 'rejected') as cv_count
    FROM jobs j
    LEFT JOIN user u ON j.created_by = u.user_code
    WHERE j.client_id = ? AND j.deleted_at IS NULL
    ORDER BY j.created_at DESC
";

$stmt = $conn->prepare($jobs_query);
$stmt->bind_param('i', $id);
$stmt->execute();
$jobs_result = $stmt->get_result();
$jobs = [];
while ($row = $jobs_result->fetch_assoc()) {
    $jobs[] = $row;
}

// Fetch recent placements
$placements_query = "
    SELECT ja.*, 
           c.candidate_name,
           j.job_title,
           j.job_code,
           o.offered_salary,
           o.offered_currency
    FROM job_applications ja
    JOIN candidates c ON ja.can_code = c.can_code
    JOIN jobs j ON ja.job_id = j.job_id
    LEFT JOIN offers o ON ja.application_id = o.application_id
    WHERE j.client_id = ? 
    AND ja.status = 'placed'
    AND ja.deleted_at IS NULL
    ORDER BY ja.placement_date DESC
    LIMIT 10
";

$stmt = $conn->prepare($placements_query);
$stmt->bind_param('i', $id);
$stmt->execute();
$placements_result = $stmt->get_result();
$placements = [];
while ($row = $placements_result->fetch_assoc()) {
    $placements[] = $row;
}
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bx bx-building me-2"></i> <?php echo htmlspecialchars($client['client_name']); ?>
            </h4>
            <p class="text-muted mb-0">
                Client Details & Activity
            </p>
        </div>
        <div>
            <a href="?action=edit&id=<?php echo $client['client_id']; ?>&ss_id=<?php echo $token; ?>" class="btn btn-primary me-2">
                <i class="bx bx-edit me-1"></i> Edit Client
            </a>
            <a href="?action=list&ss_id=<?php echo $token; ?>" class="btn btn-secondary">
                <i class="bx bx-arrow-back me-1"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Client Info -->
        <div class="col-lg-4">
            <!-- Client Information Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Client Information</h5>
                    <span class="badge bg-label-<?php echo $client['status'] === 'active' ? 'success' : 'secondary'; ?>">
                        <?php echo ucfirst($client['status']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Company Name</label>
                        <p class="mb-0 fw-medium"><?php echo htmlspecialchars($client['company_name'] ?? $client['client_name']); ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small">Contact Person</label>
                        <p class="mb-0"><?php echo htmlspecialchars($client['contact_person'] ?? 'Same as client name'); ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small">Email</label>
                        <p class="mb-0">
                            <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>">
                                <i class="bx bx-envelope me-1"></i>
                                <?php echo htmlspecialchars($client['email']); ?>
                            </a>
                        </p>
                    </div>

                    <?php if ($client['phone']): ?>
                    <div class="mb-3">
                        <label class="text-muted small">Phone</label>
                        <p class="mb-0">
                            <a href="tel:<?php echo htmlspecialchars($client['phone']); ?>">
                                <i class="bx bx-phone me-1"></i>
                                <?php echo htmlspecialchars($client['phone']); ?>
                            </a>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php if ($client['address'] || $client['city'] || $client['country']): ?>
                    <div class="mb-3">
                        <label class="text-muted small">Location</label>
                        <p class="mb-0">
                            <i class="bx bx-map me-1"></i>
                            <?php 
                            $location = array_filter([
                                $client['address'],
                                $client['city'],
                                $client['country']
                            ]);
                            echo htmlspecialchars(implode(', ', $location));
                            ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php if ($client['notes']): ?>
                    <div class="mb-3">
                        <label class="text-muted small">Internal Notes</label>
                        <div class="border rounded p-2 bg-light">
                            <small><?php echo nl2br(htmlspecialchars($client['notes'])); ?></small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mt-4 pt-3 border-top">
                        <small class="text-muted">
                            <strong>Created by:</strong> <?php echo htmlspecialchars($client['created_by_name'] ?? 'System'); ?><br>
                            <strong>Date:</strong> <?php echo date('M d, Y', strtotime($client['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="../jobs/?action=create&client_id=<?php echo $client['client_id']; ?>&ss_id=<?php echo $token; ?>" class="btn btn-success">
                            <i class="bx bx-plus-circle me-1"></i> Create New Job
                        </a>
                        <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>" class="btn btn-label-primary">
                            <i class="bx bx-envelope me-1"></i> Send Email
                        </a>
                        <?php if ($client['phone']): ?>
                        <a href="tel:<?php echo htmlspecialchars($client['phone']); ?>" class="btn btn-label-info">
                            <i class="bx bx-phone me-1"></i> Call Client
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Statistics & Jobs -->
        <div class="col-lg-8">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="avatar avatar-md bg-label-primary mx-auto mb-2">
                                <i class="bx bx-briefcase bx-sm"></i>
                            </div>
                            <h4 class="mb-0"><?php echo $client['total_jobs']; ?></h4>
                            <small class="text-muted">Total Jobs</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="avatar avatar-md bg-label-success mx-auto mb-2">
                                <i class="bx bx-check-circle bx-sm"></i>
                            </div>
                            <h4 class="mb-0"><?php echo $client['active_jobs']; ?></h4>
                            <small class="text-muted">Active Jobs</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="avatar avatar-md bg-label-info mx-auto mb-2">
                                <i class="bx bx-user-check bx-sm"></i>
                            </div>
                            <h4 class="mb-0"><?php echo $client['total_applications']; ?></h4>
                            <small class="text-muted">Applications</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="avatar avatar-md bg-label-warning mx-auto mb-2">
                                <i class="bx bx-trophy bx-sm"></i>
                            </div>
                            <h4 class="mb-0"><?php echo $client['total_placements']; ?></h4>
                            <small class="text-muted">Placements</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#jobs-tab">
                        <i class="bx bx-briefcase me-1"></i> Jobs (<?php echo count($jobs); ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#placements-tab">
                        <i class="bx bx-trophy me-1"></i> Placements (<?php echo count($placements); ?>)
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Jobs Tab -->
                <div class="tab-pane fade show active" id="jobs-tab">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Jobs for This Client</h5>
                            <a href="../jobs/?action=create&client_id=<?php echo $client['client_id']; ?>&ss_id=<?php echo $token; ?>" class="btn btn-sm btn-primary">
                                <i class="bx bx-plus me-1"></i> Create Job
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($jobs)): ?>
                                <div class="text-center py-4">
                                    <i class="bx bx-briefcase bx-lg text-muted mb-3" style="font-size: 3rem;"></i>
                                    <h5>No Jobs Yet</h5>
                                    <p class="text-muted">Create your first job for this client</p>
                                    <a href="../jobs/?action=create&client_id=<?php echo $client['client_id']; ?>&ss_id=<?php echo $token; ?>" class="btn btn-primary">
                                        <i class="bx bx-plus me-1"></i> Create First Job
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Job Title</th>
                                                <th>Code</th>
                                                <th>Status</th>
                                                <th>Applications</th>
                                                <th>CVs</th>
                                                <th>Posted</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($jobs as $job): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($job['job_title']); ?></strong>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?php echo htmlspecialchars($job['job_code']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-label-<?php 
                                                        echo $job['job_status'] === 'active' ? 'success' : 
                                                            ($job['job_status'] === 'pending' ? 'warning' : 'secondary'); 
                                                    ?>">
                                                        <?php echo ucfirst($job['job_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($job['application_count'] > 0): ?>
                                                        <a href="../applications/list.php?job_id=<?php echo $job['job_id']; ?>&ss_id=<?php echo $token; ?>" class="badge bg-label-info">
                                                            <?php echo $job['application_count']; ?> Apps
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($job['cv_count'] > 0): ?>
                                                        <a href="../jobs/cv/inbox.php?job_id=<?php echo $job['job_id']; ?>&ss_id=<?php echo $token; ?>" class="badge bg-label-primary">
                                                            <?php echo $job['cv_count']; ?> CVs
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d, Y', strtotime($job['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <a href="../jobs/?action=view&id=<?php echo $job['job_id']; ?>&ss_id=<?php echo $token; ?>" class="btn btn-sm btn-icon btn-label-primary">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Placements Tab -->
                <div class="tab-pane fade" id="placements-tab">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Successful Placements</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($placements)): ?>
                                <div class="text-center py-4">
                                    <i class="bx bx-trophy bx-lg text-muted mb-3" style="font-size: 3rem;"></i>
                                    <h5>No Placements Yet</h5>
                                    <p class="text-muted">Successful placements will appear here</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Candidate</th>
                                                <th>Job</th>
                                                <th>Salary</th>
                                                <th>Placed Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($placements as $placement): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm bg-success me-2">
                                                            <i class="bx bx-check text-white"></i>
                                                        </div>
                                                        <strong><?php echo htmlspecialchars($placement['candidate_name']); ?></strong>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($placement['job_title']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($placement['job_code']); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($placement['offered_salary']): ?>
                                                        <strong><?php echo $placement['offered_currency']; ?> <?php echo number_format($placement['offered_salary']); ?></strong>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($placement['placement_date'])); ?></td>
                                                <td>
                                                    <a href="../applications/view.php?id=<?php echo $placement['application_id']; ?>&ss_id=<?php echo $token; ?>" class="btn btn-sm btn-icon btn-label-info">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize Bootstrap tabs
$(document).ready(function() {
    // Tab persistence
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        localStorage.setItem('clientViewActiveTab', $(e.target).data('bs-target'));
    });

    // Restore active tab
    var activeTab = localStorage.getItem('clientViewActiveTab');
    if (activeTab) {
        $('button[data-bs-target="' + activeTab + '"]').tab('show');
    }
});
</script>