<?php
/**
 * Daily Report UI (daily_report.php)
 * Implements the user-first, manager-friendly daily report dashboard.
 */

// Placeholder for fetching filter options (e.g., all recruiters, job titles)
// Load common bootstrap
require_once __DIR__ . '/../_common.php';
$pageTitle = 'Daily Reports';
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

$recruiters = [];
$result = $conn->query("SELECT user_code, name FROM user WHERE level != 'admin' ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $recruiters[] = $row;
}

$jobs = [];
$result = $conn->query("SELECT job_code, job_title FROM jobs ORDER BY job_title ASC");
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

$conn->close();

?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <i class="bx bx-bar-chart-alt-2 me-2"></i> Daily Recruitment Report
    </h4>

    <!-- Filters -->
    <div class="card mb-4">
        <h5 class="card-header">Report Filters</h5>
        <div class="card-body">
            <form id="dailyReportFilterForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="date_range" class="form-label">Date Range</label>
                        <select id="date_range" name="date_range" class="form-select">
                            <option value="today" selected>Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="last_7_days">Last 7 Days</option>
                            <option value="this_month">This Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="recruiter_filter" class="form-label">Recruiter</label>
                        <select id="recruiter_filter" name="recruiter_filter" class="form-select">
                            <option value="all">All Recruiters</option>
                            <?php foreach ($recruiters as $recruiter): ?>
                                <option value="<?php echo htmlspecialchars($recruiter['user_code']); ?>"><?php echo htmlspecialchars($recruiter['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="job_filter" class="form-label">Job (Optional)</label>
                        <select id="job_filter" name="job_filter" class="form-select">
                            <option value="all">All Jobs</option>
                            <?php foreach ($jobs as $job): ?>
                                <option value="<?php echo htmlspecialchars($job['job_code']); ?>"><?php echo htmlspecialchars($job['job_title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8" id="custom_date_range_fields" style="display:none;">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary me-2"><i class="bx bx-search"></i> Generate Report</button>
                    <button type="button" class="btn btn-outline-secondary" id="exportReport"><i class="bx bx-download"></i> Export CSV</button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4" id="kpi_cards">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <i class="bx bx-user-plus text-primary fs-3"></i>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Candidates Added</span>
                    <h3 class="card-title mb-2" id="kpi_candidates_added">0</h3>
                    <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> <span id="kpi_candidates_added_change">0%</span></small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <i class="bx bx-phone-call text-info fs-3"></i>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Calls Logged</span>
                    <h3 class="card-title mb-2" id="kpi_calls_logged">0</h3>
                    <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> <span id="kpi_calls_logged_change">0%</span></small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <i class="bx bx-calendar-check text-warning fs-3"></i>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Follow-ups Done</span>
                    <h3 class="card-title mb-2" id="kpi_followups_done">0</h3>
                    <small class="text-danger fw-semibold"><i class="bx bx-down-arrow-alt"></i> <span id="kpi_followups_done_change">0%</span></small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <i class="bx bx-dollar-circle text-success fs-3"></i>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">Offers Made</span>
                    <h3 class="card-title mb-2" id="kpi_offers_made">0</h3>
                    <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> <span id="kpi_offers_made_change">0%</span></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Detailed Tables -->
    <div class="row">
        <!-- Charts -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <h5 class="card-header">Recruiter Activity (Calls & Follow-ups)</h5>
                <div class="card-body"><div id="recruiterActivityChart">Loading chart...</div></div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <h5 class="card-header">Pipeline Movement (Status Changes)</h5>
                <div class="card-body"><div id="pipelineMovementChart">Loading chart...</div></div>
            </div>
        </div>

        <!-- Detailed Tables -->
        <div class="col-12">
            <div class="card mb-4">
                <h5 class="card-header">Follow-up Pending List</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Job</th>
                                <th>Follow-up Date</th>
                                <th>Recruiter</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="followupTableBody">
                            <tr><td colspan="5" class="text-center">No pending follow-ups found.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Script to handle filter visibility
    document.getElementById('date_range').addEventListener('change', function() {
        const customFields = document.getElementById('custom_date_range_fields');
        if (this.value === 'custom') {
            customFields.style.display = 'block';
        } else {
            customFields.style.display = 'none';
        }
    });

    // Placeholder for chart rendering (requires a charting library like ApexCharts)
    function renderCharts(data) {
        // Example: Recruiter Activity Chart
        const activityOptions = {
            chart: { type: 'bar', height: 350 },
            series: [{ name: 'Calls Logged', data: data.recruiter_activity.calls }, { name: 'Follow-ups Done', data: data.recruiter_activity.followups }],
            xaxis: { categories: data.recruiter_activity.names }
        };
        // new ApexCharts(document.querySelector("#recruiterActivityChart"), activityOptions).render();
        document.getElementById("recruiterActivityChart").innerHTML = 'Chart Placeholder: Data Loaded';

        // Example: Pipeline Movement Chart
        const pipelineOptions = {
            chart: { type: 'bar', stacked: true, height: 350 },
            series: data.pipeline_movement.series,
            xaxis: { categories: data.pipeline_movement.categories }
        };
        // new ApexCharts(document.querySelector("#pipelineMovementChart"), pipelineOptions).render();
        document.getElementById("pipelineMovementChart").innerHTML = 'Chart Placeholder: Data Loaded';
    }

    // Placeholder for data fetching
    function loadReportData() {
        const form = document.getElementById('dailyReportFilterForm');
        const params = new URLSearchParams(new FormData(form));
        
        // Example AJAX call to the data handler
        fetch('daily_report_data.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update KPI Cards
                    document.getElementById('kpi_candidates_added').textContent = data.kpis.candidates_added.value;
                    document.getElementById('kpi_calls_logged').textContent = data.kpis.calls_logged.value;
                    document.getElementById('kpi_followups_done').textContent = data.kpis.followups_done.value;
                    document.getElementById('kpi_offers_made').textContent = data.kpis.offers_made.value;
                    
                    // Update change percentages (simplified)
                    document.getElementById('kpi_candidates_added_change').textContent = data.kpis.candidates_added.change;
                    document.getElementById('kpi_calls_logged_change').textContent = data.kpis.calls_logged.change;
                    document.getElementById('kpi_followups_done_change').textContent = data.kpis.followups_done.change;
                    document.getElementById('kpi_offers_made_change').textContent = data.kpis.offers_made.change;

                    // Render Charts (using placeholders for now)
                    renderCharts(data.charts);

                    // Render Follow-up Table
                    renderFollowupTable(data.tables.followup_pending);
                } else {
                    alert('Error generating report: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Network error: Could not connect to report data source.');
            });
    }

    function renderFollowupTable(followups) {
        const tableBody = document.getElementById('followupTableBody');
        tableBody.innerHTML = '';

        if (followups.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No pending follow-ups found.</td></tr>';
            return;
        }

        followups.forEach(item => {
            const row = `
                <tr>
                    <td><a href="../candidates/index.php?action=view&id=${item.candidate_id}">${item.candidate_name}</a></td>
                    <td>${item.job_title || 'N/A'}</td>
                    <td><span class="badge bg-label-warning">${item.followup_date}</span></td>
                    <td>${item.recruiter_name}</td>
                    <td>${item.status}</td>
                </tr>
            `;
            tableBody.innerHTML += row;
        });
    }

    // Event listener for form submission
    document.getElementById('dailyReportFilterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadReportData();
    });

    // Initial load
    document.addEventListener('DOMContentLoaded', loadReportData);
</script>
