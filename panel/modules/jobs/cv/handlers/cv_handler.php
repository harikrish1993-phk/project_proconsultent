<?php
// Load common bootstrap
require_once __DIR__ . '/../../_common.php';

try {

    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if ($action === 'assign') {
        $assigned_to = $_POST['assigned_to'];
        $stmt = $conn->prepare("UPDATE submittedcv SET assigned_to = ? WHERE id = ?");
        $stmt->bind_param("si", $assigned_to, $id);
        $stmt->execute();
        // Email notify
        $assigned_email = // fetch
        mail($assigned_email, "CV Assigned", "CV ID $id assigned.");
    } elseif ($action === 'status') {
        $status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE submittedcv SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM submittedcv WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        // Delete related notes
        $stmt = $conn->prepare("DELETE FROM cv_notes WHERE cv_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    } elseif ($action === 'bulk') {
        $ids = explode(',', $_POST['ids']);
        foreach ($ids as $cv_id) {
            // Apply bulk (assign/status/delete)
        }
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>