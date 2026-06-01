<?php
// FILE: dp.php - Works both locally and on Railway

// Check if running on Railway (has DATABASE_URL)
if (getenv('DATABASE_URL')) {
    // Parse Railway MySQL URL
    $dburl = parse_url(getenv('DATABASE_URL'));
    $host = $dburl['host'];
    $dbname = ltrim($dburl['path'], '/');
    $username = $dburl['user'];
    $password = $dburl['pass'];
    $port = $dburl['port'] ?? 3306;
} else {
    // Local development
    $host = 'localhost';
    $dbname = 'property_management_db';
    $username = 'root';
    $password = '';
    $port = 3306;
}

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log('DB Connection failed: ' . $e->getMessage());
    die('Database connection error. Please try again later.');
}
?>