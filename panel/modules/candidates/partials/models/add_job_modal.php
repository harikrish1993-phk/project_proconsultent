<div class="modal fade" id="addJobModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Candidate to Job</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Search Jobs</label>
                    <input type="text" class="form-control" id="jobSearch" placeholder="Search by job title, client, or reference...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="jobsTable">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Client</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available_jobs as $job): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($job['job_title']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($job['job_reference']) ?></small></td>
                                <td><?= htmlspecialchars($job['client_name']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary add-to-job" 
                                            data-job-code="<?= $job['job_code'] ?>"
                                            data-job-title="<?= htmlspecialchars($job['job_title']) ?>">
                                        Add
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($available_jobs)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-3">
                                    <i class="bx bx-search-alt display-4 text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No available jobs found</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Job search
    $('#jobSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('#jobsTable tbody tr').each(function() {
            const jobTitle = $(this).find('td:eq(0)').text().toLowerCase();
            const client = $(this).find('td:eq(1)').text().toLowerCase();
            $(this).toggle(jobTitle.includes(searchTerm) || client.includes(searchTerm));
        });
    });
    
    // Add candidate to job
    $('.add-to-job').on('click', function() {
        const jobCode = $(this).data('job-code');
        const jobTitle = $(this).data('job-title');
        const canCode = '<?= htmlspecialchars($id) ?>';
        
        if (confirm(`Add <?= htmlspecialchars($candidate['candidate_name']) ?> to job: ${jobTitle}?`)) {
            $.post('handlers/add_candidate_to_job.php', {
                can_code: canCode,
                job_code: jobCode,
                token: '<?= Auth::token() ?>'
            }, function(response) {
                if (response.success) {
                    showToast(`Candidate added to ${jobTitle} successfully!`, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    alert('Error: ' + response.message);
                }
            });
        }
    });
});
</script>