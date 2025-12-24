<?php
/**
 * Candidates List - RECRUITER-FIRST DESIGN (FIXED)
 * Fixes: CSS loading, AJAX errors, proper layout
 */

// ============================================================================
// BOOTSTRAP
// ============================================================================
require_once __DIR__ . '/../_common.php';

$pageTitle = 'Candidates Database';
$pageDescription = 'Find the right talent quickly';

// ============================================================================
// FETCH FILTER OPTIONS
// ============================================================================
$skillsList = [];
$statusList = ['New', 'Screening', 'Interview', 'Offered', 'Hired', 'Rejected', 'On Hold'];
$experienceRanges = [
    '0-2' => '0-2 years',
    '2-5' => '2-5 years',
    '5-8' => '5-8 years',
    '8+' => '8+ years'
];
$noticePeriods = ['Immediate', '15 days', '30 days', '60 days', '90 days'];
$locations = [];
$stats = ['total' => 0, 'active' => 0, 'screening' => 0, 'interview' => 0];

try {
    $conn = getDB();
    
    // Get unique skills
    $skillsQuery = "SELECT DISTINCT skills FROM candidates WHERE skills IS NOT NULL AND skills != '' AND is_archived = 0";
    $result = $conn->query($skillsQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $skills = explode(',', $row['skills']);
            foreach ($skills as $skill) {
                $skill = trim($skill);
                if (!empty($skill) && !in_array($skill, $skillsList)) {
                    $skillsList[] = $skill;
                }
            }
        }
    }
    sort($skillsList);
    
    // Get unique locations
    $locQuery = "SELECT DISTINCT current_location FROM candidates WHERE current_location IS NOT NULL AND current_location != '' AND is_archived = 0";
    $result = $conn->query($locQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $locations[] = $row['current_location'];
        }
    }
    sort($locations);
    
    // Get assigned users for admin
    $assignedUsers = [];
    if ($current_user_level === 'admin') {
        $userResult = $conn->query("SELECT user_code, name FROM user WHERE level IN ('recruiter', 'manager') ORDER BY name");
        while ($user = $userResult->fetch_assoc()) {
            $assignedUsers[] = $user;
        }
    }
    
    // Get quick stats
    $baseQuery = "SELECT COUNT(*) as count, status FROM candidates WHERE is_archived = 0";
    if ($current_user_level === 'recruiter') {
        $baseQuery .= " AND assigned_to = '$current_user_code'";
    }
    $baseQuery .= " GROUP BY status";
    
    $result = $conn->query($baseQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = strtolower($row['status'] ?? '');
            if (in_array($status, ['active', 'new'])) {
                $stats['active'] += $row['count'];
            } elseif ($status === 'screening') {
                $stats['screening'] = $row['count'];
            } elseif (in_array($status, ['interview', 'interviewing'])) {
                $stats['interview'] += $row['count'];
            }
            $stats['total'] += $row['count'];
        }
    }
    
} catch (Exception $e) {
    error_log('Error loading filter options: ' . $e->getMessage());
}

// ============================================================================
// OUTPUT BUFFER START
// ============================================================================
ob_start();
?>

<!-- ======================================================================= -->
<!-- PAGE CONTENT -->
<!-- ======================================================================= -->

<div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Page Header with Stats -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="mb-1">
                        <i class="bx bx-user-circle"></i> Candidates Database
                    </h2>
                    <p class="text-muted mb-0">Find and manage your talent pool</p>
                </div>
                <div>
                    <a href="create.php" class="btn btn-primary btn-lg">
                        <i class="bx bx-plus-circle"></i> Add New Candidate
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 fw-semibold text-muted">Total</p>
                            <h3 class="mb-0"><?php echo number_format($stats['total']); ?></h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="bx bx-group bx-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 fw-semibold text-muted">Active</p>
                            <h3 class="mb-0"><?php echo number_format($stats['active']); ?></h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="bx bx-check-circle bx-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 fw-semibold text-muted">Screening</p>
                            <h3 class="mb-0"><?php echo number_format($stats['screening']); ?></h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="bx bx-search bx-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 fw-semibold text-muted">Interview</p>
                            <h3 class="mb-0"><?php echo number_format($stats['interview']); ?></h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="bx bx-user-voice bx-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Smart Filters -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="bx bx-filter-alt text-primary"></i> 
                <strong>Search Candidates</strong>
            </h5>
        </div>
        <div class="card-body">
            <form id="candidateSearchForm">
                
                <!-- Main Search -->
                <div class="row g-4 mb-4">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold fs-5">
                            <i class="bx bx-search-alt"></i> Quick Search
                        </label>
                        <input type="text" 
                               id="quick_search" 
                               name="quick_search"
                               class="form-control form-control-lg"
                               placeholder="Search by name, email, or keyword..."
                               style="border: 2px solid #dee2e6; font-size: 1.1rem;">
                        <small class="text-muted">
                            <i class="bx bx-info-circle"></i> 
                            Try: "PHP Developer", "John", "React 5 years", etc.
                        </small>
                    </div>
                </div>
                
                <!-- Primary Filters -->
                <div class="row g-4 mb-4">
                    
                    <!-- Skills Filter -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="bx bx-code-alt text-primary"></i> 
                            Skills <span class="text-danger">*</span>
                        </label>
                        <select id="skills_filter" 
                                name="skills[]" 
                                class="form-select"
                                multiple
                                style="height: 45px;">
                            <option value="">All Skills</option>
                            <?php foreach ($skillsList as $skill): ?>
                            <option value="<?php echo htmlspecialchars($skill); ?>">
                                <?php echo htmlspecialchars($skill); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd for multiple</small>
                    </div>
                    
                    <!-- Experience Range -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bx bx-briefcase text-info"></i> 
                            Experience
                        </label>
                        <select id="experience_filter" name="experience" class="form-select" style="height: 45px;">
                            <option value="">Any Experience</option>
                            <?php foreach ($experienceRanges as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Status -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bx bx-info-circle text-warning"></i> 
                            Status
                        </label>
                        <select id="status_filter" name="status" class="form-select" style="height: 45px;">
                            <option value="">All Statuses</option>
                            <?php foreach ($statusList as $status): ?>
                            <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Search Button -->
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100" style="height: 45px;">
                            <i class="bx bx-search"></i> Search
                        </button>
                    </div>
                    
                </div>
                
                <!-- Advanced Filters (Collapsible) -->
                <div class="row">
                    <div class="col-12">
                        <a class="btn btn-link text-decoration-none p-0" 
                           data-bs-toggle="collapse" 
                           href="#advancedFilters">
                            <i class="bx bx-chevron-down"></i> 
                            <strong>Advanced Filters</strong>
                        </a>
                    </div>
                </div>
                
                <div class="collapse" id="advancedFilters">
                    <hr class="my-3">
                    <div class="row g-3">
                        
                        <!-- Location -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bx bx-map"></i> Location
                            </label>
                            <select id="location_filter" name="location" class="form-select">
                                <option value="">Any Location</option>
                                <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo htmlspecialchars($loc); ?>">
                                    <?php echo htmlspecialchars($loc); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Notice Period -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bx bx-time"></i> Notice Period
                            </label>
                            <select id="notice_filter" name="notice_period" class="form-select">
                                <option value="">Any</option>
                                <?php foreach ($noticePeriods as $period): ?>
                                <option value="<?php echo $period; ?>"><?php echo $period; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Assigned To (Admin Only) -->
                        <?php if ($current_user_level === 'admin'): ?>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bx bx-user"></i> Assigned To
                            </label>
                            <select id="assigned_filter" name="assigned_to" class="form-select">
                                <option value="">Anyone</option>
                                <option value="unassigned">Unassigned</option>
                                <?php foreach ($assignedUsers as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['user_code']); ?>">
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Clear Filters -->
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="reset" class="btn btn-outline-secondary w-100">
                                <i class="bx bx-refresh"></i> Clear All
                            </button>
                        </div>
                        
                    </div>
                </div>
                
            </form>
        </div>
    </div>
    
    <!-- Results Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bx bx-list-ul"></i> 
                <strong>Search Results</strong>
                <span id="results-count" class="badge bg-primary ms-2">0</span>
            </h5>
            <div>
                <button id="exportBtn" class="btn btn-sm btn-outline-success">
                    <i class="bx bx-download"></i> Export
                </button>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="candidatesTable" class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45%"><strong>CANDIDATE</strong></th>
                            <th style="width: 20%"><strong>CURRENT STATUS</strong></th>
                            <th style="width: 35%" class="text-center"><strong>ACTION</strong></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loaded via DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
</div>

<!-- jQuery FIRST -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables -->
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    
    console.log('Initializing candidates list page...');
    
    // Initialize Select2
    $('#skills_filter').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select skills...',
        allowClear: true,
        closeOnSelect: false
    });
    
    // Initialize DataTable
    const table = $('#candidatesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'handlers/candidate_data_handler.php',
            type: 'POST',
            data: function(d) {
                // Auth token
                d.token = '<?php echo Auth::token(); ?>';
                
                // Filters
                d.quick_search = $('#quick_search').val();
                d.skills = $('#skills_filter').val();
                d.experience = $('#experience_filter').val();
                d.status = $('#status_filter').val();
                d.location = $('#location_filter').val();
                d.notice_period = $('#notice_filter').val();
                d.assigned_to = $('#assigned_filter').val();
                
                console.log('DataTables request data:', d);
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables AJAX Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error,
                    thrown: thrown
                });
                
                // Show user-friendly error
                alert('Failed to load candidates. Error: ' + (xhr.responseJSON?.error || xhr.statusText));
            },
            dataSrc: function(json) {
                console.log('DataTables response:', json);
                $('#results-count').text(json.recordsFiltered || 0);
                return json.data;
            }
        },
        columns: [
            // Candidate Card
            { 
                data: null,
                orderable: true,
                render: function(data, type, row) {
                    let skillBadges = '';
                    if (row.skills) {
                        const skills = row.skills.split(',').slice(0, 3);
                        skills.forEach(skill => {
                            skillBadges += `<span class="badge bg-label-primary me-1">${skill.trim()}</span>`;
                        });
                        const totalSkills = row.skills.split(',').length;
                        if (totalSkills > 3) {
                            skillBadges += `<span class="badge bg-label-secondary">+${totalSkills - 3}</span>`;
                        }
                    }
                    
                    return `
                        <div class="candidate-card py-2">
                            <div class="d-flex align-items-start">
                                <div class="avatar avatar-lg me-3">
                                    <span class="avatar-initial rounded-circle bg-label-primary fs-4">
                                        ${row.first_name ? row.first_name.charAt(0).toUpperCase() : 'C'}
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold">
                                        ${row.name || 'No name'}
                                    </h6>
                                    <div class="text-muted mb-2" style="font-size: 0.9rem;">
                                        ${row.job_title || 'No title'} 
                                        ${row.experience_years ? `• ${row.experience_years} years` : ''}
                                        ${row.current_location ? `• ${row.current_location}` : ''}
                                    </div>
                                    <div class="skills-container">
                                        ${skillBadges || '<span class="text-muted">No skills</span>'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            },
            
            // Status
            { 
                data: 'status',
                render: function(data) {
                    const statusConfig = {
                        'New': { color: 'primary', icon: 'bx-user-plus' },
                        'Screening': { color: 'info', icon: 'bx-search' },
                        'Interview': { color: 'warning', icon: 'bx-user-voice' },
                        'Offered': { color: 'success', icon: 'bx-envelope' },
                        'Hired': { color: 'success', icon: 'bx-check-circle' },
                        'Rejected': { color: 'danger', icon: 'bx-x-circle' },
                        'On Hold': { color: 'secondary', icon: 'bx-pause-circle' }
                    };
                    
                    const config = statusConfig[data] || statusConfig['New'];
                    
                    return `
                        <span class="badge bg-${config.color}" style="font-size: 0.9rem; padding: 8px 15px;">
                            <i class="bx ${config.icon}"></i> 
                            ${data || 'New'}
                        </span>
                    `;
                }
            },
            
            // Action
            { 
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data) {
                    return `
                        <a href="view.php?id=${data.can_code}" 
                           class="btn btn-primary btn-lg px-4"
                           style="font-size: 1rem; font-weight: 600;">
                            <i class="bx bx-user-circle"></i> 
                            View Profile
                        </a>
                    `;
                }
            }
        ],
        
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[0, 'asc']],
        language: {
            emptyTable: "No candidates found. Try adjusting filters.",
            zeroRecords: "No matching candidates. Try different criteria.",
            info: "Showing _START_ to _END_ of _TOTAL_ candidates",
            infoEmpty: "No candidates",
            infoFiltered: "(filtered from _MAX_ total)",
            lengthMenu: "Show _MENU_ per page",
            loadingRecords: "Loading candidates...",
            processing: "Processing..."
        }
    });
    
    // Search form
    $('#candidateSearchForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Search submitted');
        table.ajax.reload();
    });
    
    // Reset
    $('#candidateSearchForm').on('reset', function() {
        $('#skills_filter').val(null).trigger('change');
        setTimeout(() => table.ajax.reload(), 100);
    });
    
    // Export
    $('#exportBtn').on('click', function() {
        const filters = {
            quick_search: $('#quick_search').val(),
            skills: $('#skills_filter').val(),
            experience: $('#experience_filter').val(),
            status: $('#status_filter').val(),
            location: $('#location_filter').val(),
            notice_period: $('#notice_filter').val(),
            assigned_to: $('#assigned_filter').val(),
            token: '<?php echo Auth::token(); ?>'
        };
        
        window.location.href = 'handlers/export_handler.php?' + $.param(filters);
    });
    
    console.log('Candidates list page initialized successfully');
});
</script>

<style>
.candidate-card {
    transition: all 0.2s ease;
}
.candidate-card:hover {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 8px !important;
}
.table tbody tr:hover {
    background-color: #f8f9fa;
}
.skills-container .badge {
    font-size: 0.8rem;
    padding: 4px 10px;
    margin-bottom: 3px;
}
.btn-primary.btn-lg {
    min-width: 150px;
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}
.btn-primary.btn-lg:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
}
.select2-container--bootstrap-5 .select2-selection {
    height: 45px !important;
    padding-top: 8px;
}
</style>

<?php
// ============================================================================
// LOAD LAYOUT
// ============================================================================
$pageContent = ob_get_clean();
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/includes/sidebar.php';
echo $pageContent;
require_once ROOT_PATH . '/panel/includes/footer.php';
?>