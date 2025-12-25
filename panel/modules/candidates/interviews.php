<?php
// ============================================================================
// BOOTSTRAP & AUTHORIZATION
// ============================================================================
require_once __DIR__ . '/../_common.php';

// Check permissions
if (!$user || !in_array($user['level'], ['admin', 'user','recruiter', 'manager'])) {
    header('HTTP/1.0 403 Forbidden');
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Access denied.</div></div>';
    exit();
}

// Page configuration
$can_id = $_GET['can_id'] ?? null;
$interview_id = $_GET['interview_id'] ?? null;
$pageTitle = $can_id ? 'Candidate Interviews' : 'Interview Management';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'index.php'],
    ['label' => 'Candidates', 'url' => 'candidates.php'],
    ['label' => $pageTitle, 'active' => true]
];

try {
    $conn = Database::getInstance()->getConnection();
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['token'] ?? '';
        // if (!Auth::verifyToken($token)) {
        //     throw new Exception('Invalid security token');
        // }
        
        $action = $_POST['action'] ?? '';
        $logged_by = $user['user_code'];
        
        switch ($action) {
            case 'add':
            case 'update':
                $can_code = $_POST['can_code'] ?? '';
                $date = $_POST['date'] ?? '';
                $time = $_POST['time'] ?? '10:00';
                $location = $_POST['location'] ?? '';
                $notes = $_POST['notes'] ?? '';
                $outcome = $_POST['outcome'] ?? 'Scheduled';
                $interview_type = $_POST['interview_type'] ?? 'Technical';
                $interviewers = $_POST['interviewers'] ?? [];
                
                if (!$can_code || !$date) {
                    throw new Exception('Candidate and date are required');
                }
                
                $datetime = $date . ' ' . $time;
                
                if ($action === 'add') {
                    $stmt = $conn->prepare("
                        INSERT INTO interviews 
                        (can_code, interview_datetime, location, notes, outcome, interview_type, logged_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->bind_param("sssssss", $can_code, $datetime, $location, $notes, $outcome, $interview_type, $logged_by);
                } else {
                    $interview_id = $_POST['interview_id'] ?? '';
                    $stmt = $conn->prepare("
                        UPDATE interviews 
                        SET can_code = ?, interview_datetime = ?, location = ?, notes = ?, outcome = ?, 
                            interview_type = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->bind_param("sssssssi", $can_code, $datetime, $location, $notes, $outcome, $interview_type, $interview_id);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to save interview: ' . $stmt->error);
                }
                
                $new_id = $action === 'add' ? $conn->insert_id : $interview_id;
                
                // Log to activity
                $stmt = $conn->prepare("
                    INSERT INTO candidate_activity_log 
                    (can_code, activity_type, description, performed_by, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $activity_type = $action === 'add' ? 'Interview Scheduled' : 'Interview Updated';
                $description = "Interview scheduled for " . date('M d, Y H:i', strtotime($datetime));
                $stmt->bind_param("ssss", $can_code, $activity_type, $description, $logged_by);
                $stmt->execute();
                
                // Handle interviewers
                if (!empty($interviewers)) {
                    // Remove existing first
                    $stmt = $conn->prepare("DELETE FROM interview_interviewers WHERE interview_id = ?");
                    $stmt->bind_param("i", $new_id);
                    $stmt->execute();
                    
                    // Add new ones
                    $stmt = $conn->prepare("
                        INSERT INTO interview_interviewers (interview_id, user_code)
                        VALUES (?, ?)
                    ");
                    foreach ($interviewers as $interviewer) {
                        $stmt->bind_param("is", $new_id, $interviewer);
                        $stmt->execute();
                    }
                }
                
                header('Location: ' . ($_SERVER['PHP_SELF'] . ($can_id ? '?can_id=' . $can_id : '')));
                exit();
                
            case 'delete':
                $interview_id = $_POST['interview_id'] ?? '';
                if (!$interview_id) {
                    throw new Exception('Interview ID required');
                }
                
                $stmt = $conn->prepare("DELETE FROM interviews WHERE id = ?");
                $stmt->bind_param("i", $interview_id);
                $stmt->execute();
                
                header('Location: ' . ($_SERVER['PHP_SELF'] . ($can_id ? '?can_id=' . $can_id : '')));
                exit();
                
            case 'update_status':
                $interview_id = $_POST['interview_id'] ?? '';
                $status = $_POST['status'] ?? 'Scheduled';
                
                if (!$interview_id) {
                    throw new Exception('Interview ID required');
                }
                
                $stmt = $conn->prepare("
                    UPDATE interviews 
                    SET outcome = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->bind_param("si", $status, $interview_id);
                $stmt->execute();
                
                // Get candidate ID for logging
                $stmt = $conn->prepare("SELECT can_code FROM interviews WHERE id = ?");
                $stmt->bind_param("i", $interview_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $interview = $result->fetch_assoc();
                
                if ($interview) {
                    $stmt = $conn->prepare("
                        INSERT INTO candidate_activity_log 
                        (can_code, activity_type, description, performed_by, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $activity_type = 'Interview Status Updated';
                    $description = "Interview status updated to: " . $status;
                    $stmt->bind_param("ssss", $interview['can_code'], $activity_type, $description, $logged_by);
                    $stmt->execute();
                }
                
                echo json_encode(['success' => true]);
                exit();
        }
    }
    
    // Fetch interviews based on context
    $interviews = [];
    $upcoming_interviews = [];
    $past_interviews = [];
    
    $baseQuery = "
        SELECT i.*, c.candidate_name, c.email_id, c.contact_details,
               GROUP_CONCAT(DISTINCT u.full_name SEPARATOR ', ') as interviewer_names
        FROM interviews i
        JOIN candidates c ON i.can_code = c.can_code
        LEFT JOIN interview_interviewers ii ON ii.interview_id = i.id
        LEFT JOIN users u ON u.user_code = ii.user_code
        WHERE 1=1
    ";
    
    $params = [];
    $types = '';
    
    if ($can_id) {
        $baseQuery .= " AND i.can_code = ?";
        $params[] = $can_id;
        $types .= 's';
    }
    
    if ($interview_id) {
        $baseQuery .= " AND i.id = ?";
        $params[] = $interview_id;
        $types .= 'i';
    }
    
    $baseQuery .= " GROUP BY i.id ORDER BY i.interview_datetime DESC";
    
    $stmt = $conn->prepare($baseQuery);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $interviews[] = $row;
        if (strtotime($row['interview_datetime']) > time()) {
            $upcoming_interviews[] = $row;
        } else {
            $past_interviews[] = $row;
        }
    }
    
    // Fetch candidates for dropdown
    $candidates = [];
    $stmt = $conn->prepare("
        SELECT can_code, candidate_name 
        FROM candidates 
        WHERE candidate_status NOT IN ('Blacklist', 'Rejected')
        ORDER BY candidate_name ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $candidates[] = $row;
    }
    
    // Fetch interviewers (recruiters and managers)
    $interviewers = [];
    $stmt = $conn->prepare("
        SELECT user_code, full_name 
        FROM users 
        WHERE role IN ('recruiter','user', 'manager', 'admin') AND is_active = 1
        ORDER BY full_name ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $interviewers[] = $row;
    }
    
    // Fetch candidate if editing
    $edit_interview = null;
    if ($interview_id) {
        $stmt = $conn->prepare("
            SELECT i.*, 
                   GROUP_CONCAT(ii.user_code SEPARATOR ',') as assigned_interviewers
            FROM interviews i
            LEFT JOIN interview_interviewers ii ON ii.interview_id = i.id
            WHERE i.id = ?
            GROUP BY i.id
        ");
        $stmt->bind_param("i", $interview_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_interview = $result->fetch_assoc();
        
        if ($edit_interview && !empty($edit_interview['assigned_interviewers'])) {
            $edit_interview['assigned_interviewers'] = explode(',', $edit_interview['assigned_interviewers']);
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo renderBreadcrumb($breadcrumbs);
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Error: ' . $e->getMessage() . '</div></div>';
    exit();
}
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <?= renderBreadcrumb($breadcrumbs) ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">
            <span class="text-muted fw-light">Interviews /</span> 
            <?= $can_id ? 'Candidate Interviews' : 'Management' ?>
        </h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInterviewModal">
            <i class="bx bx-plus me-1"></i> Schedule Interview
        </button>
    </div>

    <!-- Stats Summary Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="card border border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-3">
                            <p class="mb-1 fw-medium">Upcoming Interviews</p>
                            <h4 class="mb-0 text-primary fw-bold"><?= count($upcoming_interviews) ?></h4>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="bx bx-calendar bx-md"></i>
                            </span>
                        </div>
                    </div>
                    <small class="text-muted">Next 7 days</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="card border border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-3">
                            <p class="mb-1 fw-medium">Successful</p>
                            <h4 class="mb-0 text-success fw-bold">
                                <?= count(array_filter($past_interviews, fn($i) => $i['outcome'] === 'Positive')) ?>
                            </h4>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="bx bx-check-circle bx-md"></i>
                            </span>
                        </div>
                    </div>
                    <small class="text-muted"><?= $past_interviews ? round(count(array_filter($past_interviews, fn($i) => $i['outcome'] === 'Positive')) / count($past_interviews) * 100, 0) : 0 ?>% success rate</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
            <div class="card border border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-3">
                            <p class="mb-1 fw-medium">Rejected</p>
                            <h4 class="mb-0 text-danger fw-bold">
                                <?= count(array_filter($past_interviews, fn($i) => $i['outcome'] === 'Negative')) ?>
                            </h4>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="bx bx-x-circle bx-md"></i>
                            </span>
                        </div>
                    </div>
                    <small class="text-muted"><?= $past_interviews ? round(count(array_filter($past_interviews, fn($i) => $i['outcome'] === 'Negative')) / count($past_interviews) * 100, 0) : 0 ?>% rejection rate</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-3">
                            <p class="mb-1 fw-medium">Needs Follow-up</p>
                            <h4 class="mb-0 text-warning fw-bold">
                                <?= count(array_filter($past_interviews, fn($i) => $i['outcome'] === 'Scheduled' || $i['outcome'] === 'Neutral')) ?>
                            </h4>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="bx bx-time-five bx-md"></i>
                            </span>
                        </div>
                    </div>
                    <small class="text-muted">Awaiting decision</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter and Actions Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search Interviews</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" id="interviewSearch" placeholder="Candidate name, interviewer...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Positive">Positive</option>
                        <option value="Negative">Negative</option>
                        <option value="Neutral">Neutral</option>
                        <option value="Rescheduled">Rescheduled</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Time Period</label>
                    <select class="form-select" id="timeFilter">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="week" selected>This Week</option>
                        <option value="month">This Month</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" id="exportBtn">
                        <i class="bx bx-export me-1"></i> Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Interviews Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Interviews</h5>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bx bx-view me-1"></i> View Options
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item active" href="#" data-view="all">All Interviews</a></li>
                    <li><a class="dropdown-item" href="#" data-view="upcoming">Upcoming Only</a></li>
                    <li><a class="dropdown-item" href="#" data-view="past">Past Only</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" data-view="calendar"><i class="bx bx-calendar me-2"></i>Calendar View</a></li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover" id="interviewsTable">
                    <thead>
                        <tr>
                            <th>CANDIDATE</th>
                            <th>DATE & TIME</th>
                            <th>LOCATION</th>
                            <th>INTERVIEWERS</th>
                            <th>OUTCOME</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($interviews as $int): 
                            $date = new DateTime($int['interview_datetime']);
                            $isPast = $date->getTimestamp() < time();
                            $statusColor = match($int['outcome']) {
                                'Positive' => 'success',
                                'Negative' => 'danger',
                                'Neutral' => 'warning',
                                'Cancelled' => 'secondary',
                                default => 'primary'
                            };
                        ?>
                        <tr class="<?= $isPast ? 'table-light' : '' ?>" data-interview-id="<?= $int['id'] ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-3">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            <?= strtoupper(substr($int['candidate_name'] ?? 'CN', 0, 2)) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-medium">
                                            <a href="view.php?id=<?= htmlspecialchars($int['can_code']) ?>" class="text-body text-decoration-none">
                                                <?= htmlspecialchars($int['candidate_name']) ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted"><?= htmlspecialchars($int['email_id']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <strong><?= $date->format('M d, Y') ?></strong>
                                    <small class="text-muted"><?= $date->format('H:i') ?> <?= $isPast ? '<span class="badge bg-label-secondary ms-1">Past</span>' : '<span class="badge bg-label-primary ms-1">Upcoming</span>' ?></small>
                                </div>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= !empty($int['location']) ? htmlspecialchars($int['location']) : 'Not specified' ?>
                                </small>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= !empty($int['interviewer_names']) ? htmlspecialchars($int['interviewer_names']) : 'Not assigned' ?>
                                </small>
                            </td>
                            <td>
                                <select class="form-select form-select-sm outcome-select" data-id="<?= $int['id'] ?>">
                                    <option value="Scheduled" <?= $int['outcome'] === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="Positive" <?= $int['outcome'] === 'Positive' ? 'selected' : '' ?>>Positive</option>
                                    <option value="Negative" <?= $int['outcome'] === 'Negative' ? 'selected' : '' ?>>Negative</option>
                                    <option value="Neutral" <?= $int['outcome'] === 'Neutral' ? 'selected' : '' ?>>Neutral</option>
                                    <option value="Rescheduled" <?= $int['outcome'] === 'Rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
                                    <option value="Cancelled" <?= $int['outcome'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addInterviewModal" data-edit-id="<?= $int['id'] ?>"><i class="bx bx-edit-alt me-1"></i> Edit</a></li>
                                        <li><a class="dropdown-item" href="view.php?id=<?= htmlspecialchars($int['can_code']) ?>"><i class="bx bx-show me-1"></i> View Candidate</a></li>
                                        <li><a class="dropdown-item" href="#send-reminder" data-can-code="<?= htmlspecialchars($int['can_code']) ?>" data-interview-id="<?= $int['id'] ?>"><i class="bx bx-envelope me-1"></i> Send Reminder</a></li>
                                        <li><a class="dropdown-item" href="#join-meeting" data-meeting-link="<?= htmlspecialchars($int['meeting_link'] ?? '#') ?>"><i class="bx bx-video me-1"></i> Join Meeting</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteInterviewModal" data-id="<?= $int['id'] ?>"><i class="bx bx-trash me-1"></i> Delete</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($interviews)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="mb-3">
                                    <i class="bx bx-calendar-x display-3 text-muted"></i>
                                </div>
                                <h5 class="mb-1">No interviews found</h5>
                                <p class="text-muted mb-0">Schedule your first interview to get started</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Interview Modal -->
<div class="modal fade" id="addInterviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="interviewForm" method="POST" action="<?= $_SERVER['PHP_SELF'] . ($can_id ? '?can_id=' . $can_id : '') ?>">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="interview_id" id="editInterviewId" value="">
                <input type="hidden" name="token" value="<?= Auth::token() ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Schedule New Interview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Candidate <span class="text-danger">*</span></label>
                            <select name="can_code" class="form-select" required>
                                <option value="">Select candidate</option>
                                <?php foreach ($candidates as $cand): ?>
                                <option value="<?= htmlspecialchars($cand['can_code']) ?>" <?= ($can_id && $can_id === $cand['can_code']) || ($edit_interview && $edit_interview['can_code'] === $cand['can_code']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cand['candidate_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Interview Type</label>
                            <select name="interview_type" class="form-select">
                                <option value="Technical" <?= ($edit_interview && $edit_interview['interview_type'] === 'Technical') ? 'selected' : '' ?>>Technical</option>
                                <option value="HR" <?= ($edit_interview && $edit_interview['interview_type'] === 'HR') ? 'selected' : '' ?>>HR Screening</option>
                                <option value="Manager" <?= ($edit_interview && $edit_interview['interview_type'] === 'Manager') ? 'selected' : '' ?>>Hiring Manager</option>
                                <option value="Panel" <?= ($edit_interview && $edit_interview['interview_type'] === 'Panel') ? 'selected' : '' ?>>Panel Interview</option>
                                <option value="Final" <?= ($edit_interview && $edit_interview['interview_type'] === 'Final') ? 'selected' : '' ?>>Final Round</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" required 
                                   value="<?= $edit_interview ? date('Y-m-d', strtotime($edit_interview['interview_datetime'])) : date('Y-m-d', strtotime('+1 day')) ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Time <span class="text-danger">*</span></label>
                            <input type="time" name="time" class="form-control" required 
                                   value="<?= $edit_interview ? date('H:i', strtotime($edit_interview['interview_datetime'])) : '10:00' ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Location/Meeting Link</label>
                            <input type="text" name="location" class="form-control" 
                                   placeholder="Office address or Zoom/Teams link"
                                   value="<?= $edit_interview ? htmlspecialchars($edit_interview['location']) : '' ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Interviewers</label>
                            <select name="interviewers[]" class="form-select select2-multiple" multiple="multiple">
                                <?php foreach ($interviewers as $interviewer): 
                                    $selected = $edit_interview && !empty($edit_interview['assigned_interviewers']) && in_array($interviewer['user_code'], $edit_interview['assigned_interviewers']) ? 'selected' : '';
                                ?>
                                <option value="<?= htmlspecialchars($interviewer['user_code']) ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($interviewer['full_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Select team members who will conduct the interview</div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Interview preparation notes, questions to ask, etc."><?= $edit_interview ? htmlspecialchars($edit_interview['notes']) : '' ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Outcome/Status</label>
                            <select name="outcome" class="form-select">
                                <option value="Scheduled" <?= ($edit_interview && $edit_interview['outcome'] === 'Scheduled') ? 'selected' : 'selected' ?>>Scheduled</option>
                                <option value="Positive" <?= ($edit_interview && $edit_interview['outcome'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
                                <option value="Negative" <?= ($edit_interview && $edit_interview['outcome'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
                                <option value="Neutral" <?= ($edit_interview && $edit_interview['outcome'] === 'Neutral') ? 'selected' : '' ?>>Neutral</option>
                                <option value="Rescheduled" <?= ($edit_interview && $edit_interview['outcome'] === 'Rescheduled') ? 'selected' : '' ?>>Rescheduled</option>
                                <option value="Cancelled" <?= ($edit_interview && $edit_interview['outcome'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Interview</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Interview Modal -->
<div class="modal fade" id="deleteInterviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="<?= $_SERVER['PHP_SELF'] . ($can_id ? '?can_id=' . $can_id : '') ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="interview_id" id="deleteInterviewId" value="">
                <input type="hidden" name="token" value="<?= Auth::token() ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title">Delete Interview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this interview? This action cannot be undone.</p>
                    <div class="alert alert-warning mt-3">
                        <i class="bx bx-info-circle me-1"></i>
                        <strong>Note:</strong> This will only remove the interview record, not the candidate profile.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Interview</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/panel/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<script>
$(document).ready(function() {
    // Initialize Select2 for interviewers
    $('.select2-multiple').select2({
        theme: "bootstrap-5",
        placeholder: "Select interviewers...",
        width: '100%'
    });
    
    // Edit interview modal
    $('#addInterviewModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const interviewId = button.data('edit-id');
        
        if (interviewId) {
            // Edit mode
            $('#modalTitle').text('Edit Interview');
            $('#formAction').val('update');
            $('#editInterviewId').val(interviewId);
            
            // This would be populated via AJAX in a real implementation
            // For now, we handle it server-side with PHP
        } else {
            // Add mode
            $('#modalTitle').text('Schedule New Interview');
            $('#formAction').val('add');
            $('#editInterviewId').val('');
            $('#interviewForm')[0].reset();
            
            // Set default date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            $('input[name="date"]').val(tomorrow.toISOString().split('T')[0]);
            $('input[name="time"]').val('10:00');
            
            // Pre-select candidate if on candidate-specific page
            <?php if ($can_id): ?>
            $('select[name="can_code"]').val('<?= htmlspecialchars($can_id) ?>').trigger('change');
            <?php endif; ?>
        }
    });
    
    // Delete interview modal
    $('#deleteInterviewModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const interviewId = button.data('id');
        $('#deleteInterviewId').val(interviewId);
    });
    
    // Update interview status
    $('.outcome-select').on('change', function() {
        const interviewId = $(this).data('id');
        const status = $(this).val();
        
        $.post('<?= $_SERVER['PHP_SELF'] ?>', {
            action: 'update_status',
            interview_id: interviewId,
            status: status,
            token: '<?= Auth::token() ?>'
        }, function(response) {
            if (response.success) {
                showToast('Interview status updated successfully!', 'success');
            }
        });
    });
    
    // Search and filtering
    $('#interviewSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#interviewsTable tbody tr').each(function() {
            const candidate = $(this).find('td:eq(0)').text().toLowerCase();
            const interviewer = $(this).find('td:eq(3)').text().toLowerCase();
            $(this).toggle(candidate.includes(searchTerm) || interviewer.includes(searchTerm));
        });
    });
    
    $('#statusFilter').on('change', function() {
        const status = $(this).val().toLowerCase();
        $('#interviewsTable tbody tr').each(function() {
            if (!status) {
                $(this).show();
                return;
            }
            const outcomeCell = $(this).find('td:eq(4) select');
            const outcome = outcomeCell.val().toLowerCase();
            $(this).toggle(outcome.includes(status));
        });
    });
    
    // Export functionality
    $('#exportBtn').on('click', function() {
        alert('Export feature coming soon. This will export filtered interviews to Excel/CSV.');
    });
    
    // View options
    $('[data-view]').on('click', function(e) {
        e.preventDefault();
        const view = $(this).data('view');
        
        $('.dropdown-item').removeClass('active');
        $(this).addClass('active');
        
        if (view === 'calendar') {
            window.location.href = 'interviews_calendar.php' + (<?= $can_id ? "'?can_id=$can_id'" : "''" ?>);
            return;
        }
        
        $('#interviewsTable tbody tr').each(function() {
            const isPast = $(this).hasClass('table-light');
            
            switch(view) {
                case 'upcoming':
                    $(this).toggle(!isPast);
                    break;
                case 'past':
                    $(this).toggle(isPast);
                    break;
                default:
                    $(this).show();
            }
        });
    });
    
    // Send reminder (placeholder)
    $(document).on('click', '[data-can-code]', function(e) {
        e.preventDefault();
        const canCode = $(this).data('can-code');
        const interviewId = $(this).data('interview-id');
        alert('Sending reminder to candidate ' + canCode + ' for interview ' + interviewId);
    });
    
    // Show toast notification
    function showToast(message, type = 'info') {
        const toastId = 'toast-' + Date.now();
        if ($('#toastContainer').length === 0) {
            $('body').append('<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
        }
        
        let bgColor, icon;
        switch(type) {
            case 'success':
                bgColor = 'bg-success';
                icon = 'bx bx-check-circle';
                break;
            case 'error':
                bgColor = 'bg-danger';
                icon = 'bx bx-error-circle';
                break;
            case 'warning':
                bgColor = 'bg-warning';
                icon = 'bx bx-error';
                break;
            default:
                bgColor = 'bg-info';
                icon = 'bx bx-info-circle';
        }
        
        $(`#toastContainer`).append(`
            <div id="${toastId}" class="toast ${bgColor} text-white" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header ${bgColor}">
                    <i class="${icon} me-2"></i>
                    <strong class="me-auto">Notification</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `);
        
        const toast = new bootstrap.Toast(document.getElementById(toastId));
        toast.show();
        
        $(`#${toastId}`).on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
});
</script>

<style>
.outcome-select {
    padding: 2px 24px 2px 8px !important;
    border: none !important;
    background: transparent !important;
    cursor: pointer;
}
.select2-container--bootstrap-5 .select2-selection {
    min-height: 38px !important;
}
.table-light td {
    background-color: #f9fafb !important;
}
.badge.bg-label-primary {
    background-color: #e7f4ff !important;
    color: #696cff !important;
}
</style>