<?php
/**
 * MODULE HEADER WRAPPER
 * File: panel/includes/header.php
 * 
 * This file provides a consistent header for all modules
 * Includes sidebar navigation and starts the content area
 */

// Security check
if (!defined('MODULE_BOOTSTRAP_LOADED')) {
    die('Direct access not permitted. Please use proper module entry point.');
}

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
        /* Critical inline CSS for immediate render */
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
        
        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 25px;
            margin-bottom: 20px;
        }
        
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
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
                width: 100%;
            }
        }
        
        /* Loading indicator */
        .loading {
            text-align: center;
            padding: 40px;
            color: #718096;
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
        if (file_exists($sidebarPath)) {
            include $sidebarPath;
        } else {
            // Fallback if sidebar not found
            echo '<!-- Sidebar not found at: ' . htmlspecialchars($sidebarPath) . ' -->';
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                echo '<div class="alert alert-warning">Sidebar file not found. Please check configuration.</div>';
            }
        }
        ?>
        
        <main class="main-content">
            <?php
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
            ?>
            
            <!-- Module content starts here -->