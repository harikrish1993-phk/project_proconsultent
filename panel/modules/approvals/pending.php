<?php
require_once __DIR__ . '/../_common.php';
// Page configuration
$pageTitle = 'Jobs Approval';
$breadcrumbs = [
    'JobApproval' => '#'
];

$user = Auth::user();

// Get pending approvals for this user
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$stmt = $conn->prepare("
    SELECT ar.*, 
           ja.job_id, 
           j.title as job_title,
           c.candidate_name,
           c.email_id,
           u.name as requested_by_name
    FROM approval_requests ar
    JOIN job_applications ja ON ar.application_id = ja.application_id
    JOIN jobs j ON ja.job_id = j.job_id
    JOIN candidates c ON ja.can_code = c.can_code
    JOIN user u ON ar.requested_by = u.user_code
    WHERE ar.approver_user_code = ? 
    AND ar.status = 'pending'
    ORDER BY ar.requested_at DESC
");
$stmt->bind_param('s', $user['user_code']);
$stmt->execute();
$pendingApprovals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pending Approvals - <?php echo APP_NAME; ?></title>
    <link href="../../assets/vendor/css/core.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <h1>Pending Approvals (<?php echo count($pendingApprovals); ?>)</h1>
        
        <?php if (empty($pendingApprovals)): ?>
            <div class="alert alert-info">
                <i class="bx bx-info-circle"></i> No pending approvals
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Job</th>
                            <th>Candidate</th>
                            <th>Requested By</th>
                            <th>Requested Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingApprovals as $approval): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($approval['job_title']); ?></strong><br>
                                <small class="text-muted">Job ID: <?php echo $approval['job_id']; ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($approval['candidate_name']); ?><br>
                                <small><?php echo htmlspecialchars($approval['email_id']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($approval['requested_by_name']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($approval['requested_at'])); ?></td>
                            <td>
                                <a href="review.php?id=<?php echo $approval['approval_id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="bx bx-check-circle"></i> Review
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>