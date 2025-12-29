<?php
/**
 * Create Job - Simplified Business-Focused Version
 * File: panel/modules/jobs/create.php
 * No salary range, fixed location, fixed employment type
 */

// Load common bootstrap
require_once __DIR__ . '/../_common.php';
// Page configuration
$pageTitle = 'Create Jobs';
$breadcrumbs = [
    'Jobs' => '#'
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

// Pre-select client if coming from client page
$preselect_client = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

// Fetch active clients
$clients_query = "SELECT client_id, client_name, company_name FROM clients WHERE status = 'active' ORDER BY client_name";
$clients_result = mysqli_query($conn, $clients_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Job</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <span class="text-muted fw-light">Jobs /</span> Create New Job
            </h4>
            <p class="text-muted mb-0">Post a new position</p>
        </div>
        <a href="index.php?action=list&ss_id=<?php echo $token; ?>" class="btn btn-secondary">
            <i class="bx bx-arrow-back me-1"></i> Back to Jobs
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Job Details</h5>
                </div>
                <div class="card-body">
                    <div id="formError" class="alert alert-danger d-none mb-3"></div>
                    
                    <form id="jobForm" method="POST" action="handlers/job_save_handler.php">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="token" value="<?php echo Auth::token(); ?>">
                        <input type="hidden" name="location" value="Belgium">
                        <input type="hidden" name="employment_type" value="freelance">
                        <input type="hidden" name="job_code" value="JOB-<?php echo date('Ymd-His'); ?>">

                        <!-- Client Selection -->
                        <div class="mb-3">
                            <label class="form-label">
                                Client <span class="text-danger">*</span>
                            </label>
                            <select name="client_id" class="form-select" required>
                                <option value="">Select Client</option>
                                <?php while ($client = mysqli_fetch_assoc($clients_result)): ?>
                                    <option value="<?php echo $client['client_id']; ?>" 
                                            <?php echo ($preselect_client == $client['client_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($client['client_name']); ?>
                                        <?php if ($client['company_name'] && $client['company_name'] != $client['client_name']): ?>
                                            (<?php echo htmlspecialchars($client['company_name']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">
                                <a href="../clients/?action=create&ss_id=<?php echo $token; ?>" target="_blank">+ Add new client</a>
                            </small>
                        </div>

                        <!-- Job Title -->
                        <div class="mb-3">
                            <label class="form-label">
                                Job Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="job_title" 
                                   required 
                                   placeholder="e.g., Senior PHP Developer"
                                   maxlength="255">
                            <small class="text-muted">Keep it clear and specific</small>
                        </div>

                        <!-- Job Description -->
                        <div class="mb-3">
                            <label class="form-label">
                                Job Description <span class="text-danger">*</span>
                            </label>
                            <textarea id="description" 
                                      name="description" 
                                      class="form-control" 
                                      rows="8"
                                      required
                                      placeholder="Describe the role, responsibilities, and what you're looking for..."></textarea>
                        </div>

                        <!-- Requirements -->
                        <div class="mb-3">
                            <label class="form-label">
                                Requirements
                            </label>
                            <textarea class="form-control" 
                                      name="requirements" 
                                      rows="6"
                                      placeholder="List the skills, experience, and qualifications needed...
Example:
- 5+ years PHP development
- Laravel framework experience
- MySQL database skills"></textarea>
                            <small class="text-muted">List key requirements (optional)</small>
                        </div>

                        <!-- Rate (Daily) -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Daily Rate (€)
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       name="daily_rate" 
                                       min="0" 
                                       step="50"
                                       placeholder="e.g., 500">
                                <small class="text-muted">Freelance daily rate (optional)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Experience Required
                                </label>
                                <select class="form-select" name="experience_required">
                                    <option value="">Not specified</option>
                                    <option value="0-2 years">0-2 years (Junior)</option>
                                    <option value="3-5 years">3-5 years (Mid-level)</option>
                                    <option value="5-8 years">5-8 years (Senior)</option>
                                    <option value="8+ years">8+ years (Expert)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Start Date
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       name="start_date"
                                       min="<?php echo date('Y-m-d'); ?>">
                                <small class="text-muted">When can they start? (optional)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Closing Date
                                </label>
                                <input type="date" 
                                       class="form-control" 
                                       name="closing_date"
                                       min="<?php echo date('Y-m-d'); ?>">
                                <small class="text-muted">Application deadline (optional)</small>
                            </div>
                        </div>

                        <!-- Priority -->
                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority">
                                <option value="medium" selected>Normal Priority</option>
                                <option value="high">High Priority</option>
                                <option value="urgent">Urgent</option>
                                <option value="low">Low Priority</option>
                            </select>
                        </div>

                        <!-- Admin Notes (Source & Internal) -->
                        <div class="card bg-light mb-3">
                            <div class="card-header bg-transparent">
                                <h6 class="mb-0">Internal Information (Admin Only)</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">
                                        Source / How We Got This Job
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           name="job_source"
                                           placeholder="e.g., Client referral, LinkedIn, Direct contact, etc.">
                                    <small class="text-muted">Track where this opportunity came from</small>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">
                                        Internal Notes
                                    </label>
                                    <textarea class="form-control" 
                                              name="internal_notes" 
                                              rows="3"
                                              placeholder="Any internal notes, special requirements, budget constraints, etc."></textarea>
                                    <small class="text-muted">Notes for the team (not visible to client/candidate)</small>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1">
                                <label class="form-check-label" for="is_public">
                                    <strong>Make this job public</strong>
                                    <small class="text-muted d-block">
                                        When enabled, this job will appear on the public careers page and accept applications from candidates
                                    </small>
                                </label>
                            </div>
                        </div>                                           
                        <!-- Submit Buttons -->
                        <div class="pt-3">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bx bx-save me-1"></i> Create Job
                            </button>
                            <button type="button" class="btn btn-success me-2" id="createAndApprove">
                                <i class="bx bx-check-circle me-1"></i> Create & Approve
                            </button>
                            <a href="index.php?action=list&ss_id=<?php echo $token; ?>" class="btn btn-label-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Panel -->
        <div class="col-lg-4">
            <div class="card bg-label-info mb-3">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bx bx-info-circle me-1"></i> Quick Guide
                    </h6>
                    <ul class="mb-0 small">
                        <li class="mb-2">All jobs are <strong>Freelance</strong> positions</li>
                        <li class="mb-2">Location is automatically set to <strong>Belgium</strong></li>
                        <li class="mb-2">Use <strong>Daily Rate</strong> instead of monthly salary</li>
                        <li class="mb-2">Add <strong>Internal Notes</strong> for team reference</li>
                        <li class="mb-2">Track job <strong>Source</strong> for analytics</li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">After Creating Job</h6>
                    <p class="mb-2 small">Once created, you can:</p>
                    <ul class="mb-0 small">
                        <li>Receive CVs from website</li>
                        <li>Assign recruiters to work on it</li>
                        <li>Match existing candidates</li>
                        <li>Track applications</li>
                        <li>Submit candidates to client</li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3 border-warning">
                <div class="card-body">
                    <h6 class="card-title text-warning">
                        <i class="bx bx-shield me-1"></i> Approval Required
                    </h6>
                    <p class="mb-0 small">
                        New jobs require admin approval before they become active. 
                        Click "Create & Approve" if you're an admin.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TinyMCE for Description -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// Initialize TinyMCE
tinymce.init({
    selector: '#description',
    height: 300,
    plugins: 'lists link',
    toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter | bullist numlist | link | removeformat',
    menubar: false,
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }'
});

// Form submission
$('#jobForm').on('submit', function(e) {
    e.preventDefault();
    submitForm(false);
});

// Create and approve button
$('#createAndApprove').on('click', function() {
    submitForm(true);
});

function submitForm(autoApprove) {
    // Get TinyMCE content
    const description = tinymce.get('description').getContent();
    
    if (!description.trim()) {
        alert('Please enter a job description');
        return;
    }
    
    const formData = new FormData($('#jobForm')[0]);
    formData.set('description', description);
    
    if (autoApprove) {
        formData.set('auto_approve', '1');
    }
    
    $.ajax({
        url: 'handlers/job_save_handler.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        beforeSend: function() {
            $('#formError').addClass('d-none');
            $('button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');
            $('#createAndApprove').prop('disabled', true);
        },
        success: function(response) {
            if (response.success) {
                alert(response.message);
                window.location.href = 'index.php?action=view&id=' + response.job_id + '&ss_id=<?php echo $token; ?>';
            } else {
                $('#formError').text(response.message).removeClass('d-none');
                $('button[type="submit"]').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Create Job');
                $('#createAndApprove').prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            $('#formError').text('Network error: ' + error).removeClass('d-none');
            $('button[type="submit"]').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Create Job');
            $('#createAndApprove').prop('disabled', false);
        }
    });
}
</script>

</body>
</html>