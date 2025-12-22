<?php
require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';

$id = intval($_GET['id'] ?? 0); // Job ID
if (!$id) {
    echo '<div class="alert alert-danger">Invalid job ID.</div>';
    return;
}
?>
<div class="card mb-4">
    <div class="card-header">Apply Candidate to Job</div>
    <div class="card-body">
        <form method="POST" action="../handlers/candidate_job_application_handler.php">
            <input type="hidden" name="job_id" value="<?php echo $id; ?>">
            <select name="can_code" class="form-select">
                <?php
                $db = Database::getInstance();
                $conn = $db->getConnection();
                $stmt = $conn->query("SELECT can_code, candidate_name FROM candidates");
                while ($row = $stmt->fetch_assoc()) {
                    echo '<option value="' . htmlspecialchars($row['can_code']) . '">' . htmlspecialchars($row['candidate_name']) . '</option>';
                }
                ?>
            </select>
            <button type="submit" class="btn btn-primary mt-3">Apply</button>
        </form>
    </div>
</div>