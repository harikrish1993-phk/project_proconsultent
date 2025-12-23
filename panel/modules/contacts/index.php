<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';
$pageTitle = 'Contact Management';
$breadcrumbs = [
    'Contact' => '#'
];

// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// Display breadcrumb
echo renderBreadcrumb($breadcrumbs);
$db = Database::getInstance();
$conn = $db->getConnection();
$userId = Auth::userId();
$userRole = Auth::userRole();

// Fetch statistics
try {
    // Total contacts by status
    $statsQuery = "SELECT status, COUNT(*) as count FROM contacts WHERE is_archived = 0 GROUP BY status";
    $statsResult = $conn->query($statsQuery);
    $stats = [];
    while ($row = $statsResult->fetch_assoc()) {
        $stats[$row['status']] = $row['count'];
    }
    
    // My contacts count
    $myContactsQuery = "SELECT COUNT(*) as count FROM contacts WHERE assigned_to = ? AND is_archived = 0";
    $stmt = $conn->prepare($myContactsQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $myContactsCount = $stmt->get_result()->fetch_assoc()['count'];
    
    // Follow-ups today
    $followUpTodayQuery = "SELECT COUNT(*) as count FROM contacts 
                          WHERE next_follow_up = CURDATE() 
                          AND is_archived = 0 
                          AND status NOT IN ('converted', 'not_interested')";
    $followUpToday = $conn->query($followUpTodayQuery)->fetch_assoc()['count'];
    
    // Overdue follow-ups
    $overdueQuery = "SELECT COUNT(*) as count FROM contacts 
                    WHERE next_follow_up < CURDATE() 
                    AND is_archived = 0 
                    AND status NOT IN ('converted', 'not_interested')";
    $overdueCount = $conn->query($overdueQuery)->fetch_assoc()['count'];
    
    // This week's conversions
    $conversionsQuery = "SELECT COUNT(*) as count FROM contacts 
                        WHERE converted_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    $conversionsCount = $conn->query($conversionsQuery)->fetch_assoc()['count'];
    
    // Recent contacts
    $recentQuery = "SELECT c.*, 
                          CONCAT(u.first_name, ' ', u.last_name) as recruiter_name,
                          cs.status_label, cs.status_color
                   FROM contacts c
                   LEFT JOIN users u ON c.assigned_to = u.user_id
                   LEFT JOIN contact_statuses cs ON c.status = cs.status_value
                   WHERE c.is_archived = 0
                   ORDER BY c.created_at DESC
                   LIMIT 10";
    $recentContacts = $conn->query($recentQuery);
    
} catch (Exception $e) {
    $error = "Error loading dashboard: " . $e->getMessage();
}
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">Recruitment /</span> Contacts Pipeline
            </h4>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Stats Cards Row 1 -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="d-block mb-1 text-muted">New</span>
                            <h3 class="card-title mb-0"><?php echo $stats['new'] ?? 0; ?></h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="bx bx-user-plus bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="d-block mb-1 text-muted">Contacted</span>
                            <h3 class="card-title mb-0"><?php echo $stats['contacted'] ?? 0; ?></h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="bx bx-phone bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="d-block mb-1 text-muted">Interested</span>
                            <h3 class="card-title mb-0"><?php echo $stats['interested'] ?? 0; ?></h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="bx bx-star bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="d-block mb-1 text-muted">Converted</span>
                            <h3 class="card-title mb-0 text-success"><?php echo $stats['converted'] ?? 0; ?></h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="bx bx-check-circle bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="d-block mb-1 text-muted">My Contacts</span>
                            <h3 class="card-title mb-0"><?php echo $myContactsCount; ?></h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-secondary">
                                <i class="bx bx-user bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card <?php echo $followUpToday > 0 ? 'border-warning' : ''; ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="d-block mb-1 text-muted">Today</span>
                            <h3 class="card-title mb-0 <?php echo $followUpToday > 0 ? 'text-warning' : ''; ?>">
                                <?php echo $followUpToday; ?>
                            </h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="bx bx-alarm bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards Row 2 -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card <?php echo $overdueCount > 0 ? 'border-danger' : ''; ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="d-block mb-2">Overdue Follow-ups</span>
                            <h4 class="card-title mb-1 <?php echo $overdueCount > 0 ? 'text-danger' : ''; ?>">
                                <?php echo $overdueCount; ?>
                            </h4>
                            <small class="text-muted">Requires immediate attention</small>
                        </div>
                        <div class="avatar avatar-lg">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="bx bx-error bx-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="d-block mb-2">This Week's Conversions</span>
                            <h4 class="card-title mb-1"><?php echo $conversionsCount; ?></h4>
                            <small class="text-success">
                                <i class="bx bx-trending-up"></i> Converting to candidates
                            </small>
                        </div>
                        <div class="avatar avatar-lg">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="bx bx-transfer bx-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="d-block mb-2">Total Active Pipeline</span>
                            <h4 class="card-title mb-1 text-white">
                                <?php 
                                    $total = array_sum(array_filter($stats, function($key) {
                                        return !in_array($key, ['converted', 'not_interested']);
                                    }, ARRAY_FILTER_USE_KEY));
                                    echo $total;
                                ?>
                            </h4>
                            <small class="text-white-50">Excluding converted & not interested</small>
                        </div>
                        <div class="avatar avatar-lg">
                            <span class="avatar-initial rounded bg-white">
                                <i class="bx bx-trending-up bx-lg text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-lg-8 col-md-12 mb-3 mb-lg-0">
                    <a href="create.php" class="btn btn-primary me-2">
                        <i class="bx bx-plus me-1"></i> Add New Contact
                    </a>
                    <a href="list.php?filter=my" class="btn btn-outline-secondary me-2">
                        <i class="bx bx-user me-1"></i> My Contacts
                    </a>
                    <a href="followups.php" class="btn btn-outline-warning me-2">
                        <i class="bx bx-calendar me-1"></i> Follow-ups
                        <?php if ($overdueCount > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo $overdueCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="list.php" class="btn btn-outline-info">
                        <i class="bx bx-list-ul me-1"></i> View All
                    </a>
                </div>
                <div class="col-lg-4 col-md-12">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Quick search contacts..." id="quickSearch">
                        <button class="btn btn-outline-secondary" type="button" onclick="performQuickSearch()">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Contacts -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Contacts</h5>
            <a href="list.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Contact</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Skills</th>
                            <th>Assigned To</th>
                            <th>Follow-up</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentContacts && $recentContacts->num_rows > 0): ?>
                            <?php while ($contact = $recentContacts->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <a href="view.php?id=<?php echo $contact['contact_id']; ?>" class="text-decoration-none">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded-circle bg-label-primary">
                                                    <?php echo strtoupper(substr($contact['first_name'], 0, 1)); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($contact['email']); ?></small>
                                            </div>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-label-secondary">
                                        <?php echo ucfirst(str_replace('_', ' ', $contact['source'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $contact['status_color'] ?? 'secondary'; ?>">
                                        <?php echo $contact['status_label'] ?? ucfirst($contact['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        <?php 
                                            $skills = json_decode($contact['skills'], true);
                                            if (is_array($skills) && !empty($skills)) {
                                                echo htmlspecialchars(implode(', ', array_slice($skills, 0, 3)));
                                                if (count($skills) > 3) echo '...';
                                            } else {
                                                echo '<span class="text-muted">N/A</span>';
                                            }
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($contact['recruiter_name']): ?>
                                        <small><?php echo htmlspecialchars($contact['recruiter_name']); ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-label-warning">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($contact['next_follow_up']): ?>
                                        <?php 
                                            $followUpDate = strtotime($contact['next_follow_up']);
                                            $today = strtotime('today');
                                            $class = 'text-muted';
                                            if ($followUpDate < $today) $class = 'text-danger fw-bold';
                                            elseif ($followUpDate == $today) $class = 'text-warning fw-bold';
                                        ?>
                                        <small class="<?php echo $class; ?>">
                                            <?php echo date('M d, Y', $followUpDate); ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">Not set</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="view.php?id=<?php echo $contact['contact_id']; ?>">
                                                <i class="bx bx-show me-1"></i> View
                                            </a>
                                            <a class="dropdown-item" href="edit.php?id=<?php echo $contact['contact_id']; ?>">
                                                <i class="bx bx-edit me-1"></i> Edit
                                            </a>
                                            <?php if ($contact['status'] !== 'converted'): ?>
                                            <a class="dropdown-item" href="convert.php?id=<?php echo $contact['contact_id']; ?>">
                                                <i class="bx bx-transfer me-1"></i> Convert
                                            </a>
                                            <?php endif; ?>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="#" onclick="deleteContact(<?php echo $contact['contact_id']; ?>); return false;">
                                                <i class="bx bx-trash me-1"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bx bx-info-circle bx-lg mb-2"></i>
                                    <p>No contacts yet. <a href="create.php">Add your first contact</a></p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Quick search
function performQuickSearch() {
    const query = document.getElementById('quickSearch').value;
    if (query.trim()) {
        window.location.href = 'list.php?search=' + encodeURIComponent(query);
    }
}

document.getElementById('quickSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        performQuickSearch();
    }
});

// Delete contact
function deleteContact(id) {
    if (confirm('Are you sure you want to delete this contact? This action cannot be undone.')) {
        fetch('handlers/delete_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ contact_id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Network error: ' + error);
        });
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>