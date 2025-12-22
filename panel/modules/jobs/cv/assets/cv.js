$(document).ready(function() {
    const table = $('#cvTable').DataTable({
        lengthMenu: [10, 25, 50, 100],
        order: [[5, 'desc']]
    });
    
    $('#cvFilterForm').submit(function(e) {
        e.preventDefault();
        // Apply filters to DataTables (client-side or server-side if AJAX)
        table.column(2).search($('#job_filter').val()).draw();
        table.column(3).search($('#status_filter').val()).draw();
        table.column(4).search($('#assigned_filter').val()).draw();
        // Date range: custom filter
    });
    
    $('#selectAll').click(function() {
        $('.select-cv').prop('checked', this.checked);
        toggleBulkButtons();
    });
    
    $(document).on('change', '.select-cv', toggleBulkButtons);
    
    function toggleBulkButtons() {
        const selected = $('.select-cv:checked').length > 0;
        $('#bulkAssign, #bulkStatus, #bulkConvert, #bulkDelete').prop('disabled', !selected);
    }
    
    $('#bulkConvert').click(function() {
        if (!confirm('Convert selected?')) return;
        const ids = $('.select-cv:checked').map(function() { return this.value; }).get();
        $.post('handlers/cv_handler.php', { action: 'bulk', sub_action: 'convert', ids: ids.join(','), token: '<?php echo Auth::token(); ?>' }, (data) => {
            if (data.success) location.reload();
            else alert(data.message);
        }).fail(() => alert('Error'));
    });
    
    // Similar for other bulk
    
    $('.convert-btn').click(function() {
        const id = $(this).data('id');
        location.href = 'convert.php?id=' + id;
    });
});