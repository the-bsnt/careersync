<?php
require_once 'includes/config.php';
$pageTitle = 'Job Details - Career Sync';

# If id is missing
if (!isset($_GET['id'])) {
    header("Location: browse-jobs.php");
    exit();
}
# To retrieve job id from get request
$jobId = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT j.*, u.username as employer FROM jobs j JOIN users u ON j.employer_id = u.id WHERE j.id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        header("Location: browse-jobs.php");
        exit();
    }
} catch (PDOException $e) {
    $error = "Error fetching job details: " . $e->getMessage();
}

include 'includes/header.php';
?>

<section class="job-details">
    <div class="job-header">
        <h1><?php echo htmlspecialchars($job['title']); ?></h1>
        <h2><?php echo htmlspecialchars($job['company']); ?></h2>
        <div class="job-meta">
            <span><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></span>
            <span><strong>Type:</strong> <?php echo htmlspecialchars($job['type']); ?></span>
            <span><strong>Category:</strong> <?php echo htmlspecialchars($job['category']); ?></span>
            <?php if (!empty($job['salary'])): ?>
                <span><strong>Salary:</strong> <?php echo htmlspecialchars($job['salary']); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="job-content">
        <div class="job-section">
            <h3>Job Description</h3>
            <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
        </div>

        <div class="job-section">
            <h3>Requirements</h3>
            <p><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
        </div>

        <div class="job-section">
            <h3>Additional Information</h3>
            <p><strong>Posted:</strong> <?php echo date('F j, Y', strtotime($job['posted_at'])); ?></p>
            <p><strong>Expires:</strong> <?php echo date('F j, Y', strtotime($job['expires_at'])); ?></p>
            <p><strong>Posted by:</strong> <?php echo htmlspecialchars($job['employer']); ?></p>
        </div>
    </div>

    <div class="job-actions">
        <?php if (isLoggedIn() && isJobSeeker()): ?>
            <a href="apply-now.php?job_id=<?php echo $job['id']; ?>" class="btn">Apply Now</a>
        <?php elseif (!isLoggedIn()): ?>
            <p>You need to <a href="login.php">login</a> as a job seeker to apply for this position.</p>
        <?php endif; ?>

        <?php if (isLoggedIn() && (isAdmin() || ($_SESSION['user_id'] == $job['employer_id']))): ?>
            <a href="post-job.php?edit=<?php echo $job['id']; ?>" class="btn secondary">Edit Job</a>
            <a href="manage-jobs.php" class="btn secondary">Back</a>
        <?php else: ?>
            <a href="browse-jobs.php" class="btn secondary">Back to Jobs</a>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>