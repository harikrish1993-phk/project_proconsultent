<?php
/**
 * MODULE FOOTER WRAPPER
 * File: panel/includes/footer.php
 * 
 * This file closes the main content area and adds any necessary scripts
 */

// Security check
if (!defined('MODULE_BOOTSTRAP_LOADED')) {
    die('Direct access not permitted.');
}
?>
            <!-- Module content ends here -->
        </main>
    </div>
    
    <?php
    // Allow modules to add custom JavaScript
    if (isset($customJS)) {
        echo "<script>{$customJS}</script>";
    }
    ?>
    
    <script>
        // Global JavaScript utilities
        
        // Confirm delete actions
        function confirmDelete(message) {
            return confirm(message || 'Are you sure you want to delete this item? This action cannot be undone.');
        }
        
        // Show loading indicator
        function showLoading(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.innerHTML = '<div class="loading">⏳ Loading...</div>';
            }
        }
        
        // Flash message helper
        function showFlashMessage(message, type) {
            type = type || 'info';
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-' + type;
            alertDiv.textContent = message;
            
            const main = document.querySelector('.main-content');
            if (main) {
                main.insertBefore(alertDiv, main.firstChild);
                
                // Auto-dismiss after 5 seconds
                setTimeout(function() {
                    alertDiv.style.transition = 'opacity 0.5s';
                    alertDiv.style.opacity = '0';
                    setTimeout(function() {
                        alertDiv.remove();
                    }, 500);
                }, 5000);
            }
        }
        
        // Form validation helper
        function validateRequired(formId) {
            const form = document.getElementById(formId);
            if (!form) return false;
            
            const required = form.querySelectorAll('[required]');
            let isValid = true;
            
            required.forEach(function(field) {
                if (!field.value || field.value.trim() === '') {
                    field.style.borderColor = '#f00';
                    isValid = false;
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                showFlashMessage('Please fill in all required fields', 'error');
            }
            
            return isValid;
        }
        
        // AJAX helper
        function ajax(url, data, callback, method) {
            method = method || 'POST';
            
            const xhr = new XMLHttpRequest();
            xhr.open(method, url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        callback(response);
                    } catch (e) {
                        callback({success: false, message: 'Invalid response from server'});
                    }
                } else {
                    callback({success: false, message: 'Server error: ' + xhr.status});
                }
            };
            
            xhr.onerror = function() {
                callback({success: false, message: 'Network error occurred'});
            };
            
            // Convert data object to URL-encoded string
            const params = new URLSearchParams(data).toString();
            xhr.send(params);
        }
    </script>
</body>
</html>