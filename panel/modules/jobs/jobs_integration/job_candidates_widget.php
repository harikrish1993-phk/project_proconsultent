<?php
$job_id = $id ?? null;
if (!$job_id) return;

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT c.* FROM candidate_job_applications a LEFT JOIN candidates c ON a.can_code = c.can_code WHERE a.job_id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    return;
}
?>
<div class="card">
    <div class="card-header">Job Candidates</div>
    <div class="card-body">
        <table class="table">
            <thead><tr><th>Name</th><th>Status</th><th>Applied</th></tr></thead>
            <tbody>
                <?php foreach ($candidates as $c): ?>
                <tr>
                    <td><a href="../../candidates/view.php?id=<?php echo $c['can_code']; ?>"><?php echo htmlspecialchars($c['candidate_name']); ?></a></td>
                    <td><?php echo $c['status']; ?></td>
                    <td><?php echo $c['applied_at']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>