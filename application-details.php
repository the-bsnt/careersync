<?php
require_once 'includes/config.php';

// Redirect if not logged in as employer
if (!isLoggedIn() || !isEmployer()) {
    header("Location: login.php");
    exit();
}

$applicationId = $_GET['id'] ?? null;
$userId = $_SESSION['user_id'];
$errors = [];
$success = '';

// Handle application status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
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

// Get application details
try {
    $stmt = $pdo->prepare("SELECT a.*, j.title as job_title, j.company, j.id as job_id,
                          u.username as applicant_username, u.email as applicant_email,
                          p.full_name as applicant_name, p.phone as applicant_phone, 
                          p.education, p.experience, p.skills
                          FROM applications a
                          JOIN jobs j ON a.job_id = j.id
                          JOIN users u ON a.user_id = u.id
                          LEFT JOIN user_profiles p ON u.id = p.user_id
                          WHERE a.id = ? AND j.employer_id = ?");
    $stmt->execute([$applicationId, $userId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        $errors[] = "Application not found or you don't have permission to view it";
    }
} catch (PDOException $e) {
    $errors[] = "Error fetching application: " . $e->getMessage();
}

$pageTitle = "Application Details";
include 'includes/header.php';
?>

<section class="application-details">
    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert success">
            <p><?php echo $success; ?></p>
        </div>
    <?php endif; ?>

    <?php if ($application): ?>
        <div class="application-header">
            <div class="back-link">
                <a href="manage-applications.php<?php echo isset($_GET['job_id']) ? '?job_id=' . $_GET['job_id'] : ''; ?>" class="btn secondary">
                    &larr; Back to Applications
                </a>
            </div>

            <h1>Application for: <?php echo htmlspecialchars($application['job_title']); ?></h1>
            <h2><?php echo htmlspecialchars($application['company']); ?></h2>

            <div class="applicant-header">
                <div class="applicant-info">
                    <h3><?php echo htmlspecialchars($application['applicant_name'] ?? $application['applicant_username']); ?></h3>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($application['applicant_email']); ?></p>
                    <?php if (!empty($application['applicant_phone'])): ?>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($application['applicant_phone']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="application-status">
                    <form method="post" class="status-form">
                        <label for="status">Status:</label>
                        <select name="status" id="status" class="status-select <?php echo strtolower($application['status']); ?>">
                            <option value="Pending" <?php echo $application['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Reviewed" <?php echo $application['status'] === 'Reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                            <option value="Accepted" <?php echo $application['status'] === 'Accepted' ? 'selected' : ''; ?>>Accepted</option>
                            <option value="Rejected" <?php echo $application['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <button type="submit" name="update_status" class="btn">Update Status</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="application-content">
            <div class="application-section">
                <h3>Cover Letter</h3>
                <div class="cover-letter">
                    <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                </div>
            </div>

            <?php if (!empty($application['resume_path'])): ?>
                <div class="application-section">
                    <h3>Resume</h3>
                    <div class="resume-download">
                        <a href="<?php echo htmlspecialchars($application['resume_path']); ?>" class="btn" download>
                            Download Resume
                        </a>
                        <small>Last updated: <?php echo date('M j, Y', strtotime($application['applied_at'])); ?></small>
                    </div>
                </div>
            <?php endif; ?>

            <div class="applicant-details">
                <div class="detail-section">
                    <h3>Education</h3>
                    <?php if (!empty($application['education'])): ?>
                        <div class="detail-content">
                            <?php echo nl2br(htmlspecialchars($application['education'])); ?>
                        </div>
                    <?php else: ?>
                        <p class="no-info">No education information provided</p>
                    <?php endif; ?>
                </div>

                <div class="detail-section">
                    <h3>Experience</h3>
                    <?php if (!empty($application['experience'])): ?>
                        <div class="detail-content">
                            <?php echo nl2br(htmlspecialchars($application['experience'])); ?>
                        </div>
                    <?php else: ?>
                        <p class="no-info">No experience information provided</p>
                    <?php endif; ?>
                </div>

                <div class="detail-section">
                    <h3>Skills</h3>
                    <?php if (!empty($application['skills'])): ?>
                        <div class="skills-list">
                            <?php
                            $skills = explode(',', $application['skills']);
                            foreach ($skills as $skill):
                            ?>
                                <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-info">No skills information provided</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="application-meta">
                <div class="meta-item">
                    <strong>Applied:</strong>
                    <?php echo date('M j, Y \a\t g:i a', strtotime($application['applied_at'])); ?>
                </div>
                <?php if ($application['status'] !== 'Pending'): ?>
                    <div class="meta-item">
                        <strong>Last updated:</strong>
                        <?php echo date('M j, Y \a\t g:i a', strtotime($application['updated_at'] ?? $application['applied_at'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="application-actions">

            <?php
            $applicant_email = htmlspecialchars($application['applicant_email'], ENT_QUOTES, 'UTF-8');
            $subject = rawurlencode("Regarding Your Job Application");
            $body = rawurlencode("Hello,\n\nI would like to discuss your application.Please reply.");
            ?>

            <a href="mailto:<?php echo $applicant_email; ?>?subject=<?php echo $subject; ?>&body=<?php echo $body; ?>" class="btn">
                Contact Applicant
            </a>



        </div>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>