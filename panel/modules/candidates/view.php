<?php
// ============================================================================
// BOOTSTRAP & AUTHORIZATION
// ============================================================================
require_once __DIR__ . '/../_common.php';

// Check permissions
if (!$user) {
    header('HTTP/1.0 403 Forbidden');
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Access denied.</div></div>';
    exit();
}

// Get candidate ID
$id = $_GET['id'] ?? $_GET['can_code'] ?? null;
if (!$id) {
    header('Location: candidates.php?error=Missing candidate ID');
    exit();
}

try {
    $conn = Database::getInstance()->getConnection();
    
    // Fetch candidate data with related info
    $stmt = $conn->prepare("
        SELECT c.*, wa.status as work_auth_status_name,
               u.full_name as assigned_to_name,
               j.job_title as last_job_title,
               cj.status as last_job_status
        FROM candidates c
        LEFT JOIN work_authorization wa ON wa.id = c.work_auth_status
        LEFT JOIN users u ON u.user_code = c.assigned_to
        LEFT JOIN candidate_jobs cj ON cj.can_code = c.can_code AND cj.applied_date = (
            SELECT MAX(applied_date) FROM candidate_jobs WHERE can_code = c.can_code
        )
        LEFT JOIN jobs j ON j.job_code = cj.job_code
        WHERE c.can_code = ?
    ");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $candidate = $stmt->get_result()->fetch_assoc();
    
    if (!$candidate) {
        throw new Exception('Candidate not found');
    }
    
    // Check access permissions - recruiters can only view their assigned candidates
    if ($user['level'] === 'user' && $candidate['assigned_to'] !== $user['user_code']) {
        throw new Exception('You do not have permission to view this candidate');
    }
    
    // Fetch candidate documents
    $documents = [];
    $stmt = $conn->prepare("
        SELECT * FROM candidate_documents 
        WHERE candidate_code = ? 
        ORDER BY uploaded_at DESC
    ");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }
    
    // Fetch related jobs
    $related_jobs = [];
    $stmt = $conn->prepare("
        SELECT cj.id, j.job_title, j.job_reference, cj.applied_date, cj.status, 
               c.client_name, j.location as job_location
        FROM candidate_jobs cj
        JOIN jobs j ON j.job_code = cj.job_code
        JOIN clients c ON c.client_code = j.client_code
        WHERE cj.can_code = ?
        ORDER BY cj.applied_date DESC
    ");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $related_jobs[] = $row;
    }
    
    // Fetch interviews
    $interviews = [];
    $stmt = $conn->prepare("
        SELECT i.*, 
               GROUP_CONCAT(u.full_name SEPARATOR ', ') as interviewer_names
        FROM interviews i
        LEFT JOIN interview_interviewers ii ON ii.interview_id = i.id
        LEFT JOIN users u ON u.user_code = ii.user_code
        WHERE i.can_code = ?
        GROUP BY i.id
        ORDER BY i.interview_datetime DESC
        LIMIT 5
    ");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $interviews[] = $row;
    }
    
    // Fetch activity timeline
    $timeline = [];
    $stmt = $conn->prepare("
        (SELECT 'profile_update' as type, cei.edited_at as timestamp, 
                CONCAT('Updated ', cei.edited_field) as description, 
                u.full_name as actor, cei.edited_by as actor_code
         FROM candidates_edit_info cei
         LEFT JOIN users u ON u.user_code = cei.edited_by
         WHERE cei.can_code = ?)
        UNION
        (SELECT 'job_application' as type, cj.applied_date as timestamp,
                CONCAT('Applied for ', j.job_title) as description,
                u.full_name as actor, cj.added_by as actor_code
         FROM candidate_jobs cj
         JOIN jobs j ON j.job_code = cj.job_code
         LEFT JOIN users u ON u.user_code = cj.added_by
         WHERE cj.can_code = ?)
        UNION
        (SELECT 'interview' as type, i.interview_datetime as timestamp,
                CONCAT('Interview: ', i.outcome, ' - ', i.interview_type) as description,
                u.full_name as actor, i.logged_by as actor_code
         FROM interviews i
         LEFT JOIN users u ON u.user_code = i.logged_by
         WHERE i.can_code = ?)
        UNION
        (SELECT 'document_upload' as type, cd.uploaded_at as timestamp,
                CONCAT('Document uploaded: ', cd.file_path) as description,
                u.full_name as actor, cd.uploaded_by as actor_code
         FROM candidate_documents cd
         LEFT JOIN users u ON u.user_code = cd.uploaded_by
         WHERE cd.candidate_code = ?)
        ORDER BY timestamp DESC
        LIMIT 20
    ");
    $stmt->bind_param("ssss", $id, $id, $id, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $timeline[] = $row;
    }
    
    // Fetch available jobs for assignment
    $available_jobs = [];
    if ($user['role'] === 'admin' || $user['role'] === 'user') {
        $stmt = $conn->prepare("
            SELECT j.job_code, j.job_title, j.job_reference, c.client_name, j.location
            FROM jobs j
            JOIN clients c ON c.client_code = j.client_code
            WHERE j.status NOT IN ('Filled', 'Closed')
            AND j.job_code NOT IN (
                SELECT job_code FROM candidate_jobs WHERE can_code = ?
            )
            ORDER BY j.created_at DESC
            LIMIT 10
        ");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $available_jobs[] = $row;
        }
    }
    
    // Fetch recruiters for assignment (for admins)
    $recruiters = [];
    if ($user['level'] === 'admin') {
        $stmt = $conn->prepare("
            SELECT user_code, full_name 
            FROM users 
            WHERE role = 'user' AND is_active = 1
            ORDER BY full_name
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recruiters[] = $row;
        }
    }
    
    // Page configuration with breadcrumb
    $pageTitle = htmlspecialchars($candidate['candidate_name'] ?? 'Candidate Profile');
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => 'index.php'],
        ['label' => 'Candidates', 'url' => 'candidates.php'],
        ['label' => 'View Candidate', 'active' => true]
    ];

} catch (Exception $e) {
    echo renderBreadcrumb($breadcrumbs ?? []);
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Error: ' . $e->getMessage() . '</div></div>';
    exit();
}
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <?= renderBreadcrumb($breadcrumbs) ?>

    <!-- Profile Header -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="d-flex align-items-center mb-3">
                <div class="avatar avatar-xxl me-4">
                    <span class="avatar-initial rounded-circle bg-label-primary fs-1">
                        <?= strtoupper(substr($candidate['candidate_name'] ?? 'CN', 0, 2)) ?>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <h4 class="mb-1"><?= htmlspecialchars($candidate['candidate_name'] ?? 'Unnamed Candidate') ?></h4>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge bg-label-<?= strtolower($candidate['lead_type'] ?? 'secondary') ?> me-1">
                            <i class="bx bx-user me-1"></i> <?= htmlspecialchars($candidate['lead_type'] ?? 'N/A') ?> Lead
                        </span>
                        <span class="badge bg-label-info me-1">
                            <i class="bx bx-briefcase me-1"></i> <?= htmlspecialchars($candidate['current_position'] ?? 'N/A') ?>
                        </span>
                        <span class="badge bg-label-<?= ($candidate['notice_period'] ?? 0) == 0 ? 'success' : 'warning' ?>">
                            <i class="bx bx-time-five me-1"></i> 
                            <?= ($candidate['notice_period'] ?? 0) == 0 ? 'Immediate' : htmlspecialchars($candidate['notice_period']) . ' days notice' ?>
                        </span>
                    </div>
                    <div class="mt-2 d-flex flex-wrap gap-3">
                        <?php if (!empty($candidate['email_id'])): ?>
                        <a href="mailto:<?= htmlspecialchars($candidate['email_id']) ?>" class="d-flex align-items-center text-body">
                            <i class="bx bx-envelope me-1"></i> <?= htmlspecialchars($candidate['email_id']) ?>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($candidate['contact_details'])): ?>
                        <a href="tel:<?= htmlspecialchars($candidate['contact_details']) ?>" class="d-flex align-items-center text-body">
                            <i class="bx bx-phone me-1"></i> <?= htmlspecialchars($candidate['contact_details']) ?>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($candidate['linkedin'])): ?>
                        <a href="<?= htmlspecialchars($candidate['linkedin']) ?>" target="_blank" class="d-flex align-items-center text-body">
                            <i class="bx bxl-linkedin me-1"></i> LinkedIn Profile
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 text-end">
            <div class="d-flex flex-wrap justify-content-end gap-2">
                <button class="btn btn-outline-primary" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i> Print
                </button>
                <?php if (in_array($user['level'], ['admin', 'recruiter','user'])): ?>
                <a href="edit.php?id=<?= htmlspecialchars($id) ?>" class="btn btn-primary">
                    <i class="bx bx-edit me-1"></i> Edit Profile
                </a>
                <?php endif; ?>
                <?php if ($user['role'] === 'admin' || ($user['role'] === 'user' && $candidate['assigned_to'] === $user['user_code'])): ?>
                <div class="dropdown">
                    <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-check-circle me-1"></i> Actions
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" id="sendEmailBtn"><i class="bx bx-envelope me-2"></i> Send Email</a></li>
                        <li><a class="dropdown-item" href="#" id="logCallBtn"><i class="bx bx-phone me-2"></i> Log Call</a></li>
                        <li><a class="dropdown-item" href="#" id="sendMessageBtn"><i class="bx bx-message-alt me-2"></i> Send Message</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addJobModal"><i class="bx bx-user-plus me-2"></i> Add to Job</a></li>
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#scheduleInterviewModal"><i class="bx bx-calendar me-2"></i> Schedule Interview</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" id="blacklistBtn"><i class="bx bx-block me-2"></i> Blacklist Candidate</a></li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Candidate Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card border border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-2">
                            <p class="text-muted mb-1">Experience</p>
                            <h5 class="mb-0"><?= htmlspecialchars($candidate['experience'] ?? '0') ?> years</h5>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="bx bx-calendar-alt"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card border border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-2">
                            <p class="text-muted mb-1">Current Location</p>
                            <h5 class="mb-0"><?= htmlspecialchars($candidate['current_location'] ?? 'N/A') ?></h5>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="bx bx-map"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card border border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-2">
                            <p class="text-muted mb-1">Current Salary/Rate</p>
                            <h5 class="mb-0">€<?= number_format($candidate['current_salary'] ?? 0) ?></h5>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="bx bx-euro"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card border border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-2">
                            <p class="text-muted mb-1">Expected Salary/Rate</p>
                            <h5 class="mb-0">€<?= number_format($candidate['expected_salary'] ?? 0) ?></h5>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="bx bx-trending-up"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="card mb-4">
        <div class="card-body">
            <ul class="nav nav-pills flex-column flex-md-row mb-3">
                <li class="nav-item">
                    <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview">
                        <i class="bx bx-user me-1"></i> Overview
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#professional">
                        <i class="bx bx-briefcase me-1"></i> Professional
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#documents">
                        <i class="bx bx-file me-1"></i> Documents (<?= count($documents) ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#jobs">
                        <i class="bx bx-briefcase me-1"></i> Related Jobs (<?= count($related_jobs) ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#interviews">
                        <i class="bx bx-calendar me-1"></i> Interviews (<?= count($interviews) ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#activity">
                        <i class="bx bx-time-five me-1"></i> Activity
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="mb-4">
                                <h5 class="mb-3">Personal Details</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <p class="mb-1 fw-semibold">Email</p>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($candidate['email_id'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 fw-semibold">Alternate Email</p>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($candidate['alternate_email_id'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 fw-semibold">Primary Contact</p>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($candidate['contact_details'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 fw-semibold">Alternate Contact</p>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($candidate['alternate_contact_details'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 fw-semibold">Current Location</p>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($candidate['current_location'] ?? '-') ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h5 class="mb-3">Professional Summary</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($candidate['professional_summary'] ?? 'No summary provided')) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="sticky-md-top" style="top: 20px;">
                                <div class="mb-4">
                                    <h5 class="mb-3">Assignment & Status</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <p class="mb-1 fw-semibold">Assigned To</p>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($candidate['assigned_to'])): ?>
                                                    <div class="avatar avatar-sm me-2">
                                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                                            <?= strtoupper(substr($candidate['assigned_to_name'] ?? 'U', 0, 2)) ?>
                                                        </span>
                                                    </div>
                                                    <span><?= htmlspecialchars($candidate['assigned_to_name'] ?? 'Unassigned') ?></span>
                                                    <?php else: ?>
                                                    <span class="text-muted">Not assigned to any recruiter</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <p class="mb-1 fw-semibold">Candidate Status</p>
                                                <span class="badge bg-label-<?= 
                                                    $candidate['candidate_status'] == 'Placed' ? 'success' : 
                                                    $candidate['candidate_status'] == 'Rejected' ? 'danger' : 
                                                    $candidate['candidate_status'] == 'Offer Made' ? 'warning' : 'primary' 
                                                ?>">
                                                    <?= htmlspecialchars($candidate['candidate_status'] ?? 'New') ?>
                                                </span>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <p class="mb-1 fw-semibold">Lead Type</p>
                                                <span class="badge bg-label-<?= strtolower($candidate['lead_type'] ?? 'secondary') ?>">
                                                    <?= htmlspecialchars($candidate['lead_type'] ?? 'Cold') ?>
                                                </span>
                                            </div>
                                            
                                            <div>
                                                <p class="mb-1 fw-semibold">Follow-up Status</p>
                                                <span class="badge bg-<?= 
                                                    $candidate['follow_up'] == 'Done' ? 'success' : 'danger' 
                                                ?>">
                                                    <?= htmlspecialchars($candidate['follow_up'] ?? 'Not Done') ?>
                                                </span>
                                                <?php if (!empty($candidate['follow_up_date'])): ?>
                                                <p class="text-muted mb-0 mt-1">Scheduled for: <?= date('M d, Y', strtotime($candidate['follow_up_date'])) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h5 class="mb-3">Compensation Details</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <p class="mb-1 fw-semibold">Compensation Type</p>
                                            <p class="mb-3"><?= ($candidate['compensation_type'] ?? 'salary') == 'salary' ? 'Annual Salary' : 'Daily Rate' ?></p>
                                            
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Current:</span>
                                                <span class="fw-medium">
                                                    €<?= number_format(($candidate['compensation_type'] ?? 'salary') == 'salary' ? ($candidate['current_salary'] ?? 0) : ($candidate['current_daily_rate'] ?? 0)) ?>
                                                    <?= ($candidate['compensation_type'] ?? 'salary') == 'salary' ? 'annual' : 'daily' ?>
                                                </span>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between">
                                                <span>Expected:</span>
                                                <span class="fw-medium text-primary">
                                                    €<?= number_format(($candidate['compensation_type'] ?? 'salary') == 'salary' ? ($candidate['expected_salary'] ?? 0) : ($candidate['expected_daily_rate'] ?? 0)) ?>
                                                    <?= ($candidate['compensation_type'] ?? 'salary') == 'salary' ? 'annual' : 'daily' ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professional Tab -->
                <div class="tab-pane fade" id="professional" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="mb-4">
                                <h5 class="mb-3">Work Experience</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <p class="mb-1 fw-semibold">Current Position</p>
                                            <p class="text-muted mb-0"><?= htmlspecialchars($candidate['current_position'] ?? '-') ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <p class="mb-1 fw-semibold">Current Employer</p>
                                            <p class="text-muted mb-0"><?= htmlspecialchars($candidate['current_employer'] ?? '-') ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <p class="mb-1 fw-semibold">Working Status</p>
                                            <p class="text-muted mb-0"><?= htmlspecialchars($candidate['current_working_status'] ?? '-') ?></p>
                                        </div>
                                        <div>
                                            <p class="mb-1 fw-semibold">Willing to Relocate</p>
                                            <p class="text-muted mb-0"><?= ($candidate['willing_to_relocate'] ?? 0) ? 'Yes' : 'No' ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h5 class="mb-3">Skills & Qualifications</h5>
                                
                                <div class="mb-4">
                                    <p class="mb-1 fw-semibold">Key Skills</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php 
                                        $skills = $candidate['skill_set'] ? explode(',', $candidate['skill_set']) : [];
                                        foreach ($skills as $skill): 
                                        ?>
                                        <span class="badge bg-primary"><?= htmlspecialchars(trim($skill)) ?></span>
                                        <?php endforeach; ?>
                                        <?php if (empty($skills)): ?>
                                        <span class="text-muted">No skills specified</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="mb-1 fw-semibold">Languages</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php 
                                        $languages = $candidate['languages'] ? explode(',', $candidate['languages']) : [];
                                        foreach ($languages as $lang): 
                                        ?>
                                        <span class="badge bg-info"><?= htmlspecialchars(trim($lang)) ?></span>
                                        <?php endforeach; ?>
                                        <?php if (empty($languages)): ?>
                                        <span class="text-muted">No languages specified</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <p class="mb-1 fw-semibold">Certifications</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php 
                                        $certs = $candidate['certifications'] ? explode(',', $candidate['certifications']) : [];
                                        foreach ($certs as $cert): 
                                        ?>
                                        <span class="badge bg-warning"><?= htmlspecialchars(trim($cert)) ?></span>
                                        <?php endforeach; ?>
                                        <?php if (empty($certs)): ?>
                                        <span class="text-muted">No certifications specified</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="sticky-md-top" style="top: 20px;">
                                <div class="mb-4">
                                    <h5 class="mb-3">Work Authorization</h5>
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <div class="avatar avatar-lg mb-3 mx-auto">
                                                <span class="avatar-initial rounded bg-label-<?= 
                                                    strpos(strtolower($candidate['work_auth_status_name'] ?? ''), 'eu') !== false ? 'success' : 
                                                    strpos(strtolower($candidate['work_auth_status_name'] ?? ''), 'permit') !== false ? 'warning' : 'danger' 
                                                ?>">
                                                    <i class="bx bx-badge-check bx-md"></i>
                                                </span>
                                            </div>
                                            <h5 class="mb-1"><?= htmlspecialchars($candidate['work_auth_status_name'] ?? 'Not specified') ?></h5>
                                            <p class="text-muted mb-0">Authorization Status</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h5 class="mb-3">Availability</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <p class="mb-1 fw-semibold">Notice Period</p>
                                                <p class="text-muted mb-0"><?= ($candidate['notice_period'] ?? 0) == 0 ? 'Immediate availability' : htmlspecialchars($candidate['notice_period']) . ' days notice period' ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <p class="mb-1 fw-semibold">Earliest Start Date</p>
                                                <p class="text-muted mb-0"><?= $candidate['can_join'] ? date('M d, Y', strtotime($candidate['can_join'])) : 'Not specified' ?></p>
                                            </div>
                                            <div>
                                                <p class="mb-1 fw-semibold">Availability Status</p>
                                                <span class="badge bg-<?= 
                                                    $candidate['availability'] == 'immediate' ? 'success' : 
                                                    $candidate['availability'] == 'notice_period' ? 'warning' : 'info' 
                                                ?>">
                                                    <?= $candidate['availability'] == 'immediate' ? 'Immediate' : 
                                                       $candidate['availability'] == 'notice_period' ? 'Serving Notice' : 
                                                       'Contract Ending' ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documents Tab -->
                <div class="tab-pane fade" id="documents" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Main Documents</h5>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#"><i class="bx bx-download me-1"></i> Download All</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-3">
                                        <?php 
                                        $mainDocs = [
                                            ['name' => 'Candidate CV', 'path' => $candidate['candidate_cv'], 'icon' => 'bx bx-file', 'color' => 'primary'],
                                            ['name' => 'Consultancy CV', 'path' => $candidate['consultancy_cv'], 'icon' => 'bx bx-file', 'color' => 'info'],
                                            ['name' => 'Consent Form', 'path' => $candidate['consent'], 'icon' => 'bx bx-check-circle', 'color' => 'success']
                                        ];
                                        
                                        foreach ($mainDocs as $doc):
                                            if (!empty($doc['path'])):
                                        ?>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3">
                                                <span class="avatar-initial rounded bg-label-<?= $doc['color'] ?>">
                                                    <i class="<?= $doc['icon'] ?>"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0"><?= $doc['name'] ?></h6>
                                                <small class="text-muted"><?= basename($doc['path']) ?></small>
                                            </div>
                                            <div class="dropdown">
                                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="../<?= htmlspecialchars($doc['path']) ?>" target="_blank"><i class="bx bx-show me-1"></i> View</a></li>
                                                    <li><a class="dropdown-item" href="../<?= htmlspecialchars($doc['path']) ?>" download><i class="bx bx-download me-1"></i> Download</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <?php 
                                            else: 
                                        ?>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3">
                                                <span class="avatar-initial rounded bg-label-secondary">
                                                    <i class="bx bx-x"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0"><?= $doc['name'] ?></h6>
                                                <small class="text-muted">Not uploaded</small>
                                            </div>
                                        </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Additional Documents (<?= count($documents) ?>)</h5>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                                        <i class="bx bx-upload me-1"></i> Upload
                                    </button>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($documents)): ?>
                                    <div class="d-grid gap-3">
                                        <?php foreach ($documents as $doc): ?>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3">
                                                <span class="avatar-initial rounded bg-label-secondary">
                                                    <i class="bx bx-file"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0"><?= htmlspecialchars(basename($doc['file_path'])) ?></h6>
                                                <small class="text-muted">Uploaded: <?= date('M d, Y', strtotime($doc['uploaded_at'])) ?></small>
                                            </div>
                                            <div class="dropdown">
                                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="../<?= htmlspecialchars($doc['file_path']) ?>" target="_blank"><i class="bx bx-show me-1"></i> View</a></li>
                                                    <li><a class="dropdown-item" href="../<?= htmlspecialchars($doc['file_path']) ?>" download><i class="bx bx-download me-1"></i> Download</a></li>
                                                    <li><a class="dropdown-item text-danger" href="#"><i class="bx bx-trash me-1"></i> Delete</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bx bx-folder-open display-4 text-muted mb-2"></i>
                                        <p class="text-muted mb-0">No additional documents uploaded yet</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Jobs Tab -->
                <div class="tab-pane fade" id="jobs" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Related Jobs</h5>
                        <?php if (in_array($user['role'], ['admin', 'recruiter','user'])): ?>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addJobModal">
                            <i class="bx bx-plus me-1"></i> Add to Job
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($related_jobs)): ?>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Client</th>
                                    <th>Applied Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($related_jobs as $job): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($job['job_title']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($job['job_reference']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($job['client_name']) ?></td>
                                    <td><?= htmlspecialchars($job['job_location']) ?></td>
                                    <td><?= date('M d, Y', strtotime($job['applied_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $job['status'] == 'Applied' ? 'primary' : 
                                            ($job['status'] == 'Interview' || $job['status'] == 'Interview Scheduled') ? 'warning' : 
                                            $job['status'] == 'Offer' ? 'success' : 
                                            $job['status'] == 'Rejected' ? 'danger' : 'secondary' 
                                        ?>">
                                            <?= htmlspecialchars($job['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                <i class="bx bx-dots-vertical-rounded"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="../jobs/view.php?id=<?= htmlspecialchars($job['job_code']) ?>"><i class="bx bx-show me-1"></i> View Job</a></li>
                                                <li><a class="dropdown-item" href="#"><i class="bx bx-edit me-1"></i> Update Status</a></li>
                                                <li><a class="dropdown-item text-danger" href="#"><i class="bx bx-trash me-1"></i> Remove from Job</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bx bx-briefcase display-4 text-muted mb-3"></i>
                        <h5 class="mb-2">No Related Jobs</h5>
                        <p class="text-muted mb-4">This candidate hasn't been assigned to any jobs yet.</p>
                        <?php if (in_array($user['role'], ['admin', 'recruiter','user'])): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJobModal">
                            <i class="bx bx-plus me-1"></i> Add to Job
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Interviews Tab -->
                <div class="tab-pane fade" id="interviews" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Interview History</h5>
                        <?php if (in_array($user['role'], ['admin', 'recruiter','user'])): ?>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#scheduleInterviewModal">
                            <i class="bx bx-calendar me-1"></i> Schedule Interview
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($interviews)): ?>
                    <div class="row g-4">
                        <?php foreach ($interviews as $int): 
                            $intDate = new DateTime($int['interview_datetime']);
                            $isFuture = $intDate->getTimestamp() > time();
                            $statusColor = match($int['outcome']) {
                                'Positive' => 'success',
                                'Negative' => 'danger',
                                'Neutral' => 'warning',
                                'Cancelled' => 'secondary',
                                default => 'primary'
                            };
                        ?>
                        <div class="col-12">
                            <div class="card <?= $isFuture ? 'border border-primary' : 'border border-success' ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar me-3">
                                                    <span class="avatar-initial rounded bg-label-<?= $isFuture ? 'primary' : 'success' ?>">
                                                        <i class="bx bx-calendar bx-md"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <h5 class="mb-0"><?= $intDate->format('M d, Y') ?> at <?= $intDate->format('H:i') ?></h5>
                                                    <small class="text-muted"><?= htmlspecialchars($int['interview_type']) ?> Interview</small>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="badge bg-label-<?= $statusColor ?>">
                                            <?= htmlspecialchars($int['outcome']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <p class="mb-1 fw-semibold">Location</p>
                                            <p class="text-muted mb-0"><?= !empty($int['location']) ? htmlspecialchars($int['location']) : 'Not specified' ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1 fw-semibold">Interviewers</p>
                                            <p class="text-muted mb-0"><?= !empty($int['interviewer_names']) ? htmlspecialchars($int['interviewer_names']) : 'Not assigned' ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <p class="mb-1 fw-semibold">Notes</p>
                                        <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($int['notes'] ?? 'No notes')) ?></p>
                                    </div>
                                
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bx bx-calendar-x display-4 text-muted mb-3"></i>
                        <h5 class="mb-2">No Interviews Scheduled</h5>
                        <p class="text-muted mb-4">This candidate hasn't had any interviews yet.</p>
                        <?php if (in_array($user['role'], ['admin', 'recruiter','user'])): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleInterviewModal">
                            <i class="bx bx-calendar me-1"></i> Schedule First Interview
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Activity Timeline Tab -->
                <div class="tab-pane fade" id="activity" role="tabpanel">
                    <?php if (!empty($timeline)): ?>
                    <ul class="timeline">
                        <?php foreach ($timeline as $item): 
                            $icon = 'bx bx-info-circle';
                            $color = 'bg-label-primary';
                            switch ($item['type']) {
                                case 'profile_update': 
                                    $icon = 'bx bx-edit'; 
                                    $color = 'bg-label-warning'; 
                                    break;
                                case 'job_application': 
                                    $icon = 'bx bx-briefcase'; 
                                    $color = 'bg-label-info'; 
                                    break;
                                case 'interview': 
                                    $icon = 'bx bx-calendar'; 
                                    $color = 'bg-label-primary'; 
                                    break;
                                case 'document_upload': 
                                    $icon = 'bx bx-file'; 
                                    $color = 'bg-label-success'; 
                                    break;
                            }
                        ?>
                        <li class="timeline-item">
                            <span class="timeline-point timeline-point-<?= $color ?>"></span>
                            <div class="timeline-event">
                                <div class="d-flex justify-content-between mb-1">
                                    <h6 class="mb-0"><?= htmlspecialchars($item['description']) ?></h6>
                                    <small class="text-muted"><?= date('M d, Y H:i', strtotime($item['timestamp'])) ?></small>
                                </div>
                                <p class="text-muted mb-0">
                                    <i class="<?= $icon ?> me-1"></i> 
                                    <?php if (!empty($item['actor'])): ?>
                                    Performed by: <?= htmlspecialchars($item['actor']) ?>
                                    <?php else: ?>
                                    System event
                                    <?php endif; ?>
                                </p>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bx bx-time-five display-4 text-muted mb-3"></i>
                        <h5 class="mb-2">No Activity Yet</h5>
                        <p class="text-muted mb-0">No activity recorded for this candidate</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<?php include __DIR__ . '/partials/modals/add_job_modal.php'; ?>
<?php include __DIR__ . '/partials/modals/schedule_interview_modal.php'; ?>
<?php include __DIR__ . '/partials/modals/upload_document_modal.php'; ?>

<!-- Print Styles -->
<style media="print">
    .no-print, .no-print *, .dropdown, .btn, .nav-tabs, .card-header {
        display: none !important;
    }
    body {
        -webkit-print-color-adjust: exact;
    }
    .container-xxl, .row, .col-lg-8, .col-lg-4 {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
    }
    .card {
        border: 1px solid #ddd !important;
        margin-bottom: 1rem !important;
    }
    .card-body {
        padding: 1rem !important;
    }
    .badge {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
</style>

<?php require_once ROOT_PATH . '/panel/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<script>
$(document).ready(function() {
    // Initialize Select2 for modals
    $('.select2-multiple').select2({
        theme: "bootstrap-5",
        placeholder: "Select options...",
        width: '100%'
    });
    
    // Quick action buttons
    $('#sendEmailBtn').on('click', function() {
        const email = '<?= htmlspecialchars($candidate['email_id'] ?? '') ?>';
        if (email) {
            window.location.href = 'mailto:' + email;
        } else {
            alert('No email address available for this candidate');
        }
    });
    
    $('#logCallBtn').on('click', function() {
        alert('Call logging feature coming soon');
    });
    
    $('#sendMessageBtn').on('click', function() {
        alert('Messaging feature coming soon');
    });
    
    $('#blacklistBtn').on('click', function() {
        if (confirm('Are you sure you want to blacklist this candidate? This will remove them from active consideration.')) {
            $.post('handlers/candidate_save_handler.php', {
                action: 'update',
                can_code: '<?= htmlspecialchars($id) ?>',
                lead_type: 'Blacklist',
                token: '<?= Auth::token() ?>'
            }, function(response) {
                if (response.success) {
                    showToast('Candidate has been blacklisted successfully!', 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    alert('Error: ' + response.message);
                }
            });
        }
    });
    
    // Tab navigation with URL updates
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).data('bs-target');
        const tabName = target.replace('#', '');
        history.replaceState({}, '', `${location.pathname}?id=<?= htmlspecialchars($id) ?>#tab=${tabName}`);
    });
    
    // Load tab from URL if present
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        $(`button[data-bs-target="#${tab}"]`).tab('show');
    }
    
    // Show toast notification
    function showToast(message, type = 'info') {
        const toastId = 'toast-' + Date.now();
        if ($('#toastContainer').length === 0) {
            $('body').append('<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
        }
        
        let bgColor, icon;
        switch(type) {
            case 'success':
                bgColor = 'bg-success';
                icon = 'bx bx-check-circle';
                break;
            case 'error':
                bgColor = 'bg-danger';
                icon = 'bx bx-error-circle';
                break;
            case 'warning':
                bgColor = 'bg-warning';
                icon = 'bx bx-error';
                break;
            default:
                bgColor = 'bg-info';
                icon = 'bx bx-info-circle';
        }
        
        $(`#toastContainer`).append(`
            <div id="${toastId}" class="toast ${bgColor} text-white" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header ${bgColor}">
                    <i class="${icon} me-2"></i>
                    <strong class="me-auto">Notification</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `);
        
        const toast = new bootstrap.Toast(document.getElementById(toastId));
        toast.show();
        
        $(`#${toastId}`).on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
});
</script>