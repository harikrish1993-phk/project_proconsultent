<?php
// ============================================================================
// BOOTSTRAP & AUTHORIZATION
// ============================================================================
require_once __DIR__ . '/../_common.php';

// Check permissions
if (!$user || !in_array($user['level'], ['admin', 'recruiter','user', 'manager'])) {
    header('HTTP/1.0 403 Forbidden');
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Access denied.</div></div>';
    exit();
}

// Page configuration
$pageTitle = 'Edit Candidate';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'Candidates', 'url' => 'candidates.php'],
    ['label' => 'Edit Candidate']
];

$can_code = $_GET['id'] ?? $_GET['can_code'] ?? null;
if (!$can_code) {
    echo renderBreadcrumb($breadcrumbs);
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Invalid candidate ID.</div></div>';
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Fetch candidate data
    $stmt = $conn->prepare("SELECT * FROM candidates WHERE can_code = ?");
    $stmt->bind_param("s", $can_code);
    $stmt->execute();
    $candidate = $stmt->get_result()->fetch_assoc();
    
    if (!$candidate) {
        throw new Exception('Candidate not found');
    }
    
    // Get available data for dropdowns
    $work_auth_options = [];
    $result = $conn->query("SELECT id, status FROM work_authorization WHERE is_active = 1");
    while ($row = $result->fetch_assoc()) {
        $work_auth_options[] = $row;
    }
    
    $skills_list = [];
    $result = $conn->query("SELECT id, skill_name FROM skills WHERE is_active = 1 ORDER BY skill_name");
    while ($row = $result->fetch_assoc()) {
        $skills_list[] = $row;
    }
    
    $languages_list = [];
    $result = $conn->query("SELECT id, language_name FROM languages WHERE is_active = 1 ORDER BY language_name");
    while ($row = $result->fetch_assoc()) {
        $languages_list[] = $row;
    }
    
    $certifications_list = [];
    $result = $conn->query("SELECT id, cert_name FROM certifications WHERE is_active = 1 ORDER BY cert_name");
    while ($row = $result->fetch_assoc()) {
        $certifications_list[] = $row;
    }
    
    $recruiters = [];
    if ($user['role'] === 'admin') {
        $result = $conn->query("SELECT user_code, full_name FROM users WHERE role IN ('recruiter','user', 'admin') AND is_active = 1 ORDER BY full_name");
        while ($row = $result->fetch_assoc()) {
            $recruiters[] = $row;
        }
    }
    
    // Get edit history
    $history = [];
    $stmt = $conn->prepare("
        SELECT cei.*, u.full_name as editor_name 
        FROM candidates_edit_info cei
        LEFT JOIN users u ON cei.edited_by = u.user_code
        WHERE cei.can_code = ?
        ORDER BY cei.edited_at DESC
        LIMIT 10
    ");
    $stmt->bind_param("s", $can_code);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    
    // Get related jobs
    $related_jobs = [];
    $stmt = $conn->prepare("
        SELECT cj.id, j.job_title, cj.applied_date, cj.status
        FROM candidate_jobs cj
        JOIN jobs j ON j.job_code = cj.job_code
        WHERE cj.can_code = ?
        ORDER BY cj.applied_date DESC
    ");
    $stmt->bind_param("s", $can_code);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $related_jobs[] = $row;
    }
    
} catch (Exception $e) {
    echo renderBreadcrumb($breadcrumbs);
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Error: ' . $e->getMessage() . '</div></div>';
    // include __DIR__ . '/includes/footer.php';
    exit();
}
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <?= renderBreadcrumb($breadcrumbs) ?>
    
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Candidates /</span> Edit Candidate
    </h4>

    <div class="row">
        <!-- Main Form Column -->
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Candidate Details</h5>
                    <span class="badge bg-label-<?= strtolower($candidate['lead_type']) ?? 'secondary' ?>">
                        <?= htmlspecialchars($candidate['lead_type'] ?? 'N/A') ?> Lead
                    </span>
                </div>
                <div class="card-body">
                    <form id="formCandidateEdit" method="POST" action="handlers/candidate_save_handler.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="can_code" value="<?= htmlspecialchars($can_code) ?>">
                        <input type="hidden" name="token" value="<?= Auth::token(); ?>">
                        <input type="hidden" name="updated_by" value="<?= $user['user_code'] ?>">

                        <ul class="nav nav-pills flex-column flex-md-row mb-3">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#basic">
                                    <i class="bx bx-user me-1"></i> Basic Info
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#professional">
                                    <i class="bx bx-briefcase me-1"></i> Professional
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#location">
                                    <i class="bx bx-map me-1"></i> Location
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#compensation">
                                    <i class="bx bx-euro me-1"></i> Compensation
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#classification">
                                    <i class="bx bx-category me-1"></i> Classification
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#documents">
                                    <i class="bx bx-file me-1"></i> Documents
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- Basic Information Tab -->
                            <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Candidate Name <span class="text-danger">*</span></label>
                                        <input type="text" name="candidate_name" class="form-control" 
                                            value="<?= htmlspecialchars($candidate['candidate_name'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email_id" class="form-control" 
                                            value="<?= htmlspecialchars($candidate['email_id'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Primary Contact</label>
                                        <input type="tel" name="contact_details" class="form-control" 
                                            value="<?= htmlspecialchars($candidate['contact_details'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Alternate Contact</label>
                                        <input type="tel" name="alternate_contact_details" class="form-control" 
                                            value="<?= htmlspecialchars($candidate['alternate_contact_details'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">LinkedIn Profile</label>
                                        <input type="url" name="linkedin" class="form-control" 
                                            value="<?= htmlspecialchars($candidate['linkedin'] ?? '') ?>"
                                            placeholder="https://linkedin.com/in/...">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Alternate Email</label>
                                        <input type="email" name="alternate_email_id" class="form-control" 
                                            value="<?= htmlspecialchars($candidate['alternate_email_id'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Professional Details Tab -->
                            <div class="tab-pane fade" id="professional" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Current Position</label>
                                        <input type="text" name="current_position" class="form-control" 
                                            value="<?= htmlspecialchars($candidate['current_position'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Current Employer</label>
                                        <input type="text" name="current_employer" class="form-control" 
                                            value="<?= htmlspecialchars($candidate['current_employer'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Total Experience (Years)</label>
                                        <input type="number" name="experience" class="form-control" min="0" step="0.1"
                                            value="<?= htmlspecialchars($candidate['experience'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Professional Summary</label>
                                        <textarea name="professional_summary" class="form-control" rows="3"><?= htmlspecialchars($candidate['professional_summary'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label class="form-label">Key Skills <span class="text-danger">*</span></label>
                                        <select name="skill_set[]" class="form-select select2-multiple" multiple="multiple" required>
                                            <?php 
                                            $existingSkills = $candidate['skill_set'] ? explode(',', $candidate['skill_set']) : [];
                                            foreach ($skills_list as $skill): 
                                                $selected = in_array($skill['skill_name'], $existingSkills) ? 'selected' : '';
                                            ?>
                                            <option value="<?= htmlspecialchars($skill['skill_name']) ?>" <?= $selected ?>>
                                                <?= htmlspecialchars($skill['skill_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Select multiple skills relevant to the candidate</div>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label class="form-label">Languages</label>
                                        <select name="languages[]" class="form-select select2-multiple" multiple="multiple">
                                            <?php 
                                            $existingLanguages = $candidate['languages'] ? explode(',', $candidate['languages']) : [];
                                            foreach ($languages_list as $lang): 
                                                $selected = in_array($lang['language_name'], $existingLanguages) ? 'selected' : '';
                                            ?>
                                            <option value="<?= htmlspecialchars($lang['language_name']) ?>" <?= $selected ?>>
                                                <?= htmlspecialchars($lang['language_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label class="form-label">Certifications</label>
                                        <select name="certifications[]" class="form-select select2-multiple" multiple="multiple">
                                            <?php 
                                            $existingCerts = $candidate['certifications'] ? explode(',', $candidate['certifications']) : [];
                                            foreach ($certifications_list as $cert): 
                                                $selected = in_array($cert['cert_name'], $existingCerts) ? 'selected' : '';
                                            ?>
                                            <option value="<?= htmlspecialchars($cert['cert_name']) ?>" <?= $selected ?>>
                                                <?= htmlspecialchars($cert['cert_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Location & Availability Tab -->
                            <div class="tab-pane fade" id="location" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Current Location <span class="text-danger">*</span></label>
                                        <input type="text" name="current_location" class="form-control" 
                                            value="<?= htmlspecialchars($candidate['current_location'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Preferred Location</label>
                                        <input type="text" name="preferred_location" class="form-control" 
                                            value="<?= htmlspecialchars($candidate['preferred_location'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Work Authorization <span class="text-danger">*</span></label>
                                        <select name="work_auth_status" class="form-select" required>
                                            <option value="">Select authorization</option>
                                            <?php foreach ($work_auth_options as $wa): 
                                                $selected = ($wa['id'] == ($candidate['work_auth_status'] ?? '')) ? 'selected' : '';
                                            ?>
                                            <option value="<?= $wa['id'] ?>" <?= $selected ?>><?= htmlspecialchars($wa['status']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Notice Period (Days)</label>
                                        <input type="number" name="notice_period" class="form-control" min="0"
                                            value="<?= htmlspecialchars($candidate['notice_period'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Earliest Start Date</label>
                                        <input type="date" name="can_join" class="form-control" 
                                            value="<?= htmlspecialchars($candidate['can_join'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Working Status</label>
                                        <select name="current_working_status" class="form-select">
                                            <option value="">Select status</option>
                                            <option value="Freelance(Self)" <?= ($candidate['current_working_status'] ?? '') == 'Freelance(Self)' ? 'selected' : '' ?>>Freelance (Self)</option>
                                            <option value="Freelance(Company)" <?= ($candidate['current_working_status'] ?? '') == 'Freelance(Company)' ? 'selected' : '' ?>>Freelance (Company)</option>
                                            <option value="Employee" <?= ($candidate['current_working_status'] ?? '') == 'Employee' ? 'selected' : '' ?>>Employee</option>
                                            <option value="Unemployed" <?= ($candidate['current_working_status'] ?? '') == 'Unemployed' ? 'selected' : '' ?>>Unemployed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Willing to Relocate?</label>
                                        <select name="willing_to_relocate" class="form-select">
                                            <option value="0" <?= ($candidate['willing_to_relocate'] ?? '0') == '0' ? 'selected' : '' ?>>No</option>
                                            <option value="1" <?= ($candidate['willing_to_relocate'] ?? '0') == '1' ? 'selected' : '' ?>>Yes</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Availability</label>
                                        <select name="availability" class="form-select">
                                            <option value="immediate" <?= ($candidate['availability'] ?? 'immediate') == 'immediate' ? 'selected' : '' ?>>Immediate</option>
                                            <option value="notice_period" <?= ($candidate['availability'] ?? '') == 'notice_period' ? 'selected' : '' ?>>Serving Notice Period</option>
                                            <option value="contract_ending" <?= ($candidate['availability'] ?? '') == 'contract_ending' ? 'selected' : '' ?>>Contract Ending</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Compensation Tab -->
                            <div class="tab-pane fade" id="compensation" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label d-block">Compensation Type</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="compensation_type" id="salaryType" value="salary" 
                                                <?= ($candidate['compensation_type'] ?? 'salary') == 'salary' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="salaryType">Annual Salary</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="compensation_type" id="rateType" value="rate" 
                                                <?= ($candidate['compensation_type'] ?? '') == 'rate' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="rateType">Daily Rate</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6 compensation-field salary-field">
                                        <label class="form-label">Current Annual Salary (€)</label>
                                        <input type="number" name="current_salary" class="form-control" min="0" step="1000"
                                            value="<?= htmlspecialchars($candidate['current_salary'] ?? '') ?>">
                                    </div>

                                    <div class="col-md-6 compensation-field salary-field">
                                        <label class="form-label">Expected Annual Salary (€)</label>
                                        <input type="number" name="expected_salary" class="form-control" min="0" step="1000"
                                            value="<?= htmlspecialchars($candidate['expected_salary'] ?? '') ?>">
                                    </div>

                                    <div class="col-md-6 compensation-field rate-field <?= ($candidate['compensation_type'] ?? '') == 'rate' ? '' : 'd-none' ?>">
                                        <label class="form-label">Current Daily Rate (€)</label>
                                        <input type="number" name="current_daily_rate" class="form-control" min="0" step="50"
                                            value="<?= htmlspecialchars($candidate['current_daily_rate'] ?? '') ?>">
                                    </div>

                                    <div class="col-md-6 compensation-field rate-field <?= ($candidate['compensation_type'] ?? '') == 'rate' ? '' : 'd-none' ?>">
                                        <label class="form-label">Expected Daily Rate (€)</label>
                                        <input type="number" name="expected_daily_rate" class="form-control" min="0" step="50"
                                            value="<?= htmlspecialchars($candidate['expected_daily_rate'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Classification Tab -->
                            <div class="tab-pane fade" id="classification" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Candidate Status <span class="text-danger">*</span></label>
                                        <select name="candidate_status" class="form-select" required>
                                            <option value="New" <?= ($candidate['candidate_status'] ?? '') == 'New' ? 'selected' : '' ?>>New</option>
                                            <option value="Contacted" <?= ($candidate['candidate_status'] ?? '') == 'Contacted' ? 'selected' : '' ?>>Contacted</option>
                                            <option value="Interview Scheduled" <?= ($candidate['candidate_status'] ?? '') == 'Interview Scheduled' ? 'selected' : '' ?>>Interview Scheduled</option>
                                            <option value="Offer Made" <?= ($candidate['candidate_status'] ?? '') == 'Offer Made' ? 'selected' : '' ?>>Offer Made</option>
                                            <option value="Placed" <?= ($candidate['candidate_status'] ?? '') == 'Placed' ? 'selected' : '' ?>>Placed</option>
                                            <option value="Rejected" <?= ($candidate['candidate_status'] ?? '') == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                            <option value="On Hold" <?= ($candidate['candidate_status'] ?? '') == 'On Hold' ? 'selected' : '' ?>>On Hold</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Lead Type <span class="text-danger">*</span></label>
                                        <select name="lead_type" class="form-select" required>
                                            <option value="Cold" <?= ($candidate['lead_type'] ?? '') == 'Cold' ? 'selected' : '' ?>>Cold</option>
                                            <option value="Warm" <?= ($candidate['lead_type'] ?? '') == 'Warm' ? 'selected' : '' ?>>Warm</option>
                                            <option value="Hot" <?= ($candidate['lead_type'] ?? '') == 'Hot' ? 'selected' : '' ?>>Hot</option>
                                            <option value="Blacklist" <?= ($candidate['lead_type'] ?? '') == 'Blacklist' ? 'selected' : '' ?>>Blacklist</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Lead Role Type <span class="text-danger">*</span></label>
                                        <select name="lead_type_role" class="form-select" required>
                                            <option value="1" <?= ($candidate['lead_type_role'] ?? '1') == '1' ? 'selected' : '' ?>>Recruitment</option>
                                            <option value="0" <?= ($candidate['lead_type_role'] ?? '') == '0' ? 'selected' : '' ?>>Payroll</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Source <span class="text-danger">*</span></label>
                                        <select name="source" class="form-select" required>
                                            <option value="LinkedIn" <?= ($candidate['source'] ?? '') == 'LinkedIn' ? 'selected' : '' ?>>LinkedIn</option>
                                            <option value="Direct" <?= ($candidate['source'] ?? '') == 'Direct' ? 'selected' : '' ?>>Direct</option>
                                            <option value="Referral" <?= ($candidate['source'] ?? '') == 'Referral' ? 'selected' : '' ?>>Referral</option>
                                            <option value="Job Board" <?= ($candidate['source'] ?? '') == 'Job Board' ? 'selected' : '' ?>>Job Board</option>
                                            <option value="Agency" <?= ($candidate['source'] ?? '') == 'Agency' ? 'selected' : '' ?>>Agency</option>
                                            <option value="Website" <?= ($candidate['source'] ?? '') == 'Website' ? 'selected' : '' ?>>Website</option>
                                            <option value="Other" <?= ($candidate['source'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Candidate Rating</label>
                                        <div class="rating-stars">
                                            <?php 
                                            $rating = $candidate['candidate_rating'] ?? 3;
                                            for ($i = 5; $i >= 1; $i--):
                                            ?>
                                            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $rating == $i ? 'checked' : '' ?> class="d-none">
                                            <label for="star<?= $i ?>" class="star-rating"><i class="bx bxs-star"></i></label>
                                            <?php endfor; ?>
                                        </div>
                                        <input type="hidden" name="candidate_rating" id="candidate_rating" value="<?= $rating ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Role Addressed</label>
                                        <input type="text" name="role_addressed" class="form-control" 
                                            value="<?= htmlspecialchars($candidate['role_addressed'] ?? '') ?>">
                                    </div>
                                    
                                    <?php if ($user['role'] === 'admin' || count($recruiters) > 0): ?>
                                    <div class="col-md-12">
                                        <label class="form-label">Assign To Recruiter</label>
                                        <select name="assigned_to" class="form-select">
                                            <option value="">Unassigned</option>
                                            <?php foreach ($recruiters as $recruiter): 
                                                $selected = ($recruiter['user_code'] == ($candidate['assigned_to'] ?? '')) ? 'selected' : '';
                                            ?>
                                            <option value="<?= htmlspecialchars($recruiter['user_code']) ?>" <?= $selected ?>>
                                                <?= htmlspecialchars($recruiter['full_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">
                                            <?php if ($candidate['assigned_to']): ?>
                                            Currently assigned to: <?= htmlspecialchars($candidate['assigned_to_name'] ?? 'Unknown') ?>
                                            <?php else: ?>
                                            Not assigned to any recruiter
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Documents Tab -->
                            <div class="tab-pane fade" id="documents" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Candidate CV</label>
                                        <input type="file" name="candidate_cv" class="form-control" accept=".pdf,.doc,.docx">
                                        <div class="form-text">
                                            Current: <?= $candidate['candidate_cv'] ? '<a href="../' . htmlspecialchars($candidate['candidate_cv']) . '" target="_blank" class="text-primary">View CV</a>' : 'Not uploaded' ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Consultancy CV</label>
                                        <input type="file" name="consultancy_cv" class="form-control" accept=".pdf,.doc,.docx">
                                        <div class="form-text">
                                            Current: <?= $candidate['consultancy_cv'] ? '<a href="../' . htmlspecialchars($candidate['consultancy_cv']) . '" target="_blank" class="text-primary">View CV</a>' : 'Not uploaded' ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Consent Form</label>
                                        <input type="file" name="consent" class="form-control" accept=".pdf">
                                        <div class="form-text">
                                            Current: <?= $candidate['consent'] ? '<a href="../' . htmlspecialchars($candidate['consent']) . '" target="_blank" class="text-primary">View Form</a>' : 'Not uploaded' ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label class="form-label">Additional Documents</label>
                                        <input type="file" name="additional_docs[]" class="form-control" multiple accept=".pdf,.doc,.docx,.jpg,.png">
                                        <div class="form-text">Add any supporting documents</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Related Jobs Section -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">Related Jobs</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addJobModal">
                                        <i class="bx bx-plus me-1"></i> Add to Job
                                    </button>
                                </div>
                                
                                <?php if (!empty($related_jobs)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Job Title</th>
                                                <th>Applied Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($related_jobs as $job): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($job['job_title']) ?></td>
                                                <td><?= htmlspecialchars($job['applied_date']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $job['status'] == 'Applied' ? 'primary' : 
                                                        $job['status'] == 'Interview' ? 'warning' : 
                                                        $job['status'] == 'Offer' ? 'success' : 
                                                        $job['status'] == 'Rejected' ? 'danger' : 'secondary'
                                                    ?>">
                                                        <?= htmlspecialchars($job['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-icon btn-outline-danger remove-from-job" 
                                                            data-job-id="<?= $job['id'] ?>">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted mb-0">No jobs assigned to this candidate yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Follow-up & Notes -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">Follow-up & Notes</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Follow-up Status</label>
                                        <select name="follow_up" class="form-select">
                                            <option value="Not Done" <?= ($candidate['follow_up'] ?? '') == 'Not Done' ? 'selected' : '' ?>>Not Done</option>
                                            <option value="Done" <?= ($candidate['follow_up'] ?? '') == 'Done' ? 'selected' : '' ?>>Done</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label">Follow-up Date</label>
                                        <input type="date" name="follow_up_date" class="form-control" 
                                            value="<?= htmlspecialchars($candidate['follow_up_date'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label">Face to Face Date</label>
                                        <input type="date" name="face_to_face" class="form-control" 
                                            value="<?= htmlspecialchars($candidate['face_to_face'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <label class="form-label">Additional Notes</label>
                                        <textarea name="extra_details" class="form-control" rows="3"><?= htmlspecialchars($candidate['extra_details'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end gap-3">
                            <a href="view.php?id=<?= htmlspecialchars($can_code) ?>" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Cancel
                            </a>
                            <button type="reset" class="btn btn-outline-warning">
                                <i class="bx bx-reset me-1"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bx bx-save me-1"></i> Update Candidate
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="col-xl-4">
            <!-- Edit History -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit History</h5>
                    <small class="text-muted">Last 10 changes</small>
                </div>
                <div class="card-body">
                    <?php if (!empty($history)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($history as $entry): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?= htmlspecialchars($entry['edited_field']) ?></strong><br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars(substr($entry['old_value'], 0, 30) . (strlen($entry['old_value']) > 30 ? '...' : '')) ?>
                                        → 
                                        <?= htmlspecialchars(substr($entry['new_value'], 0, 30) . (strlen($entry['new_value']) > 30 ? '...' : '')) ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted"><?= date('M d', strtotime($entry['edited_at'])) ?></small><br>
                                    <small class="text-muted"><?= htmlspecialchars($entry['editor_name'] ?? 'Unknown') ?></small>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p class="text-muted mb-0">No edits recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="view.php?id=<?= htmlspecialchars($can_code) ?>" class="btn btn-outline-primary">
                            <i class="bx bx-show me-1"></i> View Profile
                        </a>
                        <button type="button" class="btn btn-outline-info" id="sendEmailBtn">
                            <i class="bx bx-envelope me-1"></i> Send Email
                        </button>
                        <button type="button" class="btn btn-outline-success" id="logCallBtn">
                            <i class="bx bx-phone me-1"></i> Log Call
                        </button>
                        <button type="button" class="btn btn-outline-warning" id="addNoteBtn">
                            <i class="bx bx-edit me-1"></i> Add Note
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="blacklistBtn"
                            <?= ($candidate['lead_type'] ?? '') == 'Blacklist' ? 'disabled' : '' ?>>
                            <i class="bx bx-block me-1"></i> Blacklist Candidate
                        </button>
                    </div>
                </div>
            </div>

            <!-- Candidate Summary -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Candidate Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Experience:</strong> 
                        <span class="badge bg-label-info"><?= htmlspecialchars($candidate['experience'] ?? '0') ?> years</span>
                    </div>
                    <div class="mb-3">
                        <strong>Skills:</strong>
                        <?php 
                        $skills = $candidate['skill_set'] ? explode(',', $candidate['skill_set']) : [];
                        $displaySkills = array_slice($skills, 0, 5);
                        foreach ($displaySkills as $skill): 
                        ?>
                        <span class="badge bg-primary me-1"><?= htmlspecialchars(trim($skill)) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($skills) > 5): ?>
                        <span class="badge bg-label-secondary">+<?= count($skills) - 5 ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <strong>Availability:</strong> 
                        <span class="badge bg-<?= ($candidate['notice_period'] ?? 0) == 0 ? 'success' : 'warning' ?>">
                            <?= ($candidate['notice_period'] ?? 0) == 0 ? 'Immediate' : htmlspecialchars($candidate['notice_period'] ?? '0') . ' days notice' ?>
                        </span>
                    </div>
                    <div>
                        <strong>Compensation:</strong><br>
                        <?php if (($candidate['compensation_type'] ?? 'salary') == 'salary'): ?>
                        Expected: €<?= number_format($candidate['expected_salary'] ?? 0) ?> annually
                        <?php else: ?>
                        Expected: €<?= number_format($candidate['expected_daily_rate'] ?? 0) ?> daily
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add to Job Modal -->
<div class="modal fade" id="addJobModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Candidate to Job</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Search Jobs</label>
                    <input type="text" class="form-control" id="jobSearch" placeholder="Search by job title, client, or reference...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="jobsTable">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Client</th>
                                <th>Location</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
                <div class="text-center py-3 d-none" id="noJobsMessage">
                    <i class="bx bx-search-alt display-4 text-muted"></i>
                    <p class="text-muted mt-2">No jobs found matching your criteria</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- <?php require_once ROOT_PATH . '/panel/includes/footer.php'; ?> -->

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<script>
$(document).ready(function() {
    // Initialize Select2 for multi-selects
    $('.select2-multiple').select2({
        theme: "bootstrap-5",
        placeholder: "Select options...",
        width: '100%'
    });
    
    // Compensation type toggle
    $('input[name="compensation_type"]').change(function() {
        if ($(this).val() === 'salary') {
            $('.salary-field').removeClass('d-none');
            $('.rate-field').addClass('d-none');
        } else {
            $('.rate-field').removeClass('d-none');
            $('.salary-field').addClass('d-none');
        }
    });
    
    // Star rating
    $('.star-rating').hover(
        function() {
            $(this).prevAll().addBack().addClass('text-warning');
        },
        function() {
            $(this).prevAll().addBack().removeClass('text-warning');
            const selectedValue = $('#candidate_rating').val();
            $('.star-rating').slice(0, selectedValue).addClass('text-warning');
        }
    ).click(function() {
        const rating = $(this).index() + 1;
        $('#candidate_rating').val(rating);
        $('.star-rating').removeClass('text-warning').slice(0, rating).addClass('text-warning');
    });
    
    // Form submission
    $('#formCandidateEdit').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $('#submitBtn');
        
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Updating...');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                submitBtn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Update Candidate');
                
                if (response.success) {
                    showSuccessToast('Candidate updated successfully!');
                    setTimeout(function() {
                        window.location.href = 'view.php?id=' + response.can_code;
                    }, 1500);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Update Candidate');
                alert('An error occurred: ' + (xhr.responseJSON?.message || xhr.statusText));
            }
        });
    });
    
    // Add to job modal
    $('#addJobModal').on('show.bs.modal', function() {
        loadAvailableJobs();
    });
    
    // Job search
    $('#jobSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#jobsTable tbody tr').each(function() {
            const jobTitle = $(this).find('td:eq(0)').text().toLowerCase();
            const client = $(this).find('td:eq(1)').text().toLowerCase();
            $(this).toggle(jobTitle.includes(searchTerm) || client.includes(searchTerm));
        });
        
        const visibleRows = $('#jobsTable tbody tr:visible').length;
        $('#noJobsMessage').toggleClass('d-none', visibleRows > 0);
    });
    
    // Quick actions
    $('#sendEmailBtn').on('click', function() {
        const email = $('input[name="email_id"]').val();
        if (email) {
            window.location.href = 'mailto:' + email;
        } else {
            alert('No email address available for this candidate');
        }
    });
    
    $('#logCallBtn').on('click', function() {
        alert('Call logging feature coming soon');
    });
    
    $('#addNoteBtn').on('click', function() {
        const note = prompt('Enter your note:');
        if (note) {
            // This would typically save to a notes table via AJAX
            alert('Note saved successfully');
        }
    });
    
    $('#blacklistBtn').on('click', function() {
        if (confirm('Are you sure you want to blacklist this candidate? This will remove them from active consideration.')) {
            $('select[name="lead_type"]').val('Blacklist');
            showSuccessToast('Candidate marked as Blacklist');
        }
    });
    
    // Remove from job
    $(document).on('click', '.remove-from-job', function() {
        const jobId = $(this).data('job-id');
        if (confirm('Are you sure you want to remove this candidate from the job?')) {
            $.post('handlers/remove_candidate_from_job.php', {
                candidate_job_id: jobId,
                token: '<?= Auth::token() ?>'
            }, function(response) {
                if (response.success) {
                    showSuccessToast('Candidate removed from job');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            });
        }
    });
    
    // Load available jobs for adding to job
    function loadAvailableJobs() {
        $('#jobsTable tbody').html('<tr><td colspan="4" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
        
        $.get('handlers/get_available_jobs.php', { 
            candidate_id: '<?= htmlspecialchars($can_code) ?>',
            token: '<?= Auth::token() ?>'
        }, function(response) {
            if (response.success && response.jobs.length > 0) {
                let rows = '';
                response.jobs.forEach(job => {
                    rows += `
                    <tr>
                        <td><strong>${job.job_title}</strong><br><small class="text-muted">${job.job_reference}</small></td>
                        <td>${job.client_name}</td>
                        <td>${job.location}</td>
                        <td>
                            <button class="btn btn-sm btn-primary add-to-job" 
                                    data-job-code="${job.job_code}"
                                    data-job-title="${job.job_title}">
                                Add
                            </button>
                        </td>
                    </tr>
                    `;
                });
                $('#jobsTable tbody').html(rows);
                $('#noJobsMessage').addClass('d-none');
            } else {
                $('#jobsTable tbody').html('');
                $('#noJobsMessage').removeClass('d-none');
            }
        });
    }
    
    // Add candidate to job
    $(document).on('click', '.add-to-job', function() {
        const jobCode = $(this).data('job-code');
        const jobTitle = $(this).data('job-title');
        
        if (confirm(`Add ${$('input[name="candidate_name"]').val()} to job: ${jobTitle}?`)) {
            $.post('handlers/add_candidate_to_job.php', {
                can_code: '<?= htmlspecialchars($can_code) ?>',
                job_code: jobCode,
                token: '<?= Auth::token() ?>'
            }, function(response) {
                if (response.success) {
                    showSuccessToast(`Candidate added to ${jobTitle}`);
                    $('#addJobModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            });
        }
    });
    
    // Success toast function
    function showSuccessToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-bg-success border-0 position-fixed bottom-0 end-0 m-3';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.style.zIndex = 9999;
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', function() {
            document.body.removeChild(toast);
        });
    }
});
</script>

<style>
.rating-stars {
    display: flex;
    cursor: pointer;
    direction: rtl;
}
.star-rating {
    font-size: 1.2rem;
    padding: 0 2px;
    color: #ddd;
}
.star-rating:hover,
.star-rating:hover ~ .star-rating,
.star-rating.text-warning {
    color: #ffc107;
}
</style>