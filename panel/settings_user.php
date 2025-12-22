<?php
// modules/settings/settings_user.php
require_once __DIR__ . '/../../../includes/config/config.php';
require_once __DIR__ . '/../../../includes/core/Auth.php';
require_once __DIR__ . '/../../../includes/core/Database.php';
require_once __DIR__ . '/../../../includes/header.php';

if (!Auth::check()) {
    header('Location: ../../../login.php');
    exit();
}

$user = Auth::user();
$db = Database::getInstance();
$conn = $db->getConnection();

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::token() === ($_POST['token'] ?? '')) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($name)) $errors[] = 'Name is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email';
    
    // Check duplicate email
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user['id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) $errors[] = 'Email already in use';
    
    if ($new_password || $confirm_password) {
        if (empty($old_password)) $errors[] = 'Old password required to change password';
        if ($new_password !== $confirm_password) $errors[] = 'Passwords do not match';
        if (strlen($new_password) < 8) $errors[] = 'Password must be at least 8 characters';
        
        // Verify old password
        $stmt = $conn->prepare("SELECT password FROM user WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $hashed = $stmt->get_result()->fetch_assoc()['password'];
        if (!password_verify($old_password, $hashed)) $errors[] = 'Incorrect old password';
    }
    
    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            $sql = "UPDATE user SET name = ?, email = ? WHERE id = ?";
            $types = "ssi";
            $params = [$name, $email, $user['id']];
            
            if ($new_password) {
                $hashed_new = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE user SET name = ?, email = ?, password = ? WHERE id = ?";
                $types = "sssi";
                $params = [$name, $email, $hashed_new, $user['id']];
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            $conn->commit();
            $message = 'Profile updated successfully';
            $message_type = 'success';
            
            // Update session
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Update failed: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'danger';
    }
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">My Settings</h4>
    
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <h5 class="card-header">Profile Details</h5>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo Auth::token(); ?>">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <h5 class="card-header">Change Password</h5>
        <div class="card-body">
            <form method="POST" id="passwordForm">
                <input type="hidden" name="token" value="<?php echo Auth::token(); ?>">
                <div class="mb-3">
                    <label class="form-label">Old Password</label>
                    <input type="password" class="form-control" name="old_password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="new_password" required minlength="8">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-warning">Change Password</button>
            </form>
        </div>
    </div>
</div>

<script>
$('#passwordForm').submit(function(e) {
    const newPass = $('[name="new_password"]').val();
    const confirm = $('[name="confirm_password"]').val();
    if (newPass !== confirm) {
        alert('Passwords do not match');
        e.preventDefault();
    }
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>