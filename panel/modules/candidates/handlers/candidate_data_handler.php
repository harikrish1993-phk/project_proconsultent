<?php
/**
 * Candidate Data Handler - DataTables Server-Side Processing
 * FIXED VERSION - Properly handles AJAX requests with error logging
 */

require_once __DIR__ . '/../../_common.php';

header('Content-Type: application/json');

// Enable error logging for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Candidate Data Handler Called");

try {
    // Verify authentication
    if (!isset($current_user_code)) {
        throw new Exception('Authentication required');
    }
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get DataTables parameters
    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 25);
    $searchValue = $_POST['search']['value'] ?? '';
    
    // Get custom filters
    $filters = [
        'quick_search' => $_POST['quick_search'] ?? '',
        'skills' => $_POST['skills'] ?? [], // Array from multi-select
        'experience' => $_POST['experience'] ?? '',
        'status' => $_POST['status'] ?? '',
        'location' => $_POST['location'] ?? '',
        'notice_period' => $_POST['notice_period'] ?? '',
        'assigned_to' => $_POST['assigned_to'] ?? ''
    ];
    
    error_log("Filters: " . json_encode($filters));
    
    // Build base query
    $baseQuery = "
        FROM candidates c
        LEFT JOIN user u ON c.assigned_to = u.user_code
        WHERE c.is_archived = 0
    ";
    
    $whereConditions = [];
    $bindTypes = '';
    $bindParams = [];
    
    // Quick search (name, email, skills)
    if (!empty($filters['quick_search'])) {
        $searchTerm = '%' . $filters['quick_search'] . '%';
        $whereConditions[] = "(
            c.first_name LIKE ? OR 
            c.last_name LIKE ? OR 
            c.email LIKE ? OR 
            c.skills LIKE ?
        )";
        $bindTypes .= 'ssss';
        $bindParams = array_merge($bindParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Skills filter (multi-select)
    if (!empty($filters['skills']) && is_array($filters['skills'])) {
        $skillConditions = [];
        foreach ($filters['skills'] as $skill) {
            $skillConditions[] = "c.skills LIKE ?";
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
                $whereConditions[] = "c.experience_years BETWEEN 0 AND 2";
                break;
            case '2-5':
                $whereConditions[] = "c.experience_years BETWEEN 2 AND 5";
                break;
            case '5-8':
                $whereConditions[] = "c.experience_years BETWEEN 5 AND 8";
                break;
            case '8+':
                $whereConditions[] = "c.experience_years >= 8";
                break;
        }
    }
    
    // Status filter
    if (!empty($filters['status'])) {
        $whereConditions[] = "c.status = ?";
        $bindTypes .= 's';
        $bindParams[] = $filters['status'];
    }
    
    // Location filter
    if (!empty($filters['location'])) {
        $whereConditions[] = "c.current_location = ?";
        $bindTypes .= 's';
        $bindParams[] = $filters['location'];
    }
    
    // Notice period filter
    if (!empty($filters['notice_period'])) {
        $whereConditions[] = "c.notice_period = ?";
        $bindTypes .= 's';
        $bindParams[] = $filters['notice_period'];
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
    } elseif ($current_user_level === 'recruiter') {
        // Recruiters only see their assigned candidates
        $whereConditions[] = "c.assigned_to = ?";
        $bindTypes .= 's';
        $bindParams[] = $current_user_code;
    }
    
    // DataTables search
    if (!empty($searchValue)) {
        $searchTerm = '%' . $searchValue . '%';
        $whereConditions[] = "(
            c.first_name LIKE ? OR 
            c.last_name LIKE ? OR 
            c.email LIKE ?
        )";
        $bindTypes .= 'sss';
        $bindParams = array_merge($bindParams, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Build WHERE clause
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = ' AND ' . implode(' AND ', $whereConditions);
    }
    
    // Count total records (without filters)
    $totalQuery = "SELECT COUNT(*) as total FROM candidates c WHERE c.is_archived = 0";
    if ($current_user_level === 'recruiter') {
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
    
    // Get data
    $dataQuery = "
        SELECT 
            c.id,
            c.can_code,
            c.first_name,
            c.last_name,
            CONCAT(c.first_name, ' ', c.last_name) as name,
            c.email,
            c.phone,
            c.job_title,
            c.skills,
            c.experience_years,
            c.current_location,
            c.notice_period,
            c.expected_salary,
            c.status,
            c.assigned_to,
            u.name as assigned_to_name,
            c.created_at,
            c.updated_at
        " . $baseQuery . $whereClause . "
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    // Add limit and offset to bind params
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
    
    // Return DataTables response
    $response = [
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ];
    
    error_log("Returning " . count($data) . " records");
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