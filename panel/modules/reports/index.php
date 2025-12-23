<?php
/**
 * Reports Module - Router
 * Handles routing for various reports.
 */

// Load common bootstrap
require_once __DIR__ . '/../_common.php';
require_once __DIR__ . '/../../../includes/core/Settings.php';


$user = Auth::user();
$report_type = $_GET['type'] ?? 'daily';

// Include Header (which includes the sidebar)
include __DIR__ . '/../../header.php';

// Determine which report view to load
switch ($report_type) {
    case 'daily':
        include 'daily_report.php';
        break;
    case 'pipeline':
        // Placeholder for future reports
        echo '<div class="container-xxl flex-grow-1 container-p-y"><h4 class="fw-bold py-3 mb-4">Pipeline Report (Coming Soon)</h4><p>This report will show the overall recruitment pipeline performance.</p></div>';
        break;
    default:
        include 'daily_report.php';
        break;
}

// Include Footer
include __DIR__ . '/../../footer.php';
?>
