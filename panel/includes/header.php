<?php
/**
 * MODULE HEADER WRAPPER
 * Location: panel/includes/header.php
 * 
 * IMPORTANT: This file should ONLY be loaded after _common.php
 */

// Security check - must be loaded via _common.php
if (!defined('MODULE_BOOTSTRAP_LOADED')) {
    die('Direct access not permitted. Load via _common.php first.');
}

// Add logging for header loading
error_log('Header started: ' . __FILE__);

// Set default page title if not set
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo defined('COMPANY_NAME') ? COMPANY_NAME : 'ProConsultancy'; ?></title>
    
    <style>
        /* Critical CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            color: #2d3748;
            line-height: 1.6;
        }
        
        .page-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
            width: calc(100% - 260px);
        }
        
        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .alert-error {
            background: #fee;
            border-left-color: #f00;
            color: #721c24;
        }
        
        .alert-warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        
        .alert-success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        
        .alert-info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }
        
        .page-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .page-header p {
            opacity: 0.95;
            font-size: 14px;
        }
        
        /* Content Cards */
        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: #718096;
        }
        
        .btn-secondary:hover {
            background: #4a5568;
        }
        
        .btn-success {
            background: #48bb78;
        }
        
        .btn-success:hover {
            background: #38a169;
        }
        
        .btn-danger {
            background: #f56565;
        }
        
        .btn-danger:hover {
            background: #e53e3e;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        th {
            background: #f7fafc;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #2d3748;
            border-bottom: 2px solid #e2e8f0;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        tr:hover {
            background: #f7fafc;
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
                width: 100%;
            }
        }
    </style>
    
    <?php
    // Allow modules to add custom CSS
    if (isset($customCSS)) {
        echo "<style>{$customCSS}</style>";
    }
    ?>
</head>
<body>
    <div class="page-wrapper">
        <?php 
        // Include sidebar navigation
        $sidebarPath = __DIR__ . '/sidebar.php';
        error_log('Attempting to load sidebar: ' . $sidebarPath); // ADDED: Logging

        if (file_exists($sidebarPath) && is_readable($sidebarPath)) { // FIXED: Added is_readable to prevent warning if permission issue
            try {
                @include $sidebarPath; // FIXED: Suppress warning, but log if fails
                error_log('Sidebar loaded successfully');
            } catch (Exception $e) {
                error_log('Sidebar include error: ' . $e->getMessage() . ' - Path: ' . $sidebarPath);
                echo '<div class="alert alert-error">Sidebar loading error: ' . htmlspecialchars($e->getMessage()) . '</div>'; // Display on page
            }
        } else {
            error_log('Sidebar not found or not readable at: ' . $sidebarPath);
            echo '<div class="alert alert-warning">Sidebar not found or inaccessible. Please ensure panel/includes/sidebar.php exists and is readable.</div>'; // FIXED: Updated message, display on page
        }
        ?>
        
        <main class="main-content">
            <?php
            // Display bootstrap errors if any
            if (!empty($bootstrap_errors)) {
                echo '<div class="alert alert-warning">';
                echo '<strong>⚠️ System Warnings:</strong><ul style="margin: 10px 0;">';
                foreach ($bootstrap_errors as $error) {
                    echo '<li>' . htmlspecialchars($error) . '</li>';
                }
                echo '</ul></div>';
            }
            
            // Display flash messages if any
            if (isset($_SESSION['flash_message'])) {
                $flash = $_SESSION['flash_message'];
                echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . '">';
                echo htmlspecialchars($flash['message']);
                echo '</div>';
                unset($_SESSION['flash_message']);
            }
            
            // Display error messages from URL parameters
            if (isset($_GET['error'])) {
                echo '<div class="alert alert-error">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            
            if (isset($_GET['success'])) {
                echo '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
            }
            
            if (isset($_GET['warning'])) {
                echo '<div class="alert alert-warning">' . htmlspecialchars($_GET['warning']) . '</div>';
            }
            ?>
            
            <!-- Module content starts here -->