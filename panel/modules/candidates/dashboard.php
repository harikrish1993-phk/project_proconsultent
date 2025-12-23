<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';

$db = Database::getInstance();
$conn = $db->getConnection();
$user = Auth::user();

// Get KPIs
$stats = [];

// Total candidates
$result = $conn->query("SELECT COUNT(*) as count FROM candidates WHERE is_archived = 0");
$stats['total'] = $result->fetch_assoc()['count'];

// By status
$result = $conn->query("
    SELECT candidate_status, COUNT(*) as count 
    FROM candidates 
    WHERE is_archived = 0 
    GROUP BY candidate_status
");
$stats['by_status'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['by_status'][$row['candidate_status']] = $row['count'];
}

// New this week
$result = $conn->query("
    SELECT COUNT(*) as count 
    FROM candidates 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_archived = 0
");
$stats['new_week'] = $result->fetch_assoc()['count'];

// New this month
$result = $conn->query("
    SELECT COUNT(*) as count 
    FROM candidates 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_archived = 0
");
$stats['new_month'] = $result->fetch_assoc()['count'];

// By lead type
$result = $conn->query("
    SELECT lead_type, COUNT(*) as count 
    FROM candidates 
    WHERE is_archived = 0 
    GROUP BY lead_type
");
$stats['by_lead'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['by_lead'][$row['lead_type']] = $row['count'];
}

// Recent activity
$recent_activity = [];
$result = $conn->query("
    SELECT 
        cal.activity_description,
        cal.created_at,
        c.candidate_name,
        u.name as user_name
    FROM candidate_activity_log cal
    JOIN candidates c ON cal.can_code = c.can_code
    LEFT JOIN user u ON cal.created_by = u.user_code
    WHERE c.is_archived = 0
    ORDER BY cal.created_at DESC
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $recent_activity[] = $row;
}

// Upcoming follow-ups
$follow_ups = [];
$result = $conn->query("
    SELECT 
        can_code,
        candidate_name,
        email_id,
        follow_up_date
    FROM candidates
    WHERE follow_up_date >= CURDATE()
    AND follow_up = 'Not Done'
    AND is_archived = 0
    ORDER BY follow_up_date ASC
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $follow_ups[] = $row;
}

$conn->close();
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">Candidates Dashboard</h4>
        <div>
            <a href="?action=create" class="btn btn-primary">
                <i class="bx bx-plus"></i> Add Candidate
            </a>
            <a href="?action=list" class="btn btn-outline-primary">
                <i class="bx bx-list-ul"></i> View All
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="avatar">
                            <div class="avatar-initial bg-primary rounded">
                                <i class="bx bx-user fs-4"></i>
                            </div>
                        </div>
                        <span class="badge bg-label-primary"><?= date('Y') ?></span>
                    </div>
                    <span class="d-block mb-1">Total Candidates</span>
                    <h3 class="card-title mb-2"><?= number_format($stats['total']) ?></h3>
                    <small class="text-muted">Active database</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="avatar">
                            <div class="avatar-initial bg-success rounded">
                                <i class="bx bx-check-circle fs-4"></i>
                            </div>
                        </div>
                        <span class="badge bg-label-success">Active</span>
                    </div>
                    <span class="d-block mb-1">Active Status</span>
                    <h3 class="card-title mb-2"><?= number_format($stats['by_status']['Active'] ?? 0) ?></h3>
                    <small class="text-muted">Ready for placement</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="avatar">
                            <div class="avatar-initial bg-info rounded">
                                <i class="bx bx-time-five fs-4"></i>
                            </div>
                        </div>
                        <span class="badge bg-label-info">7 days</span>
                    </div>
                    <span class="d-block mb-1">New This Week</span>
                    <h3 class="card-title mb-2"><?= number_format($stats['new_week']) ?></h3>
                    <small class="text-success">+<?= $stats['new_week'] ?> new</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="avatar">
                            <div class="avatar-initial bg-warning rounded">
                                <i class="bx bx-calendar fs-4"></i>
                            </div>
                        </div>
                        <span class="badge bg-label-warning">30 days</span>
                    </div>
                    <span class="d-block mb-1">New This Month</span>
                    <h3 class="card-title mb-2"><?= number_format($stats['new_month']) ?></h3>
                    <small class="text-muted">Monthly intake</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Status Breakdown -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">Status Distribution</h5>
                    <small class="text-muted">Current breakdown</small>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Lead Type Breakdown -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">Lead Quality</h5>
                    <small class="text-muted">By lead type</small>
                </div>
                <div class="card-body">
                    <canvas id="leadChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Activity -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                    <a href="?action=list" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <ul class="timeline mb-0">
                        <?php foreach ($recent_activity as $activity): ?>
                        <li class="timeline-item timeline-item-transparent">
                            <span class="timeline-point timeline-point-primary"></span>
                            <div class="timeline-event">
                                <div class="timeline-header mb-1">
                                    <h6 class="mb-0"><?= htmlspecialchars($activity['candidate_name']) ?></h6>
                                    <small class="text-muted"><?= date('M j, g:i A', strtotime($activity['created_at'])) ?></small>
                                </div>
                                <p class="mb-2"><?= htmlspecialchars($activity['activity_description']) ?></p>
                                <?php if ($activity['user_name']): ?>
                                <small class="text-muted">By <?= htmlspecialchars($activity['user_name']) ?></small>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Upcoming Follow-ups -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Upcoming Follow-ups</h5>
                    <span class="badge bg-warning"><?= count($follow_ups) ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($follow_ups)): ?>
                    <div class="text-center py-4">
                        <i class="bx bx-check-circle bx-lg text-success"></i>
                        <p class="mt-2 text-muted">No pending follow-ups</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <?php foreach ($follow_ups as $followup): ?>
                                <tr>
                                    <td>
                                        <a href="?action=view&id=<?= $followup['can_code'] ?>">
                                            <?= htmlspecialchars($followup['candidate_name']) ?>
                                        </a>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($followup['email_id']) ?></small>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-label-warning">
                                            <?= date('M j', strtotime($followup['follow_up_date'])) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <?php foreach ($stats['by_status'] as $status => $count): ?>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <div class="d-flex flex-column">
                                <div class="mb-2">
                                    <h4 class="mb-0"><?= $count ?></h4>
                                </div>
                                <span class="text-muted small"><?= htmlspecialchars($status) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($stats['by_status'])) ?>,
        datasets: [{
            data: <?= json_encode(array_values($stats['by_status'])) ?>,
            backgroundColor: [
                '#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d', '#17a2b8'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right'
            }
        }
    }
});

// Lead Chart
const leadCtx = document.getElementById('leadChart').getContext('2d');
new Chart(leadCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($stats['by_lead'])) ?>,
        datasets: [{
            label: 'Candidates',
            data: <?= json_encode(array_values($stats['by_lead'])) ?>,
            backgroundColor: ['#007bff', '#ffc107', '#dc3545', '#343a40']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>
