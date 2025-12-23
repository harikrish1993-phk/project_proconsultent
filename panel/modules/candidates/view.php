<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';
// Page configuration
$pageTitle = 'Candidates';
$breadcrumbs = [
    'Candidates' => '#'
];
// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// Display breadcrumb
echo renderBreadcrumb($breadcrumbs);

$id = $_GET['id'] ?? null;
if (!$id) {
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Invalid ID.</div></div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT c.*, GROUP_CONCAT(ca.user_code) as assigned_users
        FROM candidates c
        LEFT JOIN candidate_assignments ca ON c.can_code = ca.can_code
        WHERE c.can_code = ?
    ");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $candidate = $stmt->get_result()->fetch_assoc();
    if (!$candidate) throw new Exception('Candidate not found');
    
    // Timeline
    $timeline = [];
    $stmt = $conn->prepare("SELECT * FROM candidates_edit_info WHERE can_code = ? ORDER BY edited_at DESC");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $timeline[] = $row;
    
    // Assigned jobs
    $assigned_jobs = [];
    $stmt = $conn->prepare("SELECT j.* FROM candidate_job_applications a LEFT JOIN jobs j ON a.job_id = j.job_id WHERE a.can_code = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $assigned_jobs[] = $row;
} catch (Exception $e) {
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Error: ' . $e->getMessage() . '</div></div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Candidate: <?php echo htmlspecialchars($candidate['candidate_name']); ?></h4>
    
    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#profile">Profile</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#documents">Documents</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#timeline">Timeline</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#comments">Comments</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#calls">Calls</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#interviews">Interviews</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#jobs">Assigned Jobs</a>
        </li>
    </ul>
    
    <div class="tab-content">
        <div class="tab-pane fade show active" id="profile">
            <?php include 'partials/candidate_profile_table.php'; ?>
        </div>
        <div class="tab-pane fade" id="documents">
            <?php include 'partials/candidate_documents.php'; ?>
        </div>
        <div class="tab-pane fade" id="timeline">
            <?php include 'components/activity_timeline.php'; ?>
        </div>
        <div class="tab-pane fade" id="comments">
            <?php include 'components/hr-comments.php'; ?>
        </div>
        <div class="tab-pane fade" id="calls">
            <?php include 'components/call-logs.php'; ?>
        </div>
        <div class="tab-pane fade" id="interviews">
            <?php include 'interviews.php'; ?>
        </div>
        <div class="tab-pane fade" id="jobs">
            <div class="card">
                <div class="card-header">Assigned Jobs</div>
                <div class="card-body">
                    <form method="POST" action="handlers/assign_to_job_handler.php">
                        <input type="hidden" name="can_code" value="<?php echo $id; ?>">
                        <select name="job_id" class="form-select mb-3">
                            <!-- Fetch open jobs from jobs table -->
                            <?php
                            $res = $conn->query("SELECT job_id, title FROM jobs WHERE status = 'approved'");
                            while ($row = $res->fetch_assoc()) {
                                echo '<option value="' . $row['job_id'] . '">' . htmlspecialchars($row['title']) . '</option>';
                            }
                            ?>
                        </select>
                        <button type="submit" class="btn btn-primary">Assign to Job</button>
                    </form>
                    <table class="table mt-3">
                        <thead><tr><th>Job Title</th><th>Status</th><th>Applied At</th></tr></thead>
                        <tbody>
                            <?php foreach ($assigned_jobs as $job): ?>
                            <tr>
                                <td><a href="../../jobs/view.php?id=<?php echo $job['job_id']; ?>"><?php echo htmlspecialchars($job['title']); ?></a></td>
                                <td><?php echo $job['status']; ?></td>
                                <td><?php echo $job['applied_at']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>