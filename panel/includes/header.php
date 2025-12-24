<?php
/**
 * MODULE HEADER WRAPPER
 * File: panel/includes/header.php
 * 
 * FIXED VERSION - All path issues resolved
 * This file provides consistent header for all modules
 * Includes sidebar navigation and starts content area
 * 
 * REQUIREMENTS:
 * - Must be called AFTER _common.php loads
 * - $pageTitle variable should be set before including
 * - MODULE_BOOTSTRAP_LOADED must be defined
 */

// Security check - MUST be loaded via _common.php
if (!defined('MODULE_BOOTSTRAP_LOADED')) {
    die('Direct access not permitted. Please use proper module entry point via _common.php');
}

// Set default page title if not set
$pageTitle = $pageTitle ?? 'Dashboard';

// Set default breadcrumbs if not set
$breadcrumbs = $breadcrumbs ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo defined('COMPANY_NAME') ? COMPANY_NAME : 'ProConsultancy'; ?></title>
    
    <link rel="icon" type="image/x-icon" href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>/panel/assets/img/favicon/favicon.ico">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS (if needed by page) -->
    <?php if (isset($useDataTables) && $useDataTables): ?>
    <link href="https://cdn.datatables.net/v/bs5/dt-1.13.7/datatables.min.css" rel="stylesheet">
    <?php endif; ?>
    
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
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .breadcrumb-item {
            display: inline-block;
            color: #718096;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            padding: 0 8px;
            color: #cbd5e0;
        }
        
        .breadcrumb-item.active {
            color: #2d3748;
            font-weight: 500;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-success {
            background: #48bb78;
            color: white;
        }
        
        .btn-danger {
            background: #e53e3e;
            color: white;
        }
        
        .btn-secondary {
            background: #718096;
            color: white;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table thead {
            background: #f7fafc;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table tbody tr:hover {
            background: #f7fafc;
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: #2d3748;
        }
        
        .form-group {
            margin-bottom: 20px;
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
        
        .loading::after {
            content: "Loading...";
            animation: dots 1.5s steps(4, end) infinite;
        }
        
        @keyframes dots {
            0%, 20% { content: "Loading."; }
            40% { content: "Loading.."; }
            60%, 100% { content: "Loading..."; }
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
                $flashType = htmlspecialchars($flash['type'] ?? 'info');
                $flashMessage = htmlspecialchars($flash['message'] ?? '');
                echo "<div class='alert alert-{$flashType}'>";
                echo $flashMessage;
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
            
            // Display breadcrumbs if provided
            if (!empty($breadcrumbs)) {
                echo '<nav class="breadcrumb">';
                echo '<span class="breadcrumb-item"><a href="' . (defined('BASE_URL') ? BASE_URL : '') . '/panel/' . ($current_user_level === 'admin' ? 'admin.php' : 'recruiter.php') . '">Dashboard</a></span>';
                $count = count($breadcrumbs);
                $i = 0;
                foreach ($breadcrumbs as $label => $url) {
                    $i++;
                    $isActive = ($i === $count);
                    if ($isActive || $url === '#') {
                        echo '<span class="breadcrumb-item active">' . htmlspecialchars($label) . '</span>';
                    } else {
                        echo '<span class="breadcrumb-item"><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($label) . '</a></span>';
                    }
                }
                echo '</nav>';
            }
            ?>
            
            <!-- Module content starts here -->