<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';

$db = Database::getInstance();
$conn = $db->getConnection();
$userId = Auth::userId();

// Get contact ID
$contactId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$contactId) {
    header('Location: index.php');
    exit;
}

// Fetch contact details
$contactQuery = "SELECT c.*, 
                       CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name,
                       CONCAT(creator.first_name, ' ', creator.last_name) as created_by_name,
                       cs.status_label, cs.status_color,
                       src.source_label
                FROM contacts c
                LEFT JOIN users u ON c.assigned_to = u.user_id
                LEFT JOIN users creator ON c.created_by = creator.user_id
                LEFT JOIN contact_statuses cs ON c.status = cs.status_value
                LEFT JOIN contact_sources src ON c.source = src.source_value
                WHERE c.contact_id = ? AND c.is_archived = 0";

$stmt = $conn->prepare($contactQuery);
$stmt->bind_param("i", $contactId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">Contact not found or has been archived.</div>';
    require_once '../../includes/footer.php';
    exit;
}

$contact = $result->fetch_assoc();

// Parse skills
$skills = json_decode($contact['skills'], true);
if (!is_array($skills)) $skills = [];

// Check if showing created message
$showCreated = isset($_GET['created']) && $_GET['created'] == 1;
?>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="fw-bold py-3 mb-0">
                    <span class="text-muted fw-light">
                        <a href="index.php" class="text-muted">Contacts</a> /
                    </span> 
                    <?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?>
                </h4>
                <div>
                    <a href="edit.php?id=<?php echo $contactId; ?>" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i> Edit
                    </a>
                    <?php if ($contact['status'] !== 'converted'): ?>
                    <a href="convert.php?id=<?php echo $contactId; ?>" class="btn btn-success">
                        <i class="bx bx-transfer me-1"></i> Convert
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($showCreated): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <h6 class="alert-heading mb-1">
            <i class="bx bx-check-circle"></i> Contact Created Successfully!
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Contact profile content -->
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center">
                    <div class="avatar avatar-xl mx-auto mb-3">
                        <span class="avatar-initial rounded-circle bg-label-primary" style="font-size: 2rem;">
                            <?php echo strtoupper(substr($contact['first_name'], 0, 1) . substr($contact['last_name'], 0, 1)); ?>
                        </span>
                    </div>
                    <h4><?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($contact['current_title'] ?? 'N/A'); ?></p>
                    <span class="badge bg-<?php echo $contact['status_color']; ?>">
                        <?php echo $contact['status_label']; ?>
                    </span>
                </div>
                <div class="col-md-9">
                    <h5>Contact Information</h5>
                    <dl class="row">
                        <dt class="col-sm-3">Email:</dt>
                        <dd class="col-sm-9">
                            <a href="mailto:<?php echo $contact['email']; ?>">
                                <?php echo htmlspecialchars($contact['email']); ?>
                            </a>
                        </dd>
                        
                        <dt class="col-sm-3">Phone:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($contact['phone']); ?></dd>
                        
                        <?php if (!empty($skills)): ?>
                        <dt class="col-sm-3">Skills:</dt>
                        <dd class="col-sm-9">
                            <?php foreach ($skills as $skill): ?>
                            <span class="badge bg-label-primary me-1"><?php echo htmlspecialchars($skill); ?></span>
                            <?php endforeach; ?>
                        </dd>
                        <?php endif; ?>
                        
                        <dt class="col-sm-3">Source:</dt>
                        <dd class="col-sm-9"><?php echo $contact['source_label']; ?></dd>
                        
                        <dt class="col-sm-3">Assigned To:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($contact['assigned_to_name'] ?? 'Unassigned'); ?></dd>
                        
                        <dt class="col-sm-3">Created:</dt>
                        <dd class="col-sm-9"><?php echo date('M d, Y', strtotime($contact['created_at'])); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
