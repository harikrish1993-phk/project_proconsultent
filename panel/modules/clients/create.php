<?php
/**
 * Create Client Form
 * File: panel/modules/clients/create.php
 * Included by index.php
 */

// Load common bootstrap
require_once __DIR__ . '/../_common.php';
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <span class="text-muted fw-light">Clients /</span> Add New Client
            </h4>
            <p class="text-muted mb-0">Create a new client company record</p>
        </div>
        <a href="?action=list&ss_id=<?php echo $token; ?>" class="btn btn-secondary">
            <i class="bx bx-arrow-back me-1"></i> Back to List
        </a>
    </div>

    <!-- Client Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Client Information</h5>
                </div>
                <div class="card-body">
                    <div id="formError" class="alert alert-danger d-none mb-3"></div>
                    <div id="formSuccess" class="alert alert-success d-none mb-3"></div>

                    <form id="clientForm" method="POST" action="handlers/client_save_handler.php">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="token" value="<?php echo Auth::token(); ?>">

                        <!-- Client Name -->
                        <div class="mb-3">
                            <label class="form-label" for="client_name">
                                Client Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="client_name" 
                                   name="client_name" 
                                   required 
                                   placeholder="e.g., John Doe" 
                                   maxlength="200">
                            <small class="text-muted">Primary contact or decision maker name</small>
                        </div>

                        <!-- Company Name -->
                        <div class="mb-3">
                            <label class="form-label" for="company_name">
                                Company Name
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="company_name" 
                                   name="company_name" 
                                   placeholder="e.g., TechCorp Solutions NV" 
                                   maxlength="200">
                            <small class="text-muted">Legal company name (optional if same as client name)</small>
                        </div>

                        <!-- Row: Contact Person & Email -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="contact_person">
                                    Contact Person
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="contact_person" 
                                       name="contact_person" 
                                       placeholder="e.g., Sarah Manager" 
                                       maxlength="100">
                                <small class="text-muted">Alternative contact (optional)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="email">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       required 
                                       placeholder="contact@company.com" 
                                       maxlength="255">
                            </div>
                        </div>

                        <!-- Row: Phone & City -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="phone">
                                    Phone Number
                                </label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone" 
                                       placeholder="+32 2 123 4567" 
                                       maxlength="50">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="city">
                                    City
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="city" 
                                       name="city" 
                                       placeholder="e.g., Brussels" 
                                       maxlength="100">
                            </div>
                        </div>

                        <!-- Country -->
                        <div class="mb-3">
                            <label class="form-label" for="country">Country</label>
                            <select class="form-select" id="country" name="country">
                                <option value="">Select Country</option>
                                <option value="Belgium" selected>Belgium</option>
                                <option value="Netherlands">Netherlands</option>
                                <option value="Luxembourg">Luxembourg</option>
                                <option value="France">France</option>
                                <option value="Germany">Germany</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Address -->
                        <div class="mb-3">
                            <label class="form-label" for="address">
                                Address
                            </label>
                            <textarea class="form-control" 
                                      id="address" 
                                      name="address" 
                                      rows="2" 
                                      placeholder="Street address, suite, building, etc."></textarea>
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label class="form-label" for="notes">
                                Internal Notes
                            </label>
                            <textarea class="form-control" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3" 
                                      placeholder="Add any internal notes about this client..."></textarea>
                            <small class="text-muted">These notes are for internal use only</small>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="pt-3">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bx bx-save me-1"></i> Save Client
                            </button>
                            <button type="button" class="btn btn-success me-2" id="saveAndAddJob">
                                <i class="bx bx-briefcase me-1"></i> Save & Create Job
                            </button>
                            <a href="?action=list&ss_id=<?php echo $token; ?>" class="btn btn-label-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Panel -->
        <div class="col-lg-4">
            <div class="card bg-label-primary">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bx bx-info-circle me-2"></i> Tips
                    </h5>
                    <ul class="mb-0">
                        <li class="mb-2">
                            <strong>Client Name:</strong> Use the primary decision maker's name
                        </li>
                        <li class="mb-2">
                            <strong>Company Name:</strong> Add if different from client name
                        </li>
                        <li class="mb-2">
                            <strong>Email:</strong> Main contact email for job-related communication
                        </li>
                        <li class="mb-2">
                            <strong>Status:</strong> Set to "Inactive" if not currently working with this client
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title">Next Steps</h6>
                    <p class="mb-2">After saving the client, you can:</p>
                    <ul class="mb-0">
                        <li>Create job postings for this client</li>
                        <li>Link existing candidates to their jobs</li>
                        <li>Track applications and placements</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Form submission
    $('#clientForm').on('submit', function(e) {
        e.preventDefault();
        submitForm(false);
    });

    // Save and add job button
    $('#saveAndAddJob').on('click', function() {
        submitForm(true);
    });

    function submitForm(redirectToJob) {
        const formData = new FormData($('#clientForm')[0]);
        
        $.ajax({
            url: 'handlers/client_save_handler.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            beforeSend: function() {
                $('#formError, #formSuccess').addClass('d-none');
                $('button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
            },
            success: function(response) {
                if (response.success) {
                    $('#formSuccess').text(response.message).removeClass('d-none');
                    
                    if (redirectToJob && response.client_id) {
                        // Redirect to job creation with pre-selected client
                        setTimeout(function() {
                            window.location.href = '../jobs/?action=create&client_id=' + response.client_id + '&ss_id=<?php echo $token; ?>';
                        }, 1000);
                    } else {
                        // Redirect to client list
                        setTimeout(function() {
                            window.location.href = '?action=list&ss_id=<?php echo $token; ?>';
                        }, 1500);
                    }
                } else {
                    $('#formError').text(response.message).removeClass('d-none');
                    $('button[type="submit"]').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Client');
                }
            },
            error: function(xhr, status, error) {
                $('#formError').text('Network error: ' + error).removeClass('d-none');
                $('button[type="submit"]').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Client');
            }
        });
    }

    // Auto-fill company name from client name if empty
    $('#client_name').on('blur', function() {
        if ($('#company_name').val() === '') {
            $('#company_name').val($(this).val());
        }
    });

    // Phone number formatting
    $('#phone').on('blur', function() {
        let phone = $(this).val().replace(/[^\d+]/g, '');
        if (phone && !phone.startsWith('+')) {
            phone = '+32' + phone;
        }
        $(this).val(phone);
    });
});
</script>