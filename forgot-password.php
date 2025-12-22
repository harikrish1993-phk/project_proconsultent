<?php
// Load configuration
require_once __DIR__ . '/includes/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo COMPANY_NAME; ?></title>
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
        .form-control {
            width: 100%;
            padding: 14px 16px;
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

        <div class="header-section">
            <h1>Forgot Password?</h1>
            <p>Enter your email address and we'll send you a link to reset your password.</p>
        </div>

        <div id="alertBox" class="alert"></div>

        <form id="forgotPasswordForm">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    class="form-control" 
                    placeholder="Enter your registered email"
                    autocomplete="email"
                    required 
                    autofocus
                >
            </div>

            <button type="submit" class="btn-submit" id="submitButton">
                Send Reset Link
            </button>
        </form>

        <div class="back-to-login">
            <a href="login.php"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?>. All rights reserved.</p>
        </div>
    </div>

    <script>
        const form = document.getElementById('forgotPasswordForm');
        const emailInput = document.getElementById('email');
        const submitButton = document.getElementById('submitButton');
        const alertBox = document.getElementById('alertBox');

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = emailInput.value.trim();

            if (!email) {
                showAlert('Please enter your email address', 'error');
                emailInput.focus();
                return;
            }

            if (!isValidEmail(email)) {
                showAlert('Please enter a valid email address', 'error');
                emailInput.focus();
                return;
            }

            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner"></span> Sending...';

            try {
                const formData = new FormData();
                formData.append('email', email);

                const response = await fetch('includes/forgot_password_handle.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showAlert(result.message, 'success');
                    emailInput.value = '';
                    
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 3000);
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

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
    </script>
</body>
</html>
