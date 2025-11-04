<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }

    if (empty($errors)) {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, username, password, user_type FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                // Redirect based on user type
                $redirect = $user['user_type'] === 'admin' ? 'admin/' : 'dashboard.php';
                header("Location: $redirect");
                exit();
            } else {
                $errors[] = "Invalid username or password";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Login - Career Sync';
include 'includes/header.php';
?>

<section class="auth-form">
    <h1>Login to Your Account</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <div class="form-group">
            <label for="username">Username or Email</label>
            <input type="text" id="username" name="username" required
                value="<?php echo htmlspecialchars($username); ?>">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <small><a href="#">Forgot password?</a></small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Login</button>
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </form>

    <div class="auth-options">
        <div class="option-separator">
            <span>or</span>
        </div>

        <div class="user-type-options">
            <p>Are you looking to hire?</p>
            <a href="register.php?type=employer" class="btn secondary">Register as Employer</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>