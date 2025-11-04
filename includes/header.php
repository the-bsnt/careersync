<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Job Portal'; ?></title>
    <meta name="description" content="Find your dream job or post job listings">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/managejobs.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/application-details.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage-application.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/job-details.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/setting.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/profile.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/form.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/browse-jobs.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dark-mode.css">
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/images/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="<?php echo BASE_URL; ?>">
                    <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Job Portal Logo" width="150">
                </a>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="<?php echo BASE_URL; ?>/admin/index.php">Index</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                    <?php else: ?>

                        <?php if (isLoggedIn()): ?>
                            <?php if (isEmployer()): ?>
                                <li><a href="<?php echo BASE_URL; ?>/post-job.php">Post Job</a></li>
                                <li><a href="<?php echo BASE_URL; ?>/manage-jobs.php">Manage Jobs</a></li>
                            <?php else: ?>
                                <li><a href="<?php echo BASE_URL; ?>/browse-jobs.php">Browse Jobs</a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo BASE_URL; ?>/dashboard.php">Dashboard</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/setting.php">Settings</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                        <?php else: ?>

                            <li><a href="<?php echo BASE_URL; ?>/browse-jobs.php">Browse Jobs</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/login.php">Login</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/register.php">Register</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>
            <button id="darkModeToggle" aria-label="Toggle dark mode">🌌</button>


        </div>
    </header>
    <main class="container">