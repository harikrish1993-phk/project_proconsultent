<?php
/**
 * Duplicate Check API
 * Check if email already exists
 */

require_once __DIR__ . '/../../../../includes/config/config.php';
require_once __DIR__ . '/../../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../../includes/core/Database.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['exists' => false, 'error' => 'Unauthorized']);
    exit();
}

$email = $_GET['email'] ?? '';
$exclude_code = $_GET['exclude'] ?? ''; // For edit mode

if (empty($email)) {
    echo json_encode(['exists' => false]);
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

if ($exclude_code) {
    $stmt = $conn->prepare("
        SELECT can_code, candidate_name 
        FROM candidates 
        WHERE email_id = ? AND can_code != ? AND is_archived = 0
        LIMIT 1
    ");
    $stmt->bind_param("ss", $email, $exclude_code);
} else {
    $stmt = $conn->prepare("
        SELECT can_code, candidate_name 
        FROM candidates 
        WHERE email_id = ? AND is_archived = 0
        LIMIT 1
    ");
    $stmt->bind_param("s", $email);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $candidate = $result->fetch_assoc();
    echo json_encode([
        'exists' => true,
        'candidate' => $candidate
    ]);
} else {
    echo json_encode(['exists' => false]);
}

$stmt->close();
$conn->close();
?>
