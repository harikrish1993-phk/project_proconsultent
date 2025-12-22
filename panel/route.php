<?php
// /dashboard.php

// Load core files
require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/core/Auth.php';

// Check authentication
if (!Auth::check()) {
    header('Location: index.php');
    exit();
}

// Get user info
$user = Auth::user();
$user_code = $user['user_code'];
$user_level = $user['level'];

// Load database
require_once __DIR__ . '/../includes/core/Database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

// ============================================================================
// UNIVERSAL DATA (Both roles need this)
// ============================================================================

// Get basic stats
$stats = [];

// User-specific stats
if ($user_level === 'admin') {
    // Admin: System-wide stats
    $query = "SELECT 
        (SELECT COUNT(*) FROM candidates) as total_candidates,
        (SELECT COUNT(*) FROM candidates WHERE status = 'active') as active_candidates,
        (SELECT COUNT(*) FROM candidates WHERE follow_up_date = CURDATE()) as today_followups,
        (SELECT COUNT(*) FROM jobs WHERE job_status = 'active') as active_jobs,
        (SELECT COUNT(*) FROM jobs WHERE job_status = 'pending') as pending_jobs,
        (SELECT COUNT(*) FROM user WHERE status = 'active') as active_users";
} else {
    // User: Personal stats
    $query = "SELECT 
        (SELECT COUNT(*) FROM candidates WHERE created_by = '$user_code') as my_candidates,
        (SELECT COUNT(*) FROM candidates WHERE assigned_to = '$user_code') as assigned_candidates,
        (SELECT COUNT(*) FROM candidates WHERE follow_up_date = CURDATE() 
         AND (created_by = '$user_code' OR assigned_to = '$user_code')) as today_followups,
        (SELECT COUNT(*) FROM jobs WHERE created_by = '$user_code' AND job_status = '0') as waiting_jobs,
        (SELECT COUNT(*) FROM jobs WHERE created_by = '$user_code' AND job_status = '1') as posted_jobs";
}

$result = mysqli_query($conn, $query);
$stats = mysqli_fetch_assoc($result);

// Get follow-ups (CRITICAL for recruitment workflow)
$followup_query = "
    SELECT 
        c.can_code,
        c.candidate_name,
        c.follow_up_date,
        c.contact_number,
        c.status,
        u.name as created_by_name,
        ca.username as assigned_to
    FROM candidates c
    LEFT JOIN user u ON c.created_by = u.user_code
    LEFT JOIN candidate_assignments ca ON c.can_code = ca.can_code
    WHERE c.follow_up_date IS NOT NULL 
        AND c.follow_up_date != ''
        AND c.status = 'active'
";

// Add role-specific filter
if ($user_level !== 'admin') {
    $followup_query .= " AND (c.created_by = '$user_code' OR ca.usercode = '$user_code')";
}

$followup_query .= " ORDER BY 
    CASE
        WHEN c.follow_up_date < CURDATE() THEN 1  -- Overdue first
        WHEN c.follow_up_date = CURDATE() THEN 2  -- Today
        ELSE 3  -- Future
    END,
    c.follow_up_date ASC
    LIMIT 15";

$followup_result = mysqli_query($conn, $followup_query);
$followups = [];
while ($row = mysqli_fetch_assoc($followup_result)) {
    $followups[] = $row;
}

$conn->close();

// ============================================================================
// RENDER APPROPRIATE DASHBOARD
// ============================================================================

if ($user_level === 'admin') {
    // Load admin dashboard with system-wide data
    require_once __DIR__ . '/admin.php';
} else {
    // Load user dashboard with personal data
    require_once __DIR__ . '/recruiter.php';
}
?>