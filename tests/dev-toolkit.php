<?php
/**
 * DEVELOPER TOOLKIT - Main Index
 * Centralized access to all development and debugging tools
 * 
 * SECURITY: Remove this file in production or add password protection
 */

// Check if dev mode is enabled
$isDevelopment = true; // Set to false in production

if (!$isDevelopment) {
    die('Development tools are disabled in production.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProConsultancy - Developer Toolkit</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        .header h1 {
            color: #1e3c72;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 16px;
        }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .warning i {
            font-size: 24px;
        }
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .tool-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-top: 4px solid;
        }
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        .tool-card.critical { border-color: #dc3545; }
        .tool-card.important { border-color: #ffc107; }
        .tool-card.testing { border-color: #28a745; }
        .tool-card.database { border-color: #17a2b8; }
        .tool-card.security { border-color: #6f42c1; }
        .tool-card.public { border-color: #007bff; }
        
        .tool-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 24px;
            color: white;
        }
        .critical .tool-icon { background: #dc3545; }
        .important .tool-icon { background: #ffc107; }
        .testing .tool-icon { background: #28a745; }
        .database .tool-icon { background: #17a2b8; }
        .security .tool-icon { background: #6f42c1; }
        .public .tool-icon { background: #007bff; }
        
        .tool-card h3 {
            color: #2c3e50;
            font-size: 20px;
            margin-bottom: 10px;
        }
        .tool-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .tool-card ul {
            list-style: none;
            margin-bottom: 20px;
        }
        .tool-card ul li {
            padding: 5px 0;
            color: #555;
            font-size: 13px;
        }
        .tool-card ul li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 5px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            text-align: center;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.4);
        }
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-left: 10px;
        }
        .badge-new { background: #28a745; color: white; }
        .badge-important { background: #ffc107; color: #000; }
        .footer {
            text-align: center;
            color: white;
            padding: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fa-solid fa-code"></i> ProConsultancy Developer Toolkit</h1>
            <p>Complete suite of development, debugging, and testing tools</p>
        </div>

        <div class="warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Security Warning:</strong> These tools should ONLY be accessible in development environment.
                Remove or password-protect before deploying to production!
            </div>
        </div>

        <div class="tools-grid">
            <!-- Login Diagnostic Tool -->
            <div class="tool-card critical">
                <div class="tool-icon"><i class="fa-solid fa-stethoscope"></i></div>
                <h3>Login Diagnostic <span class="badge badge-important">Priority</span></h3>
                <p>Comprehensive login debugging tool that checks database connection, user table structure, existing users, and tests credentials.</p>
                <ul>
                    <li>Database connection test</li>
                    <li>User table structure validation</li>
                    <li>List all users with details</li>
                    <li>Test login credentials</li>
                    <li>Password verification</li>
                    <li>Auth.php query analysis</li>
                </ul>
                <a href="dev-tools/diagnostic.php" class="btn">
                    <i class="fa-solid fa-play"></i> Run Diagnostic
                </a>
            </div>

            <!-- Password Hash Generator -->
            <div class="tool-card critical">
                <div class="tool-icon"><i class="fa-solid fa-key"></i></div>
                <h3>Password Hash Generator</h3>
                <p>Generate bcrypt password hashes for manually creating or updating user passwords in the database.</p>
                <ul>
                    <li>Generate bcrypt hashes</li>
                    <li>Test password verification</li>
                    <li>SQL UPDATE queries ready to copy</li>
                    <li>Batch password generation</li>
                </ul>
                <a href="dev-tools/password-hash.php" class="btn">
                    <i class="fa-solid fa-play"></i> Generate Hashes
                </a>
            </div>

            <!-- Database Structure Explorer -->
            <div class="tool-card database">
                <div class="tool-icon"><i class="fa-solid fa-database"></i></div>
                <h3>Database Explorer</h3>
                <p>View complete database structure, table relationships, indexes, and data statistics.</p>
                <ul>
                    <li>All tables and columns</li>
                    <li>Foreign key relationships</li>
                    <li>Index information</li>
                    <li>Record counts</li>
                    <li>Sample data preview</li>
                </ul>
                <a href="dev-tools/database-explorer.php" class="btn">
                    <i class="fa-solid fa-play"></i> Explore Database
                </a>
            </div>

            <!-- SQL Query Tester -->
            <div class="tool-card database">
                <div class="tool-icon"><i class="fa-solid fa-terminal"></i></div>
                <h3>SQL Query Tester</h3>
                <p>Execute and test SQL queries directly from the browser with safety checks.</p>
                <ul>
                    <li>Execute SELECT queries</li>
                    <li>Test prepared statements</li>
                    <li>View query results</li>
                    <li>Execution time tracking</li>
                    <li>Query history</li>
                </ul>
                <a href="dev-tools/query-tester.php" class="btn">
                    <i class="fa-solid fa-play"></i> Test Queries
                </a>
            </div>

            <!-- Session Inspector -->
            <div class="tool-card security">
                <div class="tool-icon"><i class="fa-solid fa-user-lock"></i></div>
                <h3>Session Inspector</h3>
                <p>View and debug session data, cookies, and authentication tokens.</p>
                <ul>
                    <li>Current session variables</li>
                    <li>Cookie values</li>
                    <li>Authentication status</li>
                    <li>Token validation</li>
                    <li>Session manipulation tools</li>
                </ul>
                <a href="dev-tools/session-inspector.php" class="btn">
                    <i class="fa-solid fa-play"></i> Inspect Session
                </a>
            </div>

            <!-- Activity Log Viewer -->
            <div class="tool-card testing">
                <div class="tool-icon"><i class="fa-solid fa-list-check"></i></div>
                <h3>Activity Log Viewer</h3>
                <p>Browse and filter activity logs for debugging user actions and system events.</p>
                <ul>
                    <li>Real-time log viewing</li>
                    <li>Filter by user/action/date</li>
                    <li>Export logs</li>
                    <li>Log statistics</li>
                </ul>
                <a href="dev-tools/activity-logs.php" class="btn">
                    <i class="fa-solid fa-play"></i> View Logs
                </a>
            </div>

            <!-- Email Test Tool -->
            <div class="tool-card testing">
                <div class="tool-icon"><i class="fa-solid fa-envelope"></i></div>
                <h3>Email Test Tool</h3>
                <p>Test email configuration and send test emails to verify SMTP settings.</p>
                <ul>
                    <li>SMTP connection test</li>
                    <li>Send test emails</li>
                    <li>Template preview</li>
                    <li>Email queue status</li>
                </ul>
                <a href="dev-tools/email-tester.php" class="btn">
                    <i class="fa-solid fa-play"></i> Test Emails
                </a>
            </div>

            <!-- API Endpoint Tester -->
            <div class="tool-card testing">
                <div class="tool-icon"><i class="fa-solid fa-code-branch"></i></div>
                <h3>API Endpoint Tester</h3>
                <p>Test all API endpoints with different parameters and view responses.</p>
                <ul>
                    <li>Test all handlers</li>
                    <li>POST/GET request simulation</li>
                    <li>Response validation</li>
                    <li>Error tracking</li>
                </ul>
                <a href="dev-tools/api-tester.php" class="btn">
                    <i class="fa-solid fa-play"></i> Test APIs
                </a>
            </div>

            <!-- Module Status Checker -->
            <div class="tool-card important">
                <div class="tool-icon"><i class="fa-solid fa-puzzle-piece"></i></div>
                <h3>Module Status Checker</h3>
                <p>Verify all modules, files, and dependencies are present and working correctly.</p>
                <ul>
                    <li>Check all modules</li>
                    <li>File existence verification</li>
                    <li>PHP syntax check</li>
                    <li>Permission checks</li>
                    <li>Integration test</li>
                </ul>
                <a href="dev-tools/module-checker.php" class="btn">
                    <i class="fa-solid fa-play"></i> Check Modules
                </a>
            </div>

            <!-- Public Jobs Preview -->
            <div class="tool-card public">
                <div class="tool-icon"><i class="fa-solid fa-briefcase"></i></div>
                <h3>Public Jobs Preview <span class="badge badge-new">New</span></h3>
                <p>Preview and test the public job posting pages before making jobs live.</p>
                <ul>
                    <li>View public careers page</li>
                    <li>Test job application form</li>
                    <li>Form validation testing</li>
                    <li>Mobile responsive preview</li>
                </ul>
                <div class="btn-group">
                    <a href="careers.php" class="btn" target="_blank">
                        <i class="fa-solid fa-eye"></i> View Careers
                    </a>
                    <a href="dev-tools/job-posts-manager.php" class="btn">
                        <i class="fa-solid fa-cog"></i> Manage Posts
                    </a>
                </div>
            </div>

            <!-- Performance Monitor -->
            <div class="tool-card important">
                <div class="tool-icon"><i class="fa-solid fa-gauge-high"></i></div>
                <h3>Performance Monitor</h3>
                <p>Monitor application performance, query execution times, and resource usage.</p>
                <ul>
                    <li>Page load times</li>
                    <li>Slow query detection</li>
                    <li>Memory usage</li>
                    <li>Database performance</li>
                </ul>
                <a href="dev-tools/performance-monitor.php" class="btn">
                    <i class="fa-solid fa-play"></i> Monitor Performance
                </a>
            </div>

            <!-- Error Log Viewer -->
            <div class="tool-card critical">
                <div class="tool-icon"><i class="fa-solid fa-bug"></i></div>
                <h3>Error Log Viewer</h3>
                <p>View PHP errors, warnings, and notices from all log files in one place.</p>
                <ul>
                    <li>PHP error logs</li>
                    <li>Application logs</li>
                    <li>Filter by severity</li>
                    <li>Real-time monitoring</li>
                </ul>
                <a href="dev-tools/error-logs.php" class="btn">
                    <i class="fa-solid fa-play"></i> View Errors
                </a>
            </div>
        </div>

        <div class="footer">
            <p><i class="fa-solid fa-code"></i> ProConsultancy Developer Toolkit v1.0</p>
            <p>For development use only • <?php echo date('Y'); ?></p>
        </div>
    </div>
</body>
</html>
