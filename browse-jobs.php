<?php
require_once 'includes/config.php';
$pageTitle = 'Browse Jobs - Career Sync';
include 'includes/header.php';

// To display session msg if any


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['msg'])) {
    $type = $_SESSION['msg_type'] ?? 'info';
    $message = $_SESSION['msg'];

    echo "<div class='alert $type'>$message</div>";

    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
}
// Initialize filters
$category = $_GET['category'] ?? '';
$location = $_GET['location'] ?? '';
$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';





// Build query
$query = "SELECT * FROM jobs WHERE expires_at > NOW()";
$params = [];

if (!empty($category)) {
    $query .= " AND category = :category";
    $params[':category'] = $category;
}

if (!empty($location)) {
    $query .= " AND location LIKE :location";
    $params[':location'] = "%$location%";
}

if (!empty($type)) {
    $query .= " AND type = :type";
    $params[':type'] = $type;
}

if (!empty($search)) {
    $query .= " AND (title LIKE :search OR company LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY posted_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching jobs: " . $e->getMessage();
}

// Get unique categories, locations, and types for filters
try {
    $categories = $pdo->query("SELECT DISTINCT category FROM jobs ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
    $locations = $pdo->query("SELECT DISTINCT location FROM jobs ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);
    $types = $pdo->query("SELECT DISTINCT type FROM jobs ORDER BY type")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error = "Error fetching filter options: " . $e->getMessage();
}
?>

<section class="job-filters">
    <h2>Browse Jobs</h2>
    <form id="filterForm" method="get" action="browse-jobs.php">
        <div class="filter-group">
            <label for="search">Search:</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Job title, company, keywords">
        </div>
        <div class="filter-row">
            <div class="filter-group">
                <label for="category">Category:</label>
                <select id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="location">Location:</label>
                <select id="location" name="location">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $location === $loc ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="type">Job Type:</label>
                <select id="type" name="type">
                    <option value="">All Types</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $type === $t ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn">Filter Jobs</button>
        <button type="button" id="resetFilters" class="btn secondary">Reset Filters</button>
    </form>
</section>

<section class="job-listings">
    <?php if (!empty($jobs)): ?>
        <div class="job-grid">
            <?php foreach ($jobs as $job): ?>
                <div class="job-card">
                    <div class="job-card-header">
                        <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                        <p class="company"><?php echo htmlspecialchars($job['company']); ?></p>
                    </div>
                    <div class="job-card-body">
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($job['type']); ?></p>
                        <p><strong>Posted:</strong> <?php echo date('M j, Y', strtotime($job['posted_at'])); ?></p>
                    </div>
                    <div class="job-card-footer">
                        <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="no-results">No jobs found matching your criteria. Try adjusting your filters.</p>
    <?php endif; ?>
</section>

<script>
    document.getElementById('resetFilters').addEventListener('click', function() {
        document.getElementById('search').value = '';
        document.getElementById('category').selectedIndex = 0;
        document.getElementById('location').selectedIndex = 0;
        document.getElementById('type').selectedIndex = 0;
        document.getElementById('filterForm').submit();
    });
</script>

<?php include 'includes/footer.php'; ?>