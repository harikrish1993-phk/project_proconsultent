<?php
/**
 * MODULE PAGE TEMPLATE
 * 
 * Use this template for ALL module pages to ensure consistent, working behavior.
 * 
 * IMPORTANT PATTERN:
 * 1. Load bootstrap
 * 2. Configure page
 * 3. Process actions/forms
 * 4. Fetch data
 * 5. Start output buffer
 * 6. Generate HTML
 * 7. Get buffered content
 * 8. Load header/sidebar
 * 9. Output content
 * 10. Load footer
 */

// ============================================================================
// STEP 1: LOAD BOOTSTRAP
// ============================================================================
require_once __DIR__ . '/../_common.php';

// Optional: Require specific permission level
// requireAdmin();  // For admin-only pages
// requireLevel('recruiter');  // For recruiter+ pages

// ============================================================================
// STEP 2: PAGE CONFIGURATION
// ============================================================================
$pageTitle = 'Module Name';  // Will appear in browser title and header
$pageDescription = 'Brief description';  // Optional
$breadcrumbs = [
    'Home' => '/panel/route.php',
    'Module' => '/panel/modules/modulename/index.php',
    'Current Page' => ''
];

// ============================================================================
// STEP 3: PROCESS ACTIONS (POST/GET HANDLERS)
// ============================================================================
// Handle form submissions, actions, etc. BEFORE any HTML output
$message = '';
$messageType = '';  // 'success', 'error', 'warning', 'info'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Example: Handle form submission
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        try {
            $conn = getDB();
            
            // Validate input
            $name = sanitize($_POST['name'] ?? '');
            if (empty($name)) {
                throw new Exception('Name is required');
            }
            
            // Insert data
            $stmt = $conn->prepare("INSERT INTO tablename (name, created_by, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param('ss', $name, $current_user_code);
            
            if ($stmt->execute()) {
                $message = 'Record created successfully';
                $messageType = 'success';
                
                // Log activity
                logActivity('CREATE', 'MODULE_NAME', ['name' => $name], $conn->insert_id);
                
                // Optional: Redirect after success
                // redirect('/panel/modules/modulename/list.php');
            } else {
                throw new Exception('Database error: ' . $conn->error);
            }
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
            error_log('Module error: ' . $e->getMessage());
        }
    }
}

// Handle GET actions (delete, status change, etc.)
if (isset($_GET['action'])) {
    try {
        $conn = getDB();
        
        switch ($_GET['action']) {
            case 'delete':
                $id = intval($_GET['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $conn->prepare("DELETE FROM tablename WHERE id = ?");
                    $stmt->bind_param('i', $id);
                    
                    if ($stmt->execute()) {
                        $message = 'Record deleted successfully';
                        $messageType = 'success';
                        logActivity('DELETE', 'MODULE_NAME', [], $id);
                    }
                }
                break;
                
            case 'toggle_status':
                $id = intval($_GET['id'] ?? 0);
                // Handle status toggle
                break;
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// ============================================================================
// STEP 4: FETCH DATA
// ============================================================================
$records = [];
$stats = [];

try {
    $conn = getDB();
    
    // Example: Fetch records
    $query = "SELECT * FROM tablename WHERE 1=1";
    
    // Apply filters if needed
    if (isset($_GET['filter'])) {
        $filter = sanitize($_GET['filter']);
        $query .= " AND column LIKE '%" . $conn->real_escape_string($filter) . "%'";
    }
    
    $query .= " ORDER BY created_at DESC LIMIT 100";
    
    $result = safeQuery($conn, $query, 'fetch_records');
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }
    
    // Example: Fetch statistics
    $statsResult = safeQuery($conn, "SELECT COUNT(*) as total FROM tablename", 'stats');
    if ($statsResult) {
        $stats = $statsResult->fetch_assoc();
    }
    
} catch (Exception $e) {
    $message = 'Error fetching data: ' . $e->getMessage();
    $messageType = 'error';
    error_log('Data fetch error: ' . $e->getMessage());
}

// ============================================================================
// STEP 5: START OUTPUT BUFFER FOR PAGE CONTENT
// ============================================================================
ob_start();
?>

<!-- ======================================================================= -->
<!-- STEP 6: PAGE HTML CONTENT -->
<!-- ======================================================================= -->

<div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Page Header -->
    <div class="page-header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <?php if (!empty($pageDescription)): ?>
        <p><?php echo htmlspecialchars($pageDescription); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Alert Messages -->
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php if ($messageType === 'success'): ?>
            <i class="bx bx-check-circle"></i>
        <?php elseif ($messageType === 'error'): ?>
            <i class="bx bx-error-circle"></i>
        <?php elseif ($messageType === 'warning'): ?>
            <i class="bx bx-error"></i>
        <?php else: ?>
            <i class="bx bx-info-circle"></i>
        <?php endif; ?>
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- Bootstrap Errors (if any) -->
    <?php if (defined('BOOTSTRAP_ERROR_HTML')): ?>
        <?php echo BOOTSTRAP_ERROR_HTML; ?>
    <?php endif; ?>
    
    <!-- Action Buttons -->
    <div class="mb-4">
        <a href="create.php" class="btn btn-primary">
            <i class="bx bx-plus"></i> Add New
        </a>
        <a href="list.php" class="btn btn-outline-secondary">
            <i class="bx bx-list-ul"></i> View List
        </a>
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bx bx-printer"></i> Print
        </button>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Total Records</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total'] ?? 0); ?></h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="bx bx-user fs-4"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add more stat cards as needed -->
    </div>
    
    <!-- Main Content Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Records</h5>
            
            <!-- Search/Filter Form -->
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="filter" class="form-control form-control-sm" 
                       placeholder="Search..." value="<?php echo htmlspecialchars($_GET['filter'] ?? ''); ?>">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bx bx-search"></i>
                </button>
                <?php if (isset($_GET['filter'])): ?>
                <a href="?" class="btn btn-sm btn-outline-secondary">
                    <i class="bx bx-x"></i>
                </a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="card-body">
            <?php if (empty($records)): ?>
                <!-- Empty State -->
                <div class="text-center py-5">
                    <i class="bx bx-folder-open" style="font-size: 48px; color: #ccc;"></i>
                    <h5 class="mt-3">No records found</h5>
                    <p class="text-muted">Start by creating a new record</p>
                    <a href="create.php" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Create First Record
                    </a>
                </div>
            <?php else: ?>
                <!-- Records Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['id']); ?></td>
                                <td><?php echo htmlspecialchars($record['name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-label-success">Active</span>
                                </td>
                                <td><?php echo formatDate($record['created_at'] ?? ''); ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo $record['id']; ?>" 
                                       class="btn btn-sm btn-icon btn-outline-primary"
                                       title="View">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $record['id']; ?>" 
                                       class="btn btn-sm btn-icon btn-outline-info"
                                       title="Edit">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <a href="?action=delete&id=<?php echo $record['id']; ?>" 
                                       class="btn btn-sm btn-icon btn-outline-danger"
                                       title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this record?')">
                                        <i class="bx bx-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<!-- Page-specific JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded successfully');
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>

<?php
// ============================================================================
// STEP 7: GET BUFFERED CONTENT
// ============================================================================
$pageContent = ob_get_clean();

// ============================================================================
// STEP 8: LOAD HEADER & SIDEBAR
// ============================================================================
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/includes/sidebar.php';

// ============================================================================
// STEP 9: OUTPUT CONTENT
// ============================================================================
echo $pageContent;

// ============================================================================
// STEP 10: LOAD FOOTER
// ============================================================================
require_once ROOT_PATH . '/panel/includes/footer.php';
?>
