<?php
$id = intval($_GET['id'] ?? 0); // Job ID
if (!$id) {
    echo '<div class="alert alert-danger">Invalid job ID.</div>';
    return;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT requirements FROM jobs WHERE job_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $reqs = $stmt->get_result()->fetch_assoc()['requirements'];
    
    $keywords = explode(',', $reqs);
    $placeholders = implode(',', array_fill(0, count($keywords), '?'));
    
    $stmt = $conn->prepare("SELECT * FROM candidates WHERE skill_set LIKE CONCAT('%', $placeholders, '%')");
    $stmt->bind_param(str_repeat('s', count($keywords)), ...$keywords);
    $stmt->execute();
    $matched = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    return;
}
?>
<div class="card mb-4">
    <div class="card-header">Matched Candidates</div>
    <div class="card-body">
        <table class="table">
            <thead><tr><th>Name</th><th>Skills</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($matched as $cand): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cand['candidate_name']); ?></td>
                    <td><?php echo htmlspecialchars($cand['skill_set']); ?></td>
                    <td><a href="apply_candidate.php?job_id=<?php echo $id; ?>&can_code=<?php echo $cand['can_code']; ?>" class="btn btn-sm btn-success">Apply</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>