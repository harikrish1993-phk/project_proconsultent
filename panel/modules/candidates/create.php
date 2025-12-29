<?php
// ============================================================================
// BOOTSTRAP
// ============================================================================
require_once __DIR__ . '/../_common.php';

$pageTitle = 'Add New Candidate';

// // Display breadcrumb
// echo renderBreadcrumb($breadcrumbs);

$db = Database::getInstance();
$conn = $db->getConnection();

// Get available data for dropdowns
function getDropdownOptions($conn, $query, $valueField, $labelField) {
    $options = [];
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $options[] = [
            'value' => $row[$valueField],
            'label' => $row[$labelField]
        ];
    }
    return $options;
}

// Get data for dropdowns
$statuses = getDropdownOptions($conn, "SELECT status_value, status_label FROM candidate_statuses WHERE is_active = 1 ORDER BY status_order", 'status_value', 'status_label');
$work_auth_options = getDropdownOptions($conn, "SELECT id, status FROM work_authorization WHERE is_active = 1", 'status', 'status');
$languages = getDropdownOptions($conn, "SELECT id, language_name FROM languages WHERE is_active = 1 ORDER BY language_name", 'language_name', 'language_name');
$skills = getDropdownOptions($conn, "SELECT id, skill_name FROM skills WHERE is_active = 1 ORDER BY skill_name", 'skill_name', 'skill_name');
$recruiters = getDropdownOptions($conn, "SELECT user_code, full_name FROM users WHERE role IN ('recruiter', 'admin') AND is_active = 1", 'user_code', 'full_name');
$certifications = getDropdownOptions($conn, "SELECT id, cert_name FROM certifications WHERE is_active = 1 ORDER BY cert_name", 'cert_name', 'cert_name');

$conn->close();
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Candidates /</span> Add New Candidate
    </h4>

    <div class="card">
        <div class="card-body">
            <form id="formCandidateCreate" method="POST" action="handlers/candidate_save_handler.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="created_by" value="<?= Auth::user()['user_code'] ?>">
                <input type="hidden" name="can_code" value="<?= uniqid('CAN_') ?>">

                <!-- Tabs Navigation -->
                <ul class="nav nav-pills mb-4 flex-wrap" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#tab-basic" aria-selected="true">
                            <i class="bx bx-user me-1"></i> Basic Information
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-professional" aria-selected="false">
                            <i class="bx bx-briefcase me-1"></i> Professional Details
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-location" aria-selected="false">
                            <i class="bx bx-map me-1"></i> Location & Availability
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-compensation" aria-selected="false">
                            <i class="bx bx-euro me-1"></i> Compensation
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-classification" aria-selected="false">
                            <i class="bx bx-category me-1"></i> Classification
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-documents" aria-selected="false">
                            <i class="bx bx-file me-1"></i> Documents
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-additional" aria-selected="false">
                            <i class="bx bx-plus-circle me-1"></i> Additional Info
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- TAB 1: Basic Information -->
                    <div class="tab-pane fade show active" id="tab-basic" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="alert alert-info d-flex align-items-center mb-4">
                                    <i class="bx bx-info-circle fs-4 me-2"></i>
                                    <div>
                                        <strong>Resume Parsing Assistant</strong><br>
                                        Upload a resume to auto-fill candidate details. You can review and edit everything before saving.
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Candidate Resume</label>
                                    <input type="file" class="form-control" id="resumeFile" accept=".pdf,.doc,.docx">
                                    <div class="form-text">Supported formats: PDF, DOC, DOCX (Max 5MB)</div>
                                </div>
                            </div>

                            <div class="col-md-6 d-flex align-items-end">
                                <div class="mb-3">
                                    <button type="button" id="btnAutoFill" class="btn btn-primary">
                                        <i class="bx bx-magic me-1"></i> Extract Details
                                    </button>
                                    <div class="mt-2">
                                        <span id="parseStatus" class="text-muted small d-none"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Candidate Name <span class="text-danger">*</span></label>
                                    <input type="text" name="candidate_name" class="form-control required" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email_id" class="form-control required" required>
                                    <div class="form-text">Will check for duplicates</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Primary Contact Number</label>
                                    <input type="tel" name="contact_details" class="form-control">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Alternate Contact Number</label>
                                    <input type="tel" name="alternate_contact_details" class="form-control">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">LinkedIn Profile URL</label>
                                    <input type="url" name="linkedin" class="form-control" placeholder="https://linkedin.com/in/...">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Alternate Email</label>
                                    <input type="email" name="alternate_email_id" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: Professional Details -->
                    <div class="tab-pane fade" id="tab-professional" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Current Position</label>
                                    <input type="text" name="current_position" class="form-control">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Current Company/Employer</label>
                                    <input type="text" name="current_employer" class="form-control">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Total Experience (Years)</label>
                                    <input type="number" name="experience" class="form-control" min="0" step="0.5">
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Professional Summary</label>
                                    <textarea name="professional_summary" class="form-control" rows="3" placeholder="Brief overview of professional background..."></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Key Skills <span class="text-danger">*</span></label>
                                    <select name="skill_set[]" class="form-select select2-multiple" multiple="multiple" required>
                                        <?php foreach ($skills as $skill): ?>
                                        <option value="<?= htmlspecialchars($skill['value']) ?>"><?= htmlspecialchars($skill['label']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Select multiple skills relevant to the candidate</div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Certifications</label>
                                    <select name="certifications[]" class="form-select select2-multiple" multiple="multiple">
                                        <?php foreach ($certifications as $cert): ?>
                                        <option value="<?= htmlspecialchars($cert['value']) ?>"><?= htmlspecialchars($cert['label']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Add any professional certifications</div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Languages</label>
                                    <select name="languages[]" class="form-select select2-multiple" multiple="multiple">
                                        <?php foreach ($languages as $lang): ?>
                                        <option value="<?= htmlspecialchars($lang['value']) ?>"><?= htmlspecialchars($lang['label']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Languages the candidate can speak</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: Location & Availability (location expectiong the dropdown (belgoium /nedharlands/france/luxmberg/india)) --> 
                    <div class="tab-pane fade" id="tab-location" role="tabpanel">    
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Current Location <span class="text-danger">*</span></label>
                                    <input type="text" name="current_location" class="form-control required" required placeholder="Country">
                                </div>
                            </div>
                            
                        
                                <!-- Work Authorization -->
                                <div class="col-md-6">
                                    <label for="work_authorization_status" class="form-label">
                                        Work Authorization <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" 
                                            id="work_authorization_status" 
                                            name="work_authorization_status"
                                            required
                                            data-rules="required">
                                        <option value="">Select status...</option>
                                        <option value="eu_citizen">EU Citizen/PR</option>
                                        <option value="work_permit">Valid Work Permit</option>
                                        <option value="requires_sponsorship">Requires Sponsorship</option>
                                    </select>
                                </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Notice Period (Days)</label>
                                    <input type="number" name="notice_period" class="form-control" min="0">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Earliest Start Date</label>
                                    <input type="date" name="can_join" class="form-control">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Working Status</label>
                                    <select name="current_working_status" class="form-select">
                                        <option value="">Select status</option>
                                        <option value="Freelance(Self)">Freelance (Self)</option>
                                        <option value="Freelance(Company)">Freelance (Company)</option>
                                        <option value="Employee">Employee</option>
                                        <option value="Unemployed">Unemployed</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Willing to Relocate?</label>
                                    <select name="willing_to_relocate" class="form-select">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Availability</label>
                                    <select name="availability" class="form-select">
                                        <option value="immediate" selected>Immediate</option>
                                        <option value="notice_period">Serving Notice Period</option>
                                        <option value="contract_ending">Contract Ending</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: Compensation -->
                    <div class="tab-pane fade" id="tab-compensation" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label d-block">Compensation Type</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="compensation_type" id="salaryType" value="salary" checked>
                                        <label class="form-check-label" for="salaryType">Annual Salary</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="compensation_type" id="rateType" value="rate">
                                        <label class="form-check-label" for="rateType">Daily Rate</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 compensation-field salary-field">
                                <div class="mb-3">
                                    <label class="form-label">Current Annual Salary (€)</label>
                                    <input type="number" name="current_salary" class="form-control" min="0" step="1000" placeholder="e.g., 50000">
                                </div>
                            </div>

                            <div class="col-md-6 compensation-field salary-field">
                                <div class="mb-3">
                                    <label class="form-label">Expected Annual Salary (€)</label>
                                    <input type="number" name="expected_salary" class="form-control" min="0" step="1000" placeholder="e.g., 60000">
                                </div>
                            </div>

                            <div class="col-md-6 compensation-field rate-field d-none">
                                <div class="mb-3">
                                    <label class="form-label">Current Daily Rate (€)</label>
                                    <input type="number" name="current_daily_rate" class="form-control" min="0" step="50" placeholder="e.g., 500">
                                </div>
                            </div>

                            <div class="col-md-6 compensation-field rate-field d-none">
                                <div class="mb-3">
                                    <label class="form-label">Expected Daily Rate (€)</label>
                                    <input type="number" name="expected_daily_rate" class="form-control" min="0" step="50" placeholder="e.g., 600">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: Classification -->
                    <div class="tab-pane fade" id="tab-classification" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Candidate Status <span class="text-danger">*</span></label>
                                    <select name="candidate_status" class="form-select required" required>
                                        <?php foreach ($statuses as $status): ?>
                                        <option value="<?= $status['value'] ?>" <?= $status['value'] === 'New' ? 'selected' : '' ?>>
                                            <?= $status['label'] ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lead Type <span class="text-danger">*</span></label>
                                    <select name="lead_type" class="form-select required" required>
                                        <option value="Cold" selected>Cold</option>
                                        <option value="Warm">Warm</option>
                                        <option value="Hot">Hot</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lead Role Type <span class="text-danger">*</span></label>
                                    <select name="lead_type_role" class="form-select required" required>
                                        <option value="1" selected>Recruitment</option>
                                        <option value="0">Payroll</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Source <span class="text-danger">*</span></label>
                                    <select name="source" class="form-select required" required>
                                        <option value="LinkedIn" selected>LinkedIn</option>
                                        <option value="Direct">Direct</option>
                                        <option value="Referral">Referral</option>
                                        <option value="Job Board">Job Board</option>
                                        <option value="Agency">Agency</option>
                                        <option value="Website">Website</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Candidate Rating</label>
                                    <div class="rating-stars">
                                        <input type="radio" id="star5" name="rating" value="5" class="d-none">
                                        <label for="star5" class="star-rating"><i class="bx bxs-star"></i></label>
                                        <input type="radio" id="star4" name="rating" value="4" class="d-none">
                                        <label for="star4" class="star-rating"><i class="bx bxs-star"></i></label>
                                        <input type="radio" id="star3" name="rating" value="3" class="d-none">
                                        <label for="star3" class="star-rating"><i class="bx bxs-star"></i></label>
                                        <input type="radio" id="star2" name="rating" value="2" class="d-none">
                                        <label for="star2" class="star-rating"><i class="bx bxs-star"></i></label>
                                        <input type="radio" id="star1" name="rating" value="1" class="d-none">
                                        <label for="star1" class="star-rating"><i class="bx bxs-star"></i></label>
                                    </div>
                                    <input type="hidden" name="candidate_rating" id="candidate_rating" value="3">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Role Addressed</label>
                                    <input type="text" name="role_addressed" class="form-control" placeholder="e.g., Senior Developer">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 6: Documents -->
                    <div class="tab-pane fade" id="tab-documents" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Candidate CV</label>
                                    <input type="file" name="candidate_cv" class="form-control" accept=".pdf,.doc,.docx">
                                    <div class="form-text">PDF, DOC, DOCX (Max 5MB)</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Consultancy CV</label>
                                    <input type="file" name="consultancy_cv" class="form-control" accept=".pdf,.doc,.docx">
                                    <div class="form-text">Formatted CV for client sharing</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Consent Form</label>
                                    <input type="file" name="consent" class="form-control" accept=".pdf">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Additional Documents</label>
                                    <input type="file" name="additional_docs[]" class="form-control" multiple accept=".pdf,.doc,.docx,.jpg,.png">
                                </div>
                            </div>
                        </div>

                        <div class="progress mt-3 d-none" id="uploadProgress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- TAB 7: Additional Info -->
                    <div class="tab-pane fade" id="tab-additional" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Follow-up Required</label>
                                    <select name="follow_up" class="form-select">
                                        <option value="Not Done" selected>Not Done</option>
                                        <option value="Done">Done</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Follow-up Date</label>
                                    <input type="date" name="follow_up_date" class="form-control">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Face to Face Meeting Date</label>
                                    <input type="date" name="face_to_face" class="form-control">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Assign To Recruiter</label>
                                    <select name="assigned_to" class="form-select">
                                        <?php foreach ($recruiters as $recruiter): ?>
                                        <option value="<?= htmlspecialchars($recruiter['value']) ?>" <?= $recruiter['value'] === Auth::user()['user_code'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($recruiter['label']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Default: You (<?= htmlspecialchars(Auth::user()['full_name']) ?>)</div>
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Additional Notes</label>
                                    <textarea name="extra_details" class="form-control" rows="4" placeholder="Any additional information..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary prev-tab d-none">
                        <i class="bx bx-left-arrow-alt me-1"></i> Previous
                    </button>
                    <button type="button" class="btn btn-primary next-tab ms-auto">
                        Next <i class="bx bx-right-arrow-alt ms-1"></i>
                    </button>
                    <button type="submit" class="btn btn-success submit-form d-none">
                        <i class="bx bx-save me-1"></i> Save Candidate
                    </button>
                    <button type="reset" class="btn btn-outline-secondary ms-2">
                        <i class="bx bx-reset me-1"></i> Reset Form
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script>
$(document).ready(function() {
    // Initialize Select2 for multi-selects
    $('.select2-multiple').select2({
        placeholder: "Select options...",
        width: '100%'
    });
    
    // Tab navigation
    $('.next-tab').on('click', function() {
        const currentTab = $('.tab-pane.active');
        const nextTab = currentTab.next('.tab-pane');
        
        if (nextTab.length) {
            // Validate current tab fields
            let valid = true;
            const requiredFields = currentTab.find('.required, [required]');
            
            requiredFields.each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('is-invalid');
                    valid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            
            if (!valid) {
                alert('Please fill all required fields in this section before proceeding.');
                return;
            }
            
            // Show/hide navigation buttons
            currentTab.removeClass('show active').addClass('fade');
            nextTab.addClass('show active').removeClass('fade');
            $('.nav-link.active').removeClass('active');
            $('.nav-link').eq($('.tab-pane').index(nextTab)).addClass('active');
            
            if (nextTab.is(':last-child')) {
                $('.next-tab').addClass('d-none');
                $('.submit-form').removeClass('d-none');
            }
            
            if (!currentTab.is(':first-child')) {
                $('.prev-tab').removeClass('d-none');
            }
        }
    });
    
    $('.prev-tab').on('click', function() {
        const currentTab = $('.tab-pane.active');
        const prevTab = currentTab.prev('.tab-pane');
        
        if (prevTab.length) {
            currentTab.removeClass('show active').addClass('fade');
            prevTab.addClass('show active').removeClass('fade');
            $('.nav-link.active').removeClass('active');
            $('.nav-link').eq($('.tab-pane').index(prevTab)).addClass('active');
            
            if (currentTab.is(':last-child')) {
                $('.submit-form').addClass('d-none');
                $('.next-tab').removeClass('d-none');
            }
            
            if (prevTab.is(':first-child')) {
                $('.prev-tab').addClass('d-none');
            }
        }
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
    
    // Form submission with validation
    $('.submit-form').on('click', function() {
        // Final validation
        let valid = true;
        $('.required, [required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!valid) {
            alert('Please fill all required fields before saving.');
            return;
        }
        
        // Check for duplicate email
        const email = $('[name="email_id"]').val();
        $.get('api/duplicate_check.php', { email: email }, function(response) {
            if (response.exists) {
                if (!confirm('A candidate with this email already exists. Continue anyway?')) {
                    return;
                }
            }
            
            $('#formCandidateCreate').submit();
        });
    });
    
    // Handle actual form submission
    $('#formCandidateCreate').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $('.submit-form').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Saving...');
        $('#uploadProgress').removeClass('d-none');
        
        $.ajax({
            url: 'handlers/candidate_save_handler.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        $('#uploadProgress .progress-bar').css('width', percent + '%').text(percent + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    showSuccessToast('Candidate created successfully!');
                    setTimeout(function() {
                        window.location.href = 'candidates.php?action=view&can_code=' + response.can_code;
                    }, 1500);
                } else {
                    alert('Error: ' + response.message);
                    $('.submit-form').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Candidate');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $('.submit-form').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Candidate');
            }
        });
    });
    
    // Auto-save draft
    let saveTimer;
    $('input, textarea, select').not('[type="file"]').on('change input', function() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(function() {
            const formData = $('#formCandidateCreate').serialize();
            localStorage.setItem('candidateDraft', formData);
            console.log('Draft saved');
        }, 1000);
    });
    
    // Load draft
    const draft = localStorage.getItem('candidateDraft');
    if (draft && confirm('Load previously saved draft?')) {
        const params = new URLSearchParams(draft);
        params.forEach((value, key) => {
            const element = $('[name="' + key + '"]');
            if (element.length) {
                if (element.is('select[multiple]')) {
                    element.val(value.split(',')).trigger('change');
                } else if (element.is(':radio')) {
                    $('input[name="' + key + '"][value="' + value + '"]').prop('checked', true);
                } else {
                    element.val(value);
                }
            }
        });
    }
    
    // AI Resume Parsing
    $('#btnAutoFill').on('click', function() {
        const fileInput = $('#resumeFile')[0];
        if (!fileInput.files.length) {
            alert('Please upload a resume first.');
            return;
        }

        const file = fileInput.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit.');
            return;
        }

        const allowedTypes = ['application/pdf', 'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!allowedTypes.includes(file.type)) {
            alert('Unsupported file format. Please upload PDF or Word document.');
            return;
        }

        const formData = new FormData();
        formData.append('resume', file);
        formData.append('token', '<?= Auth::token() ?>');

        $('#parseStatus')
            .removeClass('d-none')
            .text('Analyzing resume and extracting details...');

        $('#btnAutoFill').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Processing');

        $.ajax({
            url: 'handlers/resume_parse_handler.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                $('#btnAutoFill').prop('disabled', false).html('<i class="bx bx-magic me-1"></i> Extract Details');
                
                if (!res.success) {
                    $('#parseStatus').text(res.message || 'Unable to extract details from resume. Please fill manually.');
                    return;
                }

                fillCandidateForm(res.data);
                $('#parseStatus').text('✅ Details successfully extracted. Please review before saving.');
                showSuccessToast('Resume parsed successfully! Please review the extracted details.');
            },
            error: function(xhr) {
                $('#btnAutoFill').prop('disabled', false).html('<i class="bx bx-magic me-1"></i> Extract Details');
                $('#parseStatus').text('❌ Unable to process resume. Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    function fillCandidateForm(data) {
        // Basic Information
        if (data.name) $('input[name="candidate_name"]').val(data.name);
        if (data.email) $('input[name="email_id"]').val(data.email);
        if (data.phone) $('input[name="contact_details"]').val(data.phone);
        if (data.linkedin) $('input[name="linkedin"]').val(data.linkedin);
        if (data.summary) $('textarea[name="professional_summary"]').val(data.summary);

        // Professional Details
        if (data.position) $('input[name="current_position"]').val(data.position);
        if (data.employer) $('input[name="current_employer"]').val(data.employer);
        if (data.experience) $('input[name="experience"]').val(data.experience);

        // Skills
        if (data.skills && Array.isArray(data.skills)) {
            const skillSelect = $('select[name="skill_set[]"]');
            // Clear current selection
            skillSelect.val(null).trigger('change');
            
            // Add and select skills
            data.skills.forEach(skill => {
                const optionExists = skillSelect.find(`option[value="${skill}"]`).length > 0;
                if (!optionExists) {
                    const newOption = new Option(skill, skill, true, true);
                    skillSelect.append(newOption);
                }
            });
            
            // Set the values and trigger change
            skillSelect.val(data.skills).trigger('change');
        }

        // Location
        if (data.location) $('input[name="current_location"]').val(data.location);

        // Education/Certifications
        if (data.certifications && Array.isArray(data.certifications)) {
            const certSelect = $('select[name="certifications[]"]');
            certSelect.val(null).trigger('change');
            
            data.certifications.forEach(cert => {
                const optionExists = certSelect.find(`option[value="${cert}"]`).length > 0;
                if (!optionExists) {
                    const newOption = new Option(cert, cert, true, true);
                    certSelect.append(newOption);
                }
            });
            
            certSelect.val(data.certifications).trigger('change');
        }

        // Languages
        if (data.languages && Array.isArray(data.languages)) {
            const langSelect = $('select[name="languages[]"]');
            langSelect.val(null).trigger('change');
            
            data.languages.forEach(lang => {
                const optionExists = langSelect.find(`option[value="${lang}"]`).length > 0;
                if (!optionExists) {
                    const newOption = new Option(lang, lang, true, true);
                    langSelect.append(newOption);
                }
            });
            
            langSelect.val(data.languages).trigger('change');
        }
    }

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

    // Initialize default rating
    const defaultRating = $('#candidate_rating').val();
    $('.star-rating').slice(0, defaultRating).addClass('text-warning');
    
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
        
        // Auto remove after hiding
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
}
.star-rating {
    font-size: 1.5rem;
    padding: 0 2px;
    color: #ddd;
}
.star-rating:hover,
.star-rating:hover ~ .star-rating,
.star-rating.text-warning {
    color: #ffc107;
}
</style>
<?php
$pageContent = ob_get_clean();
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/includes/sidebar.php';
echo $pageContent;
require_once ROOT_PATH . '/panel/includes/footer.php';
?>