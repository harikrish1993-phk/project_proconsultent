<?php
/**
 * Edit User Form
 * File: panel/modules/users/edit.php
 * Included by index.php
 */

// Load common bootstrap
require_once __DIR__ . '/../_common.php';
// Page configuration
// Page configuration
$pageTitle = 'Edit Team';
$breadcrumbs = [
    'Users' => '#'
];
// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// Display breadcrumb
echo renderBreadcrumb($breadcrumbs);

// Get user ID from index.php
if (!isset($id) || !$id) {
    throw new Exception('User ID is required');
}

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM user WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

if (!$user_data) {
    throw new Exception('User not found');
}
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <span class="text-muted fw-light">User Management /</span> Edit User
            </h4>
            <p class="text-muted mb-0">Update user information</p>
        </div>
        <div>
            <a href="?action=view&id=<?php echo $id; ?>&ss_id=<?php echo $token; ?>" class="btn btn-label-secondary me-2">
                <i class="bx bx-show me-1"></i> View Profile
            </a>
            <a href="?action=list&ss_id=<?php echo $token; ?>" class="btn btn-secondary">
                <i class="bx bx-arrow-back me-1"></i> Back to Users
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <div id="formError" class="alert alert-danger d-none mb-3"></div>
                    <div id="formSuccess" class="alert alert-success d-none mb-3"></div>

                    <form id="userForm" method="POST" action="handlers/user_save_handler.php">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                        <input type="hidden" name="token" value="<?php echo Auth::token(); ?>">

                        <!-- User Code (Read-only) -->
                        <div class="mb-3">
                            <label class="form-label">User Code</label>
                            <input type="text" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($user_data['user_code']); ?>" 
                                   readonly>
                        </div>

                        <!-- Full Name -->
                        <div class="mb-3">
                            <label class="form-label" for="name">
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo htmlspecialchars($user_data['name']); ?>"
                                   required 
                                   maxlength="100">
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label" for="email">
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($user_data['email']); ?>"
                                   required 
                                   maxlength="255">
                        </div>

                        <!-- Change Password Section -->
                        <div class="card bg-light mb-3">
                            <div class="card-header bg-transparent">
                                <h6 class="mb-0">Change Password (Optional)</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small">Leave blank to keep current password</p>
                                
                                <div class="mb-3">
                                    <label class="form-label" for="password">New Password</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control" 
                                               id="password" 
                                               name="password" 
                                               minlength="8"
                                               placeholder="Leave blank to keep current">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="bx bx-show"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Minimum 8 characters if changing</small>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           minlength="8"
                                           placeholder="Confirm new password">
                                </div>
                            </div>
                        </div>

                        <!-- Role -->
                        <div class="mb-3">
                            <label class="form-label" for="level">
                                Role <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="level" name="level" required>
                                <option value="admin" <?php echo $user_data['level'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                <option value="user" <?php echo $user_data['level'] === 'user' ? 'selected' : ''; ?>>Recruiter</option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?php echo $user_data['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $user_data['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>

                        <!-- Phone -->
                        <div class="mb-3">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>"
                                   maxlength="50">
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label class="form-label" for="notes">Notes</label>
                            <textarea class="form-control" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3"><?php echo htmlspecialchars($user_data['notes'] ?? ''); ?></textarea>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="pt-3">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bx bx-save me-1"></i> Save Changes
                            </button>
                            <a href="?action=view&id=<?php echo $id; ?>&ss_id=<?php echo $token; ?>" class="btn btn-label-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Panel -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Account Information</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Created:</strong><br>
                        <small><?php echo date('M d, Y H:i', strtotime($user_data['created_at'])); ?></small>
                    </p>
                    <?php if ($user_data['updated_at']): ?>
                    <p class="mb-0"><strong>Last Updated:</strong><br>
                        <small><?php echo date('M d, Y H:i', strtotime($user_data['updated_at'])); ?></small>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($user_data['user_code'] !== Auth::user()['user_code']): ?>
            <div class="card border-danger">
                <div class="card-header bg-label-danger">
                    <h6 class="mb-0 text-danger">Danger Zone</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">Deleting this user will:</p>
                    <ul class="small mb-3">
                        <li>Remove user permanently</li>
                        <li>Require reassignment of their data</li>
                        <li>Cannot be undone</li>
                    </ul>
                    <button type="button" class="btn btn-danger" onclick="deleteUser()">
                        <i class="bx bx-trash me-1"></i> Delete User
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    // Toggle password visibility
    $('#togglePassword').on('click', function() {
        const passwordField = $('#password');
        const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
        passwordField.attr('type', type);
        $(this).find('i').toggleClass('bx-show bx-hide');
    });

    // Form submission
    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate passwords match if changing
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (password || confirmPassword) {
            if (password !== confirmPassword) {
                $('#formError').text('Passwords do not match').removeClass('d-none');
                return;
            }
        }
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'handlers/user_save_handler.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#formError, #formSuccess').addClass('d-none');
                $('button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
            },
            success: function(response) {
                if (response.success) {
                    $('#formSuccess').text(response.message).removeClass('d-none');
                    
                    // Redirect to view after 1.5 seconds
                    setTimeout(function() {
                        window.location.href = '?action=view&id=<?php echo $id; ?>&ss_id=<?php echo $token; ?>';
                    }, 1500);
                } else {
                    $('#formError').text(response.message).removeClass('d-none');
                    $('button[type="submit"]').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Changes');
                }
            },
            error: function(xhr, status, error) {
                $('#formError').text('Network error: ' + error).removeClass('d-none');
                $('button[type="submit"]').prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Changes');
            }
        });
    });
});

function deleteUser() {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        return;
    }
    
    if (!confirm('Final confirmation: Delete user permanently?')) {
        return;
    }
    
    $.post('handlers/user_delete_handler.php', {
        user_id: <?php echo $id; ?>,
        token: '<?php echo Auth::token(); ?>'
    }, function(response) {
        if (response.success) {
            alert('User deleted successfully');
            window.location.href = '?action=list&ss_id=<?php echo $token; ?>';
        } else {
            alert('Error: ' + response.message);
        }
    }, 'json');
}
</script>