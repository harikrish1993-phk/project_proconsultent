<?php
/**
 * Daily Report Email Sender Logic
 * Designed to be run via a cron job or manually by an admin.
 */

// Note: This script is designed to be run from the command line (cron job)
if (php_sapi_name() !== 'cli') {
    // For manual testing, allow access, but in production, restrict to CLI
    // die("Access Denied: This script should be run from the command line.");
}

// Load common bootstrap
require_once __DIR__ . '/../_common.php';
require_once __DIR__ . '/../../../includes/core/Settings.php';
require_once __DIR__ . '/../../../includes/core/Mailer.php';

use Core\Database;
use Core\Settings;
use Core\Mailer;

$db = Database::getInstance();
$conn = $db->getConnection();
$settings = Settings::getInstance();

// --- 1. Fetch Report Data (Simplified for email) ---
// We will reuse the logic from daily_report_data.php but simplify the output
$summary_date = date('Y-m-d');

// KPI Data (Today)
$kpis_raw = [
    'candidates_added' => (int)get_kpi_count($conn, "DATE(c.created_at) = '{$summary_date}'", 'candidates', 'created_at'),
    'calls_logged' => (int)get_kpi_count($conn, "DATE(c.activity_date) = '{$summary_date}'", 'candidate_activity', 'activity_date', "activity_type = 'Call'"),
    'followups_done' => (int)get_kpi_count($conn, "DATE(c.activity_date) = '{$summary_date}'", 'candidate_activity', 'activity_date', "activity_type = 'Follow-up'"),
    'offers_made' => (int)get_kpi_count($conn, "DATE(c.status_date) = '{$summary_date}'", 'candidates', 'status_date', "status = 'Offer'"),
];

$kpis = [
    'Total Candidates Added' => $kpis_raw['candidates_added'],
    'Total Calls Logged' => $kpis_raw['calls_logged'],
    'Total Follow-ups Done' => $kpis_raw['followups_done'],
    'Total Offers Made' => $kpis_raw['offers_made'],
];

// Recruiter Breakdown (Simplified)
$recruiter_breakdown_query = "
    SELECT 
        u.name,
        COUNT(CASE WHEN DATE(c.created_at) = '{$summary_date}' THEN 1 END) AS candidates_added,
        COUNT(CASE WHEN ca.activity_type = 'Call' AND DATE(ca.activity_date) = '{$summary_date}' THEN 1 END) AS calls_logged,
        COUNT(CASE WHEN ca.activity_type = 'Follow-up' AND DATE(ca.activity_date) = '{$summary_date}' THEN 1 END) AS followups_done
    FROM user u
    LEFT JOIN candidates c ON u.user_code = c.created_by
    LEFT JOIN candidate_activity ca ON u.user_code = ca.user_code
    WHERE u.level != 'admin'
    GROUP BY u.user_code
    HAVING candidates_added > 0 OR calls_logged > 0 OR followups_done > 0
";
$recruiter_breakdown_result = $conn->query($recruiter_breakdown_query);
$recruiter_breakdown = [];
while ($row = $recruiter_breakdown_result->fetch_assoc()) {
    $recruiter_breakdown[] = $row;
}

// Follow-up Pending List (for email table)
$followup_query = "
    SELECT 
        c.candidate_name,
        c.follow_up_date,
        u.name AS recruiter_name
    FROM candidates c
    LEFT JOIN user u ON c.assigned_to = u.user_code
    WHERE c.follow_up_date IS NOT NULL 
        AND c.follow_up_date <= '{$summary_date}'
        AND c.status NOT IN ('Hired', 'Rejected')
    ORDER BY c.follow_up_date ASC
    LIMIT 5
";
$followup_result = $conn->query($followup_query);
$followup_table_html = '';
if ($followup_result->num_rows > 0) {
    $followup_table_html = '<table class="table-summary"><thead><tr><th>Candidate</th><th>Follow-up Date</th><th>Assigned Recruiter</th></tr></thead><tbody>';
    while ($row = $followup_result->fetch_assoc()) {
        $followup_table_html .= "<tr><td>{$row['candidate_name']}</td><td>{$row['follow_up_date']}</td><td>{$row['recruiter_name']}</td></tr>";
    }
    $followup_table_html .= '</tbody></table>';
}

// --- 2. Prepare Email Content ---
$view_report_url = BASE_URL . '/panel/modules/reports/index.php?type=daily'; // Assuming BASE_URL is set in config

// Start output buffering to capture the template HTML
ob_start();
include __DIR__ . '/templates/daily_summary_email.php';
$email_body = ob_get_clean();

// --- 3. Send Email ---
$mailer = new Mailer();
$subject = "Daily Recruitment Summary – " . date('d M Y', strtotime($summary_date));

// Fetch all admin emails to send the report to
$admin_emails = [];
$admin_result = $conn->query("SELECT email FROM user WHERE level = 'admin'");
while ($row = $admin_result->fetch_assoc()) {
    $admin_emails[] = $row['email'];
}

$success_count = 0;
$failure_count = 0;

if (!empty($admin_emails)) {
    foreach ($admin_emails as $email) {
        if ($mailer->send($email, $subject, $email_body)) {
            $success_count++;
        } else {
            $failure_count++;
            error_log("Failed to send daily report to {$email}: " . $mailer->getError());
        }
    }
} else {
    error_log("No admin users found to send the daily report to.");
}

echo "Daily Report Email Sending Complete.\n";
echo "Successfully sent to: {$success_count} admins.\n";
echo "Failed to send to: {$failure_count} admins.\n";

// Helper function from daily_report_data.php (copied here for CLI execution)
function get_kpi_count($conn, $condition, $table, $date_col, $where_clause = "1=1") {
    $query = "SELECT COUNT(*) AS count FROM {$table} c WHERE {$condition} AND {$where_clause}";
    $result = $conn->query($query);
    return $result ? (int)$result->fetch_assoc()['count'] : 0;
}
?>
