<?php
// Assigned Candidates Management

$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch all users for assignment
$users = [];
$result = $conn->query("SELECT user_code, name FROM user WHERE level != 'admin' ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Fetch all candidates with assignments
$candidates = [];
$result = $conn->query("
    SELECT c.can_code, c.candidate_name, c.email_id,
           GROUP_CONCAT(DISTINCT ca.username) as assigned_users,
           GROUP_CONCAT(DISTINCT ca.usercode) as assigned_codes
    FROM candidates c
    LEFT JOIN candidate_assignments ca ON c.can_code = ca.can_code
    GROUP BY c.can_code
    ORDER BY c.created_at DESC
");

while ($row = $result->fetch_assoc()) {
    $candidates[] = $row;
}

$conn->close();
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Candidates /</span> Assignment Management
    </h4>

    <div class="card">
        <h5 class="card-header">Assign Candidates to Users</h5>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="assignmentTable">
                    <thead>
                        <tr>
                            <th>Candidate ID</th>
                            <th>Candidate Name</th>
                            <th>Email</th>
                            <th>Currently Assigned</th>
                            <th>Assign New User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidates as $candidate): ?>
                        <tr data-can-code="<?php echo htmlspecialchars($candidate['can_code']); ?>">
                            <td><?php echo htmlspecialchars($candidate['can_code']); ?></td>
                            <td><?php echo htmlspecialchars($candidate['candidate_name']); ?></td>
                            <td><?php echo htmlspecialchars($candidate['email_id']); ?></td>
                            <td id="assigned-<?php echo htmlspecialchars($candidate['can_code']); ?>">
                                <?php if ($candidate['assigned_users']): ?>
                                <div class="assigned-list">
                                    <?php 
                                    $assigned_users = explode(',', $candidate['assigned_users']);
                                    foreach ($assigned_users as $user): 
                                    ?>
                                    <span class="badge bg-primary me-1 mb-1">
                                        <?php echo htmlspecialchars($user); ?>
                                        <button type="button" class="btn-close btn-close-white btn-sm ms-1" 
                                                onclick="removeAssignment('<?php echo htmlspecialchars($candidate['can_code']); ?>', '<?php echo htmlspecialchars($user); ?>')"></button>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="input-group">
                                    <select class="form-select user-select" data-can-code="<?php echo htmlspecialchars($candidate['can_code']); ?>">
                                        <option value="">Select user...</option>
                                        <?php foreach ($users as $user): ?>
                                        <option value="<?php echo htmlspecialchars($user['user_code']); ?>">
                                            <?php echo htmlspecialchars($user['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-primary assign-btn" 
                                            data-can-code="<?php echo htmlspecialchars($candidate['can_code']); ?>">
                                        Assign
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function removeAssignment(canCode, userName) {
    if (!confirm('Remove ' + userName + ' from this candidate?')) return;
    
    fetch('modules/candidates/handlers/assignment_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'remove',
            can_code: canCode,
            user_name: userName,
            token: '<?php echo Auth::token(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Assign user to candidate
document.querySelectorAll('.assign-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const canCode = this.dataset.canCode;
        const select = this.closest('.input-group').querySelector('.user-select');
        const userCode = select.value;
        const userName = select.options[select.selectedIndex].text;
        
        if (!userCode) {
            alert('Please select a user');
            return;
        }
        
        fetch('modules/candidates/handlers/assignment_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'assign',
                can_code: canCode,
                user_code: userCode,
                user_name: userName,
                token: '<?php echo Auth::token(); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });
});
</script>