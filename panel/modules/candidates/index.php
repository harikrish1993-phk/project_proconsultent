<?php
/**
 * CANDIDATES MODULE - INDEX/ROUTER
 * Location: panel/modules/candidates/index.php
 */

// Load bootstrap
require_once __DIR__ . '/../_common.php';

error_log('Loading candidates/index.php - Action: ' . $action); // ADDED: Logging at entry

// Set page title
$pageTitle = 'Candidates';

// Get action
$action = $_GET['action'] ?? 'list';

// Load UI components if available
$ui_components_loaded = false;
if (file_exists(ROOT_PATH . '/panel/components/ui_components.php') && is_readable(ROOT_PATH . '/panel/components/ui_components.php')) { // FIXED: Added is_readable
    require_once ROOT_PATH . '/panel/components/ui_components.php';
    $ui_components_loaded = true;
    error_log('UI components loaded');
} else {
    error_log('UI components not found or not readable');
    echo '<div class="alert alert-warning">UI components not loaded - some features may be missing.</div>'; // ADDED: On-page alert, non-blocking
}

// Include header
$header_loaded = false;
$header_path = ROOT_PATH . '/panel/includes/header.php';
if (file_exists($header_path)) {
    require_once $header_path;
    $header_loaded = true;
} else {
    // Fallback: Simple header
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo $pageTitle; ?> - <?php echo defined('COMPANY_NAME') ? COMPANY_NAME : 'ProConsultancy'; ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; background: #f5f7fa; }
            .page-wrapper { display: flex; min-height: 100vh; }
            .main-content { flex: 1; margin-left: 260px; padding: 30px; }
            .alert { padding: 15px; margin: 20px 0; border-radius: 6px; }
            .alert-warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
            .alert-error { background: #fee; color: #721c24; border-left: 4px solid #f00; }
            .alert-info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
        </style>
    </head>
    <body>
        <div class="page-wrapper">
            <?php 
            // $sidebar_path = ROOT_PATH . '/panel/includes/sidebar.php';
            // if (file_exists($sidebar_path)) {
            //     include $sidebar_path;
            // } else {
            //     echo '<div class="alert alert-error">Sidebar not found at: ' . htmlspecialchars($sidebar_path) . '</div>';
            // }
            ?>
            <main class="main-content">
    <?php
    $header_loaded = true;
}

// Show breadcrumb if UI components loaded
if ($ui_components_loaded && function_exists('renderBreadcrumb')) {
    $breadcrumbs = ['Candidates' => 'index.php'];
    if ($action !== 'list') {
        $breadcrumbs[ucfirst($action)] = '#';
    }
    echo renderBreadcrumb($breadcrumbs);
}

// Page header
echo '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; margin-bottom: 30px;">';
echo '<h1 style="font-size: 24px; margin-bottom: 5px;">' . $pageTitle . '</h1>';
echo '<p style="opacity: 0.9; font-size: 14px;">Manage your talent pool</p>';
echo '</div>';

// Route to appropriate page
try {
    switch ($action) {
        case 'create':
        case 'add':
            $create_page = __DIR__ . '/create.php';
            if (file_exists($create_page) && is_readable($create_page)) {
                include $create_page;
                error_log('Create page included');
            } else {
                error_log('Create page not found: ' . $create_page);
                echo '<div class="alert alert-warning">';
                echo '<strong>⚠️ Page Not Implemented</strong><br>';
                echo 'The create candidate page is not yet available.<br>';
                echo 'Expected location: ' . htmlspecialchars($create_page);
                echo '</div>';
                echo '<p><a href="index.php" style="color: #667eea;">← Back to Candidates List</a></p>';
            }
            break;
            
        case 'edit':
            $edit_page = __DIR__ . '/edit.php';
            if (file_exists($edit_page) && is_readable($edit_page)) {
                include $edit_page;
                error_log('Edit page included');
            } else {
                error_log('Edit page not found: ' . $edit_page);
                echo '<div class="alert alert-warning">';
                echo '<strong>⚠️ Page Not Implemented</strong><br>';
                echo 'The edit candidate page is not yet available.';
                echo '</div>';
            }
            break;
            
        case 'view':
            $view_page = __DIR__ . '/view.php';
            if (file_exists($view_page) && is_readable($view_page)) {
                include $view_page;
                error_log('View page included');
            } else {
                error_log('View page not found: ' . $view_page);
                echo '<div class="alert alert-warning">';
                echo '<strong>⚠️ Page Not Implemented</strong><br>';
                echo 'The view candidate page is not yet available.';
                echo '</div>';
            }
            break;
            
        case 'assigned':
            $assigned_page = __DIR__ . '/assigned.php';
            if (file_exists($assigned_page) && is_readable($assigned_page)) {
                include $assigned_page;
                error_log('Assigned page included');
            } else {
                error_log('Assigned page not found: ' . $assigned_page);
                echo '<div class="alert alert-warning">';
                echo '<strong>⚠️ Page Not Implemented</strong><br>';
                echo 'The assigned candidates page is not yet available.';
                echo '</div>';
            }
            break;
            
        case 'pipeline':
            $pipeline_page = __DIR__ . '/pipeline.php';
            if (file_exists($pipeline_page) && is_readable($pipeline_page)) {
                include $pipeline_page;
                error_log('Pipeline page included');
            } else {
                error_log('Pipeline page not found: ' . $pipeline_page);
                echo '<div class="alert alert-warning">';
                echo '<strong>⚠️ Page Not Implemented</strong><br>';
                echo 'The pipeline view page is not yet available.';
                echo '</div>';
            }
            break;
            
        case 'list':
        default:
            $list_page = __DIR__ . '/list.php';
            if (file_exists($list_page) && is_readable($list_page)) {
                include $list_page;
                error_log('List page included');
            } else {
                error_log('List page not found: ' . $list_page);
                // Show placeholder if list.php doesn't exist
                echo '<div class="alert alert-info">';
                echo '<strong>ℹ️ Candidates Module</strong><br>';
                echo 'The candidates list page is being set up.<br>';
                echo 'Expected location: ' . htmlspecialchars($list_page);
                echo '</div>';
                
                echo '<h3 style="margin-top: 30px;">Available Actions:</h3>';
                echo '<ul style="margin-top: 15px; line-height: 2;">';
                echo '<li><a href="?action=create" style="color: #667eea;">Add New Candidate</a></li>';
                echo '<li><a href="?action=list" style="color: #667eea;">View All Candidates</a></li>';
                echo '<li><a href="?action=assigned" style="color: #667eea;">Assigned Candidates</a></li>';
                echo '<li><a href="?action=pipeline" style="color: #667eea;">Pipeline View</a></li>';
                echo '</ul>';
                
                echo '<div style="margin-top: 30px; padding: 20px; background: white; border-radius: 8px;">';
                echo '<h4>Debug Information:</h4>';
                echo '<ul style="margin-top: 10px; font-family: monospace; font-size: 13px; line-height: 1.8;">';
                echo '<li><strong>ROOT_PATH:</strong> ' . htmlspecialchars(ROOT_PATH) . '</li>';
                echo '<li><strong>Current File:</strong> ' . htmlspecialchars(__FILE__) . '</li>';
                echo '<li><strong>Action:</strong> ' . htmlspecialchars($action) . '</li>';
                echo '<li><strong>User:</strong> ' . htmlspecialchars($current_user_name) . ' (' . htmlspecialchars($current_user_level) . ')</li>';
                echo '</ul>';
                echo '</div>';
            }
            break;
    }
} catch (Exception $e) {
    echo '<div class="alert alert-error">';
    echo '<strong>❌ Error Loading Page</strong><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
    error_log('Candidates module error: ' . $e->getMessage());
}

// Include footer
$footer_path = ROOT_PATH . '/panel/includes/footer.php';
if (file_exists($footer_path)) {
    require_once $footer_path;
} else {
    // Fallback: Simple footer
    ?>
            </main>
        </div>
    </body>
    </html>
    <?php
}

// ADDED: Log page completion
error_log('Candidates/index.php loaded successfully');
?>