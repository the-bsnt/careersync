<?php
require_once 'includes/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$pageTitle = 'Dashboard - Career Sync';
$userType = $_SESSION['user_type'];
$userId = $_SESSION['user_id'];

// To display session msg if any

if (isset($_SESSION['msg'])) {
    $type = $_SESSION['msg_type'] ?? 'info';
    $message = $_SESSION['msg'];

    echo "<div class='alert $type'>$message</div>";

    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
}
// Common variables
$userData = [];
$stats = [];
$recentActivity = [];

try {
    // Get user profile data
    $stmt = $pdo->prepare("SELECT u.username, u.email, u.created_at, p.full_name, p.phone 
                          FROM users u 
                          LEFT JOIN user_profiles p ON u.id = p.user_id 
                          WHERE u.id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Dashboard content based on user type
    switch ($userType) {
        case 'job_seeker':
            // Get applications stats
            $stmt = $pdo->prepare("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'Reviewed' THEN 1 ELSE 0 END) as reviewed,
                SUM(CASE WHEN status = 'Accepted' THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
                FROM applications WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get recent applications
            $stmt = $pdo->prepare("SELECT a.*, j.title as job_title, j.company 
                                  FROM applications a 
                                  JOIN jobs j ON a.job_id = j.id 
                                  WHERE a.user_id = ? 
                                  ORDER BY a.applied_at DESC 
                                  LIMIT 5");
            $stmt->execute([$userId]);
            $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'employer':
            // Get job stats
            $stmt = $pdo->prepare("SELECT 
                COUNT(*) as total_jobs,
                SUM(CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END) as active_jobs,
                SUM(CASE WHEN expires_at <= NOW() THEN 1 ELSE 0 END) as expired_jobs
                FROM jobs WHERE employer_id = ?");
            $stmt->execute([$userId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get application stats
            $stmt = $pdo->prepare("SELECT 
                COUNT(a.id) as total_applications,
                SUM(CASE WHEN a.status = 'Pending' THEN 1 ELSE 0 END) as pending_applications
                FROM applications a
                JOIN jobs j ON a.job_id = j.id
                WHERE j.employer_id = ?");
            $stmt->execute([$userId]);
            $appStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats = array_merge($stats, $appStats);

            // Get recent applications to your jobs
            $stmt = $pdo->prepare("SELECT a.*, j.title as job_title, u.username as applicant_name
                                  FROM applications a
                                  JOIN jobs j ON a.job_id = j.id
                                  JOIN users u ON a.user_id = u.id
                                  WHERE j.employer_id = ?
                                  ORDER BY a.applied_at DESC
                                  LIMIT 5");
            $stmt->execute([$userId]);
            $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'admin':
            // Get system stats
            $stats = [
                'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'total_jobs' => $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn(),
                'total_applications' => $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
                'active_jobs' => $pdo->query("SELECT COUNT(*) FROM jobs WHERE expires_at > NOW()")->fetchColumn()
            ];

            // Get recent activity
            $recentActivity = [
                'new_users' => $pdo->query("SELECT username, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC),
                'new_jobs' => $pdo->query("SELECT j.title, j.company, u.username as employer, j.posted_at 
                                          FROM jobs j JOIN users u ON j.employer_id = u.id 
                                          ORDER BY j.posted_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC)
            ];
            break;
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include 'includes/header.php';
?>

<section class="dashboard">
    <div class="dashboard-header">
        <h1>Welcome, <?php echo htmlspecialchars($userData['full_name'] ?? $userData['username']); ?></h1>
        <p>Member since <?php echo date('F Y', strtotime($userData['created_at'])); ?></p>
    </div>

    <div class="dashboard-content">
        <!-- Quick Stats Section -->
        <div class="dashboard-section stats-section">
            <h2>Your Stats</h2>
            <div class="stats-grid">
                <?php if ($userType === 'job_seeker'): ?>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['reviewed'] ?? 0; ?></div>
                        <div class="stat-label">Reviewed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['accepted'] ?? 0; ?></div>
                        <div class="stat-label">Accepted</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['rejected'] ?? 0; ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>

                <?php elseif ($userType === 'employer'): ?>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total_jobs'] ?? 0; ?></div>
                        <div class="stat-label">Total Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['active_jobs'] ?? 0; ?></div>
                        <div class="stat-label">Active Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['expired_jobs'] ?? 0; ?></div>
                        <div class="stat-label">Expired Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total_applications'] ?? 0; ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['pending_applications'] ?? 0; ?></div>
                        <div class="stat-label">Pending Applications</div>
                    </div>

                <?php elseif ($userType === 'admin'): ?>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total_users'] ?? 0; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total_jobs'] ?? 0; ?></div>
                        <div class="stat-label">Total Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['active_jobs'] ?? 0; ?></div>
                        <div class="stat-label">Active Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total_applications'] ?? 0; ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="dashboard-section activity-section">
            <h2>Recent Activity</h2>

            <?php if ($userType === 'job_seeker'): ?>
                <?php if (!empty($recentActivity)): ?>
                    <div class="activity-list">
                        <?php foreach ($recentActivity as $application): ?>
                            <div class="activity-item">
                                <div class="activity-main">
                                    <h3><?php echo htmlspecialchars($application['job_title']); ?></h3>
                                    <p><?php echo htmlspecialchars($application['company']); ?></p>
                                </div>
                                <div class="activity-meta">
                                    <span class="status-badge <?php echo strtolower($application['status']); ?>">
                                        <?php echo $application['status']; ?>
                                    </span>
                                    <span class="activity-date">
                                        Applied on <?php echo date('M j, Y', strtotime($application['applied_at'])); ?>
                                    </span>
                                </div>
                                <a href="job-details.php?id=<?php echo $application['job_id']; ?>" class="activity-link">View Job</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="section-actions">
                        <a href="applications.php" class="btn">View All Applications</a>
                    </div>
                <?php else: ?>
                    <p>You haven't applied to any jobs yet.</p>
                    <div class="section-actions">
                        <a href="browse-jobs.php" class="btn">Browse Jobs</a>
                    </div>
                <?php endif; ?>

            <?php elseif ($userType === 'employer'): ?>
                <?php if (!empty($recentActivity)): ?>
                    <div class="activity-list">
                        <?php foreach ($recentActivity as $application): ?>
                            <div class="activity-item">
                                <div class="activity-main">
                                    <h3><?php echo htmlspecialchars($application['applicant_name']); ?></h3>
                                    <p><?php echo htmlspecialchars($application['job_title']); ?></p>
                                </div>
                                <div class="activity-meta">
                                    <span class="status-badge <?php echo strtolower($application['status']); ?>">
                                        <?php echo $application['status']; ?>
                                    </span>
                                    <span class="activity-date">
                                        Applied on <?php echo date('M j, Y', strtotime($application['applied_at'])); ?>
                                    </span>
                                </div>
                                <a href="application-details.php?id=<?php echo $application['id']; ?>" class="activity-link">View Application</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="section-actions">
                        <a href="manage-applications.php" class="btn">Manage Applications</a>
                    </div>
                <?php else: ?>
                    <p>No recent applications to your jobs.</p>
                    <div class="section-actions">
                        <a href="post-job.php" class="btn">Post a Job</a>
                    </div>
                <?php endif; ?>

            <?php elseif ($userType === 'admin'): ?>
                <div class="admin-activity">
                    <div class="activity-column">
                        <h3>New Users</h3>
                        <?php if (!empty($recentActivity['new_users'])): ?>
                            <ul class="activity-list">
                                <?php foreach ($recentActivity['new_users'] as $user): ?>
                                    <li>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                        <span class="activity-date">Joined <?php echo date('M j', strtotime($user['created_at'])); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No recent users</p>
                        <?php endif; ?>
                    </div>

                    <div class="activity-column">
                        <h3>New Jobs</h3>
                        <?php if (!empty($recentActivity['new_jobs'])): ?>
                            <ul class="activity-list">
                                <?php foreach ($recentActivity['new_jobs'] as $job): ?>
                                    <li>
                                        <?php echo htmlspecialchars($job['title']); ?> at <?php echo htmlspecialchars($job['company']); ?>
                                        <span class="activity-date">Posted by <?php echo htmlspecialchars($job['employer']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No recent jobs</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="section-actions">
                    <a href="admin/" class="btn">Admin Panel</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions Section -->
        <div class="dashboard-section quick-actions">
            <h2>Quick Actions</h2>
            <div class="actions-grid">
                <?php if ($userType === 'job_seeker'): ?>
                    <a href="browse-jobs.php" class="action-card">
                        <div class="action-icon">🔍</div>
                        <h3>Browse Jobs</h3>
                        <p>Find your next opportunity</p>
                    </a>


                <?php elseif ($userType === 'employer'): ?>
                    <a href="post-job.php" class="action-card">
                        <div class="action-icon">➕</div>
                        <h3>Post a Job</h3>
                        <p>Find qualified candidates</p>
                    </a>
                    <a href="manage-jobs.php" class="action-card">
                        <div class="action-icon">📋</div>
                        <h3>Manage Jobs</h3>
                        <p>View and edit your listings</p>
                    </a>


                <?php elseif ($userType === 'admin'): ?>
                    <a href="admin/users.php" class="action-card">
                        <div class="action-icon">👥</div>
                        <h3>Manage Users</h3>
                        <p>View and edit all users</p>
                    </a>
                    <a href="admin/jobs.php" class="action-card">
                        <div class="action-icon">💼</div>
                        <h3>Manage Jobs</h3>
                        <p>View all job listings</p>
                    </a>
                    <a href="admin/settings.php" class="action-card">
                        <div class="action-icon">⚙️</div>
                        <h3>System Settings</h3>
                        <p>Configure the portal</p>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>