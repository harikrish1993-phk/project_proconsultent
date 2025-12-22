<?php
/**
 * One-time script to migrate all user passwords to bcrypt hashing.
 * 
 * WARNING: This script should be run once and then immediately deleted or secured.
 * It assumes the current passwords in the 'user' table are either plaintext or 
 * hashed with a weak, non-standard algorithm.
 */

require_once __DIR__ . '/includes/config/config.php';
require_once __DIR__ . '/includes/core/Database.php';

// Only allow execution via CLI for security
if (php_sapi_name() !== 'cli') {
    die("Access Denied: This script can only be run from the command line.");
}

echo "Starting password migration...\n";

try {
    $db = Core\Database::getInstance();
    $conn = $db->getConnection();

    // 1. Fetch all users
    $result = $conn->query("SELECT user_code, password FROM user");
    
    if (!$result) {
        throw new Exception("Failed to fetch users: " . $conn->error);
    }

    $users_to_migrate = [];
    while ($row = $result->fetch_assoc()) {
        $users_to_migrate[] = $row;
    }

    $migrated_count = 0;
    $total_users = count($users_to_migrate);

    echo "Found {$total_users} users to check.\n";

    // 2. Iterate and hash/update
    $update_stmt = $conn->prepare("UPDATE user SET password = ? WHERE user_code = ?");
    
    if (!$update_stmt) {
        throw new Exception("Failed to prepare update statement: " . $conn->error);
    }

    foreach ($users_to_migrate as $user) {
        $current_password = $user['password'];
        
        // Check if the password is already hashed with a modern algorithm (e.g., bcrypt)
        // This is a heuristic check. If it's a short string, it's likely plaintext or weak hash.
        if (password_get_info($current_password)['algo'] !== 0) {
            // Already hashed, skip
            continue;
        }

        // If it's not a valid hash, we assume it's the plaintext password and hash it.
        // NOTE: If the original password was a weak hash, this will hash the weak hash string, 
        // which is still an improvement over plaintext.
        $new_hash = password_hash($current_password, PASSWORD_BCRYPT);

        // Update the database
        $update_stmt->bind_param('ss', $new_hash, $user['user_code']);
        if ($update_stmt->execute()) {
            $migrated_count++;
            echo "Migrated password for user: {$user['user_code']}\n";
        } else {
            echo "ERROR migrating password for user {$user['user_code']}: " . $update_stmt->error . "\n";
        }
    }

    $update_stmt->close();
    $conn->close();

    echo "Migration complete. Successfully updated {$migrated_count} passwords.\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
?>
