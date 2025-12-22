<?php
require_once '../../../includes/auth.php';
require_once '../../../includes/db.php';

header('Content-Type: application/json');

// Check authentication
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$userId = Auth::userId();

try {
    // Get filter parameter
    $filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';
    
    // DataTables parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 25;
    
    // Base query
    $baseQuery = "FROM contacts c
                  LEFT JOIN users u ON c.assigned_to = u.user_id
                  WHERE c.is_archived = 0 
                  AND c.status NOT IN ('converted', 'not_interested')";
    
    $conditions = [];
    $params = [];
    $types = '';
    
    // Apply filter
    switch ($filter) {
        case 'overdue':
            $conditions[] = "c.next_follow_up < CURDATE()";
            break;
            
        case 'today':
            $conditions[] = "c.next_follow_up = CURDATE()";
            break;
            
        case 'this_week':
            $conditions[] = "c.next_follow_up BETWEEN DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            break;
            
        case 'my':
            $conditions[] = "c.assigned_to = ?";
            $params[] = $userId;
            $types .= 'i';
            break;
            
        case 'all':
            $conditions[] = "c.next_follow_up IS NOT NULL";
            break;
    }
    
    // Combine conditions
    if (!empty($conditions)) {
        $baseQuery .= " AND " . implode(' AND ', $conditions);
    }
    
    // Count total records
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery;
    
    if (!empty($params)) {
        $stmt = $conn->prepare($countQuery);
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $totalRecords = $stmt->get_result()->fetch_assoc()['total'];
    } else {
        $totalRecords = $conn->query($countQuery)->fetch_assoc()['total'];
    }
    
    // Fetch data
    $dataQuery = "SELECT 
                    c.contact_id,
                    c.first_name,
                    c.last_name,
                    c.email,
                    c.status,
                    c.priority,
                    c.next_follow_up,
                    c.last_contacted_date,
                    CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name,
                    DATEDIFF(CURDATE(), c.next_follow_up) as days_overdue
                  " . $baseQuery . "
                  ORDER BY c.next_follow_up ASC
                  LIMIT ? OFFSET ?";
    
    // Add limit and offset to params
    $params[] = $length;
    $params[] = $start;
    $types .= 'ii';
    
    $stmt = $conn->prepare($dataQuery);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    // Response
    $response = [
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>
