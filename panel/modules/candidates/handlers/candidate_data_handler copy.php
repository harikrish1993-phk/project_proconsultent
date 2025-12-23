<?php
/**
 * Candidate Data Handler - Server-side processing with ALL filters
 */
require_once __DIR__ . '/../_common.php';

try {

$user = Auth::user();
$db = Database::getInstance();
$conn = $db->getConnection();

// Get filter parameters
$params = [
    'lead_type_role' => $_GET['lead_type_role'] ?? null,
    'lead_type' => $_GET['lead_type'] ?? null,
    'work_auth' => $_GET['work_auth'] ?? null,
    'assigned' => $_GET['assigned'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null,
    'search' => $_GET['search'] ?? null,
    'page' => (int)($_GET['page'] ?? 1),
    'limit' => (int)($_GET['limit'] ?? 50)
];

// Build query
$query = "SELECT SQL_CALC_FOUND_ROWS 
            c.can_code as id,
            c.candidate_name,
            c.email_id,
            c.contact_details,
            c.lead_type_role,
            c.lead_type,
            c.work_auth_status,
            c.follow_up,
            c.updated_at,
            GROUP_CONCAT(DISTINCT ca.username) as assigned_users
          FROM candidates c
          LEFT JOIN candidate_assignments ca ON c.can_code = ca.can_code
          WHERE 1=1";

$conditions = [];
$bind_types = "";
$bind_values = [];

// Apply filters
if ($params['lead_type_role'] && $params['lead_type_role'] !== 'all') {
    $conditions[] = "c.lead_type_role = ?";
    $bind_types .= "i";
    $bind_values[] = $params['lead_type_role'];
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

// Search term
if ($params['search']) {
    $conditions[] = "(c.candidate_name LIKE ? OR c.email_id LIKE ? OR c.contact_details LIKE ? OR c.skill_set LIKE ?)";
    $bind_types .= "ssss";
    $search_term = "%{$params['search']}%";
    $bind_values[] = $search_term;
    $bind_values[] = $search_term;
    $bind_values[] = $search_term;
    $bind_values[] = $search_term;
}

if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

// Group and order
$query .= " GROUP BY c.can_code ORDER BY c.updated_at DESC";

// Pagination
$offset = ($params['page'] - 1) * $params['limit'];
$query .= " LIMIT ? OFFSET ?";
$bind_types .= "ii";
$bind_values[] = $params['limit'];
$bind_values[] = $offset;

// Prepare and execute
$stmt = $conn->prepare($query);

if ($bind_types) {
    $stmt->bind_param($bind_types, ...$bind_values);
}

 // If query fails
if (!$stmt->execute()) throw new Exception($stmt->error);
    
$result = $stmt->get_result();

$candidates = [];
while ($row = $result->fetch_assoc()) {
    $candidates[] = $row;
}

// Get total count
$total_result = $conn->query("SELECT FOUND_ROWS() as total");
$total_row = $total_result->fetch_assoc();
$total = $total_row['total'];

$pages = ceil($total / $params['limit']);

$response = [
    'success' => true,
    'candidates' => $candidates,
    'total' => $total,
    'pages' => $pages,
    'current_page' => $params['page']
];

header('Content-Type: application/json');
echo json_encode($response);

$stmt->close();
$conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}
?>
