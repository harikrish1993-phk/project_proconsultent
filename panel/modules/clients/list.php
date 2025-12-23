<?php
/**
 * Clients List Page
 * File: panel/modules/clients/list.php
 * Included by index.php - DO NOT access directly
 */

// Load common bootstrap
require_once __DIR__ . '/../_common.php';

// Fetch all clients
$query = "
    SELECT c.*, 
           u.name as created_by_name,
           (SELECT COUNT(*) FROM jobs WHERE client_id = c.client_id AND deleted_at IS NULL) as job_count
    FROM clients c
    LEFT JOIN user u ON c.created_by = u.user_code
    ORDER BY c.created_at DESC
";

$result = $conn->query($query);
$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bx bx-building me-2"></i> Clients Management
            </h4>
            <p class="text-muted mb-0">Manage your client companies and contacts</p>
        </div>
        <div>
            <a href="?action=create&ss_id=<?php echo $token; ?>" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> Add New Client
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-muted">Total Clients</p>
                            <h4 class="mb-0"><?php echo count($clients); ?></h4>
                        </div>
                        <div class="avatar avatar-md bg-label-primary">
                            <i class="bx bx-building bx-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-muted">Active Clients</p>
                            <h4 class="mb-0"><?php echo count(array_filter($clients, fn($c) => $c['status'] === 'active')); ?></h4>
                        </div>
                        <div class="avatar avatar-md bg-label-success">
                            <i class="bx bx-check-circle bx-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-muted">Active Jobs</p>
                            <h4 class="mb-0"><?php echo array_sum(array_column($clients, 'job_count')); ?></h4>
                        </div>
                        <div class="avatar avatar-md bg-label-info">
                            <i class="bx bx-briefcase bx-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 text-muted">This Month</p>
                            <h4 class="mb-0">
                                <?php 
                                echo count(array_filter($clients, function($c) {
                                    return date('Y-m', strtotime($c['created_at'])) === date('Y-m');
                                }));
                                ?>
                            </h4>
                        </div>
                        <div class="avatar avatar-md bg-label-warning">
                            <i class="bx bx-calendar bx-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Clients Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Clients</h5>
            <div>
                <button class="btn btn-sm btn-label-secondary" data-bs-toggle="tooltip" title="Export to Excel">
                    <i class="bx bx-download"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($clients)): ?>
                <div class="text-center py-5">
                    <i class="bx bx-building bx-lg text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5>No Clients Yet</h5>
                    <p class="text-muted">Add your first client to get started</p>
                    <a href="?action=create&ss_id=<?php echo $token; ?>" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Add First Client
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="clientsTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Company</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Active Jobs</th>
                                <th>Status</th>
                                <th>Added By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($client['client_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($client['company_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($client['contact_person'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($client['email']): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>">
                                            <?php echo htmlspecialchars($client['email']); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($client['phone']): ?>
                                        <a href="tel:<?php echo htmlspecialchars($client['phone']); ?>">
                                            <?php echo htmlspecialchars($client['phone']); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($client['job_count'] > 0): ?>
                                        <a href="../jobs/?action=list&client_id=<?php echo $client['client_id']; ?>&ss_id=<?php echo $token; ?>" class="badge bg-label-info">
                                            <?php echo $client['job_count']; ?> Jobs
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-label-<?php echo $client['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($client['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($client['created_by_name'] ?? 'System'); ?></small>
                                    <br>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($client['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="?action=view&id=<?php echo $client['client_id']; ?>&ss_id=<?php echo $token; ?>">
                                                    <i class="bx bx-show me-2"></i> View Details
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="?action=edit&id=<?php echo $client['client_id']; ?>&ss_id=<?php echo $token; ?>">
                                                    <i class="bx bx-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="../jobs/?action=create&client_id=<?php echo $client['client_id']; ?>&ss_id=<?php echo $token; ?>">
                                                    <i class="bx bx-briefcase me-2"></i> Create Job
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" onclick="deleteClient(<?php echo $client['client_id']; ?>)">
                                                    <i class="bx bx-trash me-2"></i> Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
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

<!-- DataTables CSS/JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#clientsTable').DataTable({
        pageLength: 25,
        order: [[7, 'desc']], // Sort by created date
        columnDefs: [
            { orderable: false, targets: [8] } // Actions column
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search clients..."
        }
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function deleteClient(clientId) {
    if (confirm('Are you sure you want to delete this client? This will also affect related jobs.')) {
        $.ajax({
            url: 'handlers/client_delete_handler.php',
            type: 'POST',
            data: {
                client_id: clientId,
                token: '<?php echo Auth::token(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Client deleted successfully');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Network error. Please try again.');
            }
        });
    }
}
</script>