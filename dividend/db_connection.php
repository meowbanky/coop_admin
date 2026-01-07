<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"

require_once __DIR__ . '/../config/EnvConfig.php';

// Load database configuration from .env
$dbConfig = EnvConfig::getDatabaseConfig();
$hostname = $dbConfig['host'];
$database = $dbConfig['name'];
$username = $dbConfig['user'];
$password = $dbConfig['password'];

// PDO connection
try {
    $pdo = new PDO("mysql:host={$hostname};dbname={$database};charset=utf8mb4", $username, $password, array(PDO::ATTR_PERSISTENT=>true));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn = $pdo; // Alias
} catch(PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    echo "Failed Connection: " . $e->getMessage();
    exit;
}
?>