<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch dropdown data
$statuses = $conn->query("SELECT * FROM contact_statuses WHERE is_active = 1 ORDER BY status_order");
$sources = $conn->query("SELECT * FROM contact_sources WHERE is_active = 1");
$recruiters = $conn->query("SELECT user_id, first_name, last_name FROM users WHERE role IN ('recruiter', 'admin') ORDER BY first_name");
$tags = $conn->query("SELECT * FROM contact_tags ORDER BY tag_name");
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">
                    <a href="index.php" class="text-muted">Contacts</a> /
                </span> 
                Add New Contact
            </h4>
        </div>
    </div>

    <!-- Duplicate Alert (Hidden by default) -->
    <div id="duplicateAlert" class="alert alert-warning alert-dismissible d-none" role="alert">
        <h6 class="alert-heading mb-1">
            <i class="bx bx-error-circle"></i> Potential Duplicate Found
        </h6>
        <p class="mb-0" id="duplicateMessage"></p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <form id="createContactForm" method="POST" action="handlers/create_handler.php">
        <div class="row">
            <!-- Main Information Card -->
            <div class="col-xl-8 col-lg-7 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="first_name">
                                    First Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       placeholder="John" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="last_name">
                                    Last Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       placeholder="Doe" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="email">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="john.doe@example.com" required>
                                <div id="emailFeedback" class="form-text"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">
                                    Phone Number <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       placeholder="+1 (555) 123-4567" required>
                                <div id="phoneFeedback" class="form-text"></div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="alternate_phone">Alternate Phone</label>
                                <input type="text" class="form-control" id="alternate_phone" name="alternate_phone" 
                                       placeholder="+1 (555) 987-6543">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="linkedin_url">LinkedIn URL</label>
                                <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" 
                                       placeholder="https://linkedin.com/in/johndoe">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="current_location">Current Location</label>
                            <input type="text" class="form-control" id="current_location" name="current_location" 
                                   placeholder="New York, NY">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="preferred_locations">Preferred Locations</label>
                            <input type="text" class="form-control" id="preferred_locations" name="preferred_locations" 
                                   placeholder="Remote, Austin, Seattle (comma separated)">
                            <small class="form-text text-muted">Separate multiple locations with commas</small>
                        </div>
                    </div>
                </div>

                <!-- Professional Information -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Professional Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="current_company">Current Company</label>
                                <input type="text" class="form-control" id="current_company" name="current_company" 
                                       placeholder="Acme Corp">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="current_title">Current Title</label>
                                <input type="text" class="form-control" id="current_title" name="current_title" 
                                       placeholder="Senior Software Engineer">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="experience_years">Years of Experience</label>
                                <input type="number" step="0.5" min="0" max="50" class="form-control" 
                                       id="experience_years" name="experience_years" placeholder="5.0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="notice_period">Notice Period</label>
                                <select class="form-select" id="notice_period" name="notice_period">
                                    <option value="">Select...</option>
                                    <option value="Immediate">Immediate</option>
                                    <option value="1 week">1 Week</option>
                                    <option value="2 weeks">2 Weeks</option>
                                    <option value="1 month">1 Month</option>
                                    <option value="2 months">2 Months</option>
                                    <option value="3 months">3 Months</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="current_salary">Current Salary (Annual)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="current_salary" 
                                           name="current_salary" placeholder="75000">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="expected_salary">Expected Salary (Annual)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="expected_salary" 
                                           name="expected_salary" placeholder="85000">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="skills">Skills</label>
                            <input type="text" class="form-control" id="skills" name="skills" 
                                   placeholder="JavaScript, React, Node.js, Python" 
                                   data-role="tagsinput">
                            <small class="form-text text-muted">Enter skills separated by commas or press Enter</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="interested_roles">Interested Roles</label>
                            <textarea class="form-control" id="interested_roles" name="interested_roles" 
                                      rows="2" placeholder="Full Stack Developer, Frontend Engineer"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-xl-4 col-lg-5">
                <!-- Status & Assignment -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Status & Assignment</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="status">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="status" name="status" required>
                                <?php while ($status = $statuses->fetch_assoc()): ?>
                                    <option value="<?php echo $status['status_value']; ?>"
                                            <?php echo $status['status_value'] === 'new' ? 'selected' : ''; ?>>
                                        <?php echo $status['status_label']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="source">
                                Source <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="source" name="source" required>
                                <option value="">Select source...</option>
                                <?php while ($source = $sources->fetch_assoc()): ?>
                                    <option value="<?php echo $source['source_value']; ?>">
                                        <?php echo $source['source_label']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3" id="sourceDetailsDiv" style="display: none;">
                            <label class="form-label" for="source_details">Source Details</label>
                            <input type="text" class="form-control" id="source_details" name="source_details" 
                                   placeholder="e.g., LinkedIn InMail Campaign">
                        </div>

                        <div class="mb-3" id="referrerDiv" style="display: none;">
                            <label class="form-label" for="referrer_name">Referrer Name</label>
                            <input type="text" class="form-control" id="referrer_name" name="referrer_name" 
                                   placeholder="Who referred this contact?">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="assigned_to">Assign To</label>
                            <select class="form-select" id="assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php while ($recruiter = $recruiters->fetch_assoc()): ?>
                                    <option value="<?php echo $recruiter['user_id']; ?>">
                                        <?php echo htmlspecialchars($recruiter['first_name'] . ' ' . $recruiter['last_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="priority">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="next_follow_up">Next Follow-up Date</label>
                            <input type="date" class="form-control" id="next_follow_up" name="next_follow_up">
                        </div>
                    </div>
                </div>

                <!-- Tags -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Tags</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($tags->num_rows > 0): ?>
                            <?php while ($tag = $tags->fetch_assoc()): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="tags[]" 
                                           value="<?php echo $tag['tag_id']; ?>" 
                                           id="tag_<?php echo $tag['tag_id']; ?>">
                                    <label class="form-check-label" for="tag_<?php echo $tag['tag_id']; ?>">
                                        <span class="badge" style="background-color: <?php echo $tag['tag_color']; ?>">
                                            <?php echo htmlspecialchars($tag['tag_name']); ?>
                                        </span>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted small">No tags available</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Initial Note -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Initial Note</h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" id="initial_note" name="initial_note" rows="4" 
                                  placeholder="Add any relevant notes about this contact..."></textarea>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2" id="submitBtn">
                            <i class="bx bx-save me-1"></i> Create Contact
                        </button>
                        <a href="index.php" class="btn btn-label-secondary w-100">
                            <i class="bx bx-x me-1"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Bootstrap Tags Input CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-tagsinput@0.8.0/dist/bootstrap-tagsinput.css">

<script src="https://cdn.jsdelivr.net/npm/bootstrap-tagsinput@0.8.0/dist/bootstrap-tagsinput.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize tags input
    $('#skills').tagsinput({
        trimValue: true,
        confirmKeys: [13, 44], // Enter and comma
        cancelConfirmKeysOnEmpty: true
    });

    // Show/hide source details based on source selection
    $('#source').on('change', function() {
        const source = $(this).val();
        
        if (source === 'referral') {
            $('#referrerDiv').slideDown();
            $('#sourceDetailsDiv').slideDown();
        } else if (source && source !== '') {
            $('#sourceDetailsDiv').slideDown();
            $('#referrerDiv').slideUp();
        } else {
            $('#sourceDetailsDiv').slideUp();
            $('#referrerDiv').slideUp();
        }
    });

    // Real-time duplicate detection for email
    let emailTimeout;
    $('#email').on('input', function() {
        clearTimeout(emailTimeout);
        const email = $(this).val().trim();
        
        if (email.length > 5 && email.includes('@')) {
            emailTimeout = setTimeout(function() {
                checkDuplicate('email', email);
            }, 500);
        } else {
            $('#emailFeedback').text('');
            $('#duplicateAlert').addClass('d-none');
        }
    });

    // Real-time duplicate detection for phone
    let phoneTimeout;
    $('#phone').on('input', function() {
        clearTimeout(phoneTimeout);
        const phone = $(this).val().trim();
        
        if (phone.length > 8) {
            phoneTimeout = setTimeout(function() {
                checkDuplicate('phone', phone);
            }, 500);
        } else {
            $('#phoneFeedback').text('');
        }
    });

    // Check for duplicates
    function checkDuplicate(field, value) {
        $.ajax({
            url: 'api/duplicate_check.php',
            method: 'GET',
            data: { field: field, value: value },
            dataType: 'json',
            success: function(response) {
                if (response.exists) {
                    const feedbackDiv = field === 'email' ? '#emailFeedback' : '#phoneFeedback';
                    $(feedbackDiv).html(
                        '<span class="text-warning"><i class="bx bx-error-circle"></i> ' +
                        'Similar contact found: <a href="view.php?id=' + response.contact_id + '" target="_blank">' +
                        response.name + '</a></span>'
                    );
                    
                    // Show duplicate alert
                    $('#duplicateMessage').html(
                        'A contact with similar ' + field + ' already exists: ' +
                        '<a href="view.php?id=' + response.contact_id + '" target="_blank" class="alert-link">' +
                        '<strong>' + response.name + '</strong></a>. ' +
                        'Please verify before creating a new contact.'
                    );
                    $('#duplicateAlert').removeClass('d-none');
                } else {
                    const feedbackDiv = field === 'email' ? '#emailFeedback' : '#phoneFeedback';
                    $(feedbackDiv).html('<span class="text-success"><i class="bx bx-check-circle"></i> Available</span>');
                }
            },
            error: function() {
                console.error('Error checking duplicate');
            }
        });
    }

    // Form submission
    $('#createContactForm').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $('#submitBtn');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');
        
        const formData = new FormData(this);
        
        // Convert skills tagsinput to JSON array
        const skills = $('#skills').tagsinput('items');
        formData.set('skills', JSON.stringify(skills));
        
        $.ajax({
            url: 'handlers/create_handler.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.href = 'view.php?id=' + response.contact_id + '&created=1';
                } else {
                    alert('Error: ' + response.message);
                    submitBtn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Create Contact');
                }
            },
            error: function(xhr) {
                alert('Network error. Please try again.');
                submitBtn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Create Contact');
            }
        });
    });

    // Set minimum date for follow-up to today
    const today = new Date().toISOString().split('T')[0];
    $('#next_follow_up').attr('min', today);
});
</script>

<?php require_once '../../includes/footer.php'; ?>
