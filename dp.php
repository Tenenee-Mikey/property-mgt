<?php
// FILE: dp.php - Works on local (MySQL) and Render (PostgreSQL)

// Check if running on Render (has DATABASE_URL)
if (getenv('DB_URL')) {
    // Render PostgreSQL connection
    $dburl = parse_url(getenv('DB_URL'));
    $host = $dburl['host'];
    $dbname = ltrim($dburl['path'], '/');
    $username = $dburl['user'];
    $password = $dburl['pass'];
    $port = $dburl['port'] ?? 5432;
    
    try {
        $pdo = new PDO(
            "pgsql:host=$host;port=$port;dbname=$dbname",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        die('Database connection error: ' . $e->getMessage());
    }
} else {
    // Local XAMPP MySQL connection
    $host = 'localhost';
    $dbname = 'property_management_db';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        die('Database connection error: ' . $e->getMessage());
    }
}
?>
