<?php
/**
 * LOG VIEWER
 * File: panel/view-logs.php
 * Admin-only access to view application logs
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config/config.php';
require_once __DIR__ . '/../includes/core/Auth.php';
require_once __DIR__ . '/../includes/core/Database.php';

// Admin only
if (!Auth::check() || Auth::user()['level'] !== 'admin') {
    http_response_code(403);
    die('Access denied. Administrator privileges required.');
}

// Load Logger if available
if (file_exists(ROOT_PATH . '/includes/core/Logger.php')) {
    require_once ROOT_PATH . '/includes/core/Logger.php';
}

$user = Auth::user();
$logDir = ROOT_PATH . '/logs';

// Get log file to view
$selectedLog = $_GET['log'] ?? 'current';
$lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 100;
$filter = $_GET['filter'] ?? '';

// Get list of available log files
$logFiles = [];
if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && strpos($file, '.log') !== false) {
            $logFiles[] = $file;
        }
    }
    rsort($logFiles); // Newest first
}

// Read log content
$logContent = [];
if ($selectedLog === 'current') {
    $logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
} else {
    $logFile = $logDir . '/' . basename($selectedLog);
}

if (file_exists($logFile)) {
    $handle = fopen($logFile, 'r');
    if ($handle) {
        // Read last N lines
        $buffer = [];
        fseek($handle, -1, SEEK_END);
        $lineCount = 0;
        $currentLine = '';
        
        while (ftell($handle) > 0 && $lineCount < $lines) {
            $char = fgetc($handle);
            
            if ($char === "\n") {
                if ($currentLine !== '') {
                    // Apply filter
                    if (empty($filter) || stripos($currentLine, $filter) !== false) {
                        array_unshift($buffer, $currentLine);
                        $lineCount++;
                    }
                    $currentLine = '';
                }
            } else {
                $currentLine = $char . $currentLine;
            }
            
            fseek($handle, -2, SEEK_CUR);
        }
        
        if ($currentLine !== '' && (empty($filter) || stripos($currentLine, $filter) !== false)) {
            array_unshift($buffer, $currentLine);
        }
        
        fclose($handle);
        $logContent = $buffer;
    }
}

$pageTitle = 'System Logs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo COMPANY_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; color: #2d3748; }
        .page-wrapper { display: flex; min-height: 100vh; }
        .main-content { flex: 1; margin-left: 260px; padding: 30px; }
        .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; margin-bottom: 8px; }
        .filters { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .filters form { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .filters label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px; }
        .filters select, .filters input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .btn { padding: 8px 16px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn:hover { background: #5568d3; }
        .btn-secondary { background: #718096; }
        .btn-secondary:hover { background: #4a5568; }
        .log-container { background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 12px; font-family: 'Courier New', monospace; font-size: 13px; overflow-x: auto; max-height: 600px; overflow-y: auto; }
        .log-line { padding: 4px 0; border-bottom: 1px solid #2d2d2d; }
        .log-line:hover { background: #2d2d2d; }
        .log-time { color: #569cd6; }
        .log-level-ERROR { color: #f48771; font-weight: bold; }
        .log-level-WARNING { color: #dcdcaa; font-weight: bold; }
        .log-level-INFO { color: #4ec9b0; }
        .log-level-DEBUG { color: #9cdcfe; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-box { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .stat-value { font-size: 24px; font-weight: bold; color: #667eea; }
        .stat-label { font-size: 13px; color: #718096; margin-top: 5px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>📋 System Logs</h1>
                <p>Monitor application activity and debug issues</p>
            </div>
            
            <!-- Stats -->
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-value"><?php echo count($logContent); ?></div>
                    <div class="stat-label">Log Entries Displayed</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo count($logFiles); ?></div>
                    <div class="stat-label">Total Log Files</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo file_exists($logFile) ? number_format(filesize($logFile) / 1024, 1) . ' KB' : '0 KB'; ?></div>
                    <div class="stat-label">Current File Size</div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <form method="GET" action="">
                    <div>
                        <label>Log File</label>
                        <select name="log">
                            <option value="current" <?php echo $selectedLog === 'current' ? 'selected' : ''; ?>>Today's Log</option>
                            <?php foreach ($logFiles as $file): ?>
                            <option value="<?php echo htmlspecialchars($file); ?>" <?php echo $selectedLog === $file ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($file); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label>Lines to Show</label>
                        <select name="lines">
                            <option value="50" <?php echo $lines == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $lines == 100 ? 'selected' : ''; ?>>100</option>
                            <option value="500" <?php echo $lines == 500 ? 'selected' : ''; ?>>500</option>
                            <option value="1000" <?php echo $lines == 1000 ? 'selected' : ''; ?>>1000</option>
                        </select>
                    </div>
                    
                    <div>
                        <label>Filter</label>
                        <input type="text" name="filter" value="<?php echo htmlspecialchars($filter); ?>" placeholder="Search logs...">
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn">Apply Filters</button>
                        <a href="view-logs.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
            
            <!-- Log Content -->
            <div class="log-container">
                <?php if (empty($logContent)): ?>
                <div style="text-align: center; padding: 40px; color: #718096;">
                    No log entries found
                </div>
                <?php else: ?>
                <?php foreach ($logContent as $line): ?>
                    <?php
                    // Parse log line
                    preg_match('/\[(.*?)\] \[(.*?)\] (.*)/', $line, $matches);
                    if ($matches) {
                        $time = $matches[1];
                        $level = $matches[2];
                        $message = $matches[3];
                        echo '<div class="log-line">';
                        echo '<span class="log-time">[' . htmlspecialchars($time) . ']</span> ';
                        echo '<span class="log-level-' . htmlspecialchars($level) . '">[' . htmlspecialchars($level) . ']</span> ';
                        echo '<span>' . htmlspecialchars($message) . '</span>';
                        echo '</div>';
                    } else {
                        echo '<div class="log-line">' . htmlspecialchars($line) . '</div>';
                    }
                    ?>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
