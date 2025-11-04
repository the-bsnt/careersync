<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isJobSeeker()) {
    header("Location: login.php");
    exit();
}

$pageTitle = 'Apply for Job - Career Sync';
$errors = [];
$success = false;

// Check if job ID is provided
if (!isset($_GET['job_id'])) {
    header("Location: browse-jobs.php");
    exit();
}

$jobId = $_GET['job_id'];
// Fetch job details
try {
    $stmt = $pdo->prepare("SELECT id, title, company FROM jobs WHERE id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        header("Location: browse-jobs.php");
        exit();
    }
} catch (PDOException $e) {
    $errors[] = "Error fetching job details: " . $e->getMessage();
}

// Check if already applied
try {
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
    $stmt->execute([$jobId, $_SESSION['user_id']]);
    $alreadyApplied = $stmt->fetch();

    if ($alreadyApplied) {
        $errors[] = "You have already applied for this position. Sorry, Application cannot be proceeded.";
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($errors)) {
            $_SESSION['msg'] = $errors[0];
            $_SESSION['msg_type'] = "error";
        }
        header("Location: browse-jobs.php");
        exit();
    }
} catch (PDOException $e) {
    $errors[] = "Error checking application: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $coverLetter = sanitizeInput($_POST['cover_letter']);

    // Handle file upload
    $resumePath = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (in_array($_FILES['resume']['type'], $allowedTypes) && $_FILES['resume']['size'] <= $maxSize) {
            $uploadDir = 'uploads/resumes/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = uniqid() . '_' . basename($_FILES['resume']['name']);
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetPath)) {
                $resumePath = $targetPath;
            } else {
                $errors[] = "Failed to upload resume.";
            }
        } else {
            $errors[] = "Invalid file type or size. Only PDF/DOC/DOCX files up to 2MB are allowed.";
        }
    } else {
        $errors[] = "Resume is required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO applications 
                (job_id, user_id, cover_letter, resume_path) 
                VALUES (?, ?, ?, ?)");

            $stmt->execute([
                $jobId,
                $_SESSION['user_id'],
                $coverLetter,
                $resumePath
            ]);

            $success = true;
        } catch (PDOException $e) {
            $errors[] = "Error submitting application: " . $e->getMessage();

            // Clean up uploaded file if database insert failed
            if ($resumePath && file_exists($resumePath)) {
                unlink($resumePath);
            }
        }
    }
}

include 'includes/header.php';
?>

<section class="apply-job">
    <h1>Apply for: <?php echo htmlspecialchars($job['title']); ?></h1>
    <h2>Company: <?php echo htmlspecialchars($job['company']); ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success):
        $_SESSION['msg'] = "Your application has been submitted successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: dashboard.php");
        exit();
    ?>
    <?php else: ?>
        <form id="applicationForm" method="post" action="apply-now.php?job_id=<?php echo $jobId; ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label for="resume">Upload Resume* (PDF, DOC, DOCX, max 2MB)</label>
                <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
            </div>

            <div class="form-group">
                <label for="cover_letter">Cover Letter</label>
                <textarea id="cover_letter" name="cover_letter" rows="8" placeholder="Explain why you're a good fit for this position..."><?php echo isset($coverLetter) ? htmlspecialchars($coverLetter) : ''; ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Submit Application</button>
                <a href="job-details.php?id=<?php echo $jobId; ?>" class="btn secondary">Cancel</a>
            </div>
        </form>
    <?php endif; ?>