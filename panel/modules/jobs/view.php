<?php
// modules/jobs/view.php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';
// Page configuration
$pageTitle = 'View Jobs';
$breadcrumbs = [
    'Jobs' => '#'
];
// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// Display breadcrumb
echo renderBreadcrumb($breadcrumbs);

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo '<div class="alert alert-danger">Invalid job ID.</div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT j.*, u.name AS created_by_name
        FROM jobs j
        LEFT JOIN user u ON j.created_by = u.user_code
        WHERE j.job_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc();
    if (!$job) throw new Exception('Job not found');
    
    // Fetch submissions
    $stmt = $conn->prepare("SELECT * FROM submittedcv WHERE job_id = ? ORDER BY submitted_at DESC");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $submissions = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $submissions[] = $row;
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Jobs /</span> View: <?php echo htmlspecialchars($job['title']); ?>
    </h4>
    
    <div class="row">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Job Details</h5>
                    <div>
                        <a href="index.php?action=edit&id=<?php echo $job['job_id']; ?>" class="btn btn-sm btn-label-primary">
                            <i class="bx bx-edit me-1"></i> Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Job Code</label>
                            <p><?php echo htmlspecialchars($job['job_code']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Status</label>
                            <p>
                                <span class="badge bg-label-<?php 
                                    switch ($job['status']) {
                                        case 'approved': echo 'success'; break;
                                        case 'rejected': echo 'danger'; break;
                                        default: echo 'warning';
                                    }
                                ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Description</label>
                        <div class="border rounded p-3 bg-light">
                            <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Requirements</label>
                        <div class="border rounded p-3 bg-light">
                            <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Salary Range</label>
                            <p><?php echo number_format($job['salary_min'], 2) . ' - ' . number_format($job['salary_max'], 2); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Created By</label>
                            <p><?php echo htmlspecialchars($job['created_by_name']); ?> on <?php echo date('Y-m-d H:i', strtotime($job['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4">
            <?php include 'job_assignment_ui.php'; // Fixed version ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Applications / Submissions</h5>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Submitted At</th>
                        <th>CV</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No submissions yet.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($submissions as $sub): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sub['candidate_name']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($sub['submitted_at'])); ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($sub['cv_path']); ?>" class="btn btn-sm btn-label-info" target="_blank">
                                <i class="bx bx-file me-1"></i> View
                            </a>
                        </td>
                        <td>
                            <!-- Link to candidate if converted -->
                            <?php if ($sub['converted_can_code']): ?>
                            <a href="../candidates/view.php?id=<?php echo $sub['converted_can_code']; ?>" class="btn btn-sm btn-label-success">
                                View Candidate
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>