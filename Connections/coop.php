<?php
require_once __DIR__ . '/../config/EnvConfig.php';

$dbConfig = EnvConfig::getDatabaseConfig();

// Legacy variables for backward compatibility
$hostname = $dbConfig['host'];
$database = $dbConfig['name'];
$username = $dbConfig['user'];
$password = $dbConfig['password'];

try {
    $conn = new PDO(
        "mysql:host={$hostname};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT         => true,
        ]
    );
    
    // Alias for backward compatibility with code using $coop
    $coop = $conn;
    
} catch (PDOException $e) {
    error_log('DB Connection Error: ' . $e->getMessage());
    die('Database connection failed.');
}