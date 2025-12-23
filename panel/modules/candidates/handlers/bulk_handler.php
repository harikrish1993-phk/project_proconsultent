<?php
// modules/candidates/handlers/bulk_handler.php
require_once __DIR__ . '/../_common.php';

header('Content-Type: application/json');


$conn = Database::getInstance()->getConnection();
$action = $_POST['action'];
$ids = $_POST['ids'] ?? [];

$conn->begin_transaction();

try {
    foreach ($ids as $id) {
        if ($action === 'archive') {
            $conn->query("UPDATE candidates SET is_archived = 1 WHERE can_code='$id'");
        }
        if ($action === 'status') {
            $status = $_POST['status'];
            $conn->query("UPDATE candidates SET status='$status' WHERE can_code='$id'");
        }
    }
    $conn->commit();
    echo json_encode(['success'=>true]);
} catch(Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
