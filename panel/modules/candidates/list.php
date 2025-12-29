<?php
/**
 * Candidates List - REFACTORED FOR BUSINESS FOCUS
 * Optimized for recruiter workflow with intuitive filtering
 */
require_once __DIR__ . '/../_common.php';
$pageTitle = 'Candidates Database';
$pageDescription = 'Manage and filter your talent pool efficiently';

// Initialize variables for filter options
$skillsList = [];
$leadTypes = ['Hot', 'Warm', 'Cold', 'Blacklist']; // Reordered by priority
$leadTypeRoles = ['Recruitment', 'Payroll', 'Mixed'];
$workingStatuses = ['Employee', 'Freelance(Self)', 'Freelance(Company)', 'Unemployed'];
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
$workAuthStatuses = [];
$stats = ['total' => 0, 'hot' => 0, 'warm' => 0, 'cold' => 0, 'blacklist' => 0];

try {
    $conn = Database::getInstance()->getConnection();
    
    // Get filter options - optimized queries
    $filters = [
        'skills' => "SELECT DISTINCT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(skill_set, ',', numbers.n), ',', -1)) AS skill
                     FROM candidates
                     INNER JOIN (
                         SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                         UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
                     ) numbers 
                     ON CHAR_LENGTH(skill_set) - CHAR_LENGTH(REPLACE(skill_set, ',', '')) >= numbers.n-1
                     WHERE skill_set IS NOT NULL AND skill_set != ''
                     ORDER BY skill",
        
        'locations' => "SELECT DISTINCT current_location 
                        FROM candidates 
                        WHERE current_location IS NOT NULL AND current_location != ''
                        ORDER BY current_location",
        
        'work_auth' => "SELECT id, status 
                        FROM work_authorization 
                        WHERE is_active = 1 
                        ORDER BY 
                            CASE status 
                                WHEN 'EU Citizen/PR holder' THEN 1 
                                WHEN 'Work Permit' THEN 2 
                                WHEN 'Sponsersip reqeuiws' THEN 3 
                                ELSE 4 
                            END"
    ];

    // Execute filter queries
    foreach ($filters as $key => $query) {
        $result = $conn->query($query);
        if ($result) {
            switch ($key) {
                case 'skills':
                    while ($row = $result->fetch_assoc()) {
                        if (!empty(trim($row['skill']))) {
                            $skillsList[] = $row['skill'];
                        }
                    }
                    break;
                case 'locations':
                    while ($row = $result->fetch_assoc()) {
                        $currentLocations[] = $row['current_location'];
                    }
                    break;
                case 'work_auth':
                    while ($row = $result->fetch_assoc()) {
                        $workAuthStatuses[] = $row;
                    }
                    break;
            }
        }
    }
    
    // Get candidate stats by lead type - optimized for performance
    $statsQuery = "SELECT 
                    lead_type, 
                    COUNT(*) AS count 
                   FROM candidates 
                   WHERE 1=1";
    
    if ($user['role'] === 'user') {
        $statsQuery .= " AND assigned_to = '" . $conn->real_escape_string($user['user_code']) . "'";
    }
    
    $statsQuery .= " GROUP BY lead_type";
    
    $statsResult = $conn->query($statsQuery);
    if ($statsResult) {
        while ($row = $statsResult->fetch_assoc()) {
            $type = strtolower($row['lead_type']);
            if (isset($stats[$type])) {
                $stats[$type] = $row['count'];
                $stats['total'] += $row['count'];
            }
        }
    }
    
    // Get last updated timestamp for data freshness indicator
    $timestampQuery = "SELECT MAX(updated_at) as last_update FROM candidates";
    $timestampResult = $conn->query($timestampQuery);
    $lastUpdate = $timestampResult->fetch_assoc()['last_update'];
} catch (Exception $e) {
    error_log('Error loading filter options: ' . $e->getMessage());
}
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header with Actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold"><span class="text-muted fw-light"><i class="bx bx-user-circle me-1"></i>Candidates /</span> Libery</h4>
            <div class="d-flex align-items-center mt-1">
                <span class="badge bg-label-primary me-2"><i class="bx bx-group me-1"></i><?= number_format($stats['total']) ?> Total Candidates</span>
                <small class="text-muted">Last updated: <?= date('M d, Y H:i', strtotime($lastUpdate)) ?></small>
            </div>
        </div>
        <div class="d-flex gap-3">
            <button class="btn btn-outline-secondary" id="saveViewBtn">
                <i class="bx bx-save me-1"></i> Save View
            </button>
            <a href="create.php" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> Add Candidate
            </a>
        </div>
    </div>

    <!-- Business-Critical Stats Summary -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="card border border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-3">
                            <p class="mb-1 fw-medium">Hot Leads</p>
                            <h4 class="mb-0 text-danger fw-bold"><?= number_format($stats['hot']) ?></h4>
                            <small class="text-danger"><?= $stats['total'] ? round(($stats['hot']/$stats['total'])*100, 1) : 0 ?>% of total</small>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="bx bx-flame bx-md"></i>
                            </span>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-danger" style="width: <?= $stats['total'] ? round(($stats['hot']/$stats['total'])*100) : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="card border border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-3">
                            <p class="mb-1 fw-medium">Warm Leads</p>
                            <h4 class="mb-0 text-warning fw-bold"><?= number_format($stats['warm']) ?></h4>
                            <small class="text-warning"><?= $stats['total'] ? round(($stats['warm']/$stats['total'])*100, 1) : 0 ?>% of total</small>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="bx bx-sun bx-md"></i>
                            </span>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-warning" style="width: <?= $stats['total'] ? round(($stats['warm']/$stats['total'])*100) : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
            <div class="card border border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-3">
                            <p class="mb-1 fw-medium">Cold Leads</p>
                            <h4 class="mb-0 text-info fw-bold"><?= number_format($stats['cold']) ?></h4>
                            <small class="text-info"><?= $stats['total'] ? round(($stats['cold']/$stats['total'])*100, 1) : 0 ?>% of total</small>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="bx bx-cloud bx-md"></i>
                            </span>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: <?= $stats['total'] ? round(($stats['cold']/$stats['total'])*100) : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border border-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-3">
                            <p class="mb-1 fw-medium">Blacklisted</p>
                            <h4 class="mb-0 text-dark fw-bold"><?= number_format($stats['blacklist']) ?></h4>
                            <small class="text-dark"><?= $stats['total'] ? round(($stats['blacklist']/$stats['total'])*100, 1) : 0 ?>% of total</small>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-dark">
                                <i class="bx bx-block bx-md"></i>
                            </span>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-dark" style="width: <?= $stats['total'] ? round(($stats['blacklist']/$stats['total'])*100) : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Business-Focused Filter Panel -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bx bx-filter-alt me-2 text-primary"></i>Find the Right Candidate</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" id="toggleAdvancedFilters">
                    <i class="bx bx-slider me-1"></i> Advanced Filters
                </button>
                <button class="btn btn-sm btn-outline-primary" id="resetFilters">
                    <i class="bx bx-reset me-1"></i> Reset
                </button>
            </div>
        </div>
        <div class="card-body">
            <form id="candidateFilterForm">
                <div class="row g-3">
                    <!-- Quick Search - Most Important Field -->
                    <div class="col-lg-6">
                        <label class="form-label fw-semibold">Quick Search</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="text" class="form-control" id="quickSearch" placeholder="Name, skills, position, company..." 
                                   aria-label="Search candidates" data-bs-toggle="tooltip" 
                                   title="Search across candidate names, skills, positions, companies and contact details">
                        </div>
                        <div class="form-text">
                            <i class="bx bx-info-circle me-1"></i>
                            Try: "React developer", "AWS", "Project Manager", "PHP", etc.
                        </div>
                    </div>
                    
                    <!-- Primary Filters Row -->
                    <div class="col-lg-3">
                        <label class="form-label fw-semibold">Lead Status</label>
                        <select class="form-select" id="leadTypeFilter" aria-label="Lead Type">
                            <option value="">All Leads</option>
                            <?php foreach ($leadTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>">
                                    <?= htmlspecialchars($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-3">
                        <label class="form-label fw-semibold">Skills</label>
                        <select class="form-select select2-skill-filter" id="skillsFilter" multiple="multiple" 
                                aria-label="Skills filter" data-placeholder="Select skills...">
                            <?php foreach ($skillsList as $skill): ?>
                                <option value="<?= htmlspecialchars($skill) ?>">
                                    <?= htmlspecialchars($skill) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-3">
                        <label class="form-label fw-semibold">Experience</label>
                        <select class="form-select" id="experienceFilter" aria-label="Experience filter">
                            <option value="">Any Experience</option>
                            <?php foreach ($experienceRanges as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>">
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-3">
                        <label class="form-label fw-semibold">Availability</label>
                        <select class="form-select" id="availabilityFilter" aria-label="Availability filter">
                            <option value="">All Availability</option>
                            <?php foreach ($noticePeriods as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>">
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-3">
                        <label class="form-label fw-semibold">Location</label>
                        <select class="form-select" id="locationFilter" aria-label="Location filter">
                            <option value="">All Locations</option>
                            <?php foreach ($currentLocations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>">
                                    <?= htmlspecialchars($loc) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-3">
                        <label class="form-label fw-semibold">Action Required</label>
                        <select class="form-select" id="actionRequiredFilter" aria-label="Action required filter">
                            <option value="">All Candidates</option>
                            <option value="follow_up_due">Follow-ups Due</option>
                            <option value="interview_scheduled">Interview Scheduled</option>
                            <option value="no_cv">Missing CV</option>
                            <option value="no_contact">No Contact Details</option>
                        </select>
                    </div>
                </div>
                
                <!-- Advanced Filters - Hidden by default -->
                <div class="row g-3 mt-2 advanced-filters d-none">
                    <div class="col-lg-3">
                        <label class="form-label">Work Authorization</label>
                        <select class="form-select" id="workAuthFilter">
                            <option value="">All Types</option>
                            <?php foreach ($workAuthStatuses as $wa): ?>
                                <option value="<?= htmlspecialchars($wa['id']) ?>">
                                    <?= htmlspecialchars($wa['status']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-3">
                        <label class="form-label">Working Status</label>
                        <select class="form-select" id="workingStatusFilter">
                            <option value="">All Statuses</option>
                            <?php foreach ($workingStatuses as $status): ?>
                                <option value="<?= htmlspecialchars($status) ?>">
                                    <?= htmlspecialchars($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-3">
                        <label class="form-label">Role Type</label>
                        <select class="form-select" id="roleTypeFilter">
                            <option value="">All Roles</option>
                            <?php foreach ($leadTypeRoles as $role): ?>
                                <option value="<?= htmlspecialchars($role) ?>">
                                    <?= htmlspecialchars($role) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-3">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="dateFromFilter" placeholder="From">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" id="dateToFilter" placeholder="To">
                        </div>
                    </div>
                    
                    <?php if ($user['role'] === 'admin'): ?>
                    <div class="col-lg-3">
                        <label class="form-label">Assigned To</label>
                        <select class="form-select" id="assignedToFilter">
                            <option value="">All Recruiters</option>
                            <option value="unassigned">Unassigned</option>
                            <?php 
                            // Get assigned users
                            $assignedUsersQuery = "SELECT user_code, full_name FROM users WHERE role IN ('recruiter', 'admin', 'user') AND is_active = 1 ORDER BY full_name";
                            $assignedUsers = $conn->query($assignedUsersQuery);
                            while ($row = $assignedUsers->fetch_assoc()):
                            ?>
                                <option value="<?= htmlspecialchars($row['user_code']) ?>">
                                    <?= htmlspecialchars($row['full_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Candidate Actions Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-sm btn-primary" id="applyFiltersBtn">
                        <i class="bx bx-check me-1"></i> Apply Filters
                    </button>
                    <button class="btn btn-sm btn-outline-success" id="exportBtn">
                        <i class="bx bx-export me-1"></i> Export Results
                    </button>
                    <button class="btn btn-sm btn-outline-info" id="bulkActionBtn" disabled>
                        <i class="bx bx-batch me-1"></i> Bulk Actions
                    </button>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="toggleGridView" checked>
                        <label class="form-check-label" for="toggleGridView">List View</label>
                    </div>
                    <div class="d-flex align-items-center">
                        <label class="form-label me-2 mb-0">Results per page:</label>
                        <select class="form-select form-select-sm w-auto" id="pageSizeSelect" style="width: 80px;">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Candidates Table - Business Critical Information First -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bx bx-list-ul me-2 text-info"></i>Candidates</h5>
            <div class="d-flex align-items-center">
                <span class="me-3 text-muted" id="resultCount">0 results</span>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="viewOptions" data-bs-toggle="dropdown">
                        <i class="bx bx-view me-1"></i> View Options
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" id="saveCurrentView"><i class="bx bx-save me-2"></i>Save Current View</a></li>
                        <li><a class="dropdown-item" href="#" id="loadSavedViews"><i class="bx bx-folder-open me-2"></i>Load Saved View</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" id="customizeColumns"><i class="bx bx-columns me-2"></i>Customize Columns</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover" id="candidatesTable">
                    <thead>
                        <tr>
                            <th class="pe-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                </div>
                            </th>
                            <th>CANDIDATE <i class="bx bx-sort ms-1 text-muted"></i></th>
                            <th>LEAD STATUS</th>
                            <th>SKILLS</th>
                            <th>EXPERIENCE</th>
                            <th>AVAILABILITY</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="candidatesTableBody">
                        <!-- Table content will be loaded via JavaScript -->
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="mb-3">
                                    <i class="bx bx-search-alt display-3 text-muted"></i>
                                </div>
                                <h5 class="mb-1">No candidates found</h5>
                                <p class="text-muted mb-0">Apply filters to search candidates or <a href="create.php" class="text-primary">add a new candidate</a></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="d-flex align-items-center">
                    <span class="me-2">Showing</span>
                    <select class="form-select form-select-sm w-auto" id="pageSelect" style="width: 70px;">
                        <!-- Will be populated by JavaScript -->
                    </select>
                    <span class="ms-2 me-3">of <span id="totalPages">0</span> pages</span>
                </div>
                <nav>
                    <ul class="pagination mb-0" id="pagination">
                        <!-- Will be populated by JavaScript -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Candidate Quick View Modal -->
<div class="modal fade" id="candidateQuickViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="candidateNameModal"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-4">
                            <h6 class="mb-3"><i class="bx bx-briefcase me-2 text-primary"></i>Professional Details</h6>
                            <div class="row">
                                <div class="col-sm-6 mb-3">
                                    <small class="text-muted">Current Position</small>
                                    <p class="mb-0" id="currentPositionModal"></p>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <small class="text-muted">Current Employer</small>
                                    <p class="mb-0" id="currentEmployerModal"></p>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <small class="text-muted">Experience</small>
                                    <p class="mb-0" id="experienceModal"></p>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <small class="text-muted">Availability</small>
                                    <p class="mb-0" id="availabilityModal"></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="mb-3"><i class="bx bx-spreadsheet me-2 text-primary"></i>Skills & Languages</h6>
                            <div id="skillsContainerModal" class="d-flex flex-wrap gap-2 mb-3">
                                <!-- Skills will be populated here -->
                            </div>
                            <div id="languagesContainerModal" class="d-flex flex-wrap gap-2">
                                <!-- Languages will be populated here -->
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-4 text-center">
                            <div class="avatar avatar-xl mb-3">
                                <span class="avatar-initial rounded-circle bg-label-primary fs-2" id="candidateInitials"></span>
                            </div>
                            <div class="mb-3">
                                <span class="badge bg-label-danger px-3 py-2" id="leadTypeBadgeModal" style="font-size: 0.9rem;">Hot Lead</span>
                            </div>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="#" class="btn btn-sm btn-icon btn-primary" id="emailCandidateBtn">
                                    <i class="bx bx-envelope"></i>
                                </a>
                                <a href="#" class="btn btn-sm btn-icon btn-primary" id="callCandidateBtn">
                                    <i class="bx bx-phone"></i>
                                </a>
                                <a href="#" class="btn btn-sm btn-icon btn-primary" id="messageCandidateBtn">
                                    <i class="bx bx-message"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="mb-3"><i class="bx bx-contact me-2 text-primary"></i>Contact Details</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="bx bx-envelope me-2 text-muted"></i>
                                    <span id="emailModal"></span>
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-phone me-2 text-muted"></i>
                                    <span id="phoneModal"></span>
                                </li>
                                <li class="mb-2">
                                    <i class="bx bxl-linkedin me-2 text-muted"></i>
                                    <a href="#" id="linkedinModal" class="text-primary"></a>
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-map me-2 text-muted"></i>
                                    <span id="locationModal"></span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="mb-3"><i class="bx bx-money me-2 text-primary"></i>Compensation</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <small class="text-muted">Current</small>
                                    <p class="mb-0 fw-semibold" id="currentCompensationModal"></p>
                                </li>
                                <li class="mb-2">
                                    <small class="text-muted">Expected</small>
                                    <p class="mb-0 fw-semibold" id="expectedCompensationModal"></p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="viewFullProfileBtn">View Full Profile</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<script>
$(document).ready(function() {
    // Initialize Select2 for skills filter
    $('.select2-skill-filter').select2({
        theme: "bootstrap-5",
        width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
        placeholder: $(this).data('placeholder'),
        closeOnSelect: false,
        allowClear: true
    });
    
    // Toggle Advanced Filters
    $('#toggleAdvancedFilters').on('click', function() {
        $('.advanced-filters').toggleClass('d-none');
        $(this).find('i').toggleClass('bx-slider bx-up-arrow-alt');
        $(this).text($('.advanced-filters').hasClass('d-none') ? 'Advanced Filters' : 'Hide Filters');
    });
    
    // Reset Filters
    $('#resetFilters').on('click', function() {
        $('#candidateFilterForm')[0].reset();
        $('.select2-skill-filter').val(null).trigger('change');
        loadCandidates(1); // Reload table
    });
    
    // Apply Filters Button
    $('#applyFiltersBtn').on('click', function() {
        loadCandidates(1); // Reload from page 1
    });
    
    // Quick Search with debounce
    let searchTimeout;
    $('#quickSearch').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            loadCandidates(1);
        }, 500);
    });
    
    // Select/Deselect all candidates
    $('#selectAll').on('change', function() {
        $('.candidate-checkbox').prop('checked', $(this).prop('checked'));
        $('#bulkActionBtn').prop('disabled', !$(this).prop('checked'));
    });
    
    // Enable bulk actions when any candidate is selected
    $(document).on('change', '.candidate-checkbox', function() {
        const anySelected = $('.candidate-checkbox:checked').length > 0;
        $('#selectAll').prop('checked', anySelected && $('.candidate-checkbox').length === $('.candidate-checkbox:checked').length);
        $('#bulkActionBtn').prop('disabled', !anySelected);
    });
    
    // Grid/List view toggle
    $('#toggleGridView').on('change', function() {
        // This would toggle between list and grid/card view in a real implementation
        if ($(this).is(':checked')) {
            $(this).next('label').text('List View');
        } else {
            $(this).next('label').text('Grid View');
        }
    });
    
    // Page size change
    $('#pageSizeSelect').on('change', function() {
        loadCandidates(1); // Reload from page 1 with new page size
    });
    
    // Initial load
    loadCandidates(1);
    
    /**
     * Load candidates with current filters
     * @param {number} page - Page number to load
     */
    function loadCandidates(page = 1) {
        const pageSize = parseInt($('#pageSizeSelect').val());
        const filters = getFilters();
        
        // Show loading state
        $('#candidatesTableBody').html(`
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading candidates...</p>
                </td>
            </tr>
        `);
        
        $.ajax({
            url: 'handlers/candidate_data_handler.php',
            type: 'POST',
            data: {
                page: page,
                page_size: pageSize,
                filters: filters,
                token: '<?= Auth::token() ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderCandidates(response.data, response.total, page, pageSize);
                } else {
                    showError(response.message || 'Failed to load candidates');
                }
            },
            error: function(xhr) {
                showError('Error loading candidates: ' + (xhr.responseJSON?.message || xhr.statusText));
            }
        });
    }
    
    /**
     * Get current filter values
     */
    function getFilters() {
        return {
            quick_search: $('#quickSearch').val().trim(),
            lead_type: $('#leadTypeFilter').val() || null,
            skill_set: $('#skillsFilter').val() || [],
            experience: $('#experienceFilter').val() || null,
            notice_period: $('#availabilityFilter').val() || null,
            current_location: $('#locationFilter').val() || null,
            work_auth_status: $('#workAuthFilter').val() || null,
            current_working_status: $('#workingStatusFilter').val() || null,
            lead_type_role: $('#roleTypeFilter').val() || null,
            action_required: $('#actionRequiredFilter').val() || null,
            date_from: $('#dateFromFilter').val() || null,
            date_to: $('#dateToFilter').val() || null,
            assigned_to: $('#assignedToFilter').val() || null
        };
    }
    
    /**
     * Render candidates table
     */
    function renderCandidates(candidates, totalRecords, currentPage, pageSize) {
        if (candidates.length === 0) {
            $('#candidatesTableBody').html(`
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="mb-3">
                            <i class="bx bx-search-alt display-3 text-muted"></i>
                        </div>
                        <h5 class="mb-1">No candidates match your filters</h5>
                        <p class="text-muted mb-0">Try adjusting your filters or <a href="create.php" class="text-primary">add a new candidate</a></p>
                    </td>
                </tr>
            `);
            updatePagination(0, currentPage, pageSize);
            $('#resultCount').text('0 results');
            return;
        }
        
        let tableRows = '';
        candidates.forEach(candidate => {
            // Determine lead type badge class
            let leadTypeClass = 'bg-secondary';
            let leadTypeIcon = 'bx bx-user';
            switch(candidate.lead_type) {
                case 'Hot': leadTypeClass = 'bg-danger'; leadTypeIcon = 'bx bx-flame'; break;
                case 'Warm': leadTypeClass = 'bg-warning'; leadTypeIcon = 'bx bx-sun'; break;
                case 'Cold': leadTypeClass = 'bg-info'; leadTypeIcon = 'bx bx-cloud'; break;
                case 'Blacklist': leadTypeClass = 'bg-dark'; leadTypeIcon = 'bx bx-block'; break;
            }
            
            // Parse skills for display
            let skills = [];
            if (candidate.skill_set) {
                skills = candidate.skill_set.split(',').map(s => s.trim()).filter(s => s);
            }
            
            // Format experience
            const experienceText = candidate.experience ? `${parseFloat(candidate.experience).toFixed(1)} years` : 'N/A';
            
            // Format availability
            let availabilityText = 'Unknown';
            if (candidate.notice_period !== null && candidate.notice_period !== '') {
                if (candidate.notice_period == 0) {
                    availabilityText = 'Immediate';
                } else {
                    availabilityText = `${candidate.notice_period} days notice`;
                }
            }
            
            // Format location
            const locationText = candidate.current_location || 'No location set';
            
            // Generate skills badges
            let skillsBadges = '';
            const displayedSkills = skills.slice(0, 3);
            displayedSkills.forEach(skill => {
                skillsBadges += `<span class="badge bg-label-primary me-1">${skill}</span>`;
            });
            if (skills.length > 3) {
                skillsBadges += `<span class="badge bg-label-secondary">+${skills.length - 3}</span>`;
            }
            
            // Candidate initials
            const initials = candidate.candidate_name ? 
                candidate.candidate_name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : 'CN';
            
            tableRows += `
                <tr class="align-middle">
                    <td>
                        <div class="form-check">
                            <input class="form-check-input candidate-checkbox" type="checkbox" value="${candidate.can_code}">
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-3">
                                <span class="avatar-initial rounded-circle bg-label-primary">${initials}</span>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-medium">
                                    <a href="view.php?id=${candidate.can_code}" class="text-body text-decoration-none">
                                        ${candidate.candidate_name || 'Unnamed Candidate'}
                                    </a>
                                </h6>
                                <small class="text-muted d-block">${candidate.current_position || 'No position set'}</small>
                                <small class="text-muted">
                                    ${candidate.role_addressed ? `<span class="badge bg-label-info">${candidate.role_addressed}</span>` : ''}
                                </small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge ${leadTypeClass} px-3 py-2 me-1">
                            <i class="${leadTypeIcon} me-1"></i> ${candidate.lead_type || 'N/A'}
                        </span>
                        ${candidate.candidate_rating ? `
                        <span class="text-warning ms-2">
                            ${'★'.repeat(Math.round(candidate.candidate_rating))}${'☆'.repeat(5 - Math.round(candidate.candidate_rating))}
                        </span>` : ''}
                    </td>
                    <td>
                        <div class="skills-container">
                            ${skillsBadges || '<span class="text-muted">No skills</span>'}
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-label-info">${experienceText}</span>
                    </td>
                    <td>
                        <span class="badge ${candidate.notice_period == 0 ? 'bg-success' : 'bg-warning'}">
                            ${availabilityText}
                        </span>
                    </td>
                    <td>
                        <span class="text-muted">${locationText}</span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm btn-icon btn-outline-primary me-2 view-details" 
                                    data-bs-toggle="modal" data-bs-target="#candidateQuickViewModal"
                                    data-candidate='${JSON.stringify(candidate)}'>
                                <i class="bx bx-show-alt"></i>
                            </button>
                            <a href="view.php?id=${candidate.can_code}" class="btn btn-sm btn-icon btn-primary">
                                <i class="bx bx-user-circle"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        $('#candidatesTableBody').html(tableRows);
        updatePagination(totalRecords, currentPage, pageSize);
        $('#resultCount').text(`${totalRecords} ${totalRecords === 1 ? 'result' : 'results'}`);
        
        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
    }
    
    /**
     * Update pagination controls
     */
    function updatePagination(totalRecords, currentPage, pageSize) {
        const totalPages = Math.ceil(totalRecords / pageSize);
        $('#totalPages').text(totalPages);
        
        // Update page select dropdown
        let pageOptions = '';
        for (let i = 1; i <= totalPages; i++) {
            pageOptions += `<option value="${i}" ${i === currentPage ? 'selected' : ''}>${i}</option>`;
        }
        $('#pageSelect').html(pageOptions || '<option value="1">1</option>');
        
        // Update pagination navigation
        let paginationHtml = '';
        if (currentPage > 1) {
            paginationHtml += `
                <li class="page-item prev">
                    <a class="page-link" href="javascript:void(0);" data-page="${currentPage - 1}">
                        <i class="tf-icon bx bx-chevron-left"></i>
                    </a>
                </li>
            `;
        }
        
        // Show page numbers (max 5 visible)
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0);" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        if (currentPage < totalPages) {
            paginationHtml += `
                <li class="page-item next">
                    <a class="page-link" href="javascript:void(0);" data-page="${currentPage + 1}">
                        <i class="tf-icon bx bx-chevron-right"></i>
                    </a>
                </li>
            `;
        }
        
        $('#pagination').html(paginationHtml || '<li class="page-item disabled"><span class="page-link">No pages</span></li>');
        
        // Add event listeners to pagination links
        $('.page-link').on('click', function() {
            const page = parseInt($(this).data('page'));
            if (!isNaN(page)) {
                loadCandidates(page);
            }
        });
        
        // Page select change
        $('#pageSelect').off('change').on('change', function() {
            loadCandidates(parseInt($(this).val()));
        });
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        $('#candidatesTableBody').html(`
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="mb-3">
                        <i class="bx bx-error display-3 text-danger"></i>
                    </div>
                    <h5 class="mb-1">Error loading candidates</h5>
                    <p class="text-muted mb-0">${message}</p>
                    <button class="btn btn-sm btn-primary mt-3" id="retryLoadBtn">Try Again</button>
                </td>
            </tr>
        `);
        
        $('#retryLoadBtn').on('click', function() {
            loadCandidates(1);
        });
    }
    
    /**
     * Initialize Quick View Modal with candidate data
     */
    $(document).on('click', '.view-details', function() {
        const candidate = $(this).data('candidate');
        if (!candidate) return;
        
        // Set modal content
        $('#candidateNameModal').text(candidate.candidate_name || 'Unnamed Candidate');
        $('#currentPositionModal').text(candidate.current_position || 'Not specified');
        $('#currentEmployerModal').text(candidate.current_employer || 'Not specified');
        $('#experienceModal').text(candidate.experience ? `${candidate.experience} years` : 'Not specified');
        
        // Availability
        let availabilityText = 'Unknown';
        if (candidate.notice_period !== null && candidate.notice_period !== '') {
            if (candidate.notice_period == 0) {
                availabilityText = 'Immediate';
            } else {
                availabilityText = `${candidate.notice_period} days notice`;
            }
        }
        $('#availabilityModal').text(availabilityText);
        
        // Skills
        let skillsHtml = '';
        if (candidate.skill_set) {
            const skills = candidate.skill_set.split(',').map(s => s.trim()).filter(s => s);
            skills.forEach(skill => {
                skillsHtml += `<span class="badge bg-primary me-1">${skill}</span>`;
            });
        }
        $('#skillsContainerModal').html(skillsHtml || '<span class="text-muted">No skills specified</span>');
        
        // Contact details
        $('#emailModal').text(candidate.email_id || 'Not provided');
        $('#phoneModal').text(candidate.contact_details || 'Not provided');
        $('#linkedinModal').text(candidate.linkedin ? 'View Profile' : 'Not provided');
        if (candidate.linkedin) {
            $('#linkedinModal').attr('href', candidate.linkedin).attr('target', '_blank');
        }
        $('#locationModal').text(candidate.current_location || 'Not specified');
        
        // Compensation
        if (candidate.current_salary) {
            $('#currentCompensationModal').html(`€${parseInt(candidate.current_salary).toLocaleString()} <small class="text-muted">(annual)</small>`);
        } else if (candidate.current_daily_rate) {
            $('#currentCompensationModal').html(`€${parseInt(candidate.current_daily_rate).toLocaleString()} <small class="text-muted">(daily)</small>`);
        } else {
            $('#currentCompensationModal').text('Not specified');
        }
        
        if (candidate.expected_salary) {
            $('#expectedCompensationModal').html(`€${parseInt(candidate.expected_salary).toLocaleString()} <small class="text-muted">(annual)</small>`);
        } else if (candidate.expected_daily_rate) {
            $('#expectedCompensationModal').html(`€${parseInt(candidate.expected_daily_rate).toLocaleString()} <small class="text-muted">(daily)</small>`);
        } else {
            $('#expectedCompensationModal').text('Not specified');
        }
        
        // Lead type badge
        let leadTypeClass = 'bg-label-secondary';
        let leadTypeText = candidate.lead_type || 'N/A';
        switch(candidate.lead_type) {
            case 'Hot': leadTypeClass = 'bg-label-danger'; break;
            case 'Warm': leadTypeClass = 'bg-label-warning'; break;
            case 'Cold': leadTypeClass = 'bg-label-info'; break;
            case 'Blacklist': leadTypeClass = 'bg-label-dark'; break;
        }
        $('#leadTypeBadgeModal')
            .removeClass('bg-label-danger bg-label-warning bg-label-info bg-label-dark bg-label-secondary')
            .addClass(leadTypeClass)
            .text(leadTypeText);
        
        // Candidate initials
        const initials = candidate.candidate_name ? 
            candidate.candidate_name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : 'CN';
        $('#candidateInitials').text(initials);
        
        // Set view full profile button
        $('#viewFullProfileBtn').data('candidate-id', candidate.can_code);
    });
    
    // View full profile button
    $('#viewFullProfileBtn').on('click', function() {
        const candidateId = $(this).data('candidate-id');
        if (candidateId) {
            window.location.href = `view.php?id=${candidateId}`;
        }
    });
    
    // Export functionality
    $('#exportBtn').on('click', function() {
        const filters = getFilters();
        window.location.href = `handlers/export_handler.php?filters=${encodeURIComponent(JSON.stringify(filters))}&token=<?= Auth::token() ?>`;
    });
    
    // Save current view
    $('#saveViewBtn, #saveCurrentView').on('click', function() {
        const viewName = prompt('Enter a name for this view:');
        if (viewName) {
            const filters = getFilters();
            $.post('handlers/save_candidate_view.php', {
                view_name: viewName,
                filters: filters,
                token: '<?= Auth::token() ?>'
            }, function(response) {
                if (response.success) {
                    showToast('View saved successfully!', 'success');
                } else {
                    showToast('Error saving view: ' + response.message, 'error');
                }
            });
        }
    });
    
    // Show toast notification
    function showToast(message, type = 'info') {
        const toastId = 'toast-' + Date.now();
        const toastContainer = $('#toastContainer');
        
        if (toastContainer.length === 0) {
            $('body').append('<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
        }
        
        let bgColor, icon;
        switch(type) {
            case 'success':
                bgColor = 'bg-success';
                icon = 'bx bx-check-circle';
                break;
            case 'error':
                bgColor = 'bg-danger';
                icon = 'bx bx-error-circle';
                break;
            case 'warning':
                bgColor = 'bg-warning';
                icon = 'bx bx-error';
                break;
            default:
                bgColor = 'bg-info';
                icon = 'bx bx-info-circle';
        }
        
        $(`#toastContainer`).append(`
            <div id="${toastId}" class="toast ${bgColor} text-white" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header ${bgColor}">
                    <i class="${icon} me-2"></i>
                    <strong class="me-auto">Notification</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `);
        
        const toast = new bootstrap.Toast(document.getElementById(toastId));
        toast.show();
        
        // Auto remove after hidden
        $(`#${toastId}`).on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
});
</script>

<style>
.avatar-initial {
    font-weight: 500;
}
.skills-container .badge {
    font-size: 0.8rem;
    padding: 0.35em 0.6em;
}
.select2-container--bootstrap-5 .select2-selection {
    height: 38px !important;
    line-height: 1.5 !important;
}
/* Modal styling */
#candidateQuickViewModal .modal-header {
    background-color: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}
/* Custom pagination */
.pagination {
    --bs-pagination-active-bg: #696cff;
    --bs-pagination-active-border-color: #696cff;
}
/* Responsive adjustments */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.9rem;
    }
    .table th, .table td {
        padding: 0.5rem !important;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.85rem !important;
    }
}
</style>

<?php
$pageContent = ob_get_clean();
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/includes/sidebar.php';
echo $pageContent;
require_once ROOT_PATH . '/panel/includes/footer.php';
?>
