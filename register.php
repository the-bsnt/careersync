<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];
$success = false;
$userType = $_GET['type'] ?? 'job_seeker'; // Default to job seeker

// Allowed user types
$allowedTypes = ['job_seeker', 'employer'];
if (!in_array($userType, $allowedTypes)) {
    $userType = 'job_seeker';
}

$formData = [
    'username' => '',
    'email' => '',
    'full_name' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'username' => sanitizeInput($_POST['username']),
        'email' => sanitizeInput($_POST['email']),
        'password' => $_POST['password'],
        'password_confirm' => $_POST['password_confirm'],
        'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
        'user_type' => in_array($_POST['user_type'], $allowedTypes) ? $_POST['user_type'] : 'job_seeker'
    ];

    // Validation
    if (empty($formData['username'])) {
        $errors[] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $formData['username'])) {
        $errors[] = "Username must be 4-20 characters (letters, numbers, underscores)";
    }

    if (empty($formData['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($formData['password'])) {
        $errors[] = "Password is required";
    } elseif (strlen($formData['password']) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif ($formData['password'] !== $formData['password_confirm']) {
        $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$formData['username'], $formData['email']]);

            if ($stmt->rowCount() > 0) {
                $errors[] = "Username or email already exists";
            } else {
                // Hash password
                $hashedPassword = password_hash($formData['password'], PASSWORD_BCRYPT);

                // Begin transaction
                $pdo->beginTransaction();

                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $formData['username'],
                    $formData['email'],
                    $hashedPassword,
                    $formData['user_type']
                ]);

                $userId = $pdo->lastInsertId();

                // Insert profile if name provided
                if (!empty($formData['full_name'])) {
                    $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, full_name) VALUES (?, ?)");
                    $stmt->execute([$userId, $formData['full_name']]);
                }

                // Commit transaction
                $pdo->commit();

                // Auto-login after registration
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $formData['username'];
                $_SESSION['user_type'] = $formData['user_type'];
                $success = true;
                $_SESSION['success'] = "Registration successful! Welcome to Career Sync.";
                header("Location: dashboard.php");
                exit();
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Register - Career Sync';
include 'includes/header.php';
?>

<section class="auth-form">
    <h1><?php echo $userType === 'employer' ? 'Employer Registration' : 'Create Your Account'; ?></h1>

    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="register.php">
        <input type="hidden" name="user_type" value="<?php echo $userType; ?>">

        <div class="form-group">
            <label for="username">Username*</label>
            <input type="text" id="username" name="username" required
                value="<?php echo htmlspecialchars($formData['username']); ?>"
                pattern="[a-zA-Z0-9_]{4,20}" title="4-20 characters (letters, numbers, underscores)">
            <small>4-20 characters (letters, numbers, underscores)</small>
        </div>

        <div class="form-group">
            <label for="email">Email*</label>
            <input type="email" id="email" name="email" required
                value="<?php echo htmlspecialchars($formData['email']); ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password">Password*</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirm Password*</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
            </div>
        </div>

        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name"
                value="<?php echo htmlspecialchars($formData['full_name']); ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Register</button>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </form>

    <?php if ($userType === 'job_seeker'): ?>
        <div class="auth-options">
            <div class="option-separator">
                <span>or</span>
            </div>

            <div class="user-type-options">
                <p>Are you looking to hire?</p>
                <a href="register.php?type=employer" class="btn secondary">Register as Employer</a>
            </div>
        </div>
    <?php endif; ?>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            const strengthIndicator = document.createElement('div');
            strengthIndicator.className = 'password-strength';
            passwordInput.parentNode.appendChild(strengthIndicator);

            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;

                // Length check
                if (password.length >= 8) strength++;
                if (password.length >= 12) strength++;

                // Complexity checks
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;

                // Update indicator
                const strengthText = ['Very Weak', 'Weak', 'Moderate', 'Strong', 'Very Strong'][strength] || '';
                const strengthColors = ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#27ae60'];

                strengthIndicator.textContent = strengthText;
                strengthIndicator.style.color = strengthColors[strength] || '#000';
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>