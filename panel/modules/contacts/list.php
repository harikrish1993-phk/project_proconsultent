<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';

$db = Database::getInstance();
$conn = $db->getConnection();
$userId = Auth::userId();
$userRole = Auth::userRole();

// Get filter options
$statuses = $conn->query("SELECT * FROM contact_statuses WHERE is_active = 1 ORDER BY status_order");
$sources = $conn->query("SELECT * FROM contact_sources WHERE is_active = 1");
$recruiters = $conn->query("SELECT user_id, first_name, last_name FROM users WHERE role IN ('recruiter', 'admin') ORDER BY first_name");
$tags = $conn->query("SELECT * FROM contact_tags ORDER BY tag_name");
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="fw-bold py-3 mb-0">
                    <span class="text-muted fw-light">
                        <a href="index.php" class="text-muted">Contacts</a> /
                    </span> 
                    All Contacts
                </h4>
                <div>
                    <a href="create.php" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Add Contact
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bx bx-filter me-1"></i> Filters
                </h5>
                <button class="btn btn-sm btn-label-secondary" id="clearFilters">
                    <i class="bx bx-x me-1"></i> Clear All
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Name, email, phone...">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="filterStatus">
                        <option value="">All Statuses</option>
                        <?php 
                        $statuses->data_seek(0);
                        while ($status = $statuses->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $status['status_value']; ?>">
                                <?php echo $status['status_label']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Source</label>
                    <select class="form-select" id="filterSource">
                        <option value="">All Sources</option>
                        <?php 
                        $sources->data_seek(0);
                        while ($source = $sources->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $source['source_value']; ?>">
                                <?php echo $source['source_label']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Assigned To</label>
                    <select class="form-select" id="filterAssigned">
                        <option value="">All</option>
                        <option value="unassigned">Unassigned</option>
                        <option value="me">My Contacts</option>
                        <optgroup label="Recruiters">
                            <?php 
                            $recruiters->data_seek(0);
                            while ($recruiter = $recruiters->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $recruiter['user_id']; ?>">
                                    <?php echo htmlspecialchars($recruiter['first_name'] . ' ' . $recruiter['last_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </optgroup>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Follow-up</label>
                    <select class="form-select" id="filterFollowUp">
                        <option value="">All</option>
                        <option value="overdue">Overdue</option>
                        <option value="today">Today</option>
                        <option value="this_week">This Week</option>
                        <option value="not_set">Not Set</option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">Priority</label>
                    <select class="form-select" id="filterPriority">
                        <option value="">All</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
            </div>
            
            <!-- Tag Filters -->
            <?php if ($tags->num_rows > 0): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <label class="form-label">Tags</label>
                    <div>
                        <?php while ($tag = $tags->fetch_assoc()): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input filter-tag" type="checkbox" 
                                       value="<?php echo $tag['tag_id']; ?>" 
                                       id="tag_<?php echo $tag['tag_id']; ?>">
                                <label class="form-check-label" for="tag_<?php echo $tag['tag_id']; ?>">
                                    <span class="badge" style="background-color: <?php echo $tag['tag_color']; ?>">
                                        <?php echo htmlspecialchars($tag['tag_name']); ?>
                                    </span>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="card mb-4" id="bulkActionsCard" style="display: none;">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <span class="me-3">
                    <strong id="selectedCount">0</strong> contacts selected
                </span>
                <div class="btn-group">
                    <button class="btn btn-sm btn-primary" data-action="assign">
                        <i class="bx bx-user me-1"></i> Assign
                    </button>
                    <button class="btn btn-sm btn-info" data-action="status">
                        <i class="bx bx-refresh me-1"></i> Change Status
                    </button>
                    <button class="btn btn-sm btn-warning" data-action="follow-up">
                        <i class="bx bx-calendar me-1"></i> Set Follow-up
                    </button>
                    <button class="btn btn-sm btn-secondary" data-action="tag">
                        <i class="bx bx-tag me-1"></i> Add Tags
                    </button>
                    <button class="btn btn-sm btn-danger" data-action="delete">
                        <i class="bx bx-trash me-1"></i> Delete
                    </button>
                </div>
                <button class="btn btn-sm btn-label-secondary ms-auto" id="deselectAll">
                    <i class="bx bx-x me-1"></i> Deselect All
                </button>
            </div>
        </div>
    </div>

    <!-- Contacts Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Contacts List</h5>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-secondary" id="exportCSV">
                    <i class="bx bx-download me-1"></i> Export CSV
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="contactsTable">
                    <thead>
                        <tr>
                            <th style="width: 30px;">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>Contact</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Skills</th>
                            <th>Priority</th>
                            <th>Assigned To</th>
                            <th>Follow-up</th>
                            <th>Created</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Action Modals -->
<!-- Assign Modal -->
<div class="modal fade" id="bulkAssignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Contacts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Assign to</label>
                <select class="form-select" id="bulkAssignTo">
                    <option value="">Select recruiter...</option>
                    <?php 
                    $recruiters->data_seek(0);
                    while ($recruiter = $recruiters->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $recruiter['user_id']; ?>">
                            <?php echo htmlspecialchars($recruiter['first_name'] . ' ' . $recruiter['last_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="bulkAssignConfirm">Assign</button>
            </div>
        </div>
    </div>
</div>

<!-- Status Modal -->
<div class="modal fade" id="bulkStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">New status</label>
                <select class="form-select" id="bulkStatus">
                    <?php 
                    $statuses->data_seek(0);
                    while ($status = $statuses->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $status['status_value']; ?>">
                            <?php echo $status['status_label']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="bulkStatusConfirm">Update Status</button>
            </div>
        </div>
    </div>
</div>

<!-- Follow-up Modal -->
<div class="modal fade" id="bulkFollowUpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Follow-up Date</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Follow-up date</label>
                <input type="date" class="form-control" id="bulkFollowUpDate">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="bulkFollowUpConfirm">Set Date</button>
            </div>
        </div>
    </div>
</div>

<script>
let contactsTable;
let selectedContacts = [];

$(document).ready(function() {
    // Initialize DataTable
    contactsTable = $('#contactsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'api/get_contacts.php',
            type: 'POST',
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.source = $('#filterSource').val();
                d.assigned = $('#filterAssigned').val();
                d.follow_up = $('#filterFollowUp').val();
                d.priority = $('#filterPriority').val();
                d.tags = getSelectedTags();
                d.user_id = <?php echo $userId; ?>;
            }
        },
        columns: [
            { 
                data: 'contact_id',
                orderable: false,
                render: function(data) {
                    return '<input type="checkbox" class="form-check-input contact-checkbox" value="' + data + '">';
                }
            },
            { 
                data: null,
                render: function(data) {
                    return `
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-2">
                                <span class="avatar-initial rounded-circle bg-label-primary">
                                    ${data.first_name.charAt(0)}
                                </span>
                            </div>
                            <div>
                                <a href="view.php?id=${data.contact_id}" class="text-decoration-none">
                                    <strong>${data.first_name} ${data.last_name}</strong>
                                </a><br>
                                <small class="text-muted">${data.email || 'N/A'}</small>
                            </div>
                        </div>
                    `;
                }
            },
            { 
                data: 'source',
                render: function(data) {
                    return '<span class="badge bg-label-secondary">' + 
                           data.replace('_', ' ').toUpperCase() + '</span>';
                }
            },
            { 
                data: 'status',
                render: function(data, type, row) {
                    const colors = {
                        'new': 'primary',
                        'contacted': 'info',
                        'interested': 'warning',
                        'not_interested': 'secondary',
                        'converted': 'success',
                        'on_hold': 'dark'
                    };
                    return '<span class="badge bg-' + (colors[data] || 'secondary') + '">' + 
                           data.replace('_', ' ').toUpperCase() + '</span>';
                }
            },
            { 
                data: 'skills',
                render: function(data) {
                    if (!data) return '<span class="text-muted">N/A</span>';
                    try {
                        const skills = JSON.parse(data);
                        if (skills.length === 0) return '<span class="text-muted">N/A</span>';
                        const display = skills.slice(0, 2).join(', ');
                        return '<small>' + display + (skills.length > 2 ? '...' : '') + '</small>';
                    } catch (e) {
                        return '<span class="text-muted">N/A</span>';
                    }
                }
            },
            { 
                data: 'priority',
                render: function(data) {
                    const colors = {
                        'urgent': 'danger',
                        'high': 'warning',
                        'medium': 'info',
                        'low': 'secondary'
                    };
                    return '<span class="badge bg-label-' + (colors[data] || 'secondary') + '">' + 
                           data.toUpperCase() + '</span>';
                }
            },
            { 
                data: 'assigned_to_name',
                render: function(data) {
                    return data || '<span class="badge bg-label-warning">Unassigned</span>';
                }
            },
            { 
                data: 'next_follow_up',
                render: function(data) {
                    if (!data) return '<small class="text-muted">Not set</small>';
                    const date = new Date(data);
                    const today = new Date();
                    today.setHours(0,0,0,0);
                    const followUpDate = new Date(date);
                    followUpDate.setHours(0,0,0,0);
                    
                    let className = 'text-muted';
                    if (followUpDate < today) className = 'text-danger fw-bold';
                    else if (followUpDate.getTime() === today.getTime()) className = 'text-warning fw-bold';
                    
                    return '<small class="' + className + '">' + 
                           date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + 
                           '</small>';
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    const date = new Date(data);
                    return '<small class="text-muted">' + 
                           date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + 
                           '</small>';
                }
            },
            { 
                data: 'contact_id',
                orderable: false,
                render: function(data, type, row) {
                    let actions = `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="view.php?id=${data}">
                                    <i class="bx bx-show me-1"></i> View
                                </a>
                                <a class="dropdown-item" href="edit.php?id=${data}">
                                    <i class="bx bx-edit me-1"></i> Edit
                                </a>
                    `;
                    
                    if (row.status !== 'converted') {
                        actions += `
                            <a class="dropdown-item" href="convert.php?id=${data}">
                                <i class="bx bx-transfer me-1"></i> Convert
                            </a>
                        `;
                    }
                    
                    actions += `
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="#" onclick="deleteContact(${data}); return false;">
                                    <i class="bx bx-trash me-1"></i> Delete
                                </a>
                            </div>
                        </div>
                    `;
                    
                    return actions;
                }
            }
        ],
        order: [[8, 'desc']], // Sort by created_at descending
        pageLength: 25,
        language: {
            emptyTable: "No contacts found",
            zeroRecords: "No matching contacts found"
        }
    });

    // Filter handlers
    $('#searchInput, #filterStatus, #filterSource, #filterAssigned, #filterFollowUp, #filterPriority').on('change keyup', function() {
        contactsTable.search($('#searchInput').val()).draw();
    });

    $('.filter-tag').on('change', function() {
        contactsTable.draw();
    });

    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#searchInput').val('');
        $('#filterStatus, #filterSource, #filterAssigned, #filterFollowUp, #filterPriority').val('');
        $('.filter-tag').prop('checked', false);
        contactsTable.search('').draw();
    });

    // Selection handlers
    $('#selectAll').on('change', function() {
        $('.contact-checkbox:visible').prop('checked', this.checked);
        updateSelectedContacts();
    });

    $(document).on('change', '.contact-checkbox', function() {
        updateSelectedContacts();
    });

    $('#deselectAll').on('click', function() {
        $('.contact-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false);
        updateSelectedContacts();
    });

    // Bulk action buttons
    $('[data-action]').on('click', function() {
        const action = $(this).data('action');
        
        if (selectedContacts.length === 0) {
            alert('Please select contacts first');
            return;
        }
        
        switch(action) {
            case 'assign':
                $('#bulkAssignModal').modal('show');
                break;
            case 'status':
                $('#bulkStatusModal').modal('show');
                break;
            case 'follow-up':
                $('#bulkFollowUpModal').modal('show');
                break;
            case 'delete':
                bulkDelete();
                break;
        }
    });

    // Bulk action confirmations
    $('#bulkAssignConfirm').on('click', function() {
        const assignTo = $('#bulkAssignTo').val();
        if (!assignTo) {
            alert('Please select a recruiter');
            return;
        }
        performBulkAction('assign', { assigned_to: assignTo });
    });

    $('#bulkStatusConfirm').on('click', function() {
        const status = $('#bulkStatus').val();
        performBulkAction('status', { status: status });
    });

    $('#bulkFollowUpConfirm').on('click', function() {
        const date = $('#bulkFollowUpDate').val();
        if (!date) {
            alert('Please select a date');
            return;
        }
        performBulkAction('follow_up', { next_follow_up: date });
    });
});

function getSelectedTags() {
    return $('.filter-tag:checked').map(function() {
        return this.value;
    }).get();
}

function updateSelectedContacts() {
    selectedContacts = $('.contact-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    $('#selectedCount').text(selectedContacts.length);
    
    if (selectedContacts.length > 0) {
        $('#bulkActionsCard').slideDown();
    } else {
        $('#bulkActionsCard').slideUp();
    }
}

function performBulkAction(action, data) {
    data.contact_ids = selectedContacts;
    data.action = action;
    
    $.ajax({
        url: 'handlers/bulk_actions_handler.php',
        method: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                $('.modal').modal('hide');
                contactsTable.ajax.reload();
                selectedContacts = [];
                updateSelectedContacts();
                $('#selectAll').prop('checked', false);
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Network error. Please try again.');
        }
    });
}

function bulkDelete() {
    if (confirm('Are you sure you want to delete ' + selectedContacts.length + ' contact(s)? This action cannot be undone.')) {
        performBulkAction('delete', {});
    }
}

function deleteContact(id) {
    if (confirm('Are you sure you want to delete this contact? This action cannot be undone.')) {
        $.ajax({
            url: 'handlers/delete_handler.php',
            method: 'POST',
            data: JSON.stringify({ contact_id: id }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    contactsTable.ajax.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }
        });
    }
}

// Export CSV
$('#exportCSV').on('click', function() {
    window.location.href = 'api/export_contacts.php?' + $.param({
        status: $('#filterStatus').val(),
        source: $('#filterSource').val(),
        assigned: $('#filterAssigned').val(),
        follow_up: $('#filterFollowUp').val(),
        priority: $('#filterPriority').val()
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
