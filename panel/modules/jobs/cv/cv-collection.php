<?php
require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';
require_once __DIR__ . '/../../../includes/header.php';

if (!Auth::check()) {
    header('Location: ../../../login.php');
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("SELECT * FROM submittedcv ORDER BY submitted_at DESC");
    $cvs = [];
    while ($row = $stmt->fetch_assoc()) $cvs[] = $row;
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">CV Collection Archive</h4>
    
    <input type="text" id="cvSearch" class="form-control mb-3" placeholder="Search CVs...">
    
    <table class="table">
        <thead>
            <tr>
                <th>Candidate</th>
                <th>Job</th>
                <th>Submitted</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cvs as $cv): ?>
            <tr>
                <td><?php echo htmlspecialchars($cv['candidate_name']); ?></td>
                <td><?php echo htmlspecialchars($cv['job_title']); ?></td>
                <td><?php echo $cv['submitted_at']; ?></td>
                <td>
                    <a href="view.php?id=<?php echo $cv['id']; ?>" class="btn btn-sm btn-info">View</a>
                    <a href="convert.php?id=<?php echo $cv['id']; ?>" class="btn btn-sm btn-success">Convert</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
$('#cvSearch').on('keyup', function() {
    const value = this.value.toLowerCase();
    $('table tbody tr').filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});
</script>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>