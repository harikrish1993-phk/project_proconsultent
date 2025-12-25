<div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="documentForm" method="POST" action="handlers/document_upload_handler.php" enctype="multipart/form-data">
                <input type="hidden" name="can_code" value="<?= htmlspecialchars($id) ?>">
                <input type="hidden" name="token" value="<?= Auth::token() ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title">Upload Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Document Type</label>
                        <select name="document_type" class="form-select" required>
                            <option value="">Select document type</option>
                            <option value="certificate">Certificate</option>
                            <option value="reference">Reference Letter</option>
                            <option value="id_proof">ID Proof</option>
                            <option value="other">Other Document</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Document File <span class="text-danger">*</span></label>
                        <input type="file" name="document_file" class="form-control" required
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <div class="form-text">PDF, DOC, DOCX, JPG, PNG (Max 5MB)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#documentForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
             formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showToast('Document uploaded successfully!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred during upload');
            }
        });
    });
});
</script>