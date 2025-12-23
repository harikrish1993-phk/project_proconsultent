<?php
/**
 * PUBLIC CAREERS PAGE
 * Simple job listings for public visitors
 * BUSINESS RULES: Show only public jobs, hide client info, hide salary, Belgium location only
 */

require_once __DIR__ . '/includes/config/config.php';
require_once __DIR__ . '/includes/core/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch public jobs
$sql = "SELECT job_ref, job_title, job_description, skills_required, experience_level, created_at
        FROM jobs 
        WHERE is_public = 1 AND job_status = 'active' AND job_type = 'freelance'
        ORDER BY created_at DESC";

$result = $conn->query($sql);
$jobs = [];
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Careers - <?php echo COMPANY_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; color: #2d3748; }
        .header { background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.08); padding: 20px 0; position: sticky; top: 0; z-index: 100; }
        .header-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: 700; color: #667eea; text-decoration: none; }
        .nav { display: flex; gap: 25px; align-items: center; }
        .nav a { color: #4a5568; text-decoration: none; font-weight: 500; transition: color 0.3s; }
        .nav a:hover { color: #667eea; }
        .staff-login-btn { background: linear-gradient(135deg, #667eea, #764ba2); color: white !important; padding: 8px 18px; border-radius: 6px; font-size: 14px; }
        .hero { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 60px 20px; text-align: center; }
        .hero h1 { font-size: 42px; margin-bottom: 15px; font-weight: 700; }
        .hero p { font-size: 18px; opacity: 0.95; }
        .container { max-width: 1200px; margin: 0 auto; padding: 50px 20px; }
        .section-title { font-size: 32px; margin-bottom: 40px; text-align: center; }
        .jobs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
        .job-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.07); transition: all 0.3s; border: 2px solid transparent; }
        .job-card:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(0,0,0,0.15); border-color: #667eea; }
        .job-title { font-size: 20px; font-weight: 700; color: #2d3748; margin-bottom: 12px; }
        .job-description { color: #4a5568; font-size: 14px; margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .job-skills { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 15px; }
        .skill-tag { background: #e6f0ff; color: #667eea; padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: 600; }
        .job-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-top: 12px; border-top: 1px solid #e2e8f0; }
        .experience-badge { background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 5px; font-size: 12px; font-weight: 600; }
        .job-date { color: #718096; font-size: 12px; }
        .apply-btn { display: block; width: 100%; padding: 11px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; text-align: center; transition: all 0.3s; }
        .apply-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4); }
        .no-jobs { text-align: center; padding: 60px 20px; }
        .no-jobs i { font-size: 70px; color: #cbd5e0; margin-bottom: 15px; }
        .footer { background: #2d3748; color: white; padding: 30px 20px; text-align: center; margin-top: 60px; }
        @media (max-width: 768px) { .hero h1 { font-size: 28px; } .jobs-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <a href="/" class="logo"><?php echo COMPANY_NAME; ?></a>
            <nav class="nav">
                <a href="/">Home</a>
                <a href="careers.php">Careers</a>
                <a href="login.php" class="staff-login-btn"><i class="fa-solid fa-user-lock"></i> Staff Login</a>
            </nav>
        </div>
    </header>

    <section class="hero">
        <h1>Join Our Network</h1>
        <p>Discover exciting freelance opportunities with leading companies in Belgium</p>
    </section>

    <div class="container">
        <h2 class="section-title">Available Positions</h2>

        <?php if (count($jobs) > 0): ?>
            <div class="jobs-grid">
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <h3 class="job-title"><?php echo htmlspecialchars($job['job_title']); ?></h3>
                        <p class="job-description">
                            <?php echo htmlspecialchars(substr(strip_tags($job['job_description']), 0, 140)) . '...'; ?>
                        </p>
                        <?php if (!empty($job['skills_required'])): ?>
                            <div class="job-skills">
                                <?php foreach (array_slice(explode(',', $job['skills_required']), 0, 4) as $skill): ?>
                                    <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="job-meta">
                            <?php if ($job['experience_level']): ?>
                                <span class="experience-badge"><?php echo htmlspecialchars($job['experience_level']); ?></span>
                            <?php endif; ?>
                            <span class="job-date"><i class="fa-regular fa-clock"></i> <?php echo date('M j', strtotime($job['created_at'])); ?></span>
                        </div>
                        <a href="job-details.php?ref=<?php echo urlencode($job['job_ref']); ?>" class="apply-btn">
                            View & Apply <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-jobs">
                <i class="fa-solid fa-briefcase"></i>
                <h3>No Open Positions</h3>
                <p>Check back later for new opportunities.</p>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?>. All rights reserved.</p>
    </footer>
</body>
</html>
