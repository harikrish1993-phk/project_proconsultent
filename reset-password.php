<?php
// Load configuration
require_once __DIR__ . '/includes/config/config.php';
require_once __DIR__ . '/includes/core/Database.php';

$token = $_GET['token'] ?? '';
$validToken = false;
$errorMessage = '';

if (empty($token)) {
    $errorMessage = 'Invalid reset link';
} else {
    // Verify token
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT user_code, email, expires_at, used 
        FROM password_resets 
        WHERE token = ? 
        LIMIT 1
    ");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $reset = $result->fetch_assoc();
    
    if (!$reset) {
        $errorMessage = 'Invalid or expired reset link';
    } elseif ($reset['used'] == 1) {
        $errorMessage = 'This reset link has already been used';
    } elseif (strtotime($reset['expires_at']) < time()) {
        $errorMessage = 'This reset link has expired. Please request a new one.';
    } else {
        $validToken = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo COMPANY_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 440px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 45px;
            animation: slideIn 0.4s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo-section {
            text-align: center;
            margin-bottom: 35px;
        }
        .logo-text {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }
        .logo-tagline {
            font-size: 14px;
            color: #8895a7;
            font-weight: 500;
        }
        .header-section {
            text-align: center;
            margin-bottom: 35px;
        }
        .header-section h1 {
            font-size: 24px;
            color: #2d3748;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .header-section p {
            font-size: 15px;
            color: #718096;
            line-height: 1.6;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-label {
            display: block;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            letter-spacing: 0.3px;
        }
        .input-wrapper {
            position: relative;
        }
        .form-control {
            width: 100%;
            padding: 14px 16px;
            padding-right: 45px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
            color: #2d3748;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            background: #f8f9ff;
        }
        .form-control::placeholder {
            color: #a0aec0;
        }
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #a0aec0;
            font-size: 18px;
            transition: color 0.2s;
            user-select: none;
        }
        .password-toggle:hover {
            color: #667eea;
        }
        .password-strength {
            margin-top: 10px;
            font-size: 13px;
        }
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e2e8f0;
            margin-top: 5px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        .strength-weak { background: #fc8181; width: 33%; }
        .strength-medium { background: #f6ad55; width: 66%; }
        .strength-strong { background: #68d391; width: 100%; }
        .password-requirements {
            font-size: 13px;
            color: #718096;
            margin-top: 10px;
            padding: 12px;
            background: #f7fafc;
            border-radius: 8px;
        }
        .password-requirements ul {
            margin: 5px 0 0 20px;
            padding: 0;
        }
        .password-requirements li {
            margin: 3px 0;
        }
        .requirement-met {
            color: #48bb78;
        }
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            letter-spacing: 0.5px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .btn-submit:active {
            transform: translateY(0);
        }
        .btn-submit:disabled {
            background: linear-gradient(135deg, #cbd5e0 0%, #a0aec0 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            display: none;
            animation: alertSlide 0.3s ease;
        }
        @keyframes alertSlide {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert.show { display: flex; align-items: center; }
        .alert-error {
            background: #fff5f5;
            border: 2px solid #fc8181;
            color: #c53030;
        }
        .alert-success {
            background: #f0fff4;
            border: 2px solid #68d391;
            color: #2f855a;
        }
        .alert i {
            margin-right: 10px;
            font-size: 16px;
        }
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
            margin-right: 10px;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .error-container {
            text-align: center;
            padding: 40px 20px;
        }
        .error-icon {
            font-size: 64px;
            color: #fc8181;
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 18px;
            color: #2d3748;
            margin-bottom: 30px;
        }
        .back-to-login {
            text-align: center;
            margin-top: 25px;
        }
        .back-to-login a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }
        .back-to-login a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        .footer {
            text-align: center;
            margin-top: 35px;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            font-size: 13px;
            color: #a0aec0;
        }
        @media (max-width: 480px) {
            .container {
                padding: 30px 25px;
            }
            .logo-text {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-section">
            <div class="logo-text"><?php echo COMPANY_NAME; ?></div>
            <div class="logo-tagline"><?php echo COMPANY_TAGLINE; ?></div>
        </div>

        <?php if (!$validToken): ?>
            <div class="error-container">
                <div class="error-icon">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
                <a href="forgot-password.php" class="btn-submit" style="display: inline-block; text-decoration: none; max-width: 250px;">
                    Request New Link
                </a>
            </div>
        <?php else: ?>
            <div class="header-section">
                <h1>Reset Your Password</h1>
                <p>Please enter your new password below.</p>
            </div>

            <div id="alertBox" class="alert"></div>

            <form id="resetPasswordForm">
                <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            class="form-control" 
                            placeholder="Enter new password"
                            autocomplete="new-password"
                            required 
                            autofocus
                        >
                        <i class="fa-solid fa-eye password-toggle" id="togglePassword" title="Show password"></i>
                    </div>
                    <div class="password-strength" id="passwordStrength" style="display: none;">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span id="strengthText"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="confirmPassword" 
                            class="form-control" 
                            placeholder="Confirm new password"
                            autocomplete="new-password"
                            required
                        >
                        <i class="fa-solid fa-eye password-toggle" id="toggleConfirmPassword" title="Show password"></i>
                    </div>
                </div>

                <div class="password-requirements">
                    <strong>Password must contain:</strong>
                    <ul>
                        <li id="req-length">At least 8 characters</li>
                        <li id="req-uppercase">One uppercase letter</li>
                        <li id="req-lowercase">One lowercase letter</li>
                        <li id="req-number">One number</li>
                    </ul>
                </div>

                <button type="submit" class="btn-submit" id="submitButton">
                    Reset Password
                </button>
            </form>
        <?php endif; ?>

        <div class="back-to-login">
            <a href="login.php"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?>. All rights reserved.</p>
        </div>
    </div>

    <script>
        const form = document.getElementById('resetPasswordForm');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const submitButton = document.getElementById('submitButton');
        const alertBox = document.getElementById('alertBox');
        const tokenInput = document.getElementById('token');

        // Password toggle functionality
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            togglePasswordVisibility(passwordInput, this);
        });

        document.getElementById('toggleConfirmPassword')?.addEventListener('click', function() {
            togglePasswordVisibility(confirmPasswordInput, this);
        });

        function togglePasswordVisibility(input, icon) {
            const type = input.getAttribute('type');
            if (type === 'password') {
                input.setAttribute('type', 'text');
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                icon.setAttribute('title', 'Hide password');
            } else {
                input.setAttribute('type', 'password');
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                icon.setAttribute('title', 'Show password');
            }
        }

        // Password strength checker
        passwordInput?.addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');

            if (password.length === 0) {
                strengthDiv.style.display = 'none';
                return;
            }

            strengthDiv.style.display = 'block';

            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            strengthFill.className = 'strength-fill';
            if (strength <= 2) {
                strengthFill.classList.add('strength-weak');
                strengthText.textContent = 'Weak password';
                strengthText.style.color = '#fc8181';
            } else if (strength <= 3) {
                strengthFill.classList.add('strength-medium');
                strengthText.textContent = 'Medium password';
                strengthText.style.color = '#f6ad55';
            } else {
                strengthFill.classList.add('strength-strong');
                strengthText.textContent = 'Strong password';
                strengthText.style.color = '#68d391';
            }

            // Update requirements
            updateRequirement('req-length', password.length >= 8);
            updateRequirement('req-uppercase', /[A-Z]/.test(password));
            updateRequirement('req-lowercase', /[a-z]/.test(password));
            updateRequirement('req-number', /[0-9]/.test(password));
        });

        function updateRequirement(id, met) {
            const element = document.getElementById(id);
            if (met) {
                element.classList.add('requirement-met');
                element.innerHTML = '<i class="fa-solid fa-check"></i> ' + element.textContent.replace('✓ ', '');
            } else {
                element.classList.remove('requirement-met');
                element.textContent = element.textContent.replace('<i class="fa-solid fa-check"></i> ', '');
            }
        }

        // Form submission
        form?.addEventListener('submit', async function(e) {
            e.preventDefault();

            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const token = tokenInput.value;

            // Validation
            if (password.length < 8) {
                showAlert('Password must be at least 8 characters long', 'error');
                return;
            }

            if (!/[A-Z]/.test(password)) {
                showAlert('Password must contain at least one uppercase letter', 'error');
                return;
            }

            if (!/[a-z]/.test(password)) {
                showAlert('Password must contain at least one lowercase letter', 'error');
                return;
            }

            if (!/[0-9]/.test(password)) {
                showAlert('Password must contain at least one number', 'error');
                return;
            }

            if (password !== confirmPassword) {
                showAlert('Passwords do not match', 'error');
                return;
            }

            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner"></span> Resetting...';

            try {
                const formData = new FormData();
                formData.append('token', token);
                formData.append('password', password);

                const response = await fetch('includes/reset_password_handle.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showAlert(result.message, 'success');
                    
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    showAlert(result.message || 'An error occurred. Please try again.', 'error');
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }

            } catch (error) {
                console.error('Error:', error);
                showAlert('Connection error. Please check your connection and try again.', 'error');
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });

        function showAlert(message, type = 'info') {
            const icon = type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check';
            alertBox.innerHTML = `<i class="fa-solid ${icon}"></i><span>${message}</span>`;
            alertBox.className = `alert alert-${type} show`;
            
            if (type === 'success') {
                setTimeout(() => {
                    alertBox.className = 'alert';
                }, 5000);
            }
        }
    </script>
</body>
</html>
