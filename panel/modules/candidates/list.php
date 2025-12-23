<?php
// modules/candidates/list.php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';
// Page configuration
$pageTitle = 'Candidates';
$breadcrumbs = [
    'Candidates' => '#'
];
// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// Display breadcrumb
echo renderBreadcrumb($breadcrumbs);

// Page configuration
$pageTitle = 'Candidates';
$breadcrumbs = [
    'Candidates' => '#'
];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Fetch filter data (lead types, etc.)
    $lead_types = ['Cold', 'Warm', 'Hot', 'Blacklist'];
    $work_auth = [];
    $res = $conn->query("SELECT DISTINCT auth_status FROM work_auth");
    while ($r = $res->fetch_assoc()) $work_auth[] = $r['auth_status'];
    
    $assigned_users = [];
    if (Auth::user()['level'] === 'admin') {
        $res = $conn->query("SELECT user_code, name FROM user WHERE level != 'admin' ORDER BY name");
        while ($r = $res->fetch_assoc()) $assigned_users[] = $r;
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    return;
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Candidate List</h4>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="candidateFilterForm" class="row g-3">
                <!-- Filters as in your doc, with tooltips -->
                <div class="col-md-3">
                    <label>Lead Type Role <i class="bx bx-info-circle" data-bs-toggle="tooltip" title="Filter by role type"></i></label>
                    <select id="lead_role_filter" class="form-select">
                        <option value="">All</option>
                        <option value="payroll">Payroll</option>
                        <option value="recruitment">Recruitment</option>
                    </select>
                </div>
                <?php if ($current_user['level'] === 'admin'): ?>
                <div class="col-md-3">
                    <label class="form-label">Assigned To</label>
                    <select id="assigned_filter" class="form-select">
                        <option value="all">All</option>
                        <?php foreach ($assigned_users as $u): ?>
                            <option value="<?= $u['user_code'] ?>"><?= $u['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" id="date_from" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" id="date_to" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" id="search_term" class="form-control"
                           placeholder="Name, Email, Phone, Skills">
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">Apply Filters</button>
                    <button type="reset" class="btn btn-outline-secondary ms-2">Reset</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bulk Actions -->
    <div class="mb-3">
        <button id="bulkAssign" class="btn btn-secondary disabled">Assign</button>
        <button id="bulkStatus" class="btn btn-secondary disabled">Change Status</button>
        <button id="bulkDelete" class="btn btn-danger disabled">Delete</button>
        <button id="exportBtn" class="btn btn-info disabled">Export</button>
    </div>
    
    <table id="candidatesTable" class="table table-hover">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Lead Type</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody> <!-- AJAX loaded -->
    </table>
</div>

<script src="https://cdn.datatables.net/v/bs5/dt-1.13.7/datatables.min.js"></script>
<script>
const table = $('#candidatesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: 'handlers/candidate_data_handler.php',
        type: 'POST',
        data: function(d) {
            d.token = '<?php echo Auth::token(); ?>';
            d.lead_role = $('#lead_role_filter').val();
            // Add other filters
        },
        error: function() {
            alert('Data load error. Retry?');
        }
    },
    columns: [
        { data: null, render: data => `<input type="checkbox" class="rowCheck" value="${data.id}">` },
        { data: 'name' },
        { data: 'email' },
        { data: 'phone' },
        { data: 'lead_type' },
        { data: 'status', render: data => `<span class="badge bg-label-primary">${data}</span>` },
        { data: null, render: data => `
            <a href="?action=view&id=${data.id}" class="btn btn-sm btn-info">View</a>
            <a href="?action=edit&id=${data.id}" class="btn btn-sm btn-primary">Edit</a>
        ` }
    ],
    lengthMenu: [10, 25, 50, 100],
    responsive: true
});

// Bulk logic similar to job
// Empty state handled by DataTables language
</script>