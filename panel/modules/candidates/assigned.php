<?php
// ============================================================================
// BOOTSTRAP & AUTHORIZATION
// ============================================================================
require_once __DIR__ . '/../_common.php';

// Check permissions - only admins can manage assignments
if (!$user || $user['role'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Access denied. Admins only.</div></div>';
    exit();
}

// Page configuration
$pageTitle = 'Assignment Management';


try {
    $conn = Database::getInstance()->getConnection();
    
    // Fetch all recruiters
    $recruiters = [];
    $result = $conn->query("
        SELECT user_code, full_name, email, COUNT(ca.can_code) as assigned_count
        FROM users u
        LEFT JOIN candidate_assignments ca ON ca.usercode = u.user_code
        WHERE u.level = 'user' AND u.is_active = 1
        GROUP BY u.user_code
        ORDER BY full_name ASC
    ");
    while ($row = $result->fetch_assoc()) {
        $recruiters[] = $row;
    }
    
    // Fetch all candidates with assignment details
    $candidates = [];
    $result = $conn->query("
        SELECT c.can_code, c.candidate_name, c.email_id, c.current_position, 
               c.experience, c.lead_type, c.candidate_status, c.created_at,
               GROUP_CONCAT(DISTINCT CONCAT(ca.usercode, ':', ca.username) SEPARATOR '|') as assignments,
               COUNT(ca.id) as assignment_count
        FROM candidates c
        LEFT JOIN candidate_assignments ca ON c.can_code = ca.can_code
        GROUP BY c.can_code
        ORDER BY c.created_at DESC
    ");
    while ($row = $result->fetch_assoc()) {
        // Parse assignments into array
        $assignments = [];
        if ($row['assignments']) {
            $assignmentStrings = explode('|', $row['assignments']);
            foreach ($assignmentStrings as $str) {
                list($usercode, $username) = explode(':', $str, 2);
                $assignments[] = [
                    'usercode' => $usercode,
                    'username' => $username
                ];
            }
        }
        $row['assignments'] = $assignments;
        $candidates[] = $row;
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo renderBreadcrumb($breadcrumbs);
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Error: ' . $e->getMessage() . '</div></div>';
    exit();
}
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <?= renderBreadcrumb($breadcrumbs) ?>

    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Candidates /</span> Assignment Management
    </h4>

    <!-- Stats Summary -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-2">
                            <p class="text-muted mb-1">Total Candidates</p>
                            <h4 class="mb-0"><?= number_format(count($candidates)) ?></h4>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="bx bx-user"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="badge bg-label-primary">Unassigned: <?= number_format(count(array_filter($candidates, fn($c) => empty($c['assignments'])))) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-2">
                            <p class="text-muted mb-1">Total Recruiters</p>
                            <h4 class="mb-0"><?= number_format(count($recruiters)) ?></h4>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="bx bx-group"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="badge bg-label-info">Active: <?= number_format(count(array_filter($recruiters, fn($r) => $r['assigned_count'] > 0))) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-2">
                            <p class="text-muted mb-1">Avg Assignments</p>
                            <h4 class="mb-0">
                                <?= count($recruiters) > 0 ? number_format(array_sum(array_column($recruiters, 'assigned_count')) / count($recruiters), 1) : 0 ?>
                            </h4>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="bx bx-stats"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-2">
                            <p class="text-muted mb-1">Total Assignments</p>
                            <h4 class="mb-0">
                                <?= number_format(array_sum(array_column($recruiters, 'assigned_count'))) ?>
                            </h4>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="bx bx-task"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignment Controls -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Manage Assignments</h5>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="bulkActions" data-bs-toggle="dropdown">
                        <i class="bx bx-batch me-1"></i> Bulk Actions
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" id="bulkAssign"><i class="bx bx-user-plus me-2"></i> Assign Selected</a></li>
                        <li><a class="dropdown-item" href="#" id="bulkUnassign"><i class="bx bx-user-minus me-2"></i> Unassign Selected</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" id="bulkBlacklist"><i class="bx bx-block me-2"></i> Blacklist Selected</a></li>
                    </ul>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignmentMatrixModal">
                    <i class="bx bx-grid-alt me-1"></i> View Assignment Matrix
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Search Candidates</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" id="candidateSearch" placeholder="Name, position, skills...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="New">New</option>
                        <option value="Contacted">Contacted</option>
                        <option value="Interview Scheduled">Interview Scheduled</option>
                        <option value="Offer Made">Offer Made</option>
                        <option value="Placed">Placed</option>
                        <option value="Rejected">Rejected</option>
                        <option value="On Hold">On Hold</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Lead Type</label>
                    <select class="form-select" id="leadTypeFilter">
                        <option value="">All Leads</option>
                        <option value="Hot">Hot Leads</option>
                        <option value="Warm">Warm Leads</option>
                        <option value="Cold">Cold Leads</option>
                        <option value="Blacklist">Blacklisted</option>
                    </select>
                </div>
            </div>
            
            <div class="table-responsive text-nowrap">
                <table class="table table-hover" id="assignmentTable">
                    <thead>
                        <tr>
                            <th class="pe-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                </div>
                            </th>
                            <th>CANDIDATE</th>
                            <th>POSITION</th>
                            <th>EXPERIENCE</th>
                            <th>STATUS</th>
                            <th>ASSIGNED TO</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidates as $candidate): ?>
                        <tr data-can-code="<?= htmlspecialchars($candidate['can_code']) ?>">
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input candidate-checkbox" type="checkbox" value="<?= htmlspecialchars($candidate['can_code']) ?>">
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-3">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            <?= strtoupper(substr($candidate['candidate_name'] ?? 'CN', 0, 2)) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-medium">
                                            <a href="full_view.php?id=<?= htmlspecialchars($candidate['can_code']) ?>" class="text-body text-decoration-none">
                                                <?= htmlspecialchars($candidate['candidate_name']) ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted"><?= htmlspecialchars($candidate['email_id']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($candidate['current_position'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-label-info">
                                    <?= htmlspecialchars($candidate['experience'] ?? '0') ?> years
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-label-<?= 
                                    $candidate['candidate_status'] == 'Placed' ? 'success' : 
                                    ($candidate['candidate_status'] == 'Interview Scheduled' || $candidate['candidate_status'] == 'Offer Made') ? 'warning' : 
                                    $candidate['candidate_status'] == 'Rejected' ? 'danger' : 'primary' 
                                ?>">
                                    <?= htmlspecialchars($candidate['candidate_status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php if (!empty($candidate['assignments'])): ?>
                                        <?php foreach ($candidate['assignments'] as $assignment): ?>
                                        <span class="badge bg-primary">
                                            <?= htmlspecialchars($assignment['username']) ?>
                                            <button type="button" class="btn-close btn-close-white btn-sm ms-1 remove-assignment" 
                                                    data-can-code="<?= htmlspecialchars($candidate['can_code']) ?>" 
                                                    data-user-code="<?= htmlspecialchars($assignment['usercode']) ?>" 
                                                    aria-label="Remove"></button>
                                        </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="badge bg-label-secondary">Unassigned</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="full_view.php?id=<?= htmlspecialchars($candidate['can_code']) ?>"><i class="bx bx-show me-1"></i> View Profile</a></li>
                                        <li><a class="dropdown-item" href="#"><i class="bx bx-edit me-1"></i> Edit Candidate</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <div class="px-3 py-2">
                                                <p class="fw-semibold mb-2">Assign to Recruiter:</p>
                                                <select class="form-select form-select-sm assign-select" data-can-code="<?= htmlspecialchars($candidate['can_code']) ?>">
                                                    <option value="">Select Recruiter</option>
                                                    <?php foreach ($recruiters as $recruiter): ?>
                                                    <option value="<?= htmlspecialchars($recruiter['user_code']) ?>">
                                                        <?= htmlspecialchars($recruiter['full_name']) ?> 
                                                        (<?= $recruiter['assigned_count'] ?> assigned)
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="d-flex align-items-center">
                    <label class="form-label me-2 mb-0">Show:</label>
                    <select class="form-select form-select-sm w-auto" style="width: 80px;">
                        <option>10</option>
                        <option selected>25</option>
                        <option>50</option>
                        <option>100</option>
                    </select>
                    <span class="ms-2 text-muted">entries</span>
                </div>
                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item prev disabled">
                            <a class="page-link" href="javascript:void(0);">
                                <i class="tf-icon bx bx-chevron-left"></i>
                            </a>
                        </li>
                        <li class="page-item active">
                            <a class="page-link" href="javascript:void(0);">1</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0);">2</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="javascript:void(0);">3</a>
                        </li>
                        <li class="page-item next">
                            <a class="page-link" href="javascript:void(0);">
                                <i class="tf-icon bx bx-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Assignment Matrix Modal -->
    <div class="modal fade" id="assignmentMatrixModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assignment Matrix</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Recruiter</th>
                                    <th>Total Assignments</th>
                                    <th>Hot Leads</th>
                                    <th>Warm Leads</th>
                                    <th>Cold Leads</th>
                                    <th>Candidates</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recruiters as $recruiter): 
                                    // Count assignments by lead type
                                    $hotCount = 0;
                                    $warmCount = 0;
                                    $coldCount = 0;
                                    $candidateList = [];
                                    
                                    foreach ($candidates as $candidate) {
                                        foreach ($candidate['assignments'] as $assignment) {
                                            if ($assignment['usercode'] === $recruiter['user_code']) {
                                                switch ($candidate['lead_type']) {
                                                    case 'Hot': $hotCount++; break;
                                                    case 'Warm': $warmCount++; break;
                                                    case 'Cold': $coldCount++; break;
                                                }
                                                $candidateList[] = $candidate['candidate_name'];
                                            }
                                        }
                                    }
                                ?>
                                <tr>
                                    <td class="fw-medium"><?= htmlspecialchars($recruiter['full_name']) ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?= $recruiter['assigned_count'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger"><?= $hotCount ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?= $warmCount ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $coldCount ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($candidateList)): ?>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                View Candidates (<?= count($candidateList) ?>)
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php foreach ($candidateList as $name): ?>
                                                <li><a class="dropdown-item" href="#"><?= htmlspecialchars($name) ?></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted">No assignments</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Export Report</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/panel/includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Select/Deselect all
    $('#selectAll').on('change', function() {
        $('.candidate-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Enable bulk actions when candidates selected
    $('.candidate-checkbox').on('change', function() {
        const anySelected = $('.candidate-checkbox:checked').length > 0;
        $('.dropdown-item[id^="bulk"]').closest('li').toggleClass('disabled', !anySelected);
    });
    
    // Remove assignment
    $(document).on('click', '.remove-assignment', function(e) {
        e.stopPropagation();
        const canCode = $(this).data('can-code');
        const userCode = $(this).data('user-code');
        const username = $(this).closest('.badge').text().replace(/\s*×\s*$/, '');
        
        if (confirm(`Remove ${username.trim()} from this candidate?`)) {
            $.post('handlers/assignment_handler.php', {
                action: 'remove',
                can_code: canCode,
                user_code: userCode,
                token: '<?= Auth::token() ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            });
        }
    });
    
    // Assign from dropdown
    $(document).on('change', '.assign-select', function() {
        const canCode = $(this).data('can-code');
        const userCode = $(this).val();
        const userName = $(this).find(`option[value="${userCode}"]`).text().split('(')[0].trim();
        
        if (userCode) {
            $.post('handlers/assignment_handler.php', {
                action: 'assign',
                can_code: canCode,
                user_code: userCode,
                user_name: userName,
                token: '<?= Auth::token() ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            });
        }
    });
    
    // Bulk assign
    $('#bulkAssign').on('click', function(e) {
        e.preventDefault();
        const selectedCandidates = $('.candidate-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedCandidates.length === 0) {
            alert('Please select candidates first');
            return;
        }
        
        const userCode = prompt('Enter recruiter user code to assign these candidates to:');
        if (userCode) {
            $.post('handlers/assignment_handler.php', {
                action: 'bulk_assign',
                can_codes: selectedCandidates,
                user_code: userCode,
                token: '<?= Auth::token() ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            });
        }
    });
    
    // Search and filtering
    $('#candidateSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#assignmentTable tbody tr').each(function() {
            const name = $(this).find('td:eq(1)').text().toLowerCase();
            const position = $(this).find('td:eq(2)').text().toLowerCase();
            $(this).toggle(name.includes(searchTerm) || position.includes(searchTerm));
        });
    });
    
    // Status filter
    $('#statusFilter').on('change', function() {
        const status = $(this).val().toLowerCase();
        $('#assignmentTable tbody tr').each(function() {
            if (!status) {
                $(this).show();
                return;
            }
            const candidateStatus = $(this).find('td:eq(4) .badge').text().toLowerCase();
            $(this).toggle(candidateStatus.includes(status));
        });
    });
    
    // Lead type filter
    $('#leadTypeFilter').on('change', function() {
        const leadType = $(this).val().toLowerCase();
        $('#assignmentTable tbody tr').each(function() {
            if (!leadType) {
                $(this).show();
                return;
            }
            const row = $(this);
            // This would need to be enhanced to filter by actual lead type
            // For now, we'll just show all rows
            row.show();
        });
    });
});
</script>