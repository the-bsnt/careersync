<?php
require_once 'includes/config.php';

// Redirect if not logged in as employer
if (!isLoggedIn() || !isEmployer()) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$jobId = $_GET['job_id'] ?? null;
$errors = [];
$success = '';

// Handle application status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $applicationId = $_POST['application_id'];
    $newStatus = $_POST['status'];

    try {
        // Verify the application belongs to the employer's job
        $stmt = $pdo->prepare("UPDATE applications a
                              JOIN jobs j ON a.job_id = j.id
                              SET a.status = ?
                              WHERE a.id = ? AND j.employer_id = ?");
        $stmt->execute([$newStatus, $applicationId, $userId]);

        if ($stmt->rowCount() > 0) {
            $success = "Application status updated successfully";
        } else {
            $errors[] = "Application not found or you don't have permission";
        }
    } catch (PDOException $e) {
        $errors[] = "Error updating application: " . $e->getMessage();
    }
}

// Get applications for the employer's jobs
try {
    if ($jobId) {
        // Get applications for a specific job
        $stmt = $pdo->prepare("SELECT a.*, j.title as job_title, 
                              u.username as applicant_name, u.email as applicant_email,
                              p.full_name as applicant_full_name, p.phone as applicant_phone
                              FROM applications a
                              JOIN jobs j ON a.job_id = j.id
                              JOIN users u ON a.user_id = u.id
                              LEFT JOIN user_profiles p ON u.id = p.user_id
                              WHERE j.employer_id = ? AND j.id = ?
                              ORDER BY a.applied_at DESC");
        $stmt->execute([$userId, $jobId]);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get job title for heading
        $stmt = $pdo->prepare("SELECT title FROM jobs WHERE id = ? AND employer_id = ?");
        $stmt->execute([$jobId, $userId]);
        $jobTitle = $stmt->fetchColumn();
    } else {
        // Get all applications for all jobs
        $stmt = $pdo->prepare("SELECT a.*, j.title as job_title, 
                              u.username as applicant_name, u.email as applicant_email,
                              p.full_name as applicant_full_name, p.phone as applicant_phone
                              FROM applications a
                              JOIN jobs j ON a.job_id = j.id
                              JOIN users u ON a.user_id = u.id
                              LEFT JOIN user_profiles p ON u.id = p.user_id
                              WHERE j.employer_id = ?
                              ORDER BY a.applied_at DESC");
        $stmt->execute([$userId]);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $errors[] = "Error fetching applications: " . $e->getMessage();
}

$pageTitle = "Manage Applications";
include 'includes/header.php';
?>

<section class="manage-applications">
    <div class="section-header">
        <h1>
            <?php echo $jobId ? "Applications for: " . htmlspecialchars($jobTitle) : "All Applications"; ?>
        </h1>

        <?php if ($jobId): ?>
            <a href="manage-applications.php" class="btn secondary">View All Applications</a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
        <div class="alert success">
            <p><?php echo $success; ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($applications)): ?>
        <div class="applications-list">
            <?php foreach ($applications as $app): ?>
                <div class="application-card">
                    <div class="application-main">
                        <div class="applicant-info">
                            <h3>
                                <?php echo htmlspecialchars($app['applicant_full_name'] ?? $app['applicant_name']); ?>
                            </h3>
                            <p><?php echo htmlspecialchars($app['applicant_email']); ?></p>
                            <?php if (!empty($app['applicant_phone'])): ?>
                                <p><?php echo htmlspecialchars($app['applicant_phone']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="job-info">
                            <h4><?php echo htmlspecialchars($app['job_title']); ?></h4>
                            <p>Applied on <?php echo date('M j, Y', strtotime($app['applied_at'])); ?></p>
                        </div>
                    </div>

                    <div class="application-status">
                        <form method="post" class="status-form">
                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                            <select name="status" class="status-select <?php echo strtolower($app['status']); ?>">
                                <option value="Pending" <?php echo $app['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Reviewed" <?php echo $app['status'] === 'Reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                <option value="Accepted" <?php echo $app['status'] === 'Accepted' ? 'selected' : ''; ?>>Accepted</option>
                                <option value="Rejected" <?php echo $app['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                            <button type="submit" name="update_status" class="btn small">Update</button>
                        </form>
                    </div>

                    <div class="application-actions">
                        <a href="application-details.php?id=<?php echo $app['id']; ?>" class="btn small">View Details</a>
                        <?php if (!empty($app['resume_path'])): ?>
                            <a href="<?php echo htmlspecialchars($app['resume_path']); ?>" class="btn small" download>Download Resume</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-applications">
            <p>No applications found.</p>
            <a href="manage-jobs.php" class="btn">View Your Jobs</a>
        </div>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>