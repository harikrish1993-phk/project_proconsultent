<?php
require_once __DIR__ . '/../_common.php';
header('Content-Type: application/json');


if (!isset($_FILES['resume'])) {
    echo json_encode(['success' => false, 'message' => 'No resume uploaded']);
    exit;
}

$file = $_FILES['resume'];

// Validate file
$allowedTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode([
        'success' => false,
        'message' => 'Unsupported file format'
    ]);
    exit;
}

// Move to temp
$tmpPath = sys_get_temp_dir() . '/' . uniqid('resume_');
move_uploaded_file($file['tmp_name'], $tmpPath);

// Call Python parser (base parser only)
$cmd = escapeshellcmd("python3 " . __DIR__ . "/../../../ai/parse_resume.py " . $tmpPath);
$output = shell_exec($cmd);

@unlink($tmpPath);

$data = json_decode($output, true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Unable to extract details from resume'
    ]);
    exit;
}

/**
 * Map parser output → business fields
 */
$response = [
    'candidate_name' => $data['name'] ?? null,
    'email'          => $data['email'] ?? null,
    'phone'          => $data['mobile_number'] ?? null,
    'skills'         => $data['skills'] ?? [],
    'confidence'     => 0.8
];

// AI refinement can be plugged here later
// if (FEATURE_AI_REFINEMENT) { ... }
// After pyresparser
// $skills = $data['skills'];
// $prompt = "Normalize these skills: " . implode(', ', $skills);
// $ai_response = '';
$data['skills'] = $ai_response['normalized'];
echo json_encode([
    'success' => true,
    'data'    => $response
]);
