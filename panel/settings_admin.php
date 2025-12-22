<?php
// modules/settings/settings_admin.php
require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';
require_once __DIR__ . '/../../../includes/core/Settings.php';
require_once __DIR__ . '/../../../includes/header.php';

if (!Auth::check() || Auth::user()['level'] !== 'admin') {
    header('Location: ../../../login.php');
    exit();
}

$settings = Settings::getInstance();

$message = '';
$message_type = '';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::token() === ($_POST['token'] ?? '')) {
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = intval($_POST['smtp_port'] ?? 0);
    $smtp_user = trim($_POST['smtp_user'] ?? '');
    $smtp_pass = $_POST['smtp_pass'] ?? '';
    $smtp_from = trim($_POST['smtp_from'] ?? '');
    $smtp_from_name = trim($_POST['smtp_from_name'] ?? '');
    
    $errors = [];
    
    if (empty($smtp_host)) $errors[] = 'SMTP Host required';
    if ($smtp_port < 1 || $smtp_port > 65535) $errors[] = 'Invalid port';
    if (empty($smtp_user)) $errors[] = 'SMTP User required';
    if (!filter_var($smtp_from, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid From email';
    if (empty($smtp_from_name)) $errors[] = 'From Name required';
    
    if (empty($errors)) {
        try {
            $settings->set('smtp_host', $smtp_host);
            $settings->set('smtp_port', $smtp_port);
            $settings->set('smtp_user', $smtp_user);
            if ($smtp_pass) $settings->set('smtp_pass', $smtp_pass); // Only update if provided
            $settings->set('smtp_from', $smtp_from);
            $settings->set('smtp_from_name', $smtp_from_name);
            
            $message = 'Settings updated successfully';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Update failed: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'danger';
    }
}

$current_settings = $settings->getAll();
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Admin Settings</h4>
    
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Email Configuration</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo Auth::token(); ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" name="smtp_host" value="<?php echo htmlspecialchars($current_settings['smtp_host'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" name="smtp_port" value="<?php echo $current_settings['smtp_port'] ?? ''; ?>" required min="1" max="65535">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">SMTP User</label>
                        <input type="text" class="form-control" name="smtp_user" value="<?php echo htmlspecialchars($current_settings['smtp_user'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">SMTP Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" name="smtp_pass">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">From Email</label>
                        <input type="email" class="form-control" name="smtp_from" value="<?php echo htmlspecialchars($current_settings['smtp_from'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">From Name</label>
                        <input type="text" class="form-control" name="smtp_from_name" value="<?php echo htmlspecialchars($current_settings['smtp_from_name'] ?? ''); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Save</button>
            </form>
        </div>
    </div>
    

</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>