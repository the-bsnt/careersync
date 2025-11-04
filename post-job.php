<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isEmployer()) {
    header("Location: login.php");
    exit();
}

$pageTitle = 'Post Job - Career Sync';
$errors = [];
$success = false;

// Check if editing existing job
$editMode = isset($_GET['edit']);
$jobId = $editMode ? $_GET['edit'] : null;
$jobData = [
    'title' => '',
    'company' => '',
    'description' => '',
    'requirements' => '',
    'location' => '',
    'type' => 'Full-time',
    'category' => '',
    'salary' => '',
    'expires_at' => date('Y-m-d', strtotime('+30 days'))
];

if ($editMode) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
        $stmt->execute([$jobId, $_SESSION['user_id']]);
        $jobData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$jobData) {
            header("Location: dashboard.php");
            exit();
        }
    } catch (PDOException $e) {
        $errors[] = "Error fetching job data: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $jobData['title'] = sanitizeInput($_POST['title']);
    $jobData['company'] = sanitizeInput($_POST['company']);
    $jobData['description'] = sanitizeInput($_POST['description']);
    $jobData['requirements'] = sanitizeInput($_POST['requirements']);
    $jobData['location'] = sanitizeInput($_POST['location']);
    $jobData['type'] = sanitizeInput($_POST['type']);
    $jobData['category'] = sanitizeInput($_POST['category']);
    $jobData['salary'] = sanitizeInput($_POST['salary']);
    $jobData['expires_at'] = sanitizeInput($_POST['expires_at']);

    // Validate required fields
    if (empty($jobData['title'])) $errors[] = "Job title is required";
    if (empty($jobData['company'])) $errors[] = "Company name is required";
    if (empty($jobData['description'])) $errors[] = "Job description is required";
    if (empty($jobData['requirements'])) $errors[] = "Job requirements are required";
    if (empty($jobData['location'])) $errors[] = "Location is required";
    if (empty($jobData['category'])) $errors[] = "Category is required";

    if (empty($errors)) {
        try {
            if ($editMode) {
                // Update existing job
                $stmt = $pdo->prepare("UPDATE jobs SET 
                    title = ?, company = ?, description = ?, requirements = ?, 
                    location = ?, type = ?, category = ?, salary = ?, expires_at = ?
                    WHERE id = ? AND employer_id = ?");

                $stmt->execute([
                    $jobData['title'],
                    $jobData['company'],
                    $jobData['description'],
                    $jobData['requirements'],
                    $jobData['location'],
                    $jobData['type'],
                    $jobData['category'],
                    $jobData['salary'],
                    $jobData['expires_at'],
                    $jobId,
                    $_SESSION['user_id']
                ]);

                $success = "Job updated successfully!";
            } else {
                // Insert new job
                $stmt = $pdo->prepare("INSERT INTO jobs 
                    (employer_id, title, company, description, requirements, 
                    location, type, category, salary, expires_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $_SESSION['user_id'],
                    $jobData['title'],
                    $jobData['company'],
                    $jobData['description'],
                    $jobData['requirements'],
                    $jobData['location'],
                    $jobData['type'],
                    $jobData['category'],
                    $jobData['salary'],
                    $jobData['expires_at']
                ]);

                $success = "Job posted successfully!";
                $jobId = $pdo->lastInsertId();
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<section class="post-job">
    <h1><?php echo $editMode ? 'Edit Job Posting' : 'Post a New Job'; ?></h1>

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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($success) {
            $_SESSION['msg'] = $success;
            $_SESSION['msg_type'] = "success";
        }
        header("Location: manage-jobs.php");
        exit();
    ?>


    <?php else: ?>
        <form id="jobForm" method="post" action="post-job.php<?php echo $editMode ? '?edit=' . $jobId : ''; ?>">
            <div class="form-group">
                <label for="title">Job Title*</label>
                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($jobData['title']); ?>">
            </div>

            <?php
            // to retrive company Name from employer profile if set.
            try {
                if (isEmployer()) {
                    $userId = $_SESSION['user_id'];
                    $stmt = $pdo->prepare("SELECT company_name FROM employer_profiles WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $employer = $stmt->fetch(PDO::FETCH_ASSOC);
                    $companyName = $employer['company_name'] ?? '';
                }
            } catch (PDOException $e) {
                die("Error fetching company name: " . $e->getMessage());
            } ?>


            <div class="form-group">
                <label for="company">Company Name*</label>
                <input type="text" id="company" name="company" required value="<?php echo htmlspecialchars($companyName); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="location">Location*</label>
                    <input type="text" id="location" name="location" required value="<?php echo htmlspecialchars($jobData['location']); ?>">
                </div>

                <div class="form-group">
                    <label for="type">Job Type*</label>
                    <select id="type" name="type" required>
                        <option value="Full-time" <?php echo $jobData['type'] === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                        <option value="Part-time" <?php echo $jobData['type'] === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                        <option value="Contract" <?php echo $jobData['type'] === 'Contract' ? 'selected' : ''; ?>>Contract</option>
                        <option value="Temporary" <?php echo $jobData['type'] === 'Temporary' ? 'selected' : ''; ?>>Temporary</option>
                        <option value="Internship" <?php echo $jobData['type'] === 'Internship' ? 'selected' : ''; ?>>Internship</option>
                        <option value="Remote" <?php echo $jobData['type'] === 'Remote' ? 'selected' : ''; ?>>Remote</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="category">Category*</label>
                    <input type="text" id="category" name="category" required value="<?php echo htmlspecialchars($jobData['category']); ?>">
                </div>

                <div class="form-group">
                    <label for="salary">Salary (optional)</label>
                    <input type="text" id="salary" name="salary" value="<?php echo htmlspecialchars($jobData['salary']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Job Description*</label>
                <textarea id="description" name="description" rows="6" required><?php echo htmlspecialchars($jobData['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="requirements">Requirements*</label>
                <textarea id="requirements" name="requirements" rows="6" required><?php echo htmlspecialchars($jobData['requirements']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="expires_at">Expiration Date*</label>
                <input type="date" id="expires_at" name="expires_at" required value="<?php echo htmlspecialchars($jobData['expires_at']); ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn"><?php echo $editMode ? 'Update Job' : 'Post Job'; ?></button>
                <a href="<?php echo $editMode ? 'manage-jobs.php' : 'dashboard.php'; ?>" class="btn secondary">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Client-side validation
        document.getElementById('jobForm').addEventListener('submit', function(e) {
            let valid = true;
            const requiredFields = ['title', 'company', 'description', 'requirements', 'location', 'category', 'expires_at'];

            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (!element.value.trim()) {
                    element.classList.add('error');
                    valid = false;
                } else {
                    element.classList.remove('error');
                }
            });

            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>