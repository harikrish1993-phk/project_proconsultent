<?php
require_once '../includes/config/config.php';

echo "<h1>Database Connection Test</h1>";

// Test connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    echo "<p style='color:red;'>❌ Connection FAILED: " . mysqli_connect_error() . "</p>";
    exit;
}

echo "<p style='color:green;'>✅ Connected successfully to database: " . DB_NAME . "</p>";

// Test all tables exist
$requiredTables = [
    'user', 'tokens', 'password_resets',
    'companies', 'contacts', 'contact_documents',
    'jobs', 'candidates', 'candidate_documents', 'candidate_notes',
    'job_applications', 'interviews', 'cv_inbox',
    'call_logs', 'hr_comments', 'candidate_assignments', 'job_assignments',
    'work_authorization', 'technical_skills',
    'activity_log', 'settings', 'candidates_edit_info'
];

echo "<h2>Table Check:</h2>";
echo "<ul>";

$result = mysqli_query($conn, "SHOW TABLES");
$existingTables = [];
while ($row = mysqli_fetch_array($result)) {
    $existingTables[] = $row[0];
}

$missingTables = [];
foreach ($requiredTables as $table) {
    if (in_array($table, $existingTables)) {
        echo "<li style='color:green;'>✅ $table</li>";
    } else {
        echo "<li style='color:red;'>❌ $table - MISSING</li>";
        $missingTables[] = $table;
    }
}
echo "</ul>";

if (!empty($missingTables)) {
    echo "<p style='color:red;font-weight:bold;'>";
    echo "⚠️ WARNING: Missing " . count($missingTables) . " tables. Run complete_schema_v2.sql";
    echo "</p>";
} else {
    echo "<p style='color:green;font-weight:bold;'>✅ All required tables exist!</p>";
}

mysqli_close($conn);
?>