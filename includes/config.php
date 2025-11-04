<?php
// Basic configuration
// session_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db.php';

// Define base URL
// define('BASE_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/~nishan.aran');
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/careersync');


// Helper functions
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isEmployer()
{
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employer';
}

function isJobSeeker()
{
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'job_seeker';
}

function isAdmin()
{
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function redirect($url)
{
    header("Location: $url");
    exit();
}

function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}
