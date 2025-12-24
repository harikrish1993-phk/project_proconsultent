<?php
$pageTitle = 'Page Not Found';
require_once ROOT_PATH . '/panel/includes/header.php';
?>
<div class="content-card">
    <div style="text-align: center; padding: 60px 20px;">
        <h1 style="font-size: 48px; color: #667eea; margin-bottom: 20px;">404</h1>
        <h2>Page Not Found</h2>
        <p style="color: #718096; margin: 20px 0;">The page you're looking for doesn't exist.</p>
        <a href="<?php echo ROOT_PATH; ?>/panel/admin.php" class="btn btn-primary">Return to Dashboard</a>
    </div>
</div>
<?php require_once ROOT_PATH . '/panel/includes/footer.php'; ?>