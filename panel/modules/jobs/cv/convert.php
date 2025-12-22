<?php
/**
 * Convert CV to Candidate - Complete Workflow
 * File: panel/modules/jobs/cv/convert.php
 * Creates: Candidate + Application in one step
 */

require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';

if (!Auth::check()) {
    header('Location: ../../../login.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();
$user = Auth::user();
$token = $_GET['ss_id'] ?? '';

// Get CV ID
$cv_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cv_id) {
    die('CV ID required');
}

// Fetch CV details with job info
$stmt = $conn->prepare("
    SELECT cv.*, 
           j.job_id,
           j.job_title,
           j.job_code,
           c.client_name
    FROM cv_inbox cv
    LEFT JOIN jobs j ON cv.job_id = j.job_id
    LEFT JOIN clients c ON j.client_id = c.client_id
    WHERE cv.id = ?
");

$stmt->bind_param('i', $cv_id);
$stmt->execute();
$cv = $stmt->get_result()->fetch_assoc();

if (!$cv) {
    die('CV not found');
}

// Check if already converted
if ($cv['status'] === 'converted') {
    echo '<div class="alert alert-warning">This CV has already been converted to a candidate.</div>';
    echo '<a href="../../../can_full_view?can_code=' . $cv['converted_to_candidate'] . '&ss_id=' . $token . '" class="btn btn-primary">View Candidate</a>';
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Convert CV to Candidate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bx bx-transfer me-2"></i> Convert CV to Candidate
            </h4>
            <p class="text-muted mb-0">Create candidate profile and application from CV submission</p>
        </div>
        <a href="inbox.php?ss_id=<?php echo $token; ?>" class="btn btn-secondary">
            <i class="bx bx-arrow-back me-1"></i> Back to Inbox
        </a>
    </div>

    <div class="row">
        <!-- Left Column: Conversion Form -->
        <div class="col-lg-8">
            <!-- CV Preview Card -->
            <div class="card mb-4 bg-light">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0">CV Information (Read-Only)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($cv['candidate_name']); ?></p>
                            <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($cv['email']); ?></p>
                            <?php if ($cv['phone']): ?>
                                <p class="mb-2"><strong>Phone:</strong> <?php echo htmlspecialchars($cv['phone']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Applied for:</strong> <?php echo htmlspecialchars($cv['job_title']); ?></p>
                            <p class="mb-2"><strong>Client:</strong> <?php echo htmlspecialchars($cv['client_name']); ?></p>
                            <p class="mb-2"><strong>Submitted:</strong> <?php echo date('M d, Y', strtotime($cv['submitted_at'])); ?></p>
                        </div>
                    </div>
                    <?php if ($cv['cv_path']): ?>
                        <div class="mt-3">
                            <a href="<?php echo htmlspecialchars($cv['cv_path']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                <i class="bx bx-file"></i> View Original CV
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Conversion Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Candidate Details</h5>
                </div>
                <div class="card-body">
                    <div id="formError" class="alert alert-danger d-none mb-3"></div>
                    
                    <form id="convertForm" method="POST" action="handlers/convert_handler.php">
                        <input type="hidden" name="cv_id" value="<?php echo $cv_id; ?>">
                        <input type="hidden" name="job_id" value="<?php echo $cv['job_id']; ?>">
                        <input type="hidden" name="token" value="<?php echo Auth::token(); ?>">

                        <!-- Candidate Code (Auto-generated) -->
                        <div class="mb-3">
                            <label class="form-label">Candidate Code</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="can_code" 
                                   value="CAN-<?php echo date('Ymd-His'); ?>" 
                                   readonly>
                            <small class="text-muted">Auto-generated unique code</small>
                        </div>

                        <!-- Name (Pre-filled) -->
                        <div class="mb-3">
                            <label class="form-label">
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   name="candidate_name" 
                                   value="<?php echo htmlspecialchars($cv['candidate_name']); ?>" 
                                   required>
                        </div>

                        <!-- Email (Pre-filled) -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       name="email_id" 
                                       value="<?php echo htmlspecialchars($cv['email']); ?>" 
                                       required>
                            </div>

                            <!-- Phone (Pre-filled) -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Phone
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="phone" 
                                       value="<?php echo htmlspecialchars($cv['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Experience & Location -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Years of Experience
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       name="experience_years" 
                                       min="0" 
                                       max="50"
                                       placeholder="e.g., 5">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Current Location
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="current_location" 
                                       placeholder="e.g., Brussels">
                            </div>
                        </div>

                        <!-- Expected Salary -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Expected Daily Rate (€)
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       name="expected_salary" 
                                       min="0" 
                                       step="50"
                                       placeholder="e.g., 500">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Availability
                                </label>
                                <select class="form-select" name="availability">
                                    <option value="">Not specified</option>
                                    <option value="Immediate">Immediate</option>
                                    <option value="2 weeks">2 weeks notice</option>
                                    <option value="1 month">1 month notice</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Skills -->
                        <div class="mb-3">
                            <label class="form-label">
                                Key Skills
                            </label>
                            <textarea class="form-control" 
                                      name="skills" 
                                      rows="3"
                                      placeholder="Enter main skills separated by commas (e.g., PHP, Laravel, MySQL)"></textarea>
                        </div>

                        <!-- Application Settings -->
                        <div class="card bg-light mb-3">
                            <div class="card-header bg-transparent">
                                <h6 class="mb-0">Application Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">
                                        Apply to Job
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($cv['job_title']); ?> (<?php echo htmlspecialchars($cv['job_code']); ?>)" 
                                           readonly>
                                    <small class="text-muted">Application will be created for this job automatically</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Initial Status
                                    </label>
                                    <select class="form-select" name="application_status">
                                        <option value="applied">Applied</option>
                                        <option value="screening" selected>Screening (Recommended)</option>
                                        <option value="screening_passed">Screening Passed</option>
                                    </select>
                                    <small class="text-muted">Start the candidate at this stage</small>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="send_welcome_email" value="1" id="sendEmail">
                                    <label class="form-check-label" for="sendEmail">
                                        Send welcome email to candidate
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label class="form-label">
                                Initial Notes
                            </label>
                            <textarea class="form-control" 
                                      name="notes" 
                                      rows="3"
                                      placeholder="Add any notes about this candidate (CV screening notes, observations, etc.)"></textarea>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="pt-3">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bx bx-transfer me-1"></i> Create Candidate & Application
                            </button>
                            <a href="inbox.php?ss_id=<?php echo $token; ?>" class="btn btn-label-secondary btn-lg">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Help & Info -->
        <div class="col-lg-4">
            <div class="card bg-label-info mb-3">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bx bx-info-circle me-1"></i> What Happens Next?
                    </h6>
                    <ul class="mb-0 small">
                        <li class="mb-2">Candidate profile will be created</li>
                        <li class="mb-2">Original CV will be linked to candidate</li>
                        <li class="mb-2">Application will be created for "<?php echo htmlspecialchars($cv['job_title']); ?>"</li>
                        <li class="mb-2">CV Inbox status will update to "Converted"</li>
                        <li class="mb-2">You'll be redirected to candidate profile</li>
                    </ul>
                </div>
            </div>

            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="card-title text-warning">
                        <i class="bx bx-error me-1"></i> Important
                    </h6>
                    <p class="mb-0 small">
                        Make sure all information is correct before converting. 
                        You can edit the candidate profile later, but you cannot undo the conversion.
                    </p>
                </div>
            </div>

            <?php if ($cv['cover_letter']): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Cover Letter</h6>
                </div>
                <div class="card-body">
                    <p class="small"><?php echo nl2br(htmlspecialchars($cv['cover_letter'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$('#convertForm').on('submit', function(e) {
    e.preventDefault();
    
    if (!confirm('Create candidate and application from this CV?')) {
        return;
    }
    
    const formData = new FormData(this);
    
    $.ajax({
        url: 'handlers/convert_handler.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        beforeSend: function() {
            $('#formError').addClass('d-none');
            $('button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Converting...');
        },
        success: function(response) {
            if (response.success) {
                alert('Candidate created successfully!');
                // Redirect to candidate profile
                window.location.href = '../../../can_full_view?can_code=' + response.can_code + '&ss_id=<?php echo $token; ?>';
            } else {
                $('#formError').text(response.message).removeClass('d-none');
                $('button[type="submit"]').prop('disabled', false).html('<i class="bx bx-transfer me-1"></i> Create Candidate & Application');
            }
        },
        error: function(xhr, status, error) {
            $('#formError').text('Network error: ' + error).removeClass('d-none');
            $('button[type="submit"]').prop('disabled', false).html('<i class="bx bx-transfer me-1"></i> Create Candidate & Application');
        }
    });
});
</script>

</body>
</html>