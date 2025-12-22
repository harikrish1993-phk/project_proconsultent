<?php
// modules/candidates/interviews.php
require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';
require_once __DIR__ . '/../../../includes/header.php';

if (!Auth::check()) {
    header('Location: ../../../login.php');
    exit();
}

$can_id = $_GET['can_id'] ?? null; // Optional for candidate-specific

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $can_code = $_POST['can_code'];
            $date = $_POST['date'];
            $notes = $_POST['notes'];
            $outcome = $_POST['outcome'];
            $logged_by = Auth::user()['user_code'];
            
            $stmt = $conn->prepare("INSERT INTO interviews (can_code, interview_date, notes, outcome, logged_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $can_code, $date, $notes, $outcome, $logged_by);
            $stmt->execute();
            
            // Log to timeline
            $stmt = $conn->prepare("INSERT INTO candidates_edit_info (can_code, edited_field, new_value, edited_by) VALUES (?, 'Interview Scheduled', ?, ?)");
            $new_value = "Date: $date, Outcome: $outcome";
            $stmt->bind_param("sss", $can_code, $new_value, $logged_by);
            $stmt->execute();
            
            header('Location: ?action=interviews');
        }
    }
    
    // Fetch interviews
    $query = "SELECT i.*, c.candidate_name FROM interviews i LEFT JOIN candidates c ON i.can_code = c.can_code";
    if ($can_id) {
        $query .= " WHERE i.can_code = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $can_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    $interviews = [];
    while ($row = $result->fetch_assoc()) $interviews[] = $row;
    
    // Candidates for dropdown
    $candidates = [];
    $res = $conn->query("SELECT can_code, candidate_name FROM candidates");
    while ($row = $res->fetch_assoc()) $candidates[] = $row;
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Interviews</h4>
    
    <!-- Add Interview Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="row">
                    <div class="col-md-4">
                        <label>Candidate</label>
                        <select name="can_code" class="form-select" required>
                            <?php foreach ($candidates as $cand): ?>
                            <option value="<?php echo $cand['can_code']; ?>"><?php echo htmlspecialchars($cand['candidate_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label>Outcome</label>
                        <select name="outcome" class="form-select">
                            <option value="Positive">Positive</option>
                            <option value="Negative">Negative</option>
                            <option value="Neutral">Neutral</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Schedule Interview</button>
            </form>
        </div>
    </div>
    
    <!-- Interviews List -->
    <table class="table">
        <thead>
            <tr>
                <th>Candidate</th>
                <th>Date</th>
                <th>Outcome</th>
                <th>Notes</th>
                <th>By</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($interviews as $int): ?>
            <tr>
                <td><?php echo htmlspecialchars($int['candidate_name']); ?></td>
                <td><?php echo $int['interview_date']; ?></td>
                <td><span class="badge bg-<?php echo strtolower($int['outcome']); ?>"><?php echo $int['outcome']; ?></span></td>
                <td><?php echo nl2br(htmlspecialchars($int['notes'])); ?></td>
                <td><?php echo $int['logged_by']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>