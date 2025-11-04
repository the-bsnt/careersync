<?php
require_once 'includes/config.php';
$pageTitle = 'Home';
include 'includes/header.php';

// Fetch featured jobs
try {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE expires_at > NOW() ORDER BY posted_at DESC LIMIT 6");
    $stmt->execute();
    $featuredJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching featured jobs: " . $e->getMessage();
}
?>

<section class="hero">
    <div class="hero-content">
        <h1>Find Your Dream Job Today</h1>
        <p>Thousands of jobs waiting for you. Insync your career with us.</p>
        <a href="browse-jobs.php" class="btn">Browse Jobs</a>
        <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn secondary">Register Now</a>
        <?php endif; ?>
    </div>
</section>

<section class="featured-jobs">
    <h2>Featured Jobs</h2>
    <div class="job-grid">
        <?php if (!empty($featuredJobs)): ?>
            <?php foreach ($featuredJobs as $job): ?>
                <div class="job-card">
                    <div class="job-card-header">
                        <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                        <p class="company"><?php echo htmlspecialchars($job['company']); ?></p>
                    </div>
                    <div class="job-card-body">
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($job['type']); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($job['category']); ?></p>
                    </div>
                    <div class="job-card-footer">
                        <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No featured jobs available at the moment.</p>
        <?php endif; ?>
    </div>
</section>

<section class="cta-section">
    <div class="cta-content">
        <?php if (isLoggedIn() && isEmployer()): ?>
            <h2>Ready to Post a Job?</h2>
            <a href="post-job.php" class="btn">Post a Job Now</a>
        <?php elseif (!isLoggedIn()): ?>
            <h2>Are You an Employer?</h2>
            <p>Post your job listings and find the perfect candidates.</p>
            <a href="register.php?type=employer" class="btn">Register as Employer</a>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>