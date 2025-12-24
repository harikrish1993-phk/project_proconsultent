<?php
$pageTitle = 'Access Denied';
require_once ROOT_PATH . '/panel/includes/header.php';
?>
<div class="content-card">
    <div style="text-align: center; padding: 60px 20px;">
        <h1 style="font-size: 48px; color: #f00; margin-bottom: 20px;">403</h1>
        <h2>Access Denied</h2>
        <p style="color: #718096; margin: 20px 0;">You don't have permission to access this resource.</p>
        <a href="<?php echo ROOT_PATH; ?>/panel/admin.php" class="btn btn-primary">Return to Dashboard</a>
    </div>
</div>
<?php require_once ROOT_PATH . '/panel/includes/footer.php'; ?>