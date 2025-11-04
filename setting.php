<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];
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
// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $confirm = $_POST['confirm_delete'] ?? '';

    if ($confirm !== 'DELETE') {
        $errors[] = "Please type DELETE to confirm account deletion";
    } else {
        try {
            $pdo->beginTransaction();

            // Delete user data based on their type
            if ($userType === 'job_seeker') {
                // Delete applications
                $stmt = $pdo->prepare("DELETE FROM applications WHERE user_id = ?");
                $stmt->execute([$userId]);

                // Delete profile
                $stmt = $pdo->prepare("DELETE FROM user_profiles WHERE user_id = ?");
                $stmt->execute([$userId]);
            } elseif ($userType === 'employer') {
                // Get all job IDs for this employer
                $stmt = $pdo->prepare("SELECT id FROM jobs WHERE employer_id = ?");
                $stmt->execute([$userId]);
                $jobIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($jobIds)) {
                    // Delete applications for these jobs
                    $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
                    $stmt = $pdo->prepare("DELETE FROM applications WHERE job_id IN ($placeholders)");
                    $stmt->execute($jobIds);

                    // Delete jobs
                    $stmt = $pdo->prepare("DELETE FROM jobs WHERE employer_id = ?");
                    $stmt->execute([$userId]);
                }

                // Delete employer profile
                $stmt = $pdo->prepare("DELETE FROM employer_profiles WHERE user_id = ?");
                $stmt->execute([$userId]);
            }


            // Finally delete the user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);

            $pdo->commit();

            // Logout and redirect
            session_destroy();
            header("Location: index.php?account_deleted=1");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Error deleting account: " . $e->getMessage();
        }
    }
}

// Handle data export request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_data'])) {
    try {
        $userData = [];

        // Get basic user info
        $stmt = $pdo->prepare("SELECT username, email, user_type, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData['account_info'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get profile data based on user type
        if ($userType === 'job_seeker') {
            $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $userData['profile'] = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT a.*, j.title as job_title, j.company 
                                  FROM applications a 
                                  JOIN jobs j ON a.job_id = j.id 
                                  WHERE a.user_id = ?");
            $stmt->execute([$userId]);
            $userData['applications'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($userType === 'employer') {
            $stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $userData['company_profile'] = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT * FROM jobs WHERE employer_id = ?");
            $stmt->execute([$userId]);
            $userData['jobs_posted'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Generate JSON file
        $jsonData = json_encode($userData, JSON_PRETTY_PRINT);
        $filename = "user_data_export_" . date('Y-m-d') . ".json";

        // Send as download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $jsonData;
        exit();
    } catch (PDOException $e) {
        $errors[] = "Error exporting data: " . $e->getMessage();
    }
}

$pageTitle = "Account Settings";
include 'includes/header.php';
?>

<section class="settings">
    <h1>Account Settings</h1>

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
    <!-- to retrive all user info from database -->
    <?php
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching user profile information: " . $e->getMessage());
    }
    ?>
    <!-- to retirve user profile info from database -->
    <?php
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // Now $user contains all columns: id, username, email, user_type, created_at, etc.
    } catch (PDOException $e) {
        die("Error fetching user data: " . $e->getMessage());
    }
    ?>
    <!-- to retirve company info from database -->
    <?php
    try {
        $stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching company data: " . $e->getMessage());
    }
    ?>

    <div class="settings-sections">
        <!-- Account Information Section -->
        <div class="settings-section">
            <h2><i class="fas fa-user-circle"></i> Account Information</h2>
            <div class="setting-info">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>User Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $user['user_type'])); ?></p>
                <p><strong>Member Since:</strong> <?php echo date('M j Y', strtotime($user['created_at'])); ?>
                </p>
            </div>
        </div>
        <!-- Profile Section -->
        <div class="settings-section">
            <h2><i class="fas fa-solid fa-user"></i></fa-solid>
                <?php echo isEmployer() ? "Company Profile" :  "User Profile" ?>
            </h2>

            <?php if (!$company && !$profile): ?>
                <p>Sorry, No data available.</p>
            <?php else: ?>
                <div class="setting-info">
                    <?php if (isEmployer() && $company): ?>
                        <p><strong>Employeer:</strong> <?php echo htmlspecialchars($profile['full_name']); ?></p>
                        <p><strong>Company:</strong> <?php echo nl2br(htmlspecialchars($company['company_name'])); ?></p>
                        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($company['company_description'])); ?></p>
                        <p><strong>Website:</strong> <?php echo nl2br(htmlspecialchars($company['website'])); ?></p>
                        <p><strong>Industry:</strong> <?php echo nl2br(htmlspecialchars($company['industry'])); ?></p>
                        <p><strong>Location:</strong> <?php echo nl2br(htmlspecialchars($company['company_location'])); ?></p>
                    <?php elseif (!isEmployer() && $profile): ?>
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($profile['full_name']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($profile['phone']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($profile['address']); ?></p>
                        <p><strong>Education:</strong> <?php echo nl2br(htmlspecialchars($profile['education'])); ?></p>
                        <p><strong>Experience:</strong> <?php echo nl2br(htmlspecialchars($profile['experience'])); ?></p>
                        <p><strong>Skills:</strong><?php echo strtoupper(str_replace(',', ',   ', $profile['skills'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <a href="profile.php" class="btn small">Edit Profile</a>
        </div>
        <!-- Data Export Section -->
        <div class="settings-section">
            <h2><i class="fas fa-file-export"></i> Data Export</h2>
            <p>Download the copy of User Data in JSON format.</p>
            <form method="post">
                <button type="submit" name="export_data" class="btn">
                    <i class="fas fa-download"></i> Export My Data
                </button>
            </form>
        </div>

        <!-- Account Deletion Section -->
        <div class="settings-section danger-zone">
            <h2><i class="fas fa-exclamation-triangle"></i> Danger Zone</h2>
            <p>Permanently delete your account and all associated data. This action cannot be undone.</p>

            <form method="post" id="deleteForm" onsubmit="return confirm('Are you absolutely sure? This will permanently delete ALL your data.');">
                <div class="form-group">
                    <label for="confirm_delete">
                        Type <strong>DELETE</strong> to confirm:
                    </label>
                    <input type="text" id="confirm_delete" name="confirm_delete"
                        placeholder="Type DELETE here" required>
                </div>
                <button type="submit" name="delete_account" class="btn danger">
                    <i class="fas fa-trash-alt"></i> Permanently Delete My Account
                </button>
            </form>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>