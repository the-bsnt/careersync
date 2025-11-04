<?php
require_once '../includes/config.php';

// Redirect if not logged in as admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Admin Dashboard";
include '../includes/header.php';

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $userId = (int)$_GET['delete_user'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        echo "<p class='alert success'>User deleted successfully.</p>";
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        echo "<p class='alert error'>Error deleting user: " . $e->getMessage() . "</p>";
    }
}

// Fetch statistics
try {
    $userStats = $pdo->query("SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN user_type = 'job_seeker' THEN 1 ELSE 0 END) as job_seekers,
        SUM(CASE WHEN user_type = 'employer' THEN 1 ELSE 0 END) as employers,
        SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as admins
    FROM users")->fetch();

    $appStats = $pdo->query("SELECT COUNT(*) as total_applications FROM applications")->fetch();

    $allUsers = $pdo->query("SELECT id, username, user_type, created_at FROM users WHERE user_type <> 'admin' ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="admin-dashboard">
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>

    <div class="stats">
        <h2>Statistics</h2>
        <ul>
            <li><strong>Total Users:</strong> <?php echo $userStats['total_users']; ?></li>
            <li><strong>Job Seekers:</strong> <?php echo $userStats['job_seekers']; ?></li>
            <li><strong>Employers:</strong> <?php echo $userStats['employers']; ?></li>
            <li><strong>Admins:</strong> <?php echo $userStats['admins']; ?></li>
            <li><strong>Total Applications:</strong> <?php echo $appStats['total_applications']; ?></li>
        </ul>
    </div>

    <div class="user-management">
        <h2>Manage Users</h2>
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>User Type</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allUsers as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        <td>
                            <a href="?delete_user=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');" style="color:red;">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>