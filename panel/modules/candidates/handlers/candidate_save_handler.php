<?php
/**
 * Candidate Save Handler - Create and Update
 */
require_once __DIR__ . '/../_common.php';

$conn = Database::getInstance()->getConnection();
$user = Auth::user();

function validateCandidate($data, $conn) {
    $errors = [];
    if (empty(trim($data['candidate_name'] ?? ''))) {
        $errors[] = 'Candidate name required';
    }
    if (!empty($data['email_id']) &&
        !filter_var($data['email_id'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email';
    }
    return $errors;
}

function uploadFile($file, $can_code) {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;

    $allowed = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    $max = 5 * 1024 * 1024;

    if ($file['size'] > $max) {
        throw new Exception('File too large');
    }

    $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);
    if (!in_array($mime, $allowed)) {
        throw new Exception('Invalid file type');
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = $can_code . '_' . uniqid() . '.' . $ext;
    $path = 'uploads/candidates/' . $name;

    move_uploaded_file($file['tmp_name'], "../$path");
    return $path;
}

$conn->begin_transaction();

try {
    $checkStmt = $conn->prepare("SELECT can_code FROM candidates WHERE email_id = ?");
    $checkStmt->bind_param('s', $email);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        die(json_encode(['success' => false, 'message' => 'Candidate already exists']));
    }
    $errors = validateCandidate($_POST, $conn);
    if ($errors) throw new Exception(implode(', ', $errors));

    $can_code = $_POST['can_code'] ?? uniqid('CAN');

    if ($_POST['action'] === 'create') {
        $stmt = $conn->prepare("
            INSERT INTO candidates (can_code, candidate_name, email_id, created_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss",
            $can_code,
            $_POST['candidate_name'],
            $_POST['email_id'],
            $user['user_code']
        );
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("
            UPDATE candidates
            SET candidate_name = ?, email_id = ?, updated_at = NOW()
            WHERE can_code = ?
        ");
        $stmt->bind_param("sss",
            $_POST['candidate_name'],
            $_POST['email_id'],
            $can_code
        );
        $stmt->execute();
    }

    foreach (['candidate_cv','consultancy_cv','consent'] as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $path = uploadFile($_FILES[$field], $can_code);
            $conn->query("UPDATE candidates SET $field = '$path' WHERE can_code = '$can_code'");
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'can_code' => $can_code]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
