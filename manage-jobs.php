<?php
require_once 'includes/config.php';

// Redirect if not logged in as employer
if (!isLoggedIn() || !isEmployer()) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$errors = [];
$success = '';
// To display session msg if any

if (isset($_SESSION['msg'])) {
    $type = $_SESSION['msg_type'] ?? 'info';
    $message = $_SESSION['msg'];

    echo "<div class='alert $type'>$message</div>";

    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
}
// Handle the deletion of the job.
if (isset($_GET['delete'])) {
    $jobId = $_GET['delete'];

    try {
        // Verify that the job belongs to the employer before deleting it.
        $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND employer_id = ?");
        $stmt->execute([$jobId, $userId]);

        if ($stmt->rowCount() > 0) {
            $success = "Job deleted successfully";
            //To follow The POST–Redirect–GET (PRG) pattern

            // as session already started no need to start session.
            if ($success) {
                $_SESSION['msg'] = $success;
                $_SESSION['msg_type'] = "success";
            }
            header("Location: manage-jobs.php");
            exit();
            header("Location: manage-jobs.php");
            exit();
        } else {
            $errors[] = "Job not found or you don't have permission to delete it";
        }
    } catch (PDOException $e) {
        $errors[] = "Error deleting job: " . $e->getMessage();
    }
}

// Get all jobs posted by this employer
try {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE employer_id = ? ORDER BY posted_at DESC");
    $stmt->execute([$userId]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error fetching jobs: " . $e->getMessage();
}

$pageTitle = "Manage Jobs";
include 'includes/header.php';
?>

<section class="manage-jobs">
    <h1>Manage Your Job Postings</h1>

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



    <?php if (!empty($jobs)): ?>
        <div class="jobs-table">
            <div class="table-header">
                <div class="header-item">Job Title</div>
                <div class="header-item">Status</div>
                <div class="header-item">Posted Date</div>
                <div class="header-item">Deadline</div>
                <div class="header-item">Applications</div>
                <div class="header-item">Actions</div>

            </div>

            <?php foreach ($jobs as $job): ?>
                <?php
                // Count applications for this job
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
                    $stmt->execute([$job['id']]);
                    $applicationCount = $stmt->fetchColumn();
                } catch (PDOException $e) {
                    $applicationCount = 0;
                }

                // Determine job status
                // $isExpired = strtotime($job['expires_at']) < time();
                // $status = $job['status'];
                // $statusClass = 'expired';
                // if ($isExpired) {
                //     $status = 'Expired';
                // }
                // if ($status == 'Active') {
                //     $statusClass = 'active';
                // }

                $isExpired = strtotime($job['expires_at']) < time();
                $status = $isExpired ? 'Expired' : 'Active';
                $statusClass = $isExpired ? 'expired' : 'active';



                ?>



                <div class="table-row">
                    <div class="table-cell">
                        <a href="job-details.php?id=<?php echo $job['id']; ?>" class='link'>
                            <?php echo htmlspecialchars($job['title']); ?>
                        </a>
                        <div class="company"><?php echo htmlspecialchars($job['company']); ?></div>
                    </div>

                    <div class="table-cell">
                        <span class="status-badge <?php echo $statusClass; ?>">
                            <?php echo $status; ?>
                        </span>
                    </div>

                    <div class="table-cell">
                        <?php echo date('M j, Y', strtotime($job['posted_at'])); ?>
                    </div>

                    <div class="table-cell">
                        <?php echo date('M j, Y', strtotime($job['expires_at'])); ?>
                    </div>

                    <div class="table-cell">
                        <a href="manage-applications.php?job_id=<?php echo $job['id']; ?>">
                            <?php echo $applicationCount; ?> application(s)
                        </a>
                    </div>


                    <div class="table-cell actions">
                        <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn small safe">View</a>
                        <a href="post-job.php?edit=<?php echo $job['id']; ?>" class="btn small">Edit</a>

                        <a href="manage-jobs.php?delete=<?php echo $job['id']; ?>"
                            class="btn small danger"
                            onclick="return confirm('Are you sure you want to delete this job?');">
                            Delete
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="job-actions">
            <a href="post-job.php" class="btn">Post New Job</a>
        </div>
    <?php else: ?>
        <div class="no-jobs">
            <p>You haven't posted any jobs yet.</p>
            <a href="post-job.php" class="btn">Post Your First Job</a>
        </div>
    <?php endif; ?>

</section>

<?php include 'includes/footer.php'; ?>