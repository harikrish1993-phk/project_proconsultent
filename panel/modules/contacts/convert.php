<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';

$db = Database::getInstance();
$conn = $db->getConnection();
$userId = Auth::userId();

// Get contact ID
$contactId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$contactId) {
    header('Location: index.php');
    exit;
}

// Fetch contact details
$contactQuery = "SELECT c.* FROM contacts c WHERE c.contact_id = ? AND c.is_archived = 0 AND c.status != 'converted'";
$stmt = $conn->prepare($contactQuery);
$stmt->bind_param("i", $contactId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">Contact not found or already converted.</div>';
    require_once '../../includes/footer.php';
    exit;
}

$contact = $result->fetch_assoc();

// Parse skills
$skills = json_decode($contact['skills'], true);
if (!is_array($skills)) $skills = [];
$skillsString = implode(',', $skills);
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">
                    <a href="index.php" class="text-muted">Contacts</a> /
                    <a href="view.php?id=<?php echo $contactId; ?>" class="text-muted">
                        <?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?>
                    </a> /
                </span> 
                Convert to Candidate
            </h4>
        </div>
    </div>

    <div class="alert alert-info mb-4" role="alert">
        <h6 class="alert-heading">
            <i class="bx bx-info-circle"></i> Converting Contact to Candidate
        </h6>
        <p class="mb-0">
            This will create a new candidate record with the information below. The contact record will be marked as converted and linked to the new candidate.
        </p>
    </div>

    <form id="convertForm" method="POST" action="handlers/convert_handler.php">
        <input type="hidden" name="contact_id" value="<?php echo $contactId; ?>">
        
        <div class="row">
            <!-- Main Form -->
            <div class="col-xl-8 col-lg-7 mb-4">
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Candidate Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" 
                                       value="<?php echo htmlspecialchars($contact['first_name']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" name="middle_name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" 
                                       value="<?php echo htmlspecialchars($contact['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($contact['email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($contact['phone']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">LinkedIn</label>
                                <input type="url" class="form-control" name="linkedin_url" 
                                       value="<?php echo htmlspecialchars($contact['linkedin_url'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location" 
                                       value="<?php echo htmlspecialchars($contact['current_location'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professional Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Professional Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Current Company</label>
                                <input type="text" class="form-control" name="current_company" 
                                       value="<?php echo htmlspecialchars($contact['current_company'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Current Title</label>
                                <input type="text" class="form-control" name="current_title" 
                                       value="<?php echo htmlspecialchars($contact['current_title'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Total Experience (Years)</label>
                                <input type="number" step="0.5" class="form-control" name="experience_years" 
                                       value="<?php echo $contact['experience_years'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notice Period</label>
                                <input type="text" class="form-control" name="notice_period" 
                                       value="<?php echo htmlspecialchars($contact['notice_period'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Current Salary (Annual)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="current_salary" 
                                           value="<?php echo $contact['current_salary'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expected Salary (Annual)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="expected_salary" 
                                           value="<?php echo $contact['expected_salary'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Primary Skills <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="skills" name="skills" 
                                   value="<?php echo htmlspecialchars($skillsString); ?>" 
                                   data-role="tagsinput" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Summary/Bio</label>
                            <textarea class="form-control" name="summary" rows="4" 
                                      placeholder="Brief professional summary..."><?php echo htmlspecialchars($contact['interested_roles'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Additional Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Visa Status</label>
                            <select class="form-select" name="visa_status">
                                <option value="">Select...</option>
                                <option value="US Citizen">US Citizen</option>
                                <option value="Green Card">Green Card</option>
                                <option value="H1B">H1B</option>
                                <option value="OPT">OPT</option>
                                <option value="CPT">CPT</option>
                                <option value="EAD">EAD</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Preferred Work Type</label>
                                <select class="form-select" name="work_type">
                                    <option value="">Select...</option>
                                    <option value="Full-time">Full-time</option>
                                    <option value="Contract">Contract</option>
                                    <option value="Contract-to-Hire">Contract-to-Hire</option>
                                    <option value="Part-time">Part-time</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Work Authorization</label>
                                <select class="form-select" name="work_authorization">
                                    <option value="">Select...</option>
                                    <option value="Authorized">Authorized to work in US</option>
                                    <option value="Require Sponsorship">Require Sponsorship</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Willing to Relocate?</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="willing_to_relocate" value="1" id="relocateYes">
                                <label class="form-check-label" for="relocateYes">Yes</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="willing_to_relocate" value="0" id="relocateNo" checked>
                                <label class="form-check-label" for="relocateNo">No</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Preferred Locations</label>
                            <input type="text" class="form-control" name="preferred_locations" 
                                   value="<?php echo htmlspecialchars($contact['preferred_locations'] ?? ''); ?>"
                                   placeholder="Remote, New York, Austin (comma separated)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-xl-4 col-lg-5">
                <!-- Candidate Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Candidate Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="candidate_status">
                                <option value="Active">Active</option>
                                <option value="Available">Available</option>
                                <option value="Placed">Placed</option>
                                <option value="On Hold">On Hold</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Availability Date</label>
                            <input type="date" class="form-control" name="availability_date">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Source</label>
                            <input type="text" class="form-control" name="source" 
                                   value="Converted from Contact" readonly>
                        </div>
                    </div>
                </div>

                <!-- Conversion Note -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Conversion Note</h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" name="conversion_note" rows="4" 
                                  placeholder="Add any notes about this conversion..."></textarea>
                        <small class="text-muted">This note will be added to both contact and candidate records.</small>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-success w-100 mb-2" id="convertBtn">
                            <i class="bx bx-transfer me-1"></i> Convert to Candidate
                        </button>
                        <a href="view.php?id=<?php echo $contactId; ?>" class="btn btn-label-secondary w-100">
                            <i class="bx bx-x me-1"></i> Cancel
                        </a>
                    </div>
                </div>

                <!-- Contact Info Summary -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Contact History</h5>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">
                            <strong>Original Contact:</strong><br>
                            Created: <?php echo date('M d, Y', strtotime($contact['created_at'])); ?><br>
                            Status: <?php echo ucfirst($contact['status']); ?><br>
                            Source: <?php echo ucfirst(str_replace('_', ' ', $contact['source'])); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-tagsinput@0.8.0/dist/bootstrap-tagsinput.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap-tagsinput@0.8.0/dist/bootstrap-tagsinput.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize tags input
    $('#skills').tagsinput({
        trimValue: true,
        confirmKeys: [13, 44],
        cancelConfirmKeysOnEmpty: true
    });

    // Form submission
    $('#convertForm').on('submit', function(e) {
        e.preventDefault();
        
        const convertBtn = $('#convertBtn');
        convertBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Converting...');
        
        const formData = new FormData(this);
        
        // Convert skills to JSON
        const skills = $('#skills').tagsinput('items');
        formData.set('skills', JSON.stringify(skills));
        
        $.ajax({
            url: 'handlers/convert_handler.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.href = '../candidates/view.php?id=' + response.candidate_id + '&converted=1';
                } else {
                    alert('Error: ' + response.message);
                    convertBtn.prop('disabled', false).html('<i class="bx bx-transfer me-1"></i> Convert to Candidate');
                }
            },
            error: function() {
                alert('Network error. Please try again.');
                convertBtn.prop('disabled', false).html('<i class="bx bx-transfer me-1"></i> Convert to Candidate');
            }
        });
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
