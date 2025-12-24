<?php
/**
 * MINIMAL WORKING CANDIDATE CREATE PAGE
 * File: panel/modules/candidates/create.php
 * 
 * This is a MINIMAL page to test:
 * 1. Form rendering
 * 2. Data submission
 * 3. Database insertion
 */

// Load common bootstrap
require_once __DIR__ . '/../_common.php';

$pageTitle = 'Create Candidate';
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Get form data
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'new';
        
        // Validate
        if (empty($firstName) || empty($lastName) || empty($email)) {
            throw new Exception('First name, last name, and email are required');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM candidates WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('A candidate with this email already exists');
        }
        
        // Generate candidate code
        $result = $conn->query("SELECT MAX(CAST(SUBSTRING(candidate_code, 4) AS UNSIGNED)) as max_num FROM candidates WHERE candidate_code LIKE 'CAN%'");
        $row = $result->fetch_assoc();
        $nextNum = ($row['max_num'] ?? 0) + 1;
        $candidateCode = 'CAN' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        
        // Insert candidate
        $stmt = $conn->prepare("
            INSERT INTO candidates (candidate_code, first_name, last_name, email, phone, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param('ssssss', $candidateCode, $firstName, $lastName, $email, $phone, $status);
        
        if ($stmt->execute()) {
            $candidateId = $conn->insert_id;
            
            // Log activity
            if (function_exists('logActivity')) {
                logActivity('create', 'candidates', $candidateCode, "Created: $firstName $lastName");
            }
            
            $message = "✅ Candidate created successfully! Code: $candidateCode";
            $messageType = 'success';
            
            // Clear form
            $_POST = [];
        } else {
            throw new Exception('Failed to insert candidate: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ProConsultancy</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
        }
        .form-label .required {
            color: #e53e3e;
        }
        .form-control, .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #718096;
            color: white;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background: #4a5568;
        }
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message.success {
            background: #f0fff4;
            border: 2px solid #48bb78;
            color: #22543d;
        }
        .message.error {
            background: #fff5f5;
            border: 2px solid #e53e3e;
            color: #742a2a;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>➕ Create New Candidate</h1>
            <p>Add a new candidate to the system</p>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            First Name <span class="required">*</span>
                        </label>
                        <input type="text" 
                               name="first_name" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                               required
                               placeholder="Enter first name">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Last Name <span class="required">*</span>
                        </label>
                        <input type="text" 
                               name="last_name" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                               required
                               placeholder="Enter last name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            Email <span class="required">*</span>
                        </label>
                        <input type="email" 
                               name="email" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required
                               placeholder="candidate@example.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" 
                               name="phone" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                               placeholder="+32 XXX XX XX XX">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="new" <?php echo ($_POST['status'] ?? '') === 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="active" <?php echo ($_POST['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="screening" <?php echo ($_POST['status'] ?? '') === 'screening' ? 'selected' : ''; ?>>Screening</option>
                        <option value="interviewing" <?php echo ($_POST['status'] ?? '') === 'interviewing' ? 'selected' : ''; ?>>Interviewing</option>
                    </select>
                </div>

                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                    <button type="submit" class="btn btn-primary">
                        💾 Create Candidate
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        ← Back to Index
                    </a>
                    <a href="list.php" class="btn btn-secondary">
                        📋 View All
                    </a>
                </div>
            </form>
        </div>

        <!-- Recent Submissions -->
        <?php if ($messageType === 'success'): ?>
        <div class="card" style="margin-top: 20px;">
            <h3 style="margin-bottom: 15px; color: #2d3748;">What's Next?</h3>
            <p style="margin-bottom: 15px; color: #718096;">The candidate has been created successfully. You can now:</p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="create.php" class="btn btn-primary">➕ Add Another Candidate</a>
                <a href="list.php" class="btn btn-secondary">📋 View All Candidates</a>
                <a href="index.php" class="btn btn-secondary">🏠 Module Home</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>