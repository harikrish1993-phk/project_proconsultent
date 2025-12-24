<?php
/**
 * Candidate Profile View
 * Location: panel/modules/candidates/view.php
 */

// ============================================================================
// BOOTSTRAP
// ============================================================================
require_once __DIR__ . '/../_common.php';

$pageTitle = 'Candidate Profile';

// ============================================================================
// GET CANDIDATE ID
// ============================================================================
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: list.php');
    exit();
}

// ============================================================================
// FETCH CANDIDATE DATA
// ============================================================================
$candidate = null;
$timeline = [];
$documents = [];
$comments = [];
$calls = [];
$interviews = [];
$assigned_jobs = [];

try {
    $conn = getDB();
    
    // Get candidate
    $stmt = $conn->prepare("
        SELECT c.*, 
               GROUP_CONCAT(DISTINCT ca.user_code) as assigned_users
        FROM candidates c
        LEFT JOIN candidate_assignments ca ON c.can_code = ca.can_code
        WHERE c.can_code = ?
        GROUP BY c.can_code
    ");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $candidate = $stmt->get_result()->fetch_assoc();
    
    if (!$candidate) {
        header('Location: list.php?error=Candidate not found');
        exit();
    }
    
    // Get timeline/activity
    $stmt = $conn->prepare("
        SELECT * FROM candidate_activity_log 
        WHERE can_code = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $timeline[] = $row;
    }
    
    // Get documents
    $stmt = $conn->prepare("
        SELECT * FROM candidate_documents 
        WHERE can_code = ? 
        ORDER BY uploaded_at DESC
    ");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }
    
    // Get assigned jobs
    $stmt = $conn->prepare("
        SELECT j.*, cja.status as application_status, cja.applied_at
        FROM candidate_job_applications cja
        LEFT JOIN jobs j ON cja.job_id = j.job_id
        WHERE cja.can_code = ?
        ORDER BY cja.applied_at DESC
    ");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assigned_jobs[] = $row;
    }
    
} catch (Exception $e) {
    error_log('View candidate error: ' . $e->getMessage());
    header('Location: list.php?error=' . urlencode($e->getMessage()));
    exit();
}

// ============================================================================
// OUTPUT BUFFER START
// ============================================================================
ob_start();
?>

<!-- ======================================================================= -->
<!-- CANDIDATE PROFILE PAGE -->
<!-- ======================================================================= -->

<div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- ============================================================ -->
    <!-- PROFILE HEADER WITH ACTION BUTTONS -->
    <!-- ============================================================ -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row align-items-center">
                
                <!-- Left: Candidate Info -->
                <div class="col-md-6">
                    <div class="d-flex align-items-start">
                        <!-- Avatar -->
                        <div class="avatar avatar-xl me-3">
                            <span class="avatar-initial rounded-circle bg-label-primary fs-2">
                                <?php echo strtoupper(substr($candidate['first_name'] ?? 'C', 0, 1)); ?>
                            </span>
                        </div>
                        
                        <!-- Info -->
                        <div>
                            <h3 class="mb-1">
                                <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                            </h3>
                            <p class="text-muted mb-2">
                                <?php echo htmlspecialchars($candidate['job_title'] ?? 'No title'); ?>
                                <?php if ($candidate['experience_years']): ?>
                                • <?php echo htmlspecialchars($candidate['experience_years']); ?> years experience
                                <?php endif; ?>
                            </p>
                            
                            <!-- Status Badge -->
                            <?php
                            $statusColors = [
                                'New' => 'primary',
                                'Screening' => 'info',
                                'Interview' => 'warning',
                                'Offered' => 'success',
                                'Hired' => 'success',
                                'Rejected' => 'danger',
                                'On Hold' => 'secondary'
                            ];
                            $statusColor = $statusColors[$candidate['status'] ?? 'New'] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $statusColor; ?>" style="font-size: 0.9rem; padding: 6px 12px;">
                                <i class="bx bx-info-circle"></i> 
                                <?php echo htmlspecialchars($candidate['status'] ?? 'New'); ?>
                            </span>
                            
                            <!-- Skills -->
                            <?php if (!empty($candidate['skills'])): ?>
                            <div class="mt-2">
                                <?php 
                                $skills = array_slice(explode(',', $candidate['skills']), 0, 5);
                                foreach ($skills as $skill): 
                                ?>
                                <span class="badge bg-label-primary me-1"><?php echo trim($skill); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Action Buttons -->
                <div class="col-md-6 text-end">
                    <div class="btn-group" role="group">
                        
                        <!-- Edit Profile Button -->
                        <a href="edit.php?id=<?php echo $candidate['can_code']; ?>" 
                           class="btn btn-primary btn-lg">
                            <i class="bx bx-edit"></i> Edit Profile
                        </a>
                        
                        <!-- Full View (Print) Button -->
                        <a href="full_view.php?id=<?php echo $candidate['can_code']; ?>" 
                           class="btn btn-outline-secondary btn-lg"
                           target="_blank">
                            <i class="bx bx-file"></i> Full View
                        </a>
                        
                        <!-- More Actions Dropdown -->
                        <div class="btn-group" role="group">
                            <button type="button" 
                                    class="btn btn-outline-secondary btn-lg dropdown-toggle" 
                                    data-bs-toggle="dropdown">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#assignJobModal">
                                        <i class="bx bx-briefcase text-info"></i> Assign to Job
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#scheduleInterviewModal">
                                        <i class="bx bx-calendar text-warning"></i> Schedule Interview
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changeStatusModal">
                                        <i class="bx bx-refresh text-primary"></i> Change Status
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="downloadResume()">
                                        <i class="bx bx-download text-success"></i> Download Resume
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="exportProfile()">
                                        <i class="bx bx-export text-info"></i> Export Profile
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" 
                                       href="#" 
                                       onclick="archiveCandidate('<?php echo $candidate['can_code']; ?>')">
                                        <i class="bx bx-archive"></i> Archive Candidate
                                    </a>
                                </li>
                            </ul>
                        </div>
                        
                    </div>
                    
                    <!-- Back Button -->
                    <div class="mt-2">
                        <a href="list.php" class="btn btn-sm btn-link">
                            <i class="bx bx-arrow-back"></i> Back to List
                        </a>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- ============================================================ -->
    <!-- QUICK CONTACT INFO -->
    <!-- ============================================================ -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Contact Information</h6>
                    <div class="mb-2">
                        <i class="bx bx-envelope text-primary"></i>
                        <strong class="ms-2">Email:</strong>
                        <div class="ms-4">
                            <a href="mailto:<?php echo htmlspecialchars($candidate['email'] ?? ''); ?>">
                                <?php echo htmlspecialchars($candidate['email'] ?? 'Not provided'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="mb-2">
                        <i class="bx bx-phone text-success"></i>
                        <strong class="ms-2">Phone:</strong>
                        <div class="ms-4">
                            <a href="tel:<?php echo htmlspecialchars($candidate['phone'] ?? ''); ?>">
                                <?php echo htmlspecialchars($candidate['phone'] ?? 'Not provided'); ?>
                            </a>
                        </div>
                    </div>
                    <div>
                        <i class="bx bx-map text-info"></i>
                        <strong class="ms-2">Location:</strong>
                        <div class="ms-4">
                            <?php echo htmlspecialchars($candidate['current_location'] ?? 'Not provided'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Professional Details</h6>
                    <div class="mb-2">
                        <strong>Total Experience:</strong>
                        <div class="ms-2"><?php echo htmlspecialchars($candidate['experience_years'] ?? 'Not specified'); ?> years</div>
                    </div>
                    <div class="mb-2">
                        <strong>Notice Period:</strong>
                        <div class="ms-2"><?php echo htmlspecialchars($candidate['notice_period'] ?? 'Not specified'); ?></div>
                    </div>
                    <div>
                        <strong>Expected Salary:</strong>
                        <div class="ms-2"><?php echo htmlspecialchars($candidate['expected_salary'] ?? 'Not specified'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-3">System Info</h6>
                    <div class="mb-2">
                        <strong>Added on:</strong>
                        <div class="ms-2"><?php echo formatDate($candidate['created_at']); ?></div>
                    </div>
                    <div class="mb-2">
                        <strong>Last updated:</strong>
                        <div class="ms-2"><?php echo timeAgo($candidate['updated_at']); ?></div>
                    </div>
                    <div>
                        <strong>Assigned to:</strong>
                        <div class="ms-2">
                            <?php echo htmlspecialchars($candidate['assigned_users'] ?? 'Unassigned'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ============================================================ -->
    <!-- TABS - MAIN CONTENT AREA -->
    <!-- ============================================================ -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview" type="button">
                        <i class="bx bx-user"></i> Overview
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#resume" type="button">
                        <i class="bx bx-file"></i> Resume
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#timeline" type="button">
                        <i class="bx bx-time"></i> Timeline
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#comments" type="button">
                        <i class="bx bx-message-square"></i> Comments
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#documents" type="button">
                        <i class="bx bx-folder"></i> Documents
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#jobs" type="button">
                        <i class="bx bx-briefcase"></i> Jobs (<?php echo count($assigned_jobs); ?>)
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="card-body">
            <div class="tab-content">
                
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview">
                    <?php include 'partials/candidate_profile_table.php'; ?>
                </div>
                
                <!-- Resume Tab -->
                <div class="tab-pane fade" id="resume">
                    <h5>Resume/CV</h5>
                    <?php if (!empty($candidate['resume_path'])): ?>
                        <iframe src="<?php echo htmlspecialchars($candidate['resume_path']); ?>" 
                                width="100%" 
                                height="600px">
                        </iframe>
                    <?php else: ?>
                        <p class="text-muted">No resume uploaded</p>
                    <?php endif; ?>
                </div>
                
                <!-- Timeline Tab -->
                <div class="tab-pane fade" id="timeline">
                    <?php include 'components/activity_timeline.php'; ?>
                </div>
                
                <!-- Comments Tab -->
                <div class="tab-pane fade" id="comments">
                    <?php include 'components/hr-comments.php'; ?>
                </div>
                
                <!-- Documents Tab -->
                <div class="tab-pane fade" id="documents">
                    <?php include 'partials/candidate_documents.php'; ?>
                </div>
                
                <!-- Jobs Tab -->
                <div class="tab-pane fade" id="jobs">
                    <h5 class="mb-3">Assigned Jobs</h5>
                    
                    <?php if (empty($assigned_jobs)): ?>
                        <p class="text-muted">No jobs assigned yet</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignJobModal">
                            <i class="bx bx-plus"></i> Assign to Job
                        </button>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Job Title</th>
                                        <th>Company</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assigned_jobs as $job): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($job['title']); ?></td>
                                        <td><?php echo htmlspecialchars($job['company_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatDate($job['applied_at']); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($job['application_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="../jobs/view.php?id=<?php echo $job['job_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                View Job
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

<!-- MODALS GO HERE -->
<!-- Assign Job Modal, Schedule Interview Modal, etc. -->

<script>
function archiveCandidate(canCode) {
    if (!confirm('Are you sure you want to archive this candidate? They will be hidden from active searches.')) {
        return;
    }
    
    // Handle archive
    fetch('handlers/candidate_data_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'archive',
            can_code: canCode,
            token: '<?php echo Auth::token(); ?>'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Candidate archived successfully');
            window.location.href = 'list.php';
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>

<?php
// ============================================================================
// LOAD LAYOUT
// ============================================================================
$pageContent = ob_get_clean();
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/includes/sidebar.php';
echo $pageContent;
require_once ROOT_PATH . '/panel/includes/footer.php';
?>