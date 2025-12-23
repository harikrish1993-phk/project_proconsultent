<?php
/**
 * Daily Report Data Handler (daily_report_data.php)
 * Fetches and processes data for the Daily Report UI.
 */

// Load common bootstrap
require_once __DIR__ . '/../_common.php';
$pageTitle = 'Reports Data';
$breadcrumbs = [
    'Reports' => '#'
];
// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// Display breadcrumb
echo renderBreadcrumb($breadcrumbs);

$db = Core\Database::getInstance();
$conn = $db->getConnection();

// --- Input Parameters ---
$date_range = $_GET['date_range'] ?? 'today';
$recruiter_filter = $_GET['recruiter_filter'] ?? 'all';
$job_filter = $_GET['job_filter'] ?? 'all';
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// --- Date Range Calculation (Simplified for placeholder) ---
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

switch ($date_range) {
    case 'today':
        $date_condition = "DATE(c.created_at) = '{$today}'";
        $prev_date_condition = "DATE(c.created_at) = '{$yesterday}'";
        break;
    case 'yesterday':
        $date_condition = "DATE(c.created_at) = '{$yesterday}'";
        $prev_date_condition = "DATE(c.created_at) = DATE(NOW() - INTERVAL 2 DAY)";
        break;
    case 'last_7_days':
        $date_condition = "c.created_at >= DATE(NOW() - INTERVAL 7 DAY)";
        $prev_date_condition = "c.created_at >= DATE(NOW() - INTERVAL 14 DAY) AND c.created_at < DATE(NOW() - INTERVAL 7 DAY)";
        break;
    case 'custom':
        if ($start_date && $end_date) {
            $date_condition = "DATE(c.created_at) BETWEEN '{$start_date}' AND '{$end_date}'";
            // No easy way to calculate "previous" period for custom range, so we skip comparison
            $prev_date_condition = "1=0"; 
        } else {
            $date_condition = "1=1"; // No filter
            $prev_date_condition = "1=0";
        }
        break;
    default:
        $date_condition = "DATE(c.created_at) = '{$today}'";
        $prev_date_condition = "DATE(c.created_at) = '{$yesterday}'";
        break;
}

// --- Filtering Logic ---
$recruiter_condition = ($recruiter_filter !== 'all') ? " AND c.created_by = '{$recruiter_filter}'" : "";
$job_condition = ($job_filter !== 'all') ? " AND c.job_code = '{$job_filter}'" : "";

// --- KPI Calculation Function ---
function get_kpi_count($conn, $condition, $table, $date_col, $where_clause = "1=1") {
    $query = "SELECT COUNT(*) AS count FROM {$table} c WHERE {$condition} AND {$where_clause}";
    $result = $conn->query($query);
    return $result ? (int)$result->fetch_assoc()['count'] : 0;
}

// --- 1. KPI Data (Current Period) ---
$kpis = [
    'candidates_added' => get_kpi_count($conn, $date_condition, 'candidates', 'created_at', "1=1 {$recruiter_condition} {$job_condition}"),
    'calls_logged' => get_kpi_count($conn, $date_condition, 'candidate_activity', 'activity_date', "activity_type = 'Call' {$recruiter_condition} {$job_condition}"),
    'followups_done' => get_kpi_count($conn, $date_condition, 'candidate_activity', 'activity_date', "activity_type = 'Follow-up' {$recruiter_condition} {$job_condition}"),
    'offers_made' => get_kpi_count($conn, $date_condition, 'candidates', 'status_date', "status = 'Offer' {$recruiter_condition} {$job_condition}"),
];

// --- 2. KPI Data (Previous Period for Comparison) ---
$prev_kpis = [
    'candidates_added' => get_kpi_count($conn, $prev_date_condition, 'candidates', 'created_at', "1=1 {$recruiter_condition} {$job_condition}"),
    'calls_logged' => get_kpi_count($conn, $prev_date_condition, 'candidate_activity', 'activity_date', "activity_type = 'Call' {$recruiter_condition} {$job_condition}"),
    'followups_done' => get_kpi_count($conn, $prev_date_condition, 'candidate_activity', 'activity_date', "activity_type = 'Follow-up' {$recruiter_condition} {$job_condition}"),
    'offers_made' => get_kpi_count($conn, $prev_date_condition, 'candidates', 'status_date', "status = 'Offer' {$recruiter_condition} {$job_condition}"),
];

// --- 3. Follow-up Pending List ---
$followup_query = "
    SELECT 
        c.can_code AS candidate_id,
        c.candidate_name,
        c.follow_up_date,
        c.status,
        u.name AS recruiter_name,
        j.job_title
    FROM candidates c
    LEFT JOIN user u ON c.assigned_to = u.user_code
    LEFT JOIN jobs j ON c.job_code = j.job_code
    WHERE c.follow_up_date IS NOT NULL 
        AND c.follow_up_date <= '{$today}'
        AND c.status NOT IN ('Hired', 'Rejected')
    ORDER BY c.follow_up_date ASC
    LIMIT 10
";
$followup_result = $conn->query($followup_query);
$followup_pending = [];
while ($row = $followup_result->fetch_assoc()) {
    $followup_pending[] = $row;
}

// --- 4. Charts Data (Placeholder Structure) ---
$charts_data = [
    'recruiter_activity' => [
        'names' => ['Recruiter A', 'Recruiter B', 'Recruiter C'],
        'calls' => [15, 22, 10],
        'followups' => [8, 15, 5]
    ],
    'pipeline_movement' => [
        'categories' => ['Sourced', 'Screening', 'Interview', 'Offer'],
        'series' => [
            ['name' => 'Moved In', 'data' => [50, 30, 15, 5]],
            ['name' => 'Moved Out', 'data' => [10, 20, 10, 2]]
        ]
    ]
];

// --- Final Response Assembly ---
$response_kpis = [];
foreach ($kpis as $key => $value) {
    $prev_value = $prev_kpis[$key];
    $change = ($prev_value > 0) ? round((($value - $prev_value) / $prev_value) * 100, 1) : ($value > 0 ? 100 : 0);
    
    $response_kpis[$key] = [
        'value' => $value,
        'change' => "{$change}%"
    ];
}

$response = [
    'status' => 'success',
    'kpis' => $response_kpis,
    'charts' => $charts_data,
    'tables' => [
        'followup_pending' => $followup_pending
    ]
];

header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
?>
