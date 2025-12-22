<?php
// <div id="parseNotice" class="alert alert-info d-none">
//     Candidate details were filled from resume.
//     Please review before saving.
// </div>

if (!Auth::check()) throw new Exception('Unauthorized');

$db = Database::getInstance();
$conn = $db->getConnection();

// Get available statuses
$statuses = [];
$result = $conn->query("SELECT status_value, status_label FROM candidate_statuses WHERE is_active = 1 ORDER BY status_order");
while ($row = $result->fetch_assoc()) {
    $statuses[] = $row;
}

// Get work auth options
$work_auth_options = [];
$result = $conn->query("SELECT DISTINCT status FROM work_authorization");
while ($row = $result->fetch_assoc()) {
    $work_auth_options[] = $row['status'];
}

$conn->close();
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Candidates /</span> Add New Candidate
    </h4>

    <form id="formCandidateCreate" method="POST" action="handlers/candidate_save_handler.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="created_by" value="<?= Auth::user()['user_code'] ?>">

        <div class="row">
            <div class="col-md-12">
                
                <!-- Basic Information -->
                <div class="card mb-4">
                    <h5 class="card-header">Basic Information</h5>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Candidate Name <span class="text-danger">*</span></label>
                                <input type="text" name="candidate_name" class="form-control required" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Email Address<span class="text-danger">*</span></label>
                                <input type="email" name="email_id" class="form-control required" required>
                                <div class="form-text">Will check for duplicates</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Primary Contact Number</label>
                                <input type="tel" name="contact_details" class="form-control">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Alternate Number</label>
                                <input type="tel" name="alternate_contact_details" class="form-control">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">LinkedIn Profile <span class="text-danger">*</span></label>
                                <input type="url" name="linkedin" class="form-control required" placeholder="https://linkedin.com/in/..." required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Alternate Email</label>
                                <input type="email" name="alternate_email_id" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professional Details -->
                <div class="card mb-4">
                    <h5 class="card-header">Professional Details</h5>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Current Position</label>
                                <input type="text" name="current_position" class="form-control">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Current Employer</label>
                                <input type="text" name="current_employer" class="form-control">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Total Experience (Years)</label>
                                <input type="number" name="experience" class="form-control" min="0" step="0.5">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Notice Period (Days)</label>
                                <input type="number" name="notice_period" class="form-control" min="0">
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Professional Summary</label>
                                <textarea name="professional_summary" class="form-control" rows="3" placeholder="Brief overview of professional background..."></textarea>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Key Skills</label>
                                <input type="text" name="skill_set" class="form-control" placeholder="PHP, JavaScript, React, Node.js..." data-role="tagsinput">
                                <div class="form-text">Comma-separated or press Enter after each skill</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location & Work Auth -->
                <div class="card mb-4">
                    <h5 class="card-header">Location & Work Authorization</h5>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Current Location</label>
                                <input type="text" name="current_location" class="form-control" placeholder="City, Country">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Preferred Location</label>
                                <input type="text" name="preferred_location" class="form-control" placeholder="City, Country">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Work Authorization <span class="text-danger">*</span></label>
                                <select name="work_auth_status" class="form-select required" required>
                                    <option value="">Select...</option>
                                    <?php foreach ($work_auth_options as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Willing to Relocate?</label>
                                <select name="willing_to_relocate" class="form-select">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compensation -->
                <div class="card mb-4">
                    <h5 class="card-header">Compensation</h5>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Current Salary (€)</label>
                                <input type="number" name="current_salary" class="form-control" min="0" step="1000">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Expected Salary (€)</label>
                                <input type="number" name="expected_salary" class="form-control" min="0" step="1000">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Current Daily Rate (€)</label>
                                <input type="number" name="current_daily_rate" class="form-control" min="0" step="50">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Expected Daily Rate (€)</label>
                                <input type="number" name="expected_daily_rate" class="form-control" min="0" step="50">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status & Classification -->
                <div class="card mb-4">
                    <h5 class="card-header">Status & Classification</h5>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Candidate Status</label>
                                <select name="candidate_status" class="form-select">
                                    <?php foreach ($statuses as $status): ?>
                                    <option value="<?= $status['status_value'] ?>" <?= $status['status_value'] === 'New' ? 'selected' : '' ?>>
                                        <?= $status['status_label'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Lead Type</label>
                                <select name="lead_type" class="form-select">
                                    <option value="Cold">Cold</option>
                                    <option value="Warm">Warm</option>
                                    <option value="Hot">Hot</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Lead Type Role</label>
                                <select name="lead_type_role" class="form-select">
                                    <option value="1">Recruitment</option>
                                    <option value="0">Payroll</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Working Status</label>
                                <select name="current_working_status" class="form-select">
                                    <option value="Freelance(Self)">Freelance (Self)</option>
                                    <option value="Freelance(Company)">Freelance (Company)</option>
                                    <option value="Employee">Employee</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Source</label>
                                <select name="source" class="form-select">
                                    <option value="Direct">Direct</option>
                                    <option value="LinkedIn">LinkedIn</option>
                                    <option value="Referral">Referral</option>
                                    <option value="Job Board">Job Board</option>
                                    <option value="Agency">Agency</option>
                                    <option value="Website">Website</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Can Join From</label>
                                <input type="date" name="can_join" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Follow-up -->
                <div class="card mb-4">
                    <h5 class="card-header">Follow-up & Notes</h5>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Follow-up Status</label>
                                <select name="follow_up" class="form-select">
                                    <option value="Not Done" selected>Not Done</option>
                                    <option value="Done">Done</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Follow-up Date</label>
                                <input type="date" name="follow_up_date" class="form-control">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Face to Face Date</label>
                                <input type="date" name="face_to_face" class="form-control">
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label">Additional Notes</label>
                                <textarea name="extra_details" class="form-control" rows="3" placeholder="Any additional information..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documents -->
                <div class="card mb-4">
                    <h5 class="card-header">Document Upload</h5>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Candidate CV</label>
                                <input type="file" name="candidate_cv" class="form-control" accept=".pdf,.doc,.docx">
                                <div class="form-text">PDF, DOC, DOCX (Max 5MB)</div>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Consultancy CV</label>
                                <input type="file" name="consultancy_cv" class="form-control" accept=".pdf,.doc,.docx">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Consent Form</label>
                                <input type="file" name="consent" class="form-control" accept=".pdf">
                            </div>
                        </div>
                        
                        <div class="progress mt-3 d-none" id="uploadProgress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">
                        Candidate Resume
                        <i class="bx bx-info-circle"
                        data-bs-toggle="tooltip"
                        title="Upload resume to automatically fill candidate details. You can review and edit everything before saving.">
                        </i>
                    </label>

                    <input type="file"
                        class="form-control"
                        id="resumeFile"
                        accept=".pdf,.doc,.docx">

                    <div class="form-text">
                        Supported formats: PDF, DOC, DOCX (Max 5MB)
                    </div>
                </div>

                <div class="col-12 d-flex align-items-center gap-2 mt-2">
                    <button type="button"
                            id="btnAutoFill"
                            class="btn btn-outline-primary btn-sm">
                        Auto-Fill Candidate Details
                    </button>

                    <span id="parseStatus" class="text-muted small d-none"></span>
                </div>

                <!-- Form Actions -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary me-2" id="submitBtn">
                            <i class="bx bx-save"></i> Save Candidate
                        </button>
                        <button type="reset" class="btn btn-outline-secondary me-2">
                            <i class="bx bx-reset"></i> Reset
                        </button>
                        <a href="?action=list" class="btn btn-outline-secondary">
                            <i class="bx bx-x"></i> Cancel
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Form submission with progress
    $('#formCandidateCreate').on('submit', function(e) {
        e.preventDefault();
        
        // Basic validation
        let valid = true;
        $('.required').each(function() {
            if (!this.value.trim()) {
                $(this).addClass('is-invalid');
                valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!valid) {
            alert('Please fill all required fields');
            return false;
        }
        
        // Check for duplicate email
        const email = $('[name="email_id"]').val();
        $.get('api/duplicate_check.php', { email: email }, function(response) {
            if (response.exists) {
                if (!confirm('A candidate with this email already exists. Continue anyway?')) {
                    return;
                }
            }
            
            submitForm();
        });
    });
    
    function submitForm() {
        const formData = new FormData(document.getElementById('formCandidateCreate'));
        
        $('#uploadProgress').removeClass('d-none');
        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
        
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
                    alert('Candidate created successfully!');
                    window.location.href = '?action=list';
                } else {
                    alert('Error: ' + response.message);
                    $('#submitBtn').prop('disabled', false).html('<i class="bx bx-save"></i> Save Candidate');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $('#submitBtn').prop('disabled', false).html('<i class="bx bx-save"></i> Save Candidate');
            }
        });
    }
    
    // Auto-save draft
    let saveTimer;
    $('input, textarea, select').on('change', function() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(function() {
            localStorage.setItem('candidateDraft', $('#formCandidateCreate').serialize());
            console.log('Draft saved');
        }, 1000);
    });
    
    // Load draft
    const draft = localStorage.getItem('candidateDraft');
    if (draft && confirm('Load previously saved draft?')) {
        const params = new URLSearchParams(draft);
        params.forEach((value, key) => {
            $('[name="' + key + '"]').val(value);
        });
    }
});
$('#btnAutoFill').on('click', function () {

    const fileInput = $('#resumeFile')[0];
    if (!fileInput.files.length) {
        alert('Please upload a resume first.');
        return;
    }

    const formData = new FormData();
    formData.append('resume', fileInput.files[0]);
    formData.append('token', '<?php echo Auth::token(); ?>');

    $('#parseStatus')
        .removeClass('d-none')
        .text('Reading resume and extracting details…');

    $.ajax({
        url: 'handlers/resume_parse_handler.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (res) {
            if (!res.success) {
                $('#parseStatus').text(res.message);
                return;
            }

            fillCandidateForm(res.data);
            $('#parseNotice').removeClass('d-none');
            $('#parseStatus').text('Details added from resume.');
        },
        error: function () {
            $('#parseStatus').text('Unable to read resume. Please fill manually.');
        }
    });
});

function fillCandidateForm(data) {
    if (data.candidate_name)
        $('input[name="candidate_name"]').val(data.candidate_name);

    if (data.email)
        $('input[name="email_id"]').val(data.email);

    if (data.phone)
        $('input[name="contact_details"]').val(data.phone);

    if (data.linkedin)
        $('input[name="linkedin"]').val(data.linkedin);

    // Skills (example – depends on your UI)
    if (data.skills && Array.isArray(data.skills)) {
        // Populate skill chips / multiselect later
        console.log('Parsed skills:', data.skills);
    }
}

</script>
