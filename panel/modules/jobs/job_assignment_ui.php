<?php
// modules/jobs/job_assignment_ui.php 
// Load common bootstrap
require_once __DIR__ . '/../_common.php';
// Page configuration
$pageTitle = 'Assignment Jobs';
$breadcrumbs = [
    'Jobs' => '#'
];
// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// Display breadcrumb
echo renderBreadcrumb($breadcrumbs);

// Assume $job_id is set from parent page (view/edit)
if (!isset($job_id)) {
    echo '<div class="alert alert-danger">Job ID not provided.</div>';
    return;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Fetch all recruiters
    $recruiters_result = $conn->query("SELECT user_code, name FROM user WHERE level != 'admin' ORDER BY name ASC");
    $all_recruiters = [];
    while ($row = $recruiters_result->fetch_assoc()) {
        $all_recruiters[] = $row;
    }
    
    // Fetch assigned
    $stmt = $conn->prepare("
        SELECT u.user_code, u.name 
        FROM job_assignments ja
        JOIN user u ON ja.user_code = u.user_code
        WHERE ja.job_id = ?
    ");
    $stmt->bind_param('i', $job_id);
    $stmt->execute();
    $assigned_result = $stmt->get_result();
    $assigned_recruiters = [];
    while ($row = $assigned_result->fetch_assoc()) {
        $assigned_recruiters[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error loading assignments: ' . $e->getMessage() . '</div>';
    return;
}
?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Assigned Recruiters</h5>
    </div>
    <div class="card-body">
        <div id="assignedRecruitersList" class="d-flex flex-wrap gap-2 mb-3">
            <?php if (empty($assigned_recruiters)): ?>
            <p class="text-muted">No recruiters are currently assigned to this job.</p>
            <?php else: ?>
            <?php foreach ($assigned_recruiters as $recruiter): ?>
            <span id="badge-<?php echo htmlspecialchars($recruiter['user_code']); ?>" class="badge bg-label-primary me-1">
                <?php echo htmlspecialchars($recruiter['name']); ?>
                <button type="button" class="btn-close btn-close-white ms-1" aria-label="Remove" onclick="unassignRecruiter('<?php echo htmlspecialchars($recruiter['user_code']); ?>')"></button>
            </span>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="input-group">
            <select id="recruiterSelect" class="form-select">
                <option value="">Select recruiter to assign...</option>
                <?php foreach ($all_recruiters as $recruiter): ?>
                <option value="<?php echo htmlspecialchars($recruiter['user_code']); ?>">
                    <?php echo htmlspecialchars($recruiter['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="button" id="assignButton">Assign</button>
        </div>
    </div>
</div>

<script>
function assignRecruiter(userCode, userName) {
    if (!userCode) return;
    
    $.ajax({
        url: 'handlers/job_assign_handle.php',
        type: 'POST',
        data: {
            action: 'assign',
            job_id: <?php echo $job_id; ?>,
            user_code: userCode,
            token: '<?php echo Auth::token(); ?>'
        },
        success: function(data) {
            if (data.success) {
                if ($('#assignedRecruitersList p.text-muted').length) {
                    $('#assignedRecruitersList').empty();
                }
                $('#assignedRecruitersList').append(`
                    <span id="badge-${userCode}" class="badge bg-label-primary me-1">
                        ${userName}
                        <button type="button" class="btn-close btn-close-white ms-1" aria-label="Remove" onclick="unassignRecruiter('${userCode}')"></button>
                    </span>
                `);
                $('#recruiterSelect').val('');
                alert(data.message);
            } else {
                alert(data.message);
            }
        },
        error: function() {
            alert('Network error. Please try again.');
        }
    });
}

function unassignRecruiter(userCode) {
    if (!confirm('Remove this recruiter from the job?')) return;
    
    $.ajax({
        url: 'handlers/job_assign_handle.php',
        type: 'POST',
        data: {
            action: 'unassign',
            job_id: <?php echo $job_id; ?>,
            user_code: userCode,
            token: '<?php echo Auth::token(); ?>'
        },
        success: function(data) {
            if (data.success) {
                $(`#badge-${userCode}`).remove();
                if ($('#assignedRecruitersList').children().length === 0) {
                    $('#assignedRecruitersList').html('<p class="text-muted">No recruiters are currently assigned to this job.</p>');
                }
                alert(data.message);
            } else {
                alert(data.message);
            }
        },
        error: function() {
            alert('Network error. Please try again.');
        }
    });
}

$(document).ready(function() {
    $('#assignButton').on('click', function() {
        const userCode = $('#recruiterSelect').val();
        const userName = $('#recruiterSelect option:selected').text();
        if (userCode) {
            assignRecruiter(userCode, userName);
        } else {
            alert('Please select a recruiter');
        }
    });
});
</script>