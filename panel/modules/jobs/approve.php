<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';

if (!Auth::check() || Auth::user()['level'] !== 'admin') {
    echo '<div class="alert alert-danger">Admin only.</div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("SELECT * FROM jobs WHERE status = 'pending' ORDER BY created_at DESC");
    $pending = [];
    while ($row = $stmt->fetch_assoc()) $pending[] = $row;
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Approve Jobs</h4>
    
    <table class="table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Created By</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pending as $job): ?>
            <tr>
                <td><?php echo htmlspecialchars($job['title']); ?></td>
                <td><?php echo $job['created_by']; ?></td>
                <td><?php echo $job['created_at']; ?></td>
                <td>
                    <a href="?action=view&id=<?php echo $job['job_id']; ?>" class="btn btn-sm btn-info">View</a>
                    <button class="btn btn-sm btn-success approve-job" data-id="<?php echo $job['job_id']; ?>">Approve</button>
                    <button class="btn btn-sm btn-danger reject-job" data-id="<?php echo $job['job_id']; ?>">Reject</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
$('.approve-job').click(function() {
    const id = $(this).data('id');
    $.post('handlers/job_handle.php', {action: 'approve', job_id: id, token: '<?php echo Auth::token(); ?>'}, function(data) {
        if (data.success) location.reload();
        else alert(data.message);
    });
});

$('.reject-job').click(function() {
    const id = $(this).data('id');
    if (confirm('Reject job?')) {
        $.post('handlers/job_handle.php', {action: 'reject', job_id: id, token: '<?php echo Auth::token(); ?>'}, function(data) {
            if (data.success) location.reload();
            else alert(data.message);
        });
    }
});
</script>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>