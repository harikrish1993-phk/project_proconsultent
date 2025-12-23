<?php
// Load common bootstrap
require_once __DIR__ . '/../_common.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Invalid ID.</div></div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM candidates WHERE can_code = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $candidate = $stmt->get_result()->fetch_assoc();
    if (!$candidate) throw new Exception('Candidate not found');
    
    // Dynamic fields
    $dynamic = [];
    $stmt = $conn->prepare("SELECT j.column_name, c.data FROM candidates_edit_info c JOIN job_columns j ON j.id = c.column_id WHERE c.can_code = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $dynamic[$row['column_name']] = $row['data'];
    
    // History
    $history = [];
    $stmt = $conn->prepare("SELECT * FROM candidates_edit_info WHERE can_code = ? ORDER BY edited_at DESC");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $history[] = $row;
    
    // Fetch dropdown data
    $work_auth = [];
    $res = $conn->query("SELECT id, status FROM work_authorization");
    while ($r = $res->fetch_assoc()) $work_auth[] = $r;
    
    $technical_skills = [];
    $res = $conn->query("SELECT id, skill FROM technical_skills");
    while ($r = $res->fetch_assoc()) $technical_skills[] = $r;
    
    $job_columns = [];
    $res = $conn->query("SELECT id, column_name FROM job_columns");
    while ($r = $res->fetch_assoc()) $job_columns[] = $r;
} catch (Exception $e) {
    echo '<div class="container-xxl flex-grow-1 container-p-y"><div class="alert alert-danger">Error: ' . $e->getMessage() . '</div></div>';
    include __DIR__ . '/../../../includes/footer.php';
    exit();
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Edit Candidate: <?php echo htmlspecialchars($candidate['candidate_name']); ?></h4>
    
    <div class="row">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-body">
                    <form id="formCandidateEdit" method="POST" action="handlers/candidate_save_handler.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="can_code" value="<?php echo $id; ?>">
                        <input type="hidden" name="token" value="<?php echo Auth::token(); ?>">
                        
                        <div class="accordion" id="editAccordion">
                            <!-- Basic Info -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#basicInfo" aria-expanded="true">
                                        Basic Information
                                    </button>
                                </h2>
                                <div id="basicInfo" class="accordion-collapse collapse show">
                                    <div class="accordion-body row g-3">
                                        <!-- Fields with values from $candidate -->
                                        <div class="col-md-6">
                                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="candidate_name" value="<?php echo htmlspecialchars($candidate['candidate_name']); ?>" required>
                                        </div>
                                        <!-- All other basic fields... -->
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                            <label class="form-label fw-semibold">
                                Key Skills
                                <i class="bx bx-info-circle"
                                data-bs-toggle="tooltip"
                                title="Skills identified from resume or added manually. Please review and update as needed.">
                                </i>
                            </label>

                            <div id="skillsContainer" class="d-flex flex-wrap gap-2 mb-2">
                                <?php
                                $existingSkills = array_filter(array_map('trim', explode(',', $candidate['skills'] ?? '')));
                                foreach ($existingSkills as $skill):
                                ?>
                                    <span class="badge bg-primary skill-chip">
                                        <?php echo htmlspecialchars($skill); ?>
                                        <button type="button"
                                                class="btn-close btn-close-white btn-sm ms-1 remove-skill"
                                                aria-label="Remove"
                                                data-skill="<?php echo htmlspecialchars($skill); ?>">
                                        </button>
                                    </span>
                                <?php endforeach; ?>
                            </div>

                            <div class="input-group">
                                <input type="text"
                                    id="skillInput"
                                    class="form-control"
                                    placeholder="Type a skill and press Enter">
                                <button type="button" class="btn btn-outline-secondary" id="addSkillBtn">
                                    Add
                                </button>
                            </div>

                            <input type="hidden" name="skills" id="skillsHidden"
                                value="<?php echo htmlspecialchars($candidate['skills'] ?? ''); ?>">

                            <div class="form-text">
                                Add or remove skills to reflect the candidate's actual expertise.
                            </div>
                        </div>

                            <!-- Professional, Additional, Documents, Custom as in create.php with values pre-filled -->
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary me-2">Update</button>
                            <a href="?action=view&id=<?php echo $id; ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">Edit History</div>
                <div class="card-body">
                    <?php if (empty($history)): ?>
                    <p class="text-muted">No history</p>
                    <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($history as $h): ?>
                        <li class="list-group-item">
                            <?php echo htmlspecialchars($h['edited_field']); ?> changed from <?php echo htmlspecialchars($h['old_value']); ?> to <?php echo htmlspecialchars($h['new_value']); ?>
                            <small>by <?php echo $h['edited_name']; ?> on <?php echo $h['edited_at']; ?></small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$('#formCandidateEdit').submit(function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    $.ajax({
        url: this.action,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: (data) => {
            if (data.success) {
                alert('Updated');
                location.href = '?action=view&id=<?php echo $id; ?>';
            } else {
                alert(data.message);
            }
        },
        error: () => alert('Error')
    });
});
function syncSkills() {
    let skills = [];
    $('#skillsContainer .skill-chip').each(function () {
        skills.push($(this).text().trim());
    });
    $('#skillsHidden').val(skills.join(','));
}

// Add skill
$('#addSkillBtn').on('click', addSkill);
$('#skillInput').on('keypress', function (e) {
    if (e.which === 13) {
        e.preventDefault();
        addSkill();
    }
});

function addSkill() {
    let skill = $('#skillInput').val().trim();
    if (!skill) return;

    // Prevent duplicates
    let exists = false;
    $('#skillsContainer .skill-chip').each(function () {
        if ($(this).text().trim().toLowerCase() === skill.toLowerCase()) {
            exists = true;
        }
    });
    if (exists) {
        $('#skillInput').val('');
        return;
    }

    $('#skillsContainer').append(`
        <span class="badge bg-primary skill-chip">
            ${skill}
            <button type="button"
                    class="btn-close btn-close-white btn-sm ms-1 remove-skill"
                    aria-label="Remove">
            </button>
        </span>
    `);

    $('#skillInput').val('');
    syncSkills();
}

// Remove skill
$(document).on('click', '.remove-skill', function () {
    $(this).closest('.skill-chip').remove();
    syncSkills();
});

</script>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>