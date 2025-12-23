<?php
/**
 * CV Inbox - Main List Page (FIXED VERSION)
 * File: panel/modules/jobs/cv/inbox.php
 */

// Load common bootstrap
require_once __DIR__ . '/../../_common.php';
// Page configuration
$pageTitle = 'CV Management';
$breadcrumbs = [
    'CVinbox' => '#'
];
// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// Display breadcrumb
echo renderBreadcrumb($breadcrumbs);

$db = Database::getInstance();
$conn = $db->getConnection();
$user = Auth::user();
$token = $_GET['ss_id'] ?? '';

// Get filter parameters
$filter_job = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$filter_status = $_GET['status'] ?? '';
$filter_assigned = $_GET['assigned'] ?? '';

// Build query
$query = "
    SELECT cv.*, 
           j.job_title,
           j.job_code,
           c.client_name,
           u.name AS assigned_to_name,
           conv.name as converted_by_name
    FROM cv_inbox cv
    LEFT JOIN jobs j ON cv.job_id = j.job_id
    LEFT JOIN clients c ON j.client_id = c.client_id
    LEFT JOIN user u ON cv.assigned_to = u.user_code
    LEFT JOIN user conv ON cv.converted_by = conv.user_code
    WHERE 1=1
";

$params = [];
$types = '';

if ($filter_job) {
    $query .= " AND cv.job_id = ?";
    $params[] = $filter_job;
    $types .= 'i';
}

if ($filter_status) {
    $query .= " AND cv.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($filter_assigned) {
    $query .= " AND cv.assigned_to = ?";
    $params[] = $filter_assigned;
    $types .= 's';
}

$query .= " ORDER BY cv.submitted_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$cvs_result = $stmt->get_result();
$cvs = [];
while ($row = $cvs_result->fetch_assoc()) {
    $cvs[] = $row;
}

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_cvs,
        SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
        SUM(CASE WHEN status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
        SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM cv_inbox
    WHERE 1=1
";

if ($filter_job) {
    $stats_query .= " AND job_id = " . $filter_job;
}

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get jobs for filter
$jobs_query = "SELECT job_id, job_title, job_code FROM jobs WHERE job_status = 'active' AND deleted_at IS NULL ORDER BY job_title";
$jobs_result = mysqli_query($conn, $jobs_query);

// Get users for filter
$users_query = "SELECT user_code, name FROM user WHERE status = 'active' ORDER BY name";
$users_result = mysqli_query($conn, $users_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>CV Inbox</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bx bx-inbox me-2"></i> CV Inbox
            </h4>
            <p class="text-muted mb-0">Review and process CV submissions</p>
        </div>
        <div>
            <a href="../index.php?action=list&ss_id=<?php echo $token; ?>" class="btn btn-secondary">
                <i class="bx bx-arrow-back me-1"></i> Back to Jobs
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0"><?php echo $stats['total']; ?></h4>
                    <small class="text-muted">Total CVs</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-primary"><?php echo $stats['new_cvs']; ?></h4>
                    <small class="text-muted">New</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-info"><?php echo $stats['reviewed']; ?></h4>
                    <small class="text-muted">Reviewed</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-success"><?php echo $stats['shortlisted']; ?></h4>
                    <small class="text-muted">Shortlisted</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-secondary"><?php echo $stats['converted']; ?></h4>
                    <small class="text-muted">Converted</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-danger"><?php echo $stats['rejected']; ?></h4>
                    <small class="text-muted">Rejected</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="ss_id" value="<?php echo $token; ?>">
                
                <div class="col-md-3">
                    <label class="form-label">Job</label>
                    <select name="job_id" class="form-select">
                        <option value="">All Jobs</option>
                        <?php while ($job = mysqli_fetch_assoc($jobs_result)): ?>
                            <option value="<?php echo $job['job_id']; ?>" <?php echo ($filter_job == $job['job_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($job['job_title']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="new" <?php echo ($filter_status === 'new') ? 'selected' : ''; ?>>New</option>
                        <option value="reviewed" <?php echo ($filter_status === 'reviewed') ? 'selected' : ''; ?>>Reviewed</option>
                        <option value="shortlisted" <?php echo ($filter_status === 'shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                        <option value="rejected" <?php echo ($filter_status === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        <option value="converted" <?php echo ($filter_status === 'converted') ? 'selected' : ''; ?>>Converted</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Assigned To</label>
                    <select name="assigned" class="form-select">
                        <option value="">All Recruiters</option>
                        <?php while ($rec = mysqli_fetch_assoc($users_result)): ?>
                            <option value="<?php echo $rec['user_code']; ?>" <?php echo ($filter_assigned === $rec['user_code']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rec['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bx bx-filter"></i> Filter
                    </button>
                    <a href="inbox.php?ss_id=<?php echo $token; ?>" class="btn btn-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- CV List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">CV Submissions</h5>
        </div>
        <div class="card-body">
            <?php if (empty($cvs)): ?>
                <div class="text-center py-5">
                    <i class="bx bx-inbox bx-lg text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5>No CVs in Inbox</h5>
                    <p class="text-muted">CV submissions will appear here</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="cvTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>Candidate</th>
                                <th>Job</th>
                                <th>Client</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Submitted</th>
                                <th>CV</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cvs as $cv): ?>
                            <tr>
                                <td><input type="checkbox" class="cv-checkbox" value="<?php echo $cv['id']; ?>"></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($cv['candidate_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($cv['email']); ?></small>
                                    <?php if ($cv['phone']): ?>
                                        <br><small class="text-muted"><i class="bx bx-phone"></i> <?php echo htmlspecialchars($cv['phone']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($cv['job_title']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($cv['job_code']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($cv['client_name'] ?? '-'); ?></td>
                                <td>
                                    <?php
                                    $status_colors = [
                                        'new' => 'primary',
                                        'reviewed' => 'info',
                                        'shortlisted' => 'success',
                                        'rejected' => 'danger',
                                        'converted' => 'secondary'
                                    ];
                                    $color = $status_colors[$cv['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-label-<?php echo $color; ?>">
                                        <?php echo ucfirst($cv['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($cv['assigned_to_name'] ?? 'Unassigned'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($cv['submitted_at'])); ?></td>
                                <td>
                                    <?php if ($cv['cv_path']): ?>
                                        <a href="<?php echo htmlspecialchars($cv['cv_path']); ?>" target="_blank" class="btn btn-sm btn-label-info">
                                            <i class="bx bx-file"></i> View
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="view.php?id=<?php echo $cv['id']; ?>&ss_id=<?php echo $token; ?>">
                                                    <i class="bx bx-show me-2"></i> View Details
                                                </a>
                                            </li>
                                            <?php if ($cv['status'] !== 'converted'): ?>
                                            <li>
                                                <a class="dropdown-item text-success" href="convert.php?id=<?php echo $cv['id']; ?>&ss_id=<?php echo $token; ?>">
                                                    <i class="bx bx-transfer me-2"></i> Convert to Candidate
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="changeStatus(<?php echo $cv['id']; ?>, 'shortlisted')">
                                                    <i class="bx bx-check me-2"></i> Shortlist
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" onclick="rejectCV(<?php echo $cv['id']; ?>)">
                                                    <i class="bx bx-x me-2"></i> Reject
                                                </a>
                                            </li>
                                            <?php else: ?>
                                            <li>
                                                <a class="dropdown-item" href="../../../can_full_view?can_code=<?php echo $cv['converted_to_candidate']; ?>&ss_id=<?php echo $token; ?>">
                                                    <i class="bx bx-user me-2"></i> View Candidate
                                                </a>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#cvTable').DataTables({
        pageLength: 25,
        order: [[6, 'desc']], // Sort by submitted date
        columnDefs: [
            { orderable: false, targets: [0, 7, 8] }
        ]
    });

    // Select all
    $('#selectAll').on('click', function() {
        $('.cv-checkbox').prop('checked', this.checked);
    });
});

function changeStatus(cvId, status) {
    if (confirm('Change CV status to ' + status + '?')) {
        $.post('handlers/cv_handler.php', {
            action: 'change_status',
            cv_id: cvId,
            status: status,
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

function rejectCV(cvId) {
    const reason = prompt('Rejection reason:');
    if (reason) {
        $.post('handlers/cv_handler.php', {
            action: 'reject',
            cv_id: cvId,
            reason: reason,
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
</script>

</body>
</html>