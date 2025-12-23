<?php
require_once __DIR__ . '/../_common.php';

$conn = Database::getInstance()->getConnection();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=candidates.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['Code','Name','Email','Status']);

$res = $conn->query("
    SELECT can_code, candidate_name, email_id, status
    FROM candidates WHERE is_archived = 0
");

while ($r = $res->fetch_assoc()) {
    foreach ($r as &$v) {
        if (preg_match('/^[=+\-@]/', $v)) $v = "'".$v;
    }
    fputcsv($out, $r);
}
fclose($out);
