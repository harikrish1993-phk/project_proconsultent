<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';
// Page configuration
$pageTitle = 'List Jobs';
$breadcrumbs = [
    'Jobs' => '#'
];
// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// Display breadcrumb
echo renderBreadcrumb($breadcrumbs);

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $result = $conn->query("SELECT j.*, u.name AS created_by_name FROM jobs j LEFT JOIN user u ON j.created_by = u.user_code ORDER BY j.created_at DESC");
    $jobs = [];
    while ($row = $result->fetch_assoc()) $jobs[] = $row;
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Job List</h4>
    
    <a href="?action=create" class="btn btn-primary mb-3">Add Job</a>
    
    <table id="jobsTable" class="table table-hover">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>Title</th>
                <th>Status</th>
                <th>Created By</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jobs as $job): ?>
            <tr>
                <td><input type="checkbox" class="select-job" value="<?php echo $job['job_id']; ?>"></td>
                <td><?php echo htmlspecialchars($job['title']); ?></td>
                <td><span class="badge bg-<?php echo strtolower($job['status']); ?>"><?php echo ucfirst($job['status']); ?></span></td>
                <td><?php echo htmlspecialchars($job['created_by_name']); ?></td>
                <td><?php echo $job['created_at']; ?></td>
                <td>
                    <a href="?action=view&id=<?php echo $job['job_id']; ?>" class="btn btn-sm btn-info">View</a>
                    <a href="?action=edit&id=<?php echo $job['job_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script>
$('#jobsTable').DataTable({
    responsive: true,
    lengthMenu: [10, 25, 50, 100]
});

// Select all
$('#selectAll').on('click', function() {
    $('.select-job').prop('checked', this.checked);
});

// Bulk example (add more)
$('.bulk-delete').on('click', function() {
    const ids = $('.select-job:checked').map(function() { return this.value; }).get();
    if (ids.length && confirm('Delete selected jobs?')) {
        $.post('handlers/bulk_actions_handler.php', {
            action: 'bulk_delete',
            ids: ids,
            token: '<?php echo Auth::token(); ?>'
        }, function(data) {
            if (data.success) location.reload();
            else alert(data.message);
        });
    }
});
</script>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>