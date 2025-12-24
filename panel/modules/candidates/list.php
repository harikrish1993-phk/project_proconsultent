<?php
/**
 * Candidates List - COMPLETE VERSION
 * Based on ACTUAL schema with all proper fields and filters
 */

// ============================================================================
// BOOTSTRAP
// ============================================================================
require_once __DIR__ . '/../_common.php';

$pageTitle = 'Candidates Database';
$pageDescription = 'Comprehensive talent pool management';

// ============================================================================
// FETCH FILTER OPTIONS FROM DATABASE
// ============================================================================
$skillsList = [];
$leadTypes = ['Cold', 'Warm', 'Hot', 'Blacklist'];
$leadTypeRoles = ['Payroll', 'Recruitment', 'Mixed'];
$workingStatuses = ['Freelance(Self)', 'Freelance(Company)', 'Employee'];
$experienceRanges = [
    '0-2' => '0-2 years',
    '2-5' => '2-5 years',
    '5-8' => '5-8 years',
    '8-15' => '8-15 years',
    '15+' => '15+ years'
];
$noticePeriods = [
    'immediate' => 'Immediate',
    '15' => 'Up to 15 days',
    '30' => 'Up to 30 days',
    '60' => 'Up to 60 days',
    '90' => 'Up to 90 days',
    '90+' => 'More than 90 days'
];
$currentLocations = [];
$preferredLocations = [];
$workAuthStatuses = [];
$stats = ['total' => 0, 'cold' => 0, 'warm' => 0, 'hot' => 0];

try {
    $conn = getDB();
    
    // Get unique skills from skill_set field
    $skillsQuery = "SELECT DISTINCT skill_set FROM candidates WHERE skill_set IS NOT NULL AND skill_set != ''";
    $result = $conn->query($skillsQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $skills = explode(',', $row['skill_set']);
            foreach ($skills as $skill) {
                $skill = trim($skill);
                if (!empty($skill) && !in_array($skill, $skillsList)) {
                    $skillsList[] = $skill;
                }
            }
        }
    }
    sort($skillsList);
    
    // Get unique current locations
    $locQuery = "SELECT DISTINCT current_location FROM candidates WHERE current_location IS NOT NULL AND current_location != ''";
    $result = $conn->query($locQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $currentLocations[] = $row['current_location'];
        }
    }
    sort($currentLocations);
    
    // Get unique preferred locations
    $prefLocQuery = "SELECT DISTINCT preferred_location FROM candidates WHERE preferred_location IS NOT NULL AND preferred_location != ''";
    $result = $conn->query($prefLocQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $preferredLocations[] = $row['preferred_location'];
        }
    }
    sort($preferredLocations);
    
    // Get work authorization statuses
    $waQuery = "SELECT id, status FROM work_authorization ORDER BY status";
    $result = $conn->query($waQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $workAuthStatuses[] = $row;
        }
    }
    
    // Get assigned users for admin
    $assignedUsers = [];
    if ($current_user_level === 'admin') {
        $userResult = $conn->query("SELECT user_code, name FROM user WHERE level IN ('recruiter', 'manager') ORDER BY name");
        while ($user = $userResult->fetch_assoc()) {
            $assignedUsers[] = $user;
        }
    }
    
    // Get quick stats by lead_type
    $baseQuery = "SELECT COUNT(*) as count, lead_type FROM candidates WHERE 1=1";
    if ($current_user_level === 'recruiter') {
        $baseQuery .= " AND assigned_to = '$current_user_code'";
    }
    $baseQuery .= " GROUP BY lead_type";
    
    $result = $conn->query($baseQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $lead_type = strtolower($row['lead_type'] ?? '');
            if ($lead_type === 'cold') {
                $stats['cold'] = $row['count'];
            } elseif ($lead_type === 'warm') {
                $stats['warm'] = $row['count'];
            } elseif ($lead_type === 'hot') {
                $stats['hot'] = $row['count'];
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

<div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Page Header with Stats -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="mb-1">
                        <i class="bx bx-user-circle"></i> Candidates Database
                    </h2>
                    <p class="text-muted mb-0">Comprehensive talent pool management</p>
                </div>
                <div>
                    <a href="create.php" class="btn btn-primary btn-lg">
                        <i class="bx bx-plus-circle"></i> Add New Candidate
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards - Lead Type Distribution -->
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 fw-semibold text-muted">Total Candidates</p>
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
                            <p class="mb-1 fw-semibold text-muted">Hot Leads</p>
                            <h3 class="mb-0 text-danger"><?php echo number_format($stats['hot']); ?></h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="bx bx-trending-up bx-md"></i>
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
                            <p class="mb-1 fw-semibold text-muted">Warm Leads</p>
                            <h3 class="mb-0 text-warning"><?php echo number_format($stats['warm']); ?></h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="bx bx-line-chart bx-md"></i>
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
                            <p class="mb-1 fw-semibold text-muted">Cold Leads</p>
                            <h3 class="mb-0 text-info"><?php echo number_format($stats['cold']); ?></h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="bx bx-chart bx-md"></i>
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
                <strong>Search & Filter Candidates</strong>
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
                               placeholder="Search by name, email, skills, role, position..."
                               style="border: 2px solid #dee2e6; font-size: 1.1rem;">
                        <small class="text-muted">
                            <i class="bx bx-info-circle"></i> 
                            Search across name, email, skills, role addressed, and current position
                        </small>
                    </div>
                </div>
                
                <!-- Primary Filters (Most Important) -->
                <div class="row g-4 mb-4">
                    
                    <!-- Lead Type (CRITICAL FILTER) -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bx bx-target-lock text-danger"></i> 
                            Lead Type <span class="text-danger">*</span>
                        </label>
                        <select id="lead_type_filter" name="lead_type" class="form-select" style="height: 45px;">
                            <option value="">All Leads</option>
                            <?php foreach ($leadTypes as $type): ?>
                            <option value="<?php echo $type; ?>">
                                <?php echo $type; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Skills -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bx bx-code-alt text-primary"></i> 
                            Skills
                        </label>
                        <select id="skill_set_filter" 
                                name="skill_set[]" 
                                class="form-select"
                                multiple
                                style="height: 45px;">
                            <?php foreach ($skillsList as $skill): ?>
                            <option value="<?php echo htmlspecialchars($skill); ?>">
                                <?php echo htmlspecialchars($skill); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd for multiple</small>
                    </div>
                    
                    <!-- Experience -->
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">
                            <i class="bx bx-briefcase text-info"></i> 
                            Experience
                        </label>
                        <select id="experience_filter" name="experience" class="form-select" style="height: 45px;">
                            <option value="">Any</option>
                            <?php foreach ($experienceRanges as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Notice Period -->
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">
                            <i class="bx bx-time text-warning"></i> 
                            Availability
                        </label>
                        <select id="notice_period_filter" name="notice_period" class="form-select" style="height: 45px;">
                            <option value="">Any</option>
                            <?php foreach ($noticePeriods as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
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
                
                <!-- Advanced Filters -->
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
                        
                        <!-- Lead Type Role -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bx bx-category"></i> Lead Role
                            </label>
                            <select id="lead_type_role_filter" name="lead_type_role" class="form-select">
                                <option value="">All Roles</option>
                                <?php foreach ($leadTypeRoles as $role): ?>
                                <option value="<?php echo $role; ?>"><?php echo $role; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Current Location -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bx bx-map"></i> Current Location
                            </label>
                            <select id="current_location_filter" name="current_location" class="form-select">
                                <option value="">Any Location</option>
                                <?php foreach ($currentLocations as $loc): ?>
                                <option value="<?php echo htmlspecialchars($loc); ?>">
                                    <?php echo htmlspecialchars($loc); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Preferred Location -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bx bx-map-pin"></i> Preferred Location
                            </label>
                            <select id="preferred_location_filter" name="preferred_location" class="form-select">
                                <option value="">Any</option>
                                <?php foreach ($preferredLocations as $loc): ?>
                                <option value="<?php echo htmlspecialchars($loc); ?>">
                                    <?php echo htmlspecialchars($loc); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Work Auth Status -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bx bx-shield"></i> Work Authorization
                            </label>
                            <select id="work_auth_filter" name="work_auth_status" class="form-select">
                                <option value="">All</option>
                                <?php foreach ($workAuthStatuses as $wa): ?>
                                <option value="<?php echo $wa['id']; ?>">
                                    <?php echo htmlspecialchars($wa['status']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Working Status -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bx bx-building"></i> Working Status
                            </label>
                            <select id="working_status_filter" name="current_working_status" class="form-select">
                                <option value="">All</option>
                                <?php foreach ($workingStatuses as $status): ?>
                                <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Follow-up Status -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bx bx-phone"></i> Follow-up
                            </label>
                            <select id="follow_up_filter" name="follow_up" class="form-select">
                                <option value="">All</option>
                                <option value="Done">Done</option>
                                <option value="Not Done">Not Done</option>
                            </select>
                        </div>
                        
                        <!-- Date From -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bx bx-calendar"></i> Created From
                            </label>
                            <input type="date" id="date_from_filter" name="date_from" class="form-control">
                        </div>
                        
                        <!-- Date To -->
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="bx bx-calendar"></i> Created To
                            </label>
                            <input type="date" id="date_to_filter" name="date_to" class="form-control">
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
                            <th style="width: 40%"><strong>CANDIDATE</strong></th>
                            <th style="width: 15%"><strong>LEAD TYPE</strong></th>
                            <th style="width: 15%"><strong>ROLE</strong></th>
                            <th style="width: 15%"><strong>LOCATION</strong></th>
                            <th style="width: 15%" class="text-center"><strong>ACTION</strong></th>
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

<!-- jQuery -->
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
    
    console.log('Initializing candidates list (ACTUAL schema version)...');
    
    // Initialize Select2 for multi-select
    $('#skill_set_filter').select2({
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
                d.token = '<?php echo Auth::token(); ?>';
                
                // All filters based on actual schema
                d.quick_search = $('#quick_search').val();
                d.skill_set = $('#skill_set_filter').val();
                d.experience = $('#experience_filter').val();
                d.lead_type = $('#lead_type_filter').val();
                d.lead_type_role = $('#lead_type_role_filter').val();
                d.current_location = $('#current_location_filter').val();
                d.preferred_location = $('#preferred_location_filter').val();
                d.work_auth_status = $('#work_auth_filter').val();
                d.current_working_status = $('#working_status_filter').val();
                d.notice_period = $('#notice_period_filter').val();
                d.follow_up = $('#follow_up_filter').val();
                d.date_from = $('#date_from_filter').val();
                d.date_to = $('#date_to_filter').val();
                d.assigned_to = $('#assigned_filter').val();
                
                console.log('Filter data:', d);
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables Error:', {xhr, error, thrown});
                alert('Failed to load. Error: ' + (xhr.responseJSON?.error || xhr.statusText));
            },
            dataSrc: function(json) {
                console.log('Response:', json);
                $('#results-count').text(json.recordsFiltered || 0);
                return json.data;
            }
        },
        columns: [
            // Candidate Card
            { 
                data: null,
                render: function(data, type, row) {
                    let skillBadges = '';
                    if (row.skill_set) {
                        const skills = row.skill_set.split(',').slice(0, 3);
                        skills.forEach(skill => {
                            skillBadges += `<span class="badge bg-label-primary me-1">${skill.trim()}</span>`;
                        });
                        if (row.skill_set.split(',').length > 3) {
                            skillBadges += `<span class="badge bg-label-secondary">+${row.skill_set.split(',').length - 3}</span>`;
                        }
                    }
                    
                    const initials = row.candidate_name ? row.candidate_name.split(' ').map(n => n[0]).join('').toUpperCase().substr(0, 2) : 'C';
                    
                    return `
                        <div class="d-flex align-items-start py-2">
                            <div class="avatar avatar-lg me-3">
                                <span class="avatar-initial rounded-circle bg-label-primary fs-4">
                                    ${initials}
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold">${row.candidate_name || 'N/A'}</h6>
                                <div class="text-muted mb-2" style="font-size: 0.9rem;">
                                    ${row.role_addressed || row.current_position || 'No role'} 
                                    ${row.experience ? `• ${row.experience} yrs` : ''}
                                </div>
                                <div>${skillBadges || '<span class="text-muted">No skills</span>'}</div>
                            </div>
                        </div>
                    `;
                }
            },
            
            // Lead Type (IMPORTANT!)
            { 
                data: 'lead_type',
                render: function(data) {
                    const colors = {
                        'Hot': 'danger',
                        'Warm': 'warning',
                        'Cold': 'info',
                        'Blacklist': 'dark'
                    };
                    const color = colors[data] || 'secondary';
                    return `<span class="badge bg-${color}" style="font-size: 0.9rem; padding: 8px 12px;">${data || 'N/A'}</span>`;
                }
            },
            
            // Role
            { 
                data: 'lead_type_role',
                render: function(data) {
                    return data || '<span class="text-muted">N/A</span>';
                }
            },
            
            // Location
            { 
                data: 'current_location',
                render: function(data) {
                    return data || '<span class="text-muted">N/A</span>';
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
                           class="btn btn-primary px-4">
                            <i class="bx bx-user-circle"></i> View
                        </a>
                    `;
                }
            }
        ],
        
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[0, 'asc']],
        language: {
            emptyTable: "No candidates found",
            zeroRecords: "No matches",
            loadingRecords: "Loading..."
        }
    });
    
    // Search form
    $('#candidateSearchForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });
    
    // Reset
    $('#candidateSearchForm').on('reset', function() {
        $('#skill_set_filter').val(null).trigger('change');
        setTimeout(() => table.ajax.reload(), 100);
    });
    
    // Export
    $('#exportBtn').on('click', function() {
        const filters = {
            quick_search: $('#quick_search').val(),
            skill_set: $('#skill_set_filter').val(),
            experience: $('#experience_filter').val(),
            lead_type: $('#lead_type_filter').val(),
            token: '<?php echo Auth::token(); ?>'
        };
        window.location.href = 'handlers/export_handler.php?' + $.param(filters);
    });
});
</script>

<style>
.table tbody tr:hover { background-color: #f8f9fa; }
.skills-container .badge { font-size: 0.8rem; padding: 4px 10px; }
.select2-container--bootstrap-5 .select2-selection { height: 45px !important; padding-top: 8px; }
</style>

<?php
$pageContent = ob_get_clean();
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/includes/sidebar.php';
echo $pageContent;
require_once ROOT_PATH . '/panel/includes/footer.php';
?>
