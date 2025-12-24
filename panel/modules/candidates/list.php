<?php
// modules/candidates/list.php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';

// Page configuration
$pageTitle = 'Candidates';
$breadcrumbs = ['Candidates' => '#'];

// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// NOW display breadcrumb (after ui_components loaded)
echo renderBreadcrumb($breadcrumbs);

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Fetch filter data (lead types, etc.)
    $lead_types = ['Cold', 'Warm', 'Hot', 'Blacklist'];
    
    // FIXED: Temporarily disabled - work_auth table not deployed
    $work_auth = [];
    // TODO: Uncomment after deploying work_auth table
    // $res = $conn->query("SELECT DISTINCT auth_status FROM work_auth");
    // while ($r = $res->fetch_assoc()) $work_auth[] = $r['auth_status'];
    
    $assigned_users = [];
    // FIXED: Changed $current_user['level'] to $current_user_level
    if ($current_user_level === 'admin') {
        $res = $conn->query("SELECT user_code, name FROM user WHERE level != 'admin' ORDER BY name");
        while ($r = $res->fetch_assoc()) $assigned_users[] = $r;
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    include ROOT_PATH . '/panel/includes/footer.php';
    exit;
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Candidate List</h4>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="candidateFilterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Lead Type Role</label>
                    <select id="lead_role_filter" class="form-select">
                        <option value="">All</option>
                        <option value="payroll">Payroll</option>
                        <option value="recruitment">Recruitment</option>
                    </select>
                </div>
                
                <!-- FIXED: Changed $current_user['level'] to $current_user_level -->
                <?php if ($current_user_level === 'admin'): ?>
                <div class="col-md-3">
                    <label class="form-label">Assigned To</label>
                    <select id="assigned_filter" class="form-select">
                        <option value="all">All</option>
                        <?php foreach ($assigned_users as $u): ?>
                            <option value="<?= htmlspecialchars($u['user_code']) ?>">
                                <?= htmlspecialchars($u['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" id="date_from" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" id="date_to" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" id="search_term" class="form-control"
                           placeholder="Search by name, email, phone, or skills">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button type="reset" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bulk Actions -->
    <div class="mb-3">
        <button id="bulkAssign" class="btn btn-secondary disabled" disabled>
            <i class="fas fa-user-plus"></i> Assign Selected
        </button>
        <button id="bulkStatus" class="btn btn-secondary disabled" disabled>
            <i class="fas fa-edit"></i> Change Status
        </button>
        <button id="bulkDelete" class="btn btn-danger disabled" disabled>
            <i class="fas fa-trash"></i> Delete Selected
        </button>
        <button id="exportBtn" class="btn btn-info">
            <i class="fas fa-download"></i> Export
        </button>
    </div>
    
    <!-- Candidates Table -->
    <div class="card">
        <div class="card-body">
            <table id="candidatesTable" class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th width="30"><input type="checkbox" id="selectAll"></th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loaded via DataTables AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Include footer -->
<?php include ROOT_PATH . '/panel/includes/footer.php'; ?>

<!-- jQuery (required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- DataTables -->
<link href="https://cdn.datatables.net/v/bs5/dt-1.13.7/datatables.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/v/bs5/dt-1.13.7/datatables.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#candidatesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'handlers/candidate_data_handler.php',
            type: 'POST',
            data: function(d) {
                // Add authentication token
                d.token = '<?php echo Auth::token(); ?>';
                
                // Add filter values
                d.lead_role = $('#lead_role_filter').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
                d.search_term = $('#search_term').val();
                d.assigned_to = $('#assigned_filter').val();
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', {xhr, error, thrown});
                alert('Failed to load candidate data. Please check the console for details or contact support.');
            }
        },
        columns: [
            { 
                data: null, 
                orderable: false,
                searchable: false,
                render: data => `<input type="checkbox" class="rowCheck" value="${data.id}" data-id="${data.id}">` 
            },
            { 
                data: 'name',
                render: (data, type, row) => {
                    return `<a href="view.php?id=${row.id}">${data}</a>`;
                }
            },
            { data: 'email' },
            { data: 'phone' },
            { 
                data: 'status', 
                render: data => {
                    const badges = {
                        'new': 'primary',
                        'active': 'success',
                        'screening': 'info',
                        'interviewing': 'warning',
                        'hired': 'success',
                        'rejected': 'danger'
                    };
                    const badge = badges[data] || 'secondary';
                    return `<span class="badge bg-${badge}">${data}</span>`;
                }
            },
            { 
                data: 'created_at',
                render: data => {
                    if (!data) return 'N/A';
                    const date = new Date(data);
                    return date.toLocaleDateString('en-GB');
                }
            },
            { 
                data: null,
                orderable: false,
                searchable: false,
                render: data => `
                    <div class="btn-group btn-group-sm">
                        <a href="view.php?id=${data.id}" class="btn btn-info" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit.php?id=${data.id}" class="btn btn-primary" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="deleteCandidate(${data.id})" class="btn btn-danger" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ` 
            }
        ],
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        pageLength: 25,
        responsive: true,
        language: {
            emptyTable: "No candidates found. Click 'Add Candidate' to create one.",
            zeroRecords: "No matching candidates found. Try adjusting your filters.",
            loadingRecords: "Loading candidates...",
            processing: "Processing..."
        },
        order: [[5, 'desc']] // Sort by created_at descending
    });
    
    // Filter form submission
    $('#candidateFilterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });
    
    // Reset filters
    $('#candidateFilterForm').on('reset', function() {
        setTimeout(() => {
            table.ajax.reload();
        }, 100);
    });
    
    // Select all checkbox
    $('#selectAll').on('change', function() {
        $('.rowCheck').prop('checked', this.checked);
        updateBulkButtons();
    });
    
    // Individual checkbox
    $(document).on('change', '.rowCheck', function() {
        updateBulkButtons();
    });
    
    // Update bulk action buttons
    function updateBulkButtons() {
        const checked = $('.rowCheck:checked').length;
        const buttons = $('#bulkAssign, #bulkStatus, #bulkDelete');
        
        if (checked > 0) {
            buttons.removeClass('disabled').prop('disabled', false);
        } else {
            buttons.addClass('disabled').prop('disabled', true);
        }
    }
    
    // Export function
    $('#exportBtn').on('click', function() {
        const filters = {
            lead_role: $('#lead_role_filter').val(),
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val(),
            search_term: $('#search_term').val(),
            assigned_to: $('#assigned_filter').val()
        };
        
        const queryString = $.param(filters);
        window.location.href = `handlers/export_handler.php?${queryString}`;
    });
});

// Delete candidate function
function deleteCandidate(id) {
    if (!confirm('Are you sure you want to delete this candidate? This action cannot be undone.')) {
        return;
    }
    
    $.post('handlers/candidate_delete_handler.php', {
        id: id,
        token: '<?php echo Auth::token(); ?>'
    }, function(response) {
        if (response.success) {
            alert('Candidate deleted successfully');
            $('#candidatesTable').DataTable().ajax.reload();
        } else {
            alert('Error: ' + (response.message || 'Failed to delete candidate'));
        }
    }, 'json').fail(function() {
        alert('Failed to delete candidate. Please try again.');
    });
}
</script>