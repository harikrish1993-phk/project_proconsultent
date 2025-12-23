<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';
// Page configuration
$pageTitle = 'Candidates';
$breadcrumbs = [
    'Candidates' => '#'
];
// Include header
require_once ROOT_PATH . '/panel/includes/header.php';
require_once ROOT_PATH . '/panel/components/ui_components.php';

// Display breadcrumb
echo renderBreadcrumb($breadcrumbs);

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stages = ['Sourced', 'Screening', 'Interview', 'Offer', 'Hired', 'Rejected'];
    $pipeline = [];
    foreach ($stages as $stage) {
        $stmt = $conn->prepare("SELECT can_code as id, candidate_name as name, role_addressed as job FROM candidates WHERE status = ?");
        $stmt->bind_param("s", $stage);
        $stmt->execute();
        $pipeline[$stage] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Error: ' . $e->getMessage() . '</div></div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Candidate Pipeline</h4>
    
    <div class="row g-4">
        <?php foreach ($stages as $stage): ?>
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-header bg-<?php echo strtolower($stage); ?> text-white">
                    <h5 class="mb-0"><?php echo $stage; ?> (<?php echo count($pipeline[$stage]); ?>)</h5>
                </div>
                <div class="card-body p-2" id="stage-<?php echo $stage; ?>">
                    <?php if (empty($pipeline[$stage])): ?>
                    <p class="text-muted text-center">No candidates</p>
                    <?php else: ?>
                    <?php foreach ($pipeline[$stage] as $cand): ?>
                    <div class="card mb-2 draggable" data-id="<?php echo $cand['id']; ?>">
                        <div class="card-body p-2">
                            <h6 class="mb-1"><?php echo htmlspecialchars($cand['name']); ?></h6>
                            <small><?php echo htmlspecialchars($cand['job']); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.querySelectorAll('.card-body').forEach(el => {
    new Sortable(el, {
        group: 'pipeline',
        animation: 150,
        ghostClass: 'bg-light',
        onEnd: function (e) {
            const id = e.item.dataset.id;
            const newStage = e.to.id.replace('stage-', '');
            fetch('handlers/candidate_data_handler.php', {
                method: 'POST',
                headers: {'Content-Type' : 'application/json'},
                body: JSON.stringify({
                    action: 'update_status',
                    id: id,
                    status: newStage,
                    token: '<?php echo Auth::token(); ?>'
                })
            }).then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message);
                    location.reload();
                }
            }).catch(() => alert('Error updating status'));
        }
    });
});
</script>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>