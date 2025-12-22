<?php if (!isset($candidate)) return; ?>

<div class="card">
    <div class="card-header">Documents</div>
    <div class="card-body">
        <?php 
        $docs = [
            'Consent Form' => $candidate['consent'],
            'Candidate CV' => $candidate['candidate_cv'],
            'Consultancy CV' => $candidate['consultancy_cv']
        ];
        if (empty(array_filter($docs))): ?>
        <p class="text-muted text-center"><i class="bx bx-file-blank bx-lg"></i><br>No documents uploaded.</p>
        <?php else: ?>
        <div class="list-group">
            <?php foreach ($docs as $label => $path): if ($path): ?>
            <a href="<?php echo htmlspecialchars($path); ?>" target="_blank" class="list-group-item list-group-item-action">
                <i class="bx bx-file me-2"></i> <?php echo $label; ?>
                <span class="badge bg-label-primary float-end">View</span>
            </a>
            <?php endif; endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>