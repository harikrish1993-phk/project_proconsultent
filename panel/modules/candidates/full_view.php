<?php
// ============================================================================
// BOOTSTRAP & AUTHORIZATION
// ============================================================================
require_once __DIR__ . '/../_common.php';

// Check permissions
if (!$user) {
    header('HTTP/1.0 403 Forbidden');
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Access denied. Please log in.</div></div>';
    exit();
}

// Page configuration
$id = $_GET['id'] ?? $_GET['can_code'] ?? null;
if (!$id) {
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Invalid candidate ID.</div></div>';
    exit();
}

try {
    $conn = Database::getInstance()->getConnection();
    
    // Fetch candidate data with related info
    $stmt = $conn->prepare("
        SELECT c.*, 
               wa.status as work_auth_status_name,
               u.full_name as assigned_to_name
        FROM candidates c
        LEFT JOIN work_authorization wa ON wa.id = c.work_auth_status
        LEFT JOIN users u ON u.user_code = c.assigned_to
        WHERE c.can_code = ?
    ");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $candidate = $stmt->get_result()->fetch_assoc();
    
    if (!$candidate) {
        throw new Exception('Candidate not found');
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
        SELECT cj.id, j.job_title, j.job_reference, cj.applied_date, cj.status, c.client_name
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
    
    // Fetch activity timeline
    $timeline = [];
    $stmt = $conn->prepare("
        (SELECT 'candidate_edit' as type, cei.edited_at as timestamp, 
                CONCAT('Edited ', cei.edited_field) as description, 
                u.full_name as actor
         FROM candidates_edit_info cei
         LEFT JOIN users u ON u.user_code = cei.edited_by
         WHERE cei.can_code = ?)
        UNION
        (SELECT 'job_application' as type, cj.applied_date as timestamp,
                CONCAT('Applied for ', j.job_title) as description,
                u.full_name as actor
         FROM candidate_jobs cj
         JOIN jobs j ON j.job_code = cj.job_code
         LEFT JOIN users u ON u.user_code = cj.added_by
         WHERE cj.can_code = ?)
        UNION
        (SELECT 'document_upload' as type, cd.uploaded_at as timestamp,
                CONCAT('Uploaded document: ', cd.file_path) as description,
                u.full_name as actor
         FROM candidate_documents cd
         LEFT JOIN users u ON u.user_code = cd.uploaded_by
         WHERE cd.candidate_code = ?)
        ORDER BY timestamp DESC
        LIMIT 20
    ");
    $stmt->bind_param("sss", $id, $id, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $timeline[] = $row;
    }
    
    // Page configuration with breadcrumb
    $pageTitle = htmlspecialchars($candidate['candidate_name'] ?? 'Candidate Profile');
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => 'index.php'],
        ['label' => 'Candidates', 'url' => 'candidates.php'],
        ['label' => 'View Candidate', 'active' => true]
    ];

} catch (Exception $e) {
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
                <div>
                    <h4 class="mb-1"><?= htmlspecialchars($candidate['candidate_name'] ?? 'Unnamed Candidate') ?></h4>
                    <div class="d-flex flex-wrap gap-2">
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
                        <li><a class="dropdown-item" href="#"><i class="bx bx-envelope me-2"></i> Send Email</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bx bx-phone me-2"></i> Call Candidate</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bx bx-message-alt me-2"></i> Send Message</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="bx bx-user-plus me-2"></i> Add to Job</a></li>
                        <li><a class="dropdown-item text-danger" href="#"><i class="bx bx-block me-2"></i> Blacklist</a></li>
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
                            <p class="text-muted mb-1">Location</p>
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
                            <p class="text-muted mb-1">Current Salary</p>
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
                            <p class="text-muted mb-1">Expected Salary</p>
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
                    <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile">
                        <i class="bx bx-user me-1"></i> Profile
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
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#activity">
                        <i class="bx bx-time-five me-1"></i> Activity Timeline
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#notes">
                        <i class="bx bx-message-alt me-1"></i> Notes & Comments
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                    <div class="row g-4">
                        <!-- Left Column - Professional Details -->
                        <div class="col-lg-8">
                            <div class="mb-4">
                                <h5 class="mb-3">Professional Details</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <p class="mb-1 fw-semibold">Current Position</p>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($candidate['current_position'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 fw-semibold">Current Employer</p>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($candidate['current_employer'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 fw-semibold">Work Authorization</p>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($candidate['work_auth_status_name'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 fw-semibold">Working Status</p>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($candidate['current_working_status'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 fw-semibold">LinkedIn</p>
                                        <p class="mb-0">
                                            <?php if (!empty($candidate['linkedin'])): ?>
                                            <a href="<?= htmlspecialchars($candidate['linkedin']) ?>" target="_blank" class="text-primary">
                                                <i class="bx bxl-linkedin me-1"></i> Profile Link
                                            </a>
                                            <?php else: ?>
                                            <span class="text-muted">Not Provided</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 fw-semibold">Willing to Relocate</p>
                                        <p class="text-muted mb-0"><?= ($candidate['willing_to_relocate'] ?? 0) ? 'Yes' : 'No' ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h5 class="mb-3">Skills & Qualifications</h5>
                                
                                <div class="mb-3">
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
                                
                                <div class="mb-3">
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
                                
                                <div class="mb-3">
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
                            
                            <div class="mb-4">
                                <h5 class="mb-3">Professional Summary</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($candidate['professional_summary'] ?? 'No summary provided')) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column - Additional Info -->
                        <div class="col-lg-4">
                            <div class="sticky-md-top" style="top: 20px;">
                                <div class="mb-4">
                                    <h5 class="mb-3">Contact Information</h5>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-3">
                                            <div class="d-flex">
                                                <div class="avatar me-3">
                                                    <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-envelope"></i></span>
                                                </div>
                                                <div>
                                                    <p class="mb-0 fw-medium">Primary Email</p>
                                                    <a href="mailto:<?= htmlspecialchars($candidate['email_id'] ?? '#') ?>" class="text-primary">
                                                        <?= htmlspecialchars($candidate['email_id'] ?? '-') ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="mb-3">
                                            <div class="d-flex">
                                                <div class="avatar me-3">
                                                    <span class="avatar-initial rounded bg-label-info"><i class="bx bx-envelope"></i></span>
                                                </div>
                                                <div>
                                                    <p class="mb-0 fw-medium">Alternate Email</p>
                                                    <a href="mailto:<?= htmlspecialchars($candidate['alternate_email_id'] ?? '#') ?>" class="text-primary">
                                                        <?= htmlspecialchars($candidate['alternate_email_id'] ?? '-') ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="mb-3">
                                            <div class="d-flex">
                                                <div class="avatar me-3">
                                                    <span class="avatar-initial rounded bg-label-success"><i class="bx bx-phone"></i></span>
                                                </div>
                                                <div>
                                                    <p class="mb-0 fw-medium">Primary Contact</p>
                                                    <a href="tel:<?= htmlspecialchars($candidate['contact_details'] ?? '#') ?>" class="text-primary">
                                                        <?= htmlspecialchars($candidate['contact_details'] ?? '-') ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="mb-3">
                                            <div class="d-flex">
                                                <div class="avatar me-3">
                                                    <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-phone"></i></span>
                                                </div>
                                                <div>
                                                    <p class="mb-0 fw-medium">Alternate Contact</p>
                                                    <a href="tel:<?= htmlspecialchars($candidate['alternate_contact_details'] ?? '#') ?>" class="text-primary">
                                                        <?= htmlspecialchars($candidate['alternate_contact_details'] ?? '-') ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                
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
                                    <button type="button" class="btn btn-sm btn-primary">Upload New</button>
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
                                                <li><a class="dropdown-item" href="#"><i class="bx bx-show me-1"></i> View Job</a></li>
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
                        <button class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Add to Job
                        </button>
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
                                case 'candidate_edit': 
                                    $icon = 'bx bx-edit'; 
                                    $color = 'bg-label-warning'; 
                                    break;
                                case 'job_application': 
                                    $icon = 'bx bx-briefcase'; 
                                    $color = 'bg-label-info'; 
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

                <!-- Notes & Comments Tab -->
                <div class="tab-pane fade" id="notes" role="tabpanel">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0 avatar me-3">
                                    <span class="avatar-initial rounded-circle bg-label-primary">
                                        <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 2)) ?>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <form>
                                        <div class="mb-3">
                                            <textarea class="form-control" rows="3" placeholder="Add a note or comment..."></textarea>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-primary">Add Note</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Comments (3)</h5>
                            <small class="text-muted">Last updated today</small>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-4">
                                <div class="flex-shrink-0 avatar me-3">
                                    <span class="avatar-initial rounded-circle bg-label-success">
                                        JS
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between mb-1">
                                        <h6 class="mb-0">John Smith</h6>
                                        <small class="text-muted">Today, 14:30</small>
                                    </div>
                                    <p class="mb-0">Had a great call with the candidate. They are very interested in the position and available for an interview next week.</p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-start mb-4">
                                <div class="flex-shrink-0 avatar me-3">
                                    <span class="avatar-initial rounded-circle bg-label-info">
                                        MA
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between mb-1">
                                        <h6 class="mb-0">Maria Anders</h6>
                                        <small class="text-muted">Yesterday, 09:15</small>
                                    </div>
                                    <p class="mb-0">Candidate has excellent technical skills but seems a bit hesitant about relocation. Need to discuss benefits package in more detail.</p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0 avatar me-3">
                                    <span class="avatar-initial rounded-circle bg-label-warning">
                                        TL
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between mb-1">
                                        <h6 class="mb-0">Tom Lee</h6>
                                        <small class="text-muted">May 15, 2023</small>
                                    </div>
                                    <p class="mb-0">Initial screening completed. Candidate meets all technical requirements and has good communication skills. Moving to next stage.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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

<!-- <?php require_once ROOT_PATH . '/panel/includes/footer.php'; ?> -->

<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Handle tab changes to update URL
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
});
</script>

<?php
$pageContent = ob_get_clean();
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/includes/sidebar.php';
echo $pageContent;
require_once ROOT_PATH . '/panel/includes/footer.php';
?>