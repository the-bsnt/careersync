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

// Fetch current profile data
try {
    if ($userType === 'job_seeker') {
        $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    } elseif ($userType === 'employer') {
        $stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
    }
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $errors[] = "Error loading profile: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $data = [
        'user_id' => $userId
    ];

    // Job seeker specific fields
    if ($userType === 'job_seeker') {
        $data['full_name'] = sanitizeInput($_POST['full_name'] ?? '');
        $data['phone'] = sanitizeInput($_POST['phone'] ?? '');
        $data['address'] = sanitizeInput($_POST['address'] ?? '');
        $data['education'] = sanitizeInput($_POST['education'] ?? '');
        $data['experience'] = sanitizeInput($_POST['experience'] ?? '');
        $data['skills'] = sanitizeInput($_POST['skills'] ?? '');
    }
    // Employer specific fields
    elseif ($userType === 'employer') {
        $data['company_name'] = sanitizeInput($_POST['company_name'] ?? '');
        $data['company_description'] = sanitizeInput($_POST['company_description'] ?? '');
        $data['website'] = sanitizeInput($_POST['website'] ?? '');
        $data['industry'] = sanitizeInput($_POST['industry'] ?? '');
        $data['company_location'] = sanitizeInput($_POST['company_location'] ?? '');
    }

    // Validate required fields



    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if ($userType === 'job_seeker') {
                $stmt = $pdo->prepare("INSERT INTO user_profiles 
                    (user_id, full_name, phone, address, education, experience, skills) 
                    VALUES (:user_id, :full_name, :phone, :address, :education, :experience, :skills)
                    ON DUPLICATE KEY UPDATE
                    full_name = VALUES(full_name),
                    phone = VALUES(phone),
                    address = VALUES(address),
                    education = VALUES(education),
                    experience = VALUES(experience),
                    skills = VALUES(skills)");
            } elseif ($userType === 'employer') {
                $stmt = $pdo->prepare("INSERT INTO employer_profiles 
                    (user_id, company_name, company_description, website, industry, company_location) 
                    VALUES (:user_id, :company_name, :company_description, :website, :industry, :company_location)
                    ON DUPLICATE KEY UPDATE
                    company_name = VALUES(company_name),
                    company_description = VALUES(company_description),
                    website = VALUES(website),
                    industry = VALUES(industry),
                    company_location = VALUES(company_location)");
            }

            $stmt->execute($data);
            $pdo->commit();
            $success = "Profile updated successfully!";
            if ($success) {
                $_SESSION['msg'] = $success;
                $_SESSION['msg_type'] = "success";
            }
            header("Location: setting.php");
            exit();

            // Reload profile data
            if ($userType === 'job_seeker') {
                $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
            } else {
                $stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
            }
            $stmt->execute([$userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Error saving profile: " . $e->getMessage();
        }
    }
}

$pageTitle = "My Profile";
include 'includes/header.php';
?>

<section class="profile">
    <h1>My Profile</h1>

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

    <form method="post" class="profile-form">
        <?php if ($userType === 'job_seeker'): ?>
            <div class="form-section">
                <h2>Personal Information</h2>

                <div class="form-group">
                    <label for="full_name">Full Name*</label>
                    <input type="text" id="full_name" name="full_name" required
                        value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone"
                            value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address"
                            value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>">
                    </div>
                </div>
            </div>


            <div class="form-section">
                <h2>Professional Information</h2>

                <div class="form-group">
                    <label for="education">Education</label>
                    <textarea id="education" name="education" rows="4"><?php echo htmlspecialchars($profile['education'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="experience">Work Experience</label>
                    <textarea id="experience" name="experience" rows="4"><?php echo htmlspecialchars($profile['experience'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="skills">Skills (comma separated)</label>
                    <textarea id="skills" name="skills" rows="2"><?php echo htmlspecialchars($profile['skills'] ?? ''); ?>
                </textarea>
                </div>
            </div>

        <?php elseif ($userType === 'employer'): ?>
            <div class="form-section">
                <h2>Company Details</h2>

                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name"
                        value="<?php echo htmlspecialchars($profile['company_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="company_description">Company Description</label>
                    <textarea id="company_description" name="company_description" rows="4"><?php echo htmlspecialchars($profile['company_description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website"
                        value="<?php echo htmlspecialchars($profile['website'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="industry">Industry</label>
                        <input type="text" id="industry" name="industry"
                            value="<?php echo htmlspecialchars($profile['industry'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="company_location">Location</label>
                        <input type="text" id="company_location" name="company_location"
                            value="<?php echo htmlspecialchars($profile['company_location'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn">Save Profile</button>
        </div>
    </form>
</section>

<?php include 'includes/footer.php'; ?>