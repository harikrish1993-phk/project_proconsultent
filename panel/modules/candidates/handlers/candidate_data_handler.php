<?php
/**
 * Candidate Data Handler - COMPLETE VERSION
 * Based on ACTUAL database schema with all fields
 */

require_once __DIR__ . '/../../_common.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Auth check
    if (!isset($current_user_code)) {
        throw new Exception('Authentication required');
    }
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // DataTables parameters
    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 25);
    $searchValue = $_POST['search']['value'] ?? '';
    
    // Custom filters (matching your schema)
    $filters = [
        'quick_search' => $_POST['quick_search'] ?? '',
        'skill_set' => $_POST['skill_set'] ?? [], // Array from multi-select
        'experience' => $_POST['experience'] ?? '',
        'lead_type' => $_POST['lead_type'] ?? '', // Cold/Warm/Hot/Blacklist
        'lead_type_role' => $_POST['lead_type_role'] ?? '',
        'current_location' => $_POST['current_location'] ?? '',
        'preferred_location' => $_POST['preferred_location'] ?? '',
        'work_auth_status' => $_POST['work_auth_status'] ?? '',
        'current_working_status' => $_POST['current_working_status'] ?? '',
        'notice_period' => $_POST['notice_period'] ?? '',
        'follow_up' => $_POST['follow_up'] ?? '',
        'assigned_to' => $_POST['assigned_to'] ?? '',
        'date_from' => $_POST['date_from'] ?? '',
        'date_to' => $_POST['date_to'] ?? ''
    ];
    
    error_log("Candidate filters: " . json_encode($filters));
    
    // Base query with work_auth join
    $baseQuery = "
        FROM candidates c
        LEFT JOIN user u ON c.assigned_to = u.user_code
        LEFT JOIN work_authorization wa ON c.work_auth_status = wa.id
        WHERE 1=1
    ";
    
    $whereConditions = [];
    $bindTypes = '';
    $bindParams = [];
    
    // Quick search (name, email, skills, role)
    if (!empty($filters['quick_search'])) {
        $searchTerm = '%' . $filters['quick_search'] . '%';
        $whereConditions[] = "(
            c.candidate_name LIKE ? OR 
            c.email_id LIKE ? OR 
            c.alternate_email_id LIKE ? OR
            c.skill_set LIKE ? OR
            c.role_addressed LIKE ? OR
            c.current_position LIKE ?
        )";
        $bindTypes .= 'ssssss';
        $bindParams = array_merge($bindParams, array_fill(0, 6, $searchTerm));
    }
    
    // Skills filter (multi-select on skill_set field)
    if (!empty($filters['skill_set']) && is_array($filters['skill_set'])) {
        $skillConditions = [];
        foreach ($filters['skill_set'] as $skill) {
            $skillConditions[] = "c.skill_set LIKE ?";
            $bindTypes .= 's';
            $bindParams[] = '%' . $skill . '%';
        }
        if (!empty($skillConditions)) {
            $whereConditions[] = '(' . implode(' OR ', $skillConditions) . ')';
        }
    }
    
    // Experience range filter
    if (!empty($filters['experience'])) {
        switch ($filters['experience']) {
            case '0-2':
                $whereConditions[] = "c.experience BETWEEN 0 AND 2";
                break;
            case '2-5':
                $whereConditions[] = "c.experience BETWEEN 2 AND 5";
                break;
            case '5-8':
                $whereConditions[] = "c.experience BETWEEN 5 AND 8";
                break;
            case '8-15':
                $whereConditions[] = "c.experience BETWEEN 8 AND 15";
                break;
            case '15+':
                $whereConditions[] = "c.experience >= 15";
                break;
        }
    }
    
    // Lead type filter (IMPORTANT!)
    if (!empty($filters['lead_type'])) {
        $whereConditions[] = "c.lead_type = ?";
        $bindTypes .= 's';
        $bindParams[] = $filters['lead_type'];
    }
    
    // Lead type role filter
    if (!empty($filters['lead_type_role'])) {
        $whereConditions[] = "c.lead_type_role = ?";
        $bindTypes .= 's';
        $bindParams[] = $filters['lead_type_role'];
    }
    
    // Current location filter
    if (!empty($filters['current_location'])) {
        $whereConditions[] = "c.current_location = ?";
        $bindTypes .= 's';
        $bindParams[] = $filters['current_location'];
    }
    
    // Preferred location filter
    if (!empty($filters['preferred_location'])) {
        $whereConditions[] = "c.preferred_location = ?";
        $bindTypes .= 's';
        $bindParams[] = $filters['preferred_location'];
    }
    
    // Work auth status filter
    if (!empty($filters['work_auth_status'])) {
        $whereConditions[] = "c.work_auth_status = ?";
        $bindTypes .= 'i';
        $bindParams[] = intval($filters['work_auth_status']);
    }
    
    // Current working status filter
    if (!empty($filters['current_working_status'])) {
        $whereConditions[] = "c.current_working_status = ?";
        $bindTypes .= 's';
        $bindParams[] = $filters['current_working_status'];
    }
    
    // Notice period filter
    if (!empty($filters['notice_period'])) {
        switch ($filters['notice_period']) {
            case 'immediate':
                $whereConditions[] = "c.notice_period = 0";
                break;
            case '15':
                $whereConditions[] = "c.notice_period <= 15";
                break;
            case '30':
                $whereConditions[] = "c.notice_period <= 30";
                break;
            case '60':
                $whereConditions[] = "c.notice_period <= 60";
                break;
            case '90':
                $whereConditions[] = "c.notice_period <= 90";
                break;
            case '90+':
                $whereConditions[] = "c.notice_period > 90";
                break;
        }
    }
    
    // Follow-up filter
    if (!empty($filters['follow_up'])) {
        $whereConditions[] = "c.follow_up = ?";
        $bindTypes .= 's';
        $bindParams[] = $filters['follow_up'];
    }
    
    // Date range filter
    if (!empty($filters['date_from'])) {
        $whereConditions[] = "DATE(c.created_at) >= ?";
        $bindTypes .= 's';
        $bindParams[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $whereConditions[] = "DATE(c.created_at) <= ?";
        $bindTypes .= 's';
        $bindParams[] = $filters['date_to'];
    }
    
    // Assigned to filter (Admin only)
    if ($current_user_level === 'admin' && !empty($filters['assigned_to'])) {
        if ($filters['assigned_to'] === 'unassigned') {
            $whereConditions[] = "c.assigned_to IS NULL";
        } else {
            $whereConditions[] = "c.assigned_to = ?";
            $bindTypes .= 's';
            $bindParams[] = $filters['assigned_to'];
        }
    } elseif ($current_user_level === 'user') {
        // Recruiters only see their assigned candidates
        $whereConditions[] = "c.assigned_to = ?";
        $bindTypes .= 's';
        $bindParams[] = $current_user_code;
    }
    
    // DataTables global search
    if (!empty($searchValue)) {
        $searchTerm = '%' . $searchValue . '%';
        $whereConditions[] = "(
            c.candidate_name LIKE ? OR 
            c.email_id LIKE ? OR 
            c.contact_details LIKE ?
        )";
        $bindTypes .= 'sss';
        $bindParams = array_merge($bindParams, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Build WHERE clause
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = ' AND ' . implode(' AND ', $whereConditions);
    }
    
    // Count total records
    $totalQuery = "SELECT COUNT(*) as total FROM candidates c WHERE 1=1";
    if ($current_user_level === 'user') {
        $totalQuery .= " AND c.assigned_to = '$current_user_code'";
    }
    $totalResult = $conn->query($totalQuery);
    $totalRecords = $totalResult->fetch_assoc()['total'];
    
    // Count filtered records
    $filteredQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
    if (!empty($bindParams)) {
        $stmt = $conn->prepare($filteredQuery);
        if (!empty($bindTypes)) {
            $stmt->bind_param($bindTypes, ...$bindParams);
        }
        $stmt->execute();
        $filteredRecords = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    } else {
        $filteredRecords = $conn->query($filteredQuery)->fetch_assoc()['total'];
    }
    
    // Get data with ALL fields from your schema
    $dataQuery = "
        SELECT 
            c.can_code,
            c.candidate_name,
            c.contact_details,
            c.alternate_contact_details,
            c.email_id,
            c.alternate_email_id,
            c.linkedin,
            c.role_addressed,
            c.current_position,
            c.experience,
            c.notice_period,
            c.current_location,
            c.preferred_location,
            c.current_employer,
            c.current_agency,
            c.current_salary,
            c.expected_salary,
            c.can_join,
            c.current_daily_rate,
            c.expected_daily_rate,
            c.current_working_status,
            c.languages,
            c.lead_type,
            c.lead_type_role,
            c.work_auth_status,
            wa.status as work_auth_name,
            c.follow_up,
            c.follow_up_date,
            c.face_to_face,
            c.skill_set,
            c.extra_details,
            c.assigned_to,
            u.name as assigned_to_name,
            c.created_at,
            c.updated_at
        " . $baseQuery . $whereClause . "
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $bindTypes .= 'ii';
    $bindParams = array_merge($bindParams, [$length, $start]);
    
    $stmt = $conn->prepare($dataQuery);
    if (!empty($bindTypes)) {
        $stmt->bind_param($bindTypes, ...$bindParams);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    
    // Return response
    $response = [
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ];
    
    error_log("Returning " . count($data) . " candidates");
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Candidate Data Handler Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'draw' => intval($_POST['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ]);
}
?>
