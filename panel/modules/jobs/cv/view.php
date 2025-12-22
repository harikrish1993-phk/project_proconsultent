<?php
require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';
require_once __DIR__ . '/../../../includes/header.php';

if (!Auth::check()) {
    header('Location: ../../../login.php');
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo '<div class="alert alert-danger">Invalid CV ID.</div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM submittedcv WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $cv = $stmt->get_result()->fetch_assoc();
    if (!$cv) throw new Exception('CV not found');
    
    // Notes
    $stmt = $conn->prepare("SELECT * FROM cv_notes WHERE cv_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">CV View: <?php echo htmlspecialchars($cv['candidate_name']); ?></h4>
    
    <div class="card mb-4">
        <div class="card-body">
            <h5>CV Preview</h5>
            <iframe src="<?php echo htmlspecialchars($cv['cv_path']); ?>" width="100%" height="600"></iframe>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <h5>Details</h5>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($cv['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($cv['phone']); ?></p>
            <p><strong>Job:</strong> <?php echo htmlspecialchars($cv['job_title']); ?></p>
            <p><strong>Submitted:</strong> <?php echo $cv['submitted_at']; ?></p>
            <p><strong>Status:</strong> <?php echo $cv['status']; ?></p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Notes</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="handlers/note_handler.php">
                <input type="hidden" name="cv_id" value="<?php echo $id; ?>">
                <textarea name="note" class="form-control" rows="3"></textarea>
                <button type="submit" class="btn btn-primary mt-3">Add Note</button>
            </form>
            <div class="mt-3">
                <?php foreach ($notes as $note): ?>
                <div class="alert alert-info">
                    <p><?php echo nl2br(htmlspecialchars($note['note'])); ?></p>
                    <small><?php echo $note['created_at']; ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <a href="convert.php?id=<?php echo $id; ?>" class="btn btn-success mt-3">Convert to Candidate</a>
</div>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>