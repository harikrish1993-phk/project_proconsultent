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
$contactQuery = "SELECT c.* FROM contacts c WHERE c.contact_id = ? AND c.is_archived = 0";
$stmt = $conn->prepare($contactQuery);
$stmt->bind_param("i", $contactId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">Contact not found.</div>';
    require_once '../../includes/footer.php';
    exit;
}

$contact = $result->fetch_assoc();

// Fetch dropdown data
$statuses = $conn->query("SELECT * FROM contact_statuses WHERE is_active = 1 ORDER BY status_order");
$sources = $conn->query("SELECT * FROM contact_sources WHERE is_active = 1");
$recruiters = $conn->query("SELECT user_id, first_name, last_name FROM users WHERE role IN ('recruiter', 'admin') ORDER BY first_name");
$tags = $conn->query("SELECT * FROM contact_tags ORDER BY tag_name");

// Fetch selected tags
$selectedTagsQuery = "SELECT tag_id FROM contact_tag_map WHERE contact_id = ?";
$stmt = $conn->prepare($selectedTagsQuery);
$stmt->bind_param("i", $contactId);
$stmt->execute();
$selectedTagsResult = $stmt->get_result();
$selectedTags = [];
while ($row = $selectedTagsResult->fetch_assoc()) {
    $selectedTags[] = $row['tag_id'];
}

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
                Edit
            </h4>
        </div>
    </div>

    <form id="editContactForm" method="POST" action="handlers/update_handler.php">
        <input type="hidden" name="contact_id" value="<?php echo $contactId; ?>">
        
        <div class="row">
            <!-- Main Information -->
            <div class="col-xl-8 col-lg-7 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" 
                                       value="<?php echo htmlspecialchars($contact['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
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
                                <label class="form-label">Alternate Phone</label>
                                <input type="text" class="form-control" name="alternate_phone" 
                                       value="<?php echo htmlspecialchars($contact['alternate_phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">LinkedIn URL</label>
                                <input type="url" class="form-control" name="linkedin_url" 
                                       value="<?php echo htmlspecialchars($contact['linkedin_url'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Current Location</label>
                            <input type="text" class="form-control" name="current_location" 
                                   value="<?php echo htmlspecialchars($contact['current_location'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Preferred Locations</label>
                            <input type="text" class="form-control" name="preferred_locations" 
                                   value="<?php echo htmlspecialchars($contact['preferred_locations'] ?? ''); ?>">
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
                                <label class="form-label">Experience (Years)</label>
                                <input type="number" step="0.5" class="form-control" name="experience_years" 
                                       value="<?php echo $contact['experience_years'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notice Period</label>
                                <select class="form-select" name="notice_period">
                                    <option value="">Select...</option>
                                    <option value="Immediate" <?php echo $contact['notice_period'] === 'Immediate' ? 'selected' : ''; ?>>Immediate</option>
                                    <option value="1 week" <?php echo $contact['notice_period'] === '1 week' ? 'selected' : ''; ?>>1 Week</option>
                                    <option value="2 weeks" <?php echo $contact['notice_period'] === '2 weeks' ? 'selected' : ''; ?>>2 Weeks</option>
                                    <option value="1 month" <?php echo $contact['notice_period'] === '1 month' ? 'selected' : ''; ?>>1 Month</option>
                                    <option value="2 months" <?php echo $contact['notice_period'] === '2 months' ? 'selected' : ''; ?>>2 Months</option>
                                    <option value="3 months" <?php echo $contact['notice_period'] === '3 months' ? 'selected' : ''; ?>>3 Months</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Current Salary</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="current_salary" 
                                           value="<?php echo $contact['current_salary'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expected Salary</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="expected_salary" 
                                           value="<?php echo $contact['expected_salary'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Skills</label>
                            <input type="text" class="form-control" id="skills" name="skills" 
                                   value="<?php echo htmlspecialchars($skillsString); ?>" data-role="tagsinput">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Interested Roles</label>
                            <textarea class="form-control" name="interested_roles" rows="2"><?php echo htmlspecialchars($contact['interested_roles'] ?? ''); ?></textarea>
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
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <?php while ($status = $statuses->fetch_assoc()): ?>
                                    <option value="<?php echo $status['status_value']; ?>"
                                            <?php echo $contact['status'] === $status['status_value'] ? 'selected' : ''; ?>>
                                        <?php echo $status['status_label']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Source</label>
                            <select class="form-select" name="source">
                                <?php while ($source = $sources->fetch_assoc()): ?>
                                    <option value="<?php echo $source['source_value']; ?>"
                                            <?php echo $contact['source'] === $source['source_value'] ? 'selected' : ''; ?>>
                                        <?php echo $source['source_label']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Source Details</label>
                            <input type="text" class="form-control" name="source_details" 
                                   value="<?php echo htmlspecialchars($contact['source_details'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Assign To</label>
                            <select class="form-select" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php while ($recruiter = $recruiters->fetch_assoc()): ?>
                                    <option value="<?php echo $recruiter['user_id']; ?>"
                                            <?php echo $contact['assigned_to'] == $recruiter['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($recruiter['first_name'] . ' ' . $recruiter['last_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority">
                                <option value="low" <?php echo $contact['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo $contact['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo $contact['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="urgent" <?php echo $contact['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Next Follow-up</label>
                            <input type="date" class="form-control" name="next_follow_up" 
                                   value="<?php echo $contact['next_follow_up'] ?? ''; ?>">
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
                                           <?php echo in_array($tag['tag_id'], $selectedTags) ? 'checked' : ''; ?>
                                           id="tag_<?php echo $tag['tag_id']; ?>">
                                    <label class="form-check-label" for="tag_<?php echo $tag['tag_id']; ?>">
                                        <span class="badge" style="background-color: <?php echo $tag['tag_color']; ?>">
                                            <?php echo htmlspecialchars($tag['tag_name']); ?>
                                        </span>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="bx bx-save me-1"></i> Save Changes
                        </button>
                        <a href="view.php?id=<?php echo $contactId; ?>" class="btn btn-label-secondary w-100">
                            <i class="bx bx-x me-1"></i> Cancel
                        </a>
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
    $('#editContactForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Convert skills to JSON
        const skills = $('#skills').tagsinput('items');
        formData.set('skills', JSON.stringify(skills));
        
        $.ajax({
            url: 'handlers/update_handler.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.href = 'view.php?id=<?php echo $contactId; ?>&updated=1';
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Network error. Please try again.');
            }
        });
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
