<?php
// modules/jobs/dashboard.php 
// Load common bootstrap
require_once __DIR__ . '/../_common.php';
// Page configuration
$pageTitle = 'Jobs Dashboard';
$breadcrumbs = [
    'Jobs' => '#'
];
// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// Display breadcrumb
echo renderBreadcrumb($breadcrumbs);

if (!Auth::check()) {
    header('Location: ../../../login.php');
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // KPIs
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM jobs GROUP BY status");
    $kpis = ['total' => 0];
    while ($row = $stmt->fetch_assoc()) {
        $kpis[$row['status']] = $row['count'];
        $kpis['total'] += $row['count'];
    }
    
    // Recent activity (last 5 jobs)
    $stmt = $conn->query("SELECT * FROM jobs ORDER BY updated_at DESC LIMIT 5");
    $recent = [];
    while ($row = $stmt->fetch_assoc()) $recent[] = $row;
    
    // Urgent (pending >7 days)
    $stmt = $conn->query("SELECT * FROM jobs WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT 5");
    $urgent = [];
    while ($row = $stmt->fetch_assoc()) $urgent[] = $row;
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Job Dashboard</h4>
    
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5>Total Jobs</h5>
                    <h2><?php echo $kpis['total']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h5>Active</h5>
                    <h2><?php echo $kpis['approved'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <h5>Pending</h5>
                    <h2><?php echo $kpis['pending'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <h5>Rejected</h5>
                    <h2><?php echo $kpis['rejected'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="?action=create" class="btn btn-primary mb-2 d-block">
                        <i class="bx bx-plus me-1"></i> Create New Job
                    </a>
                    <a href="?action=list" class="btn btn-secondary mb-2 d-block">
                        <i class="bx bx-list-ul me-1"></i> View All Jobs
                    </a>
                    <a href="?action=approve" class="btn btn-warning mb-2 d-block">
                        <i class="bx bx-check-shield me-1"></i> Review Pending Jobs
                    </a>
                    <a href="?action=status" class="btn btn-info mb-2 d-block">
                        <i class="bx bx-bar-chart me-1"></i> View Status Overview
                    </a>
                    <a href="cv/inbox.php" class="btn btn-primary d-block">
                        <i class="bx bx-file-find me-1"></i> CV Inbox
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Activity</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($recent)): ?>
                    <div class="list-group-item text-center text-muted">No recent activity</div>
                    <?php else: ?>
                    <?php foreach ($recent as $item): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <span><?php echo htmlspecialchars($item['title']); ?></span>
                            <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($item['updated_at'])); ?></small>
                        </div>
                        <small>Status: <?php echo ucfirst($item['status']); ?></small>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Urgent Items</h5>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($urgent)): ?>
                    <tr><td colspan="3" class="text-center text-muted">No urgent items</td></tr>
                    <?php else: ?>
                    <?php foreach ($urgent as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($item['created_at'])); ?></td>
                        <td>
                            <a href="?action=view&id=<?php echo $item['job_id']; ?>" class="btn btn-sm btn-warning">Review</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>