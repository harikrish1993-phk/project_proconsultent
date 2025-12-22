<?php
// Load configuration
require_once __DIR__ . '/includes/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo COMPANY_NAME; ?></title>
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
        .login-container {
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
        .welcome-section {
            text-align: center;
            margin-bottom: 35px;
        }
        .welcome-section h1 {
            font-size: 24px;
            color: #2d3748;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .welcome-section p {
            font-size: 15px;
            color: #718096;
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
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 28px;
        }
        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            cursor: pointer;
            border-radius: 4px;
        }
        .checkbox-wrapper label {
            font-size: 14px;
            color: #4a5568;
            cursor: pointer;
            user-select: none;
        }
        .btn-login {
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
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .btn-login:disabled {
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
            .login-container {
                padding: 30px 25px;
            }
            .logo-text {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo-text"><?php echo COMPANY_NAME; ?></div>
            <div class="logo-tagline"><?php echo COMPANY_TAGLINE; ?></div>
        </div>

        <div class="welcome-section">
            <h1>Welcome Back</h1>
            <p>Please sign in to continue</p>
        </div>

        <div id="alertBox" class="alert"></div>

        <form id="loginForm" autocomplete="off">
            <div class="form-group">
                <label class="form-label">User Code</label>
                <div class="input-wrapper">
                    <input 
                        type="text" 
                        id="userCode" 
                        class="form-control" 
                        placeholder="Enter your user code"
                        autocomplete="username"
                        required 
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        class="form-control" 
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                    <i class="fa-solid fa-eye password-toggle" id="togglePassword" title="Show password"></i>
                </div>
            </div>

            <div class="checkbox-wrapper" style="justify-content: space-between;">
                <div>
                    <input type="checkbox" id="rememberMe" name="remember_me">
                    <label for="rememberMe">Remember me</label>
                </div>
                <a href="forgot-password.php" style="color: #667eea; text-decoration: none; font-size: 14px; font-weight: 500;">Forgot password?</a>
            </div>

            <button type="submit" class="btn-login" id="loginButton">
                Sign In
            </button>
        </form>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?>. All rights reserved.</p>
        </div>
    </div>

    <script>
        // ========================================
        // CONFIGURATION
        // ========================================
        const API_URL = 'includes/core/login_handle.php';
        
        // ========================================
        // DOM ELEMENTS
        // ========================================
        const loginForm = document.getElementById('loginForm');
        const userCodeInput = document.getElementById('userCode');
        const passwordInput = document.getElementById('password');
        const rememberMeCheckbox = document.getElementById('rememberMe');
        const loginButton = document.getElementById('loginButton');
        const alertBox = document.getElementById('alertBox');
        const togglePassword = document.getElementById('togglePassword');

        // ========================================
        // INITIALIZE
        // ========================================
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Login page initialized');
            
            // Load remembered user
            checkRememberedUser();
            
            // Setup event listeners
            loginForm.addEventListener('submit', handleLogin);
            togglePassword.addEventListener('click', togglePasswordVisibility);
            
            // Enter key on password field
            passwordInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    handleLogin(e);
                }
            });
        });

        // ========================================
        // PASSWORD VISIBILITY TOGGLE
        // ========================================
        function togglePasswordVisibility() {
            const type = passwordInput.getAttribute('type');
            
            if (type === 'password') {
                passwordInput.setAttribute('type', 'text');
                togglePassword.classList.remove('fa-eye');
                togglePassword.classList.add('fa-eye-slash');
                togglePassword.setAttribute('title', 'Hide password');
            } else {
                passwordInput.setAttribute('type', 'password');
                togglePassword.classList.remove('fa-eye-slash');
                togglePassword.classList.add('fa-eye');
                togglePassword.setAttribute('title', 'Show password');
            }
        }

        // ========================================
        // HANDLE LOGIN
        // ========================================
        async function handleLogin(e) {
            e.preventDefault();

            console.log('Login attempt started');

            // Get form values
            const userCode = userCodeInput.value.trim();
            const password = passwordInput.value;
            const rememberMe = rememberMeCheckbox.checked;

            // Validation
            if (!userCode) {
                showAlert('Please enter your email or user code', 'error');
                userCodeInput.focus();
                return;
            }

            if (!password) {
                showAlert('Please enter your password', 'error');
                passwordInput.focus();
                return;
            }

            if (password.length < 3) {
                showAlert('Password must be at least 3 characters', 'error');
                passwordInput.focus();
                return;
            }

            // Disable button and show loading
            const originalButtonText = loginButton.innerHTML;
            loginButton.disabled = true;
            loginButton.innerHTML = '<span class="spinner"></span> Signing in...';

            try {
                // Prepare form data
                const formData = new FormData();
                formData.append('type', 'user_login');
                formData.append('user_code', userCode);
                formData.append('password', password);
                formData.append('remember_me', rememberMe ? '1' : '0');

                console.log('Sending login request to:', API_URL);

                // Submit login request
                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                console.log('Response status:', response.status);

                if (!response.ok) {
                    throw new Error('Network response was not ok (HTTP ' + response.status + ')');
                }

                // Parse JSON response
                const result = await response.json();
                console.log('Server response:', result);

                // Handle response
                if (result.status === 'success') {
                    // Save remembered user
                    if (rememberMe) {
                        localStorage.setItem('rememberedUser', userCode);
                    } else {
                        localStorage.removeItem('rememberedUser');
                    }
                    
                    // Show success message
                    showAlert('Login successful! Redirecting...', 'success');
                    
                    // Redirect to dashboard
                    setTimeout(() => {
                        console.log('Redirecting to dashboard...');
                        window.location.href = 'panel/route.php';
                    }, 800);
                } else {
                    // Login failed
                    const errorMessage = result.message || 'Invalid email/user code or password';
                    showAlert(errorMessage, 'error');
                    
                    // Re-enable button
                    loginButton.disabled = false;
                    loginButton.innerHTML = originalButtonText;
                    
                    // Clear password
                    passwordInput.value = '';
                    passwordInput.focus();
                }

            } catch (error) {
                console.error('Login error:', error);
                
                // Show error message
                showAlert('Connection error. Please check your connection and try again.', 'error');
                
                // Re-enable button
                loginButton.disabled = false;
                loginButton.innerHTML = originalButtonText;
            }
        }

        // ========================================
        // CHECK REMEMBERED USER
        // ========================================
        function checkRememberedUser() {
            const rememberedUser = localStorage.getItem('rememberedUser');
            
            if (rememberedUser) {
                userCodeInput.value = rememberedUser;
                rememberMeCheckbox.checked = true;
                passwordInput.focus();
                console.log('Loaded remembered user:', rememberedUser);
            }
        }
        // ========================================
        // SHOW ALERT MESSAGE
        // ========================================
        function showAlert(message, type = 'info') {
            const icon = type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check';
            
            alertBox.innerHTML = `<i class="fa-solid ${icon}"></i><span>${message}</span>`;
            alertBox.className = `alert alert-${type} show`;
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    alertBox.className = 'alert';
                }, 5000);
            }
        }
    </script>
</body>
</html>