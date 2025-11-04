<?php
// Database connection
// $host = 'localhost';
// $dbname = 'nishanaran';
// $username = 'nishan.aran';
// $password = 'GC9YWADC';
$host = 'localhost';
$dbname = 'careersync';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create tables if they don't exist
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        user_type ENUM('job_seeker', 'employer', 'admin') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employer_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        company VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        requirements TEXT NOT NULL,
        location VARCHAR(100) NOT NULL,
        type ENUM('Full-time', 'Part-time', 'Contract', 'Temporary', 'Internship', 'Remote') NOT NULL,
        category VARCHAR(50) NOT NULL,
        salary VARCHAR(50),
        posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at DATE,
        FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        user_id INT NOT NULL,
        cover_letter TEXT,
        resume_path VARCHAR(255),
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('Pending', 'Reviewed', 'Rejected', 'Accepted') DEFAULT 'Pending',
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS employer_profiles (
        user_id INT NOT NULL PRIMARY KEY,
        company_name VARCHAR(100) NOT NULL,
        company_description TEXT NOT NULL,
        website VARCHAR(255),
        industry VARCHAR(50),
        company_location VARCHAR(100),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    
    ";

    $pdo->exec($sql);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
