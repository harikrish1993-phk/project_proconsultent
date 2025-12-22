<?php
require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';

header('Content-Type: application/json');

try {
    if (!Auth::check()) throw new Exception('Unauthorized');
    
    if (Auth::token() !== ($_POST['token'] ?? '')) throw new Exception('Invalid token');

    $user = Auth::user();
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Parameters (from POST for server-side)
    $params = [
        'search' => $_POST['search'] ?? '',
        'lead_role' => $_POST['lead_role'] ?? '',
        'lead_type' => $_POST['lead_type'] ?? '',
        'work_auth' => $_POST['work_auth'] ?? '',
        'assigned' => $_POST['assigned'] ?? '',
        'date_from' => $_POST['date_from'] ?? '',
        'date_to' => $_POST['date_to'] ?? '',
        'limit' => intval($_POST['length'] ?? 10),
        'offset' => intval($_POST['start'] ?? 0),
        'order' => $_POST['order'][0]['column'] ?? 0,
        'dir' => $_POST['order'][0]['dir'] ?? 'desc'
    ];
    
    $base_query = "FROM candidates c LEFT JOIN user u ON c.created_by = u.user_code";
    $where = " WHERE 1=1";
    $bind_types = '';
    $bind_params = [];
    
    if ($params['search']) {
        $search = "%{$params['search']}%";
        $where .= " AND (c.candidate_name LIKE ? OR c.email_id LIKE ? OR c.contact_details LIKE ?)";
        $bind_types .= 'sss';
        $bind_params = array_merge($bind_params, [$search, $search, $search]);
    }
    
    if ($params['lead_role']) {
        $where .= " AND c.lead_type_role = ?";
        $bind_types .= 's';
        $bind_params[] = $params['lead_role'];
    }
    
    if ($params['lead_type'] && $params['lead_type'] !== 'all') {
        $conditions[] = "c.lead_type = ?";
        $bind_types .= "s";
        $bind_values[] = $params['lead_type'];
    }

    if ($params['work_auth'] && $params['work_auth'] !== 'all') {
        $conditions[] = "c.work_auth_status = ?";
        $bind_types .= "s";
        $bind_values[] = $params['work_auth'];
    }

    // Assigned filter
    if ($user['level'] !== 'admin' || ($params['assigned'] && $params['assigned'] !== 'all')) {
        if ($user['level'] === 'admin' && $params['assigned'] && $params['assigned'] !== 'all') {
            $conditions[] = "ca.usercode = ?";
            $bind_types .= "s";
            $bind_values[] = $params['assigned'];
        } elseif ($user['level'] !== 'admin') {
            // Non-admin only sees their assigned candidates
            $conditions[] = "ca.usercode = ?";
            $bind_types .= "s";
            $bind_values[] = $user['user_code'];
        }
    }

    // Date range
    if ($params['date_from']) {
        $conditions[] = "DATE(c.updated_at) >= ?";
        $bind_types .= "s";
        $bind_values[] = $params['date_from'];
    }

    if ($params['date_to']) {
        $conditions[] = "DATE(c.updated_at) <= ?";
        $bind_types .= "s";
        $bind_values[] = $params['date_to'];
    }


    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }
    
    if ($user['level'] !== 'admin') {
        $where .= " AND (c.created_by = ? OR EXISTS (SELECT 1 FROM candidate_assignments ca WHERE ca.can_code = c.can_code AND ca.user_code = ?))";
        $bind_types .= 'ss';
        $bind_params = array_merge($bind_params, [$user['user_code'], $user['user_code']]);
    }
    
    // Count query
    $count_query = "SELECT COUNT(*) as total $base_query $where";
    $stmt = $conn->prepare($count_query);
    if ($bind_types) $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Data query
    $columns = ['c.can_code', 'c.candidate_name', 'c.email_id', 'c.contact_details', 'c.lead_type', 'c.status'];
    $order_col = $columns[$params['order']] ?? 'c.created_at';
    $data_query = "SELECT $base_query $where ORDER BY $order_col {$params['dir']} LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($data_query);
    $bind_types .= 'ii';
    $bind_params = array_merge($bind_params, [$params['limit'], $params['offset']]);
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'draw' => intval($_POST['draw']),
        'recordsTotal' => $total,
        'recordsFiltered' => $total, // For simple filtering
        'data' => $candidates
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>