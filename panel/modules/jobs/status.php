<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';
// Page configuration
$pageTitle = 'Jobs Status';

// Include header
require_once ROOT_PATH . '/panel/includes/header.php';


try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM jobs GROUP BY status");
    $statuses = [];
    while ($row = $stmt->fetch_assoc()) $statuses[$row['status']] = $row['count'];
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Job Status Overview</h4>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Pending</h5>
                    <h3><?php echo $statuses['pending'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Approved</h5>
                    <h3><?php echo $statuses['approved'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5>Rejected</h5>
                    <h3><?php echo $statuses['rejected'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Closed</h5>
                    <h3><?php echo $statuses['closed'] ?? 0; ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Status Distribution</h5>
        </div>
        <div class="card-body">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('statusChart'), {
    type: 'pie',
    data: {
        labels: ['Pending', 'Approved', 'Rejected', 'Closed'],
        datasets: [{
            data: [<?php echo implode(',', array_values($statuses)); ?>],
            backgroundColor: ['#ffc107', '#28a745', '#dc3545', '#6c757d']
        }]
    },
    options: {
        responsive: true
    }
});
</script>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>