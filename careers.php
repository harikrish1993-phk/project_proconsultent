<?php
/**
 * Public Careers Page
 * Displays all active public job postings
 */

require_once 'includes/config/config.php';
require_once 'includes/core/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get filter parameters
$location = $_GET['location'] ?? '';
$employment_type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = ["job_status = 'active'", "is_public = 1"];
$params = [];
$types = '';

if ($location) {
    $where_clauses[] = "location LIKE ?";
    $params[] = "%$location%";
    $types .= 's';
}

if ($employment_type) {
    $where_clauses[] = "employment_type = ?";
    $params[] = $employment_type;
    $types .= 's';
}

if ($search) {
    $where_clauses[] = "(job_title LIKE ? OR job_description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

$where = implode(' AND ', $where_clauses);

$query = "SELECT 
    j.job_id,
    j.job_ref,
    j.job_title,
    j.job_description,
    j.location,
    j.employment_type,
    j.remote_type,
    j.salary_min,
    j.salary_max,
    j.salary_currency,
    j.experience_level,
    j.created_at,
    c.company_name
FROM jobs j
LEFT JOIN companies c ON j.company_id = c.company_id
WHERE $where
ORDER BY j.created_at DESC";

$stmt = $conn->prepare($query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$jobs = $result->fetch_all(MYSQLI_ASSOC);

// Get distinct locations for filter
$locations_query = "SELECT DISTINCT location FROM jobs WHERE is_public = 1 AND job_status = 'active' ORDER BY location";
$locations = $conn->query($locations_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Careers - ProConsultancy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
        }
        
        .job-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid var(--primary-color);
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .badge-employment {
            background-color: #e7f3ff;
            color: #0d6efd;
        }
        
        .badge-remote {
            background-color: #d4edda;
            color: #155724;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">ProConsultancy</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="careers.php">Careers</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-3">Join Our Team</h1>
            <p class="lead mb-4">Discover exciting opportunities and build your career with us</p>
            <p class="h5"><?php echo count($jobs); ?> Open Position<?php echo count($jobs) !== 1 ? 's' : ''; ?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-light py-4">
        <div class="container">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search jobs..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="location">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc['location']); ?>" 
                                <?php echo $location === $loc['location'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc['location']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <option value="full-time" <?php echo $employment_type === 'full-time' ? 'selected' : ''; ?>>Full-time</option>
                        <option value="part-time" <?php echo $employment_type === 'part-time' ? 'selected' : ''; ?>>Part-time</option>
                        <option value="contract" <?php echo $employment_type === 'contract' ? 'selected' : ''; ?>>Contract</option>
                        <option value="temporary" <?php echo $employment_type === 'temporary' ? 'selected' : ''; ?>>Temporary</option>
                        <option value="internship" <?php echo $employment_type === 'internship' ? 'selected' : ''; ?>>Internship</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Jobs Listing -->
    <div class="container my-5">
        <?php if (empty($jobs)): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle me-2"></i>
                No jobs found matching your criteria. Try adjusting your filters.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($jobs as $job): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card job-card h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><?php echo htmlspecialchars($job['job_title']); ?></h5>
                                
                                <?php if ($job['company_name']): ?>
                                    <p class="text-muted mb-2">
                                        <i class="bi bi-building me-1"></i>
                                        <?php echo htmlspecialchars($job['company_name']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <p class="text-muted mb-3">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    <?php echo htmlspecialchars($job['location']); ?>
                                </p>
                                
                                <div class="mb-3">
                                    <span class="badge badge-employment me-1">
                                        <?php echo ucfirst(str_replace('-', ' ', $job['employment_type'])); ?>
                                    </span>
                                    <span class="badge badge-remote">
                                        <?php echo ucfirst($job['remote_type']); ?>
                                    </span>
                                </div>
                                
                                <p class="card-text text-muted small">
                                    <?php echo substr(strip_tags($job['job_description']), 0, 150); ?>...
                                </p>
                                
                                <?php if ($job['salary_min'] && $job['salary_max']): ?>
                                    <p class="text-success fw-bold mb-3">
                                        <?php echo $job['salary_currency']; ?> 
                                        <?php echo number_format($job['salary_min']); ?> - 
                                        <?php echo number_format($job['salary_max']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <a href="job-details.php?ref=<?php echo urlencode($job['job_ref']); ?>" 
                                   class="btn btn-primary btn-sm w-100">
                                    View Details & Apply
                                </a>
                            </div>
                            <div class="card-footer bg-light text-muted small">
                                Posted <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> ProConsultancy. All rights reserved.</p>
            <p class="small">
                <a href="privacy.php" class="text-white-50 me-3">Privacy Policy</a>
                <a href="terms.php" class="text-white-50">Terms of Service</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>