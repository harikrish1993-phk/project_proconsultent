<?php
require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';

if (!Auth::check()) {
    header('Location: ../../../login.php');
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo 'Invalid ID';
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM candidates WHERE can_code = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $candidate = $stmt->get_result()->fetch_assoc();
    if (!$candidate) throw new Exception('Not found');
    
    // Timeline, dynamic, etc. as in view
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
    exit();
}

// Print-friendly CSS
?>
<style>
body { font-family: Arial, sans-serif; }
.print-section { page-break-inside: avoid; }
@media print {
    .no-print { display: none; }
}
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">
        <?php echo htmlspecialchars($candidate['candidate_name']); ?>
    </h2>

    <div class="no-print">
        <a href="?action=edit&id=<?php echo $id; ?>" class="btn btn-primary btn-sm">
            Edit Profile
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            Print
        </button>
    </div>
</div>

<div class="container">
    <h2>Candidate Full View: <?php echo htmlspecialchars($candidate['candidate_name']); ?></h2>
    <div class="card mb-4 print-section">
    <div class="card-body row g-3">
        <div class="col-md-4">
            <strong>Email:</strong><br>
            <?php echo htmlspecialchars($candidate['email_id']); ?>
        </div>
        <div class="col-md-4">
            <strong>Contact:</strong><br>
            <?php echo htmlspecialchars($candidate['contact_details']); ?>
        </div>
        <div class="col-md-4">
            <strong>Current Title:</strong><br>
            <?php echo htmlspecialchars($candidate['current_designation'] ?? '-'); ?>
        </div>

        <div class="col-md-4">
            <strong>Total Experience:</strong><br>
            <?php echo htmlspecialchars($candidate['total_experience'] ?? '-'); ?>
        </div>
        <div class="col-md-4">
            <strong>Location:</strong><br>
            <?php echo htmlspecialchars($candidate['location'] ?? '-'); ?>
        </div>
        <div class="col-md-4">
            <strong>Profile Status:</strong><br>
            <?php echo htmlspecialchars($candidate['status'] ?? 'New'); ?>
        </div>
    </div>
</div>

    <button onclick="window.print()" class="no-print">Print</button>
    
    <div class="print-section">
        <?php include 'partials/candidate_profile_table.php'; ?>
    </div>
    
    <div class="print-section">
        <?php include 'partials/candidate_documents.php'; ?>
    </div>
    
    <div class="print-section">
        <?php include 'components/activity_timeline.php'; ?>
    </div>
    
    <div class="print-section">
        <?php include 'components/hr-comments.php'; ?>
    </div>
    
    <div class="print-section">
        <?php include 'components/call-logs.php'; ?>
    </div>
</div>